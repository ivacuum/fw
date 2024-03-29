<?php namespace fw\traits;

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

		if ($gmepoch == 0) {
			return $this->lang['NEVER'];
		}

		/**
		* Определяем переменные
		*/
		$format = !$format ? $this->config['dateformat'] : $format;
		$tz = 3600 * $this->config['site.tz'];
		$forcedate = !isset($this->lang['datetime']) ? true : $forcedate;

		if (!$midnight) {
			/* Определение полуночи */
			list($d, $m, $y) = explode(' ', gmdate('j n Y', $this->request->time + $tz));
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
		if (false !== $short_form) {
			if (strpos($format, '|') !== false && $gmepoch < $midnight - 86400 && !$forcedate) {
				return strtr(@gmdate(str_replace('|', '', substr($format, 0, strrpos($format, '|'))), $gmepoch + $tz), $this->lang['datetime']);
			}
		}

		if (strpos($format, '|') === false || ($gmepoch < $midnight - 86400 && !$forcedate) || ($gmepoch > $midnight + 172800 && !$forcedate)) {
			return strtr(@gmdate(str_replace('|', '', $format), $gmepoch + $tz), $this->lang['datetime']);
		}

		if ($gmepoch > $midnight + 86400 && !$forcedate) {
			/* Завтра ... */
			return $this->lang['datetime']['TOMORROW'] . strtr(@gmdate(substr($format, strpos($format, '|', 1) + 1), $gmepoch + $tz), $this->lang['datetime']);
		} elseif ($gmepoch > $midnight && !$forcedate) {
			/* Сегодня ... */
			return $this->lang['datetime']['TODAY'] . strtr(@gmdate(substr($format, strpos($format, '|', 1) + 1), $gmepoch + $tz), $this->lang['datetime']);
		} elseif ($gmepoch > $midnight - 86400 && !$forcedate) {
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
		$days = $days > 0 ? $this->plural($days, 'день;дня;дней') : '';
		$time -= $time >= 86400 ? 86400 * $days : 0;
		
		if ($only_days) {
			return $days;
		}

		/* Часы */
		$hours = $time >= 3600 ? intval($time / 3600) : 0;
		$hours = $hours > 0 ? $this->plural($hours, 'час;часа;часов') : '';
		$time -= $time >= 3600 ? 3600 * $hours : 0;

		/* Минуты */
		$minutes = $time >= 60 ? intval($time / 60) : 0;
		$minutes = $minutes > 0 ? $this->plural($minutes, 'минуту;минуты;минут') : '';
		$time -= $time >= 60 ? 60 * $minutes : 0;

		if (!$days && !$hours && !$minutes && false !== $no_seconds) {
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

		if ($min == $this->lang['SIZE_KB'] && $size < 1024) {
			$size    = $size / 1024;
			$ext     = $this->lang['SIZE_KB'];
			$rounder = 1;
		} else {
			for ($i = 1, $cnt = sizeof($sizes); $i < $cnt && $size >= 1024; $i++) {
				$size = $size / 1024;
				$ext  = $sizes[$i];
				$rnd  = $rounders[$i];
			}
		}

		if (!$rounder) {
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
		if (!isset($this->lang[$key])) {
			return $key;
		}

		/* Был просто запрошен индекс */
		if (sizeof($args) == 1) {
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
	* Загрузка языковых файлов из базы
	*
	* @param	string	$lang_file		Имя файла для загрузки
	* @param	bool	$force_update	Нужно ли принудительно обновить данные из базы
	* @param	string	$language		Язык для обновления (при обновлении переводов из админки)
	*/
	public function load_language($lang_file, $force_update = false, $language = false)
	{
		$lang      = [];
		$language  = $language ?: $this->request->language;
		$lang_file = str_replace('/', '_', $lang_file);
		
		/* Общая локализация */
		$lang = array_merge_recursive($lang, $this->get_i18n_data(0, $language, $lang_file, $force_update));
		
		/* Локализация проекта */
		$lang = array_merge_recursive($lang, $this->get_i18n_data($this->site_id, $language, $lang_file, $force_update));
		
		if ($language == $this->request->language) {
			$this->lang = array_merge_recursive($this->lang, $lang);
			return;
		}
		
		return $lang;
	}
	
	/**
	* Возвращает число в заданном формате
	*
	* @param	int	$value	Число
	*
	* @return	int			Число в заданном формате
	*/
	public function num_format($value, $decimals = 0)
	{
		return number_format($value, $decimals, $this->config['number.dec_point'], $this->config['number.thousands_sep']);
	}
	
	/**
	* Формы слова во множественном числе
	*
	* @param	int		$n		Число
	* @param	array	$forms	Формы слова или индекс в массиве $this->lang['plural']
	*
	* @param	string			Фраза во множественном числе
	*/
	public function plural($n = 0, $forms, $format = '%1$s %2$s')
	{
		if (!$forms) {
			return;
		}
		
		$forms = explode(';', isset($this->lang['plural'][$forms]) ? $this->lang['plural'][$forms] : $forms);

		switch ($this->request->language) {
			/* Русский язык */
			case 'ru':

				$forms[2] = sizeof($forms) < 3 ? $forms[1] : $forms[2];

				if (!is_int($n) && !ctype_digit(strval($n))) {
					$plural = 1;
				} else {
					$plural = ($n % 10 == 1 && $n % 100 != 11) ? 0 : ($n % 10 >= 2 && $n % 10 <= 4 && ($n % 100 < 10 || $n % 100 >= 20) ? 1 : 2);
				}

			break;
			/* Язык по умолчанию - английский */
			default:
			
				$plural = $n == 1 ? 0 : 1;
		}
	
		return sprintf($format, $n, $forms[$plural]);
	}

	/**
	* Относительная дата
	*/
	/*
	public function relative_date($ts, $ts2 = time())
	{
		$ts   = !ctype_digit($ts) ? strtotime($ts) : $ts;
		$diff = $ts2 - $ts;
		
		if ($diff >= 0) {
			if ($diff < 60) {
				return 'just now';
			}
			if ($diff < 3600) {
				return floor($diff / 60) . ' minutes ago';
			}
			if ($diff < 86400) {
				return floor($diff / 3600) . ' hours ago';
			}
			
			$day_diff = floor($diff / 86400);
			
			if ($day_diff === 1) {
				return 'yesterday';
			}
			if ($day_diff < 28) {
				return $day_diff . ' days ago';
			}
			
			return $this->create_date($ts);
		}
		
		$diff = abs($diff);
		
		if ($diff < 120) {
			return 'in a minute';
		}
		if ($diff < 3600) {
			return 'in ' . floor($diff / 60) . ' minutes';
		}
		if ($diff < 86400) {
			return 'in ' . floor($diff / 3600) . ' hours';
		}
		
		$day_diff = floor($diff / 86400);
		
		if ($day_diff === 1) {
			return 'tomorrow';
		}
		if ($day_diff < 28) {
			return "in {$day_diff} days";
		}
		
		return $this->create_date($ts);
	}
	*/

	/**
	* Извлечение переводов
	*/
	protected function get_i18n_data($site_id, $language, $lang_file, $force_update = false)
	{
		$cache_entry = "i18n_{$lang_file}_{$language}";
		
		if ($force_update ||
			(0 === $site_id && (false === $lang = $this->cache->get_shared($cache_entry))) ||
			(0 !== $site_id && (false === $lang = $this->cache->get($cache_entry)))
		) {
			$sql = '
				SELECT
					i18n_subindex,
					i18n_index,
					i18n_file,
					i18n_translation
				FROM
					site_i18n
				WHERE
					site_id = ?
				AND
					i18n_lang = ?
				AND
					i18n_file = ?';
			$this->db->query($sql, [$site_id, $language, $lang_file]);
			$lang = [];

			while ($row = $this->db->fetchrow()) {
				if ($row['i18n_subindex']) {
					$lang[$row['i18n_subindex']][$row['i18n_index']] = $row['i18n_translation'];
				} else {
					$lang[$row['i18n_index']] = $row['i18n_translation'];
				}
			}

			$this->db->freeresult();
			
			if (0 === $site_id) {
				$this->cache->set_shared($cache_entry, $lang);
			} else {
				$this->cache->set($cache_entry, $lang);
			}
		}
		
		return $lang;
	}
}
