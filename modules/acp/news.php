<?php
/**
* @package fw
* @copyright (c) 2013
*/

namespace fw\modules\acp;

use app\models\page;

class news extends page
{
	public function index()
	{
		$sql = 'SELECT * FROM site_news WHERE site_id = ? ORDER BY news_time DESC';
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
