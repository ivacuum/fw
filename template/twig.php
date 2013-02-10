<?php
/**
* @package fw
* @copyright (c) 2013
*/

namespace fw\template;

/**
* Шаблонизатор Twig
*/
class twig
{
	public $env;
	public $file;
	public $path;
	public $path_fw;

	private $loader;
	private $vars = [];

	function __construct()
	{
		$this->path    = SITE_DIR . '../templates';
		$this->path_fw = FW_DIR . 'templates';
		$this->loader  = new \Twig_Loader_Filesystem([$this->path, $this->path_fw]);
		$this->env     = new \Twig_Environment($this->loader, [
			'auto_reload' => true,
			'autoescape'  => false,
			'cache'       => SITE_DIR . '../cache/templates',
		]);

		$this->env->addFilter(new \Twig_SimpleFilter('truncate', [$this, 'filter_truncate']));
	}
	
	public function add_function($function_name, $handler)
	{
		$this->env->addFunction(new \Twig_SimpleFunction($function_name, $handler));
	}
	
	/**
	* Переменные цикла
	*/
	public function append($loop_name, $vars_array)
	{
		if (false !== strpos($loop_name, '.'))
		{
			/**
			* Цикл в цикле
			*/
			$loops       = explode('.', $loop_name);
			$loops_count = sizeof($loops) - 1;

			$str = &$this->vars;

			for ($i = 0; $i < $loops_count; $i++)
			{
				$str = &$str[$loops[$i]];
				$str = &$str[sizeof($str) - 1];
			}

			/* Вставка данных */
			$str[$loops[$loops_count]][] = $vars_array;
		}
		else
		{
			/* Вставка данных */
			$this->vars[$loop_name][] = $vars_array;
		}

		return true;
	}

	/**
	* Присвоение значения переменной
	*/
	public function assign()
	{
		$args = func_get_args();

		if (is_array($args[0]))
		{
			foreach ($args[0] as $key => $value)
			{
				$this->vars[$key] = $value;
			}

			return true;
		}

		$len = sizeof($args);

		if ($len % 2 != 0)
		{
			return false;
		}

		for ($i = 0; $i < $len; $i += 2)
		{
			$this->vars[$args[$i]] = $args[$i + 1];
		}
	}

	/**
	* Обработка и вывод шаблона
	*/
	public function display($file = '')
	{
		$this->file = $file ?: $this->file;
		
		if (!file_exists("{$this->path}/{$this->file}") && !file_exists("{$this->path_fw}/{$this->file}"))
		{
			trigger_error('TEMPLATE_NOT_FOUND');
		}

		echo $this->env->render($this->file, $this->vars);
	}
	
	/**
	* Вывод xml данных
	*/
	public function display_xml($file = '')
	{
		header('Content-type: text/xml; charset=utf-8');
		$this->display($file);
		garbage_collection(false);
		exit;
	}

	/**
	* Установка нового пути к шаблонам
	*/
	public function set_template_path($path)
	{
		$this->path   = $path;
		$this->loader = new \Twig_Loader_Filesystem($this->path);
		$this->env    = new \Twig_Environment($this->loader, [
			'auto_reload' => true,
			'autoescape'  => false,
			'cache'       => SITE_DIR . 'cache/templates',
		]);
		$this->is_globals_set = false;
	}

	/**
	* Вывод части строки
	*/
	protected function filter_truncate($string, $length = 80, $etc = '...', $break_words = false, $middle = false)
	{
		if (!$length)
		{
			return;
		}

		if (mb_strlen($string) > $length)
		{
			$length -= min($length, mb_strlen($etc));

			if (!$break_words && !$middle)
			{
				$string = preg_replace('#\s+?(\S+)?$#u', '', mb_substr($string, 0, $length + 1));
			}

			if (!$middle)
			{
				return mb_substr($string, 0, $length) . $etc;
			}
		
			return mb_substr($string, 0, $length / 2) . $etc . mb_substr($string, - $length / 2);
		}
	
		return $string;
	}
}
