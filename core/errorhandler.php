<?php
/**
* @package fw
* @copyright (c) 2013
*/

namespace fw\core;

class errorhandler
{
	protected static $document_root;
	protected static $options = [
		'debug.ips'   => [],
		'email.401'   => '',
		'email.404'   => '',
		'email.error' => '',
		'standalone'  => true,
	];
	
	public static function handle_error($type, $text, $file, $line)
	{
		global $app;
		
		/* Выходим, если проверка отключена через @ */
		if (error_reporting() == 0 && $type != E_USER_ERROR && $type != E_USER_WARNING && $type != E_USER_NOTICE)
		{
			return;
		}
		
		$file = str_replace(static::$document_root, '', $file);
		
		switch ($type)
		{
			/* Ошибка/предупреждение */
			case E_NOTICE:
			case E_WARNING:
			
				static::log_mail("{$text} on line {$line} in file {$file}");

				if (!static::$options['standalone'])
				{
					$app['profiler']->log_error($text, $line, $file);
				}
				
				return;
			
			break;
			/**
			* Критическая ошибка
			* Если sql, то выводим как есть
			*/
			case E_USER_ERROR:
			
				if (defined('IN_SQL_ERROR'))
				{
					global $error_ary;
					
					static::log_mail($error_ary);
					
					if (in_array($_SERVER['REMOTE_ADDR'], static::$options['debug.ips']))
					{
						/* Service Unavailable */
						http_response_code(503);
						echo '<!DOCTYPE html>';
						echo '<html lang="ru">';
						echo '<head>';
						echo '<meta charset="utf-8">';
						echo '<meta name="robots" content="noindex, nofollow">';
						echo '<title>Сервис временно недоступен</title>';
						echo '</head>';
						echo '<body>';
						echo '<h2>Ошибка в SQL запросе</h2>';
						echo '<ul>';
						echo '	<li>Код ошибки: <b>', $error_ary['code'], '</b></li>';
						echo '	<li>Текст ошибки: <b>', $error_ary['text'], '</b></li>';
						echo '</ul>';
						echo '<pre>', $error_ary['sql'], '</pre>';
						echo '</body>';
						echo '</html>';
						exit;
					}
				}
				else
				{
					static::log_mail("{$text} on line {$line} in file {$file}");
				}

				/* Service Unavailable */
				http_response_code(503);
				echo '<!DOCTYPE html>';
				echo '<html lang="ru">';
				echo '<head>';
				echo '<meta charset="utf-8">';
				echo '<meta name="robots" content="noindex, nofollow">';
				echo '<title>Сервис временно недоступен</title>';
				echo '</head>';
				echo '<body>';
				echo '<h1>Сервис временно недоступен</h1>';
				echo '<p>Отчет о произошедшей ошибке отправлен администратору.</p>';
				echo '<p>Приносим извинения за доставленные неудобства.</p>';
				echo '</body>';
				echo '</html>';
				exit;

			break;
			/**
			* Пользовательская ошибка
			* Выводим, используя оформление сайта
			*/
			case E_USER_NOTICE:
			case E_USER_WARNING:
			
				if (static::$options['standalone'])
				{
					/**
					* Необходимо выдать HTTP/1.1 404 Not Found,
					* если сообщение об отсутствии данных или ошибке
					*/
					preg_match('#NOT_FOUND$#', $text, $matches);

					if (!empty($matches) || 0 === strpos($text, 'ERR_'))
					{
						http_response_code(404);
						static::log_mail("Page http://{$_SERVER['SERVER_NAME']}{$_REQUEST['path']} not found", "404 Not Found", 404);
					}
					
					echo $text;
					exit;
				}
				
				if (!empty($app['router']) && is_object($app['router']->handler))
				{
					$handler = $app['router']->handler;
				}
				else
				{
					$handler = new \app\models\page($app['router.options']);
					$handler->data['site_id'] = $app['site_info']['id'];
					$handler->format = !empty($app['router']) ? $app['router']->format : $app['router.options']['default_extension'];
					$handler->_set_app($app)
						->additional_tplengine_features()
						->set_preconfigured_urls($app['urls'])
						->set_site_menu();

					/* Предустановки */
					if (method_exists($handler, '_setup'))
					{
						call_user_func([$handler, '_setup']);
					}
				}
				
				/* Запрет индексирования страницы */
				$handler->data['page_noindex'] = 1;

				/**
				* Необходимо выдать HTTP/1.1 404 Not Found,
				* если сообщение об отсутствии данных или ошибке
				*/
				preg_match('#NOT_FOUND$#', $text, $matches);

				if (!empty($matches) || 0 === strpos($text, 'ERR_'))
				{
					http_response_code(404);
					static::log_mail("Page http://{$_SERVER['SERVER_NAME']}{$_REQUEST['path']} not found", "404 Not Found", 404);
					$handler->data['page_title'] = '404 Not Found';
				}
				
				if (!$handler->format || $handler->format == 'json')
				{
					$error = ['code' => $text];

					echo json_encode(['errors' => [$error]]);
					exit;
				}

				$app['template']->assign([
					'page' => $handler->data,
					
					'MESSAGE_TEXT'  => isset($app['user']->lang[$text]) ? $app['user']->lang[$text] : $text,
					'MESSAGE_TITLE' => $app['user']->lang['SITE_MESSAGE']
				]);
				
				$app['template']->file = 'message_body.html';
				
				$handler->page_header();
				$handler->page_footer();

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
		if ($error = error_get_last())
		{
			switch ($error['type'])
			{
				case E_ERROR:
				case E_CORE_ERROR:
				case E_COMPILE_ERROR:
				case E_USER_ERROR:
				
					static::log_mail("Fatal error: {$error['message']} on line {$error['line']} in file {$error['file']}");

					if (PHP_SAPI != 'cli' && !in_array($_SERVER['REMOTE_ADDR'], static::$options['debug.ips']))
					{
						return;
					}

					$error['file'] = str_replace(static::$document_root, '', $error['file']);

					printf('<b style="color: red;">***</b> <b style="white-space: pre-line;">%s</b> on line <b>%d</b> in file <b>%s</b>.<br>', $error['message'], $error['line'], $error['file']);

					if (function_exists('xdebug_print_function_stack'))
					{
						ob_start();
						xdebug_print_function_stack();
						$call_stack = str_replace(static::$document_root, '', ob_get_clean());
						echo '<pre>', $call_stack, '</pre>';
					}

				break;
			}
		}

		if (function_exists('fastcgi_finish_request'))
		{
			fastcgi_finish_request();
		}
	}
	
	/**
	* Уведомление администратора о произошедшей ошибке
	*/
	public static function log_mail($text, $title = '', $email = '')
	{
		global $app;
		
		$email = $email ?: 'error';
		
		if (!isset(static::$options["email.{$email}"]) || !static::$options["email.{$email}"])
		{
			return;
		}
		
		$call_stack = '';
		$text       = is_array($text) ? print_r($text, true) : $text;
		$title      = $_SERVER['SERVER_NAME'] . ($title ? ' ' . $title : '');
		
		if (function_exists('xdebug_print_function_stack'))
		{
			ob_start();
			xdebug_print_function_stack();
			$call_stack = str_replace(static::$document_root, '', ob_get_clean());
		}
		
		if (PHP_SAPI == 'cli')
		{
			mail(static::$options["email.{$email}"], $title, sprintf("[%s]\n%s\n%s\$_SERVER => %s", strftime('%c'), $text, $call_stack, print_r($_SERVER, true)), sprintf("From: fw@%s\r\n", gethostname()));
			return;
		}
		
		mail(static::$options["email.{$email}"], $title, sprintf("[%s]\n%s\n%s\$app['user']->data => %s\n\$_SERVER => %s\n\$_REQUEST => %s", strftime('%c'), $text, $call_stack, !empty($app['user']) ? print_r($app['user']->data, true) : '', print_r($_SERVER, true), print_r($_REQUEST, true)), sprintf("From: fw@%s\r\n", gethostname()));
	}

	/**
	* Регистрация обработчика
	*/
	public static function register(array $options = [])
	{
		static::$document_root = realpath(rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/../../') . '/';
		static::$options       = array_merge(static::$options, $options);
		
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
