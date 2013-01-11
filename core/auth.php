<?php
/**
* @package fw
* @copyright (c) 2012
*/

namespace fw\core;

/**
* Класс привилегий
*/
class auth
{
	const ACL_NEVER = 0;
	const ACL_YES   = 1;
	const ACL_NO    = -1;
	
	private $acl     = array();
	private $cache   = array();
	private $options = array();

	/**
	* Инициализация привилегий
	*/
	public function init(&$userdata)
	{
		global $cache, $db;

		$this->acl = $this->cache = $this->options = array();

		if( false === $this->options = $cache->_get('src_auth_options') )
		// if( false === false )
		{
			$sql = '
				SELECT
					auth_id,
					auth_var,
					auth_global,
					auth_local
				FROM
					' . AUTH_OPTIONS_TABLE . '
				ORDER BY
					auth_id ASC';
			$result = $db->query($sql);
			$this->options = array();

			while( $row = $db->fetchrow($result) )
			{
				if( $row['auth_global'] )
				{
					$this->options['global'][$row['auth_var']] = $row['auth_id'];
				}

				if( $row['auth_local'] )
				{
					$this->options['local'][$row['auth_var']] = $row['auth_id'];
				}

				$this->options['id'][$row['auth_var']] = $row['auth_id'];
				$this->options['option'][$row['auth_id']] = $row['auth_var'];
			}

			$db->freeresult($result);
			// $this->profiler->log($this->options);
			$cache->_set('src_auth_options', $this->options);
		}

		if( $userdata['user_id'] > 0 && !$userdata['user_access'] )
		{
			$this->acl_cache($userdata);
		}

		// $this->acl_cache($userdata);

		/* Запоминаем права пользователя */
		$this->_fill_acl($userdata['user_access']);
	}

	/**
	* Удаление закэшированных прав доступа
	*/
	public function acl_clear_prefetch($user_id = false)
	{
		global $cache, $db;

		/**
		* Обновление кэша ролей
		*/
		$cache->_delete('src_role_cache');

		$sql = '
			SELECT
				*
			FROM
				' . AUTH_ROLES_DATA_TABLE . '
			ORDER BY
				role_id ASC';
		$result = $db->query($sql);
		$this->role_cache = array();

		while( $row = $db->fetchrow($result) )
		{
			$this->role_cache[$row['role_id']][$row['auth_option_id']] = $row['auth_value'];
		}

		$db->freeresult($result);

		foreach( $this->role_cache as $role_id => $role_options )
		{
			$this->role_cache[$role_id] = serialize($role_options);
		}

		$cache->_set('src_role_cache', $this->role_cache);

		/**
		* Обнуление кэша прав пользователей
		*/
		$where_sql = '';

		if( $user_id !== false )
		{
			$user_id = ( !is_array($user_id) ) ? $user_id = array((int) $user_id) : array_map('intval', $user_id);
			$where_sql = ' WHERE ' . $db->in_set('user_id', $user_id);
		}

		$sql = '
			UPDATE
				' . USERS_TABLE . '
			SET
				user_access = ""' .
			$where_sql;
		$db->query($sql);
	}

	/**
	* Проверка доступа
	* Если запрос начинается с !, то возвращаем обратный результат
	*
	* Если указан $local_id, то поиск будет произведен как по локальным переменным,
	* так и по глобальным
	* Если $local_id не указан, то проверяться будут только глобальные переменные
	*/
	public function acl_get($opt, $local_id = 0)
	{
		$negate = false;

		if( strpos($opt, '!') === 0 )
		{
			$negate = true;
			$opt = substr($opt, 1);
		}

		if( !isset($this->cache[$local_id][$opt]) )
		{
			/**
			* Применяется правило ИЛИ
			* Если у пользователя глобальные права, то локальные тоже будут установлены
			*/
			$this->cache[$local_id][$opt] = false;

			/* Глобальные права */
			if( isset($this->options['global'][$opt]) )
			{
				if( isset($this->acl[0]) )
				{
					$this->cache[$local_id][$opt] = $this->acl[0][$this->options['global'][$opt]];
				}
			}

			/**
			* Локальные права
			* Проверяются только при установленном $local_id
			*/
			if( $local_id != 0 && isset($this->options['local'][$opt]) )
			{
				if( isset($this->acl[$local_id]) && isset($this->acl[$local_id][$this->options['local'][$opt]]) )
				{
					$this->cache[$local_id][$opt] |= $this->acl[$local_id][$this->options['local'][$opt]];
				}
			}
		}

		return ($negate) ? !$this->cache[$local_id][$opt] : $this->cache[$local_id][$opt];
	}

