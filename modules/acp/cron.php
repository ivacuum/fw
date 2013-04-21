<?php
/**
* @package fw
* @copyright (c) 2013
*/

namespace fw\modules\acp;

use app\models\page;

class cron extends page
{
	public function index()
	{
		$sql = 'SELECT * FROM site_cron WHERE site_id = ? ORDER BY run_order ASC';
		$this->db->query($sql, [$this->site_id]);
		$this->template->assign('entries', $this->db->fetchall());
		$this->db->freeresult();
	}
	
	public function add()
	{
	}
	
	public function delete()
	{
	}
	
	public function edit()
	{
	}
}
