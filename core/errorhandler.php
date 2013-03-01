<?php
/**
* @package fw
* @copyright (c) 2013
*/

namespace fw\core;

/**
* Обработчик ошибок
*/
class errorhandler
{
	public static $mail;
	
	public static function handle_error($type, $text, $file, $line)
	{
		global $app;
		
		/* Выходим, если проверка отключена через @ */
		/*
		if (error_reporting() == 0 && $type != E_USER_ERROR && $type != E_USER_WARNING && $type != E_USER_NOTICE)
		{
			return;
		}
		*/
		
		$file = str_replace($app['request']->server('DOCUMENT_ROOT'), '', $file);
		
		switch ($type)
		{
			/**
			* Ошибка/предупреждение
			*/
			case E_NOTICE:
			case E_WARNING:
			
				$app['profiler']->log_error($text, $line, $file);
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
					
					if ($app['auth']->acl_get('a_'))
					{
						$app['template']->assign('error', $error_ary);
						$app['template']->display('sql_error.html');
						exit;
					}
				}
				else
				{
					static::log_mail($text);
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

				if (!defined('IN_CHECK_BAN'))
				{
					if (empty($app['user']->data))
					{
						$app['user']->session_begin();
					}

					$app['auth']->init($app['user']->data);

					if (empty($app['user']->lang))
					{
						$app['user']->setup();
					}
				}
				
				if (!empty($app['router']) && is_object($app['router']->handler))
				{
					$handler = $app['router']->handler;
				}
				else
				{
					$handler = new \app\models\page();
					$handler->data['site_id'] = $app['site_info']['id'];
					$handler->format = !empty($app['router']) ? $app['router']->format : $app['config']['router_default_extension'];
					
					$handler->_set_app($app)
						->additional_tplengine_features()
						->set_auth_urls($app['auth.signin_url'], $app['auth.signout_url'])
						->set_site_menu();

					/* Предустановки */
					if( method_exists($handler, '_setup') )
					{
						call_user_func([$handler, '_setup']);
					}
				}
				
				/* Запрет индексирования страницы */
				$handler->data['page_noindex'] = 1;

				/**
				* Необходимо выдать HTTP/1.0 404 Not Found,
				* если сообщение об отсутствии данных или ошибке
				*/
				preg_match('#NOT_FOUND$#', $text, $matches);

				if (!empty($matches) || 0 === strpos($text, 'ERR_'))
				{
					http_response_code(404);
					// static::log_mail('Page http://' . $app['request']->hostname . $app['user']->data['session_page'] . ' not found', '404 Not Found');
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
				
					static::log_mail('Fatal error: ' . $error['message']);

					if ($_SERVER['REMOTE_ADDR'] != '192.168.1.1')
					{
						return;
					}

					$error['file'] = str_replace('/srv/www/vhosts/', '', $error['file']);

					printf('<b style="color: red;">***</b> <b style="white-space: pre-line;">%s</b> on line <b>%d</b> in file <b>%s</b>.<br>', $error['message'], $error['line'], $error['file']);

					if (function_exists('xdebug_print_function_stack'))
					{
						ob_start();
						xdebug_print_function_stack();
						$call_stack = str_replace('/srv/www/vhosts/', '', ob_get_clean());
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
	public static function log_mail($text, $title = '')
	{
		global $app;
		
		if (!static::$mail)
		{
			return;
		}
		
		$call_stack = '';
		$text       = is_array($text) ? print_r($text, true) : $text;
		$title      = $app['request']->hostname . ($title ? ' ' . $title : '');
		
		if (function_exists('xdebug_print_function_stack'))
		{
			ob_start();
			xdebug_print_function_stack();
			$call_stack = str_replace('/srv/www/vhosts/', '', ob_get_clean());
		}
		
		mail(static::$mail, $title, sprintf("%s\n%s%s\n%s\n%s", $text, $call_stack, print_r($app['user']->data, true), print_r($_SERVER, true), print_r($_REQUEST, true)), sprintf("From: fw@%s\r\n", gethostname()));
	}

	/**
	* Регистрация обработчика
	*/
	public static function register()
	{
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
