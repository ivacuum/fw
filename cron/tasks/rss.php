<?php namespace fw\cron\tasks;

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
		
		try {
			$response = $request->send()->getBody();
		} catch (\Exception $e) {
			$this->log($e->getMessage());
			return false;
		}
		
		libxml_use_internal_errors(true);
		
		if (false === $response = simplexml_load_string($response, 'SimpleXMLElement', LIBXML_NOCDATA)) {
			foreach (libxml_get_errors() as $error) {
				$this->log($error->message);
			}
			
			libxml_clear_errors();
			return false;
		}
		
		return $response;
	}
}
