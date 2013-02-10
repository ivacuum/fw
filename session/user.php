<?php
/**
* @package fw
* @copyright (c) 2012
*/

namespace fw\session;

/**
* Пользователь
*/
class user extends session
{
	public $lang = [];
	
	/**
	* Создание даты в нужном формате
	*
	* @param	int		$gmepoch	Время
	* @param	string	$format		Формат вывода даты
	* @param	bool	$forcedate	Вывод на английском (по умолчанию дата переводится на язык пользователя)
	*
	* @return	string				Дата в выбранном формате
	*/
	public function create_date($gmepoch, $format = false, $forcedate = false, $short_form = false)
	{
		static $midnight;

		if ($gmepoch == 0)
		{
			return $this->lang['NEVER'];
		}

		/**
		* Определяем переменные
		*/
		$format = !$format ? $this->config['dateformat'] : $format;
		$tz = 3600 * $this->config['site_tz'];
		$forcedate = !isset($this->lang['datetime']) ? true : $forcedate;

		if (!$midnight)
		{
			/* Определение полуночи */
			list($d, $m, $y) = explode(' ', gmdate('j n Y', $this->ctime + $tz));
			$midnight = gmmktime(0, 0, 0, $m, $d, $y) - $tz;
		}

		/**
		* Короткая форма
		* |j F Y|, H:i выдаст:
		*
		* Сегодня, 18:25
		* Вчера, 14:55
		* 10 Августа 2009
		*/
		if (false !== $short_form)
		{
			if (strpos($format, '|') !== false && $gmepoch < $midnight - 86400 && !$forcedate)
			{
				return strtr(@gmdate(str_replace('|', '', substr($format, 0, strrpos($format, '|'))), $gmepoch + $tz), $this->lang['datetime']);
			}
		}

		if (strpos($format, '|') === false || ($gmepoch < $midnight - 86400 && !$forcedate) || ($gmepoch > $midnight + 172800 && !$forcedate))
		{
			return strtr(@gmdate(str_replace('|', '', $format), $gmepoch + $tz), $this->lang['datetime']);
		}

		if ($gmepoch > $midnight + 86400 && !$forcedate)
		{
			/* Завтра ... */
			return $this->lang['datetime']['TOMORROW'] . strtr(@gmdate(substr($format, strpos($format, '|', 1) + 1), $gmepoch + $tz), $this->lang['datetime']);
		}
		elseif ($gmepoch > $midnight && !$forcedate)
		{
			/* Сегодня ... */
			return $this->lang['datetime']['TODAY'] . strtr(@gmdate(substr($format, strpos($format, '|', 1) + 1), $gmepoch + $tz), $this->lang['datetime']);
		}
		elseif ($gmepoch > $midnight - 86400 && !$forcedate)
		{
			/* Вчера ... */
			return $this->lang['datetime']['YESTERDAY'] . strtr(@gmdate(substr($format, strpos($format, '|', 1) + 1), $gmepoch + $tz), $this->lang['datetime']);
		}

		return strtr(@gmdate(str_replace('|', '', $format), $gmepoch + $tz), $this->lang['datetime']);
	}

	public function get_back_url()
	{
		return urlencode('//' . $this->request->hostname . $this->page);
	}
	
	/**
	* Проверка авторизации
	*/
	public function is_auth($mode = '')
	{
		if ($this->is_registered)
		{
			return true;
		}

		switch ($mode)
		{
			/**
			* Запрет просмотра страницы
			*/
			case 'deny':
			
				http_response_code(401);
				// \fw\core\errorhandler::log_mail('Unauthorized access to http://' . $this->request->hostname . $this->page . ' page', '401 Unauthorized');

				if ($this->request->hostname == 'dev.ivacuum.ru')
				{
					trigger_error(sprintf($this->lang['NEED_LOGIN'], ilink(sprintf('/ucp/login.html?goto=%s', $this->get_back_url()))));
				}
				
				trigger_error(sprintf($this->lang['NEED_LOGIN'], ilink(sprintf('http://ivacuum.ru/ucp/login.html?goto=%s', $this->get_back_url()))));

			break;
			/**
			* Перенаправление на форму авторизации
			*/
			case 'redirect':
			
				if ($this->request->hostname == 'dev.ivacuum.ru')
				{
					$this->request->redirect(ilink(sprintf('/ucp/login.html?goto=%s', $this->get_back_url())), $this->config['router_local_redirect']);
				}
				
				$this->request->redirect(ilink(sprintf('http://ivacuum.ru/ucp/login.html?goto=%s', $this->get_back_url())), $this->config['router_local_redirect']);
			
			break;
			/**
			* Возврат результата проверки. Ручная обработка в вызывающем скрипте
			*/
			default:

				return $this->is_registered;

			break;
		}
	}

