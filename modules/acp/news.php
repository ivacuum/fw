<?php
/**
* @package fw
* @copyright (c) 2014
*/

namespace fw\modules\acp;

use app\models\page;
use fw\models\news as news_model;

class news extends page
{
	protected $edit_url_params = ['news_id'];
	protected $news;
	
	public function _setup()
	{
		parent::_setup();
		
		$this->news = (new news_model($this->site_id))->_set_app($this->app);
	}
	
	public function index()
	{
		$this->template->assign('entries', $this->news->get_page(20, ilink($this->url)));
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
		try {
			$row = $this->news->get_by_id($id);
		} catch (Exception $e) {
			trigger_error($e->getMessage());
		}
		
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
}
