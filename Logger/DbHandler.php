<?php namespace fw\Logger;

use Monolog\Logger;
use Monolog\Handler\AbstractProcessingHandler;

class DbHandler extends AbstractProcessingHandler
{
	protected $db;
	protected $request;

	public function __construct($db, $request, $level = Logger::INFO, $bubble = true)
	{
		$this->db = $db;
		$this->request = $request;
		
		parent::__construct($level, $bubble);
	}
	
	protected function write(array $record)
	{
		if (empty($record['context']['action'])) {
			return false;
		}
		
		$log_action = $record['context']['action'];
		unset($record['context']['action']);
		
		$sql_ary = [
			'site_id'     => (int) $record['extra']['site_id'],
			'user_id'     => (int) $record['extra']['user_id'],
			'log_date'    => (int) $record['datetime']->format('U'),
			'log_action'  => $log_action,
			'level'       => $record['level'],
			'message'     => $record['message'],
			'context'     => !empty($record['context']) ? json_encode($record['context'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : '',
			'url'         => $this->request->url,
			'http_code'   => $this->request->code,
			'http_method' => $this->request->method,
			'is_ajax'     => $this->request->is_ajax,
			'ip'          => $record['extra']['ip'],
		];
		
		$this->db->query('INSERT INTO site_logs ' . $this->db->build_array('INSERT', $sql_ary));
	}
}
