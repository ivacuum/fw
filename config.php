<?php namespace fw;

$app = [
	/* Настройки кэша */
	'cache.driver.options' => [
		'host'          => '',
		'port'          => 0,
		'prefix'        => '',
		'shared_prefix' => '',
		'type'          => 'null',
	],
	
	/* Настройки кода подтверждения */
	'captcha.fonts' => ['tremble.ttf'],
	'captcha.type'  => 'gd',
	
	/* Настройки подключения к БД */
	'db.options' => [
		'host' => 'localhost',
		'port' => false,
		'name' => '',
		'user' => '',
		'pass' => '',
		'sock' => '/tmp/mysql.sock',
		'pers' => false,
	],
	
	/* Пути к папкам */
	'dir.app'             => realpath(SITE_DIR . '../includes'),
	'dir.fonts'           => FW_DIR . 'assets/fonts',
	'dir.logs'            => realpath(SITE_DIR . '../logs'),
	'dir.templates.app'   => realpath(SITE_DIR . '../templates'),
	'dir.templates.cache' => realpath(SITE_DIR . '../cache/templates'),
	'dir.templates.fw'    => FW_DIR . 'templates',
	
	/* Настройки обработчика ошибок */
	'errorhandler.options' => [
		'debug.ips'   => [],
		'email.401'   => '',
		'email.404'   => '',
		'email.error' => 'fw@ivacuum.ru',
		'enabled'     => true,
		'standalone'  => false,
	],
	
	/* Пути к файлам */
	'file.cron.allowed' => 'cron_allowed',
	'file.cron.running' => 'cron_running',
	
	/* Настройки логирования */
	'logger.options' => [
		'cron.format'   => "%datetime%: %message%\n",
		'guzzle.format' => '{req_header_User-Agent} "{method} {host}{resource} {protocol}/{version}" {code} {res_header_Content-Length} connect={connect_time}s total={total_time}s',
	],
	
	/* Настройки миграций phinx */
	'migrator.options' => [
		'fw.dir'     => FW_DIR . 'Migrations',
		'fw.table'   => 'site_migrations_fw',
		'site.dir'   => realpath(SITE_DIR . '../migrations'),
		'site.table' => 'site_migrations',
	],

	/* Настройки профайлера */
	'profiler.options' => [
		'debug.ips'  => [],
		'enabled'    => true,
		'host'       => '',
		'port'       => 0,
		'send_stats' => false,
	],

	/* Замена доменов на их локальные варианты при редиректе */
	'request.options' => [
		'local_redirect.from' => '',
		'local_redirect.to'   => '',
	],

	/* Настройки маршрутизатора */
	'router.options' => [
		'allowed_extensions' => 'html;xml',
		'default_extension'  => 'html',
		'directory_index'    => 'index',
		'send_status_code'   => false,
	],
	
	/* Настройки сессий */
	'session.options' => [
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
	'sphinx.options' => [
		'host' => 'localhost',
		'port' => false,
		'sock' => '/tmp/sphinx.sock',
	],
	
	/* Ссылки */
	'urls' => [
		'register'     => '/',
		'signin'       => '/',
		'signout'      => '/',
		'static'       => '',
		'static_local' => '',
	],
];
