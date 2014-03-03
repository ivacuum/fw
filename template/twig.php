<?php
/**
* @package fw
* @copyright (c) 2014
*/

namespace fw\template;

class twig
{
	public $file;

	protected $dirs;
	protected $env;
	protected $vars = [];

	function __construct(array $dirs, $cache_dir)
	{
		$this->dirs = $dirs;
		$this->env  = new \Twig_Environment(new \Twig_Loader_Filesystem($this->dirs), [
			'auto_reload' => true,
			'autoescape'  => false,
			'cache'       => $cache_dir,
		]);

		$this->env->addFilter(new \Twig_SimpleFilter('truncate', [$this, 'filter_truncate']));
	}
	
	public function add_function($function_name, $handler)
	{
		$this->env->addFunction(new \Twig_SimpleFunction($function_name, $handler));
		
		return $this;
	}
	
	/**
	* Переменные цикла
	*/
	public function append($loop_name, $vars_array)
	{
		if (false !== strpos($loop_name, '.'))
		{
			/* Вложенный цикл */
			$loops       = explode('.', $loop_name);
			$loops_count = sizeof($loops) - 1;

			$str = &$this->vars;

			for ($i = 0; $i < $loops_count; $i++)
			{
				$str = &$str[$loops[$i]];
				$str = &$str[sizeof($str) - 1];
			}

			$str[$loops[$loops_count]][] = $vars_array;
		}
		else
		{
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

		if ($len % 2 !== 0)
		{
			return false;
		}

		for ($i = 0; $i < $len; $i += 2)
		{
			$this->vars[$args[$i]] = $args[$i + 1];
		}
	}

	/**
	* Вывод шаблона
	*/
	public function display($file = '')
	{
		echo $this->render($file);
	}
	
	/**
	* Вывод xml данных
	*/
	public function display_xml($file = '')
	{
		header('Content-type: text/xml; charset=utf-8');
		$this->display($file);
		exit;
	}
	
	/**
	* Обработка и возврат данных для вывода
	*/
	public function render($file = '')
	{
		$file = $file ?: $this->file;
		$found = false;
		
		foreach ($this->dirs as $dir)
		{
			if (file_exists("{$dir}/{$file}"))
			{
				$found = true;
				break;
			}
		}
		
		if (!$found)
		{
			trigger_error('TEMPLATE_NOT_FOUND');
		}
		
		return $this->env->render($file, $this->vars);
	}
	
	public function set_number_format($decimals, $dec_point, $thousands_sep)
	{
		$this->env->getExtension('core')->setNumberFormat($decimals, $dec_point, $thousands_sep);
		
		return $this;
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
