<?php namespace fw\helpers\traverse\tree;

use fw\helpers\traverse\tree;

/**
* Обход страниц сайта
*/
class site_pages extends tree
{
	protected $base_url = [];
	protected $options = [
		'default_extension' => 'html',
		'directory_index'   => 'index',
		'return_as_tree'    => false,
	];
	
	public function get_pages_data($pages)
	{
		$this->process_nodes($pages);
		
		return $this->tree;
	}
	
	/**
	* Ссылка на страницу
	*/
	protected function get_data()
	{
		$this->base_url[] = $this->row['is_dir'] ? $this->row['page_url'] : ($this->row['page_url'] == $this->options['directory_index'] ? '' : ($this->options['default_extension'] ? sprintf('%s.%s', $this->row['page_url'], $this->options['default_extension']) : $this->row['page_url']));
		
		return $this->options['return_as_tree'] ? ['url' => ilink(implode('/', $this->base_url)), 'children' => []] : ilink(implode('/', $this->base_url));
	}
	
	/**
	* Возврат на уровень вверх
	*/
	protected function on_depth_decrease()
	{
		array_pop($this->base_url);
	}
	
	/**
	* Массив только включенных страниц
	*/
	protected function skip_condition()
	{
		return !$this->row['page_enabled'];
	}
}
