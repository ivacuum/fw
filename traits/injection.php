<?php
/**
* @package fw
* @copyright (c) 2013
*/

namespace fw\traits;

trait injection
{
	protected $app;
	
	function __get($name)
	{
		return $this->app[$name];
	}

	public function _set_app($app)
	{
		$this->app = $app;
		
		return $this;
	}
	
	public function _set_auth($auth)
	{
		$this->auth = $auth;
		
		return $this;
	}
	
	public function _set_cache($cache)
	{
		$this->cache = $cache;
		
		return $this;
	}
	
	public function _set_config($config)
	{
		$this->config = $config;
		
		return $this;
	}
	
	public function _set_db($db)
	{
		$this->db = $db;
		
		return $this;
	}
	
	public function _set_profiler($profiler)
	{
		$this->profiler = $profiler;
		
		return $this;
	}
	
	public function _set_request($request)
	{
		$this->request = $request;
		
		return $this;
	}
	
	public function _set_template($template)
	{
		$this->template = $template;
		
		return $this;
	}
	
	public function _set_user($user)
	{
		$this->user = $user;
		
		return $this;
	}
}
