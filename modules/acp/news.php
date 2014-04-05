<?php namespace fw\modules\acp;

use app\models\page;

class news extends page
{
	protected $edit_url_params = ['news_id'];
	protected $news;
	
	public function _setup()
	{
		parent::_setup();
		
		$this->news = $this->getApi('News', $this->site_id);
	}
	
	public function index()
	{
		$on_page = 20;
		
		$this->template->assign('entries', $this->news->get(compact('on_page')));
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
		
		if (!$this->form->is_valid) {
			return;
		}

		$this->news->add($this->form->get_fields_values());
		
		if ($this->request->is_set_post('submit')) {
			$this->request->redirect(ilink($this->get_handler_url('index')));
		}
	}
	
	public function delete($id)
	{
		$row = $this->news->getById($id);
		
		$this->form->add_form([
			'title'         => '',
			'alias'         => 'custom',
			'action'        => ilink($this->url),
			'action_cancel' => ilink($this->get_handler_url('index')),
			'cancel_text'   => 'Отмена',
			'submit_class'  => 'btn btn-danger',
			'submit_text'   => 'Удалить новость',
		])->append_template();
		
		$this->template->assign('entry_title', $row['news_subject']);
	}
	
	public function delete_post($id)
	{
		$this->news->delete($id);
		$this->request->redirect(ilink($this->get_handler_url('index')));
	}
	
	public function edit($id)
	{
		try {
			$row = $this->news->getById($id);
		} catch (Exception $e) {
			trigger_error($e->getMessage());
		}
		
		$this->get_edit_form()
			->bind_data($row)
			->append_template();
	}
	
	public function edit_post($id)
	{
		$row = $this->news->getById($id);
		
		$this->get_edit_form()
			->bind_data($row)
			->bind_request()
			->validate()
			->append_template();
		
		if (!$this->form->is_valid) {
			return;
		}
		
		$this->news->update($id, $this->form->get_fields_values());

		if ($this->request->is_set_post('submit')) {
			$this->request->redirect(ilink($this->get_handler_url('index')));
		}
	}
	
	protected function get_edit_form()
	{
		return $this->form->add_form([
			'title'         => '',
			'alias'         => 'custom',
			'action'        => ilink($this->url),
			'class'         => 'form-horizontal',
			'action_cancel' => ilink($this->get_handler_url('index')),
			'action_save'   => ilink($this->url),
		])->add_field([
			'type'  => 'text',
			'title' => 'Заголовок',
			'alias' => 'news_subject',
		])->add_field([
			'type' => 'date',
			'title' => 'Дата публикации',
			'alias' => 'news_time',
		])->add_field([
			'type'  => 'text',
			'title' => 'URL',
			'alias' => 'news_url',
		])->add_field([
			'type'   => 'texteditor',
			'title'  => 'Текст новости',
			'alias'  => 'news_text',
			'height' => '30em',
		])->add_field([
			'type'  => 'hidden',
			'title' => 'Автор',
			'alias' => 'user_id',
			'value' => $this->user['user_id'],
		]);
	}
}
