<?php
/**
* @package fw
* @copyright (c) 2012
*/

namespace fw\cron;

/**
* Задача по расписанию
*/
class task
{
	public $data = [];

	protected $cache;
	protected $config;
	protected $ctime;
	protected $db;

	function __construct(array $row)
	{
		global $app;

		$this->cache  = $app['cache'];
		$this->config = $app['config'];
		$this->ctime  = time();
		$this->data   = $row;
		$this->db     = $app['db'];
	}
	
	/**
	* Лог операций
	*/
	protected function log($text)
	{
		printf("%s: [%s] %s\n", date('Y-m-d H:i:s'), $this->data['cron_script'], $text);
	}
}
