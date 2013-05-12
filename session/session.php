<?php
/**
* @package fw
* @copyright (c) 2013
*/

namespace fw\session;

use ArrayAccess;
use Countable;
use IteratorAggregate;
use SessionHandlerInterface;

class session implements ArrayAccess, Countable, IteratorAggregate, SessionHandlerInterface
{
	public $browser        = '';
	public $cookie         = [];
	public $data           = [];
	public $forwarded_for  = '';
	public $ip             = '';
	public $isp;
	public $page           = '';
	public $page_prev      = '';
	public $referer        = '';
	public $session_id     = '';
	
	public $is_bot = false;
	public $is_registered = false;
	
	protected $signin_url;
	protected $site_id;
	
	protected $cache;
	protected $config;
	protected $db;
	protected $request;
	
	function __construct($cache, $config, $db, $request, array $options = [], $site_id, $signin_url)
	{
		$this->cache   = $cache;
		$this->config  = $config;
		$this->db      = $db;
		$this->request = $request;
		
		$this->site_id    = $site_id;
		$this->signin_url = $signin_url;
		
		/* Данные посетителя */
		$this->browser       = $this->request->header('User-Agent');
		$this->cookie        = ['u' => 0, 'k' => ''];
		$this->forwarded_for = $this->request->header('X-Forwarded-For');
		$this->ip            = $this->request->server('REMOTE_ADDR');
		$this->referer       = $this->request->header('Referer');
		
		$options['cookie_domain'] = $options['cookie_domain'] ?: $this->config['cookie.domain'];
		$options['name'] = $this->config['cookie.prefix'] . $options['name'];
		
		foreach ($options as $key => $value)
		{
			ini_set("session.{$key}", $value);
		}
		
		session_set_save_handler($this);
		session_start();
	}
	
	public function close()
	{
		return true;
	}
	
	public function delete_login_key($key_id, $user_id = false)
	{
		$user_id = $user_id ?: $this->data['user_id'];

		$sql = 'DELETE FROM site_sessions_keys WHERE user_id = ? AND key_id = ?';
		$this->db->query($sql, [$user_id, md5($key_id)]);
		
		return $this;
	}
	
	public function destroy($session_id = false)
	{
		$session_id = $session_id ?: $this->session_id;
		
		$sql = 'DELETE FROM site_sessions WHERE session_id = ? AND user_id = ?';
		$this->db->query($sql, [$session_id, $this->data['user_id']]);

		if ($this->data['user_id'] > 0)
		{
			register_shutdown_function([$this, 'user_update'], ['user_last_visit' => $this->request->time]);

			if ($this->cookie['k'])
			{
				$this->delete_login_key($this->cookie['k']);
			}
		}

		$this->set_cookie('k', false);
		$this->set_cookie('u', false);
		$this->set_cookie('sid', false);
		
		$_SESSION = $this->data = [];
		$this->cookie = ['u' => 0, 'k' => ''];
		$this->session_id = '';

		return true;
	}
	
	public function gc($maxlifetime)
	{
		return true;
	}
	
	public function get_back_url()
	{
		return urlencode("//{$this->request->hostname}{$this->request->url}");
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
		
		if (!$mode)
		{
			/* Возврат результата проверки. Ручная обработка в вызывающем скрипте */
			return $this->is_registered;
		}

		if ($mode == 'deny')
		{
			/* Запрет просмотра страницы */
			http_response_code(401);
			trigger_error(sprintf($this->lang['NEED_LOGIN'], ilink(sprintf('%s?goto=%s', $this->signin_url, $this->get_back_url()))));
		}
		
		if ($mode == 'redirect')
		{
			/* Перенаправление на форму авторизации */
			$this->request->redirect(ilink(sprintf('%s?goto=%s', $this->signin_url, $this->get_back_url())));
		}
	}

	public function open($save_path, $name)
	{
		return true;
	}
	
