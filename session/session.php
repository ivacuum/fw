<?php
/**
* @package fw
* @copyright (c) 2012
*/

namespace fw\session;

/**
* Сеанс
*/
class session implements \ArrayAccess, \IteratorAggregate, \Countable
{
	public $browser        = '';
	public $cookie         = [];
	public $ctime          = 0;
	public $data           = [];
	public $domain         = '';
	public $forwarded_for  = '';
	public $ip             = '';
	public $isp;
	public $page           = '';
	public $page_prev      = '';
	public $referer        = '';
	public $session_id     = '';
	
	public $is_bot = false;
	public $is_registered = false;
	
	protected $cache;
	protected $config;
	protected $db;
	protected $request;
	
	function __construct($request)
	{
		$this->request = $request;
		
		/* Данные посетителя */
		$this->browser       = $this->request->header('User-Agent');
		$this->cookie        = ['u' => 0, 'k' => ''];
		$this->ctime         = time();
		$this->domain        = $this->request->server('SERVER_NAME');
		$this->forwarded_for = $this->request->header('X-Forwarded-For');
		$this->ip            = $this->request->server('REMOTE_ADDR');
		$this->isp           = $this->request->header('Provider', 'internet');
		$this->page          = $this->request->get_requested_url();
		$this->referer       = $this->request->header('Referer');
	}
	
	public function _set_cache($cache)
	{
		$this->cache = $cache;
		
		return $this;
	}
	
	public function _set_config($config)
	{
		$this->config = $config;
		
		return $this;
	}
	
	public function _set_db($db)
	{
		$this->db = $db;
		
		return $this;
	}

	/**
	* Забанен ли пользователь
	*
	* Проверка осуществляется по id, ip и e-mail. Если не передавать параметры, то
	* используются данные текущей сессии. По умолчанию (если $return == false) будет
	* выведена причина бана и прекращена обработка страницы
	*
	* @param string|array	$user_ips	Один или более IP-адресов пользователя
	*/
	private function check_ban($user_id = false, $user_ips = false, $user_email = false, $return = false)
	{
		if (defined('IN_CHECK_BAN'))
		{
			return;
		}

		$banned = false;
		$where_sql = [];
		
		$sql_array = [
			'SELECT' => 'ban_ip, user_id, ban_email, ban_exclude, ban_reason, ban_end',
			'FROM'   => BANLIST_TABLE,
			'WHERE'  => [],
		];
		
		if (false === $user_email)
		{
			$sql_array['WHERE'][] = 'ban_email = ""';
		}
		
		if (false === $user_ips)
		{
			$sql_array['WHERE'][] = '(ban_ip = "" OR ban_exclude = 1)';
		}
		
		if (false === $user_id)
		{
			$sql_array['WHERE'][] = '(user_id = 0 OR ban_exclude = 1)';
		}
		else
		{
			$_sql = '(user_id = ' . $user_id;

			if (false !== $user_email)
			{
				$_sql .= " OR ban_email <> ''";
			}

			if (false !== $user_ips)
			{
				$_sql .= " OR ban_ip <> ''";
			}

			$_sql .= ')';

			$sql_array['WHERE'][] = $_sql;
		}
		
		$result = $this->db->query($this->db->build_query('SELECT', $sql_array));
		$ban_triggered_by = 'user';

		while ($row = $this->db->fetchrow($result))
		{
			if ($row['ban_end'] && $row['ban_end'] < $this->ctime)
			{
				continue;
			}

			$ip_banned = false;

			if (!empty($row['ban_ip']))
			{
				if (!is_array($user_ips))
				{
					$ip_banned = preg_match('#^' . str_replace('\*', '.*?', preg_quote($row['ban_ip'], '#')) . '$#i', $user_ips);
				}
				else
				{
					foreach ($user_ips as $user_ip)
					{
						if (preg_match('#^' . str_replace('\*', '.*?', preg_quote($row['ban_ip'], '#')) . '$#i', $user_ip))
						{
							$ip_banned = true;
							break;
						}
					}
				}
			}

			if ((!empty($row['user_id']) && $row['user_id'] == $user_id) || $ip_banned ||
				(!empty($row['ban_email']) && preg_match('#^' . str_replace('\*', '.*?', preg_quote($row['ban_email'], '#')) . '$#i', $user_email)))
			{
				if (!empty($row['ban_exclude']))
				{
					$banned = false;
					break;
				}
				else
				{
					$banned = true;
					$ban_row = $row;

					if (!empty($row['user_id']) && $row['user_id'] == $user_id)
					{
						$ban_triggered_by = 'user';
					}
					elseif ($ip_banned)
					{
						$ban_triggered_by = 'ip';
					}
					else
					{
						$ban_triggered_by = 'email';
					}
				}
			}
		}

		$this->db->freeresult($result);

		if ($banned && !$return)
		{
			global $message_title;

			$this->preferences();

			if ($this->data['user_id'])
			{
				/* Если пользователь получил бан, то он должен покинуть сайт */
				$this->session_end();
			}

			$till_date = $ban_row['ban_end'] ? $this->create_date($ban_row['ban_end']) : 'не предвидится';
			$message = '<b>Заблокирован</b>: ' . $ban_triggered_by . '<br><b>Окончание бана</b>: ' . $till_date . '<br><b>Причина</b>: ' . $ban_row['ban_reason'];

			$this->session_end(false);

			define('IN_CHECK_BAN', 1);
			$message_title = 'Вы заблокированы на сайте';
			trigger_error($message);
		}

		return $banned && $ban_row['ban_reason'] ? $row['ban_reason'] : $banned;
	}

