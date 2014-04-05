<?php namespace fw\Api;

use fw\Traits\Injection;

abstract class AbstractApi
{
	use Injection;

	abstract public function add(array $row);
	abstract public function delete($id);
	abstract public function get(array $filter = []);
	abstract public function getCount(array $filter = []);
	abstract public function update($id, array $row);
	abstract protected function processFilterParams(array $f);
	abstract protected function processInput(array $row);
}