	public function read($session_id)
	{
		/* Отличительные черты пользователя */
		$this->cookie['k'] = $this->request->cookie("{$this->config['cookie.prefix']}k", '');
		$this->cookie['u'] = $this->request->cookie("{$this->config['cookie.prefix']}u", 0);
		$this->session_id  = $this->request->cookie("{$this->config['cookie.prefix']}sid", '');
		
		if (!$this->session_id)
		{
			/* Пользователь зашел впервые, надо создать сессию */
			$this->session_create($session_id);
			
			return $this->data['session_data'];
		}

		$this->data = $this->cookie['u'] ? $this->get_user_session() : $this->get_guest_session();		
		
		/* Сессия не найдена или IP, браузер или прокси отличаются */
		if (empty($this->data) || !$this->is_session_params_valid())
		{
			$this->session_create();
			
			return $this->data['session_data'];
		}
		
		$this->page_prev = $this->data['session_page'];

		if (!$this->is_session_expired())
		{
			/* Обновляем информацию о местонахождении не чаще раза в минуту */
			if ($this->request->time - $this->data['session_time'] > 60 || $this->data['session_page'] != $this->request->url)
			{
				$sql_ary = [];
				$sql_ary['session_time'] = $this->data['session_time'] = $this->request->time;

				if ($this->data['session_domain'] != $this->request->hostname)
				{
					$sql_ary['session_domain'] = $this->data['session_domain'] = (string) $this->request->hostname;
				}
				
				if ($this->data['session_page'] != $this->request->url)
				{
					$sql_ary['session_page'] = $this->data['session_page'] = (string) $this->request->url;
				}
				
				register_shutdown_function([$this, 'session_update'], $sql_ary);
			}

			$this->is_bot = false;
			$this->is_registered = $this->data['user_id'] > 0;

			return $this->data['session_data'];
		}

		$this->session_create();
	
		return $this->data['session_data'];
	}
	
	/**
	* Сохранение массива $_SESSION
	*/
	public function write($session_id, $session_data, $immediate_update = false)
	{
		if ($this->data['session_data'] === $session_data)
		{
			return true;
		}
		
		if ($immediate_update)
		{
			$this->session_update(compact('session_data'), $session_id);
			return true;
		}
		
		register_shutdown_function([$this, 'session_update'], compact('session_data'), $session_id);
		return true;
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
			'FROM'   => 'site_banlist',
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
			if ($row['ban_end'] && $row['ban_end'] < $this->request->time)
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

		$sql = 'DELETE FROM site_sessions_keys WHERE user_id = ?';
		$this->db->query($sql, [$user_id]);

		/* Также удаляем все текущие сессии, кроме используемой */
		$sql_session_id = $user_id == $this->data['user_id'] ? ' AND session_id <> ' . $this->db->check_value($this->session_id) : '';
		
		$sql = 'DELETE FROM site_sessions WHERE user_id = ? :session_id';
		$this->db->query($sql, [$user_id, ':session_id' => $sql_session_id]);

		if (false !== $set_new_key && $user_id === $this->data['user_id'] && $this->cookie['k'])
		{
			/* Создаем новый ключ, если был выполнен автовход */
			$this->set_login_key($user_id);
		}
	}
	
