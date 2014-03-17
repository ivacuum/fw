<?php namespace fw\core;

use Guzzle\Http\Client;
use Guzzle\Log\MonologLogAdapter;
use Guzzle\Log\MessageFormatter;
use Guzzle\Plugin\Log\LogPlugin;
use Monolog\Logger;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Processor\WebProcessor;
use Symfony\Component\EventDispatcher\EventDispatcher;
use fw\captcha\service as captcha_service;
use fw\captcha\validator as captcha_validator;
use fw\cron\manager as cron_manager;
use fw\config\db as config_db;
use fw\db\mysqli as db_mysqli;
use fw\db\sphinx as db_sphinx;
use fw\Logger\DbHandler;
use fw\Logger\MailHandler;
use fw\session\user;
use fw\template\smarty;

class application implements \ArrayAccess
{
	const VERSION = '1.6.3';
	
	private $values = [];
	private $factories;
	private $protected;
	private $frozen = [];
	private $raw = [];
	private $keys = [];
	
	function __construct(array $values = [])
	{
		$this->factories = new \SplObjectStorage();
		$this->protected = new \SplObjectStorage();
		
		foreach ($values as $key => $value) {
			$this->offsetSet($key, $value);
		}
		
		$app = $this;
		
		$this['profiler'] = function () use ($app) {
			return new profiler(START_TIME, $app['profiler.options']);
		};
		
		$this['autoloader'] = function () use ($app) {
			require FW_DIR . 'core/autoloader.php';
			
			return (new autoloader())
				->register_namespaces($app['autoloader.namespaces'])
				->register_pears($app['autoloader.pears'])
				->register();
		};
		
		$this['template'] = function () use ($app) {
			define('SMARTY_DIR', "{$app['dir.lib']}/smarty/{$app['version.smarty']}/Smarty/");
			require SMARTY_DIR . 'Smarty.class.php';

			return new smarty([$app['dir.templates.app'], $app['dir.templates.fw']], $app['dir.templates.cache']);
		};
		
		$this['request'] = function () use ($app) {
			return new request($app['request.options']);
		};
		
		$this['events'] = function () use ($app) {
			$dispatcher = new EventDispatcher();
			$subscriber = (new EventSubscriber())
				->_set_app($app);
			$dispatcher->addSubscriber($subscriber);
			return $dispatcher;
		};
		
		$this['db'] = function () use ($app) {
			return new db_mysqli($app['cache.driver'], $app['events'], $app['profiler'], $app['db.options']);
		};
		
		$this['cache.driver'] = function () use ($app) {
			$class = "\\fw\\cache\\driver\\{$app['cache.driver.options']['type']}";
			return new $class($app['cache.driver.options']);
		};
		
		$this['cache'] = function () use ($app) {
			if (file_exists("{$app['dir.app']}/cache/service.php")) {
				return new \app\cache\service($app['db'], $app['cache.driver']);
			}
			
			return new \fw\cache\service($app['db'], $app['cache.driver']);
		};

		$this['user'] = function () use ($app) {
			return (new user($app['cache'], $app['config'], $app['db'], $app['request'], $app['session.options'], $app['site_info']['id'], $app['urls']['signin']))
				->setup();
		};
		
		$this['auth'] = function () use ($app) {
			return (new auth($app['cache'], $app['db'], $app['user']))
				->init($app['user']->data);
		};

		/* Настройки сайта и движка */
		$this['config'] = function () use ($app) {
			return new config_db($app['cache'], $app['db'], $app['site_info']);
		};

		$this['router'] = function () use ($app) {
			return (new router($app['router.options']))
				->_set_app($app);
		};

		/* Информация об обслуживаемом сайте */
		$this['site_info'] = function () use ($app) {
			if (false === $site_info = $app['cache']->get_site_info_by_url($app['request']->hostname, $app['request']->url)) {
				trigger_error('Сайт не найден', E_USER_ERROR);
			}
			
			$app['request']->set_language($site_info['language'])
				->set_server_name($site_info['domain']);
			
			setlocale(LC_ALL, $site_info['locale']);
			
			return $site_info;
		};
		
		$this['captcha'] = function () use ($app) {
			$class = "\\fw\\captcha\\driver\\{$app['captcha.type']}";

			return new captcha_service($app['config'], $app['db'], $app['request'], $app['user'], new $class($app['dir.fonts'], $app['captcha.fonts']));
		};
		
		$this['captcha_validator'] = function () use ($app) {
			return new captcha_validator($app['config'], $app['db'], $app['request'], $app['user']);
		};
		
		$this['cron'] = function () use ($app) {
			return (new cron_manager($app['dir.logs'], $app['file.cron.allowed'], $app['file.cron.running']))
				->_set_app($app);
		};
		
		$this['form'] = function () use ($app) {
			return new form($app['config'], $app['db'], $app['request'], $app['template']);
		};
		
		$this['http_client'] = function () use ($app) {
			$client = new Client();
			$client->addSubscriber(new LogPlugin(new MonologLogAdapter($app['logger']), $app['logger.options']['guzzle.format']));
			
			return $client;
		};
		
		$this['logger'] = function () use ($app) {
			$logger = new Logger($app['site_info']['domain']);
			
			if (PHP_SAPI == 'cli') {
				$handler = new StreamHandler('php://stdout');
				$handler->setFormatter(new LineFormatter($app['logger.options']['cron.format']));
				
				/* debug и выше */
				$logger->pushHandler($handler);
				
				return $logger;
			}

			/* info и выше */
			$logger->pushHandler(new DbHandler($app['db'], $app['request']));
			
			/* error и выше */
			$logger->pushHandler(new MailHandler($app['user']));

			$logger->pushProcessor(function ($record) use ($app) {
				$record['extra']['site_id'] = $app['site_info']['id'];
				$record['extra']['user_id'] = $app['user']['user_id'];
				$record['extra']['ip'] = $app['user']->ip;
				
				return $record;
			});
			
			return $logger;
		};
		
		$this['mailer'] = function () use ($app) {
			require "{$app['dir.lib']}/swiftmailer/{$app['version.swift']}/swift_init.php";

			return new mailer($app['config'], $app['template']);
		};
		
		$this['sphinx'] = function () use ($app) {
			return new db_sphinx($app['cache.driver'], $app['profiler'], $app['sphinx.options']);
		};
		
		foreach ($this['include.files'] as $file) {
			require $file;
		}
		
		if ($this['autoloader.options']['enabled']) {
			$this['autoloader'];
		}
		
		if ($this['errorhandler.options']['enabled']) {
			errorhandler::register($this['errorhandler.options'], $this['events']);
		}
	}
	
