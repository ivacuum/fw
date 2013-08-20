<?php
/** 
* @package fw
* @copyright (c) 2013
*/

namespace fw\cache\driver;

class file
{
	protected $cache_dir;
	protected $data = [];
	protected $data_expires = [];
	protected $is_modified = false;
	protected $options = [
		'prefix'        => '',
		'shared_prefix' => '',
		'type'          => '',
	];
	
	function __construct(array $options = [])
	{
		$this->options = array_merge($this->options, $options);
		$this->cache_dir = SITE_DIR . '../cache/';

		if (!$this->options['prefix'] || !$this->options['shared_prefix'])
		{
			trigger_error('Для работы системы кэширования должны быть настроены prefix и shared_prefix.', E_USER_ERROR);
		}
	}
	
	/**
	* Получение данных из кэша
	*/
	public function _get($filename)
	{
		$file = "{$this->cache_dir}{$filename}.php";
		
		if (!file_exists($file))
		{
			return false;
		}
		
		if (false === $handle = fopen($file, 'rb'))
		{
			return false;
		}
		
		/* Пропуск заголовка */
		fgets($handle);
		
		if ($filename == "{$this->options['prefix']}global")
		{
			$this->data = $this->data_expires = [];
			$time = time();
			
			while (($expires = (int) fgets($handle)) && !feof($handle))
			{
				$bytes = substr(fgets($handle), 0, -1);
				
				if (!is_numeric($bytes) || ($bytes = (int) $bytes) === 0)
				{
					fclose($handle);
					
					$this->data = $this->data_expires = [];
					$this->is_modified = false;
					$this->remove_file($file);
					
					return false;
				}
				
				if ($time >= $expires)
				{
					fseek($handle, $bytes, SEEK_CUR);
					continue;
				}
				
				$var = substr(fgets($handle), 0, -1);
				
				$data = fread($handle, $bytes - strlen($var));
				$data = @unserialize($data);
				
				if (false !== $data)
				{
					$this->data[$var] = $data;
					$this->data_expires[$var] = $expires;
				}
				
				fgets($handle);
			}
			
			fclose($handle);
			
			$this->is_modified = false;
			
			return true;
		}
		else
		{
			$data = false;
			$line = 0;
			
			while (($buffer = fgets($handle)) && !feof($handle))
			{
				$buffer = substr($buffer, 0, -1);
				
				if (!is_numeric($buffer))
				{
					break;
				}
				
				if ($line == 0)
				{
					$expires = (int) $buffer;
					
					if (time() >= $expires)
					{
						break;
					}
					
					if (0 === strpos($filename, "{$this->options['prefix']}sql_"))
					{
						fgets($handle);
					}
				}
				elseif ($line == 1)
				{
					$bytes = (int) $buffer;
					
					/* Никогда не должно быть 0 байт */
					if (!$bytes)
					{
						break;
					}
					
					/* Чтение сериализованных данных */
					$data = fread($handle, $bytes);
					
					/* Чтение 1 байта для вызова EOF */
					fread($handle, 1);
					
					if (!feof($handle))
					{
						/* Кто-то изменил данные */
						$data = false;
					}
					
					break;
				}
				else
				{
					/* Что-то пошло не так */
					break;
				}
				
				$line++;
			}
			
			fclose($handle);
			
			$data = false !== $data ? @unserialize($data) : $data;
			
			if (false === $data)
			{
				$this->remove_file($file);
				return false;
			}
			
			return $data;
		}
	}
	
	/**
	* Обновление/добавление записи
	*/
	public function _set($filename, $data = null, $expires = 2592000, $query = '')
	{
		$file = "{$this->cache_dir}{$filename}.php";
		
		if ($handle = fopen($file, 'wb'))
		{
			flock($handle, LOCK_EX);
			fwrite($handle, '<' . '?php exit; ?' . '>');
			
			if ($filename == "{$this->options['prefix']}global")
			{
				foreach ($this->data as $var => $data)
				{
					if (false !== strpos($var, "\r") || false !== strpos($var, "\n"))
					{
						continue;
					}
					
					$data = serialize($data);
					
					fwrite($handle, "\n{$this->data_expires[$var]}\n");
					fwrite($handle, strlen($data . $var) . "\n");
					fwrite($handle, "{$var}\n");
					fwrite($handle, $data);
				}
			}
			else
			{
				fwrite($handle, "\n" . (time() + $expires) . "\n");
				
				if (0 === strpos($filename, "{$this->options['prefix']}sql_"))
				{
					fwrite($handle, "{$query}\n");
				}
				
				$data = serialize($data);
				
				fwrite($handle, strlen($data) . "\n");
				fwrite($handle, $data);
			}
			
			flock($handle, LOCK_UN);
			fclose($handle);
			
			return true;
		}
		
		return false;
	}
	
