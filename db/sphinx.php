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
		$this->connect_id = mysqli_connect($this->server, '', '', '', $this->port, $this->socket);

		return $this->connect_id ? $this->connect_id : $this->error();
	}
}
