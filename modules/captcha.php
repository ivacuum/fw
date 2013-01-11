<?php
/**
* @package src.ivacuum.ru
* @copyright (c) 2012 vacuum
*/

namespace engine\modules;

use app\models\page;

/**
* Вывод кода подтверждения (каждый раз нового)
*/
class captcha extends page
{
	public function index()
	{
		$factory = new \engine\captcha\factory($this->config['confirm_type']);
		$captcha = $factory->get_service();
		$captcha->send();
		
		garbage_collection(false);
		exit;
	}
}
