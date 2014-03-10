<?php namespace fw\cache\driver;

class memcache extends memory
{
	protected $flags = 0;
	protected $memcache;

	function __construct(array $options = [])
	{
		parent::__construct($options);

		$this->memcache = new \Memcache();
		$this->memcache->pconnect($this->options['host'], $this->options['port']);
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
		if (!$this->memcache->replace($var, $data, $this->flags, $ttl)) {
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
