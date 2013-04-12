<?php
/**
* @package fw
* @copyright (c) 2013
*/

namespace fw\db;

/**
* Класс работы с MySQL версии 4.1 и выше
*/
class mysqli
{
	protected $options = [
		'host' => 'localhost',
		'port' => false,
		'name' => '',
		'user' => '',
		'pass' => '',
		'sock' => '',
		'pers' => false,
	];

	protected $cache_rowset = [];
	protected $cache_row_pointer = [];
	protected $connect_id;
	protected $query_result;
	protected $num_queries = ['cached' => 0, 'normal' => 0, 'total' => 0];
	protected $transaction = false;
	protected $transactions = 0;

	protected $cache;
	protected $profiler;
	
	/**
	* Сбор параметров
	* Само подключение к серверу выполняется при первом запросе
	*/
	function __construct($cache, $profiler, array $options = [])
	{
		$this->options = array_merge($this->options, $options);
		
		$this->cache    = $cache;
		$this->profiler = $profiler;
		
		if (false !== $this->options['pers'] && $this->options['host'] == 'localhost' && version_compare(PHP_VERSION, '5.3.0', '>='))
		{
			$this->options['host'] = "p:{$this->options['host']}";
		}
	}
	
	function __destruct()
	{
		$this->close();
	}

	/**
	* Затронутые поля
	*/
	public function affected_rows()
	{
		return $this->connect_id ? mysqli_affected_rows($this->connect_id) : false;
	}

	/**
	* Преобразование массива в строку
	* и выполнение запроса
	*/
	public function build_array($query, $data = false)
	{
		if (!is_array($data))
		{
			return false;
		}

		$fields = $values = [];

		if ($query == 'INSERT')
		{
			foreach ($data as $key => $value)
			{
				$fields[] = $key;
				$values[] = $this->check_value($value);
			}

			$query = ' (' . implode(', ', $fields) . ') VALUES (' . implode(', ', $values) . ')';
		}
		elseif ($query == 'SELECT' || $query == 'UPDATE')
		{
			$values = [];
			
			foreach ($data as $key => $value)
			{
				$values[] = $key . ' = ' . $this->check_value($value);
			}

			$query = implode($query == 'UPDATE' ? ', ' : ' AND ', $values);
		}

		return $query;
	}
	
	/**
	* Построение sql-запроса из массива данных
	*/
	public function build_query($query, $array)
	{
		$sql = '';
		
		switch ($query)
		{
			case 'SELECT':
			case 'SELECT_DISTINCT':
			
				$sql = str_replace('_', '', $query) . ' ' . (is_array($array['SELECT']) ? implode(', ', $array['SELECT']) : $array['SELECT']) . ' FROM ';
				
				if (is_array($array['FROM']))
				{
					$table_array = $aliases = [];
					$used_multi_alias = false;

					foreach ($array['FROM'] as $table_name => $alias)
					{
						if (is_array($alias))
						{
							$used_multi_alias = true;

							foreach ($alias as $multi_alias)
							{
								$table_array[] = $table_name . ' ' . $multi_alias;
								$aliases[] = $multi_alias;
							}
						}
						else
						{
							$table_array[] = $table_name . ' ' . $alias;
							$aliases[] = $alias;
						}
					}

					$sql .= implode(', ', $table_array);
				}
				else
				{
					$sql .= $array['FROM'];
				}
				
				if (!empty($array['LEFT_JOIN']))
				{
					if (is_array($array['LEFT_JOIN']))
					{
						foreach ($array['LEFT_JOIN'] as $join)
						{
							$sql .= ' LEFT JOIN ' . key($join['FROM']) . ' ' . current($join['FROM']) . ' ON (' . $join['ON'] . ')';
						}
					}
					else
					{
						$sql .= ' LEFT JOIN ' . $array['LEFT_JOIN'];
					}
				}
				
				if (!empty($array['WHERE']))
				{
					$sql .= ' WHERE ' . implode(' AND ', $array['WHERE']);
				}
				
				if (!empty($array['GROUP_BY']))
				{
					$sql .= ' GROUP BY ' . $array['GROUP_BY'];
				}
				
				if (!empty($array['ORDER_BY']))
				{
					$sql .= ' ORDER BY ' . $array['ORDER_BY'];
				}
				
			break;
		}
		
		return $sql;
	}

	/**
	* Сверяем тим переменной и её значение,
	* строки также экранируем
	*/
	public function check_value($value)
	{
		if (is_null($value))
		{
			return 'NULL';
		}
		elseif (is_string($value))
		{
			return "'" . $this->escape($value) . "'";
		}
		
		return is_bool($value) ? intval($value) : $value;
	}

