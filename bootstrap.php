<?php namespace fw;

use fw\core\application;

define('START_TIME', microtime(true));
define('FW_DIR', __DIR__ . '/');
define('SITE_DIR', rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/');

date_default_timezone_set('Europe/Moscow');
ini_set('display_errors', false);
error_reporting(E_ALL);
mb_internal_encoding('utf-8');

require FW_DIR . 'config.php';
require SITE_DIR . '../config.php';

$app = new application($app);
