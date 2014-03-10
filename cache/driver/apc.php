<?php namespace fw\cache\driver;

class apc extends memory
{
	public function _delete($var)
	{
		return apc_delete($var);
	}
	
	public function _get($var)
	{
		return apc_fetch($var);
	}
	
	public function _set($var, $data, $ttl = 2592000)
	{
		return apc_store($var, $data, $ttl);
	}

	public function purge()
	{
		apc_clear_cache('user');
		
		parent::purge();
	}
}
