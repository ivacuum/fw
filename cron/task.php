<?php
/**
* @package ivacuum.fw
* @copyright (c) 2013
*/

namespace fw\cron;

use fw\traits\injection;

/**
* Задача по расписанию
*/
class task
{
	use injection;
	
	public $data = [];

	protected $ctime;

	function __construct(array $row)
	{
		$this->ctime = time();
		$this->data  = $row;
	}
	
	/**
	* Лог операций
	*/
	protected function log($text)
	{
		printf("%s: [%s] %s\n", date('Y-m-d H:i:s'), $this->data['cron_script'], $text);
	}
}
