<?php
/**
* @package fw
* @copyright (c) 2013
*/

namespace fw\traits;

trait breadcrumbs
{
	/**
	* Навигационная ссылка
	*
	* @param	string	$url	Ссылка на страницу
	* @param	string	$text	Название страницы
	* @param	string	$image	Изображение
	*/
	public function breadcrumbs($url, $text, $image = false)
	{
		$this->template->append('nav_links', [
			'IMAGE' => $image,
			'TEXT'  => $text,
			'URL'   => $url
		]);
	}
}
