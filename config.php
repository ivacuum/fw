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
	
	/* Пути */
	'dir.app' => SITE_DIR . '../includes',
	'dir.fw'  => FW_DIR,
	'dir.lib' => FW_DIR . '../lib',
	
	/* Версии библиотек */
	'version.geocoder' => '1.1.6',
	'version.imagine'  => '0.4.1.',
	'version.monolog'  => '1.0.3',
	'version.swift'    => '4.3.0',
	'version.twig'     => '1.12.1',
];