	/**
	* Возвращаем перевод
	* Если языковый элемент не найден, то возвращаем пустую строку
	*/
	public function lang()
	{
		$args = func_get_args();
		$key  = $args[0];

		/* Если языковой элемент не найден, то возвращаем индекс элемента */
		if (!isset($this->lang[$key]))
		{
			return $key;
		}

		/* Был просто запрошен индекс */
		if (sizeof($args) == 1)
		{
			return $this->lang[$key];
		}

		/**
		* Запрос с параметрами:
		*
		* $this->lang('INDEX', 5, 'some text', 2.39)
		*/
		$args[0] = $this->lang[$key];
		return call_user_func_array('sprintf', $args);
	}
	
	/**
	* Доступен ли сайт в выбранной локализации
	*/
	public function language_exists($language)
	{
		$sites = $this->cache->obtain_sites();
		
		foreach ($sites as $row)
		{
			if ($this->request->hostname == $row['site_url'] && $language == $row['site_language'])
			{
				return true;
			}
		}
		
		return false;
	}

	/**
	* Загрузка языковых файлов из базы
	*
	* @param	string	$lang_file		Имя файла для загрузки
	* @param	bool	$force_update	Нужно ли принудительно обновить данные из базы
	* @param	string	$language		Язык для обновления (при обновлении переводов из админки)
	*/
	public function load_language($lang_file, $force_update = false, $language = false)
	{
		$lang      = [];
		$language  = $language ?: $this->lang['.'];
		$lang_file = str_replace('/', '_', $lang_file);
		
		/* Общая локализация */
		$lang = array_merge_recursive($lang, $this->get_i18n_data(0, $language, $lang_file, $force_update));
		
		if (0 !== strpos($lang_file, 'fw_'))
		{
			/* Локализация проекта */
			$site_info = $this->cache->get_site_info_by_url_lang($this->request->hostname, $language);
			
			$lang = array_merge_recursive($lang, $this->get_i18n_data($site_info['id'], $language, $lang_file, $force_update));
		}
		
		if ($language == $this->lang['.'])
		{
			$this->lang = array_merge_recursive($this->lang, $lang);
			return;
		}
		
		return $lang;
	}

	/**
	* Авторизация
	*/
	public function login($username, $password)
	{
		$username = mb_strtolower($username);
		
		if (!$username)
		{
			return [
				'message'  => 'Вы не указали имя',
				'status'   => 'ERROR_USERNAME',
				'user_row' => ['user_id' => 0],
			];
		}

		if (!$password)
		{
			return [
				'message'  => 'Вы не указали пароль',
				'status'   => 'ERROR_PASSWORD',
				'user_row' => ['user_id' => 0],
			];
		}

		$sql = '
			SELECT
				user_id,
				user_active,
				username,
				user_password,
				user_salt,
				user_email,
				user_login_attempts
			FROM
				' . USERS_TABLE . '
			WHERE
				username_clean = ' . $this->db->check_value($username);
		$this->db->query($sql);
		$row = $this->db->fetchrow();
		$this->db->freeresult();
		$attempts = 0;
		
		// if ($ip)
		// {
		// 	$sql = '
		// 		SELECT
		// 			COUNT(attempt_id) AS attempts
		// 		FROM
		// 			' . LOGIN_ATTEMPT_TABLE . '
		// 		WHERE
		// 			attempt_time > ' . (time() - (int) $this->config['ip_login_limit_time']) . '
		// 		AND
		// 			attempt_ip = ' . $this->db->check_value($ip);
		// 	$this->db->query($sql);
		// 	$attempts = (int) $this->db->fetchfield('attempts');
		// 	$this->db->freeresult();
		// 
		// 	$sql_ary = [
		// 		'attempt_ip'			=> $ip,
		// 		'attempt_browser'		=> trim(substr($browser, 0, 149)),
		// 		'attempt_forwarded_for'	=> $forwarded_for,
		// 		'attempt_time'			=> time(),
		// 		'user_id'				=> $row ? (int) $row['user_id'] : 0,
		// 		'username'				=> $username,
		// 		'username_clean'		=> $username_clean,
		// 	];
		// 	
		// 	$sql = 'INSERT INTO ' . LOGIN_ATTEMPT_TABLE . $this->db->build_array('INSERT', $sql_ary);
		// 	$this->db->sql_query($sql);
		// }

		if (!$row)
		{
			return [
				'message'  => 'Неверно указано имя или пароль',
				'status'   => 'ERROR_LOGIN',
				'user_row' => ['user_id' => 0],
			];
		}
		
		/* Не пора ли показывать капчу */
		// $show_captcha = ($this->config['max_login_attempts'] && $row['user_login_attempts'] >= $this->config['max_login_attempts']) || ($this->config['ip_login_limit_max'] && $attempts >= $this->config['ip_login_limit_max']);
		$show_captcha = false;
		
		// if ($show_captcha)
		// {
		// 	$captcha =& captcha\factory::get_instance($this->config['captcha_plugin']);
		// 	$captcha->init(CONFIRM_LOGIN);
		// 	$vc_response = $captcha->validate($row);
		// 	
		// 	if ($vc_response)
		// 	{
		// 		return [
		// 			'status'    => 'error_attempts',
		// 			'error_msg' => 'LOGIN_ERROR_ATTEMPTS',
		// 			'user_row'  => $row,
		// 		];
		// 	}
		// 	
		// 	$captcha->reset();
		// }

		/* Проверка пароля */
		if (($row['user_salt'] && md5($password . $row['user_salt']) == $row['user_password']) || (!$row['user_salt'] && md5($password) == $row['user_password']))
		{
			if (!$row['user_salt'])
			{
				$salt = make_random_string(5);
				
				$this->user_update([
					'user_password' => md5($password . $salt),
					'user_salt'     => $salt
				], $row['user_id']);
			}
			
			/*
			$sql = '
				DELETE
				FROM
					' . LOGIN_ATTEMPT_TABLE . '
				WHERE
					user_id = ' . $this->db->check_value($row['user_id']);
			$this->db->query($sql);
			*/
			
			if ($row['user_login_attempts'])
			{
				$this->user_update(['user_login_attempts' => 0], $row['user_id']);
			}
			
			if (!$row['user_active'])
			{
				return [
					'message'  => 'Эта учетная запись отключена',
					'status'   => 'ERROR_NOT_ACTIVE',
					'user_row' => ['user_id' => $row],
				];
			}
			
			/* Успешная авторизация */
			return [
				'message'  => '',
				'status'   => 'OK',
				'user_row' => $row,
			];
		}
		
		/* Неверный пароль */
		$this->user_update(['user_login_attempts' => $row['user_login_attempts'] + 1], $row['user_id']);
		
		return [
			'message'  => $show_captcha ? 'Слишком много ошибок при авторизации. Введите код подтверждения' : 'Неверно указано имя или пароль',
			'status'   => 'ERROR_LOGIN',
			'user_row' => $row,
		];
	}

