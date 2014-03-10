<?php

require_once dirname(__FILE__) . '/../../engine/sessions.php';

class user_lang_test extends PHPUnit_Framework_TestCase
{
	public function test_user_lang_sprintf()
	{
		$user = new session();
		$user->lang = [
			'TEST'   => 'BRO',
			'STR'    => 'There are %d users %s.',
			'EMPTY'  => '',
			'ZERO'   => 0
		];
		
		/* Существующие элементы */
		$this->assertEquals($user->lang('TEST'), 'BRO');
		$this->assertEquals($user->lang('EMPTY'), '');
		$this->assertEquals($user->lang('ZERO'), 0);
		
		/* Несуществующий элемент */
		$this->assertEquals($user->lang('VOID'), 'VOID');
		
		/* С параметрами (избыточны) */
		$this->assertEquals($user->lang('TEST', 1, 2, 3), 'BRO');
		$this->assertEquals($user->lang('TEST', 'TEXT'), 'BRO');
		$this->assertEquals($user->lang('TEST', 1, 'ADMIN'), 'BRO');
		
		/* С параметрами (к месту) */
		$this->assertEquals($user->lang('STR', 25, 'onboard'), 'There are 25 users onboard.');
		$this->assertEquals($user->lang('STR', 2, 'away right now'), 'There are 2 users away right now.');
	}
}