	/**
	* Удаление ключей автовхода
	*
	* Используется при смене пароля
	*/
	public function reset_login_keys($user_id = false, $set_new_key = true)
	{
		$user_id = $user_id ?: $this->data['user_id'];

		$sql = '
			DELETE
			FROM
				' . SESSIONS_KEYS_TABLE . '
			WHERE
				user_id = ' . $this->db->check_value($user_id);
		$this->db->query($sql);

		/**
		* Также удаляем все текущие сессий, кроме используемой
		*/
		$sql = '
			DELETE
			FROM
				' . SESSIONS_TABLE . '
			WHERE
				user_id = ' . $this->db->check_value($user_id) .
			(($user_id === $this->data) ? ' AND session_id <> ' . $this->db->check_value($this->session_id) : '');
		$this->db->query($sql);

		if (false !== $set_new_key && $user_id === $this->data['user_id'] && $this->cookie['k'])
		{
			/* Создаем новый ключ, если был выполнен автовход */
			$this->set_login_key($user_id);
		}
	}
	
	/**
	* Основная функция сессий
	* Вызывается на всех страницах
	*
	* Получаем данные пользоваля (браузер, ip, страницу, установленные cookie, session_id)
	*
	* Если cookie включены, то ищем session_id в них
	* Если выключены - ищем в адресной строке
	*
	* Если session_id не найден, то это новый посетитель => отправляем обработку методу session_create()
	*/
	public function session_begin($update_page = true)
	{
		$update_last_visit_time = false;

		/**
		* Отличительные черты пользователя
		*/
		$this->cookie['k'] = $this->request->cookie($this->config['cookie_name'] . '_k', '');
		$this->cookie['u'] = $this->request->cookie($this->config['cookie_name'] . '_u', 0);
		$this->session_id  = $this->request->cookie($this->config['cookie_name'] . '_sid', '');
		
		if (!$this->session_id)
		{
			/* Пользователь зашел впервые, надо создать сессию */
			return $this->session_create();
		}
		
		if (!$this->cookie['u'])
		{
			/* Гость */
			$sql = '
				SELECT
					*
				FROM
					' . SESSIONS_TABLE . '
				WHERE
					session_id = ' . $this->db->check_value($this->session_id) . '
				AND
					user_id = 0';
			$this->db->query($sql);
			$this->data = $this->db->fetchrow();
			$this->db->freeresult();

			if (!empty($this->data))
			{
				$this->guest_defaults();
			}
		}
		else
		{
			/* Зарегистрированный пользователь */
			$sql = '
				SELECT
					s.*,
					u.*
				FROM
					' . SESSIONS_TABLE . ' s
				LEFT JOIN
					' . USERS_TABLE . ' u ON (u.user_id = s.user_id)
				WHERE
					s.session_id = ' . $this->db->check_value($this->session_id);
			$this->db->query($sql);
			$this->data = $this->db->fetchrow();
			$this->db->freeresult();
		}
		
		if (!isset($this->data['user_id']))
		{
			/* Сессия не найдена, создаем новую */
			return $this->session_create();
		}
		
		/**
		* Проверяем в IP только первые два числа
		*/
		$ip_check = 2;

		$s_ip = implode('.', array_slice(explode('.', $this->data['session_ip']), 0, $ip_check));
		$u_ip = implode('.', array_slice(explode('.', $this->ip), 0, $ip_check));

		$s_browser = trim(strtolower(substr($this->data['session_browser'], 0, 149)));
		$u_browser = trim(strtolower(substr($this->browser, 0, 149)));

		$s_forwarded_for = substr($this->data['session_forwarded_for'], 0, 254);
		$u_forwarded_for = substr($this->forwarded_for, 0, 254);

		/* Сверяем текущие данные с табличными */
		if ($s_ip !== $u_ip || $s_browser !== $u_browser || $s_forwarded_for !== $u_forwarded_for)
		{
			return $this->session_create();
		}
		
		$session_expired = $this->is_session_expired();
		$this->page_prev = $this->data['session_page'];

		if (!$session_expired)
		{
			/* Обновляем информацию о местонахождении не чаще раза в минуту */
			if ($update_page && ($this->ctime - $this->data['session_time'] > 60 || $this->data['session_page'] != $this->page))
			{
				$this->data['session_time'] = $this->ctime;
				$sql_ary = ['session_time' => $this->ctime];

				if ($update_page && $this->data['session_domain'] != $this->domain)
				{
					$sql_ary['session_domain'] = $this->data['session_domain'] = (string) $this->domain;
				}
				
				if ($update_page && $this->data['session_page'] != $this->page)
				{
					$sql_ary['session_page'] = $this->data['session_page'] = (string) $this->page;
				}
				
				$this->session_update($sql_ary);
			}

			$this->is_bot = false;
			$this->is_registered = $this->data['user_id'] > 0;

			return true;
		}

		return $this->session_create();
	}

