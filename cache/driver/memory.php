<?php
/**
* @package fw
* @copyright (c) 2013
*/

namespace fw\cache\driver;

class memory
{
	protected $data = [];
	protected $is_modified = false;
	protected $options = [
		'prefix'        => '',
		'shared_prefix' => '',
		'type'          => '',
	];
	
	function __construct(array $options = [])
	{
		$this->options = array_merge($this->options, $options);
		
		if (!$this->options['type'] || !extension_loaded($this->options['type']))
		{
			trigger_error("Не удается найти расширение «{$this->extension}».", E_USER_ERROR);
		}
		
		if (!$this->options['prefix'] || !$this->options['shared_prefix'])
		{
			trigger_error('Для работы системы кэширования должны быть настроены prefix и shared_prefix.', E_USER_ERROR);
		}
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
			unset($this->data[$var]);

			/* cache hit */
			$this->save();
		}
		elseif ($var[0] != '_')
		{
			$this->_delete($this->options['prefix'] . $var);
		}
	}

	/**
	* Удаление записи из общего для нескольких проектов кэша
	*/
	public function delete_shared($var)
	{
		$this->_delete($this->options['shared_prefix'] . $var);
	}

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
	* Получение данных из общего для нескольких проектов кэша
	*/
	public function get_shared($var)
	{
		return $this->_get($this->options['shared_prefix'] . $var);
	}

	/**
	* Загрузка глобальных настроек
	*/
	public function load()
	{
		$this->data = $this->_get("{$this->options['prefix']}global");

		return false !== $this->data;
	}
	
	public function purge()
	{
		unset($this->data);

		$this->data = [];
		$this->is_modified = false;
	}
	
	/**
	* Сохранение глобальных настроек
	*/
	public function save()
	{
		if (!$this->is_modified)
		{
			return;
		}

		$this->_set("{$this->options['prefix']}global", $this->data, 2592000);
		$this->is_modified = false;
	}
	
	public function set($var, $data, $ttl = 2592000)
	{
		if ($var[0] == '_')
		{
			$this->data[$var] = $data;
			$this->is_modified = true;
		}
		else
		{
			$this->_set($this->options['prefix'] . $var, $data, $ttl);
		}
	}

	/**
	* Запись данных в общий для нескольких проектов кэш
	*/
	public function set_shared($var, $data, $ttl = 2592000)
	{
		$this->_set($this->options['shared_prefix'] . $var, $data, $ttl);
	}

	/**
	* Выгрузка данных
	*/
	public function unload()
	{
		$this->save();
		unset($this->data);
		$this->data = [];
	}

	/**
	* Проверка наличия данных в кэше
	*/
	protected function _exists($var)
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
}
