<?php namespace fw\core;

/**
* Класс привилегий
*/
class auth
{
	const ACL_NEVER = 0;
	const ACL_YES   = 1;
	const ACL_NO    = -1;
	
	protected $cache;
	protected $db;
	protected $user;
	
	private $acl     = [];
	private $auth    = [];
	private $options = [];
	
	function __construct($cache, $db, $user)
	{
		$this->cache = $cache;
		$this->db    = $db;
		$this->user  = $user;
	}

	/**
	* Инициализация привилегий
	*/
	public function init(&$userdata)
	{
		$this->acl = $this->auth = $this->options = [];

		if (false === $this->options = $this->cache->get_shared('auth_options')) {
		// if (false === false) {
			$sql = '
				SELECT
					auth_id,
					auth_var,
					auth_global,
					auth_local
				FROM
					site_auth_options
				ORDER BY
					auth_id ASC';
			$result = $this->db->query($sql);
			$this->options = [];

			while ($row = $this->db->fetchrow($result)) {
				if ($row['auth_global']) {
					$this->options['global'][$row['auth_var']] = $row['auth_id'];
				}

				if ($row['auth_local']) {
					$this->options['local'][$row['auth_var']] = $row['auth_id'];
				}

				$this->options['id'][$row['auth_var']] = $row['auth_id'];
				$this->options['option'][$row['auth_id']] = $row['auth_var'];
			}

			$this->db->freeresult($result);
			// $this->profiler->log($this->options);
			$this->cache->set_shared('auth_options', $this->options);
		}

		if ($userdata['user_id'] > 0 && !$userdata['user_access']) {
			$this->acl_cache($userdata);
		}

		// $this->acl_cache($userdata);

		/* Запоминаем права пользователя */
		$this->_fill_acl($userdata['user_access']);
		
		return $this;
	}

	/**
	* Удаление закэшированных прав доступа
	*/
	public function acl_clear_prefetch($user_id = false)
	{
		/**
		* Обновление кэша ролей
		*/
		$this->cache->delete_shared('role_cache');

		$sql = 'SELECT * FROM site_auth_roles_data ORDER BY role_id ASC';
		$result = $this->db->query($sql);
		$this->role_cache = [];

		while ($row = $this->db->fetchrow($result)) {
			$this->role_cache[$row['role_id']][$row['auth_option_id']] = $row['auth_value'];
		}

		$this->db->freeresult($result);

		foreach ($this->role_cache as $role_id => $role_options) {
			$this->role_cache[$role_id] = serialize($role_options);
		}

		$this->cache->set_shared('role_cache', $this->role_cache);

		/**
		* Обнуление кэша прав пользователей
		*/
		$where_sql = '';

		if (false !== $user_id) {
			$user_id = !is_array($user_id) ? $user_id = [(int) $user_id] : array_map('intval', $user_id);
			$where_sql = 'WHERE ' . $this->db->in_set('user_id', $user_id);
		}

		$sql = 'UPDATE site_users SET user_access = "" :where_sql';
		$this->db->query($sql, [':where_sql' => $where_sql]);
	}

	/**
	* Проверка доступа
	* Если запрос начинается с !, то возвращаем обратный результат
	*
	* Если указан $local_id, то поиск будет произведен как по локальным переменным,
	* так и по глобальным
	* Если $local_id не указан, то проверяться будут только глобальные переменные
	*/
	public function acl_get($opt, $local_id = 0) {
		$negate = false;

		if (0 === strpos($opt, '!')) {
			$negate = true;
			$opt = substr($opt, 1);
		}

		if (!isset($this->auth[$local_id][$opt])) {
			/**
			* Применяется правило ИЛИ
			* Если у пользователя глобальные права, то локальные тоже будут установлены
			*/
			$this->auth[$local_id][$opt] = false;

			/* Глобальные права */
			if (isset($this->options['global'][$opt])) {
				if (isset($this->acl[0])) {
					$this->auth[$local_id][$opt] = $this->acl[0][$this->options['global'][$opt]];
				}
			}

			/**
			* Локальные права
			* Проверяются только при установленном $local_id
			*/
			if ($local_id != 0 && isset($this->options['local'][$opt])) {
				if (isset($this->acl[$local_id]) && isset($this->acl[$local_id][$this->options['local'][$opt]])) {
					$this->auth[$local_id][$opt] |= $this->acl[$local_id][$this->options['local'][$opt]];
				}
			}
		}

		return $negate ? !$this->auth[$local_id][$opt] : $this->auth[$local_id][$opt];
	}

