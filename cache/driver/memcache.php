<?php
/**
* @package fw
* @copyright (c) 2013
*/

namespace fw\cache\driver;

use Memcache;

class memcache extends memory
{
	protected $extension = 'memcache';

	private $memcache;
	private $flags = 0;

	function __construct($prefix = '', $shared_prefix = '')
	{
		parent::__construct($prefix, $shared_prefix);

		$this->memcache = new Memcache();
		$this->memcache->pconnect('unix:///var/run/memcached/memcached.lock', 0);
	}

	public function _delete($var)
	{
		return $this->memcache->delete($var, 0);
	}

	public function _get($var)
	{
		return $this->memcache->get($var);
	}

	public function _set($var, $data, $ttl = 2592000)
	{
		if (!$this->memcache->replace($var, $data, $this->flags, $ttl))
		{
			return $this->memcache->set($var, $data, $this->flags, $ttl);
		}

		return true;
	}

	public function purge()
	{
		$this->memcache->flush();

		parent::purge();
	}

	public function unload()
	{
		parent::unload();

		$this->memcache->close();
	}
}
