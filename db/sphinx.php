<?php
/**
* @package src.ivacuum.ru
* @copyright (c) 2012 vacuum
*/

namespace engine\db;

/**
* Класс работы со Sphinx по протоколу MySQL версии 4.1
*/
class sphinx
{
	/**
	* Глобальные переменные класса
	*/
	var $connect_id;
	var $query_result;
	var $total_queries = 0;
	var $transaction = false;
	var $transactions = 0;

	var $user = '';
	var $server = '';
	var $database = '';

	/**
	* Установка подключения к БД
	*/
	function connect($sqlserver, $port = false, $socket = '')
	{
		$this->server = $sqlserver;
		$port = ( $port === false ) ? null : $port;

		$this->connect_id = mysqli_connect($this->server, '', '', '', $port, $socket);

		return ( $this->connect_id ) ? $this->connect_id : $this->error();
	}

	/**
	* Закрытие текущего подключения
	*/
	function close()
	{
		if( !$this->connect_id )
		{
			return false;
		}

		return ( $this->connect_id ) ? mysqli_close($this->connect_id) : false;
	}

	/**
	* Число запросов к БД (для отладки)
	*/
	function total_queries()
	{
		return $this->total_queries;
	}

	/**
	* Выполнение запроса к БД
	*/
	function query($query = '')
	{
		if( $query )
		{
			$this->query_result = false;
			$this->total_queries++;

			if( $this->query_result === false )
			{
				if( ( $this->query_result = mysqli_query($this->connect_id, $query) ) === false )
				{
					$this->error($query);
				}
			}
		}
		else
		{
			return false;
		}

		return ( $this->query_result ) ? $this->query_result : false;
	}

	/**
	* Выборка
	*/
	function fetchrow($query_id = false)
	{
		if( $query_id === false )
		{
			$query_id = $this->query_result;
		}

		return ( $query_id !== false ) ? mysqli_fetch_assoc($query_id) : false;
	}

	/**
	* Заносим полученные данные в цифровой массив
	*
	* @param string $field Поле, по которому создавать массив
	*/
	function fetchall($query_id = false, $field = false)
	{
		if( $query_id === false )
		{
			$query_id = $this->query_result;
		}

		if( $query_id !== false )
		{
			$result = array();

			while( $row = $this->fetchrow($query_id) )
			{
				if( $field !== false )
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
	* Освобождение памяти
	*/
	function freeresult($query_id = false)
	{
		if( $query_id === false )
		{
			$query_id = $this->query_result;
		}

		return mysqli_free_result($query_id);
	}

	/**
	* Преобразование массива в строку
	* и выполнение запроса
	*/
	function build_array($query, $data = false)
	{
		if( !is_array($data) )
		{
			return false;
		}

		$fields = $values = array();

		if( $query == 'INSERT' )
		{
			foreach( $data as $key => $value )
			{
				$fields[] = $key;
				$values[] = $this->check_value($value);
			}

			$query = ' (' . implode(', ', $fields) . ') VALUES (' . implode(', ', $values) . ')';
		}
		elseif( $query == 'SELECT' || $query == 'UPDATE' )
		{
			foreach( $data as $key => $value )
			{
				$values[] = $key . ' = ' . $this->check_value($value);
			}

			$query = implode( ( $query == 'UPDATE' ) ? ', ' : ' AND ', $values);
		}

		return $query;
	}

	/**
	* Сверяем тим переменной и её значение,
	* строки также экранируем
	*/
	function check_value($value)
	{
		if( is_null($value) )
		{
			return 'NULL';
		}
		elseif( is_string($value) )
		{
			return "'" . $this->escape($value) . "'";
		}
		else
		{
			return ( is_bool($value) ) ? intval($value) : $value;
		}
	}

	/**
	* Экранирование LIKE запроса
	*/
	function like_expression($expression)
	{
		$expression = str_replace(array('_', '%'), array("\_", "\%"), $expression);
		$expression = str_replace(array(chr(0) . "\_", chr(0) . "\%"), array('_', '%'), $expression);

		return 'LIKE \'%' . $this->escape($expression) . '%\'';
	}

	/**
	* Затронутые поля
	*/
	function affected_rows()
	{
		return ( $this->connect_id ) ? mysqli_affected_rows($this->connect_id) : false;
	}

	/**
	* ID последнего добавленного элемента
	*/
	function insert_id()
	{
		return ( $this->connect_id ) ? mysqli_insert_id($this->connect_id) : false;
	}

	function in_set($field, $array, $negate = false, $allow_empty_set = false)
	{
		if( !sizeof($array) )
		{
			if( !$allow_empty_set )
			{
				// Print the backtrace to help identifying the location of the problematic code
				$this->error('No values specified for SQL IN comparison');
			}
			else
			{
				// NOT IN () actually means everything so use a tautology
				if( $negate )
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

		if( !is_array($array) )
		{
			$array = array($array);
		}

		if( sizeof($array) == 1 )
		{
			@reset($array);
			$var = current($array);

			return $field . ($negate ? ' <> ' : ' = ') . $this->check_value($var);
		}
		else
		{
			return $field . ($negate ? ' NOT IN ' : ' IN ') . '(' . implode(', ', array_map(array($this, 'check_value'), $array)) . ')';
		}
	}

	/**
	* Экранируем символы
	*/
	function escape($message)
	{
		return mysqli_real_escape_string($this->connect_id, $message);
	}

	/**
	* SQL ошибки передаём нашему обработчику
	*/
	function error($sql = '')
	{
		global $custom_error_style, $user;

		$code = ( $this->connect_id ) ? mysqli_errno($this->connect_id) : mysqli_connect_errno();
		$custom_error_style = true;
		$message = ( $this->connect_id ) ? mysqli_error($this->connect_id) : mysqli_connect_error();

		$message = '<br /><b style="color: red;">Ошибка SQL</b>:<br /><blockquote>Код ошибки: <b>' . $code . '</b>.<br />Текст ошибки: «<b>' . $message . '</b>».<br />';
		$message .= ( $sql ) ? '<br /><b>SQL запрос:</b> ' . htmlspecialchars($sql) . '<br />' : '';
		$message .= '</blockquote>';

		/**
		* Стандартные настройки сервера не позволяют выводить
		* сообщение об ошибке длиной более 1024 символов
		*/
		if( strlen($message) >= 1024 )
		{
			global $message_long_error;

			$message_long_error = $message;

			trigger_error(false, E_USER_ERROR);
		}

		trigger_error($message, E_USER_ERROR);

		return $result;
	}
}
