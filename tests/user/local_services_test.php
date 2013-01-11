<?php
/**
* @package src.ivacuum.ru
* @copyright (c) 2012
*/

require_once(dirname(__FILE__) . '/../../engine/sessions.php');
require_once(dirname(__FILE__) . '/../../engine/functions.php');

class user_local_services_test extends PHPUnit_Framework_TestCase
{
	public function test_download_link()
	{
		$user = new session();

		/* Локальные ссылки */
		$user->isp = 'local';
		$this->assertEquals($user->download_link(1), '//dl.local.ivacuum.ru/1/');
		
		$user->isp = 'beeline-kaluga';
		$this->assertEquals($user->download_link(2), '//dl.local.ivacuum.ru/2/');
		
		$user->isp = 'corbina-kaluga';
		$this->assertEquals($user->download_link(3), '//dl.local.ivacuum.ru/3/');
		
		/* Внешние ссылки */
		$user->isp = 'internet';
		$this->assertEquals($user->download_link(1), '//dl.ivacuum.ru/1/');
		
		$user->isp = 'hz-telecom';
		$this->assertEquals($user->download_link(2), '//dl.ivacuum.ru/2/');
	}
	
	public function data_download_link()
	{
		return array(
			/* Локальные ссылки */
			array('//dl.local.ivacuum.ru/1/', 'local', 1),
			array('//dl.local.ivacuum.ru/2/', 'beeline-kaluga', 2),
			array('//dl.local.ivacuum.ru/3/', 'corbina-kaluga', 3),
			array('//dl.local.ivacuum.ru/4/', 'vacuum', 4),
			
			/* Внешние ссылки */
			array('//dl.ivacuum.ru/1/', 'internet', 1),
			array('//dl.ivacuum.ru/2/', 'hz-telecom', 2)
		);
	}
}
