<?php
/**
* @package fw
* @copyright (c) 2012
*/

/**
* Вывод на печать ajax данных
*
* @param	string	$file	Файл в папке templates/ajax
*/
function ajax_output($file = false)
{
	global $app;
	
	$file = $file ?: $app['template']->file;

	header('Content-type: text/xml; charset=utf-8');
	$app['template']->display('ajax/' . $file);
	garbage_collection(false);
	exit;
}

/**
* Создание скрытых полей по данным переданного массива
*
* @param	array	$row	Массив данных
*
* @return	string			Готовая html строка со скрытыми полями
*/
function build_hidden_fields($row)
{
	$string = '';

	foreach ($row as $key => $value)
	{
		$string .= '<input type="hidden" name="' . $key . '" value="' . $value . '">';
	}

	return $string;
}

/**
* Текстовое представление метки времени
*
* @param	int		$time		Метка времени
* @param	bool	$no_seconds	Следует ли выводить секунды
*
* @return	string				Текстовое представление метки времени
*/
function create_time($time, $no_seconds = false)
{
	/* Дни */
	$days = $time >= 86400 ? intval($time / 86400) : 0;
	$days = $days > 0 ? $days . ' дн. ' : '';
	$time -= $time >= 86400 ? 86400 * $days : 0;

	/* Часы */
	$hours = $time >= 3600 ? intval($time / 3600) : 0;
	$hours = $hours > 0 ? $hours . ' ч. ' : '';
	$time -= $time >= 3600 ? 3600 * $hours : 0;

	/* Минуты */
	$minutes = $time >= 60 ? intval($time / 60) : 0;
	$minutes = $minutes > 0 ? $minutes . ' мин.' : '';
	$time -= $time >= 60 ? 60 * $minutes : 0;

	if (!$days && !$hours && !$minutes && false !== $no_seconds)
	{
		return '1 мин.';
	}
	else
	{
		return $days . $hours . $minutes . (($no_seconds === false) ? ((!$days && !$hours && !$minutes && $time < 60) ? '' : ' и ') . $time . ' сек.' : '');
	}
}

/**
* Сборщик мусора
*/
function garbage_collection($display_profiler = true)
{
	global $app;

	if (!empty($app['profiler']))
	{
		if ($display_profiler && !$app['request']->is_ajax && !defined('IN_SQL_ERROR'))
		{
			if (($app['auth']->acl_get('a_') || $_SERVER['REMOTE_ADDR'] == '192.168.1.1') && $app['config']['profiler_display'])
			{
				$app['profiler']->display();
			}
		}

		if ($app['config']['profiler_send_stats'])
		{
			$app['profiler']->send_stats($app['config']['profiler_ip'], $app['config']['profiler_port']);
		}
	}
	
	if (!empty($app['cache']))
	{
		$app['cache']->unload();
	}

	if (!empty($app['db']))
	{
		$app['db']->close();
	}
}

/**
* Создание ссылки на определенную страницу
*
* @return	string	Ссылка на страницу
*/
function generate_page_link($page, $base_url, $query_string)
{
	if (!$page)
	{
		return false;
	}

	if ($page == 1)
	{
		return $base_url . $query_string;
	}

	$url_delim = !$query_string ? '?' : '&amp;';

	return $base_url . sprintf('%s%sp=%d', $query_string, $url_delim, $page);
}

/**
* Возвращает требуемое регулярное выражение
*
* @param	string	$type	Тип регулярного выражения
*
* @return	string			Код регулярного выражения
*/
function get_preg_expression($type)
{
	switch($type)
	{
		case 'url_symbols': return '[a-z\d\_\-\.\x{7f}-\x{ff}\(\)]+';
	}

	return false;
}

/**
* Поиск URL сайта по его уникальному идентификатору
*/
function get_site_info_by_id($site_id)
{
	global $app;
	
	$sites = $app['cache']->obtain_sites();
	
	foreach ($sites as $row)
	{
		if ($site_id == $row['site_id'])
		{
			return [
				'domain'   => $row['site_url'],
				'language' => $row['site_language'],
				'title'    => $row['site_title']
			];
		}
	}
	
	return false;
}