	/**
	* Создание новой сессии
	*
	* @param	int		$user_id	ID пользователя, false или 0 - анонимный
	* @param	bool	$autologin	Автовход, используя cookie
	*/
	public function session_create($session_id = false, $user_id = false, $autologin = false, $set_admin = false, $openid_provider = '')
	{
		$_SESSION = $this->data = [];

		if (!$this->config['autologin.allow'])
		{
			$this->cookie['k'] = $autologin = false;
		}

		if ($this->cookie['k'] && $this->cookie['u'])
		{
			$sql = '
				SELECT
					u.*,
					sk.openid_provider
				FROM
					site_users u
				LEFT JOIN
					site_sessions_keys sk ON (sk.user_id = u.user_id)
				WHERE
					u.user_id = ?
				AND
					sk.key_id = ?';
			$result = $this->db->query($sql, [$this->cookie['u'], md5($this->cookie['k'])]);
			$this->data = $this->db->fetchrow($result);
			$this->db->freeresult($result);
			$bot = false;
			$openid_provider = $this->data['openid_provider'] ?: $openid_provider;
		}
		elseif (false !== $user_id)
		{
			$this->cookie['k'] = '';
			$this->cookie['u'] = $user_id;

			/**
			* Если был передан ID пользователя, то
			* получаем его данные из базы
			*/
			$sql = 'SELECT * FROM site_users WHERE user_id = ?';
			$result = $this->db->query($sql, [$this->cookie['u']]);
			$this->data = $this->db->fetchrow($result);
			$this->db->freeresult($result);
			$bot = false;
		}
		else
		{
			/* А не бот ли к нам заглянул? */
			$bot = $this->check_for_bot();
		}

		if (empty($this->data))
		{
			/**
			* Если массив данных пользователя до сих пор не сформирован, то это анонимный
			* пользатель или бот. Настало время получить его данные из базы.
			*/
			$this->cookie['k'] = '';
			$this->cookie['u'] = 0;

			$this->data = $bot ? $this->get_bot_data($bot) : $this->get_guest_defaults();
		}

		/* Время последнего визита */
		if ($this->data['user_id'] > 0 && !$bot)
		{
			$this->data['session_last_visit'] = isset($this->data['session_time']) && $this->data['session_time'] ? $this->data['session_time'] : (isset($this->data['user_last_visit']) ? $this->data['user_last_visit'] : $this->request->time);
		}
		else
		{
			$this->data['session_last_visit'] = $this->request->time;
		}

		/**
		* Проверка бана
		*/
/*
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
*/

		$this->is_bot = $bot ? true : false;
		$this->is_registered = !$bot && $this->data['user_id'] > 0 ? true : false;

		if ($this->is_bot && $bot == $this->data['user_id'] && $this->data['session_id'])
		{
			if ($this->is_session_params_valid())
			{
				$this->session_id = $this->data['session_id'];

				/* Обновляем информацию о местонахождении не чаще раза в минуту */
				if ($this->request->time - $this->data['session_time'] > 60 || $this->data['session_page'] != $this->request->url)
				{
					$this->data['session_time'] = $this->data['session_last_visit'] = $this->request->time;
					$this->data['session_page'] = $this->request->url;
					
					register_shutdown_function([$this, 'session_update'], [
						'session_last_visit' => $this->request->time,
						'session_time'       => $this->request->time,
						'session_domain'     => $this->request->hostname,
						'session_page'       => $this->request->url,
						'session_referer'    => $this->referer,
					]);

					register_shutdown_function([$this, 'user_update'], [
						'user_session_page' => (string) $this->request->url,
						'user_last_visit'   => (int) $this->data['session_last_visit'],
						'user_ip'           => (string) $this->ip,
					]);
				}

				return true;
			}
			else
			{
				/* Для каждого бота должна остаться только одна сессия */
				$sql = 'DELETE FROM site_sessions WHERE user_id = ?';
				$this->db->query($sql, [$this->data['user_id']]);
			}
		}

		$session_autologin = ($this->cookie['k'] || $autologin) && $this->is_registered ? true : false;
		$set_admin = $set_admin && $this->is_registered ? true : false;

		$sql_ary = [
			'user_id'                 => (int) $this->data['user_id'],
			'openid_provider'         => (string) $openid_provider,
			'session_last_visit'      => (int) $this->data['session_last_visit'],
			'session_start'           => (int) $this->request->time,
			'session_time'            => (int) $this->request->time,
			'session_data'            => '',
			'session_browser'         => (string) trim(substr($this->browser, 0, 149)),
			'session_forwarded_for'   => (string) $this->forwarded_for,
			'session_domain'          => (string) $this->request->hostname,
			'session_page'            => (string) $this->request->url,
			'session_referer'         => (string) $this->referer,
			'session_ip'              => (string) $this->ip,
			'session_autologin'       => (int) $autologin,
			'session_admin'           => (int) $set_admin,
		];
		
		if (false === $session_id)
		{
			session_id(strtolower(make_random_string(32)));
			$this->session_id = session_id();
		}
		else
		{
			$this->session_id = $session_id;
		}
		
		$sql_ary['session_id'] = $this->data['session_id'] = $this->session_id;

		/* Добавляем информацию о сессии в базу */
		$sql = 'INSERT INTO site_sessions ' . $this->db->build_array('INSERT', $sql_ary);
		$this->db->query($sql);

		if ($session_autologin)
		{
			$this->set_login_key(false, false, false, $openid_provider);
		}

		$this->data = array_merge($this->data, $sql_ary);
		
		if ($bot)
		{
			/* Обновление даты последнего визита бота */
			register_shutdown_function([$this, 'user_update'], [
				'user_session_page' => (string) $this->request->url,
				'user_last_visit'   => (int) $this->request->time,
				'user_ip'           => (string) $this->ip,
			]);

			return true;
		}

		$cookie_expire = $this->request->time + ($this->config['autologin.time'] ? 86400 * $this->config['autologin.time'] : 31536000);
		$this->set_cookie('k', $this->cookie['k'], $cookie_expire);
		$this->set_cookie('u', $this->cookie['u'], $cookie_expire);
		
		if (false === $session_id)
		{
			$this->set_cookie('sid', $this->session_id);
		}

		return true;
	}
	
