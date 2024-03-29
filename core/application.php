<?php namespace fw\core;

use Guzzle\Http\Client;
use Guzzle\Log\MonologLogAdapter;
use Guzzle\Log\MessageFormatter;
use Guzzle\Plugin\Log\LogPlugin;
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

class application implements \ArrayAccess
{
	const VERSION = '1.6.5';

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

		$this['profiler'] = function() use ($app) {
			return new profiler(START_TIME, $app['profiler.options']);
		};

		$this['template'] = function() use ($app) {
			return new smarty([$app['dir.templates.app'], $app['dir.templates.fw']], $app['dir.templates.cache']);
		};

		$this['request'] = function() use ($app) {
			return new request($app['request.options']);
		};

		$this['db'] = function() use ($app) {
			return new db_mysqli($app['cache.driver'], $app['profiler'], $app['db.options']);
		};

		$this['cache.driver'] = function() use ($app) {
			$class = "\\fw\\cache\\driver\\{$app['cache.driver.options']['type']}";
			return new $class($app['cache.driver.options']);
		};

		$this['cache'] = function() use ($app) {
			if (file_exists("{$app['dir.app']}/cache/service.php")) {
				return new \app\cache\service($app['db'], $app['cache.driver']);
			}

			return new \fw\cache\service($app['db'], $app['cache.driver']);
		};

		$this['user'] = function() use ($app) {
			return (new user($app['cache'], $app['config'], $app['db'], $app['request'], $app['session.options'], $app['site_info']['id'], $app['urls']['signin']))
				->setup();
		};

		$this['auth'] = function() use ($app) {
			return (new auth($app['cache'], $app['db'], $app['user']))
				->init($app['user']->data);
		};

		/* Настройки сайта и движка */
		$this['config'] = function() use ($app) {
			return new config_db($app['cache'], $app['db'], $app['site_info']);
		};

		$this['router'] = function() use ($app) {
			return (new router($app['router.options']))
				->_set_app($app);
		};

		/* Информация об обслуживаемом сайте */
		$this['site_info'] = function() use ($app) {
			if (false === $site_info = $app['cache']->get_site_info_by_url($app['request']->hostname, $app['request']->url)) {
				trigger_error('Сайт не найден', E_USER_ERROR);
			}

			$app['request']->set_language($site_info['language'])
				->set_server_name($site_info['domain']);

			setlocale(LC_ALL, $site_info['locale']);

			return $site_info;
		};

		$this['captcha'] = function() use ($app) {
			$class = "\\fw\\captcha\\driver\\{$app['captcha.type']}";

			return new captcha_service($app['config'], $app['db'], $app['request'], $app['user'], new $class($app['dir.fonts'], $app['captcha.fonts']));
		};

		$this['captcha_validator'] = function() use ($app) {
			return new captcha_validator($app['config'], $app['db'], $app['request'], $app['user']);
		};

		$this['cron'] = function() use ($app) {
			return (new cron_manager($app['dir.logs'], $app['file.cron.allowed'], $app['file.cron.running']))
				->_set_app($app);
		};

		$this['form'] = function() use ($app) {
			return new form($app['config'], $app['db'], $app['request'], $app['template']);
		};

		$this['http_client'] = function() use ($app) {
			$client = new Client();
			$client->addSubscriber(new LogPlugin(new MonologLogAdapter($app['logger']), $app['logger.options']['guzzle.format']));

			return $client;
		};

		$this['logger'] = function() use ($app) {
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

			// if ($email) {
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
		};

		$this['mailer'] = function() use ($app) {
			return new mailer($app['config'], $app['template']);
		};

		$this['sphinx'] = function() use ($app) {
			return new db_sphinx($app['cache.driver'], $app['profiler'], $app['sphinx.options']);
		};

		if ($this['errorhandler.options']['enabled']) {
			errorhandler::register($this['errorhandler.options']);
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

    #[\ReturnTypeWillChange]
	public function offsetExists($offset)
	{
		return array_key_exists($offset, $this->values);
	}

    #[\ReturnTypeWillChange]
	public function offsetGet($offset)
	{
		if (!isset($this->keys[$offset])) {
			throw new \InvalidArgumentException(sprintf('Ключ «%s» не найден.', $offset));
		}

		if (isset($this->raw[$offset])
			|| !is_object($this->values[$offset])
			|| isset($this->protected[$this->values[$offset]])
			|| !method_exists($this->values[$offset], '__invoke')
		) {
			return $this->values[$offset];
		}

		if (isset($this->factories[$this->values[$offset]])) {
			return $this->values[$offset]($this);
		}

		$this->frozen[$offset] = true;
		$this->raw[$offset] = $this->values[$offset];

		return $this->values[$offset] = $this->values[$offset]($this);
	}

    #[\ReturnTypeWillChange]
	public function offsetSet($offset, $value)
	{
		if (isset($this->frozen[$offset])) {
			throw new \RuntimeException(sprintf('Нельзя изменять замороженный объект «%s»', $offset));
		}

		$this->values[$offset] = $value;
		$this->keys[$offset] = true;
	}

    #[\ReturnTypeWillChange]
	public function offsetUnset($offset)
	{
		unset($this->values[$offset]);

		if (isset($this->keys[$offset])) {
			if (is_object($this->values[$offset])) {
				unset($this->factories[$this->values[$offset]], $this->protected[$this->values[$offset]]);
			}

			unset($this->values[$offset], $this->frozen[$offset], $this->raw[$offset], $this->keys[$offset]);
		}
	}
}
