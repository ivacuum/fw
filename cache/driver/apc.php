<?php
/** 
* @package ivacuum.fw
* @copyright (c) 2013
*/

namespace fw\cache\driver;

/**
* Класс кэша
*/
class apc extends memory
{
	protected $extension = 'apc';
	
	/**
	* Очистка кэша
	*/
	public function purge()
	{
		apc_clear_cache('user');
		
		parent::purge();
	}

	/**
	* Удаление записи из кэша
	*/
	public function _delete($var)
	{
		return apc_delete($var);
	}
	
	/**
	* Чтение записи из кэша
	*/
	public function _get($var)
	{
		return apc_fetch($var);
	}
	
	/**
	* Запись данных в кэш
	*/
	public function _set($var, $data, $ttl = 2592000)
	{
		return apc_store($var, $data, $ttl);
	}
}