	/**
	* Список прав
	*/
	public function acl_get_list($user_id = false, $opts = false, $local_id = false)
	{
		if( $user_id !== false && !is_array($user_id) && $opts === false && $local_id === false )
		{
			$ary = array($user_id => $this->get_user_acl($user_id));
		}
		else
		{
			$ary = $this->acl_raw_data($user_id, $opts, $local_id);
		}

		$auth_ary = array();

		foreach( $ary as $user_id => $local_ary )
		{
			foreach( $local_ary as $local_id => $auth_var_ary )
			{
				foreach( $auth_var_ary as $auth_var => $auth_value )
				{
					if( $auth_value )
					{
						$auth_ary[$local_id][$auth_var][] = $user_id;
					}
				}
			}
		}

		return $auth_ary;
	}

	/**
	* Проверка сразу нескольких полей доступа
	*/
	public function acl_gets()
	{
		$args     = func_get_args();
		$local_id = array_pop($args);

		if( !is_numeric($local_id) )
		{
			$args[]   = $local_id;
			$local_id = 0;
		}

		// Другая запись: acl_gets(array('a_', 'u_'), $local_id)
		if( is_array($args[0]) )
		{
			$args = $args[0];
		}

		$acl = 0;

		foreach( $args as $opt )
		{
			$acl |= $this->acl_get($opt, $local_id);
		}

		return $acl;
	}

	/**
	* Права группы
	*/
	public function acl_group_raw_data($group_id = false, $opts = false, $local_id = false)
	{
		global $db;

		$sql_group = ( $group_id !== false ) ? ((!is_array($group_id)) ? 'group_id = ' . (int) $group_id : $db->in_set('group_id', array_map('intval', $group_id))) : '';
		$sql_local = ( $local_id !== false ) ? ((!is_array($local_id)) ? 'AND a.local_id = ' . (int) $local_id : 'AND ' . $db->in_set('a.local_id', array_map('intval', $local_id))) : '';

		$sql_opts = '';
		$ary = $sql_ary = array();

		if( $opts !== false )
		{
			$this->build_auth_option_statement('ao.auth_var', $opts, $sql_opts);
		}

		/* Права группы */
		$sql_ary[] = '
			SELECT
				a.group_id,
				a.local_id,
				a.auth_value,
				a.auth_option_id,
				ao.auth_var
			FROM
				' . AUTH_GROUPS_TABLE . ' a,
				' . AUTH_OPTIONS_TABLE . ' ao
			WHERE
				a.auth_role_id = 0
			AND
				a.auth_option_id = ao.auth_id ' .
				(($sql_group) ? 'AND a.' . $sql_group : '') .
				$sql_local . $sql_opts . '
			ORDER BY
				a.local_id,
				ao.auth_var';

		/* Права группы на основе ролей */
		$sql_ary[] = '
			SELECT
				a.group_id,
				a.local_id,
				r.auth_value,
				r.auth_option_id,
				ao.auth_var
			FROM
				' . AUTH_GROUPS_TABLE . ' a,
				' . AUTH_ROLES_DATA_TABLE . ' r,
				' . AUTH_OPTIONS_TABLE . ' ao
			WHERE
				a.auth_role_id = r.role_id
			AND
				r.auth_option_id = ao.auth_id ' .
				(($sql_group) ? 'AND a.' . $sql_group : '') .
				$sql_local . $sql_opts . '
			ORDER BY
				a.local_id,
				ao.auth_var';

		foreach( $sql_ary as $sql )
		{
			$result = $db->query($sql);

			while( $row = $db->fetchrow($result) )
			{
				$ary[$row['group_id']][$row['local_id']][$row['auth_var']] = $row['auth_value'];
			}

			$db->freeresult($result);
		}

		return $ary;
	}

