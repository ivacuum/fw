<?php
/**
* @package src.ivacuum.ru
* @copyright (c) 2012 vacuum
*/

namespace engine\upload;

/**
* Загрузка файлов
*/
class fileupload
{
	public $max_filesize = 0;
	public $max_height = 0;
	public $max_width = 0;
	public $min_height = 0;
	public $min_width = 0;

	private $allowed_extensions = array();
	private $disallowed_content = array('body', 'head', 'html', 'img', 'plaintext', 'a href', 'pre', 'script', 'table', 'title');

	/**
	* Конструктор класса
	*
	* @param	array	$allowed_extensions		Массив расширений файлов, разрешенных к загрузке
	* @param	int		$max_filesize			Максимально допустимый размер загружаемого файла
	* @param	int		$min_width				Минимальная ширина изображения
	* @param	int		$min_height				Минимальная высота изображения
	* @param	int		$max_width				Максимальная ширина изображения
	* @param	int		$max_height				Максимальная высота изображения
	* @param	bool	$disallowed_content		Проверять ли заголовок сообщения на наличие запрещенных тэгов
	*/
	function __construct($allowed_extensions = false, $max_filesize = false, $min_width = false, $min_height = false, $max_width = false, $max_height = false, $disallowed_content = false)
	{
		$this->set_allowed_extensions($allowed_extensions);
		$this->set_max_filesize($max_filesize);
		$this->set_allowed_dimensions($min_width, $min_height, $max_width, $max_height);
		$this->set_disallowed_content($disallowed_content);
	}

	/**
	* Прием загрузки файлов из формы
	*/
	public function form_upload($form_name)
	{
		unset($_FILES[$form_name]['local_mode']);
		$file = new filespec($_FILES[$form_name], $this);

		if( $file->init_error )
		{
			$file->error[] = 'Ошибка инициализации загрузки';
			return $file;
		}

		if( isset($_FILES[$form_name]['error']) )
		{
			if( false !== $error = $this->assign_internal_error($_FILES[$form_name]['error']) )
			{
				$file->error[] = $error;
				return $file;
			}
		}

		/* Проверяем не загружен ли пустой файл */
		if( isset($_FILES[$form_name]['size']) && 0 == $_FILES[$form_name]['size'] )
		{
			$file->error[] = 'Файл пуст';
			return $file;
		}

		/* Превышен максимальный размер файла */
		if( $file->get('filename') == 'none' )
		{
			$file->error[] = 'Превышен максимальный размер файла';
			return $file;
		}

		if( !$file->is_uploaded() )
		{
			$file->error[] = 'Файл не загружен';
			return $file;
		}

		$this->common_checks($file);

		return $file;
	}

	/**
	* Типы изображений
	*/
	public function image_types()
	{
		return array(
			1 => array('gif'),
			2 => array('jpg', 'jpeg'),
			3 => array('png')
		);
	}

	/**
	* Сброс значений переменных
	*/
	public function reset_vars()
	{
		$this->max_filesize = $this->min_width = $this->min_height = $this->max_width = $this->max_height = 0;
		$this->allowed_extensions = $this->disallowed_content = array();
	}

	/**
	* Установка допустимых размеров изображения
	*/
	public function set_allowed_dimensions($min_width, $min_height, $max_width, $max_height)
	{
		$this->min_width = (int) $min_width;
		$this->min_height = (int) $min_height;
		$this->max_width = (int) $max_width;
		$this->max_height = (int) $max_height;
	}

	/**
	* Установка разрешенных к загрузке расширений файлов
	*/
	public function set_allowed_extensions($allowed_extensions)
	{
		if( false !== $allowed_extensions && is_array($allowed_extensions) )
		{
			$this->allowed_extensions = $allowed_extensions;
		}
	}

	/**
	* Список запрещенных тэгов для проверки
	*/
	public function set_disallowed_content($disallowed_content)
	{
		if( false !== $disallowed_content && is_array($disallowed_content) )
		{
			$this->disallowed_content = array_diff($disallowed_content, array(''));
		}
	}

	/**
	* Установка максимального размера файла, разрешенного к загрузке
	*/
	public function set_max_filesize($max_filesize)
	{
		if( false !== $max_filesize && (int) $max_filesize )
		{
			$this->max_filesize = (int) $max_filesize;
		}
	}

	/**
	* Проверяем допустимые ли размеры изображения
	*/
	public function valid_dimensions(&$file)
	{
		if( !$this->max_width && !$this->max_height && !$this->min_width && !$this->min_height )
		{
			return true;
		}

		$height = $file->get('height');
		$width  = $file->get('width');

		if( ($this->max_width && $width > $this->max_width) ||
			($this->max_height && $height > $this->max_height) ||
			($this->min_width && $width < $this->min_width) ||
			($this->min_height && $height < $this->min_height) )
		{
			return false;
		}

		return true;
	}

	/**
	* Внутренняя ошибка загрузки
	*/
	private function assign_internal_error($error_code)
	{
		switch( $error_code )
		{
			case 1: return 'Превышен допустимый размер файла';
			case 2: return 'Превышен допустимый размер файла';
			case 3: return 'Файл был загружен частично';
			case 4: return 'Файл не загружен';
			case 6: return 'Временная папка не найдена';
			case 7: return 'Ошибка записи файла на диск';
		}
		
		return false;
	}

	/**
	* Общие проверки
	*/
	private function common_checks(&$file)
	{
		if( $this->max_filesize && ($file->get('filesize') > $this->max_filesize || 0 == $file->get('filesize')) )
		{
			$file->error[] = 'Превышен максимальный размер файла';
		}

		if( preg_match('#[\\/:*?\"<>|]#i', $file->get('realname')) )
		{
			$file->error[] = 'Имя файла содержит недопустимые символы';
		}

		if( !$this->valid_extension($file) )
		{
			$file->error[] = 'Файлы данного формата запрещены к загрузке';
		}

		if( !$this->valid_content($file) )
		{
			$file->error[] = 'Файл содержит запрещенные тэги в заголовочной информации';
		}
	}

	/**
	* Нет ли запрещенных тэгов в заголовочной информации
	*/
	private function valid_content(&$file)
	{
		return $file->check_content($this->disallowed_content);
	}

	/**
	* Допустимое ли расширение
	*/
	private function valid_extension(&$file)
	{
		return in_array($file->get('extension'), $this->allowed_extensions);
	}
}
