<?php namespace fw\traits;

trait breadcrumbs
{
	/**
	* Навигационная ссылка
	*
	* @param	string	$text	Название страницы
	* @param	string	$url	Ссылка на страницу
	* @param	string	$image	Изображение
	*/
	public function breadcrumbs($text, $url = '', $image = false)
	{
		$this->template->append('breadcrumbs', [
			'IMAGE' => $image,
			'TEXT'  => $text,
			'URL'   => $url,
		]);
	}
}