	public function acl_raw_data($user_id = false, $opts = false, $local_id = false)
	{
		global $db;

		$sql_user = ( $user_id !== false ) ? ((!is_array($user_id)) ? 'user_id = ' . (int) $user_id : $db->in_set('user_id', array_map('intval', $user_id))) : '';
		$sql_local = ( $local_id !== false ) ? ((!is_array($local_id)) ? 'AND a.local_id = ' . (int) $local_id : 'AND ' . $db->in_set('a.local_id', array_map('intval', $local_id))) : '';

		$sql_opts = $sql_opts_select = $sql_opts_from = '';
		$ary = array();

		if( $opts !== false )
		{
			$sql_opts_select = ', ao.auth_var';
			$sql_opts_from = ', ' . AUTH_OPTIONS_TABLE . ' ao';
			$this->build_auth_option_statement('ao.auth_var', $opts, $sql_opts);
		}

		$sql_ary = array();

		/* Права пользователя */
		$sql_ary[] = '
			SELECT
				a.user_id,
				a.local_id,
				a.auth_value,
				a.auth_option_id
				' . $sql_opts_select . '
			FROM
				' . AUTH_USERS_TABLE . ' a
				' . $sql_opts_from . '
			WHERE
				a.auth_role_id = 0 ' .
				(($sql_opts_from) ? 'AND a.auth_option_id = ao.auth_id ' : '') .
				(($sql_user) ? 'AND a.' . $sql_user : '') .
				$sql_local . $sql_opts;

		/* Права пользователя на основе ролей */
		$sql_ary[] = '
			SELECT
				a.user_id,
				a.local_id,
				r.auth_option_id,
				r.auth_value
				' . $sql_opts_select . '
			FROM
				' . AUTH_USERS_TABLE . ' a,
				' . AUTH_ROLES_DATA_TABLE . ' r,
				' . $sql_opts_from . '
			WHERE
				a.auth_role_id = r.role_id ' .
			(($sql_opts_from) ? 'AND r.auth_options_id = ao.auth_id ' : '') .
			(($sql_user) ? 'AND a.' . $sql_user : '') .
			$sql_local . $sql_opts;

		foreach( $sql_ary as $sql )
		{
			$result = $db->query($sql);

			while( $row = $db->fetchrow($result) )
			{
				$option = ( $sql_opts_select ) ? $row['auth_var'] : $this->options['option'][$row['auth_option_id']];
				$ary[$row['user_id']][$row['local_id']][$option] = $row['auth_value'];
			}

			$db->freeresult($result);
		}

		$sql_ary = array();

		/* Права группы */
		$sql_ary[] = '
			SELECT
				ug.user_id,
				a.local_id,
				a.auth_value,
				a.auth_option_id
				' . $sql_opts_select . '
			FROM
				' . AUTH_GROUPS_TABLE . ' a,
				' . USERS_GROUP_TABLE . ' ug,
				' . GROUPS_TABLE . ' g
				' . $sql_opts_from . '
			WHERE
				a.auth_role_id = 0 ' .
				(($sql_opts_from) ? 'AND a.auth_option_id = ao.auth_id ' : '') . '
			AND
				a.group = ug.group_id
			AND
				g.group_id = ug.group_id
			AND
				ug.user_pending = 0
			AND NOT
				(ug.group_leader = 1 AND g.group_skip_auth = 1)
				' . (($sql_user) ? 'AND ug.' . $sql_user : '') .
				$sql_local . $sql_opts;

		/* Права группы на основе ролей */
		$sql_ary[] = '
			SELECT
				ug.user_id,
				a.local_id,
				r.auth_value,
				r.auth_option_id
				' . $sql_opts_select . '
			FROM
				' . AUTH_GROUPS_TABLE . ' a,
				' . USERS_GROUP_TABLE . ' ug,
				' . GROUPS_TABLE . ' g,
				' . AUTH_ROLES_DATA_TABLE . ' r
				' . $sql_opts_from . '
			WHERE
				a.auth_role_id = r.role_id ' .
				(($sql_opts_from) ? 'AND r.auth_option_id = ao.auth_id ' : '') . '
			AND
				a.group_id = ug.group_id
			AND
				g.group_id = ug.group_id
			AND
				ug.user_pending = 0
			AND NOT
				(ug.group_leader = 1 AND g.group_skip_auth = 1)
				' . (($sql_user) ? 'AND ug.' . $sql_user : '') .
				$sql_local . $sql_opts;

		foreach( $sql_ary as $sql )
		{
			$result = $db->query($sql);

			while( $row = $db->fetchrow($result) )
			{
				$option = ( $sql_opts_select ) ? $row['auth_var'] : $this->options['option'][$row['auth_option_id']];

				if( !isset($ary[$row['user_id']][$row['local_id']][$option]) || (isset($ary[$row['user_id']][$row['local_id']][$option]) && $ary[$row['user_id']][$row['local_id']][$option] != self::ACL_NEVER) )
				{
					$ary[$row['user_id']][$row['local_id']][$option] = $row['auth_value'];

					/* Убираем флаг, если встретили ACL_NEVER */
					if( $row['auth_value'] == self::ACL_NEVER )
					{
						$flag = substr($option, 0, strpos($option, '_') + 1);

						if( isset($ary[$row['user_id']][$row['local_id']][$flag]) && $ary[$row['user_id']][$row['local_id']][$flag] == self::ACL_YES )
						{
							unset($ary[$row['user_id']][$row['local_id']][$flag]);
						}
					}
				}
			}

			$db->freeresult($result);
		}

		return $ary;
	}

