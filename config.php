<?php
/**
* @package fw
* @copyright (c) 2012
*/

namespace fw;

/* Настройки подключения к БД */
$dbhost = 'localhost';
$dbport = false;
$dbname = '';
$dbuser = '';
$dbpass = '';
$dbsock = '/tmp/mysql.sock';
$dbpers = false;

/* Настройки кэша */
$acm_prefix = 'src.ivacuum.ru';
$acm_type   = 'memcache';

$app = [
	/* Настройки подключения к БД */
	'db.host' => 'localhost',
	'db.port' => false,
	'db.name' => '',
	'db.user' => '',
	'db.pass' => '',
	'db.sock' => '',
	'db.pers' => false,
	
	/* Настройки кэша */
	'acm.prefix' => 'src.ivacuum.ru',
	'acm.type'   => 'memcache',
];
