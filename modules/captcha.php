<?php
/**
* @package ivacuum.fw
* @copyright (c) 2012
*/

namespace fw\modules;

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