	/**
	* Роли, назначенные группе или пользователю
	*/
	public function acl_role_data($user_type, $role_type, $ug_id = false, $local_id = false)
	{
		global $db;

		$roles = array();

		$sql_id = ( $user_type == 'user' ) ? 'user_id' : 'group_id';
		$sql_ug = ( $ug_id !== false ) ? ((!is_array($ug_id)) ? "AND a.$sql_id = $ug_id" : 'AND ' . $db->in_set("a.$sql_id", $ug_id)) : '';
		$sql_local = ( $local_id !== false ) ? ((!is_array($local_id)) ? "AND a.local_id = $local_id" : 'AND ' . $db->in_set('a.local_id', $local_id)) : '';

		$sql = '
			SELECT
				a.auth_role_id,
				a.' . $sql_id . ',
				a.local_id
			FROM
				' . (($user_type == 'user') ? AUTH_GROUPS_TABLE : AUTH_USERS_TABLE) . ' a,
				' . AUTH_ROLES_TABLE . ' r
			WHERE
				a.auth_role_id = r.role_id
			AND
				r.role_type = ' . $db->check_value($role_type) .
				$sql_ug . $sql_local . '
			ORDER BY
				r.role_sort ASC';
		$result = $db->query($sql);

		while( $row = $db->fetchrow($result) )
		{
			$roles[$row[$sql_id]][$row['local_id']] = $row['auth_role_id'];
		}

		$db->freeresult($result);

		// $this->profiler->log($roles);

		return $roles;
	}

