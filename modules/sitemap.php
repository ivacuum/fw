<?php
/**
* @package fw
* @copyright (c) 2014
*/

namespace fw\modules;

use app\models\page;
use fw\helpers\traverse\tree\site_pages;

class sitemap extends page
{
	public function index()
	{
		$sql = '
			SELECT
				page_id,
				site_id,
				parent_id,
				left_id,
				right_id,
				is_dir,
				page_enabled,
				page_name,
				page_url,
				page_noindex,
				page_image
			FROM
				site_pages
			WHERE
				site_id = ?
			ORDER BY
				left_id ASC';
		$this->db->query($sql, [$this->data['site_id']]);
	}

	public function index_html()
	{
		$traversal = new traverse_sitemap_pages_html([
			'default_extension' => $this->options['default_extension'],
			'directory_index'   => $this->options['directory_index'],
			'return_as_tree'    => true,
		]);
		
		while ($row = $this->db->fetchrow()) {
			$traversal->process_node($row);
		}
		
		$this->db->freeresult();
		$this->template->assign('pages', $traversal->get_tree_data());
	}
	
	public function index_xml()
	{
		$traversal = new traverse_sitemap_pages_xml([
			'default_extension' => $this->options['default_extension'],
			'directory_index'   => $this->options['directory_index'],
		]);
		
		while ($row = $this->db->fetchrow()) {
			$traversal->process_node($row);
		}
		
		$this->db->freeresult();
		$this->template->assign('pages', $traversal->get_tree_data());
	}
}

/**
* Обход дерева страниц для построения карты сайта
*/
class traverse_sitemap_pages_html extends site_pages
{
	protected function get_data()
	{
		return [
			'ID'       => $this->row['page_id'],
			'IMAGE'    => $this->row['page_image'],
			'TITLE'    => $this->row['page_name'],
			'URL'      => parent::get_data()['url'],
			'children' => [],
		];
	}

	/**
	* В карту сайта попадают только включенные и индексируемые страницы
	*/
	protected function skip_condition()
	{
		return !$this->row['page_enabled'] || $this->row['page_noindex'] || $this->row['page_url'] == '*';
	}
}

/**
* Обход дерева страниц для построения карты сайта
*/
class traverse_sitemap_pages_xml extends site_pages
{
	protected function get_data()
	{
		/**
		* Пропуск индексных страниц, чтобы одна и та же страница
		* не отображалась в карте сайта дважды, например:
		*
		* /новости/ и /новости/index.html
		*
		* Исключение: главная страница сайта
		*/
		if (!$this->row['is_dir'] && $this->row['page_url'] == $this->options['directory_index']) {
			$this->base_url[] = '';
			
			if (!$this->row['parent_id']) {
				return ilink();
			}
			
			return false;
		}
		
		/* Карта сайта не может содержать кириллические символы */
		$this->row['page_url'] = urlencode($this->row['page_url']);
		
		return parent::get_data();
	}
	
	/**
	* В карту сайта попадают только включенные и индексируемые страницы
	*/
	protected function skip_condition()
	{
		return !$this->row['page_enabled'] || $this->row['page_noindex'] || $this->row['page_url'] == '*';
	}
}
