<?php namespace fw\cache\driver;

class null
{
	function __construct()
	{
	}
	
	public function _delete($var)
	{
	}
	
	public function _get($var)
	{
		return false;
	}
	
	public function _set($var, $data, $ttl = 0)
	{
	}
	
	public function delete($var)
	{
	}
	
	public function delete_shared($var)
	{
	}
	
	public function get($var)
	{
		return false;
	}
	
	public function get_shared($var)
	{
		return false;
	}

	public function load()
	{
		return true;
	}

	public function purge()
	{
	}

	public function save()
	{
	}

	public function set($var, $data, $ttl = 0)
	{
	}
	
	public function set_shared($var, $data, $ttl = 0)
	{
	}
	
	public function unload()
	{
	}

	protected function _exists($var)
	{
		return false;
	}
}
