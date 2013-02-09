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

	private $loader;
	private $vars = [];

	function __construct()
	{
		$this->path   = SITE_DIR . '../templates';
		$this->loader = new \Twig_Loader_Filesystem([$this->path, FW_DIR . 'templates']);
		$this->env    = new \Twig_Environment($this->loader, [
			'auto_reload' => true,
			'autoescape'  => false,
			'cache'       => SITE_DIR . '../cache/templates',
		]);

		$this->env->addFunction('lang', new \Twig_Function_Function('\\fw\\template\\twig_lang'));
		$this->env->addFunction('static', new \Twig_Function_Function('\\fw\\template\\twig_static'));
		$this->env->addFilter('truncate', new \Twig_Filter_Function('\\fw\\template\\twig_truncate'));
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
	* Переменные цикла
	*/
	public function cycle_vars($loop_name, $vars_array)
	{
		return $this->append($loop_name, $vars_array);
	}

	/**
	* Обработка и вывод шаблона
	*/
	public function display($file = '')
	{
		$this->file = $file ?: $this->file;
		
		// if (!file_exists($this->path . '/' . $this->file))
		// {
		// 	trigger_error('TEMPLATE_NOT_FOUND');
		// }

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
	* Обработка и вывод шаблона
	*/
	public function go($file = '')
	{
		$this->display($file);
	}

	/**
	* Одиночная переменная
	*/
	public function setvar($key, $value)
	{
		return $this->assign($key, $value);
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
	* Массив переменных
	*/
	public function vars($data)
	{
		return $this->assign($data);
	}
}

/**
* Языковые переменные
*/
function twig_lang()
{
	global $app;
	
	$args = func_get_args();
	
	return call_user_func_array([$app['user'], 'lang'], $args);
}

/**
* Локальная или внешняя ссылка на статичные файлы
*
* 'image', '/_/application
*/
function twig_static($type, $url, $custom_extension = false)
{
	global $app;
	static $is_local = null;
	
	if (is_null($is_local))
	{
		$is_local = $app['request']->isp === 'local';
	}
	
	switch ($type)
	{
		case 'd':
		case 'download': $prefix = $this->config['download_path']; $ext = 'html'; break;
		case 'flag':
		case 'flag24':   $prefix = $this->config['flags_path'] . '/24'; $ext = 'png'; break;
		case 'flag16':   $prefix = $this->config['flags_path'] . '/16'; $ext = 'png'; break;
		case 'flag32':   $prefix = $this->config['flags_path'] . '/32'; $ext = 'png'; break;
		case 'flag48':   $prefix = $this->config['flags_path'] . '/48'; $ext = 'png'; break;
		case 'g':
		case 'gallery':  $prefix = $this->config['gallery_path']; break;
		case 'i':
		case 'img':
		case 'image':    $prefix = $this->config['images_path']; $ext = 'png'; break;
		case 'js':       $prefix = $this->config['js_path']; $ext = 'js'; break;
		case 'rank':     $prefix = $this->config['ranks_path']; $ext = ''; break;
		case 'smile':
		case 'smiley':   $prefix = $this->config['smilies_path']; $ext = 'gif'; break;
		
		default: $prefix = ''; $ext = 'png';
	}
	
	if ($is_local)
	{
		$prefix = str_replace(['ivacuum.ru', 'ivacuum.org'], ['local.ivacuum.ru', '0.ivacuum.org'], $prefix);
	}
	
	if (false !== $custom_extension)
	{
		$ext = $custom_extension;
	}
	
	return $ext ? sprintf('%s/%s.%s', $prefix, $url, $ext) : sprintf('%s/%s', $prefix, $url);
}

/**
* Вывод части строки
*/
function twig_truncate($string, $length = 80, $etc = '...', $break_words = false, $middle = false)
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
