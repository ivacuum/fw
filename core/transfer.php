<?php
/**
* @package fw
* @copyright (c) 2012
*/

if( !defined('IN_SITE') )
{
	exit;
}

/**
* Передача файлов
*/
class transfer
{
	var $connection;
	var $host;
	var $port;
	var $username;
	var $password;
	var $timeout;
	var $root_path;
	var $tmp_path;
	var $file_perms;
	var $dir_perms;

	function __construct()
	{
		global $site_root_path;

		$this->file_perms = 0666;
		$this->dir_perms  = 0777;

		/* Папка для временного хранения файлов на время передачи */
		$this->tmp_path = $site_root_path . '../static.ivacuum.ru/tmp/';
	}

	/**
	* Запись файла
	*/
	function write_file($destination_file = '', $contents = '')
	{
		global $site_root_path;

		$destination_file = $this->root_path . str_replace($site_root_path, '', $destination_file);

		/**
		* FTP функции не могут создавать файлы - только перемещать
		* Используем для записи временную папку
		*/
		$temp_name = tempnam($this->tmp_path, 'transfer_');
		@unlink($temp_name);

		$fp = @fopen($temp_name, 'w');

		if( !$fp )
		{
			trigger_error('Unable to create temporary file ' . $temp_name, E_USER_ERROR);
		}

		@fwrite($fp, $contents);
		@fclose($fp);

		$result = $this->overwrite_file($temp_name, $destination_file);

		/* Удаляем временный файл */
		@unlink($temp_name);

		return $result;
	}

	/**
	* Перемещение файла в заданную папку. Если файл уже существует, то перезаписываем его
	*/
	function overwrite_file($source_file, $destination_file)
	{
		$this->_delete($destination_file);
		$result = $this->_put($source_file, $destination_file);
		$this->_chmod($destination_file, $this->file_perms);

		return $result;
	}

	/**
	* Создание дерева папок
	*/
	function make_dir($dir)
	{
		global $site_root_path;

		$dir = str_replace($site_root_path, '', $dir);
		$dir = explode('/', $dir);
		$dirs = '';

		for( $i = 0, $total = sizeof($dir); $i < $total; $i++ )
		{
			$result = true;

			if( strpos($dir[$i], '.') === 0 )
			{
				continue;
			}

			$cur_dir = $dir[$i] . '/';

			if( !file_exists($site_root_path . $dirs . $cur_dir) )
			{
				/* Создаем папку */
				$result = $this->_mkdir($dir[$i]);
				$this->_chmod($dir[$i], $this->dir_perms);
			}

			$this->_chdir($this->root_path . $dirs . $dir[$i]);
			$dirs .= $cur_dir;
		}

		$this->_chdir($this->root_path);

		return $result;
	}

	/**
	* Копирование файла в папку назначения
	*/
	function copy_file($from_loc, $to_loc)
	{
		global $site_root_path;

		$from_loc = ((strpos($from_loc, $site_root_path) !== 0) ? $site_root_path : '') . $from_loc;
		$to_loc = $this->root_path . str_replace($site_root_path, '', $to_loc);

		if( !file_exists($from_loc) )
		{
			return false;
		}

		$result = $this->overwrite_file($from_loc, $to_loc);

		return $result;
	}

	/**
	* Удаление файла
	*/
	function delete_file($file)
	{
		global $site_root_path;

		$file = $this->root_path . str_replace($site_root_path, '', $file);

		return $this->_delete($file);
	}

	/**
	* Удаление папки
	*/
	function remove_dir($dir)
	{
		global $site_root_path;

		$dir = $this->root_path . str_replace($site_root_path, '', $dir);

		return $this->_rmdir($dir);
	}

	/**
	* Перенаименование файла или папки
	*/
	function rename($old_handle, $new_handle)
	{
		global $site_root_path;

		$old_handle = $this->root_path . str_replace($site_root_path, '', $old_handle);

		return $this->_rename($old_handle, $new_handle);
	}

	/**
	* Проверка существования файла
	*/
	function file_exists($directory, $filename)
	{
		global $site_root_path;

		$directory = $this->root_path . str_replace($site_root_path, '', $directory);

		$this->_chdir($directory);
		$result = $this->_ls();

		if( $result !== false && is_array($result) )
		{
			return (in_array($filename, $result)) ? true : false;
		}

		return false;
	}

	/**
	* Сеанс связи
	*/
	function open_session()
	{
		return $this->_init();
	}

	/**
	* Закрытие сеанса связи
	*/
	function close_session()
	{
		return $this->_close();
	}

	/**
	* Определяем какие функции можно использовать
	*/
	function methods()
	{
		$methods = array();

		if( @extension_loaded('ftp') )
		{
			$methods[] = 'ftp';
		}

		return $methods;
	}
}