	public function delete($var)
	{
		if (!$this->_exists($var))
		{
			return;
		}

		if (isset($this->data[$var]))
		{
			$this->is_modified = true;
			unset($this->data[$var], $this->data_expires[$var]);

			/* cache hit */
			$this->save();
		}
		elseif ($var[0] != '_')
		{
			$this->remove_file("{$this->options['prefix']}{$var}.php", true);
		}
	}

	/**
	* Получение данных из кэша
	*/
	public function get($var)
	{
		if (!$this->_exists($var))
		{
			return false;
		}

		if ($var[0] == '_')
		{
			return $this->data[$var];
		}
		
		return $this->_get($this->options['prefix'] . $var);
	}

	/**
	* Загрузка глобальных настроек
	*/
	public function load()
	{
		return $this->_get("{$this->options['prefix']}global");
	}

	/**
	* Сброс кэша
	*/
	public function purge()
	{
		if (false === $dir = opendir($this->cache_dir))
		{
			return;
		}
		
		while (false !== $entry = readdir($dir))
		{
			if (0 !== strpos($entry, $this->options['prefix']))
			{
				continue;
			}
			
			$this->remove_file($this->cache_dir . $entry);
		}
		
		closedir($dir);
		
		unset($this->data, $this->data_expires);

		$this->data = $this->data_expires = [];
		$this->is_modified = false;
	}
	
	/**
	* Удаление файла с кэшем
	*/
	public function remove_file($filename, $check = false)
	{
		if ($check && !is_writable($this->cache_dir))
		{
			trigger_error('Проверьте права доступа к директории с кэшем.', E_USER_ERROR);
		}
		
		return unlink($filename);
	}
	
	/**
	* Запись данных в кэш
	*/
	public function set($var, $data, $ttl = 2592000)
	{
		if ($var[0] == '_')
		{
			$this->data[$var] = $data;
			$this->data_expires[$var] = time() + $ttl;
			$this->is_modified = true;
		}
		else
		{
			$this->_set($this->options['prefix'] . $var, $data, $ttl);
		}
	}
	
	/**
	* Удаление устаревшего кэша
	*/
	public function tidy()
	{
		if (false === $dir = opendir($this->cache_dir))
		{
			return;
		}
		
		$time = time();
		
		while (false !== $entry = readdir($dir))
		{
			if (0 !== strpos($entry, "{$this->options['prefix']}sql_") && 0 !== strpos($entry, "{$this->options['prefix']}global"))
			{
				continue;
			}
			
			if (false === $handle = fopen($this->cache_dir . $entry, 'rb'))
			{
				continue;
			}
			
			/* Пропускаем заголовок */
			fgets($handle);
			
			$expires = (int) fgets($handle);
			
			fclose($handle);
			
			if ($time >= $expires)
			{
				$this->remove_file($this->cache_dir . $entry);
			}
		}
		
		closedir($dir);
		
		if (file_exists("{$this->cache_dir}{$this->options['prefix']}global.php"))
		{
			if (!sizeof($this->data))
			{
				$this->load();
			}
			
			foreach ($this->data_expires as $var => $expires)
			{
				if ($time >= $expires)
				{
					$this->delete($var);
				}
			}
		}
	}

	/**
	* Выгрузка данных
	*/
	public function unload()
	{
		$this->save();
		
		unset($this->data, $this->data_expires);
		
		$this->data = $this->data_expires = [];
	}

	/**
	* Проверка наличия данных в кэше
	*/
	private function _exists($var)
	{
		if ($var[0] == '_')
		{
			if (!sizeof($this->data))
			{
				$this->load();
			}
			
			if (!isset($this->data_expires[$var]))
			{
				return false;
			}

			return (time() > $this->data_expires[$var]) ? false : isset($this->data[$var]);
		}
		else
		{
			return file_exists("{$this->cache_dir}{$this->options['prefix']}{$var}.php");
		}
		
	}

	/**
	* Сохранение глобальных настроек
	*/
	protected function save() 
	{
		if (!$this->is_modified)
		{
			return;
		}
		
		if (!$this->_set("{$this->options['prefix']}global"))
		{
			if (!is_writable($this->cache_dir))
			{
				trigger_error('Не удалось сохранить кэш. Проверьте права доступа к директории с кэшем.', E_USER_ERROR);
			}
			
			trigger_error('Не удалось сохранить кэш', E_USER_ERROR);
		}
		
		$this->is_modified = false;
	}
}
