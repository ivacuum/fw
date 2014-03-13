<?php namespace fw\cron;

use fw\Traits\Injection;

/**
* Задача по расписанию
*/
class task
{
	use Injection;
	
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
