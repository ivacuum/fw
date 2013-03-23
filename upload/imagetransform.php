<?php
/**
* @package fw
* @copyright (c) 2012
*/

namespace fw\upload;

/**
* Преобразование изображений
*/
class imagetransform
{
	public $source;
	
	public $filesize = 0;
	public $mimetype;
	public $height = 0;
	public $width = 0;
	
	public $error = [];
	
	private $init_error = false;
	
	function __construct($source)
	{
		if (!file_exists($source) || (false === $image_info = @getimagesize($source)))
		{
			$this->init_error = true;
			return false;
		}

		list($this->width, $this->height, $this->mimetype, ) = @getimagesize($source);
		$this->source = $source;
		$this->filesize = @filesize($source);
	}
	
	/**
	* Создание уменьшенной копии изображения
	*/
	public function make_thumbnail($max_width, $destination)
	{
		if ($this->init_error)
		{
			return false;
		}
		
		/* Если размеры исходника меньше желаемых, то будет создана полная копия изображения */
		if ($this->width <= $max_width && $this->height <= $max_width)
		{
			if ($this->source == $destination)
			{
				return true;
			}
			
			return @copy($this->source, $destination);
		}
		
		list($width, $height) = $this->get_thumbnail_dimensions($max_width);

		if ($this->mimetype == 'image/gif')
		{
			/* -sample не нарушает gif-анимацию */
			@passthru(sprintf('%sgm convert "%s" -sample %dx%d +profile "*" "%s"', escapeshellcmd('/usr/local/bin/'), $this->source, $width, $height, $destination));
		}
		else
		{
			/* -size ускоряет создание превью, фильтр немного замыливает изображение */
			@passthru(sprintf('%sgm convert -size %dx%d "%s" -quality %d -filter triangle -resize %dx%d +profile "*" "%s"', escapeshellcmd('/usr/local/bin/'), $width, $height, $this->source, 75, $width, $height, $destination));
		}

		if (!file_exists($destination))
		{
			return false;
		}

		chmod($destination, 0666);

		return true;
	}
	
	/**
	* Добавление водяного знака
	*/
	public function set_watermark($watermark, $position = false)
	{
		global $app;
		
		if ($this->init_error || !$watermark)
		{
			$this->error[] = 'Ошибка инициализация модуля наложения водяного знака';
			return false;
		}
		
		$sql = '
			SELECT
				*
			FROM
				' . IMAGE_WATERMARKS_TABLE . '
			WHERE
				wm_file = ' . $app['db']->check_value($watermark);
		$app['db']->query($sql);
		$row = $app['db']->fetchrow();
		$app['db']->freeresult();
		
		if (!$row)
		{
			$this->error[] = 'Водяной знак не найден';
			return false;
		}
		
		$watermark = sprintf('%swatermark_%s.png', $app['config']['watermarks.dir'], $watermark);
		
		if (!file_exists($watermark))
		{
			$this->error[] = 'Изображение с водяным знаком не найдено';
			return false;
		}
		
		switch ($position)
		{
			case 'northwest':
			case 'north':
			case 'northeast':
			case 'west':
			case 'center':
			case 'east':
			case 'southwest':
			case 'south':
			case 'southeast':

			break;
			default:

				$position = 'southeast';

			break;
		}

		if ($this->width < $row['wm_width'] || $this->height < $row['wm_height'])
		{
			$this->error[] = 'Размеры изображения меньше размеров водяного знака';
			return false;
		}
		
		@passthru(sprintf('%sgm composite -dissolve 80 -geometry +7+7 -gravity %s "%s" "%s" "%s"', escapeshellcmd('/usr/local/bin/'), $position, $watermark, $this->source, $this->source));
		
		return true;
	}
	
	/**
	* Пропорции превью
	*/
	private function get_thumbnail_dimensions($max_width)
	{
		if ($this->width > $this->height)
		{
			return [
				round($this->width * ($max_width / $this->width)),
				round($this->height * ($max_width / $this->width))
			];
		}
		
		return [
			round($this->width * ($max_width / $this->height)),
			round($this->height * ($max_width / $this->height))
		];
	}
}
