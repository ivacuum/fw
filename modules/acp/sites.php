<?php
/**
* @package fw
* @copyright (c) 2013
*/

namespace fw\modules\acp;

use app\models\page;

class sites extends page
{
	public function index()
	{
		$sql = 'SELECT * FROM site_sites ORDER BY site_url ASC, site_language ASC';
		$this->db->query($sql);
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