/**
* FTP-передачи
*/
class ftp extends transfer
{
	function __construct($host, $username, $password, $root_path, $port = 21, $timeout = 5)
	{
		$this->host      = $host;
		$this->port      = $port;
		$this->username  = $username;
		$this->password  = $password;
		$this->timeout   = $timeout;
		$this->root_path = str_replace('\\', '/', $this->root_path);

		if( !empty($root_path) )
		{
			$this->root_path = (($root_path[0] != '/' ) ? '/' : '') . $root_path . ((substr($root_path, -1, 1) == '/') ? '' : '/');
		}

		parent::__construct();

		return;
	}

	/**
	* Запрос данных
	*/
	public function data()
	{
		return array(
			'host'      => 'localhost',
			'username'  => 'anonymous',
			'password'  => '',
			'root_path' => '/',
			'port'      => 21,
			'timeout'   => 5
		);
	}

	/**
	* Сеанс связи
	*/
	private function _init()
	{
		/* Подключение к серверу */
		$this->connection = @ftp_connect($this->host, $this->port, $this->timeout);

		if( !$this->connection )
		{
			return 'ERR_CONNECTING_SERVER';
		}

		/* Авторизация */
		if( !@ftp_login($this->connection, $this->username, $this->password) )
		{
			return 'ERR_UNABLE_TO_LOGIN';
		}

		/* Пассивный режим */
		@ftp_pasv($this->connection, true);

		/* Переход в корень */
		if( !$this->_chdir($this->root_path) )
		{
			return 'ERR_CHANGING_DIRECTORY';
		}

		return true;
	}

	/**
	* Создание папки
	*/
	private function _mkdir($dir)
	{
		return @ftp_mkdir($this->connection, $dir);
	}

	/**
	* Удаление папки
	*/
	private function _rmdir($dir)
	{
		return @ftp_rmdir($this->connection, $dir);
	}

	/**
	* Перенаименование файла
	*/
	private function _rename($old_handle, $new_handle)
	{
		return @ftp_rename($this->connection, $old_handle, $new_handle);
	}

	/**
	* Переход в другую папку
	*/
	private function _chdir($dir = '')
	{
		if( $dir && $dir !== '/' )
		{
			if( substr($dir, -1, 1) == '/' )
			{
				$dir = substr($dir, 0, -1);
			}
		}

		return @ftp_chdir($this->connection, $dir);
	}

	/**
	* Изменение прав на файл
	*/
	private function _chmod($file, $perms)
	{
		if( function_exists('ftp_chmod') )
		{
			$err = @ftp_chmod($this->connection, $perms, $file);
		}
		else
		{
			/* Передаем новые права в восьмеричном формате */
			$chmod_cmd = 'CHMOD ' . base_convert($perms, 10, 8) . ' ' . $file;
			$err = $this->_site($chmod_cmd);
		}

		return $err;
	}

	/**
	* Загрузка файла в указанное место
	*/
	private function _put($from_file, $to_file)
	{
		/* Расширение файла */
		$file_extension = strtolower(substr(strrchr($to_file, '.'), 1));

		/* Бинарная передача файлов для предотвращения внесения в них изменений ftp-сервером */
		$mode = FTP_BINARY;

		$to_dir = dirname($to_file);
		$to_file = basename($to_file);
		$this->_chdir($to_dir);

		$result = @ftp_put($this->connection, $to_file, $from_file, $mode);
		$this->_chdir($this->root_path);

		return $result;
	}

	/**
	* Удаление файла
	*/
	private function _delete($file)
	{
		return @ftp_delete($this->connection, $file);
	}

	/**
	* Завершение сеанса связи
	*/
	private function _close()
	{
		if( !$this->connection )
		{
			return false;
		}

		return @ftp_quit($this->connection);
	}

	/**
	* Папка, в которой находимся
	*/
	private function _cwd()
	{
		return @ftp_pwd($this->connection);
	}

	/**
	* Список файлов в папке
	*/
	private function _ls($dir = './')
	{
		$list = @ftp_nlist($this->connection, $dir);

		/* Некоторые FTP-сервера не любят './' */
		if( $dir === './' )
		{
			/* Пойдем другим путем */
			$list = (empty($list)) ? @ftp_nlist($this->connection, '.') : $list;
			$list = (empty($list)) ? @ftp_nlist($this->connection, '') : $list;
		}

		/* Выход при ошибке */
		if( $list === false )
		{
			return false;
		}

		foreach( $list as $key => $item )
		{
			$dir  = str_replace('\\', '/', $dir);
			$item = str_replace('\\', '/', $item);

			if( !empty($dir) && strpos($item, $dir) === 0 )
			{
				$item = substr($item, strlen($dir));
			}

			$list[$key] = $item;
		}

		return $list;
	}

	/**
	* FTP-команда SITE
	*/
	private function _site($command)
	{
		return @ftp_site($this->connection, $command);
	}
}
