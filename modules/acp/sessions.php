<?php
/**
* @package fw
* @copyright (c) 2013
*/

namespace fw\modules\acp;

use app\models\page;

class sessions extends page
{
	public function index()
	{
		$sql = 'SELECT * FROM site_sessions ORDER BY site_time DESC';
		$this->db->query($sql);
		$this->template->assign('entries', $this->db->fetchall());
		$this->db->freeresult();
	}
	
	public function delete()
	{
	}
	
	public function edit()
	{
	}
}
