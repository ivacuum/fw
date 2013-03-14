<?php
/**
* @package fw
* @copyright (c) 2013
*/

namespace fw\template;

class smarty
{
	public $file;
	
	protected $dirs;
	protected $driver;
	
	function __construct(array $dirs, $cache_dir)
	{
		$this->dirs = $dirs;
		
		$this->driver = new \Smarty();
		$this->driver->setTemplateDir($dirs);
		
		$this->driver->compile_dir     = $cache_dir;
		$this->driver->caching         = false;
		$this->driver->compile_check   = true;
		$this->driver->debugging       = false;
		$this->driver->error_reporting = E_ALL ^ E_NOTICE;
		$this->driver->force_compile   = false;
		$this->driver->use_sub_dirs    = false;
	}
	
	function __call($method, $args)
	{
		return call_user_func_array([$this->driver, $method], $args);
	}
	
	public function add_filter($filter_name, $callback)
	{
		$this->driver->registerPlugin('modifier', $filter_name, $callback);
		
		return $this;
	}

	public function add_function($function_name, $callback)
	{
		$this->driver->registerPlugin('function', $function_name, $callback);
		
		return $this;
	}

	/**
	* Обработка и вывод шаблона
	*/
	public function display($file = '')
	{
		$file = $file ?: $this->file;
		
		if (!$this->is_template_exist($file))
		{
			trigger_error('TEMPLATE_NOT_FOUND');
		}
		
		echo $this->driver->display($file);
	}
	
	/**
	* Обработка и возврат данных для вывода
	*/
	public function fetch($file = '')
	{
		$file = $file ?: $this->file;
		
		if (!$this->is_template_exist($file))
		{
			trigger_error('TEMPLATE_NOT_FOUND');
		}
		
		return $this->driver->fetch($file);
	}
	
	/**
	* Алиас $this->fetch()
	*/
	public function render($file = '')
	{
		return $this->fetch($file);
	}
	
	/**
	* Проверка существования шаблона
	*/
	protected function is_template_exist($file)
	{
		foreach ($this->dirs as $dir)
		{
			if (file_exists("{$dir}/{$file}"))
			{
				return true;
			}
		}
		
		return false;
	}
}
