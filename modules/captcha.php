<?php namespace fw\modules;

use app\models\page;

/**
* Вывод кода подтверждения (каждый раз нового)
*/
class captcha extends page
{
	public function index()
	{
		$this->captcha->send();
		exit;
	}
}
