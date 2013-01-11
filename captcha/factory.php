<?php
/**
* @package src.ivacuum.ru
* @copyright (c) 2012 vacuum
*/

namespace engine\captcha;

/**
* Фабрика для капчи
*/
class factory
{
	private $type;

	function __construct($type)
	{
		$this->type = $type;
	}
	
	public function get_driver()
	{
		$class = 'engine\\captcha\\driver\\' . $this->type;
		
		return new $class();
	}
	
	public function get_service()
	{
		return new service($this->get_driver());
	}
}
