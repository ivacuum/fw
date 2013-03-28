<?php
/**
* @package ivacuum.fw
* @copyright (c) 2013
*/

namespace fw\form\field;

/**
* Флажок
*/
class checkbox extends generic
{
	public function get_default_value($is_bound = false)
	{
		/* Возвращаем 0, если флажок не был установлен при отправке формы */
		return $is_bound ? 0 : $this->data['field_value'];
	}
	
	public function validate()
	{
		if ($this->data['field_disabled'])
		{
			return true;
		}
		
		if ($this->data['field_required'] && !$this->data['value'])
		{
			return false;
		}
		
		return true;
	}
}
