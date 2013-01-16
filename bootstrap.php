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

require(FW_DIR . 'traits/constants.php');
require(FW_DIR . 'core/profiler.php');
require(FW_DIR . 'core/application.php');
require(FW_DIR . 'core/autoloader.php');
require(FW_DIR . 'functions.php');
require(FW_DIR . 'config.php');
require(SITE_DIR . '../config.php');

$app = new application($app);

require(FW_DIR . 'constants.php');
require(SITE_DIR . '../constants.php');

$profiler = $app['profiler'];

/* Внедрение зависимостей */
$app['db']->_set_cache($app['cache'])
	->_set_profiler($app['profiler']);

/* Собственный обработчик ошибок */
errorhandler::register();

$request  = $app['request'];
$cache    = $app['cache'];
$db       = $app['db'];
$user     = $app['user'];
$auth     = $app['auth'];
$config   = $app['config'];
$template = $app['template'];