	/**
	* Права пользователя
	*/
	public function acl_user_raw_data($user_id = false, $opts = false, $local_id = false)
	{
		global $db;

		$sql_user = ( $user_id !== false ) ? ((!is_array($user_id)) ? 'user_id = ' . (int) $user_id : $db->in_set('user_id', array_map('intval', $user_id))) : '';
		$sql_local = ( $local_id !== false ) ? ((!is_array($local_id)) ? 'AND a.local_id = ' . (int) $local_id : 'AND ' . $db->in_set('a.local_id', array_map('intval', $local_id))) : '';

		$sql_opts = '';
		$ary = $sql_ary = array();

		if( $opts !== false )
		{
			$this->build_auth_option_statement('ao.auth_var', $opts, $sql_opts);
		}

		/* Права пользователя */
		$sql_ary[] = '
			SELECT
				a.user_id,
				a.local_id,
				a.auth_value,
				a.auth_option_id,
				ao.auth_var
			FROM
				' . AUTH_USERS_TABLE . ' a,
				' . AUTH_OPTIONS_TABLE . ' ao
			WHERE
				a.auth_role_id = 0
			AND
				a.auth_option_id = ao.auth_id ' .
				(($sql_user) ? 'AND a.' . $sql_user : '') .
				$sql_local . $sql_opts . '
			ORDER BY
				a.local_id,
				ao.auth_var';

		/* Права пользователя на основе ролей */
		$sql_ary[] = '
			SELECT
				a.user_id,
				a.local_id,
				r.auth_option_id,
				r.auth_value,
				ao.auth_var
			FROM
				' . AUTH_USERS_TABLE . ' a,
				' . AUTH_ROLES_DATA_TABLE . ' r,
				' . AUTH_OPTIONS_TABLE . ' ao
			WHERE
				a.auth_role_id = r.role_id
			AND
				r.auth_option_id = ao.auth_id ' .
				(($sql_user) ? 'AND a.' . $sql_user : '') .
				$sql_local . $sql_opts . '
			ORDER BY
				a.local_id,
				ao.auth_var';

		foreach( $sql_ary as $sql )
		{
			$result = $db->query($sql);

			while( $row = $db->fetchrow($result) )
			{
				$ary[$row['user_id']][$row['local_id']][$row['auth_var']] = $row['auth_value'];
			}

			$db->freeresult($result);
		}

		return $ary;
	}

	/**
	* Авторизация
	* TODO
	*/
	public function login($username, $password, $autologin = false, $viewonline = 1, $admin = 0)
	{
		global $db, $user;
		
		$login = $user->login($username, $password);

		if( $login['status'] == 'OK' )
		{
			$old_session_id = $user->session_id;

			if( $admin )
			{
				$cookie_expire = $user->ctime - 31536000;
				$user->set_cookie('u', '', $cookie_expire);
				$user->set_cookie('sid', '', $cookie_expire);
				unset($cookie_expire);

				$user->session_id = '';
			}

			if( true === $result = $user->session_create($login['user_row']['user_id'], $autologin, $admin, $viewonline) )
			{
				/* Реаутентификация, удаляем прежнюю сессию */
				if( $admin )
				{
					$sql = '
						DELETE
						FROM
							' . SESSIONS_TABLE . '
						WHERE
							session_id = ' . $db->check_value($old_session_id) . '
						AND
							user_id = ' . $db->check_value($login['user_row']['user_id']);
					$db->query($sql);
				}

				return $login;
			}

			return array(
				'message'  => $result,
				'status'   => 'LOGIN_BREAK',
				'user_row' => $login['user_row']
			);
		}

		return $login;
	}

	/**
	* Права пользователя
	*/
	private function _fill_acl($user_access)
	{
		$this->acl = ( is_array($user_access) ) ? $user_access : unserialize(base64_decode($user_access));
		// $this->profiler->log($this->acl);
	}

	private function _set_group_hold_ary(&$ary, $option_id, $value)
	{
		if( !isset($ary[$option_id]) || (isset($ary[$option_id]) && $ary[$option_id] != 0) )
		{
			$ary[$option_id] = $value;
		}
	}

