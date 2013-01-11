<?php
/**
* @package fw
* @copyright (c) 2012
*/

namespace fw\captcha;

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
		$class = 'fw\\captcha\\driver\\' . $this->type;
		
		return new $class();
	}
	
	public function get_service()
	{
		return new service($this->get_driver());
	}
}
