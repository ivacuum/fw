<?php
/**
* @package src.ivacuum.ru
* @copyright (c) 2012
*/

namespace engine;

/**
* Настройки, необходимые для
* функционирования сайта
*/
date_default_timezone_set('Europe/Moscow');
error_reporting(E_ALL);
mb_internal_encoding('utf-8');

$site_root_path = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/';
$src_root_path  = dirname(__FILE__) . '/';

autoloader::register();

/* Профайлер подключается первым */
$profiler = new core\profiler();

require($src_root_path . 'functions.php');
require($src_root_path . 'config.php');

if( file_exists($site_root_path . '../config.php') )
{
	require($site_root_path . '../config.php');
}

/* Собственный обработчик ошибок */
core\error_handler::register();

$request = new core\request();

/* Инициализация кэша */
$factory = new cache\factory($acm_type, $acm_prefix);
$cache   = $factory->get_service();

/* Инициализация классов */
$db   = new db\mysqli($dbhost, $dbuser, $dbpass, $dbname, $dbport, $dbsock, $dbpers);
$user = new session\user();
$auth = new core\auth();

if( false === $site_info = get_site_info_by_url($user->domain, $user->page) )
{
	$site_info = get_site_info_by_url($user->domain);
}

$config   = new config\db($site_info);
$template = new template\twig();

/* Планировщику задач понадобится путь к папке проекта */
if( $site_root_path != $config['site_dir'] )
{
	$config->set('site_dir', $site_root_path);
}

/**
* Автозагрузчик классов
*/
class autoloader
{
	/**
	* Загрузка класса
	*/
	static public function autoload($class)
	{
		global $site_root_path, $src_root_path;

		if( strpos($class, '\\') === false )
		{
			return;
		}

		list($prefix, $filename) = explode('/', str_replace('\\', '/', $class), 2);
		
		if( $prefix == 'engine' && file_exists($src_root_path . $filename . '.php') )
		{
			require($src_root_path . $filename . '.php');
			return true;
		}
		elseif( $prefix == 'app' && file_exists($site_root_path . '../includes/' . $filename . '.php') )
		{
			require($site_root_path . '../includes/' . $filename . '.php');
			return true;
		}
		
		return false;
	}
	
	/**
	* Регистрация загрузчика
	*/
	static public function register()
	{
		spl_autoload_register(array(new self, 'autoload'));
	}
}
