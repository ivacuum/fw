<?php
/**
* @package fw
* @copyright (c) 2013
*/

namespace fw;

use fw\core\application;
use fw\core\errorhandler;

/**
* Настройки, необходимые для
* функционирования сайта
*/
define('FW_DIR', __DIR__ . '/');
define('SITE_DIR', rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/');

date_default_timezone_set('Europe/Moscow');
error_reporting(E_ALL);
mb_internal_encoding('utf-8');

require(FW_DIR . 'core/profiler.php');
require(FW_DIR . 'core/application.php');
require(FW_DIR . 'core/autoloader.php');
require(FW_DIR . 'functions.php');
require(FW_DIR . 'config.php');

if (file_exists(SITE_DIR . '../config.php'))
{
	require(SITE_DIR . '../config.php');
}

$app = new application($app);

$app['autoloader']->register_namespaces(array(
	'fw'       => __DIR__,
	'app'      => SITE_DIR . '../includes',
	// 'Geocoder' => __DIR__ . '/../lib/geocoder/1.1.6/Geocoder',
	// 'Imagine'  => __DIR__ . '/../lib/imagine/0.4.1/Imagine',
	// 'Monolog'  => __DIR__ . '/../lib/monolog/1.0.3/Monolog',
	// 'Swift'    => __DIR__ . '/../lib/swiftmailer/4.3/classes/Swift',
	'Twig'     => __DIR__ . '/../lib/twig/1.12/Twig',
));

$profiler = $app['profiler'];

/* Внедрение зависимостей */
// $app['cache']->_set_db($app['db']);
// $app['db']->_set_cache($app['cache'])
// 	->_set_profiler($app['profiler']);
// $app['user']->_set_db($app['db']);

/* Собственный обработчик ошибок */
errorhandler::register();

$request = $app['request'];

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
$template = $app['template'];

/* Планировщику задач понадобится путь к папке проекта */
if (SITE_DIR != $config['site_dir'])
{
	$config->set('site_dir', SITE_DIR);
}
