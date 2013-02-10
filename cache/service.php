<?php
/**
* @package fw
* @copyright (c) 2013
*/

namespace fw\cache;

use fw\helpers\traverse\tree\site_pages;

/**
* Слой для работы с кэшем
*/
class service
{
	public $sql_rowset;
	public $sql_row_pointer;

	protected $config;
	protected $db;
	protected $driver;

	function __construct($db, $driver)
	{
		$this->db = $db;
		$this->set_driver($driver);
	}
	
	public function _set_config($config)
	{
		$this->config = $config;
		
		return $this;
	}
	
	/**
	* Возвращает название используемого кэша
	*/
	public function get_driver()
	{
		return $this->driver;
	}
	
	/**
	* Устанавливает новый механизм работы с кэшем
	*/
	public function set_driver($driver)
	{
		$this->driver = $driver;

		$this->sql_rowset      =& $this->driver->sql_rowset;
		$this->sql_row_pointer =& $this->driver->sql_row_pointer;
	}

	/**
	* Установка префикса записей
	*/
	public function set_prefix($prefix)
	{
		$this->driver->set_prefix($prefix);
	}
	
	public function sql_save($query, &$query_result, $ttl)
	{
		$this->driver->sql_save($query, $query_result, $ttl);
	}
	
	public function __call($method, $args)
	{
		return call_user_func_array([$this->driver, $method], $args);
	}

	/**
	* Поиск URL сайта по его уникальному идентификатору
	*/
	public function get_site_info_by_id($site_id)
	{
		foreach ($this->obtain_sites() as $row)
		{
			if ($site_id == $row['site_id'])
			{
				return [
					'domain'   => $row['site_url'],
					'language' => $row['site_language'],
					'title'    => $row['site_title']
				];
			}
		}
	
		return false;
	}

	/**
	* Поиск информации о сайте по его доменному имени
	* и языку, если передан просматриваемой URL страницы
	*
	* Если страница не указана, то будет выдан сайт
	* на языке по умолчанию (site_default = 1)
	*/
	public function get_site_info_by_url($hostname, $page = '')
	{
		$page     = trim($page, '/');
		$param    = $page ? explode('/', $page)[0] : '';
		$language = strlen($param) === 2 ? $param : '';
		$sites    = [];
		
		/* Все локализации сайта */
		foreach ($this->obtain_sites() as $row)
		{
			if ($hostname == $row['site_url'])
			{
				$sites[] = [
					'default'  => (int) $row['site_default'],
					'domain'   => $row['site_url'],
					'id'       => (int) $row['site_id'],
					'language' => $row['site_language'],
					'title'    => $row['site_title'],
				];
			}
		}
		
		foreach ($sites as $row)
		{
			if ($row['default'] && !$language or $language && $language == $row['language'])
			{
				return $row;
			}
		}
	
		return false;
	}

	/**
	* Поиск URL сайта по его доменному имени и локализации
	*/
	public function get_site_info_by_url_lang($hostname, $lang)
	{
		foreach ($this->obtain_sites() as $row)
		{
			if ($hostname == $row['site_url'] && $lang == $row['site_language'])
			{
				return [
					'id'    => (int) $row['site_id'],
					'title' => $row['site_title']
				];
			}
		}
	
		return false;
	}

	/**
	* Список включенных ботов
	*/
	public function obtain_bots()
	{
		if (false === $bots = $this->driver->_get('src_bots'))
		{
			$sql = '
				SELECT
					user_id,
					bot_agent
				FROM
					' . BOTS_TABLE . '
				ORDER BY
					LENGTH(bot_agent) DESC';
			$result = $this->db->query($sql);
			$bots = $this->db->fetchall($result);
			$this->db->freeresult($result);
			$this->driver->_set('src_bots', $bots);
		}

		return $bots;
	}

	/**
	* Список динамических страниц
	*/
	public function obtain_handlers_urls($site_id)
	{
		static $cache_entry, $handlers, $site_info;
		
		if (!$site_id)
		{
			return false;
		}
		
		if (empty($handlers))
		{
			$site_info = $this->get_site_info_by_id($site_id);
			$cache_entry = sprintf('%s_handlers_%s', $site_info['domain'], $site_info['language']);
		}
		
		if (empty($handlers) && (false === $handlers = $this->driver->_get($cache_entry)))
		{
			$sql = '
				SELECT
					*
				FROM
					' . PAGES_TABLE . '
				WHERE
					site_id = ' . $this->db->check_value($site_id) . '
				ORDER BY
					left_id ASC';
			$this->db->query($sql);
			$traversal = new traverse_handlers_urls();
			
			while ($row = $this->db->fetchrow())
			{
				$traversal->process_node($row);
			}
			
			$this->db->freeresult();
			$handlers = $traversal->get_tree_data();
			
			$this->driver->_set($cache_entry, $handlers);
		}
		
		return $handlers;
	}
	