/**
* Поиск информации о сайте по его доменному имени
* и языку, если передан просматриваемой URL страницы
*
* Если страница не указана, то будет выдан сайт
* на языке по умолчанию (site_default = 1)
*/
function get_site_info_by_url($url, $page = '')
{
	global $app;

	$language = '';
	$page     = trim($page, '/');
	$params   = $page ? explode('/', $page) : [];
	
	if (!empty($params) && strlen($params[0]) == 2)
	{
		$language = $params[0];
	}
	
	$sites = $app['cache']->obtain_sites();
	
	foreach ($sites as $row)
	{
		if ($url == $row['site_url'] && (($row['site_default'] && !$language) || ($language && $language == $row['site_language'])))
		{
			return [
				'default'  => (int) $row['site_default'],
				'domain'   => $row['site_url'],
				'id'       => (int) $row['site_id'],
				'language' => $row['site_language'],
				'title'    => $row['site_title']
			];
		}
	}
	
	return false;
}

/**
* Размер в понятной человеку форме, округленный к ближайшему ГБ, МБ, КБ
*
* @param	int		$size		Размер
* @param	int		$rounder	Необходимое количество знаков после запятой
* @param	string	$min		Минимальный размер ('КБ', 'МБ' и т.п.)
* @param	string	$space		Разделитель между числами и текстом (1< >МБ)
*
* @return	string				Размер в понятной человеку форме
*/
function humn_size($size, $rounder = '', $min = '', $space = '&nbsp;')
{
	global $app;

	$sizes = [$app['user']->lang['SIZE_BYTES'], $app['user']->lang['SIZE_KB'], $app['user']->lang['SIZE_MB'], $app['user']->lang['SIZE_GB'], $app['user']->lang['SIZE_TB'], $app['user']->lang['SIZE_PB'], $app['user']->lang['SIZE_EB'], $app['user']->lang['SIZE_ZB'], $app['user']->lang['SIZE_YB']];
	static $rounders = [0, 0, 1, 2, 3, 3, 3, 3, 3];

	$size = (float) $size;
	$ext  = $sizes[0];
	$rnd  = $rounders[0];

	if ($min == $app['user']->lang['SIZE_KB'] && $size < 1024)
	{
		$size    = $size / 1024;
		$ext     = $app['user']->lang['SIZE_KB'];
		$rounder = 1;
	}
	else
	{
		for ($i = 1, $cnt = sizeof($sizes); ($i < $cnt && $size >= 1024); $i++)
		{
			$size = $size / 1024;
			$ext  = $sizes[$i];
			$rnd  = $rounders[$i];
		}
	}

	if (!$rounder)
	{
		$rounder = $rnd;
	}

	return round($size, $rounder) . $space . $ext;
}

/**
* Внутренняя ссылка
*
* @param	string	$url		ЧПУ ссылка
* @param	string	$prefix		Префикс (по умолчанию $app['config']['site_root_path'])
*
* @return	string				Готовый URL
*/
function ilink($url = '', $prefix = false)
{
	global $app;

	/**
	* Этапы обработки URL: а) сайт, находящийся в дочерней папке; б) на другом домене; в) в корне;
	*
	* 1а) /csstats		1б) http://wc3.ivacuum.ru/	1в) /
	* 2а) /csstats/		2б) http://wc3.ivacuum.ru/	2в) /en/
	*/
	if (0 === strpos($url, '/'))
	{
		/**
		* Ссылка от корня сайта
		*
		* /acp/
		* /about.html
		*/
		$link = $app['config']['site_root_path'];
		$url  = substr($url, 1);
	}
	elseif (0 === strpos($url, 'http://'))
	{
		$link = 'http://';
		$url  = substr($url, 7);
	}
	elseif (0 === strpos($url, '//'))
	{
		$link = '//';
		$url  = substr($url, 2);
	}
	else
	{
		$link = false === $prefix ? $app['config']['site_root_path'] : $prefix;
		$link .= substr($link, -1) == '/' ? '' : '/';
	}

	/**
	* Добавляем язык, если выбран отличный от языка по умолчанию и ссылка от корня сайта
	*
	* Если язык уже присутствует в ссылке, то пропускаем этот шаг
	*/
	if (($link == $app['config']['site_root_path'] && $prefix === false) || (false !== strpos($prefix, 'ivacuum.ru')))
	{
		if (!$app['site_info']['default'] && (false === strpos($link . $url, sprintf('/%s/', $app['site_info']['language']))))
		{
			$link = sprintf('%s%s/', $link, $app['site_info']['language']);
		}
	}
	
	$link .= $url;
	$ary = pathinfo($url);
	
	if (isset($ary['extension']) || substr($link, -1) == '/' || !$app['config']['router_default_extension'])
	{
		return $link;
	}
	
	return sprintf('%s/', $link);
}

