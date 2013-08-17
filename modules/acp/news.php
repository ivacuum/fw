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
		$this->get_edit_form()
			->append_template();
	}
	
	public function delete($id)
	{
	}
	
	public function edit($id)
	{
		$row = $this->get_news_data($id);
		
		$this->get_edit_form()
			->bind_data($row)
			->append_template();
	}
	
	protected function get_edit_form()
	{
		return $this->form->add_form(['title' => '', 'alias' => 'custom', 'action' => ilink($this->url), 'class' => 'form-horizontal'])
			->add_field(['type' => 'text', 'title' => 'Заголовок', 'alias' => 'news_subject'])
			->add_field(['type' => 'texteditor', 'title' => 'Текст новости', 'alias' => 'news_text', 'height' => '30em']);
	}
	
	protected function get_entries_count()
	{
		$sql = 'SELECT COUNT(*) AS total FROM site_news WHERE site_id = ?';
		$this->db->query($sql, [$this->site_id]);
		$total = (int) $this->db->fetchfield('total');
		$this->db->freeresult();
		
		return $total;
	}
	
	protected function get_news_data($id)
	{
		$sql = 'SELECT * FROM site_news WHERE news_id = ? AND site_id = ?';
		$this->db->query($sql, [$id, $this->site_id]);
		$row = $this->db->fetchrow();
		$this->db->freeresult();
		
		if (!$row)
		{
			trigger_error('NEWS_NOT_FOUND');
		}
		
		return $row;
	}
}
