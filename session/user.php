<?php
/**
* @package ivacuum.fw
* @copyright (c) 2013
*/

namespace fw\session;

use fw\traits\i18n;

/**
* Пользователь
*/
class user extends session
{
	use i18n;
	
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
		$this->load_language('general');
		
		return $this;
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
}
