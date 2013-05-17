<?php
/**
* @package fw
* @copyright (c) 2013
*/

namespace fw\cron\tasks;

use Guzzle\Http\Client;
use fw\cron\task;

/**
* Обработка RSS
*/
class rss extends task
{
	public function get_rss_xml_data($url, $timeout = false)
	{
		$timeout = $timeout !== false ? intval($timeout) : $this->config['cron.rss_timeout'];
		
		$client = new Client();
		$request = $client->get($url);
		$request->getCurlOptions()->set(CURLOPT_CONNECTTIMEOUT, $timeout);
		
		$this->log("Запрос RSS: {$url}");
		
		return simplexml_load_string($client->send($request)->getBody(), 'SimpleXMLElement', LIBXML_NOCDATA);
	}
}
