<?php
/**
* @package fw
* @copyright (c) 2013
*/

namespace fw\db;

/**
* Класс работы со Sphinx по протоколу MySQL версии 4.1
*/
class sphinx extends mysqli
{
	protected function connect()
	{
		$this->connect_id = mysqli_connect($this->options['host'], '', '', '', $this->options['port'], $this->options['sock']);

		return $this->connect_id ? $this->connect_id : $this->error();
	}
}
