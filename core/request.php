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
	
	public $hostname;
	public $http_methods = ['GET', 'POST', 'PUT', 'DELETE', 'HEAD', 'OPTIONS'];
	public $is_ajax;
	public $is_secure;
	public $isp;
	public $method;
	public $url;
	
	private $globals = [
		self::GET     => '_GET',
		self::POST    => '_POST',
		self::COOKIE  => '_COOKIE',
		self::REQUEST => '_REQUEST',
		self::SERVER  => '_SERVER'
	];
	
	function __construct()
	{
		$this->hostname  = $this->get_hostname();
		$this->is_ajax   = $this->header('X-Requested-With') == 'XMLHttpRequest';
		$this->is_secure = $this->server('HTTPS') == 'on';
		$this->isp       = $this->header('Provider', 'internet');
		$this->method    = strtolower($this->server('REQUEST_METHOD', 'get'));
		$this->url       = $this->get_requested_url();
		
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
	* Адрес страницы
	*/
	public function get_requested_url()
	{
		$url = $this->is_set('path') ? sprintf('/%s', $this->get('path', '')) : '';
		
		if (!$url)
		{
			$url = $this->server('PHP_SELF');
			$url = $url ?: $this->server('REQUEST_URI');
			$url = str_replace('index.php', '', $url);
		}
		
		$query_string = '';

		foreach ($_GET as $k => $v)
		{
			if ($k == 'path' || $k == 'sid')
			{
				continue;
			}

			if ($query_string)
			{
				$query_string .= '&';
			}

			$query_string .= @sprintf('%s=%s', $k, $v);
		}
		
		$url .= $query_string ? '?' . $query_string : '';
		
		return $url;
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
	public function redirect($url, $status_code = 302, $try_local_redirect = false)
	{
		if (false !== strpos(urldecode($url), "\n") || false !== strpos(urldecode($url), "\r"))
		{
			trigger_error('Bad URL.', E_USER_ERROR);
		}
	
		/**
		* Если пользователь из локальной сети,
		* то перенаправлять его следует на локальный домен
		*/
		if ($try_local_redirect && $this->isp == 'local')
		{
			$url = str_replace(['ivacuum.ru/', 't.local.ivacuum.ru/'], ['local.ivacuum.ru/', 't.ivacuum.ru/'], $url);
		}
	
		if ($status_code != 302)
		{
			$this->send_status_line($status_code);
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
	* Вывод заголовка
	*
	* send_status_line(404, 'Not Found');
	*
	* HTTP/1.x 404 Not Found
	*/
	public function send_status_line($code, $message = '')
	{
		if (!$message)
		{
			switch ($code)
			{
				case 200: $message = 'OK'; break;
				case 201: $message = 'Created'; break;
				case 202: $message = 'Accepted'; break;
				case 204: $message = 'No Content'; break;
			
				case 301: $message = 'Moved Permanently'; break;
				case 302: $message = 'Found'; break;
				case 303: $message = 'See Other'; break;
				case 304: $message = 'Not Modified'; break;
			
				case 400: $message = 'Bad Request'; break;
				case 401: $message = 'Unauthorized'; break;
				case 403: $message = 'Forbidden'; break;
				case 404: $message = 'Not Found'; break;
				case 405: $message = 'Method Not Allowed'; break;
				case 409: $message = 'Conflict'; break;
				case 410: $message = 'Gone'; break;
			
				case 500: $message = 'Internal Server Error'; break;
				case 501: $message = 'Not Implemented'; break;
				case 502: $message = 'Bad Gateway'; break;
				case 503: $message = 'Service Unavailable'; break;
				case 504: $message = 'Gateway Timeout'; break;
			
				default: return;
			}
		}
	
		if (substr(strtolower(PHP_SAPI), 0, 3) === 'cgi')
		{
			header(sprintf('Status: %d %s', $code, $message), true, $code);
			return;
		}
	
		if (false != $version = $this->server('SERVER_PROTOCOL'))
		{
			header(sprintf('%s %d %s', $version, $code, $message), true, $code);
			return;
		}
	
		header(sprintf('HTTP/1.0 %d %s', $code, $message), true, $code);
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
	private function set_type(&$result, $var, $type)
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
	private function recursive_set_type(&$var, $default)
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
