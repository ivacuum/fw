<?php
/**
* @package fw
* @copyright (c) 2012
*/

namespace fw\core;

/**
* Данные запроса пользователя
*/
class request
{
	const GET     = 0;
	const POST    = 1;
	const COOKIE  = 2;
	const REQUEST = 3;
	const SERVER  = 4;
	
	public $code;
	public $hostname;
	public $http_methods = ['GET', 'POST', 'PUT', 'DELETE', 'HEAD', 'OPTIONS'];
	public $is_ajax;
	public $is_secure;
	public $isp;
	public $language;
	public $method;
	public $server_name;
	public $url;
	
	protected $options = [
		'local_redirect.from' => '',
		'local_redirect.to'   => '',
	];
	
	protected $globals = [
		self::GET     => '_GET',
		self::POST    => '_POST',
		self::COOKIE  => '_COOKIE',
		self::REQUEST => '_REQUEST',
		self::SERVER  => '_SERVER'
	];
	
	function __construct(array $options = [])
	{
		$this->options = array_merge($this->options, $options);

		$this->code      = http_response_code();
		$this->hostname  = $this->get_hostname();
		$this->is_ajax   = $this->header('X-Requested-With') == 'XMLHttpRequest';
		$this->is_secure = $this->server('HTTPS') == 'on';
		$this->isp       = $this->header('Provider', 'internet');
		$this->method    = strtolower($this->server('REQUEST_METHOD', 'get'));
		$this->url       = urldecode($this->server('REQUEST_URI'));
		
		/* По умолчанию при использовании метода PUT данные не попадают в $_REQUEST */
		if ($this->method == 'put')
		{
			$_REQUEST = array_merge(json_decode(file_get_contents('php://input'), true), $_REQUEST);
		}
	}
	
	/**
	* Данные из $_COOKIE
	*/
	public function cookie($var, $default)
	{
		return $this->variable($var, $default, self::COOKIE);
	}
	
	/**
	* Доменное имя обслуживаемого сайта
	*/
	public function get_hostname()
	{
		$hostname = mb_strtolower($this->header('Host') ?: $this->server('SERVER_NAME'));
		$hostname = 0 === strpos($hostname, 'www.') ? substr($hostname, 4) : $hostname;
		$hostname = (false !== $pos = strpos($hostname, ':')) ? substr($hostname, 0, $pos) : $hostname;
		
		return $hostname;
	}
	
	/**
	* Данные из $_GET
	*/
	public function get($var, $default)
	{
		return $this->variable($var, $default, self::GET);
	}
	
	/**
	* Данные заголовка
	*/
	public function header($header, $default = '')
	{
		return $this->server('HTTP_' . str_replace('-', '_', strtoupper($header)), $default);
	}
	
	/**
	* Установлена ли переменная в требуемом массиве
	*/
	public function is_set($var, $global = self::REQUEST)
	{
		return isset($GLOBALS[$this->globals[$global]][$var]);
	}
	
	/**
	* Установлена ли переменная в массиве $_COOKIE
	*/
	public function is_set_cookie($var)
	{
		return $this->is_set($var, self::COOKIE);
	}
	
	/**
	* Установлена ли переменная в массиве $_POST
	*/
	public function is_set_post($var)
	{
		return $this->is_set($var, self::POST);
	}
	
	/**
	* Данные из $_POST
	*/
	public function post($var, $default)
	{
		return $this->variable($var, $default, self::POST);
	}
	
	/**
	* Переадресация
	*
	* @param	string	$url	Адрес для мгновенного перенаправления
	*/
	public function redirect($url, $status_code = 302)
	{
		if (false !== strpos(urldecode($url), "\n") || false !== strpos(urldecode($url), "\r"))
		{
			trigger_error('Bad URL.', E_USER_ERROR);
		}
	
		/**
		* Если пользователь из локальной сети,
		* то перенаправлять его следует на локальный домен
		*/
		if ($this->isp == 'local' && !empty($this->options['local_redirect.from']) && !empty($this->options['local_redirect.to']))
		{
			$url = str_replace($this->options['local_redirect.from'], $this->options['local_redirect.to'], $url);
		}
	
		if ($status_code != 302)
		{
			http_response_code($status_code);
		}

		header('Location: ' . $url);
		exit;
	}

	/**
	* Данные из $_REQUEST
	*/
	public function request($var, $default)
	{
		return $this->variable($var, $default, self::REQUEST);
	}
	
	/**
	* Данные из $_SERVER
	*/
	public function server($var, $default = '')
	{
		if ($this->is_set($var, self::SERVER))
		{
			return $this->variable($var, $default, self::SERVER);
		}
		
		$var = getenv($var);
		$this->recursive_set_type($var, $default);
		
		return $var;
	}
	
	/**
	* Язык сайта
	* Устанавливается во время определения обслуживаемого сайта
	*/
	public function set_language($language)
	{
		$this->language = $language;
		
		return $this;
	}
	
	/**
	* Главное доменное имя сайта
	* Устанавливается во время определения обслуживаемого сайта
	*/
	public function set_server_name($server_name)
	{
		$this->server_name = $server_name;
		
		return $this;
	}
	
	/**
	* Поиск переменной в указанном глобальном массиве
	*/
	public function variable($var, $default, $global = self::REQUEST)
	{
		$input = $this->globals[$global];
		$path  = false;
		
		if (is_array($var))
		{
			$path = $var;
			
			if (empty($path))
			{
				return is_array($default) ? [] : $default;
			}
			
			$var = array_shift($path);
		}
		
		if (!isset($GLOBALS[$input][$var]))
		{
			/**
			* Переменная не установлена
			* Возвращаем значение по умолчанию
			*/
			return is_array($default) ? [] : $default;
		}
		
		$var = $GLOBALS[$input][$var];
		
		if ($path)
		{
			foreach ($path as $key)
			{
				if (is_array($key) && isset($var[$key]))
				{
					$var = $var[$key];
				}
				else
				{
					return is_array($default) ? [] : $default;
				}
			}
		}
		
		$this->recursive_set_type($var, $default);
		
		return $var;
	}
	
	/**
	* Приведение типов
	* Экранирование строк
	*/
	protected function set_type(&$result, $var, $type)
	{
		settype($var, $type);
		$result = $var;
		
		if ($type == 'string')
		{
			$result = trim(htmlspecialchars(str_replace(["\r\n", "\r", "\0"], ["\n", "\n", ''], $result), ENT_COMPAT, 'UTF-8'));
		}
	}
	
	/**
	* Рекурсивное приведение типов
	*/
	protected function recursive_set_type(&$var, $default)
	{
		if (is_array($var) !== is_array($default))
		{
			$var = is_array($default) ? [] : $default;
			return;
		}
		
		if (!is_array($default))
		{
			$type = gettype($default);
			$this->set_type($var, $var, $type);
			return;
		}
		
		if (empty($default))
		{
			$var = [];
			return;
		}
		
		list($default_key, $default_value) = each($default);
		$value_type = gettype($default_value);
		$key_type = gettype($default_key);
		
		$_var = $var;
		$var = [];
		
		foreach ($_var as $k => $v)
		{
			$this->set_type($k, $k, $key_type);
			$this->recursive_set_type($v, $default_value);
			$var[$k] = $v;
		}
	}
}