	/**
	* Создание новой сессии
	*
	* @param	int		$user_id	ID пользователя, false или 0 - анонимный
	* @param	bool	$autologin	Автовход, используя cookie
	*/
	public function session_create($user_id = false, $autologin = false, $set_admin = false, $viewonline = true, $openid_provider = '')
	{
		$this->data = [];

		if (!$this->config['autologin_allow'])
		{
			$this->cookie['k'] = $autologin = false;
		}

		/**
		* А не бот ли к нам заглянул?
		*/
		$bot = false;
		$bots = $this->cache->obtain_bots();

		foreach ($bots as $row)
		{
			/**
			* Сравниваем браузер бота с данными в базе
			* Если что-то совпало, то бот зарегистрирован
			*/
			if ($row['bot_agent'] && preg_match('#' . str_replace('\*', '.*?', preg_quote($row['bot_agent'], '#')) . '#i', $this->browser))
			{
				$bot = (int) $row['user_id'];
				break;
			}
		}

		if (isset($this->cookie['k']) && $this->cookie['k'] && $this->cookie['u'] && !sizeof($this->data))
		{
			$sql = '
				SELECT
					u.*,
					sk.openid_provider
				FROM
					' . USERS_TABLE . ' u
				LEFT JOIN
					' . SESSIONS_KEYS_TABLE . ' sk ON (sk.user_id = u.user_id)
				WHERE
					u.user_id = ' . $this->db->check_value($this->cookie['u']) . '
				AND
					sk.key_id = ' . $this->db->check_value(md5($this->cookie['k']));
			$result = $this->db->query($sql);
			$this->data = $this->db->fetchrow($result);
			$this->db->freeresult($result);
			$bot = false;
			$openid_provider = $this->data['openid_provider'] ?: $openid_provider;
		}
		elseif (false !== $user_id && !sizeof($this->data))
		{
			$this->cookie['k'] = '';
			$this->cookie['u'] = $user_id;

			/**
			* Если был передан ID пользователя, то
			* получаем его данные из базы
			*/
			$sql = '
				SELECT
					*
				FROM
					' . USERS_TABLE . '
				WHERE
					user_id = ' . $this->db->check_value($this->cookie['u']);
			$result = $this->db->query($sql);
			$this->data = $this->db->fetchrow($result);
			$this->db->freeresult($result);
			$bot = false;
		}

		if (!sizeof($this->data) || !is_array($this->data))
		{
			/**
			* Если массив данных пользователя до сих пор не сформирован, то это анонимный
			* пользатель или бот. Настало время получить его данные из базы.
			*/
			$this->cookie['k'] = '';
			$this->cookie['u'] = $bot ? $bot : 0;

			if ($bot)
			{
				/* Бот получает одну и ту же сессию */
				$sql = '
					SELECT
						u.*,
						s.*
					FROM
						' . USERS_TABLE . ' u
					LEFT JOIN
						' . SESSIONS_TABLE . ' s ON (s.user_id = u.user_id)
					WHERE
						u.user_id = ' . $this->db->check_value($bot);
				$result = $this->db->query($sql);
				$this->data = $this->db->fetchrow($result);
				$this->db->freeresult($result);
				
				$this->data['user_id'] = $bot;
			}
			else
			{
				/**
				* Для гостя данные более не берутся из базы
				*/
				$this->guest_defaults();
			}
		}

		/**
		* Время последнего визита
		*/
		if ($this->data['user_id'] > 0 && !$bot)
		{
			$this->data['session_last_visit'] = isset($this->data['session_time']) && $this->data['session_time'] ? $this->data['session_time'] : ((isset($this->data['user_last_visit'])) ? $this->data['user_last_visit'] : $this->ctime);
		}
		else
		{
			$this->data['session_last_visit'] = $this->ctime;
		}

		/**
		* Проверка бана
		*/
		if (!$this->config['forwarded_for_check'])
		{
			$this->check_ban($this->data['user_id'], $this->ip);
		}
		else
		{
			$ips = explode(' ', $this->forwarded_for);
			$ips[] = $this->ip;
			$this->check_ban($this->data['user_id'], $ips);
		}

		$this->is_bot = $bot ? true : false;
		$this->is_registered = !$bot && $this->data['user_id'] > 0 ? true : false;

		if ($this->is_bot && $bot == $this->data['user_id'] && $this->data['session_id'])
		{
			$ip_check = 2;

			$s_ip = implode('.', array_slice(explode('.', $this->data['session_ip']), 0, $ip_check));
			$u_ip = implode('.', array_slice(explode('.', $this->ip), 0, $ip_check));

			$s_browser = trim(strtolower(substr($this->data['session_browser'], 0, 149)));
			$u_browser = trim(strtolower(substr($this->browser, 0, 149)));

			$s_forwarded_for = substr($this->data['session_forwarded_for'], 0, 254);
			$u_forwarded_for = substr($this->forwarded_for, 0, 254);

			/* Сверяем текущие данные с табличными */
			if ($s_ip === $u_ip && $s_browser === $u_browser && $s_forwarded_for === $u_forwarded_for)
			{
				$this->session_id = $this->data['session_id'];

				/* Обновляем информацию о местонахождении не чаще раза в минуту */
				if ($this->ctime - $this->data['session_time'] > 60 || $this->data['session_page'] != $this->page)
				{
					$this->data['session_time'] = $this->data['session_last_visit'] = $this->ctime;
					$this->data['session_page'] = $this->page;

					$this->session_update([
						'session_last_visit'      => $this->ctime,
						'session_time'            => $this->ctime,
						'session_domain'          => $this->domain,
						'session_page'            => $this->page,
						'session_referer'         => $this->referer,
					]);

					$this->user_update([
						'user_session_time' => (int) $this->data['session_time'],
						'user_session_page' => (string) $this->page,
						'user_last_visit'   => (int) $this->data['session_last_visit'],
						'user_ip'           => (string) $this->ip,
					]);
				}

				return true;
			}
			else
			{
				/**
				* Для каждого бота должна остаться только одна сессия
				*/
				$sql = '
					DELETE
					FROM
						' . SESSIONS_TABLE . '
					WHERE
						user_id = ' . $this->db->check_value($this->data['user_id']);
				$this->db->query($sql);
			}
		}

		$session_autologin = ($this->cookie['k'] || $autologin) && $this->is_registered ? true : false;
		$set_admin = $set_admin && $this->is_registered ? true : false;

		/**
		* Массив данных сессии
		*/
		$sql_ary = [
			'user_id'                 => (int) $this->data['user_id'],
			'openid_provider'         => (string) $openid_provider,
			'session_last_visit'      => (int) $this->data['session_last_visit'],
			'session_start'           => (int) $this->ctime,
			'session_time'            => (int) $this->ctime,
			'session_browser'         => (string) trim(substr($this->browser, 0, 149)),
			'session_forwarded_for'   => (string) $this->forwarded_for,
			'session_domain'          => (string) $this->domain,
			'session_page'            => (string) $this->page,
			'session_referer'         => (string) $this->referer,
			'session_ip'              => (string) $this->ip,
			'session_viewonline'      => (int) $viewonline,
			'session_autologin'       => (int) $autologin,
			'session_admin'           => (int) $set_admin
		];

		$sql = '
			DELETE
			FROM
				' . SESSIONS_TABLE . '
			WHERE
				session_id = ' . $this->db->check_value($this->session_id) . '
			AND
				user_id = 0';
		$this->db->query($sql);

		/* Уникальный ID сессии */
		$this->session_id = $this->data['session_id'] = md5(make_random_string());
		$sql_ary['session_id'] = (string) $this->session_id;

		/* Добавляем информацию о сессии в базу */
		$sql = 'INSERT INTO ' . SESSIONS_TABLE . ' ' . $this->db->build_array('INSERT', $sql_ary);
		$this->db->query($sql);

		if ($session_autologin)
		{
			$this->set_login_key(false, false, false, $openid_provider);
		}

		$this->data = array_merge($this->data, $sql_ary);

		if (!$bot)
		{
			/* Устанавливаем куки */
			$cookie_expire = $this->ctime + (($this->config['autologin_time']) ? 86400 * $this->config['autologin_time'] : 31536000);
			$this->set_cookie('k', $this->cookie['k'], $cookie_expire);
			$this->set_cookie('u', $this->cookie['u'], $cookie_expire);
			$this->set_cookie('sid', $this->session_id, $cookie_expire);
			unset($cookie_expire);

			/**
			* Соль для форм
			*/
			$sql = '
				SELECT
					COUNT(*) AS sessions
				FROM
					' . SESSIONS_TABLE . '
				WHERE
					user_id = ' . $this->db->check_value($this->data['user_id']) . '
				AND
					session_time >= ' . $this->db->check_value($this->ctime - (max($this->config['session_length'], $this->config['form_token_lifetime'])));
			$result = $this->db->query($sql);
			$row = $this->db->fetchrow($result);
			$this->db->freeresult($result);

			if ((int) $row['sessions'] <= 1 || empty($this->data['user_form_salt']))
			{
				$this->data['user_form_salt'] = make_random_string();
				
				$this->user_update(['user_form_salt' => (string) $this->data['user_form_salt']]);
			}
		}
		else
		{
			/**
			* Обновляем дату последнего визита бота
			*/
			$this->user_update([
				'user_session_time' => (int) $this->ctime,
				'user_session_page' => (string) $this->page,
				'user_last_visit'   => (int) $this->ctime,
				'user_ip'           => (string) $this->ip
			]);
		}

		return true;
	}

