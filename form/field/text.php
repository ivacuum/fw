<?php namespace fw\form\field;

/**
* Текстовое поле
*/
class text extends generic
{
	/**
	* required
	* disabled
	* readonly
	* trim
	* rounding_mode
	* precision
	* always_empty
	* default_protocol
	*/
	public function validate()
	{
		if ($this->data['field_disabled'] || $this->data['field_readonly']) {
			return true;
		}
		
		if ($this->data['field_required'] && !$this->data['value']) {
			return false;
		}
		
		return true;
	}
}
