<?php
/**
* @package fw
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
		$class = '\\fw\\captcha\\driver\\' . $this->config['confirm_type'];

		$captcha = new captcha_service($this->config, $this->db, $this->request, $this->user, new $class());
		$captcha->send();
		
		garbage_collection();
	}
}
