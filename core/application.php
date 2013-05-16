<?php
/**
* @package fw
* @copyright (c) 2013
*/

namespace fw\core;

use ArrayAccess;
use Closure;
use Monolog\Logger;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\NativeMailerHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Processor\WebProcessor;
use fw\captcha\service as captcha_service;
use fw\captcha\validator as captcha_validator;
use fw\cron\manager as cron_manager;
use fw\config\db as config_db;
use fw\db\mysqli as db_mysqli;
use fw\db\sphinx as db_sphinx;
use fw\logger\handlers\db as DBHandler;
use fw\session\user;
use fw\template\smarty;

class application implements ArrayAccess
{
	const VERSION = '1.5.0';
	
	private $values;
	
	function __construct(array $values = [])
	{
		$this->values = $values;
		$app = $this;
		
		$this['profiler'] = $this->share(function() use ($app) {
			return new profiler(START_TIME, $app['profiler.options']);
		});
		
		$this['autoloader'] = $this->share(function() use ($app) {
			require FW_DIR . 'core/autoloader.php';
			
			return (new autoloader())
				->register_namespaces($app['autoloader.namespaces'])
				->register_pears($app['autoloader.pears'])
				->register();
		});
		
		$this['template'] = $this->share(function() use ($app) {
			define('SMARTY_DIR', "{$app['dir.lib']}/smarty/{$app['version.smarty']}/Smarty/");
			require SMARTY_DIR . 'Smarty.class.php';

			return new smarty([$app['dir.templates.app'], $app['dir.templates.fw']], $app['dir.templates.cache']);
		});
		
		$this['request'] = $this->share(function() use ($app) {
			return new request($app['request.options']);
		});
		
		$this['db'] = $this->share(function() use ($app) {
			return new db_mysqli($app['cache.driver'], $app['profiler'], $app['db.options']);
		});
		
		$this['cache.driver'] = $this->share(function() use ($app) {
			$class = "\\fw\\cache\\driver\\{$app['cache.driver.options']['type']}";
			return new $class($app['cache.driver.options']);
		});
		
		$this['cache'] = $this->share(function() use ($app) {
			if (file_exists("{$app['dir.app']}/cache/service.php"))
			{
				return new \app\cache\service($app['db'], $app['cache.driver']);
			}
			
			return new \fw\cache\service($app['db'], $app['cache.driver']);
		});

		$this['user'] = $this->share(function() use ($app) {
			return (new user($app['cache'], $app['config'], $app['db'], $app['request'], $app['session.options'], $app['site_info']['id'], $app['urls']['signin']))
				->setup();
		});
		
		$this['auth'] = $this->share(function() use ($app) {
			return (new auth($app['cache'], $app['db'], $app['user']))
				->init($app['user']->data);
		});

		/* Настройки сайта и движка */
		$this['config'] = $this->share(function() use ($app) {
			return new config_db($app['cache'], $app['db'], $app['site_info']);
		});

		$this['router'] = $this->share(function() use ($app) {
			return (new router($app['router.options']))
				->_set_app($app);
		});

		/* Информация об обслуживаемом сайте */
		$this['site_info'] = $this->share(function() use ($app) {
			if (false === $site_info = $app['cache']->get_site_info_by_url($app['request']->hostname, $app['request']->url))
			{
				trigger_error('Сайт не найден', E_USER_ERROR);
			}
			
			$app['request']->set_language($site_info['language'])
				->set_server_name($site_info['domain']);
			
			setlocale(LC_ALL, $site_info['locale']);
			
			return $site_info;
		});
		
		$this['captcha'] = $this->share(function() use ($app) {
			$class = "\\fw\\captcha\\driver\\{$app['captcha.type']}";

			return new captcha_service($app['config'], $app['db'], $app['request'], $app['user'], new $class($app['dir.fonts'], $app['captcha.fonts']));
		});
		
		$this['captcha_validator'] = $this->share(function() use ($app) {
			return new captcha_validator($app['config'], $app['db'], $app['request'], $app['user']);
		});
		
		$this['cron'] = $this->share(function() use ($app) {
			return (new cron_manager($app['dir.logs'], $app['file.cron.allowed'], $app['file.cron.running']))
				->_set_app($app);
		});
		
		$this['form'] = $this->share(function() use ($app) {
			return new form($app['config'], $app['db'], $app['request'], $app['template']);
		});
		
		$this['logger'] = $this->share(function() use ($app) {
			$logger = new Logger($app['site_info']['domain']);
			$email  = $app['errorhandler.options']['email.error'];
			
			if (PHP_SAPI == 'cli')
			{
				$handler = new StreamHandler('php://stdout');
				$handler->setFormatter(new LineFormatter($app['logger.options']['cron.format']));
				
				/* debug и выше */
				$logger->pushHandler($handler);
				
				return $logger;
			}

			/* info и выше */
			$logger->pushHandler(new DBHandler($app['db'], $app['request']));
			
			// if ($email)
			// {
			// 	/* warn и выше */
			// 	$logger->pushHandler(new NativeMailerHandler($email, $app['request']->server_name, 'fw@' . gethostname()));
			// }

			$logger->pushProcessor(function($record) use ($app) {
				$record['extra']['site_id'] = $app['site_info']['id'];
				$record['extra']['user_id'] = $app['user']['user_id'];
				$record['extra']['ip'] = $app['user']->ip;
				
				return $record;
			});
			
			return $logger;
		});
		
		$this['mailer'] = $this->share(function() use ($app) {
			require "{$app['dir.lib']}/swiftmailer/{$app['version.swift']}/swift_init.php";

			return new mailer($app['config'], $app['template']);
		});
		
		$this['sphinx'] = $this->share(function() use ($app) {
			return new db_sphinx($app['cache.driver'], $app['profiler'], $app['sphinx.options']);
		});
		
		foreach ($this['include.files'] as $file)
		{
			require $file;
		}
		
		if ($this['autoloader.options']['enabled'])
		{
			$this['autoloader'];
		}
		
		if ($this['errorhandler.options']['enabled'])
		{
			errorhandler::register($this['errorhandler.options']);
		}
	}
	
