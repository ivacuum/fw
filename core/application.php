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

/**
* Контейнер приложения
*/
class application implements \ArrayAccess
{
	const VERSION = '1.0-dev';
	
	private $values;
	
	function __construct(array $values = array())
	{
		$this->values = $values;
		
		$app = $this;
		
		/* Автозагрузчик классов */
		$this['autoloader'] = $this->share(function() use ($app) {
			$loader = new autoloader($app['acm.prefix']);
			$loader->register();
			
			return $loader;
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
			return new cache_service(new $class($app['acm.prefix']));
		});

		/* Пользователь */
		$this['user'] = $this->share(function() use ($app) {
			return new user($app['request']);
		});

		/* Настройки сайта и движка */
		// $this['config'] = $this->share(function() use ($app) {
		// 	return new config_db($app['cache'], $app['db'], $app['site_info'], CONFIG_TABLE);
		// });

		/* Маршрутизатор запросов */
		// $this['router'] = $this->share(function() use ($app) {
		// 	return new router($app['cache'], $app['config'], $app['db'], $app['request'], $app['template'], $app['user']);
		// });
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
