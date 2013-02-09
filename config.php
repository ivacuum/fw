<?php
/**
* @package fw
* @copyright (c) 2013
*/

namespace fw;

$app = [
	/* Настройки подключения к БД */
	'db.host' => 'localhost',
	'db.port' => false,
	'db.name' => '',
	'db.user' => '',
	'db.pass' => '',
	'db.sock' => '/tmp/mysql.sock',
	'db.pers' => false,
	
	/* Настройки кэша */
	'acm.type' => 'memcache',
];
