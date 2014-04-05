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
		$this->getEditForm()
			->appendTemplate();
	}
	
	public function add_post()
	{
		$this->getEditForm()
			->bindRequest()
			->validate()
			->appendTemplate();
		
		if (!$this->form->is_valid) {
			return;
		}

		$this->news->add($this->form->getFieldsValues());
		
		if ($this->request->is_set_post('submit')) {
			$this->request->redirect(ilink($this->get_handler_url('index')));
		}
	}
	
	public function delete($id)
	{
		$row = $this->news->getById($id);
		
		$this->form->addForm([
			'title'         => '',
			'alias'         => 'custom',
			'action'        => ilink($this->url),
			'action_cancel' => ilink($this->get_handler_url('index')),
			'cancel_text'   => 'Отмена',
			'submit_class'  => 'btn btn-danger',
			'submit_text'   => 'Удалить новость',
		])->appendTemplate();
		
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
		
		$this->getEditForm()
			->bindData($row)
			->appendTemplate();
	}
	
	public function edit_post($id)
	{
		$row = $this->news->getById($id);
		
		$this->getEditForm()
			->bindData($row)
			->bindRequest()
			->validate()
			->appendTemplate();
		
		if (!$this->form->is_valid) {
			return;
		}
		
		$this->news->update($id, $this->form->getFieldsValues());

		if ($this->request->is_set_post('submit')) {
			$this->request->redirect(ilink($this->get_handler_url('index')));
		}
	}
	
	protected function getEditForm()
	{
		return $this->form->addForm([
			'title'         => '',
			'alias'         => 'custom',
			'action'        => ilink($this->url),
			'class'         => 'form-horizontal',
			'action_cancel' => ilink($this->get_handler_url('index')),
			'action_save'   => ilink($this->url),
		])->addField([
			'type'     => 'text',
			'title'    => 'Заголовок',
			'alias'    => 'news_subject',
			'required' => true,
		])->addField([
			'type' => 'date',
			'title' => 'Дата публикации',
			'alias' => 'news_time',
		])->addField([
			'type'  => 'text',
			'title' => 'URL',
			'alias' => 'news_url',
		])->addField([
			'type'   => 'texteditor',
			'title'  => 'Текст новости',
			'alias'  => 'news_text',
			'height' => '30em',
		])->addField([
			'type'  => 'hidden',
			'title' => 'Автор',
			'alias' => 'user_id',
			'value' => $this->user['user_id'],
		]);
	}
}
