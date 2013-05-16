<?php
/**
* @package fw
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
		$this->logger->info("[{$this->data['cron_script']}] {$text}");
	}
}
