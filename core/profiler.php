<?php
/**
* @package fw
* @copyright (c) 2013
*/

namespace fw\core;

/**
* Класс для хранения информации о скрипте и ходе его выполнения
*/
class console
{
	protected $error_count   = 0;
	protected $file_count    = 0;
	protected $file_size     = 0;
	protected $file_largest  = 0;
	protected $log_count     = 0;
	protected $memory_count  = 0;
	protected $memory_total  = 0;
	protected $memory_used   = 0;
	protected $speed_allowed = 0;
	protected $speed_count   = 0;
	protected $speed_total   = 0;
	protected $query_cached  = 0;
	protected $query_count   = 0;
	protected $query_time    = 0;

	protected $logs = [];
	protected $queries = [];

	public function log()
	{
		if (PHP_SAPI == 'cli')
		{
			return $this;
		}
		
		foreach (func_get_args() as $arg)
		{
			$this->logs[] = [
				'data' => $arg,
				'type' => 'log',
			];
		}
		
		$this->log_count++;
		
		return $this;
	}

	public function log_memory($object = false, $name = 'php')
	{
		if (PHP_SAPI == 'cli')
		{
			return $this;
		}
		
		$this->logs[] = [
			'data'      => $object ? strlen(serialize($object)) : memory_get_usage(),
			'type'      => 'memory',
			'name'      => $name,
			'data_type' => gettype($object),
		];

		$this->memory_count++;
		
		return $this;
	}

	public function log_error($message, $line, $file)
	{
		if (PHP_SAPI == 'cli')
		{
			return $this;
		}
		
		$call_stack = '';
		
		if (function_exists('xdebug_print_function_stack'))
		{
			ob_start();
			xdebug_print_function_stack();
			$call_stack = str_replace($this->document_root, '', ob_get_clean());
		}
		
		$this->logs[] = [
			'call_stack' => $call_stack,
			'data'       => $message,
			'type'       => 'error',
			'file'       => $file,
			'line'       => $line,
		];

		$this->error_count++;
		
		return $this;
	}

	public function log_speed($name = 'label')
	{
		if (PHP_SAPI == 'cli')
		{
			return $this;
		}
		
		$this->logs[] = [
			'data' => (microtime(true) - $this->start_time) * 1000,
			'type' => 'speed',
			'name' => $name,
		];
		
		$this->speed_count++;
		
		return $this;
	}

	public function log_query($sql, $start_time, $cached = false)
	{
		if (PHP_SAPI == 'cli')
		{
			return $this;
		}
		
		$query_time = (microtime(true) - $start_time) * 1000;
		$this->query_time += $query_time;
		$this->query_count++;
		
		$this->queries[] = [
			'cached' => $cached,
			'sql'    => preg_replace('#[\n\r\s\t]+#', ' ', $sql),
			'time'   => $query_time,
		];

		if ($cached)
		{
			$this->query_cached++;
		}
		
		return $this;
	}
}

class profiler extends console
{
	protected $document_root;
	protected $output = [];
	protected $start_time;
	
	protected $options = [
		'debug.ips'  => [],
		'enabled'    => false,
		'host'       => '',
		'port'       => 0,
		'send_stats' => false,
	];

