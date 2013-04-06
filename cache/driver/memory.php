<?php
/**
* @package fw
* @copyright (c) 2013
*/

namespace fw\cache\driver;

/**
* Хранение кэша в памяти
*/
class memory
{
	protected $prefix;
	protected $shared_prefix;
	
	private $data = [];
	private $is_modified = false;
	
	function __construct($prefix = '', $shared_prefix = '')
	{
		$this->set_prefixes($prefix, $shared_prefix);
		
		if (!isset($this->extension) || !extension_loaded($this->extension))
		{
			trigger_error(sprintf('Не удается найти расширение [%s] для ACM.', $this->extension), E_USER_ERROR);
		}
	}
	
	/**
	* Удаление записи из кэша
	*/
	public function delete($var, $table = '')
	{
		if (!$this->_exists($var))
		{
			return;
		}

		if (isset($this->data[$var]))
		{
			$this->is_modified = true;
			unset($this->data[$var]);

			/* cache hit */
			$this->save();
		}
		elseif ($var[0] != '_')
		{
			$this->_delete($this->prefix . $var);
		}
	}

	/**
	* Удаление записи из общего для нескольких проектов кэша
	*/
	public function delete_shared($var)
	{
		$this->_delete($this->shared_prefix . $var);
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
		
		return $this->_get($this->prefix . $var);
	}

	/**
	* Получение данных из общего для нескольких проектов кэша
	*/
	public function get_shared($var)
	{
		return $this->_get($this->shared_prefix . $var);
	}

	/**
	* Загрузка глобальных настроек
	*/
	private function load()
	{
		$this->data = $this->_get("{$this->prefix}global");

		return false !== $this->data;
	}

	/**
	* Сброс кэша
	*/
	protected function purge()
	{
		unset($this->data);

		$this->data = [];
		$this->is_modified = false;
	}
	
	/**
	* Запись данных в кэш
	*/
	public function set($var, $data, $ttl = 2592000)
	{
		if ($var[0] == '_')
		{
			$this->data[$var] = $data;
			$this->is_modified = true;
		}
		else
		{
			$this->_set($this->prefix . $var, $data, $ttl);
		}
	}

	/**
	* Установка префиксов записей
	*/
	public function set_prefixes($prefix = '', $shared_prefix = '')
	{
		$this->prefix        = $prefix ? "{$prefix}_" : '';
		$this->shared_prefix = $shared_prefix ? "{$shared_prefix}_" : '';
	}

	/**
	* Запись данных в общий для нескольких проектов кэш
	*/
	public function set_shared($var, $data, $ttl = 2592000)
	{
		$this->_set($this->shared_prefix . $var, $data, $ttl);
	}

	/**
	* Выгрузка данных
	*/
	protected function unload()
	{
		$this->save();
		unset($this->data);
		$this->data = [];
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

			return isset($this->data[$var]);
		}
		else
		{
			return true;
		}
	}
	
	/**
	* Сохранение глобальных настроек
	*/
	private function save()
	{
		if (!$this->is_modified)
		{
			return;
		}

		$this->_set("{$this->prefix}global", $this->data, 2592000);
		$this->is_modified = false;
	}
}
