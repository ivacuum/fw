<?php
/**
* @package fw
* @copyright (c) 2014
*/

namespace fw\upload;

/**
* Информация о загруженном файле
*/
class filespec
{
	var $extension = '';
	var $filename = '';
	var $filesize = 0;
	var $image_info = [];
	var $mimetype = '';
	var $realname = '';
	var $uploadname = '';
	
	var $height = 0;
	var $width = 0;

	var $destination_file = '';
	var $destination_path = '';

	var $file_moved = false;
	var $init_error = false;
	var $local = false;

	var $error = [];
	var $upload = '';

	/**
	* Конструктор
	*/
	function __construct($upload_ary, $upload_namespace)
	{
		if (!isset($upload_ary)) {
			$this->init_error = true;
			return;
		}

		$this->filename = $upload_ary['tmp_name'];
		$this->filesize = $upload_ary['size'];
		$name = trim(htmlspecialchars(basename($upload_ary['name'])));
		$this->realname = $this->uploadname = $name;
		$this->mimetype = $upload_ary['type'];

		/* Опера добавляет имя к mime типу */
		$this->mimetype = false !== strpos($this->mimetype, '; name') ? str_replace(strstr($this->mimetype, '; name'), '', $this->mimetype) : $this->mimetype;

		if (!$this->mimetype) {
			$this->mimetype = 'application/octetstream';
		}

		$this->extension = strtolower($this->get_extension($this->realname));

		/* Пытаемся получить размер файла, загруженного во временную папку php */
		$this->filesize = @filesize($this->filename) ? @filesize($this->filename) : $this->filesize;

		$this->height = $this->width = 0;
		$this->file_moved = false;

		$this->local = isset($upload_ary['local_mode']);
		$this->upload = $upload_namespace;
	}

	/**
	* Проверка заголовочной информации файла (первых 256 байт)
	* на наличие вредоносного кода (запрещенных html-тэгов)
	*/
	public function check_content($disallowed_content)
	{
		if (empty($disallowed_content)) {
			return true;
		}

		if (false !== $fp = @fopen($this->filename, 'rb')) {
			$ie_mime_relevant = fread($fp, 256);
			fclose($fp);

			foreach ($disallowed_content as $forbidden) {
				if (false !== stripos($ie_mime_relevant, '<' . $forbidden )) {
					return false;
				}
			}
		}

		return true;
	}
	
	/**
	* Обработка имени будущего файла
	*/
	public function clean_filename($mode = 'unique', $prefix = '', $user_id = '')
	{
		if ($this->init_error) {
			return;
		}

		switch ($mode) {
			case 'avatar':

				$this->realname = sprintf('%s%s.%s', $prefix, $user_id, $this->extension);

			break;
			case 'defined':

				$this->realname = sprintf('%s.%s', $prefix, $this->extension);

			break;
			case 'real':

				/* Отбрасываем расширение */
				if (false !== $pos = strpos($this->realname, '.')) {
					$this->realname = substr($this->realname, 0, $pos);
				}

				/* Заменяем запрещенные символы нижним подчеркиванием */
				$bad_chars = ["'", "\\", ' ', '/', ':', '*', '?', '"', '<', '>', '|'];

				$this->realname = rawurlencode(str_replace($bad_chars, '_', strtolower($this->realname)));
				$this->realname = preg_replace("/%(\w{2})/", '_', $this->realname);
				$this->realname = sprintf('%s%s.%s', $prefix, $this->realname, $this->extension);

			break;
			case 'unique':

				$this->realname = sprintf('%s%s', $prefix, make_random_string());

			break;
			case 'unique_ext':

				$this->realname = sprintf('%s%s.%s', $prefix, make_random_string(), $this->extension);

			break;
		}
	}

	/**
	* Значение свойства объекта
	*/
	public function get($property)
	{
		if ($this->init_error || !isset($this->$property)) {
			return false;
		}

		return $this->$property;
	}

	/**
	* Проверяем, загружен ли файл
	*/
	public function is_uploaded()
	{
		if (!$this->local && !is_uploaded_file($this->filename)) {
			return false;
		}

		if ($this->local && !file_exists($this->filename)) {
			return false;
		}

		return true;
	}

