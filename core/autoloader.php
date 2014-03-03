<?php
/**
* @package fw
* @copyright (c) 2014
*/

namespace fw\core;

class autoloader
{
	private $apc_cache;
	private $namespaces = [];
	private $namespace_fallbacks = [];
	private $namespace_prefixes = [];
	private $pears = [];
	private $pear_fallbacks = [];
	private $pear_prefixes = [];
	private $use_include_path = false;
	
	function __construct()
	{
		$this->apc_cache = extension_loaded('apc');
	}
	
	/**
	* Загрузка заданного класса
	*/
	public function autoload($class)
	{
		if ($file = $this->find_file($class)) {
			require $file;
		}
	}
	
	/**
	* Поиск файла, в котором находится искомый класс
	*/
	public function find_file($class)
	{
		if ('\\' == $class[0]) {
			$class = substr($class, 1);
		}
		
		if (false !== $pos = strrpos($class, '\\')) {
			/* Пространства имен */
			$namespace  = substr($class, 0, $pos);
			$class_name = substr($class, $pos + 1);
			
			if (false !== strpos($namespace, '\\')) {
				list(, $suffix) = explode('\\', $namespace, 2);
			} else {
				$suffix = '';
			}
			
			$filename = str_replace('\\', DIRECTORY_SEPARATOR, $suffix) . DIRECTORY_SEPARATOR . str_replace('_', DIRECTORY_SEPARATOR, $class_name) . '.php';
			
			foreach ($this->namespaces as $ns => $dirs) {
				/* NS-именованные классы */
				if (0 !== strpos($namespace, $ns)) {
					continue;
				}
				
				if (isset($this->namespace_prefixes[$ns]) && false !== $file = apc_fetch($this->namespace_prefixes[$ns] . $class)) {
					return $file;
				}

				foreach ($dirs as $dir) {
					$file = $dir . DIRECTORY_SEPARATOR . $filename;
					
					if (is_file($file)) {
						if (isset($this->namespace_prefixes[$ns])) {
							apc_store($this->namespace_prefixes[$ns] . $class, $file);
						}
						
						return $file;
					}
				}
			}
			
			foreach ($this->namespace_fallbacks as $dir) {
				$file = $dir . DIRECTORY_SEPARATOR . $filename;
				
				if (is_file($file)) {
					return $file;
				}
			}
		} else {
			/* PEAR-именованные классы */
			$filename = str_replace('_', DIRECTORY_SEPARATOR, $class) . '.php';
			
			foreach ($this->pears as $prefix => $dirs) {
				if (0 !== strpos($class, $prefix)) {
					continue;
				}
				
				if (isset($this->pear_prefixes[$prefix]) && false !== $file = apc_fetch($this->pear_prefixes[$prefix] . $class)) {
					return $file;
				}
				
				foreach ($dirs as $dir) {
					$file = $dir . DIRECTORY_SEPARATOR . $filename;
					
					if (is_file($file)) {
						if (isset($this->pear_prefixes[$prefix])) {
							apc_store($this->pear_prefixes[$prefix] . $class, $file);
						}

						return $file;
					}
				}
			}
			
			foreach ($this->pear_fallbacks as $dir) {
				$file = $dir . DIRECTORY_SEPARATOR . $filename;
				
				if (is_file($file)) {
					return $file;
				}
			}
		}
		
		if ($this->use_include_path && $file = stream_resolve_include_path($filename)) {
			return $file;
		}
	}
	
	/**
	* Регистрация загрузчика
	*/
	public function register($prepend = false)
	{
		spl_autoload_register([$this, 'autoload'], true, $prepend);
		
		return $this;
	}
	
	/**
	* Регистрация директорий для поиска класса в определенном пространстве имен
	*/
	public function register_namespace($namespace, $dirs)
	{
		$this->namespaces[$namespace] = (array) $dirs;
		
		return $this;
	}
	
	/**
	* Регистрация резервной директории для поиска в ней NS-именованных классов
	*/
	public function register_namespace_fallback($dir)
	{
		$this->namespace_fallbacks[] = $dir;
		
		return $this;
	}
	
	/**
	* Регистрация резервных директорий для поиска в них NS-именованных классов
	*/
	public function register_namespace_fallbacks(array $dirs)
	{
		$this->namespace_fallbacks = $dirs;
		
		return $this;
	}
	
	/**
	* Регистрация директорий для поиска классов в определенном пространстве имен
	*/
	public function register_namespaces(array $ary)
	{
		foreach ($ary as $namespace => $dirs) {
			$this->namespaces[$namespace] = (array) $dirs;
		}
		
		return $this;
	}
	
	/**
	* Регистрация директорий для поиска класса с определенным префиксом
	*/
	public function register_pear($prefix, $dirs)
	{
		$this->pears[$prefix] = (array) $dirs;
		
		return $this;
	}
	
	/**
	* Регистрация резервной директории для поиска в ней PEAR-именованных классов
	*/
	public function register_pear_fallback($dir)
	{
		$this->pear_fallbacks[] = $dir;
		
		return $this;
	}
	
	/**
	* Регистрация резервных директорий для поиска в них PEAR-именованных классов
	*/
	public function register_pear_fallbacks(array $dirs)
	{
		$this->pear_fallbacks = $dirs;
		
		return $this;
	}
	
	/**
	* Регистрация директорий для поиска классов с определенным префиксом
	*/
	public function register_pears(array $ary)
	{
		foreach ($ary as $prefix => $dirs) {
			$this->pears[$prefix] = (array) $dirs;
		}
		
		return $this;
	}
	
	/**
	* Префикс для записей в кэше классов в определенном пространстве имен
	*/
	public function set_namespace_prefix($ns, $value)
	{
		if (!$this->apc_cache) {
			return $this;
		}
		
		$this->namespace_prefixes[$ns] = "{$value}_";
		
		return $this;
	}
	
	/**
	* Префиксы для записей в кэше классов в определенном пространстве имен
	*/
	public function set_namespace_prefixes(array $ary)
	{
		if (!$this->apc_cache) {
			return $this;
		}
		
		foreach ($ary as $ns => $value) {
			$this->namespace_prefixes[$ns] = "{$value}_";
		}
		
		return $this;
	}
	
	/**
	* Префикс для записей в кэше PEAR-именованных классов
	*/
	public function set_pear_prefix($prefix, $value)
	{
		if (!$this->apc_cache) {
			return $this;
		}
		
		$this->pear_prefixes[$prefix] = "{$value}_";
		
		return $this;
	}
	
	/**
	* Префиксы для записей в кэше PEAR-именованных классов
	*/
	public function set_pear_prefixes(array $ary)
	{
		if (!$this->apc_cache) {
			return $this;
		}
		
		foreach ($ary as $prefix => $value) {
			$this->pear_prefixes[$prefix] = "{$value}_";
		}
		
		return $this;
	}
	
	/**
	* Следует ли искать классы в папке по умолчанию (include_path)
	*/
	public function use_include_path($flag) {
		$this->use_include_path = $flag;
		
		return $this;
	}
}
