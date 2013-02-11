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
	* Создание даты в нужном формате
	*
	* @param	int		$gmepoch	Время
	* @param	string	$format		Формат вывода даты
	* @param	bool	$forcedate	Вывод на английском (по умолчанию дата переводится на язык пользователя)
	*
	* @return	string				Дата в выбранном формате
	*/
	public function create_date($gmepoch, $format = false, $forcedate = false, $short_form = false)
	{
		static $midnight;

		if ($gmepoch == 0)
		{
			return $this->lang['NEVER'];
		}

		/**
		* Определяем переменные
		*/
		$format = !$format ? $this->config['dateformat'] : $format;
		$tz = 3600 * $this->config['site_tz'];
		$forcedate = !isset($this->lang['datetime']) ? true : $forcedate;

		if (!$midnight)
		{
			/* Определение полуночи */
			list($d, $m, $y) = explode(' ', gmdate('j n Y', $this->ctime + $tz));
			$midnight = gmmktime(0, 0, 0, $m, $d, $y) - $tz;
		}

		/**
		* Короткая форма
		* |j F Y|, H:i выдаст:
		*
		* Сегодня, 18:25
		* Вчера, 14:55
		* 10 Августа 2009
		*/
		if (false !== $short_form)
		{
			if (strpos($format, '|') !== false && $gmepoch < $midnight - 86400 && !$forcedate)
			{
				return strtr(@gmdate(str_replace('|', '', substr($format, 0, strrpos($format, '|'))), $gmepoch + $tz), $this->lang['datetime']);
			}
		}

		if (strpos($format, '|') === false || ($gmepoch < $midnight - 86400 && !$forcedate) || ($gmepoch > $midnight + 172800 && !$forcedate))
		{
			return strtr(@gmdate(str_replace('|', '', $format), $gmepoch + $tz), $this->lang['datetime']);
		}

		if ($gmepoch > $midnight + 86400 && !$forcedate)
		{
			/* Завтра ... */
			return $this->lang['datetime']['TOMORROW'] . strtr(@gmdate(substr($format, strpos($format, '|', 1) + 1), $gmepoch + $tz), $this->lang['datetime']);
		}
		elseif ($gmepoch > $midnight && !$forcedate)
		{
			/* Сегодня ... */
			return $this->lang['datetime']['TODAY'] . strtr(@gmdate(substr($format, strpos($format, '|', 1) + 1), $gmepoch + $tz), $this->lang['datetime']);
		}
		elseif ($gmepoch > $midnight - 86400 && !$forcedate)
		{
			/* Вчера ... */
			return $this->lang['datetime']['YESTERDAY'] . strtr(@gmdate(substr($format, strpos($format, '|', 1) + 1), $gmepoch + $tz), $this->lang['datetime']);
		}

		return strtr(@gmdate(str_replace('|', '', $format), $gmepoch + $tz), $this->lang['datetime']);
	}

	/**
	* Продолжительность
	*
	* @param	int		$time		Время в секундах
	* @param	bool	$no_seconds	Выводить ли секунды
	* @param	bool	$only_days	Выводить ли только дни
	*
	* @return	string				Сформированная строка
	*/
	public function create_time($time, $no_seconds = false, $only_days = false)
	{
		/* Дни */
		$days = $time >= 86400 ? intval($time / 86400) : 0;
		$days = $days > 0 ? "{$days} дн." : '';
		$time -= $time >= 86400 ? 86400 * $days : 0;
		
		if ($only_days)
		{
			return $days;
		}

		/* Часы */
		$hours = $time >= 3600 ? intval($time / 3600) : 0;
		$hours = $hours > 0 ? "{$hours} ч." : '';
		$time -= $time >= 3600 ? 3600 * $hours : 0;

		/* Минуты */
		$minutes = $time >= 60 ? intval($time / 60) : 0;
		$minutes = $minutes > 0 ? "{$minutes} мин." : '';
		$time -= $time >= 60 ? 60 * $minutes : 0;

		if (!$days && !$hours && !$minutes && false !== $no_seconds)
		{
			return '1 мин.';
		}
		
		return "{$days} {$hours} {$minutes}" . ($no_seconds === false ? (!$days && !$hours && !$minutes && $time < 60 ? '' : ' и ') . $time . ' сек.' : '');
	}

	/**
	* Размер в понятной человеку форме, округленный к ближайшему ГБ, МБ, КБ
	*
	* @param	int		$size		Размер
	* @param	int		$rounder	Необходимое количество знаков после запятой
	* @param	string	$min		Минимальный размер ('КБ', 'МБ' и т.п.)
	* @param	string	$space		Разделитель между числами и текстом (1< >МБ)
	*
	* @return	string				Размер в понятной человеку форме
	*/
	public function humn_size($size, $rounder = '', $min = '', $space = '&nbsp;')
	{
		$sizes = [$this->lang['SIZE_BYTES'], $this->lang['SIZE_KB'], $this->lang['SIZE_MB'], $this->lang['SIZE_GB'], $this->lang['SIZE_TB'], $this->lang['SIZE_PB'], $this->lang['SIZE_EB'], $this->lang['SIZE_ZB'], $this->lang['SIZE_YB']];
		static $rounders = [0, 0, 1, 2, 3, 3, 3, 3, 3];

		$size = (float) $size;
		$ext  = $sizes[0];
		$rnd  = $rounders[0];

		if ($min == $this->lang['SIZE_KB'] && $size < 1024)
		{
			$size    = $size / 1024;
			$ext     = $this->lang['SIZE_KB'];
			$rounder = 1;
		}
		else
		{
			for ($i = 1, $cnt = sizeof($sizes); $i < $cnt && $size >= 1024; $i++)
			{
				$size = $size / 1024;
				$ext  = $sizes[$i];
				$rnd  = $rounders[$i];
			}
		}

		if (!$rounder)
		{
			$rounder = $rnd;
		}

		return round($size, $rounder) . $space . $ext;
	}

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
	* Формы слова во множественном числе
	*
	* @param	int		$n		Число
	* @param	array	$forms	Формы слова или индекс в массиве $this->lang['plural']
	*
	* @param	string			Фраза во множественном числе
	*/
	public function plural($n = 0, $forms, $format = '%s %s')
	{
		if (!$forms)
		{
			return;
		}
		
		$forms = explode(';', isset($this->lang['plural'][$forms]) ? $this->lang['plural'][$forms] : $forms);

		switch ($this->request->language)
		{
			/* Русский язык */
			case 'ru':

				$forms[2] = sizeof($forms) < 3 ? $forms[1] : $forms[2];

				$plural = ($n % 10 == 1 && $n % 100 != 11) ? 0 : ($n % 10 >= 2 && $n % 10 <= 4 && ($n % 100 < 10 || $n % 100 >= 20) ? 1 : 2);

			break;
			/* Язык по умолчанию - английский */
			default:
			
				$plural = $n == 1 ? 0 : 1;
		}
	
		return sprintf($format, $n, $forms[$plural]);
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
