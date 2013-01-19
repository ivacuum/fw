<?php
/**
* @package fw
* @copyright (c) 2013
*/

namespace fw\core;

use fw\cache\service as cache_service;
use fw\config\db as config_db;
use fw\db\mysqli as db_mysqli;
use fw\session\user;
use fw\template\twig;
use fw\traits\constants;

/**
* Контейнер приложения
*/
class application implements \ArrayAccess
{
	use constants;
	
	const VERSION = '1.0-dev';
	
	private $values;
	
	function __construct(array $values = array())
	{
		$this->values = $values;
		
		$app = $this;
		
		/* Автозагрузчик классов */
		$this['autoloader'] = $this->share(function() use ($app) {
			return (new autoloader($app['acm.prefix']))->register();
		});
		
		/* Шаблонизатор */
		$this['template'] = $this->share(function() {
			return new twig();
		});
		
		$this['profiler'] = $this->share(function() use ($app) {
			return new profiler($app['template']);
		});

		/* Данные запроса */
		$this['request'] = $this->share(function() {
			return new request();
		});
		
		/* Подключение к базе данных */
		$this['db'] = $this->share(function() use ($app) {
			return new db_mysqli($app['db.host'], $app['db.user'], $app['db.pass'], $app['db.name'], $app['db.port'], $app['db.sock'], $app['db.pers']);
		});
		
		/* Инициализация кэша */
		$this['cache'] = $this->share(function() use ($app) {
			$class = '\\fw\\cache\\driver\\' . $app['acm.type'];
			
			return new cache_service($app['db'], new $class($app['db'], $app['acm.prefix']));
		});

		/* Пользователь */
		$this['user'] = $this->share(function() use ($app) {
			return new user($app['cache'], $app['config'], $app['db'], $app['request']);
		});
		
		/* Привилегии */
		$this['auth'] = $this->share(function() {
			return new auth();
		});

		/* Настройки сайта и движка */
		$this['config'] = $this->share(function() use ($app) {
			return new config_db($app['cache'], $app['db'], $app['site_info'], CONFIG_TABLE);
		});

		/* Маршрутизатор запросов */
		$this['router'] = $this->share(function() use ($app) {
			return new router($app['auth'], $app['cache'], $app['config'], $app['db'], $app['profiler'], $app['request'], $app['template'], $app['user']);
		});

		/* Информация об обслуживаемом сайте */
		$this['site_info'] = $this->share(function() use ($app) {
			$site_info = $app['cache']->get_site_info_by_url($app['request']->hostname, $app['request']->url);
			
			return false !== $site_info ? $site_info : $app['cache']->get_site_info_by_url($app['request']->hostname);
		});
		
		/* Явный вызов автозагрузчика, чтобы он начал свою работу */
		$this['autoloader']->register_namespaces([
			'fw'       => __DIR__,
			'app'      => SITE_DIR . '../includes',
			// 'Geocoder' => __DIR__ . '/../lib/geocoder/1.1.6/Geocoder',
			// 'Imagine'  => __DIR__ . '/../lib/imagine/0.4.1/Imagine',
			// 'Monolog'  => __DIR__ . '/../lib/monolog/1.0.3/Monolog',
		])->register_prefixes([
			// 'Swift' => __DIR__ . '/../lib/swiftmailer/4.3/classes',
			'Twig'  => __DIR__ . '/../lib/twig/1.12.1',
		]);

		/**
		* Профайлер должен начать работать как можно раньше
		* Но не раньше автозагрузчика, так как профайлер зависит от шаблонизатора
		*/
		$this['profiler'];
	}
	
	/**
	* Расширение определенного объекта
	*
	* Полезно, когда необходимо расширить объект, не инициализируя его
	*/
	public function extend($id, \Closure $callable)
	{
		if (!array_key_exists($id, $this->values))
		{
			trigger_error(sprintf('Ключ "%s" не найден.', $id));
		}
		
		$factory = $this->values[$id];
		
		if (!($factory instanceof \Closure))
		{
			trigger_error(sprintf('Ключ "%s" не содержит объект.', $id));
		}
		
		return $this->values[$id] = function ($c) use ($callable, $factory)
		{
			return $callable($factory($c), $c);
		};
	}

	/**
	* Данный объект не будет вызван при обращении
	* Его необходимо вызывать вручную
	*/
	public function protect(\Closure $callable)
	{
		return function ($c) use ($callable)
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
			trigger_error(sprintf('Ключ "%s" не найден.', $id));
		}
		
		return $this->values[$id];
	}

	/**
	* Объект-одиночка
	*/
	public function share(\Closure $callable)
	{
		return function ($c) use ($callable)
		{
			static $object;
			
			if (null === $object)
			{
				$object = $callable($c);
			}
			
			return $object;
		};
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
