<?php
/**
* @package fw
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
		
		$request = $this->http_client->get($url);
		$request->getCurlOptions()->set(CURLOPT_CONNECTTIMEOUT, $timeout);
		
		return simplexml_load_string($this->http_client->send($request)->getBody(), 'SimpleXMLElement', LIBXML_NOCDATA);
	}
}
