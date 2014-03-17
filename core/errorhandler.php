<?php namespace fw\core;

use fw\Events\GenericEvent;

class errorhandler
{
	protected static $doc_root;
	protected static $events;
	protected static $options = [
		'debug.ips'   => [],
		'email.401'   => '',
		'email.404'   => '',
		'email.error' => '',
		'standalone'  => true,
	];
	
	public static function handle_error($type, $text, $file, $line)
	{
		/* Выходим, если проверка отключена через @ */
		if (error_reporting() == 0 && $type != E_USER_ERROR && $type != E_USER_WARNING && $type != E_USER_NOTICE) {
			return;
		}
		
		$file = str_replace(static::$doc_root, '', $file);
		
		switch ($type) {
			/* Ошибка/предупреждение */
			case E_NOTICE:
			case E_WARNING:
			
				if (!static::$options['standalone']) {
					static::$events->dispatch('error.notice', new GenericEvent(compact('text', 'line', 'file')));
				}
				
				return;
			
			break;
			/**
			* Критическая ошибка
			* Если sql, то выводим как есть
			*/
			case E_USER_ERROR:
			
				if (defined('IN_SQL_ERROR')) {
					if (!static::$options['standalone']) {
						global $error_ary;
						static::$events->dispatch('error.sql', new GenericEvent($error_ary));
						exit;
					}
				}
				
				if (!static::$options['standalone']) {
					static::$events->dispatch('error.crit', new GenericEvent(compact('text')));
					exit;
				}
				
				http_response_code(503);
				echo 'Сервис временно недоступен';
				exit;

			break;
			/**
			* Пользовательская ошибка
			* Выводим, используя оформление сайта
			*/
			case E_USER_NOTICE:
			case E_USER_WARNING:
			
				$event = 'error.page.inform';
				
				preg_match('#NOT_FOUND$#', $text, $matches);

				if (!empty($matches) || 0 === strpos($text, 'ERR_')) {
					$event = 'error.404';
				}
			
				if (static::$options['standalone']) {
					echo $text;
					exit;
				}
				
				static::$events->dispatch($event, new GenericEvent(compact('text')));

			break;
		}
		
		/**
		* Обработчик ошибок PHP не будет задействован, если не возвратить false
		* Возвращаем false, чтобы необработанные ошибки были помещены в журнал
		*/
		return false;
	}

	/**
	* Перехват критических ошибок
	*/
	public static function handle_fatal_error()
	{
		if ($error = error_get_last()) {
			switch ($error['type']) {
				case E_ERROR:
				case E_CORE_ERROR:
				case E_COMPILE_ERROR:
				case E_USER_ERROR:
				
					$error['file'] = str_replace(static::$doc_root, '', $error['file']);
				
					if (!static::$options['standalone']) {
						static::$events->dispatch('error.fatal', new GenericEvent($error));
					}
				
					if (PHP_SAPI != 'cli' && !in_array($_SERVER['REMOTE_ADDR'], static::$options['debug.ips'])) {
						return;
					}

					printf('<b style="color: red;">***</b> <b style="white-space: pre-line;">%s</b> on line <b>%d</b> in file <b>%s</b>.<br>', $error['message'], $error['line'], $error['file']);

					if ($call_stack = str_replace(static::$doc_root, '', get_call_stack())) {
						echo '<pre>', $call_stack, '</pre>';
					}

				break;
			}
		}

		/**
		* Немедленная отправка страницы клиенту
		* Скрипт же может продолжать работу, например, рассылать письма
		*/
		if (function_exists('fastcgi_finish_request')) {
			fastcgi_finish_request();
		}
	}

	/**
	* Регистрация обработчика
	*/
	public static function register(array $options = [], $dispatcher = null)
	{
		static::$doc_root = realpath(rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/../../') . '/';
		static::$events   = $dispatcher;
		static::$options  = array_merge(static::$options, $options);
		
		set_error_handler([new static, 'handle_error']);
		register_shutdown_function([new static, 'handle_fatal_error']);
	}
	
	/**
	* Возврат обработчика по умолчанию
	*/
	public static function unregister()
	{
		restore_error_handler();
	}
}