/**
* Вывод json данных
*
* @param	string	$output	Данные для выдачи
*/
function json_output($output)
{
	header('Content-Type: application/json; charset=utf-8');
	echo json_encode($output);
	garbage_collection(false);
	exit;
}

/**
* Загрузка констант
*/
function load_constants()
{
	global $acm_prefix;
	
	if (!function_exists('apc_fetch'))
	{
		return false;
	}

	return apc_load_constants($acm_prefix . '_constants');
}

/**
* Generate login box or verify password
*/
function login_box($redirect = '', $l_explain = '', $l_success = '', $admin = false, $s_display = true)
{
	global $app;

	$err = '';

	/* Убеждаемся, что учтены настройки пользователя */
	if (empty($app['user']->lang))
	{
		$app['user']->setup();
	}

	/**
	* Пользователь пытается авторизоваться как администратор не имея на то прав
	*/
	if ($admin && !$app['auth']->acl_get('a_'))
	{
		/**
		* Анонимные/неактивные пользователи никак не смогут попасть в админку,
		* даже если у них есть соответствующие привилегии
		*/
		// if ($app['user']->is_registered)
		// {
		// 	add_log('admin', 'LOG_ADMIN_AUTH_FAIL');
		// }
		
		trigger_error('NO_AUTH_ADMIN');
	}

	if ($app['request']->is_set_post('submit'))
	{
		$admin 		= $admin ? 1 : 0;
		$autologin	= $app['request']->is_set_post('autologin');
		$password	= $app['request']->post('password', '');
		$username	= $app['request']->post('username', '');
		$viewonline = $admin ? $app['user']['session_viewonline'] : (int) !$app['request']->is_set_post('viewonline');

		// Check if the supplied username is equal to the one stored within the database if re-authenticating
		if ($admin && $username != $app['user']['username'])
		{
			// add_log('admin', 'LOG_ADMIN_AUTH_FAIL');
			trigger_error('NO_AUTH_ADMIN_USER_DIFFER');
		}

		// If authentication is successful we redirect user to previous page
		$result = $app['auth']->login($username, $password, $autologin, $viewonline, $admin);

		/**
		* Ведем лог всех авторизаций администраторов
		*/
		// if ($admin)
		// {
		// 	if ($result['status'] == 'OK')
		// 	{
		// 		add_log('admin', 'LOG_ADMIN_AUTH_SUCCESS');
		// 	}
		// 	else
		// 	{
		// 		/**
		// 		* Анонимные/неактивные пользователя никогда не попадут в админку
		// 		*/
		// 		if ($app['user']->is_registered)
		// 		{
		// 			add_log('admin', 'LOG_ADMIN_AUTH_FAIL');
		// 		}
		// 	}
		// }

		if ($result['status'] == 'OK')
		{
			$message  = $l_success ? $l_success : $app['user']->lang['LOGIN_REDIRECT'];

			/* Разрешаем создателю авторизоваться даже при бане */
			if (defined('IN_CHECK_BAN') && $result['user_row']['user_id'] === 1)
			{
				return;
			}
			
			redirect(ilink($redirect));
		}

		/* Неудалось создать сессию */
		if ($result['status'] == 'LOGIN_BREAK')
		{
			trigger_error($result['message']);
		}
		
		/* Различные ошибки авторизации */
		// $err = $app['user']->lang[$result['status']];
		$err = $result['message'];
	}
	
	$s_hidden_fields = [];

	if ($redirect)
	{
		$s_hidden_fields['goto'] = $redirect;
	}

	$s_hidden_fields = build_hidden_fields($s_hidden_fields);
	
	$app['template']->assign([
		'LOGIN_ERROR'   => $err,
		'LOGIN_EXPLAIN' => $l_explain,
		'USERNAME'      => $admin ? $app['user']['username'] : '',

		'U_SEND_PASSWORD' => $app['config']['email_enable'] ? 'ucp/sendpassword.html' : '',
		'U_TERMS_USE'     => 'ucp/terms.html',
		'U_PRIVACY'       => 'ucp/privacy.html',

		'S_ADMIN_AUTH'         => $admin,
		'S_DISPLAY_FULL_LOGIN' => $s_display ? true : false,
		'S_HIDDEN_FIELDS'      => $s_hidden_fields
	]);

	$app['template']->file = 'ucp_login.html';
}

