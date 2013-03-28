<?php
/**
* @package fw
* @copyright (c) 2013
*/

namespace fw\traits;

trait constants
{
	/**
	* Загрузка констант
	*/
	public function load_constants($prefix)
	{
		if (!function_exists('apc_fetch'))
		{
			return false;
		}

		return apc_load_constants("{$prefix}_constants");
	}

	/**
	* Установка констант
	*/
	public function set_constants($prefix, $constants)
	{
		if (!function_exists('apc_fetch'))
		{
			foreach ($constants as $key => $value)
			{
				define($key, $value);
			}
		
			return;
		}
	
		apc_define_constants("{$prefix}_constants", $constants);
	}
}