	/**
	* Расширение определенного объекта
	*
	* Полезно, когда необходимо расширить объект, не инициализируя его
	*/
	public function extend($id, $callable)
	{
        if (!isset($this->keys[$id])) {
            throw new \InvalidArgumentException(sprintf('Ключ «%s» не найден.', $id));
        }

        if (!is_object($this->values[$id]) || !method_exists($this->values[$id], '__invoke')) {
            throw new \InvalidArgumentException(sprintf('По ключу «%s» расположен не объект.', $id));
        }

        if (!is_object($callable) || !method_exists($callable, '__invoke')) {
            throw new \InvalidArgumentException('Расширение должно быть замыканием или объектом с определенным методом __invoke().');
        }

        $factory = $this->values[$id];

        $extended = function ($c) use ($callable, $factory) {
            return $callable($factory($c), $c);
        };

        if (isset($this->factories[$factory])) {
            $this->factories->detach($factory);
            $this->factories->attach($extended);
        }

        return $this[$id] = $extended;
	}
	
	/**
	* Задание фабричного сервиса
	* При каждом вызове будет создаваться новый экземпляр
	*/
	public function factory($callable)
	{
		if (!is_object($callable) || !method_exists($callable, '__invoke')) {
			throw new \InvalidArgumentException('Необходимо передать замыкание или объект с определенным методом __invoke().');
		}
		
		$this->factories->attach($callable);
		
		return $callable;
	}

	/**
	* Данный объект не будет вызван при обращении
	* Его необходимо вызывать вручную
	*/
	public function protect($callable)
	{
		if (!is_object($callable) || !method_exists($callable, '__invoke')) {
			throw new \InvalidArgumentException('Необходимо передать замыкание или объект с определенным методом __invoke().');
		}
		
		$this->protected->attach($callable);
		
		return $callable;
	}
	
	/**
	* Извлечение параметра или замыкания, которое определяет объект
	*/
	public function raw($id)
	{
		if (!isset($this->keys[$id])) {
			throw new \InvalidArgumentException(sprintf('Ключ «%s» не найден.', $id));
		}
		
		if (isset($this->raw[$id])) {
			return $this->raw[$id];
		}
		
		return $this->values[$id];
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
		if (!isset($this->keys[$id])) {
			throw new \InvalidArgumentException(sprintf('Ключ «%s» не найден.', $id));
		}
		
		if (isset($this->raw[$id])
			|| !is_object($this->values[$id])
			|| isset($this->protected[$this->values[$id]])
			|| !method_exists($this->values[$id], '__invoke')
		) {
			return $this->values[$id];
		}
		
		if (isset($this->factories[$this->values[$id]])) {
			return $this->values[$id]($this);
		}
		
		$this->frozen[$id] = true;
		$this->raw[$id] = $this->values[$id];
		
		return $this->values[$id] = $this->values[$id]($this);
	}
	
	public function offsetSet($id, $value)
	{
		if (isset($this->frozen[$id])) {
			throw new \RuntimeException(sprintf('Нельзя изменять замороженный объект «%s»', $id));
		}
		
		$this->values[$id] = $value;
		$this->keys[$id] = true;
	}
	
	public function offsetUnset($id)
	{
		unset($this->values[$id]);
		
		if (isset($this->keys[$id])) {
			if (is_object($this->values[$id])) {
				unset($this->factories[$this->values[$id]], $this->protected[$this->values[$id]]);
			}
			
			unset($this->values[$id], $this->frozen[$id], $this->raw[$id], $this->keys[$id]);
		}
	}
}
