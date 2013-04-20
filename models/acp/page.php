<?php
/**
* @package fw
* @copyright (c) 2013
*/

namespace fw\models\acp;

use fw\models\page as base_page;

class page extends base_page
{
	protected $site_id = 1;
	
	public function _setup()
	{
		if (!$this->auth->acl_get('a_'))
		{
			trigger_error('ERR_NO_AUTH');
		}
		
		$this->site_id = $this->request->cookie("{$this->config['cookie.prefix']}site_id", 1);
		
		$this->template->assign([
			'sites' => $this->cache->obtain_sites(),
			
			'S_SITE_ID' => $this->site_id,
		]);
	}
}