	/**
	* Кэширование прав пользователя
	*/
	private function acl_cache(&$userdata)
	{
		global $db;

		/* Сбрасываем доступ */
		$userdata['user_access'] = '';

		$ary = $this->get_user_acl($userdata['user_id']);

		/* Индекс 0 содержит глобальные права */

		/* Создатель обладает всеми правами администратора */
		if( $userdata['user_id'] == 1 )
		{
			foreach( $this->options['global'] as $opt => $id )
			{
				if( strpos($opt, 'a_') === 0 )
				{
					$ary[0][$this->options['id'][$opt]] = 1;
				}
			}
		}

		$userdata['user_access'] = $ary;

		$sql = '
			UPDATE
				' . USERS_TABLE . '
			SET
				user_access = ' . $db->check_value(base64_encode(serialize($userdata['user_access']))) . '
			WHERE
				user_id = ' . $db->check_value($userdata['user_id']);
		$db->query($sql);
	}

	/**
	* Заполняем выражение auth_var для дальнейшего использования
	* в запросах на основе заданных настроек
	*/
	private function build_auth_option_statement($key, $auth_options, &$sql_opts)
	{
		global $db;

		if( !is_array($auth_options) )
		{
			if( strpos($auth_options, '%') !== false )
			{
				$sql_opts = "AND $key " . $db->like_expression(str_replace('%', chr(0) . '%', $auth_options));
			}
			else
			{
				$sql_opts = "AND $key = " . $db->check_value($auth_options);
			}
		}
		else
		{
			$is_like_expression = false;

			foreach( $auth_options as $option )
			{
				if( strpos($option, '%') !== false )
				{
					$is_like_expression = true;
				}
			}

			if( !$is_like_expression )
			{
				$sql_opts = 'AND ' . $db->in_set($key, $auth_options);
			}
			else
			{
				$sql = array();

				foreach( $auth_options as $option )
				{
					if( strpos($option, '%') !== false )
					{
						$sql[] = $key . ' ' . $db->like_expression(str_replace('%', chr(0) . '%', $option));
					}
					else
					{
						$sql[] = $key . " = " . $db->check_value($option);
					}
				}

				$sql_opts = 'AND (' . implode(' OR ', $sql) . ')';
			}
		}
	}

	/**
	* Права пользователя для заполнения user_access
	* Возвращает те же данные, что acl_user_raw_data,
	* но без user_id в качестве первого ключа массива
	*/
	private function get_user_acl($user_id)
	{
		global $cache, $db;

		if( false === $role_cache = $cache->_get('src_role_cache') )
		// if( false === false )
		{
			$role_cache = array();

			$sql = '
				SELECT
					*
				FROM
					' . AUTH_ROLES_DATA_TABLE . '
				ORDER BY
					role_id ASC';
			$result = $db->query($sql);

			while( $row = $db->fetchrow($result) )
			{
				$role_cache[$row['role_id']][$row['auth_option_id']] = (int) $row['auth_value'];
			}

			$db->freeresult($result);

			foreach( $role_cache as $role_id => $role_options )
			{
				$role_cache[$role_id] = serialize($role_options);
			}

			$cache->_set('src_role_cache', $role_cache);
		}

		$ary = array();

		/**
		* Права пользователя
		*/
		$sql = '
			SELECT
				local_id,
				auth_option_id,
				auth_role_id,
				auth_value
			FROM
				' . AUTH_USERS_TABLE . '
			WHERE
				user_id = ' . $db->check_value($user_id);
		$db->query($sql);

		while( $row = $db->fetchrow() )
		{
			if( $row['auth_role_id'] )
			{
				$ary[$row['local_id']] = ( empty($ary[$row['local_id']]) ) ? unserialize($role_cache[$row['auth_role_id']]) : $ary[$row['local_id']] + unserialize($role_cache[$row['auth_role_id']]);
			}
			else
			{
				$ary[$row['local_id']][$row['auth_option_id']] = $row['auth_value'];
			}
		}

		$db->freeresult();

		/**
		* Права от группы
		*/
		$sql = '
			SELECT
				a.local_id,
				a.auth_option_id,
				a.auth_role_id,
				a.auth_value
			FROM
				' . AUTH_GROUPS_TABLE . ' a
			LEFT JOIN
				' . USER_GROUPS_TABLE . ' ug ON (ug.group_id = a.group_id)
			LEFT JOIN
				' . GROUPS_TABLE . ' g ON (g.group_id = ug.group_id)
			WHERE
				ug.user_pending = 0
			AND NOT
				(ug.group_leader = 1 AND g.group_skip_auth = 1)
			AND
				ug.user_id = ' . $db->check_value($user_id);
		$db->query($sql);

		while( $row = $db->fetchrow() )
		{
			if( !$row['auth_role_id'] )
			{
				$this->_set_group_hold_ary($ary[$row['local_id']], $row['auth_option_id'], $row['auth_value']);
			}
			elseif( !empty($role_cache[$row['auth_role_id']]) )
			{
				foreach( unserialize($role_cache[$row['auth_role_id']]) as $option_id => $value )
				{
					$this->_set_group_hold_ary($ary[$row['local_id']], $option_id, $value);
				}
			}
		}

		$db->freeresult();

		return $ary;
	}

