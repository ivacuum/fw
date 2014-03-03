<?php
/**
* @package fw
* @copyright (c) 2014
*/

namespace fw\modules\acp;

use app\models\page;

class sessions extends page
{
	protected $edit_url_params = ['session_id'];

	public function index()
	{
		$sql = 'SELECT * FROM site_sessions ORDER BY session_time DESC';
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