/**
* Возвращает число в заданном формате
*
* В данный момент для всех языков оформление едино:
* 12345678 -> 12 345 678
*
* @param	int	$value	Число
*
* @return	int			Число в заданном формате
*/
function num_format($value, $decimals = 0)
{
	global $app;
	
	return number_format($value, $decimals, $app['config']['number_dec_point'], $app['config']['number_thousands_sep']);
}

/**
* Возвращает число в пределах $min:$max
*
* @param	int	$value	Число
* @param	int	$min	Минимальная граница
* @param	int $max	Максимальная граница
*
* @return	int			Число не менее $min и не более $max
*/
function num_in_range($value, $min, $max = false)
{
	$max = $max ?: $value;

	return $value < $min ? $min : ($value > $max ? $max : $value);
}

/**
* Создание случайной строки заданной длины
*
* @param	int		$length		Длина строки
*
* @return	string				Случайная строка заданной длины
*/
function make_random_string($length = 10)
{
	return substr(str_shuffle(preg_replace('#[^0-9a-zA-Z]#', '', crypt(uniqid(mt_rand(), true)))), 0, $length);
}

/**
* Мета-переадресация
*
* @param	int		$time	Время в секундах
* @param	string	$url	Будущий URL
*/
function meta_refresh($time, $url)
{
	global $app;
	
	$app['template']->assign('META', sprintf('<meta http-equiv="refresh" content="%d;url=%s">', $time, $url));
}

/**
* Навигационная ссылка
*
* @param	string	$url	Ссылка на страницу
* @param	string	$text	Название страницы
* @param	string	$image	Изображение
*/
function navigation_link($url, $text, $image = false)
{
	global $app;
	
	$app['template']->append('nav_links', [
		'IMAGE' => $image,
		'TEXT'  => $text,
		'URL'   => $url
	]);
}

