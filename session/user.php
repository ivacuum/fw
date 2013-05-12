<?php
/**
* @package fw
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
	public function login($username_or_email, $password)
	{
		$username_or_email = mb_strtolower($username_or_email);
		
		if (!$username_or_email)
		{
			return [
				'message'  => 'Вы не указали логин или электронную почту',
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
				site_users
			WHERE
				username_clean = ?';
		$this->db->query($sql, [$username_or_email]);
		$row = $this->db->fetchrow();
		$this->db->freeresult();
		$attempts = 0;
		
		if (!$row)
		{
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
					site_users
				WHERE
					user_email = ?';
			$this->db->query($sql, [$username_or_email]);
			$row = $this->db->fetchrow();
			$this->db->freeresult();
		}
		
		// if ($ip)
		// {
		// 	$sql = '
		// 		SELECT
		// 			COUNT(attempt_id) AS attempts
		// 		FROM
		// 			site_login_attempts
		// 		WHERE
		// 			attempt_time > ?
		// 		AND
		// 			attempt_ip = ?';
		// 	$this->db->query($sql, [time() - (int) $this->config['ip_login_limit_time'], $ip]);
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
		// 	$sql = 'INSERT INTO site_login_attempts ' . $this->db->build_array('INSERT', $sql_ary);
		// 	$this->db->sql_query($sql);
		// }

		if (!$row)
		{
			return [
				'message'  => 'Неверно указан логин, почта или пароль',
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
			$sql = 'DELETE FROM site_login_attempts WHERE user_id = ?';
			$this->db->query($sql, [$row['user_id']]);
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
		
		$sql = 'UPDATE site_users SET :update_ary WHERE user_id = ?';
		$this->db->query($sql, [$user_id, ':update_ary' => $this->db->build_array('UPDATE', $sql_ary)]);
		
		$this->data = $user_id == $this->data['user_id'] ? array_merge($this->data, $sql_ary) : $this->data;
	}
}