	function __construct($start_time = false, array $options = [])
	{
		$this->document_root = realpath(rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/../../') . '/';
		$this->start_time    = $start_time ?: microtime(true);
		
		$this->options = array_merge($this->options, $options);
	}

	public function get_stats()
	{
		if (PHP_SAPI == 'cli')
		{
			return;
		}
		
		$this->get_console_data()
			->get_file_data()
			->get_memory_data()
			->get_query_data()
			->get_speed_data();

		return [
			'profiler_logs'    => $this->output['logs'],
			'profiler_files'   => $this->output['files'],
			'profiler_queries' => $this->output['queries'],
			
			'FILE_COUNT'      => $this->file_count,
			'FILE_SIZE'       => $this->file_size,
			'FILE_LARGEST'    => $this->file_largest,
			'LOG_COUNT'       => $this->log_count,
			'LOGS_COUNT'      => sizeof($this->output['logs']),
			'ERROR_COUNT'     => $this->error_count,
			'MEMORY_COUNT'    => $this->memory_count,
			'MEMORY_TOTAL'    => $this->memory_total,
			'MEMORY_USED'     => $this->memory_used,
			'SERVER_HOSTNAME' => gethostname(),
			'SPEED_ALLOWED'   => $this->speed_allowed,
			'SPEED_COUNT'     => $this->speed_count,
			'SPEED_TOTAL'     => $this->speed_total,
			'QUERY_CACHED'    => $this->query_cached,
			'QUERY_COUNT'     => $this->query_count,
			'QUERY_TIME'      => $this->query_time,
		];
	}
	
	public function is_enabled()
	{
		return $this->options['enabled'];
	}
	
	public function is_permitted()
	{
		return in_array($_SERVER['REMOTE_ADDR'], $this->options['debug.ips']);
	}
	
	/**
	* Отправка данных внешнему профайлеру
	*/
	public function send_stats($hostname = '', $url = '')
	{
		if (PHP_SAPI == 'cli')
		{
			return;
		}
		
		if (!$this->options['send_stats'])
		{
			return;
		}
		
		if (false === $fp = fsockopen("udp://{$this->options['host']}", $this->options['port']))
		{
			return false;
		}
		
		if (!$this->memory_used)
		{
			$this->get_console_data()
				->get_file_data()
				->get_memory_data()
				->get_query_data()
				->get_speed_data();
		}

		fwrite($fp, json_encode([
			'domain' => $hostname,
			'page'   => $url,
			
			'logs' => $this->output['logs'],
			
			'file_count'    => $this->file_count,
			'file_size'     => $this->file_size,
			'file_largest'  => $this->file_largest,
			'log_count'     => $this->log_count,
			'logs_count'    => sizeof($this->output['logs']),
			'error_count'   => $this->error_count,
			'memory_count'  => $this->memory_count,
			'memory_total'  => $this->memory_total,
			'memory_used'   => $this->memory_used,
			'speed_allowed' => $this->speed_allowed,
			'speed_count'   => $this->speed_count,
			'speed_total'   => intval($this->speed_total),
			'query_cached'  => $this->query_cached,
			'query_count'   => $this->query_count,
			'query_time'    => sprintf('%.3f', $this->query_time),
		]));
		
		fclose($fp);
	}

	protected function get_console_data()
	{
		foreach ($logs = $this->logs as $key => $log)
		{
			switch ($log['type'])
			{
				case 'log':    $logs[$key]['data'] = print_r($log['data'], true); break;
				case 'memory':
				case 'speed':  $logs[$key]['data'] = $log['data']; break;
			}
		}

		$this->output['logs'] = $logs;
		
		return $this;
	}

	protected function get_file_data()
	{
		$file_list = [];
		$this->file_count = 0;

		foreach (get_included_files() as $key => $file)
		{
			if (false !== strpos($file, '/lib/'))
			{
				continue;
			}
			
			$size = filesize($file);

			$file_list[] = [
				'name' => str_replace($this->document_root, '', $file),
				'size' => $size,
			];

			$this->file_size += $size;
			$this->file_largest = $size > $this->file_largest ? $size : $this->file_largest;
			$this->file_count++;
		}

		$this->output['files'] = $file_list;
		
		return $this;
	}

	protected function get_memory_data()
	{
		$this->memory_used  = memory_get_peak_usage();
		$this->memory_total = ini_get('memory_limit');
		
		return $this;
	}

	protected function get_query_data()
	{
		$this->output['queries'] = $this->queries;
		
		return $this;
	}

	protected function get_speed_data()
	{
		$this->speed_allowed = ini_get('max_execution_time');
		$this->speed_total   = (microtime(true) - $this->start_time) * 1000;
		
		return $this;
	}
}
