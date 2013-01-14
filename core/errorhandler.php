<?php
/**
* @package fw
* @copyright (c) 2012
*/

namespace fw\core;

/**
* Обработчик ошибок
*/
class errorhandler
{
	public static function handle_error($type, $text, $file, $line)
	{
		global $profiler, $request;
		
		/* Выходим, если проверка отключена через @ */
		/*
		if (error_reporting() == 0 && $type != E_USER_ERROR && $type != E_USER_WARNING && $type != E_USER_NOTICE)
		{
			return;
		}
		*/
		
		$file = str_replace($request->server('DOCUMENT_ROOT'), '', $file);
		
		switch ($type)
		{
			/**
			* Ошибка/предупреждение
			*/
			case E_NOTICE:
			case E_WARNING:
			
				$profiler->log_error($text, $line, $file);
				return;

			break;
			/**
			* Критическая ошибка
			* Если sql, то выводим как есть
			*/
			case E_USER_ERROR:
			
				if (defined('IN_SQL_ERROR'))
				{
					global $auth, $error_ary, $template;
					
					static::log_mail($error_ary);
					
					if ($auth->acl_get('a_'))
					{
						$template->assign('error', $error_ary);
						$template->display('sql_error.html');
						garbage_collection(false);
						exit;
					}
				}
				else
				{
					static::log_mail($text);
				}

				send_status_line(503);
				garbage_collection(false);

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

				global $auth, $config, $router, $site_info, $template, $user;

				if (!defined('IN_CHECK_BAN'))
				{
					if (empty($user->data))
					{
						$user->session_begin();
					}

					$auth->init($user->data);

					if (empty($user->lang))
					{
						$user->setup();
					}
				}
				
				if (!empty($router) && is_object($router->handler))
				{
					$handler =& $router->handler;
				}
				else
				{
					$handler = new \app\models\page();
					$handler->data['site_id'] = $site_info['id'];
					$handler->set_site_menu();
					$handler->format = !empty($router) ? $router->format : $config['router_default_extension'];
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
					send_status_line(404);
					// static::log_mail('Page http://' . $user->domain . $user->data['session_page'] . ' not found', '404 Not Found');
				}
				
				if (!$handler->format || $handler->format == 'json')
				{
					$error = ['code' => $text];

					json_output(['errors' => [$error]]);
				}

				$template->assign([
					'page' => $handler->data,
					
					'MESSAGE_TEXT'  => isset($user->lang[$text]) ? $user->lang[$text] : $text,
					'MESSAGE_TITLE' => $user->lang['SITE_MESSAGE']
				]);
				
				$template->file = 'message_body.html';
				
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

					$error['file'] = str_replace($_SERVER['DOCUMENT_ROOT'], '', $error['file']);

					printf('<b style="color: red;">***</b> <b style="white-space: pre-line;">%s</b> on line <b>%d</b> in file <b>%s</b>.<br />', $error['message'], $error['line'], $error['file']);

					if (function_exists('xdebug_print_function_stack'))
					{
						echo '<pre>', xdebug_print_function_stack(), '</pre>';
					}

				break;
			}
		}
	}
	
	/**
	* Уведомление администратора о произошедшей ошибке
	*/
	public static function log_mail($text, $title = '')
	{
		global $request, $user;
		
		$call_stack = '';
		$text       = is_array($text) ? print_r($text, true) : $text;
		
		if (!$title)
		{
			$title = defined('IN_SQL_ERROR') ? 'E_USER_ERROR_SQL' : 'E_USER_ERROR';
		}
		
		if (function_exists('xdebug_print_function_stack'))
		{
			ob_start();
			xdebug_print_function_stack();
			$call_stack = str_replace(['/srv/www/vhosts'], [''], ob_get_clean());
		}
		
		mail('vacuum@ivacuum.ru', $title, sprintf("%s\n%s%s\n%s\n%s", $text, $call_stack, print_r($user->data, true), print_r($_SERVER, true), print_r($_REQUEST, true)), sprintf("From: %s@%s\r\n", $user->domain ?: 'fw', gethostname()));
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