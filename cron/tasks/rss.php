<?php
/**
* @package ivacuum.fw
* @copyright (c) 2013
*/

namespace fw\cron\tasks;

use fw\cron\task;

/**
* Обработка RSS
*/
class rss extends task
{
	public function get_rss_xml_data($url, $timeout = false)
	{
		$timeout = $timeout !== false ? intval($timeout) : $this->config['cron.rss_timeout'];
		
		$c = curl_init($url);
		curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($c, CURLOPT_CONNECTTIMEOUT, $timeout);
		$result = curl_exec($c);
		curl_close($c);
		
		return simplexml_load_string($result, 'SimpleXMLElement', LIBXML_NOCDATA);
	}
}