	/**
	* Завершение сессии посетителя
	*/
	public function session_end($new_session = true)
	{
		if ($new_session)
		{
			session_destroy();
			session_start();
			return true;
		}

		$this->destroy($this->session_id);
	}

	/**
	* Обновление данных сессии
	*/
	public function session_update(array $sql_ary, $session_id = false)
	{
		$session_id = $session_id ?: $this->session_id;
		
		$sql = 'UPDATE site_sessions SET :update_ary WHERE session_id = ?';
		$this->db->query($sql, [$session_id, ':update_ary' => $this->db->build_array('UPDATE', $sql_ary)]);
		
		$this->data = $session_id === $this->session_id ? array_merge($this->data, $sql_ary) : $this->data;
	}

	/**
	* Сессия с возможностью входа в админ-центр
	*/
	public function set_admin()
	{
		register_shutdown_function([$this, 'session_update'], ['session_admin' => 1]);
	}

	/**
	* Установка cookies
	*
	* @param int $time Метка времени, до которой cookie будет действительна (0 - в течение сеанса)
	*/
	public function set_cookie($name, $value = false, $expire = 0, $domain = false)
	{
		if (false === $value)
		{
			/* Булевы значения нельзя устанавливать в cookie, false удаляет cookie */
			$value  = '';
			$expire = gmdate(DATE_RFC1123, strtotime('-1 year'));
		}
		else
		{
			$expire = 0 !== $expire ? gmdate(DATE_RFC1123, $expire) : 0;
		}

		$name   = rawurlencode($this->config['cookie.prefix'] . $name);
		$value  = rawurlencode($value);
		$expire = 0 !== $expire ? "Expires={$expire}; " : '';
		$domain = false !== $domain ? $domain : $this->config['cookie.domain'];
		$domain = !$domain || $domain == 'localhost' || $domain == '127.0.0.1' ? '' : "Domain={$domain}; ";
		$secure = $this->config['cookie.secure'] ? 'Secure; ' : '';

		header("Set-Cookie: {$name}={$value}; {$expire}Path={$this->config['cookie.path']}; {$domain}{$secure}HttpOnly", false);
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
		$key = $key ?: $this->cookie['k'] ?: false;

		$key_id = make_random_string(16);

		$sql_ary = [
			'key_id'          => (string) md5($key_id),
			'openid_provider' => (string) $openid_provider,
			'last_ip'         => (string) $this->ip,
			'last_login'      => (int) $this->request->time,
		];

		if ($key)
		{
			$sql = 'UPDATE site_sessions_keys SET :update_ary WHERE user_id = ? AND key_id = ?';
			$params = [$user_id, md5($key), ':update_ary' => $this->db->build_array('UPDATE', $sql_ary)];
		}
		else
		{
			$sql_ary['user_id'] = (int) $user_id;
			$sql = 'INSERT INTO site_sessions_keys ' . $this->db->build_array('INSERT', $sql_ary);
			$params = [];
		}

		$this->db->query($sql, $params);
		$this->cookie['k'] = $key_id;
		
		return false;
	}
	