	/**
	* Список прав
	*/
	public function acl_get_list($user_id = false, $opts = false, $local_id = false)
	{
		if (false !== $user_id && !is_array($user_id) && false === $opts && false === $local_id) {
			$ary = [$user_id => $this->get_user_acl($user_id)];
		} else {
			$ary = $this->acl_raw_data($user_id, $opts, $local_id);
		}

		$auth_ary = [];

		foreach ($ary as $user_id => $local_ary) {
			foreach ($local_ary as $local_id => $auth_var_ary) {
				foreach ($auth_var_ary as $auth_var => $auth_value) {
					if ($auth_value) {
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

		if (!is_numeric($local_id)) {
			$args[]   = $local_id;
			$local_id = 0;
		}

		// Другая запись: acl_gets(['a_', 'u_'], $local_id)
		if (is_array($args[0])) {
			$args = $args[0];
		}

		$acl = 0;

		foreach ($args as $opt) {
			$acl |= $this->acl_get($opt, $local_id);
		}

		return $acl;
	}

	/**
	* Права группы
	*/
	public function acl_group_raw_data($group_id = false, $opts = false, $local_id = false)
	{
		$sql_group = false !== $group_id ? (!is_array($group_id) ? 'group_id = ' . (int) $group_id : $this->db->in_set('group_id', array_map('intval', $group_id))) : '';
		$sql_local = false !== $local_id ? (!is_array($local_id) ? 'AND a.local_id = ' . (int) $local_id : 'AND ' . $this->db->in_set('a.local_id', array_map('intval', $local_id))) : '';

		$sql_opts = '';
		$ary = $sql_ary = [];

		if (false !== $opts) {
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
				site_auth_groups a,
				site_auth_options ao
			WHERE
				a.auth_role_id = 0
			AND
				a.auth_option_id = ao.auth_id ' .
				($sql_group ? 'AND a.' . $sql_group : '') .
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
				site_auth_groups a,
				site_auth_roles_data r,
				site_auth_options ao
			WHERE
				a.auth_role_id = r.role_id
			AND
				r.auth_option_id = ao.auth_id ' .
				($sql_group ? 'AND a.' . $sql_group : '') .
				$sql_local . $sql_opts . '
			ORDER BY
				a.local_id,
				ao.auth_var';

		foreach ($sql_ary as $sql) {
			$result = $this->db->query($sql);

			while ($row = $this->db->fetchrow($result)) {
				$ary[$row['group_id']][$row['local_id']][$row['auth_var']] = $row['auth_value'];
			}

			$this->db->freeresult($result);
		}

		return $ary;
	}

	public function acl_raw_data($user_id = false, $opts = false, $local_id = false)
	{
		$sql_user = false !== $user_id ? (!is_array($user_id) ? 'user_id = ' . (int) $user_id : $this->db->in_set('user_id', array_map('intval', $user_id))) : '';
		$sql_local = false !== $local_id ? (!is_array($local_id) ? 'AND a.local_id = ' . (int) $local_id : 'AND ' . $this->db->in_set('a.local_id', array_map('intval', $local_id))) : '';

		$sql_opts = $sql_opts_select = $sql_opts_from = '';
		$ary = [];

		if (false !== $opts) {
			$sql_opts_select = ', ao.auth_var';
			$sql_opts_from = ', site_auth_options ao';
			$this->build_auth_option_statement('ao.auth_var', $opts, $sql_opts);
		}

		$sql_ary = [];

		/* Права пользователя */
		$sql_ary[] = '
			SELECT
				a.user_id,
				a.local_id,
				a.auth_value,
				a.auth_option_id
				' . $sql_opts_select . '
			FROM
				site_auth_users a
				' . $sql_opts_from . '
			WHERE
				a.auth_role_id = 0 ' .
				($sql_opts_from ? 'AND a.auth_option_id = ao.auth_id ' : '') .
				($sql_user ? 'AND a.' . $sql_user : '') .
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
				site_auth_users a,
				site_auth_roles_data r,
				' . $sql_opts_from . '
			WHERE
				a.auth_role_id = r.role_id ' .
			($sql_opts_from ? 'AND r.auth_options_id = ao.auth_id ' : '') .
			($sql_user ? 'AND a.' . $sql_user : '') .
			$sql_local . $sql_opts;

		foreach ($sql_ary as $sql) {
			$result = $this->db->query($sql);

			while ($row = $this->db->fetchrow($result)) {
				$option = $sql_opts_select ? $row['auth_var'] : $this->options['option'][$row['auth_option_id']];
				$ary[$row['user_id']][$row['local_id']][$option] = $row['auth_value'];
			}

			$this->db->freeresult($result);
		}

		$sql_ary = [];

		/* Права группы */
		$sql_ary[] = '
			SELECT
				ug.user_id,
				a.local_id,
				a.auth_value,
				a.auth_option_id
				' . $sql_opts_select . '
			FROM
				site_auth_groups a,
				site_user_groups ug,
				site_groups g
				' . $sql_opts_from . '
			WHERE
				a.auth_role_id = 0 ' .
				($sql_opts_from ? 'AND a.auth_option_id = ao.auth_id ' : '') . '
			AND
				a.group = ug.group_id
			AND
				g.group_id = ug.group_id
			AND
				ug.user_pending = 0
			AND NOT
				(ug.group_leader = 1 AND g.group_skip_auth = 1)
				' . ($sql_user ? 'AND ug.' . $sql_user : '') .
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
				site_auth_groups a,
				site_user_groups ug,
				site_groups g,
				site_auth_roles_data r
				' . $sql_opts_from . '
			WHERE
				a.auth_role_id = r.role_id ' .
				($sql_opts_from ? 'AND r.auth_option_id = ao.auth_id ' : '') . '
			AND
				a.group_id = ug.group_id
			AND
				g.group_id = ug.group_id
			AND
				ug.user_pending = 0
			AND NOT
				(ug.group_leader = 1 AND g.group_skip_auth = 1)
				' . ($sql_user ? 'AND ug.' . $sql_user : '') .
				$sql_local . $sql_opts;

		foreach ($sql_ary as $sql) {
			$result = $this->db->query($sql);

			while ($row = $this->db->fetchrow($result)) {
				$option = $sql_opts_select ? $row['auth_var'] : $this->options['option'][$row['auth_option_id']];

				if (!isset($ary[$row['user_id']][$row['local_id']][$option]) || (isset($ary[$row['user_id']][$row['local_id']][$option]) && $ary[$row['user_id']][$row['local_id']][$option] != self::ACL_NEVER)) {
					$ary[$row['user_id']][$row['local_id']][$option] = $row['auth_value'];

					/* Убираем флаг, если встретили ACL_NEVER */
					if ($row['auth_value'] == self::ACL_NEVER) {
						$flag = substr($option, 0, strpos($option, '_') + 1);

						if (isset($ary[$row['user_id']][$row['local_id']][$flag]) && $ary[$row['user_id']][$row['local_id']][$flag] == self::ACL_YES) {
							unset($ary[$row['user_id']][$row['local_id']][$flag]);
						}
					}
				}
			}

			$this->db->freeresult($result);
		}

		return $ary;
	}

	/**
	* Роли, назначенные группе или пользователю
	*/
	public function acl_role_data($user_type, $role_type, $ug_id = false, $local_id = false)
	{
		$roles = [];

		$sql_id = $user_type == 'user' ? 'user_id' : 'group_id';
		$sql_ug = false !== $ug_id ? (!is_array($ug_id) ? "AND a.$sql_id = $ug_id" : 'AND ' . $this->db->in_set("a.$sql_id", $ug_id)) : '';
		$sql_local = false !== $local_id ? (!is_array($local_id) ? "AND a.local_id = $local_id" : 'AND ' . $this->db->in_set('a.local_id', $local_id)) : '';

		$sql = '
			SELECT
				a.auth_role_id,
				a.' . $sql_id . ',
				a.local_id
			FROM
				' . ($user_type == 'user' ? 'site_auth_groups' : 'site_auth_users') . ' a,
				site_auth_roles r
			WHERE
				a.auth_role_id = r.role_id
			AND
				r.role_type = ' . $this->db->check_value($role_type) .
				$sql_ug . $sql_local . '
			ORDER BY
				r.role_sort ASC';
		$result = $this->db->query($sql);

		while ($row = $this->db->fetchrow($result)) {
			$roles[$row[$sql_id]][$row['local_id']] = $row['auth_role_id'];
		}

		$this->db->freeresult($result);

		// $this->profiler->log($roles);

		return $roles;
	}

	/**
	* Права пользователя
	*/
	public function acl_user_raw_data($user_id = false, $opts = false, $local_id = false)
	{
		$sql_user = false !== $user_id ? (!is_array($user_id) ? 'user_id = ' . (int) $user_id : $this->db->in_set('user_id', array_map('intval', $user_id))) : '';
		$sql_local = false !== $local_id ? (!is_array($local_id) ? 'AND a.local_id = ' . (int) $local_id : 'AND ' . $this->db->in_set('a.local_id', array_map('intval', $local_id))) : '';

		$sql_opts = '';
		$ary = $sql_ary = [];

		if (false !== $opts) {
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
				site_auth_users a,
				site_auth_options ao
			WHERE
				a.auth_role_id = 0
			AND
				a.auth_option_id = ao.auth_id ' .
				($sql_user ? 'AND a.' . $sql_user : '') .
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
				site_auth_users a,
				site_auth_roles_data r,
				site_auth_options ao
			WHERE
				a.auth_role_id = r.role_id
			AND
				r.auth_option_id = ao.auth_id ' .
				($sql_user ? 'AND a.' . $sql_user : '') .
				$sql_local . $sql_opts . '
			ORDER BY
				a.local_id,
				ao.auth_var';

		foreach ($sql_ary as $sql) {
			$result = $this->db->query($sql);

			while ($row = $this->db->fetchrow($result)) {
				$ary[$row['user_id']][$row['local_id']][$row['auth_var']] = $row['auth_value'];
			}

			$this->db->freeresult($result);
		}

		return $ary;
	}

	/**
	* Авторизация
	*/
	public function login($username_or_email, $password, $autologin = false, $admin = 0)
	{
		$login = $this->user->login($username_or_email, $password);

		if ($login['status'] == 'OK') {
			if (!empty($_SESSION)) {
				$session_data = $_SESSION;
			}
			
			$this->user->session_end(false);

			if (true === $result = $this->user->session_create(false, $login['user_row']['user_id'], $autologin, $admin)) {
				if (!empty($session_data)) {
					$_SESSION = $session_data;
					$this->user->write($this->user->session_id, session_encode(), true);
				}
				
				return $login;
			}

			return [
				'message'  => $result,
				'status'   => 'LOGIN_BREAK',
				'user_row' => $login['user_row']
			];
		}

		return $login;
	}

	/**
	* Права пользователя
	*/
	private function _fill_acl($user_access)
	{
		$this->acl = is_array($user_access) ? $user_access : unserialize(base64_decode($user_access));
		// $this->profiler->log($this->acl);
	}

	private function _set_group_hold_ary(&$ary, $option_id, $value)
	{
		if (!isset($ary[$option_id]) || (isset($ary[$option_id]) && $ary[$option_id] != 0)) {
			$ary[$option_id] = $value;
		}
	}

	/**
	* Кэширование прав пользователя
	*/
	private function acl_cache(&$userdata)
	{
		/* Сбрасываем доступ */
		$userdata['user_access'] = '';

		$ary = $this->get_user_acl($userdata['user_id']);

		/* Индекс 0 содержит глобальные права */

		/* Создатель обладает всеми правами администратора */
		if ($userdata['user_id'] == 1) {
			foreach ($this->options['global'] as $opt => $id) {
				if (strpos($opt, 'a_') === 0) {
					$ary[0][$this->options['id'][$opt]] = 1;
				}
			}
		}

		$userdata['user_access'] = $ary;

		$sql = 'UPDATE site_users SET user_access = ? WHERE user_id = ?';
		$this->db->query($sql, [base64_encode(serialize($userdata['user_access'])), $userdata['user_id']]);
	}

	/**
	* Заполняем выражение auth_var для дальнейшего использования
	* в запросах на основе заданных настроек
	*/
	private function build_auth_option_statement($key, $auth_options, &$sql_opts)
	{
		if (!is_array($auth_options)) {
			if (false !== strpos($auth_options, '%')) {
				$sql_opts = "AND $key " . $this->db->like_expression(str_replace('%', chr(0) . '%', $auth_options));
			} else {
				$sql_opts = "AND $key = " . $this->db->check_value($auth_options);
			}
		} else {
			$is_like_expression = false;

			foreach ($auth_options as $option) {
				if (false !== strpos($option, '%')) {
					$is_like_expression = true;
				}
			}

			if (!$is_like_expression) {
				$sql_opts = 'AND ' . $this->db->in_set($key, $auth_options);
			} else {
				$sql = [];

				foreach ($auth_options as $option) {
					if (false !== strpos($option, '%')) {
						$sql[] = $key . ' ' . $this->db->like_expression(str_replace('%', chr(0) . '%', $option));
					} else {
						$sql[] = $key . " = " . $this->db->check_value($option);
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
		if (false === $role_cache = $this->cache->get_shared('role_cache')) {
		// if (false === false) {
			$role_cache = [];

			$sql = 'SELECT * FROM site_auth_roles_data ORDER BY role_id ASC';
			$result = $this->db->query($sql);

			while ($row = $this->db->fetchrow($result)) {
				$role_cache[$row['role_id']][$row['auth_option_id']] = (int) $row['auth_value'];
			}

			$this->db->freeresult($result);

			foreach ($role_cache as $role_id => $role_options) {
				$role_cache[$role_id] = serialize($role_options);
			}

			$this->cache->set_shared('role_cache', $role_cache);
		}

		$ary = [];

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
				site_auth_users
			WHERE
				user_id = ?';
		$this->db->query($sql, [$user_id]);

		while ($row = $this->db->fetchrow()) {
			if ($row['auth_role_id']) {
				$ary[$row['local_id']] = empty($ary[$row['local_id']]) ? unserialize($role_cache[$row['auth_role_id']]) : $ary[$row['local_id']] + unserialize($role_cache[$row['auth_role_id']]);
			} else {
				$ary[$row['local_id']][$row['auth_option_id']] = $row['auth_value'];
			}
		}

		$this->db->freeresult();

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
				site_auth_groups a
			LEFT JOIN
				site_user_groups ug ON (ug.group_id = a.group_id)
			LEFT JOIN
				site_groups g ON (g.group_id = ug.group_id)
			WHERE
				ug.user_pending = 0
			AND NOT
				(ug.group_leader = 1 AND g.group_skip_auth = 1)
			AND
				ug.user_id = ' . $this->db->check_value($user_id);
		$this->db->query($sql);

		while ($row = $this->db->fetchrow()) {
			if (!$row['auth_role_id']) {
				$this->_set_group_hold_ary($ary[$row['local_id']], $row['auth_option_id'], $row['auth_value']);
			} elseif (!empty($role_cache[$row['auth_role_id']])) {
				foreach (unserialize($role_cache[$row['auth_role_id']]) as $option_id => $value) {
					$this->_set_group_hold_ary($ary[$row['local_id']], $option_id, $value);
				}
			}
		}

		$this->db->freeresult();

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
		if (false === $this->options = $this->cache->get_shared('auth_options')) {
			/* Все возможные привилегии */
			$sql = 'SELECT * FROM site_auth_options';
			$this->db->query($sql);

			while ($row = $this->db->fetchrow()) {
				$this->options[$row['auth_id']] = [
					'name'    => $row['auth_name'],
					'sub'     => $row['auth_sub'],
					'var'     => $row['auth_var'],
					'global'  => $row['auth_global'],
					'local'   => $row['auth_local'],
					'default' => $row['auth_default']
				];
			}

			$this->db->freeresult();

			/* Записываем в кэш */
			$this->cache->set_shared('auth_options', $this->options);
		}

		if (false === $this->roles = $this->cache->get_shared('auth_roles')) {
			/* Все возможные роли */
			$sql = 'SELECT * FROM site_auth_roles';
			$this->db->query($sql);

			while ($row = $this->db->fetchrow()) {
				$this->roles[$row['role_id']] = [
					'name'        => $row['role_name'],
					'description' => $row['role_description'],
					'sort'        => $row['role_sort']
				];
			}

			$this->db->freeresult();

			/* Записываем в кэш */
			$this->cache->set_shared('auth_roles', $this->roles);
		}
	}

	/**
	* Обновляем привилегии выбранного пользователя
	*
	* @param	int		$user_id	ID пользователя
	*/
	function update($user_id)
	{
		/* Получаем все типы привилегий */
		$this->get_all();

		$userdata = [];

		/**
		* Если данные не указаны, то используем текущего пользователя
		*/
		if (false === $user_id) {
			$need_update = true;
			$user_id = $this->user['user_id'];
		} else {
			$need_update = false;
		}

		/**
		* Получаем список установленных привилегий из базы
		*/
		$sql = 'SELECT * FROM site_auth_users WHERE user_id = ?';
		$result = $this->db->query($sql, [$user_id]);

		while ($row = $this->db->fetchrow($result)) {
			if ($row['auth_option_id']) {
				/* Если установлена определённая привилегия */
				if (!isset($this->options[$row['auth_option_id']])) {
					/* Если установленная привилегия не существует в базе, то пропускаем её */
					continue;
				}

				if ($row['auth_local_id'] && $this->options[$row['auth_option_id']]['local']) {
					/* Если указана локальная привилегия и она может быть локальной, то устанавливаем её */
					$userdata[$this->options[$row['auth_option_id']]['sub']][$row['auth_local_id']][$this->options[$row['auth_option_id']]['var']] = $row['auth_value'];
				} elseif (!$this->options[$row['auth_option_id']]['sub']) {
					$userdata[$this->options[$row['auth_option_id']]['var']] = $row['auth_value'];
				}
			} elseif ($row['auth_role_id']) {
				/* Если установлена группа привелегий */
				if (!isset($this->roles[$row['role_id']])) {
					/* Если установленная роль не существует в базе, то пропускаем её */
					continue;
				}

				/* Получаем привилегии выбранной роли */
				$sql = 'SELECT * FROM site_auth_roles_data WHERE role_id = ?';
				$result2 = $this->db->query($sql, [$row['role_id']]);

				while ($row2 = $this->db->fetchrow($result2)) {
					if (!isset($this->options[$row2['auth_option_id']])) {
						/* Если установленная привилегия не существует в базе, то пропускаем её */
						continue;
					}

					$userdata[$this->options[$row['auth_option_id']]['sub']]['.'][$this->options[$row['auth_option_id']]['var']] = $row2['auth_value'];
				}

				$this->db->freeresult($result2);
			}
		}

		$this->db->freeresult($result);

		/* Если данные не указаны, то используем текущего пользователя */
		if ($need_update) {
			$this->data = $userdata;
		}

		$sql = 'UPDATE site_users SET user_access = ? WHERE user_id = ?';
		$this->db->query($sql, [base64_encode(serialize($userdata)), $user_id]);
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
		/**
		* Нельзя одновременно задать привилегию и роль
		*/
		if ($auth_option_id) {
			$auth_role_id = 0;
		} elseif ($auth_role_id) {
			$auth_option_id = 0;
		}

		/**
		* Массив данных sql запроса
		*/
		$sql_array = [
			'auth_option_id' => $auth_option_id,
			'auth_role_id'   => $auth_role_id,
			'auth_value'     => $auth_value
		];

		$sql = 'UPDATE site_auth_users SET :update_ary WHERE user_id = ?';
		$this->db->query($sql, [$user_id, ':update_ary' => $this->db->build_array('UPDATE', $sql_array)]);

		if (!$this->db->affected_rows()) {
			/**
			* Если данные не обновились, значит либо они уже существуют в данном виде, либо их вообще нет
			* Проверяем существование
			*/
			$sql = '
				SELECT
					user_id
				FROM
					site_auth_users
				WHERE
					user_id = ?
				AND
					auth_option_id = ?
				AND
					auth_role_id = ?
				AND
					auth_value = ?';
			$result = $this->db->query($sql, [$user_id, $auth_option_id, $auth_role_id, $auth_value]);
			$this->db->freeresult($result);

			if (!$this->db->affected_rows()) {
				/**
				* Так ничего и не нашли
				* Добавляем данные
				*/
				$sql_array['user_id'] = $user_id;

				$sql = 'INSERT INTO site_auth_users ' . $this->db->build_array('INSERT', $sql_array);
				$this->db->query($sql);
			}
		}
	}
}
