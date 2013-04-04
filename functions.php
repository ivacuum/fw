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
	$app['template']->display("ajax/{$file}");
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
		$string .= sprintf('<input type="hidden" name="%s" value="%s">', $key, $value);
	}

	return $string;
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

	return sprintf('%s%s%sp=%d', $base_url, $query_string, $url_delim, $page);
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
		case 'email': return '([\w\!\#$\%\&\'\*\+\-\/\=\?\^\`{\|\}\~]+\.)*(?:[\w\!\#$\%\'\*\+\-\/\=\?\^\`{\|\}\~]|&amp;)+@((((([a-z0-9]{1}[a-z0-9\-]{0,62}[a-z0-9]{1})|[a-z])\.)+[a-z]{2,63})|(\d{1,3}\.){3}\d{1,3}(\:\d{1,5})?)';
		case 'ipv4': return '#^(?:(?:\d{1,2}|1\d\d|2[0-4]\d|25[0-5])\.){3}(?:\d{1,2}|1\d\d|2[0-4]\d|25[0-5])$#';
		case 'url_symbols': return '[a-z\d\_\-\.\x{7f}-\x{ff}\(\)]+';
	}

	return false;
}

/**
* Внутренняя ссылка
*
* @param	string	$url		ЧПУ ссылка
* @param	string	$prefix		Префикс (по умолчанию $app['config']['site.root_path'])
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
		$link = $app['config']['site.root_path'];
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
		$link = false === $prefix ? $app['config']['site.root_path'] : $prefix;
		$link .= substr($link, -1) == '/' ? '' : '/';
	}

	/**
	* Добавляем язык, если выбран отличный от языка по умолчанию и ссылка от корня сайта
	*
	* Если язык уже присутствует в ссылке, то пропускаем этот шаг
	*/
	if ($link == $app['config']['site.root_path'] && false === $prefix)
	{
		if (!$app['site_info']['default'] && (false === strpos($link . $url, "/{$app['site_info']['language']}/")))
		{
			$link = sprintf('%s%s/', $link, $app['site_info']['language']);
		}
	}
	
	$link .= $url;
	$ary = pathinfo($url);
	
	if (isset($ary['extension']) || substr($link, -1) == '/' || !$app['router.options']['default_extension'])
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
	echo json_encode($output, JSON_UNESCAPED_UNICODE);
	exit;
}

/**
* Форма входа или повторного ввода пароля в системе управления
*/
function login_box($redirect = '', $l_explain = '', $l_success = '', $admin = false)
{
	global $app;

	$err = '';

	/* Пользователь пытается авторизоваться как администратор не имея на то прав */
	if ($admin && !$app['auth']->acl_get('a_'))
	{
		trigger_error('NO_AUTH_ADMIN');
	}

	if ($app['request']->is_set_post('submit'))
	{
		$admin             = $admin ? 1 : 0;
		$autologin         = $app['request']->is_set_post('autologin');
		$password          = $app['request']->post('password', '');
		$username_or_email = $app['request']->post('username', '');
		$viewonline        = $admin ? $app['user']['session_viewonline'] : (int) !$app['request']->is_set_post('viewonline');

		if ($admin && $username_or_email != $app['user']['username'] && $username_or_email != $app['user']['user_email'])
		{
			trigger_error('NO_AUTH_ADMIN_USER_DIFFER');
		}

		$result = $app['auth']->login($username_or_email, $password, $autologin, $viewonline, $admin);

		if ($result['status'] == 'OK')
		{
			$message  = $l_success ? $l_success : $app['user']->lang('LOGIN_REDIRECT');

			/* Разрешаем создателю авторизоваться даже при бане */
			if (defined('IN_CHECK_BAN') && $result['user_row']['user_id'] === 1)
			{
				return;
			}
			
			$app['request']->redirect(ilink($redirect));
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

		'S_ADMIN_AUTH'    => $admin,
		'S_HIDDEN_FIELDS' => $s_hidden_fields,
	]);
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
		return preg_replace('#<!\-\- <smile name="(.*?)"><url>(.*?)</url><title>(.*?)</title></smile> \-\->#', '<img src="' . $app['config']['smilies.path'] . '/\2" alt="\1" title="\3">', $message);
	}
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
		default:   $pattern = '/[^a-z\d\.]/u';
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
