<?php
/**
* @package fw
* @copyright (c) 2014
*/

namespace fw\form\field;

/**
* Текстовое поле
*/
class textarea extends generic
{
	public function validate()
	{
		if ($this->data['field_required'] && !$this->data['value']) {
			return false;
		}
		
		return true;
	}
}