	function check($var, $sub = false, $num = false)
	{
		return $this->acl_get($var);
	}

	/**
	* Получаем все типы привилегий
	*/
	function get_all()
	{
		global $cache, $db;

		if( false === $this->options = $cache->_get('src_auth_options') )
		{
			/**
			* Все возможные привилегии
			*/
			$sql = '
				SELECT
					*
				FROM
					' . AUTH_OPTIONS_TABLE;
			$db->query($sql);

			while( $row = $db->fetchrow() )
			{
				$this->options[$row['auth_id']] = array(
					'name'    => $row['auth_name'],
					'sub'     => $row['auth_sub'],
					'var'     => $row['auth_var'],
					'global'  => $row['auth_global'],
					'local'   => $row['auth_local'],
					'default' => $row['auth_default']
				);
			}

			$db->freeresult();

			/* Записываем в кэш */
			$cache->_set('src_auth_options', $this->options);
		}

		if( false === $this->roles = $cache->_get('src_auth_roles') )
		{
			/**
			* Все возможные роли
			*/
			$sql = '
				SELECT
					*
				FROM
					' . AUTH_ROLES_TABLE;
			$db->query($sql);

			while( $row = $db->fetchrow() )
			{
				$this->roles[$row['role_id']] = array(
					'name'        => $row['role_name'],
					'description' => $row['role_description'],
					'sort'        => $row['role_sort']
				);
			}

			$db->freeresult();

			/* Записываем в кэш */
			$cache->_set('src_auth_roles', $this->roles);
		}
	}