	/**
	* Статистика по изображениям в галерее
	*/
	public function obtain_image_stats()
	{
		if (false === $stats = $this->driver->get('image_stats'))
		{
			$stats = [];

			/**
			* Количество изображений, загруженных за последние сутки
			*/
			$sql = '
				SELECT
					COUNT(*) as today_images
				FROM
					' . IMAGES_TABLE . '
				WHERE
					image_time >= UNIX_TIMESTAMP(CURRENT_DATE())';
			$this->db->query($sql);
			$row = $this->db->fetchrow();
			$this->db->freeresult();
			$stats += $row;

			/**
			* Общая статистика
			*/
			$sql = '
				SELECT
					COUNT(*) AS total_images,
					SUM(image_size) AS total_size,
					SUM(image_size * image_views) AS total_traffic,
					SUM(image_views) AS total_views
				FROM
					' . IMAGES_TABLE;
			$this->db->query($sql);
			$row = $this->db->fetchrow();
			$this->db->freeresult();
			$stats += $row;

			$this->driver->set('image_stats', $stats, 120);
		}

		return $stats;
	}

	/**
	* Список доступных групп
	*/
	public function obtain_groups()
	{
		if (false === $groups = $this->driver->_get('src_groups'))
		{
			$sql = '
				SELECT
					*
				FROM
					' . GROUPS_TABLE . '
				ORDER BY
					group_sort ASC';
			$this->db->query($sql);
			$groups = $this->db->fetchall(false, 'group_id');
			$this->db->freeresult();
			$this->driver->_set('src_groups', $groups);
		}

		return $groups;
	}

	/**
	* Список доступных языков
	*/
	public function obtain_languages($force_reload = false)
	{
		if ((false === $languages = $this->driver->_get('src_languages')) || $force_reload)
		{
			$sql = '
				SELECT
					*
				FROM
					' . LANGUAGES_TABLE . '
				ORDER BY
					language_sort ASC';
			$result = $this->db->query($sql);
			$languages = $this->db->fetchall($result, 'language_id');
			$this->db->freeresult($result);
			$this->driver->_set('src_languages', $languages);
		}

		return $languages;
	}
	
	/**
	* Глобальное меню сайта (page_display = 2)
	*/
	public function obtain_menu($site_id)
	{
		if (!$site_id)
		{
			return false;
		}
		
		$site_info = $this->get_site_info_by_id($site_id);
		$cache_entry = sprintf('%s_menu_%s', $site_info['domain'], $site_info['language']);
		
		if (false === $menu = $this->driver->_get($cache_entry))
		{
			$sql = '
				SELECT
					*
				FROM
					' . PAGES_TABLE . '
				WHERE
					site_id = ' . $this->db->check_value($site_id) . '
				ORDER BY
					left_id ASC';
			$this->db->query($sql);
			$traversal = new traverse_menu(true);
			$traversal->_set_config($this->config);
			
			while ($row = $this->db->fetchrow())
			{
				$traversal->process_node($row);
			}
			
			$this->db->freeresult();
			$menu = $traversal->get_tree_data();
			
			$this->driver->_set($cache_entry, $menu);
		}
		
		return $menu;
	}