/**
* Переход по страницам
*
* Проверяем наличие выбранной страницы. Устанавливаем данные шаблона.
*
* @param	int		$on_page	Количество элементов на странице
* @param	int		$overall	Общее количество элементов
* @param	string	$link		Базовый адрес (для ссылок перехода по страницам)
* @param	string	$page_var	Переменная в адресе, содержащая номер текущей страницы
*/
function pagination($on_page, $overall, $link, $page_var = 'p')
{
	global $app;

	/**
	* Определяем переменные
	*/
	$base_url     = $link;
	$p            = $app['request']->variable($page_var, 1);
	$query_string = '';
	$sort_count   = $app['request']->variable('sc', $on_page);
	$sort_dir     = $app['request']->variable('sd', 'd');
	$sort_key     = $app['request']->variable('sk', 'a');
	$start        = ($p * $sort_count) - $sort_count;

	/**
	* Нужно ли ссылки на страницы указывать с параметрами
	*/
	if ($sort_count != $on_page || $sort_dir != 'd' || $sort_key != 'a')
	{
		if ($sort_count != $on_page)
		{
			$link .= (false !== strpos($link, '?') ? '&' : '?') . 'sc=' . $sort_count;
		}

		if ($sort_dir != 'd')
		{
			$link .= (false !== strpos($link, '?') ? '&' : '?') . 'sd=' . $sort_dir;
		}

		if ($sort_key != 'a')
		{
			$link .= (false !== strpos($link, '?') ? '&' : '?') . 'sk=' . $sort_key;
		}
	}

	/* Общее количество страниц */
	$pages = max(1, intval(($overall - 1) / $sort_count) + 1);

	/* Проверка номера страницы */
	if (!$p || $p > $pages || $p <= 0)
	{
		trigger_error('PAGE_NOT_FOUND');
	}

	if (($q_pos = strpos($base_url, '?')) !== false)
	{
		/**
		* Если в адресе присутствует query_string:
		* /news/5/?sid=1
		*
		* то разбиваем его на две части:
		* /news/5/
		* ?sid=1
		*/
		$query_string = substr($base_url, $q_pos);
		$base_url     = substr($base_url, 0, $q_pos);
	}

	$url_delim = !$query_string ? '?' : '&amp;';
	$url_next = $url_prev = 0;

	if ($pages > $p)
	{
		if ($p > 1)
		{
			$url_prev = $p - 1;
		}

		$url_next = $p + 1;
	}
	elseif ($pages == $p && $pages > 1)
	{
		$url_prev = $p - 1;
	}
	
	$app['template']->assign([
		'pagination' => [
			'ITEMS'   => $overall,
			'NEXT'    => generate_page_link($url_next, $base_url, $query_string),
			'ON_PAGE' => $sort_count,
			'PAGE'    => $p,
			'PAGES'   => $pages,
			'PREV'    => generate_page_link($url_prev, $base_url, $query_string),
			'VAR'     => $page_var,
			'URL'     => $link
		],
	]);

	return [
		'offset'  => (int) $start,
		'on_page' => (int) $sort_count,
		'p'       => (int) $p,
		'pages'   => (int) $pages
	];
}

/**
* Обработка смайликов
*
* @param	string	$message Текст сообщения
* @param	bool	$force_option Принудительное возвращение кода смайлика
*/
function parse_smilies($message, $force_option = false)
{
	global $app;

	if ($force_option || !$app['config']['allow_smilies'])
	{
		return preg_replace('#<!\-\- <smile name="(.*?)"><url>.*?</url><title>.*?</title></smile> \-\->#', '\1', $message);
	}
	else
	{
		return preg_replace('#<!\-\- <smile name="(.*?)"><url>(.*?)</url><title>(.*?)</title></smile> \-\->#', '<img src="' . $app['config']['smilies_path'] . '/\2" alt="\1" title="\3">', $message);
	}
}

/**
* Формы слова во множественном числе
*
* @param	int		$n		Число
* @param	array	$forms	Формы слова
*
* @param	string			Фраза во множественном числе
*/
function plural($n = 0, $forms, $format = '%s %s')
{
	global $app;

	if (!$forms)
	{
		return;
	}

	$forms = explode(';', $forms);

	switch ($app['user']->lang['.'])
	{
		/**
		* Русский язык
		*/
		case 'ru':

			if (sizeof($forms) < 3)
			{
				$forms[2] = $forms[1];
			}

			$plural = ($n % 10 == 1 && $n % 100 != 11) ? 0 : ($n % 10 >= 2 && $n % 10 <= 4 && ($n % 100 < 10 || $n % 100 >= 20) ? 1 : 2);

		break;
		/**
		* Язык по умолчанию - английский
		*/
		default:

			$plural = ($n == 1) ? 0 : 1;

		break;
	}
	
	return sprintf($format, num_format($n), $forms[$plural]);
}

/**
* Подготовка сообщения для правки
*
* @param	string	$text	Обрабатываемый текст
* @return					Сообщение с тектовыми смайлами
*/
function prepare_text_for_edit($text)
{
	return parse_smilies($text, true);
}

/**
* Подготовка сообщения для отображения
* На данном этапе обработка смайлов
*
* @param	string	$text	Обрабатываемый текст
*/
function prepare_text_for_print($text)
{
	return parse_smilies($text);
}