	/**
	* Обновляем привилегии выбранного пользователя
	*
	* @param	int		$user_id	ID пользователя
	*/
	function update($user_id)
	{
		global $db;

		/* Получаем все типы привилегий */
		$this->get_all();

		$userdata = array();

		/**
		* Если данные не указаны, то используем текущего пользователя
		*/
		if( $user_id === false )
		{
			global $user;

			$need_update = true;
			$user_id = $user['user_id'];
		}
		else
		{
			$need_update = false;
		}

		/**
		* Получаем список установленных привилегий из базы
		*/
		$sql = '
			SELECT
				*
			FROM
				' . AUTH_USERS_TABLE . '
			WHERE
				user_id = ' . $db->check_value($user_id);
		$result = $db->query($sql);

		while( $row = $db->fetchrow($result) )
		{
			if( $row['auth_option_id'] )
			{
				/**
				* Если установлена определённая привилегия
				*/
				if( !isset($this->options[$row['auth_option_id']]) )
				{
					/**
					* Если установленная привилегия не существует в базе, то пропускаем её
					*/
					continue;
				}

				if( $row['auth_local_id'] && $this->options[$row['auth_option_id']]['local'] )
				{
					/**
					* Если указана локальная привилегия и она может быть локальной, то устанавливаем её
					*/
					$userdata[$this->options[$row['auth_option_id']]['sub']][$row['auth_local_id']][$this->options[$row['auth_option_id']]['var']] = $row['auth_value'];
				}
				elseif( !$this->options[$row['auth_option_id']]['sub'] )
				{
					$userdata[$this->options[$row['auth_option_id']]['var']] = $row['auth_value'];
				}

			}
			elseif( $row['auth_role_id'] )
			{
				/**
				* Если установлена группа привелегий
				*/
				if( !isset($this->roles[$row['role_id']]) )
				{
					/**
					* Если установленная роль не существует в базе, то пропускаем её
					*/
					continue;
				}

				/**
				* Получаем привилегии выбранной роли
				*/
				$sql = '
					SELECT
						*
					FROM
						' . AUTH_ROLES_DATA_TABLE . '
					WHERE
						role_id = ' . $db->check_value($row['role_id']);
				$result2 = $db->query($sql);

				while( $row2 = $db->fetchrow($result2) )
				{
					if( !isset($this->options[$row2['auth_option_id']]) )
					{
						/**
						* Если установленная привилегия не существует в базе, то пропускаем её
						*/
						continue;
					}

					$userdata[$this->options[$row['auth_option_id']]['sub']]['.'][$this->options[$row['auth_option_id']]['var']] = $row2['auth_value'];
				}

				$db->freeresult($result2);
			}
		}

		$db->freeresult($result);

		/**
		* Если данные не указаны, то используем текущего пользователя
		*/
		if( $need_update )
		{
			$this->data = $userdata;
		}

		/**
		* Записываем привилегии в БД
		*/
		$sql = '
			UPDATE
				' . USERS_TABLE . '
			SET
				user_access = ' . $db->check_value(base64_encode(serialize($userdata))) . '
			WHERE
				user_id = ' . $db->check_value($user_id);
		$db->query($sql);
	}

	/**
	* Запись привилегий в БД
	*
	* @param	int		$user_id			ID пользователя
	* @param	int		$auth_local_id		ID локального элемента
	* @param	int		$auth_optiond_id	ID привилегии
	* @param	int		$auth_role_id		ID роли, содержащей несколько привилегий
	* @param	int		$auth_value			Значение привилегии
	*/
	function write($user_id, $auth_local_id, $auth_option_id, $auth_role_id, $auth_value)
	{
		global $db;

		/**
		* Нельзя одновременно задать привилегию и роль
		*/
		if( $auth_option_id )
		{
			$auth_role_id = 0;
		}
		elseif( $auth_role_id )
		{
			$auth_option_id = 0;
		}

		/**
		* Массив данных sql запроса
		*/
		$sql_array = array(
			'auth_option_id' => $auth_option_id,
			'auth_role_id'   => $auth_role_id,
			'auth_value'     => $auth_value
		);

		/**
		* Обновляем данные
		*/
		$sql = '
			UPDATE
				'. AUTH_USERS_TABLE . '
			SET
				' . $db->build_array('UPDATE', $sql_array) . '
			WHERE
				user_id = ' . $db->check_value($user_id);
		$db->query($sql);

		if( !$db->affected_rows() )
		{
			/**
			* Если данные не обновились, значит либо они уже существуют в данном виде, либо их вообще нет
			* Проверяем существование
			*/
			$sql = '
				SELECT
					user_id
				FROM
					' . AUTH_USERS_TABLE . '
				WHERE
					user_id = ' . $db->check_value($user_id) . '
				AND
					auth_option_id = ' . $db->check_value($auth_option_id) . '
				AND
					auth_role_id = ' . $db->check_value($auth_role_id) . '
				AND
					auth_value = ' . $db->check_value($auth_value);
			$result = $db->query($sql);
			$db->freeresult($result);

			if( !$db->affected_rows() )
			{
				/**
				* Так ничего и не нашли
				* Добавляем данные
				*/
				$sql_array['user_id'] = $user_id;

				$sql = 'INSERT INTO ' . AUTH_USERS_TABLE . ' ' . $db->build_array('INSERT', $sql_array);
				$db->query($sql);
			}
		}
	}
}
