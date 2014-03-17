<?php namespace fw\modules\acp;

use app\models\page;

class sites extends page
{
	protected $edit_url_params = ['site_id'];
	protected $delete_tables = ['site_config', 'site_cron', 'site_i18n', 'site_news', 'site_pages'];
	
	public function index()
	{
		$sql = 'SELECT * FROM site_sites ORDER BY site_url ASC, site_language ASC';
		$this->db->query($sql);
		$this->template->assign('entries', $this->db->fetchall());
		$this->db->freeresult();
	}
	
	public function add()
	{
		$this->get_edit_form()
			->append_template();
	}
	
	public function add_post()
	{
		$this->get_edit_form()
			->bind_request()
			->validate()
			->append_template();
		
		if ($this->form->is_valid) {
			$sql = 'INSERT INTO site_sites ' . $this->db->build_array('INSERT', $this->form->get_fields_values());
			$this->db->query($sql);
			$this->purge_cache();
			$this->request->redirect(ilink($this->get_handler_url('index')));
		}
	}
	
	public function delete($id)
	{
		$row = $this->get_site_data($id);
		
		$this->form->add_form([
			'title'         => '',
			'alias'         => 'custom',
			'action'        => ilink($this->url),
			'action_cancel' => ilink($this->get_handler_url('index')),
			'cancel_text'   => 'Отмена',
			'submit_class'  => 'btn btn-danger',
			'submit_text'   => 'Удалить сайт',
		])->append_template();
		
		$data_to_delete = [];
		
		foreach ($this->delete_tables as $table) {
			$sql = 'SELECT COUNT(*) AS total FROM :table WHERE site_id = ?';
			$this->db->query($sql, [$id, ':table' => $table]);
			$data_to_delete[$table] = $this->db->fetchfield('total');
			$this->db->freeresult();
		}
		
		$data_to_delete = array_filter($data_to_delete, function ($value) {
			return !empty($value);
		});
		
		$this->template->assign([
			'data_to_delete' => $data_to_delete,
			'entry_title'    => $row['site_title'],
		]);
	}
	
	public function delete_post($id)
	{
		/* Проверка существования сайта */
		$row = $this->get_site_data($id);
		
		$this->db->transaction('begin');
		$this->delete_tables[] = 'site_sites';
		
		foreach ($this->delete_tables as $table) {
			$sql = 'DELETE FROM :table WHERE site_id = ?';
			$this->db->query($sql, [$id, ':table' => $table]);
		}

		$this->purge_cache();
		$this->db->transaction('commit');
		$this->request->redirect(ilink($this->get_handler_url('index')));
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
		
		if ($this->form->is_valid) {
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
			->add_field(['type' => 'text', 'title' => 'Адрес сайта', 'alias' => 'site_url', 'required' => true, 'placeholder' => 'example.com'])
			->add_field(['type' => 'text', 'title' => 'Алиасы', 'alias' => 'site_aliases', 'placeholder' => 'subdomain.example.com'])
			->add_field(['type' => 'text', 'title' => 'Язык', 'alias' => 'site_language', 'placeholder' => 'ru'])
			->add_field(['type' => 'text', 'title' => 'Локаль', 'alias' => 'site_locale', 'placeholder' => 'ru_RU.UTF-8'])
			->add_field(['type' => 'checkbox', 'title' => 'Локализация по умолчанию', 'alias' => 'site_default']);
	}
	
	protected function get_site_data($id)
	{
		$sql = 'SELECT * FROM site_sites WHERE site_id = ?';
		$this->db->query($sql, [$id]);
		$row = $this->db->fetchrow();
		$this->db->freeresult();
		
		if (!$row) {
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