/**
* Переадресация
*
* @param	string	$url	Адрес для мгновенного перенаправления
*/
function redirect($url, $status_code = 302)
{
	global $app;
	
	if (false !== strpos(urldecode($url), "\n") || false !== strpos(urldecode($url), "\r"))
	{
		trigger_error('Bad URL.', E_USER_ERROR);
	}
	
	/**
	* Если пользователь из локальной сети,
	* то перенаправлять его следует на локальный домен
	*/
	if ($app['config']['router_local_redirect'])
	{
		if ($app['user']->isp == 'local')
		{
			$url = str_replace(['ivacuum.ru/', 't.local.ivacuum.ru/'], ['local.ivacuum.ru/', 't.ivacuum.ru/'], $url);
		}
	}
	
	if ($status_code != 302)
	{
		send_status_line($status_code);
	}

	header('Location: ' . $url);
	garbage_collection(false);
	exit;
}

/**
* Добавление RSS потока в шапку
*
* @param	string	$url	Путь к рассылке
* @param	bool	$root	Следует ли делать ссылку на RSS от корня сайта
* @param	string	$title	Заголовок рассылки
*/
function rss_add($url, $root = false, $title = 'RSS 2.0')
{
	global $app;

	$app['template']->append('rss', [
		'TITLE' => $title,
		'URL'   => false !== $root ? ilink($url, $app['config']['site_root_path']) : ilink($url)
	]);

	return;
}

/**
* Установка констант
*/
function set_constants($constants)
{
	global $acm_prefix;

	if (!function_exists('apc_fetch'))
	{
		foreach ($constants as $key => $value)
		{
			define($key, $value);
		}
		
		return;
	}
	
	apc_define_constants($acm_prefix . '_constants', $constants);
}

/**
* Вывод заголовка
*
* send_status_line(404, 'Not Found');
*
* HTTP/1.x 404 Not Found
*/
function send_status_line($code, $message = '')
{
	global $app;
	
	if (!$message)
	{
		switch ($code)
		{
			case 200: $message = 'OK'; break;
			case 201: $message = 'Created'; break;
			case 202: $message = 'Accepted'; break;
			case 204: $message = 'No Content'; break;
			
			case 301: $message = 'Moved Permanently'; break;
			case 302: $message = 'Found'; break;
			case 303: $message = 'See Other'; break;
			case 304: $message = 'Not Modified'; break;
			
			case 400: $message = 'Bad Request'; break;
			case 401: $message = 'Unauthorized'; break;
			case 403: $message = 'Forbidden'; break;
			case 404: $message = 'Not Found'; break;
			case 405: $message = 'Method Not Allowed'; break;
			case 409: $message = 'Conflict'; break;
			case 410: $message = 'Gone'; break;
			
			case 500: $message = 'Internal Server Error'; break;
			case 501: $message = 'Not Implemented'; break;
			case 502: $message = 'Bad Gateway'; break;
			case 503: $message = 'Service Unavailable'; break;
			case 504: $message = 'Gateway Timeout'; break;
			
			default: return;
		}
	}
	
	if (substr(strtolower(PHP_SAPI), 0, 3) === 'cgi')
	{
		header(sprintf('Status: %d %s', $code, $message), true, $code);
		return;
	}
	
	if (false != $version = $app['request']->server('SERVER_PROTOCOL'))
	{
		header(sprintf('%s %d %s', $version, $code, $message), true, $code);
		return;
	}
	
	header(sprintf('HTTP/1.0 %d %s', $code, $message), true, $code);
}

/**
* Создание ЧПУ ссылки с использованием символов выбранного языка сайта
*
* @param	string	$url	Входная ссылка
*
* @return	string	$result	ЧПУ ссылка
*/
function seo_url($url, $lang = 'ru')
{
	switch ($lang)
	{
		case 'ru': $pattern = '/[^а-яa-z\d\.]/u'; break;
		default:

			$pattern = '/[^a-z\d\.]/u'; break;

		break;
	}

	/* Отсекаем неподходящие символы */
	$result = trim(preg_replace($pattern, '_', mb_strtolower(htmlspecialchars_decode($url))), '_');

	/**
	* Укорачиваем однообразные последовательности символов
	* _. заменяем на _
	* Убираем точку в конце
	*/
	$result = preg_replace(['/_{2,}/', '/\.{2,}/', '/_\./', '/(.*)\./'], ['_', '', '_', '$1'], $result);

	return $result;
}
