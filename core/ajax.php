<?php
/**
* @package fw
* @copyright (c) 2012
*/

namespace fw\core;

/**
* Ajax-запрос
*/
class ajax
{
	private $action;
	private $request = array();
	protected $response = array();

	protected $valid_actions = array(
		'get_login_form'    => 'guest',
		'get_logout_form'   => 'user',
		'get_password_form' => 'user'
	);

	function __construct()
	{
		header('Content-Type: text/plain');

		/* post или request? */
		// $this->request = $_POST;
		$this->request = $_REQUEST;

		if (!isset($this->request['action']) || !$this->request['action'] || !is_string($this->request['action']))
		{
			$this->ajax_die('Метод не найден.');
		}

		$this->action = $this->request['action'];

		if (!isset($this->valid_actions[$this->action]))
		{
			$this->ajax_die('Метод <b>' . $this->action . '</b> не найден.');
		}
	}

	/**
	* Выход с ошибкой
	*/
	protected function ajax_die($message, $code = 100)
	{
		$this->response['error_code'] = $code;
		$this->response['error_msg']  = $message;
		$this->send();
	}

	/**
	* Выполнение запроса
	*/
	public function exec()
	{
		if (!empty($this->response['error_code']))
		{
			$this->send();
		}

		switch ($this->valid_actions[$this->action])
		{
			/**
			* Все
			*/
			case 'guest';
			break;
			/**
			* Авторизованный пользователь
			*/
			case 'user':

				global $auth, $user;

				if (empty($user->data))
				{
					$user->session_begin(false);
					$auth->init($user->data);
					$user->preferences();
				}

				$this->check_user_session();

			break;
			/**
			* Модератор
			*/
			case 'mod':

				$this->ajax_die('Метод не поддерживается.');

				// trigger_error('Параметр авторизации задан неверно', E_USER_ERROR);

			break;
			/**
			* Администратор
			*/
			case 'admin':

				global $auth, $user;

				if (empty($user->data))
				{
					$user->session_begin(false);
					$auth->init($user->data);
					$user->preferences();
				}

				if (!$user->is_registered)
				{
					$this->ajax_die('NOT_YET_LOGGED_IN');
				}

				if (!$auth->check('acp_enter'))
				{
					$this->ajax_die('NOT_AN_ADMIN');
				}

				$this->check_admin_session();

			break;
			default:

				trigger_error('Параметр авторизации задан неверно', E_USER_ERROR);

			break;
		}

		$this->{$this->action}();
		$this->send();
	}

	/**
	* Отправка запроса пользователю
	*/
	private function send()
	{
		$this->response['action'] = $this->action;

		print json_encode($this->response);
		exit;
	}

	/**
	*  Проверка авторизации администратора
	*/
	private function check_admin_session()
	{
		global $user;

		if (!$user['session_admin'])
		{
			$this->response['prompt_password'] = 1;
			$this->send();
		}
	}

	/**
	* Проверка авторизации пользователя
	*/
	private function check_user_session()
	{
		global $user;

		if (!$user->is_registered)
		{
			$this->response['prompt_login'] = 1;
			$this->send();
		}
	}

	/**
	* Форма авторизации
	*/
	private function get_login_form()
	{
		global $auth, $request, $template, $user;

		$user->session_begin(false);
		$auth->init($user->data);
		$user->preferences();

		if ($user->is_registered)
		{
			$this->ajax_die('ALREADY_LOGGED_IN');
		}

		$back_url = $request->variable('back_url', $user->page_prev);

		$template->vars(array(
			'BACK_URL' => htmlspecialchars($back_url, ENT_QUOTES),

			'U_ACTION' => ilink('/ucp/login.html'))
		);

		$template->file = 'ajax/login_form.html';

		ob_start();
		$template->go();
		$template_php = ob_get_clean();

		$this->response['update_ids'] = array('dialog' => $template_php);
	}

	/**
	* Форма выхода с сайта
	*/
	private function get_logout_form()
	{
		global $template, $user;

		$user->session_begin(false);
		$user->preferences();

		$template->vars(array(
			'U_ACTION' => ilink('/ucp/logout.html'))
		);

		$template->file = 'ajax/logout_form.html';

		ob_start();
		$template->go();
		$template_php = ob_get_clean();

		$this->response['update_ids'] = array('dialog' => $template_php);
	}

	/**
	* Форма ввода пароля
	*/
	private function get_password_form()
	{
		global $auth, $request, $template, $user;

		$user->session_begin(false);
		$auth->init($user->data);
		$user->preferences();

		if ($user['session_admin'])
		{
			$this->ajax_die('ALREADY_LOGGED_IN');
		}

		$back_url = $request->variable('back_url', $user->page_prev);

		$template->vars(array(
			'BACK_URL' => htmlspecialchars($back_url, ENT_QUOTES),

			'U_ACTION' => ilink('/ucp/login.html'))
		);

		$template->file = 'ajax/password_form.html';

		$template->vars(array(
			'USERNAME' => $user['username'])
		);

		ob_start();
		$template->go();
		$template_php = ob_get_clean();

		$this->response['update_ids'] = array('dialog' => $template_php);
	}
}
