<?php namespace fw\form\field;

/**
* Текстовое поле
*/
class texteditor extends generic
{
	public function set_value($value)
	{
		/* Визуальному редактору разрешено присылать html-код */
		$this->data['value'] = htmlspecialchars_decode($value, ENT_COMPAT);
	}
	
	public function validate()
	{
		if ($this->data['field_required'] && !$this->data['value']) {
			return false;
		}
		
		return true;
	}
}
