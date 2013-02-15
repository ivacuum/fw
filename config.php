<?php
/**
* @package fw
* @copyright (c) 2013
*/

namespace fw;

$app = [
	/* Настройки кэша */
	'acm.type' => 'memcache',
	
	/* Настройки кода подтверждения */
	'captcha.fonts' => ['tremble.ttf'],
	'captcha.type'  => 'gd',
	
	/* Настройки подключения к БД */
	'db.host' => 'localhost',
	'db.port' => false,
	'db.name' => '',
	'db.user' => '',
	'db.pass' => '',
	'db.sock' => '/tmp/mysql.sock',
	'db.pers' => false,
	
	/* Пути к папкам */
	'dir.app'             => SITE_DIR . '../includes',
	'dir.fonts'           => FW_DIR . 'assets/fonts',
	'dir.fw'              => FW_DIR,
	'dir.lib'             => FW_DIR . '../lib',
	'dir.logs'            => SITE_DIR . '../logs',
	'dir.templates.app'   => SITE_DIR . '../templates',
	'dir.templates.cache' => SITE_DIR . '../cache/templates',
	'dir.templates.fw'    => FW_DIR . 'templates',
	
	/* Пути к файлам */
	'file.cron.allowed' => 'cron_allowed',
	'file.cron.running' => 'cron_running',
	
	/* Версии библиотек */
	'version.geocoder' => '1.1.6',
	'version.imagine'  => '0.4.1.',
	'version.monolog'  => '1.0.3',
	'version.swift'    => '4.3.0',
	'version.twig'     => '1.12.1',
];