	/**
	* Список пользователей, которые сейчас на сайте
	*/
	public function obtain_online_userlist($language)
	{
		if (false === $data = $this->driver->get('online_userlist_' . $language))
		{
			global $app;

			$data['guests_online'] = 0;
			$data['users_online'] = 0;
			$data['online_list'] = '';
			$data['online_userlist'] = '';
			$prev_id = [];
			$prev_ip = [];

			/**
			* Получаем данные пользователей, которые посетили сайт
			* в последние $this->config['load_online_time'] минут
			*/
			$sql = '
				SELECT
					s.session_time,
					u.user_id,
					u.username,
					u.user_url,
					u.user_colour
				FROM
					' . SESSIONS_TABLE . ' s
				LEFT JOIN
					' . USERS_TABLE . ' u ON (u.user_id = s.user_id)
				WHERE
					s.session_time >= ' . $this->db->check_value(time() - $this->config['load_online_time']) . '
				ORDER BY
					s.session_time DESC';
			$result = $this->db->query($sql);

			while ($row = $this->db->fetchrow($result))
			{
				/**
				* Для зарегистрированных пользователей формируем ссылки на просмотр профиля
				*/
				if (!isset($prev_id[$row['user_id']]))
				{
					$user_link = $this->user_profile_link('', $row['username'], $row['user_colour'], $row['user_url'], $row['user_id'], $row['session_time']);

					$data['online_userlist'] .= $data['online_userlist'] ? ', ' . $user_link : $user_link;
					$prev_id[$row['user_id']] = 1;
					$data['users_online']++;
				}
			}

			$this->db->freeresult($result);

			/**
			* Получаем количество гостей
			*/
			$sql = '
				SELECT
					session_ip
				FROM
					' . SESSIONS_TABLE . '
				WHERE
					user_id = 0
				AND
					session_time >= ' . $this->db->check_value(time() - $this->config['load_online_time']);
			$result = $this->db->query($sql);

			while ($row = $this->db->fetchrow($result))
			{
				if (!isset($prev_ip[$row['session_ip']]))
				{
					$prev_ip[$row['session_ip']] = 1;
					$data['guests_online']++;
				}
			}

			$this->db->freeresult($result);

			if (empty($data['online_userlist']))
			{
				/**
				* Если на сайте нет зарегистрированных пользователей, то сообщаем об этом
				*/
				$data['online_userlist'] = $app['user']->lang['ONLINE_LIST_EMPTY'];
			}

			/**
			* Текстовое сообщение для сайта
			*/
			$data['online_list'] = sprintf($app['user']->lang['ONLINE_LIST_TOTAL'], $data['users_online'] + $data['guests_online']);
			$data['online_list'] .= sprintf($app['user']->lang['ONLINE_LIST_REG'], $data['users_online']);
			$data['online_list'] .= sprintf($app['user']->lang['ONLINE_LIST_GUESTS'], $data['guests_online']);

			$this->driver->set('online_userlist_' . $language, $data, 180);
		}

		return $data;
	}

	/**
	* Список званий
	*/
	public function obtain_ranks()
	{
		if (false === $ranks = $this->driver->_get('src_ranks'))
		{
			$sql = '
				SELECT
					*
				FROM
					' . RANKS_TABLE;
			$result = $this->db->query($sql);
			$ranks = $this->db->fetchall($result, 'rank_id');
			$this->db->freeresult($result);
			$this->driver->_set('src_ranks', $ranks);
		}

		return $ranks;
	}

	/**
	* Список сайтов
	*/
	public function obtain_sites()
	{
		static $sites;
		
		if (empty($sites) && (false === $sites = $this->driver->_get('src_sites')))
		{
			$sql = '
				SELECT
					*
				FROM
					' . SITES_TABLE . '
				ORDER BY
					site_url ASC,
					site_language ASC';
			$this->db->query($sql);
			$sites = $this->db->fetchall();
			$this->db->freeresult();
			$this->driver->_set('src_sites', $sites);
		}

		return $sites;
	}
}

/**
* Дерево ссылок на методы
*/
class traverse_handlers_urls extends site_pages
{
	protected function tree_append($data)
	{
		if (!$this->row['page_handler'] || !$this->row['handler_method'])
		{
			return false;
		}
		
		/**
		* Замена меток (*) на параметры ($n)
		*
		* /проекты/(*)/задачи/(*).html => /проекты/$0/задачи/$1.html
		*/
		$i = 0;

		while (false !== $pos = strpos($data, '*'))
		{
			$data = substr_replace($data, '$' . $i++, $pos, 1);
		}
		
		$this->tree[$this->row['page_handler'] . '::' . $this->row['handler_method']] = $data;
	}
}

/**
* Древовидное меню
*/
class traverse_menu extends site_pages
{
	protected function get_data()
	{
		$ary = parent::get_data();
		
		return [
			'ID'    => $this->row['page_id'],
			'IMAGE' => $this->row['page_image'],
			'TITLE' => $this->row['page_name'],
			'URL'   => $ary['url'],
			'children' => [],
		];
	}
	
	protected function skip_condition()
	{
		return !$this->row['page_enabled'] || $this->row['page_display'] != 2;
	}
}