	/**
	* Завершение сессии текущего пользователя
	*/
	public function session_end($new_session = true)
	{
		$sql = '
			DELETE
			FROM
				' . SESSIONS_TABLE . '
			WHERE
				session_id = ' . $this->db->check_value($this->session_id) . '
			AND
				user_id = ' . $this->db->check_value($this->data['user_id']);
		$this->db->query($sql);

		if ($this->data['user_id'] > 0)
		{
			$this->user_update(['user_last_visit' => $this->ctime]);

			if ($this->cookie['k'])
			{
				$sql = '
					DELETE
					FROM
						' . SESSIONS_KEYS_TABLE . '
					WHERE
						user_id = ' . $this->db->check_value($this->data['user_id']) . '
					AND
						key_id = ' . $this->db->check_value(md5($this->cookie['k']));
				$this->db->query($sql);
			}
		}

		/* Трём куки */
		$cookie_expire = $this->ctime - 31536000;
		$this->set_cookie('k', '', $cookie_expire);
		$this->set_cookie('u', '', $cookie_expire);
		$this->set_cookie('sid', '', $cookie_expire);
		unset($cookie_expire);
		
		$this->data = [];
		$this->session_id = '';

		if (false !== $new_session)
		{
			/* Создаём новую сессию для анонимного пользователя */
			$this->session_create();
		}

		return true;
	}