	protected function check_for_bot()
	{
		$bots = $this->cache->obtain_bots();

		/**
		* Сравнение браузера бота с данными в базе
		* Если что-то совпало, значит бот известен
		*/
		foreach ($bots as $row)
		{
			if ($row['bot_agent'] && preg_match('#' . str_replace('\*', '.*?', preg_quote($row['bot_agent'], '#')) . '#i', $this->browser))
			{
				return (int) $row['user_id'];
			}
		}

		return false;
	}
	
	/**
	* Бот получает одну и ту же сессию
	*/
	protected function get_bot_data($bot_id)
	{
		$sql = '
			SELECT
				u.*,
				s.*
			FROM
				site_users u
			LEFT JOIN
				site_sessions s ON (s.user_id = u.user_id)
			WHERE
				u.user_id = ?';
		$result = $this->db->query($sql, [$bot_id]);
		$row = $this->db->fetchrow($result);
		$this->db->freeresult($result);
		$row['user_id'] = $bot_id;
		
		return $row;
	}
	
	/**
	* Стандартные установки гостя
	*/
	protected function get_guest_defaults()
	{
		return [
			'user_id'     => 0,
			'user_access' => '',
			'user_active' => 0,
			'username'    => 'Guest',
		];
	}

	protected function get_guest_session($session_id = false)
	{
		$session_id = $session_id ?: $this->session_id;
		
		$sql = 'SELECT * FROM site_sessions WHERE session_id = ? AND user_id = 0';
		$result = $this->db->query($sql, [$session_id]);
		$row = $this->db->fetchrow($result);
		$this->db->freeresult($result);

		if (!empty($row))
		{
			return array_merge($row, $this->get_guest_defaults());
		}
		
		return $row;
	}
	
	protected function get_user_session($session_id = false)
	{
		$session_id = $session_id ?: $this->session_id;
		
		$sql = '
			SELECT
				s.*,
				u.*
			FROM
				site_sessions s
			LEFT JOIN
				site_users u ON (u.user_id = s.user_id)
			WHERE
				s.session_id = ?';
		$result = $this->db->query($sql, [$session_id]);
		$row = $this->db->fetchrow($result);
		$this->db->freeresult($result);
		
		return $row;
	}
	
	/**
	* Устарела ли сессия
	*/
	protected function is_session_expired()
	{
		/* Не превышает ли время простоя время жизни сессии */
		if (!$this->data['session_autologin'])
		{
			return $this->data['session_time'] < $this->request->time - (ini_get('session.gc_maxlifetime') + 60);
		}
		
		/**
		* Если используется автовход, то проверяем включен ли он на сайте
		* и не превышает ли максимальное время действия автовхода
		*/
		if (!$this->config['autologin.allow'] || ($this->config['autologin.time'] && $this->data['session_time'] < $this->request->time - (86400 * $this->config['autologin.time']) + 60))
		{
			return true;
		}
		
		return false;
	}
	
	protected function is_session_params_valid()
	{
		/* Проверка в IP только первых двух чисел */
		$ip_check = 2;

		$s_ip = implode('.', array_slice(explode('.', $this->data['session_ip']), 0, $ip_check));
		$u_ip = implode('.', array_slice(explode('.', $this->ip), 0, $ip_check));

		$s_browser = trim(strtolower(substr($this->data['session_browser'], 0, 149)));
		$u_browser = trim(strtolower(substr($this->browser, 0, 149)));

		$s_forwarded_for = substr($this->data['session_forwarded_for'], 0, 254);
		$u_forwarded_for = substr($this->forwarded_for, 0, 254);
		
		return $s_ip === $u_ip && $s_browser === $u_browser && $s_forwarded_for === $u_forwarded_for;
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