	/**
	* Расширение определенного объекта
	*
	* Полезно, когда необходимо расширить объект, не инициализируя его
	*/
	public function extend($id, Closure $callable)
	{
		if (!array_key_exists($id, $this->values))
		{
			trigger_error(sprintf('Ключ "%s" не найден.', $id));
		}
		
		$factory = $this->values[$id];
		
		if (!($factory instanceof Closure))
		{
			trigger_error(sprintf('Ключ "%s" не содержит объект.', $id));
		}
		
		return $this->values[$id] = function($c) use ($callable, $factory)
		{
			return $callable($factory($c), $c);
		};
	}

	/**
	* Данный объект не будет вызван при обращении
	* Его необходимо вызывать вручную
	*/
	public function protect(Closure $callable)
	{
		return function($c) use ($callable)
		{
			return $callable;
		};
	}
	
	/**
	* Извлечение параметра или определения объекта
	*/
	public function raw($id)
	{
		if (!array_key_exists($id, $this->values))
		{
			trigger_error("Ключ «{$id}» не найден.");
		}
		
		return $this->values[$id];
	}

	/**
	* Объект-одиночка
	*/
	public function share(Closure $callable)
	{
		return function($c) use ($callable)
		{
			static $object;
			
			if (null === $object)
			{
				$object = $callable($c);
			}
			
			return $object;
		};
	}
	
	public function load_constants($prefix)
	{
		if (!function_exists('apc_fetch'))
		{
			return false;
		}

		return apc_load_constants("{$prefix}_constants");
	}

	public function set_constants($prefix, $constants)
	{
		if (!function_exists('apc_fetch'))
		{
			foreach ($constants as $key => $value)
			{
				define($key, $value);
			}
		
			return;
		}
	
		apc_define_constants("{$prefix}_constants", $constants);
	}

	public function keys()
	{
		return array_keys($this->values);
	}
	
	public function offsetExists($id)
	{
		return array_key_exists($id, $this->values);
	}
	
	public function offsetGet($id)
	{
		if (!array_key_exists($id, $this->values))
		{
			trigger_error(sprintf('Ключ "%s" не найден.', $id));
		}
		
		$is_factory = is_object($this->values[$id]) && method_exists($this->values[$id], '__invoke');
		
		return $is_factory ? $this->values[$id]($this) : $this->values[$id];
	}
	
	public function offsetSet($id, $value)
	{
		$this->values[$id] = $value;
	}
	
	public function offsetUnset($id)
	{
		unset($this->values[$id]);
	}
}