	/**
	* Закрытие текущего подключения
	*/
	public function close()
	{
		if (!$this->connect_id)
		{
			return false;
		}

		if ($this->transaction)
		{
			do
			{
				$this->transaction('commit');
			}
			while ($this->transaction);
		}

		return mysqli_close($this->connect_id);
	}

	/**
	* Экранируем символы
	*/
	public function escape($message)
	{
		if (!$this->connect_id)
		{
			$this->connect();
		}
		
		return mysqli_real_escape_string($this->connect_id, $message);
	}
	
	/**
	* Заносим полученные данные в цифровой массив
	*
	* @param string $field Поле, по которому создавать массив
	*/
	public function fetchall($query_id = false, $field = false)
	{
		if (false === $query_id)
		{
			$query_id = $this->query_result;
		}

		if (false !== $query_id)
		{
			$result = [];

			if (!is_object($query_id) && isset($this->cache_rowset[$query_id]))
			{
				if (false !== $field)
				{
					foreach ($this->cache_rowset[$query_id] as $row)
					{
						$result[$row[$field]] = $row;
					}
					
					return $result;
				}
				
				return $this->cache_rowset[$query_id];
			}
			
			while ($row = $this->fetchrow($query_id))
			{
				if (false !== $field)
				{
					$result[$row[$field]] = $row;
				}
				else
				{
					$result[] = $row;
				}
			}

			return $result;
		}

		return false;
	}
	
	/**
	* Извлечение поля
	* Если rownum = false, то используется текущая строка (по умолчанию: 0)
	*/
	public function fetchfield($field, $rownum = false, $query_id = false)
	{
		$query_id = false === $query_id ? $this->query_result : $query_id;
		
		if (false === $query_id)
		{
			return false;
		}

		if (false !== $rownum)
		{
			$this->rowseek($rownum, $query_id);
		}

		if (!is_object($query_id) && isset($this->cache_rowset[$query_id]))
		{
			if ($this->cache_row_pointer[$query_id] < sizeof($this->cache_rowset[$query_id]))
			{
				return isset($this->cache_rowset[$query_id][$this->cache_row_pointer[$query_id]][$field]) ? $this->cache_rowset[$query_id][$this->cache_row_pointer[$query_id]++][$field] : false;
			}
			
			return false;
		}

		$row = $this->fetchrow($query_id);
		
		return isset($row[$field]) ? $row[$field] : false;
	}

	/**
	* Выборка
	*/
	public function fetchrow($query_id = false)
	{
		$query_id = false === $query_id ? $this->query_result : $query_id;

		if (!is_object($query_id) && isset($this->cache_rowset[$query_id]))
		{
			if ($this->cache_row_pointer[$query_id] < sizeof($this->cache_rowset[$query_id]))
			{
				return $this->cache_rowset[$query_id][$this->cache_row_pointer[$query_id]++];
			}
		
			return false;
		}
		
		if (false !== $query_id)
		{
			$result = mysqli_fetch_assoc($query_id);
			return $result !== null ? $result : false;
		}
		
		return false;
	}

	/**
	* Освобождение памяти
	*/
	public function freeresult($query_id = false)
	{
		$query_id = false === $query_id ? $this->query_result : $query_id;

		if (!is_object($query_id))
		{
			if (!isset($this->cache_rowset[$query_id]))
			{
				return false;
			}
		
			unset($this->cache_rowset[$query_id]);
			unset($this->cache_row_pointer[$query_id]);
		
			return true;
		}
		
		return mysqli_free_result($query_id);
	}

	/**
	* Ряд значений
	*/
	public function in_set($field, $array, $negate = false, $allow_empty_set = false)
	{
		if (!sizeof($array))
		{
			if (!$allow_empty_set)
			{
				// Print the backtrace to help identifying the location of the problematic code
				$this->error('No values specified for SQL IN comparison');
			}
			else
			{
				// NOT IN () actually means everything so use a tautology
				if ($negate)
				{
					return '1=1';
				}
				// IN () actually means nothing so use a contradiction
				else
				{
					return '1=0';
				}
			}
		}

		if (!is_array($array))
		{
			$array = [$array];
		}

		if (sizeof($array) == 1)
		{
			@reset($array);
			$var = current($array);

			return $field . ($negate ? ' <> ' : ' = ') . $this->check_value($var);
		}
		else
		{
			return $field . ($negate ? ' NOT IN ' : ' IN ') . '(' . implode(', ', array_map([$this, 'check_value'], $array)) . ')';
		}
	}

	/**
	* ID последнего добавленного элемента
	*/
	public function insert_id()
	{
		return $this->connect_id ? mysqli_insert_id($this->connect_id) : false;
	}

