<?php
/**
* @package fw
* @copyright (c) 2012
*/

namespace fw\captcha;

/**
* Проверка кода подтверждения
*/
class validator
{
	protected $config;
	protected $db;
	protected $request;
	protected $user;
	
	private $code;
	private $confirm_code;
	private $solved = false;
	
	function __construct($config, $db, $request, $user)
	{
		$this->config  = $config;
		$this->db      = $db;
		$this->request = $request;
		$this->user    = $user;
		
		$this->confirm_code = mb_strtoupper($this->request->variable('confirm_code', ''));
	}

	/**
	* Верно ли введен код подтверждения
	*/
	public function is_solved()
	{
		if (!$this->config['confirm.enable'])
		{
			return true;
		}
		
		if ($this->request->is_set('confirm_code') && false === $this->solved)
		{
			$this->validate();
		}
		
		return $this->solved;
	}
	
	/**
	* Сброс кода подтверждения
	*/
	public function reset()
	{
		if (!$this->config['confirm.enable'])
		{
			return;
		}
		
		if ($this->solved)
		{
			$sql = '
				DELETE
				FROM
					site_confirm
				WHERE
					session_id = ' . $this->db->check_value($this->user->session_id) . '
				AND
					code = ' . $this->db->check_value($this->code);
			$this->db->query($sql);
		}
	}
	
	/**
	* Сравнение кода с эталонным
	*/
	private function check_code()
	{
		return (strcasecmp($this->code, $this->confirm_code) === 0);
	}
	
	/**
	* Загрузка кода
	*/
	private function load_code()
	{
		$sql = '
			SELECT
				code
			FROM
				site_confirm
			WHERE
				session_id = ' . $this->db->check_value($this->user->session_id);
		$this->db->query($sql);
		$row = $this->db->fetchrow();
		$this->db->freeresult();
		
		if (!$row)
		{
			return false;
		}
		
		$this->code = $row['code'];
	}
	
	/**
	* Проверка ввода кода
	*/
	private function validate()
	{
		if (empty($this->code))
		{
			$this->load_code();
		}
		
		$this->solved = $this->check_code();
	}
}
