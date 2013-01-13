<?php
/**
* @package fw
* @copyright (c) 2013
*/

namespace fw;

/**
* Настройки, необходимые для
* функционирования сайта
*/
define('FW_DIR', __DIR__ . '/');
define('SITE_DIR', rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/');

date_default_timezone_set('Europe/Moscow');
error_reporting(E_ALL);
mb_internal_encoding('utf-8');

autoloader::register();

/* Профайлер подключается первым */
$profiler = new core\profiler();

require(FW_DIR . 'functions.php');
require(FW_DIR . 'config.php');

if (file_exists(SITE_DIR . '../config.php'))
{
	require(SITE_DIR . '../config.php');
}

/* Собственный обработчик ошибок */
core\errorhandler::register();

$request = new core\request();

/* Инициализация кэша */
$factory = new cache\factory($acm_type, $acm_prefix);
$cache   = $factory->get_service();

/* Инициализация классов */
$db   = new db\mysqli($dbhost, $dbuser, $dbpass, $dbname, $dbport, $dbsock, $dbpers);
$user = new session\user();
$auth = new core\auth();

if (false === $site_info = get_site_info_by_url($user->domain, $user->page))
{
	$site_info = get_site_info_by_url($user->domain);
}

$config   = new config\db($site_info);
$template = new template\twig();

/* Планировщику задач понадобится путь к папке проекта */
if (SITE_DIR != $config['site_dir'])
{
	$config->set('site_dir', SITE_DIR);
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
		if (false === strpos($class, '\\'))
		{
			return;
		}

		list($prefix, $filename) = explode('/', str_replace('\\', '/', $class), 2);
		
		if ($prefix == 'fw' && file_exists(FW_DIR . "{$filename}.php"))
		{
			require(FW_DIR . "{$filename}.php");
			return true;
		}
		elseif ($prefix == 'app' && file_exists(SITE_DIR . "../includes/{$filename}.php"))
		{
			require(SITE_DIR . "../includes/{$filename}.php");
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