	/**
	* Установка настроек пользователя
	*/
	public function setup()
	{
		$this->lang['.'] = $this->detect_language();
		$this->load_language('general');
	}

	/**
	* Вычисление подсети абонента
	*/
	public function spark_subnet()
	{
		static $subnet = '';

		if (!$subnet)
		{
			list($ip1, $ip2, $ip3, $ip4) = explode('.', $this->ip);

			$ip4 -= $ip4 % 8;
			$subnet = sprintf('%d.%d.%d.%d', $ip1, $ip2, $ip3, $ip4);
		}

		return $subnet;
	}

	/**
	* Обновление данных пользователя
	*/
	public function user_update($sql_ary, $user_id = false)
	{
		$user_id = $user_id ?: $this->data['user_id'];
		
		$sql = '
			UPDATE
				' . USERS_TABLE . '
			SET
				' . $this->db->build_array('UPDATE', $sql_ary) . '
			WHERE
				user_id = ' . $this->db->check_value($user_id);
		$this->db->query($sql);
	}
	
	/**
	* Определение языка сайта по URL
	*/
	private function detect_language()
	{
		global $app;
		
		$url = trim(htmlspecialchars_decode($this->page), '/');
		$params = $url ? explode('/', $url) : [];
		
		if (empty($params))
		{
			return $app['site_info']['language'];
		}
		
		$language = $params[0];
		
		if (strlen($language) != 2)
		{
			return $app['site_info']['language'];
		}
		
		if ($app['site_info']['default'])
		{
			/* Если выбрана локализация по умолчанию, то убираем язык из URL */
			foreach ($this->cache->obtain_languages() as $id => $row)
			{
				if ($language == $row['language_title'])
				{
					$this->request->redirect(ilink(mb_substr($this->page, 3)));
				}
			}
			
			return $app['site_info']['language'];
		}
			
		if ($this->language_exists($language))
		{
			$this->page = mb_substr($this->page, 3);
			return $language;
		}
	}
	
	/**
	* Извлечение переводов
	*/
	private function get_i18n_data($site_id, $language, $lang_file, $force_update = false)
	{
		$prefix = 0 === $site_id ? 'src' : $this->request->hostname;
		$cache_entry = sprintf('%s_i18n_%s_%s', $prefix, $lang_file, $language);
		
		if ($force_update || (false === $lang = $this->cache->_get($cache_entry)))
		{
			$sql = '
				SELECT
					i18n_subindex,
					i18n_index,
					i18n_file,
					i18n_translation
				FROM
					' . I18N_TABLE . '
				WHERE
					site_id = ' . $this->db->check_value($site_id) . '
				AND
					i18n_lang = ' . $this->db->check_value($language) . '
				AND
					i18n_file = ' . $this->db->check_value($lang_file);
			$this->db->query($sql);
			$lang = [];

			while ($row = $this->db->fetchrow())
			{
				if ($row['i18n_subindex'])
				{
					$lang[$row['i18n_subindex']][$row['i18n_index']] = $row['i18n_translation'];
				}
				else
				{
					$lang[$row['i18n_index']] = $row['i18n_translation'];
				}
			}

			$this->db->freeresult();
			$this->cache->_set($cache_entry, $lang);
		}
		
		return $lang;
	}
}