	/**
	* Обновление данных сессии
	*/
	public function session_update(array $sql_ary, $session_id = false)
	{
		$session_id = $session_id ?: $this->session_id;
		
		$sql = '
			UPDATE
				' . SESSIONS_TABLE . '
			SET
				' . $this->db->build_array('UPDATE', $sql_ary) . '
			WHERE
				session_id = ' . $this->db->check_value($session_id);
		$this->db->query($sql);
	}

	/**
	* Сессия с возможностью входа в админ-центр
	*/
	public function set_admin()
	{
		$this->session_update(['session_admin' => 1]);
	}

	/**
	* Установка cookies
	*
	* @param int $time Метка времени, до которой cookie будет действительна (0 - в течение сеанса)
	*/
	public function set_cookie($name, $data, $time)
	{
		$cookie_name   = rawurlencode($this->config['cookie_name'] . '_' . $name) . '=' . rawurlencode($data);
		$cookie_expire = gmdate('D, d-M-Y H:i:s \\G\\M\\T', $time);
		$cookie_domain = !$this->config['cookie_domain'] || $this->config['cookie_domain'] == 'localhost' || $this->config['cookie_domain'] == '127.0.0.1' ? '' : '; domain=' . $this->config['cookie_domain'];

		header('Set-Cookie: ' . $cookie_name . (($cookie_expire) ? '; expires=' . $cookie_expire : '') . '; path=' . $this->config['cookie_path'] . $cookie_domain . ((!$this->config['cookie_secure']) ? '' : '; secure') . '; HttpOnly', false);
	}

