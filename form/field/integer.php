<?php
/**
* @package ivacuum.fw
* @copyright (c) 2013
*/

namespace fw\form\field;

/**
* Число
*/
class integer extends number
{
	public function set_value($value)
	{
		parent::set_value($value);
		
		$this->data['value'] = (int) $this->data['value'];
	}
	
	protected function fill_default_data($data)
	{
		return $data;
	}
}
