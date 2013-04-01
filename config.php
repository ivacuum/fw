<?php
/**
* @package fw
* @copyright (c) 2013
*/

namespace fw;

$app = [
	/* Настройки кэша */
	'acm.prefix'        => '',
	'acm.shared_prefix' => '',
	'acm.type'          => 'null',
	
	/* Настройки кода подтверждения */
	'captcha.fonts' => ['tremble.ttf'],
	'captcha.type'  => 'gd',
	
	/* Настройки отладки */
	'debug.ips' => [],
	
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
	'dir.fw'              => rtrim(FW_DIR, '/'),
	'dir.lib'             => FW_DIR . '../lib',
	'dir.logs'            => SITE_DIR . '../logs',
	'dir.templates.app'   => SITE_DIR . '../templates',
	'dir.templates.cache' => SITE_DIR . '../cache/templates',
	'dir.templates.fw'    => FW_DIR . 'templates',
	
	/* Настройки обработчика ошибок */
	'errorhandler.options' => [
		'mail.error' => 'fw@ivacuum.ru',
		'standalone' => false,
	],
	
	/* Пути к файлам */
	'file.cron.allowed' => 'cron_allowed',
	'file.cron.running' => 'cron_running',
	
	/* Замена доменов на их локальные варианты */
	'request.local_redirect.from' => '',
	'request.local_redirect.to'   => '',
	
	/* Настройки сессий */
	'session.config' => [
		'name'            => 'sid',
		'cookie_path'     => '/',
		'cookie_domain'   => '',
		'cookie_secure'   => false,
		'cookie_httponly' => true,
		'cookie_lifetime' => 0,
		'referer_check'   => false,
		'hash_function'   => 'sha1',
	],
	
	/* Настройки подключения к поисковику sphinx */
	'sphinx.host' => 'localhost',
	'sphinx.port' => false,
	'sphinx.sock' => '/tmp/sphinx.sock',
	
	/* Ссылки */
	'urls' => [
		'register' => '/',
		'signin'   => '/',
		'signout'  => '/',
	],
	
	/* Версии библиотек */
	'version.guzzle'   => '3.3.1',
	'version.smarty'   => '3.1.13',
	'version.swift'    => '4.3.0',
	'version.symfony'  => '2.2.0',
];
