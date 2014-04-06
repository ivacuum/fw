<?php namespace fw\modules\acp;

use app\models\page;

class sites extends page
{
	protected $edit_url_params = ['site_id'];
	protected $sites;
	
	public function _setup()
	{
		parent::_setup();
		
		$this->sites = $this->getApi('Sites');
	}
	
	public function index()
	{
		$this->template->assign('entries', $this->sites->get());
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
		
		$this->sites->add($this->form->getFieldsValues());
		
		if ($this->request->is_set_post('submit')) {
			$this->request->redirect(ilink($this->get_handler_url('index')));
		}
	}
	
	public function delete($id)
	{
		$row = $this->sites->getById($id);
		
		$this->form->addForm([
			'title'         => '',
			'alias'         => 'custom',
			'action'        => ilink($this->url),
			'action_cancel' => ilink($this->get_handler_url('index')),
			'cancel_text'   => 'Отмена',
			'submit_class'  => 'btn btn-danger',
			'submit_text'   => 'Удалить сайт',
		])->appendTemplate();
		
		$this->template->assign([
			'data_to_delete' => $this->sites->getDataToDelete($id),
			'entry_title'    => $row['site_title'],
		]);
	}
	
	public function delete_post($id)
	{
		$this->sites->delete($id);
		$this->request->redirect(ilink($this->get_handler_url('index')));
	}
	
	public function edit($id)
	{
		try {
			$row = $this->sites->getById($id);
		} catch (Exception $e) {
			trigger_error($e->getMessage());
		}
		
		$this->getEditForm()
			->bindData($row)
			->appendTemplate();
	}
	
	public function edit_post($id)
	{
		$row = $this->sites->getById($id);
		
		$this->getEditForm()
			->bindData($row)
			->bindRequest()
			->validate()
			->appendTemplate();
		
		if (!$this->form->is_valid) {
			return;
		}
		
		$this->sites->update($id, $this->form->getFieldsValues());
		
		if ($this->request->is_set_post('submit')) {
			$this->request->redirect(ilink($this->get_handler_url('index')));
		}
	}
	
	protected function getEditForm()
	{
		return $this->form->addForm([
			'title'         => 'Редактирование сайта',
			'alias'         => 'custom',
			'action'        => ilink($this->url),
			'class'         => 'form-horizontal',
			'action_cancel' => ilink($this->get_handler_url('index')),
			'action_save'   => ilink($this->url),
		])->addField([
			'type'  => 'text',
			'title' => 'Название сайта',
			'alias' => 'site_title',
		])->addField([
			'type'        => 'text',
			'title'       => 'Адрес сайта',
			'alias'       => 'site_url',
			'required'    => true,
			'placeholder' => 'example.com',
		])->addField([
			'type'        => 'text',
			'title'       => 'Алиасы',
			'alias'       => 'site_aliases',
			'placeholder' => 'subdomain.example.com',
		])->addField([
			'type'        => 'text',
			'title'       => 'Язык',
			'alias'       => 'site_language',
			'placeholder' => 'ru',
		])->addField([
			'type'        => 'text',
			'title'       => 'Локаль',
			'alias'       => 'site_locale',
			'placeholder' => 'ru_RU.UTF-8'
		])->addField([
			'type'  => 'checkbox',
			'title' => 'Локализация по умолчанию',
			'alias' => 'site_default'
		]);
	}
}