	/**
	* Создание ключа для автовхода
	*
	* Для одного посетителя может быть создано несколько ключей,
	* если он выполнил автовход с нескольких браузеров
	*/
	public function set_login_key($user_id = false, $key = false, $user_ip = false, $openid_provider = '')
	{
		$user_id = $user_id ?: $this->data['user_id'];
		$user_ip = $user_ip ?: $this->ip;
		$key = $key ?: (($this->cookie['k']) ? $this->cookie['k'] : false);

		$key_id = make_random_string(16);

		$sql_ary = [
			'key_id'          => (string) md5($key_id),
			'openid_provider' => (string) $openid_provider,
			'last_ip'         => (string) $this->ip,
			'last_login'      => (int) $this->ctime
		];

		if ($key)
		{
			$sql = '
				UPDATE
					' . SESSIONS_KEYS_TABLE . '
				SET
					' . $this->db->build_array('UPDATE', $sql_ary) . '
				WHERE
					user_id = ' . $this->db->check_value($user_id) . '
				AND
					key_id = ' . $this->db->check_value(md5($key));
		}
		else
		{
			$sql_ary['user_id'] = (int) $user_id;
			$sql = 'INSERT INTO ' . SESSIONS_KEYS_TABLE . ' ' . $this->db->build_array('INSERT', $sql_ary);
		}

		$this->db->query($sql);
		$this->cookie['k'] = $key_id;
		
		return false;
	}

	/**
	* Стандартные установки гостя
	*/
	private function guest_defaults()
	{
		$this->data['user_id'] = 0;
		$this->data['user_access'] = '';
		$this->data['user_active'] = 0;
		$this->data['username'] = 'Guest';
	}

	/**
	* Устарела ли сессия
	*/
	private function is_session_expired()
	{
		if (!$this->data['session_autologin'])
		{
			/**
			* Если используется автовход, то проверяем включен ли он на сайте
			* и не превышает ли максимальное время действия автовхода
			*/
			if ($this->data['session_time'] < $this->ctime - ($this->config['session_length'] + 60))
			{
				return true;
			}
		}
		elseif (!$this->config['autologin_allow'] || ($this->config['autologin_time'] && $this->data['session_time'] < $this->ctime - (86400 * $this->config['autologin_time']) + 60))
		{
			/**
			* Иначе - проверяем не превышает ли время простоя время жизни сессии
			*/
			return true;
		}
		
		return false;
	}
	
	/**
	* Реализация интерфейса Countable
	*/
	public function count()
	{
		return sizeof($this->data);
	}
	
	/**
	* Реализация интерфейса IteratorAggregate
	*/
	public function getIterator()
	{
		return new ArrayIterator($this->data);
	}
	
	/**
	* Реализация интерфейса ArrayAccess
	*/
	public function offsetExists($key)
	{
		return isset($this->data[$key]);
	}
	
	public function offsetGet($key)
	{
		return isset($this->data[$key]) ? $this->data[$key] : '';
	}
	
	public function offsetSet($key, $value)
	{
		$this->data[$key] = $value;
	}
	
	public function offsetUnset($key)
	{
		trigger_error('Функция unset() не поддерживается');
	}
}
