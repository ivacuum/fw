<?php
/**
* @package fw
* @copyright (c) 2013
*/

namespace fw\cron;

/**
* Задачи по расписанию
*/
class manager
{
	protected $cron_allowed;
	protected $cron_running;
	protected $db;
	protected $deadlock_timeout = 900;
	protected $hostname;
	protected $logs_dir;
	protected $start_time;
	protected $task_time_limit = 300;
	protected $tasks = [];
	protected $tasks_timeout = 1;

	function __construct($logs_dir, $cron_allowed, $cron_running, $db)
	{
		$this->start_time   = time();
		$this->hostname     = $_SERVER['SERVER_NAME'];
		$this->logs_dir     = $logs_dir;
		$this->cron_allowed = "{$this->logs_dir}/{$cron_allowed}";
		$this->cron_running = "{$this->logs_dir}/{$cron_running}";

		$this->db = $db;
	}
	
	/**
	* Освобождение блокировки
	*/
	public function release_file_lock()
	{
		rename($this->cron_running, $this->cron_allowed);
		touch($this->cron_allowed);
	}

	/**
	* Выполнение задач
	*/
	public function run()
	{
		if (file_exists($this->cron_running))
		{
			/**
			* До сих пор выполняются задачи в другом процессе
			* Выходим и ждем своей очереди
			*/
			return;
		}

		if (!$this->get_file_lock())
		{
			return;
		}

		register_shutdown_function([$this, 'release_file_lock']);

		$this->track_running('start');
		$this->load_tasks();
		$this->log(sprintf('Найдено готовых к запуску задач: %d', sizeof($this->tasks)));

		if ($this->tasks)
		{
			foreach ($this->tasks as $task)
			{
				$this->log(sprintf('Выполнение задачи "%s" [%s] на сайте: #%d', $task['cron_title'], $task['cron_script'], $task['site_id']));
				set_time_limit($this->task_time_limit);

				/* Выполнение задачи */
				$cron_class = "\\app\\cron\\{$task['cron_script']}";
				$cron = new $cron_class($task);
				
				if ($cron->run())
				{
					/* Установка времени следующего запуска */
					$this->set_next_run_time($task['cron_id'], $task['cron_schedule']);
					$this->log('Задача выполнена');
				}
				else
				{
					$this->log('Не удалось выполнить задачу');
				}

				sleep($this->tasks_timeout);
			}
		}

		$this->track_running('end');
		$this->log('Завершение работы менеджера задач');
	}

	/**
	* Получаем блокировку для выполнения задач
	*/
	private function get_file_lock()
	{
		if (file_exists($this->cron_allowed))
		{
			return rename($this->cron_allowed, $this->cron_running);
		}
		elseif (file_exists($this->cron_running))
		{
			$this->release_deadlock();
		}

		return touch($this->cron_allowed);
	}

	/**
	* Загрузка задач, готовых к выполнению
	*/
	private function load_tasks()
	{
		$sql = '
			SELECT
				site_id
			FROM
				' . SITES_TABLE . '
			WHERE
				site_url = ' . $this->db->check_value($this->hostname);
		$result = $this->db->query($sql);
		$site_ids = [];
		
		while ($row = $this->db->fetchrow($result))
		{
			$site_ids[] = (int) $row['site_id'];
		}
		
		$this->db->freeresult($result);
		
		$sql = '
			SELECT
				*
			FROM
				' . CRON_TABLE . '
			WHERE
				' . $this->db->in_set('site_id', $site_ids) . '
			AND
				cron_active = 1
			AND
				next_run <= ' . $this->start_time . '
			ORDER BY
				site_id ASC,
				run_order ASC';
		$result = $this->db->query($sql);
		$this->tasks = $this->db->fetchall($result);
		$this->db->freeresult($result);
	}
	
	/**
	* Лог операций
	*/
	private function log($text)
	{
		printf("%s: %s\n", date('Y-m-d H:i:s'), $text);
	}

	/**
	* Выход из тупика
	*/
	private function release_deadlock()
	{
		if (!file_exists($this->cron_running) || time() - filemtime($this->cron_running) < $this->deadlock_timeout)
		{
			return;
		}

		/* Разблокировка */
		$this->release_file_lock();
	}
	
	/**
	* Установка времени следующего запуска
	*/
	private function set_next_run_time($cron_id, $cron_schedule)
	{
		$next_run = date_create();
		date_modify($next_run, $cron_schedule);

		$sql = '
			UPDATE
				' . CRON_TABLE . '
			SET
				last_run = UNIX_TIMESTAMP(),
				next_run = ' . date_timestamp_get($next_run) . ',
				run_counter = run_counter + 1
			WHERE
				cron_id = ' . $cron_id;
		$this->db->query($sql);
	}

	/**
	* Отслеживание процесса выполнения задач
	*
	* В случае возникновения ошибок в папке логов останется файл
	*/
	private function track_running($mode)
	{
		$startmark = sprintf('%s/cron_started_at_%s', $this->logs_dir, date('Y-m-d_H-i-s', $this->start_time));

		if ($mode == 'start')
		{
			touch($this->cron_running);
			touch($startmark);
		}
		elseif ($mode == 'end')
		{
			unlink($startmark);
		}
	}
}
