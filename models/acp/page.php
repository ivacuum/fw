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
	protected $site_info;
	
	public function _setup()
	{
		if (!$this->auth->acl_get('a_'))
		{
			trigger_error('ERR_NO_AUTH');
		}
		
		$this->site_id   = $this->request->cookie("{$this->config['cookie.prefix']}site_id", 1);
		$this->site_info = $this->cache->get_site_info_by_id($this->site_id);
		
		$this->template->assign([
			'site_info' => $this->site_info,
			'sites'     => $this->cache->obtain_sites(),
		]);
	}
	
	protected function get_entries_count()
	{
		return 0;
	}
}
