<?php
/**
* @package fw
* @copyright (c) 2013
*/

namespace fw\modules\acp;

use app\models\page;

class sites extends page
{
	protected $edit_url_params = ['site_id'];
	
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
	
	public function edit($id)
	{
		$row = $this->get_site_data($id);
		
		$this->get_edit_form()
			->bind_data($row)
			->append_template();
	}
	
	public function edit_post($id)
	{
		$row = $this->get_site_data($id);
		
		$this->get_edit_form()
			->bind_data($row)
			->bind_request()
			->validate()
			->append_template();
		
		if ($this->form->is_valid)
		{
			$sql = 'UPDATE site_sites SET :update_ary WHERE site_id = ?';
			$this->db->query($sql, [$id, ':update_ary' => $this->db->build_array('UPDATE', $this->form->get_fields_values())]);
			$this->purge_cache();
			$this->request->redirect(ilink($this->get_handler_url('index')));
		}
	}
	
	protected function get_edit_form()
	{
		return $this->form->add_form(['title' => "Редактирование сайта", 'alias' => 'custom', 'action' => ilink($this->url), 'class' => 'form-horizontal'])
			->add_field(['type' => 'text', 'title' => 'Название сайта', 'alias' => 'site_title'])
			->add_field(['type' => 'text', 'title' => 'Адрес сайта', 'alias' => 'site_url', 'required' => true])
			->add_field(['type' => 'text', 'title' => 'Алиасы', 'alias' => 'site_aliases'])
			->add_field(['type' => 'text', 'title' => 'Язык', 'alias' => 'site_language'])
			->add_field(['type' => 'text', 'title' => 'Локаль', 'alias' => 'site_locale'])
			->add_field(['type' => 'checkbox', 'title' => 'Локализация по умолчанию', 'alias' => 'site_default']);
	}
	
	protected function get_site_data($id)
	{
		$sql = 'SELECT * FROM site_sites WHERE site_id = ?';
		$this->db->query($sql, [$id]);
		$row = $this->db->fetchrow();
		$this->db->freeresult();
		
		if (!$row)
		{
			trigger_error('SITE_NOT_FOUND');
		}
		
		return $row;
	}
	
	protected function purge_cache()
	{
		$this->cache->delete_shared('hostnames');
		$this->cache->delete_shared('sites');
	}
}
