<?php
/**
* @package fw
* @copyright (c) 2013
*/

namespace fw\traits;

trait i18n
{
	public $lang = [];

	/**
	* Возвращаем перевод
	* Если языковый элемент не найден, то возвращаем пустую строку
	*/
	public function lang()
	{
		$args = func_get_args();
		$key  = $args[0];

		/* Если языковой элемент не найден, то возвращаем индекс элемента */
		if (!isset($this->lang[$key]))
		{
			return $key;
		}

		/* Был просто запрошен индекс */
		if (sizeof($args) == 1)
		{
			return $this->lang[$key];
		}

		/**
		* Запрос с параметрами:
		*
		* $this->lang('INDEX', 5, 'some text', 2.39)
		*/
		$args[0] = $this->lang[$key];
		return call_user_func_array('sprintf', $args);
	}
	
	/**
	* Доступен ли сайт в выбранной локализации
	*/
	public function language_exists($language)
	{
		$sites = $this->cache->obtain_sites();
		
		foreach ($sites as $row)
		{
			if ($this->request->hostname == $row['site_url'] && $language == $row['site_language'])
			{
				return true;
			}
		}
		
		return false;
	}

	/**
	* Загрузка языковых файлов из базы
	*
	* @param	string	$lang_file		Имя файла для загрузки
	* @param	bool	$force_update	Нужно ли принудительно обновить данные из базы
	* @param	string	$language		Язык для обновления (при обновлении переводов из админки)
	*/
	public function load_language($lang_file, $force_update = false, $language = false)
	{
		$lang      = [];
		$language  = $language ?: $this->lang['.'];
		$lang_file = str_replace('/', '_', $lang_file);
		
		/* Общая локализация */
		$lang = array_merge_recursive($lang, $this->get_i18n_data(0, $language, $lang_file, $force_update));
		
		if (0 !== strpos($lang_file, 'fw_'))
		{
			/* Локализация проекта */
			$site_info = $this->cache->get_site_info_by_url_lang($this->request->hostname, $language);
			
			$lang = array_merge_recursive($lang, $this->get_i18n_data($site_info['id'], $language, $lang_file, $force_update));
		}
		
		if ($language == $this->lang['.'])
		{
			$this->lang = array_merge_recursive($this->lang, $lang);
			return;
		}
		
		return $lang;
	}

	/**
	* Определение языка сайта по URL
	*/
	protected function detect_language()
	{
		global $app;
		
		$url = trim(htmlspecialchars_decode($this->request->url), '/');
		$params = $url ? explode('/', $url) : [];
		
		if (empty($params))
		{
			return $app['site_info']['language'];
		}
		
		$language = $params[0];
		
		if (strlen($language) != 2)
		{
			return $app['site_info']['language'];
		}
		
		if ($app['site_info']['default'])
		{
			/* Если выбрана локализация по умолчанию, то убираем язык из URL */
			foreach ($this->cache->obtain_languages() as $id => $row)
			{
				if ($language == $row['language_title'])
				{
					$this->request->redirect(ilink(mb_substr($this->request->url, 3)));
				}
			}
			
			return $app['site_info']['language'];
		}
			
		if ($this->language_exists($language))
		{
			$this->request->url = mb_substr($this->request->url, 3);
			return $language;
		}
	}
	
	/**
	* Извлечение переводов
	*/
	protected function get_i18n_data($site_id, $language, $lang_file, $force_update = false)
	{
		$prefix = 0 === $site_id ? 'src' : $this->request->hostname;
		$cache_entry = sprintf('%s_i18n_%s_%s', $prefix, $lang_file, $language);
		
		if ($force_update || (false === $lang = $this->cache->_get($cache_entry)))
		{
			$sql = '
				SELECT
					i18n_subindex,
					i18n_index,
					i18n_file,
					i18n_translation
				FROM
					' . I18N_TABLE . '
				WHERE
					site_id = ' . $this->db->check_value($site_id) . '
				AND
					i18n_lang = ' . $this->db->check_value($language) . '
				AND
					i18n_file = ' . $this->db->check_value($lang_file);
			$this->db->query($sql);
			$lang = [];

			while ($row = $this->db->fetchrow())
			{
				if ($row['i18n_subindex'])
				{
					$lang[$row['i18n_subindex']][$row['i18n_index']] = $row['i18n_translation'];
				}
				else
				{
					$lang[$row['i18n_index']] = $row['i18n_translation'];
				}
			}

			$this->db->freeresult();
			$this->cache->_set($cache_entry, $lang);
		}
		
		return $lang;
	}
}