	/**
	* Перемещение файла
	*/
	public function move_file($destination, $overwrite = false, $skip_image_check = false, $chmod = false)
	{
		if (sizeof($this->error)) {
			return false;
		}
		
		$this->destination_path = $destination;

		if (!file_exists($this->destination_path)) {
			@unlink($this->filename);
			return false;
		}

		$upload_mode = $this->local ? 'local' : 'move';
		$this->destination_file = $this->destination_path . '/' . basename($this->realname);

		if (file_exists($this->destination_file) && !$overwrite) {
			@unlink($this->filename);
			return false;
		}
		
		switch ($upload_mode) {
			case 'move':

				if (!@move_uploaded_file($this->filename, $this->destination_file)) {
					if (!@copy($this->filename, $this->destination_file)) {
						$this->error[] = 'Не удалось переместить файл';
					}
				}

			break;
			case 'local':

				if (!@copy($this->filename, $this->destination_file)) {
					$this->error[] = 'Не удалось локально переместить файл';
				}

			break;
		}
		
		// @unlink($this->filename);
		
		if (sizeof($this->error)) {
			return false;
		}
		
		if (false !== $chmod) {
			chmod($this->destination_file, $chmod);
		}

		$this->filesize = @filesize($this->destination_file) ? @filesize($this->destination_file) : $this->filesize;

		if ($this->is_image() && !$skip_image_check) {
			$this->height = $this->width = 0;

			if (false !== $this->image_info = @getimagesize($this->destination_file)) {
				$this->width = $this->image_info[0];
				$this->height = $this->image_info[1];

				if (!empty($this->image_info['mime'])) {
					$this->mimetype = $this->image_info['mime'];
				}

				$types = $this->upload->image_types();

				if (!isset($types[$this->image_info[2]]) || !in_array($this->extension, $types[$this->image_info[2]])) {
					if (!isset($types[$this->image_info[2]])) {
						$this->error[] = 'Неверный тип файла';
					} else {
						$this->error[] = 'Тип файла не совпадает с расширением';
					}
				}

				if (empty($this->width) || empty($this->height)) {
					$this->error[] = 'Загружаемый файл - не картинка';
				}
			} else {
				$this->error[] = 'Не удалось определить размеры изображения. Возможно, загруженный файл - не картинка';
			}
		}

		$this->file_moved = true;
		$this->additional_checks();
		unset($this->upload);

		return true;
	}
	
	/**
	* Удаление загруженного файла
	*/
	public function remove()
	{
		if ($this->file_moved) {
			@unlink($this->destination_file);
		}
	}
	
	/**
	* Возникла ошибка - необходимо передать её шаблонизатору
	* и удалить загруженный файл
	*/
	public function trigger_error($errors = [])
	{
		global $app;
		
		$this->error = array_merge($this->error, $errors);
		
		$app['template']->append('upload_errors', [
			'ERRORS' => $this->error,
			'FILE'   => $this->uploadname,
		]);
		
		$this->remove();
	}
	
	/**
	* Дополнительные проверки
	* Производятся после загрузки файла
	*/
	private function additional_checks()
	{
		if (!$this->file_moved) {
			return false;
		}

		/* Не превышен ли максимально допустимый размер файла */
		if ($this->upload->max_filesize && ($this->get('filesize') > $this->upload->max_filesize || 0 == $this->filesize)) {
			$this->error[] = 'Превышен максимальный размер файла';
			return false;
		}

		/* Проверка ширины и высоты */
		if (!$this->upload->valid_dimensions($this)) {
			$this->error[] = 'Файл не подходит по ширине или высоте';
			return false;
		}

		return true;
	}
	
	/**
	* Расширение файла
	*/
	private function get_extension($filename)
	{
		if (false === strpos($filename, '.')) {
			return '';
		}
		
		$ary = explode('.', $filename);

		return array_pop($ary);
	}

	/**
	* MIME тип файла. При возможности используется mime_content_type()
	*/
	private function get_mimetype($filename)
	{
		$mimetype = '';

		if (function_exists('mime_content_type')) {
			$mimetype = mime_content_type($filename);
		}

		if (!$mimetype || $mimetype == 'application/octet-stream') {
			$mimetype = 'application/octetstream';
		}

		return $mimetype;
	}
	
	/**
	* Проверка по mime типу, является ли файл изображением
	*/
	private function is_image()
	{
		return false !== strpos($this->mimetype, 'image/');
	}
}
