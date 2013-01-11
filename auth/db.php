<?php
/**
* @package fw
* @copyright (c) 2010
*/

if( !defined('IN_SITE') )
{
	exit;
}

function login_db(&$username, &$password)
{
	global $config, $db;

	/* Нельзя вводить пустой пароль */
	if( !$password )
	{
		return array(
			'status'    => 'LOGIN_ERROR_PASSWORD',
			'error_msg' => 'NO_PASSWORD_SUPPLIED',
			'user_row'  => array('user_id' => 0)
		);
	}

	if( !$username )
	{
		return array(
			'status'    => 'LOGIN_ERROR_USERNAME',
			'error_msg' => 'LOGIN_ERROR_USERNAME',
			'user_row'  => array('user_id' => 0)
		);
	}

	$sql = '
		SELECT
			user_id,
			username,
			user_password,
			user_salt,
			user_email,
			user_login_attempts
		FROM
			' . USERS_TABLE . '
		WHERE
			username_clean = ' . $db->check_value($username);
	$result = $db->query($sql);
	$row = $db->fetchrow($result);
	$db->freeresult($result);

	if( !$row )
	{
		return array(
			'status'    => 'LOGIN_ERROR_USERNAME',
			'error_msg' => 'LOGIN_ERROR_USERNAME',
			'user_row'  => array('user_id' => 0)
		);
	}

	// $show_captcha = $config['max_login_attempts'] && $row['user_login_attempts'] >= $config['max_login_attempts'];
	$show_captcha = false;

	// If there are too much login attempts, we need to check for an confirm image
	// Every auth module is able to define what to do by itself...
	/*
	if ($show_captcha)
	{
		// Visual Confirmation handling
		if (!class_exists('phpbb_captcha_factory'))
		{
			global $phpbb_root_path, $phpEx;
			include ($phpbb_root_path . 'includes/captcha/captcha_factory.' . $phpEx);
		}

		$captcha =& phpbb_captcha_factory::get_instance($config['captcha_plugin']);
		$captcha->init(CONFIRM_LOGIN);
		$vc_response = $captcha->validate($row);
		if ($vc_response)
		{
			return array(
				'status'		=> LOGIN_ERROR_ATTEMPTS,
				'error_msg'		=> 'LOGIN_ERROR_ATTEMPTS',
				'user_row'		=> $row,
			);
		}
		else
		{
			$captcha->reset();
		}
	}
	*/

	/* Проверка пароля */
	if( $password === $row['user_password'] )
	{
		if( !$row['user_salt'] )
		{
		}

		if( $row['user_login_attempts'] != 0 )
		{
			$sql = '
				UPDATE
					' . USERS_TABLE . '
				SET
					user_login_attempts = 0
				WHERE
					user_id = ' . $db->check_value($row['user_id']);
			$db->query($sql);
		}

		if( !$row['user_active'] )
		{
			return array(
				'status'    => 'LOGIN_ERROR_ACTIVE',
				'error_msg' => 'ACTIVE_ERROR',
				'user_row'  => $row
			);
		}

		return array(
			'status'    => 'LOGIN_SUCCESS',
			'error_msg' => false,
			'user_row'  => $row
		);
	}

	$sql = '
		UPDATE
			' . USERS_TABLE . '
		SET
			user_login_attempts = user_login_attempts + 1
		WHERE
			user_id = ' . $db->check_value($row['user_id']) . '
		AND
			user_login_attempts < ' . $db->check_value($config['max_login_attempts']);
	$db->query($sql);

	return array(
		'status'    => ( $show_captcha ) ? 'LOGIN_ERROR_ATTEMPTS' : 'LOGIN_ERROR_PASSWORD',
		'error_msg' => ( $show_captcha ) ? 'LOGIN_ERROR_ATTEMPTS' : 'LOGIN_ERROR_PASSWORD',
		'user_row'  => $row
	);
}