	/**
	* Экранирование LIKE запроса
	*/
	public function like_expression($expression)
	{
		$expression = str_replace(['_', '%'], ["\_", "\%"], $expression);
		$expression = str_replace([chr(0) . "\_", chr(0) . "\%"], ['_', '%'], $expression);

		return 'LIKE \'%' . $this->escape($expression) . '%\'';
	}
	
	/**
	* Вставка более одной записи одновременно
	* Есть поддержка синтаксиса INSERT .. ON DUPLICATE KEY UPDATE
	*/
	public function multi_insert($table, &$sql_ary, $on_duplicate_action = '')
	{
		if (!sizeof($sql_ary))
		{
			return false;
		}
		
		$ary = [];
		
		foreach ($sql_ary as $id => $_sql_ary)
		{
			if (!is_array($_sql_ary))
			{
				return $this->query('INSERT INTO ' . $table . ' ' . $this->build_array('INSERT', $sql_ary) . ($on_duplicate_action ? ' ON DUPLICATE KEY UPDATE ' . $on_duplicate_action : ''));
			}
			
			$values = [];
			
			foreach ($_sql_ary as $key => $var)
			{
				$values[] = $this->check_value($var);
			}
			
			$ary[] = '(' . implode(', ', $values) . ')';
		}
		
		return $this->query('INSERT INTO ' . $table . ' (' . implode(', ', array_keys($sql_ary[0])) . ') VALUES ' . implode(', ', $ary) . ($on_duplicate_action ? ' ON DUPLICATE KEY UPDATE ' . $on_duplicate_action : ''));
	}
	
	/**
	* Количество запросов
	*/
	public function num_queries($cached = false)
	{
		return $cached ? $this->num_queries['cached'] : $this->num_queries['normal'];
	}

	/**
	* Выполнение запроса к БД
	*/
	public function query($query = '', array $params = [], $cache_ttl = 0)
	{
		if (!$this->connect_id)
		{
			$this->connect();
		}
		
		if (!$query)
		{
			return false;
		}
		
		if (!empty($params))
		{
			$named_placeholders = $named_params = $question_placeholders = $question_params = [];
			$named_ary = $question_ary = [];
			$i = 0;

			/**
			* SELECT * FROM table WHERE id = ? AND title = ?
			* превращается в
			* SELECT * FROM table WHERE id = $0 AND title $1
			*/
			while (false !== $pos = strpos($query, '?'))
			{
				$query = substr_replace($query, '$' . $i, $pos, 1);
				$question_ary['$' . $i] = $this->check_value($params[$i]);
				$i++;
			}
		
			foreach ($params as $key => $value)
			{
				if (!is_numeric($key))
				{
					/* Именованные параметры не экранируются */
					$named_ary[$key] = $value;
				}
			}
			
			/* $0 => value1, $1 => $value2 */
			$query = str_replace(array_keys($question_ary), array_values($question_ary), $query);
			
			/* :param1 => value1, :param2 => value2 */
			$query = str_replace(array_keys($named_ary), array_values($named_ary), $query);
		}
		
		$start_time = microtime(true);
		$this->query_result = false;
		
		if ($cache_ttl && is_object($this->cache))
		{
			$cache_key = md5(preg_replace('#[\n\r\s\t]+#', ' ', $query));
			$query_id  = sizeof($this->cache_rowset);
	
			if (false !== $result = $this->cache->get("sql_{$cache_key}"))
			{
				$this->cache_rowset[$query_id] = $result;
				$this->cache_row_pointer[$query_id] = 0;
				$this->add_num_queries(true);
				
				if (is_object($this->profiler))
				{
					$this->profiler->log_query($query, $start_time, true);
				}
				
				return $this->query_result = $query_id;
			}
		}
		
		if (false === $this->query_result = mysqli_query($this->connect_id, $query))
		{
			$this->error($query);
		}
		
		if ($cache_ttl && is_object($this->cache))
		{
			$cache_key = md5(preg_replace('#[\n\r\s\t]+#', ' ', $query));
			$query_id  = sizeof($this->cache_rowset);
			
			$this->cache_rowset[$query_id] = $this->fetchall($this->query_result);
			$this->cache_row_pointer[$query_id] = 0;
			$this->freeresult($this->query_result);
			$this->cache->set("sql_{$cache_key}", $this->cache_rowset[$query_id], $cache_ttl);
			$this->query_result = $query_id;
		}
		
		$this->add_num_queries();
		
		if (is_object($this->profiler))
		{
			$this->profiler->log_query($query, $start_time);
		}

		return $this->query_result;
	}
	
