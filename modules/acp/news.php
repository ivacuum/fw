<?php
/**
* @package fw
* @copyright (c) 2013
*/

namespace fw\modules\acp;

use app\models\page;

class news extends page
{
	protected $edit_url_params = ['news_id'];

	public function index()
	{
		$pagination = pagination(20, $this->get_entries_count(), ilink($this->url));
		
		$sql = 'SELECT * FROM site_news WHERE site_id = ? ORDER BY news_time DESC';
		$this->db->query_limit($sql, [$this->site_id], $pagination['on_page'], $pagination['offset']);
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
	
	protected function get_entries_count()
	{
		$sql = 'SELECT COUNT(*) AS total FROM site_news WHERE site_id = ?';
		$this->db->query($sql, [$this->site_id]);
		$total = (int) $this->db->fetchfield('total');
		$this->db->freeresult();
		
		return $total;
	}
}