	public function query_limit($query, array $params = [], $on_page, $offset = 0, $cache_ttl = 0)
	{
		if (empty($query))
		{
			return false;
		}
		
		$on_page = max(0, $on_page);
		$offset = max(0, $offset);
		
		$this->query_result = false;
		
		/* 0 = нет лимита */
		if ($on_page == 0)
		{
			/**
			* -1 уже нельзя
			* Приходится использовать большое число
			*/
			$on_page = '18446744073709551615';
		}
		
		$query .= "\n LIMIT " . (!empty($offset) ? $offset . ', ' . $on_page : $on_page);
		
		return $this->query($query, $params, $cache_ttl);
	}
	
	/**
	* Перемещение к определенной строке
	*/
	public function rowseek($rownum, &$query_id)
	{
		$query_id = false === $query_id ? $this->query_result : $query_id;

		if (!is_object($query_id) && isset($this->cache_rowset[$query_id]))
		{
			if ($rownum >= sizeof($this->cache_rowset[$query_id]))
			{
				return false;
			}
		
			$this->cache_row_pointer[$query_id] = $rownum;
		
			return true;
		}

		return false !== $query_id ? mysqli_data_seek($query_id, $rownum) : false;
	}
	

	/**
	* Число запросов к БД (для отладки)
	*/
	public function total_queries()
	{
		return $this->num_queries['total'];
	}

	/**
	* Транзакции
	*/
	public function transaction($status = 'begin')
	{
		if (!$this->connect_id)
		{
			$this->connect();
		}
		
		switch ($status)
		{
			case 'begin':

				if ($this->transaction)
				{
					$this->transactions++;
					return true;
				}

				if (false == $result = mysqli_autocommit($this->connect_id, false))
				{
					$this->error();
				}

				$this->transaction = true;

			break;
			case 'commit':

				if ($this->transaction && $this->transactions)
				{
					$this->transactions--;
					return true;
				}

				if (!$this->transaction)
				{
					return false;
				}

				$result = mysqli_commit($this->connect_id);
				mysqli_autocommit($this->connect_id, true);

				if (!$result)
				{
					$this->error();
				}

				$this->transaction = false;
				$this->transactions = 0;

			break;
			case 'rollback':

				$result = mysqli_rollback($this->connect_id);
				mysqli_autocommit($this->connect_id, true);
				$this->transaction = false;
				$this->transactions = 0;

			break;
		}
		
		return $result;
	}
	
	/**
	* Увеличение счетчика запросов
	*/
	protected function add_num_queries($cached = false)
	{
		$this->num_queries['cached'] += false !== $cached ? 1 : 0;
		$this->num_queries['normal'] += false !== $cached ? 0 : 1;
		$this->num_queries['total']++;
	}

	/**
	* Установка подключения к БД
	*/
	protected function connect()
	{
		$this->connect_id = mysqli_connect($this->options['host'], $this->options['user'], $this->options['pass'], $this->options['name'], $this->options['port'], $this->options['sock']);
		$this->options['pass'] = '';

		return $this->connect_id && $this->options['name'] ? $this->connect_id : $this->error();
	}

	/**
	* SQL ошибки передаём нашему обработчику
	*/
	protected function error($sql = '')
	{
		global $error_ary;

		$code    = $this->connect_id ? mysqli_errno($this->connect_id) : mysqli_connect_errno();
		$message = $this->connect_id ? mysqli_error($this->connect_id) : mysqli_connect_error();
		
		if (!defined('IN_SQL_ERROR'))
		{
			define('IN_SQL_ERROR', true);
		}
		
		/* Подсветка ключевых слов */
		$sql = preg_replace('#(SELECT|INSERT INTO|UPDATE|SET|DELETE|FROM|LEFT JOIN|WHERE|AND|GROUP BY|ORDER BY|LIMIT|AS|ON)#', '<em>${1}</em>', $sql);

		$error_ary = [
			'code' => $code,
			'sql'  => $sql,
			'text' => $message
		];

		if ($this->transaction)
		{
			$this->transaction('rollback');
		}
		
		/**
		* Автоматическое исправление таблиц
		*/
		if ($code === 145)
		{
			if (preg_match("#Table '.+/(.+)' is marked as crashed and should be repaired#", $message, $matches))
			{
				$this->query('REPAIR TABLE ' . $matches[1]);
			}
			elseif (preg_match("#Can't open file: '(.+).MY[ID]'\.? \(errno: 145\)#", $message, $matches))
			{
				$this->query('REPAIR TABLE ' . $matches[1]);
			}
		}
		
		trigger_error(false, E_USER_ERROR);

		return $result;
	}
}
