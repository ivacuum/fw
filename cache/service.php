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
	protected $db;
	protected $driver;

	function __construct($db, $driver)
	{
		$this->db     = $db;
		$this->driver = $driver;
	}
	
	function __destruct()
	{
		$this->unload();
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
		$sites = $this->obtain_sites();
		
		if (!isset($sites[$site_id]))
		{
			return false;
		}
		
		$row = $sites[$site_id];
		
		return [
			'default'  => (int) $row['site_default'],
			'domain'   => $row['site_url'],
			'id'       => (int) $row['site_id'],
			'language' => $row['site_language'],
			'locale'   => $row['site_locale'],
			'title'    => $row['site_title'],
		];
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
		$hostnames = $this->obtain_hostnames();
		$page      = trim($page, '/');
		$language  = $page ? explode('/', $page)[0] : '';
		$language  = strlen($language) === 2 ? $language : '';
		$sites     = [];
		
		if ($language && isset($hostnames["{$hostname}_{$language}"]))
		{
			return $this->get_site_info_by_id($hostnames["{$hostname}_{$language}"]);
		}
		
		return isset($hostnames[$hostname]) ? $this->get_site_info_by_id($hostnames[$hostname]) : false;
	}

	/**
	* Список включенных ботов
	*/
	public function obtain_bots()
	{
		if (false === $bots = $this->driver->get_shared('bots'))
		{
			$sql = '
				SELECT
					user_id,
					bot_agent
				FROM
					site_bots
				ORDER BY
					LENGTH(bot_agent) DESC';
			$result = $this->db->query($sql);
			$bots = $this->db->fetchall($result);
			$this->db->freeresult($result);
			$this->driver->set_shared('bots', $bots);
		}

		return $bots;
	}

	/**
	* Список доступных групп
	*/
	public function obtain_groups()
	{
		if (false === $groups = $this->driver->get_shared('groups'))
		{
			$sql = '
				SELECT
					*
				FROM
					site_groups
				ORDER BY
					group_sort ASC';
			$this->db->query($sql);
			$groups = $this->db->fetchall(false, 'group_id');
			$this->db->freeresult();
			$this->driver->set_shared('groups', $groups);
		}

		return $groups;
	}

	/**
	* Список динамических страниц
	*/
	public function obtain_handlers_urls($site_id, array $options = [])
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
					site_pages
				WHERE
					site_id = ?
				ORDER BY
					left_id ASC';
			$this->db->query($sql, [$site_id]);
			$traversal = new traverse_handlers_urls($options);
			
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
	* Список обслуживаемых сайтов
	* С алиасами и локализациями
	*/
	public function obtain_hostnames()
	{
		if (false === $hostnames = $this->driver->get_shared('hostnames'))
		{
			$sql = '
				SELECT
					*
				FROM
					site_sites
				ORDER BY
					site_id ASC';
			$this->db->query($sql);
			
			while ($row = $this->db->fetchrow())
			{
				if ($row['site_default'])
				{
					$hostnames[$row['site_url']] = $row['site_id'];
				}
				
				$hostnames["{$row['site_url']}_{$row['site_language']}"] = $row['site_id'];
				
				if (!empty($row['site_aliases']))
				{
					foreach (explode(' ', $row['site_aliases']) as $key => $hostname)
					{
						if ($row['site_default'])
						{
							$hostnames[$hostname] = $row['site_id'];
						}
						
						$hostnames["{$hostname}_{$row['site_language']}"] = $row['site_id'];
					}
				}
			}
			
			$this->db->freeresult();
			$this->driver->set_shared('hostnames', $hostnames);
		}
		
		return $hostnames;
	}

	/**
	* Список доступных языков
	*/
	public function obtain_languages($force_reload = false)
	{
		if ($force_reload || (false === $languages = $this->driver->get_shared('languages')))
		{
			$sql = '
				SELECT
					*
				FROM
					site_languages
				ORDER BY
					language_sort ASC';
			$result = $this->db->query($sql);
			$languages = $this->db->fetchall($result, 'language_id');
			$this->db->freeresult($result);
			$this->driver->set_shared('languages', $languages);
		}

		return $languages;
	}
	
	/**
	* Глобальное меню сайта (page_display = 2)
	*/
	public function obtain_menu($site_id, array $options = [])
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
					site_pages
				WHERE
					site_id = ?
				ORDER BY
					left_id ASC';
			$this->db->query($sql, [$site_id]);
			$traversal = new traverse_menu(array_merge($options, ['return_as_tree' => true]));
			
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
	* Список меню
	*/
	public function obtain_menus()
	{
		if (false === $menus = $this->driver->get_shared('menus'))
		{
			$sql = '
				SELECT
					*
				FROM
					site_menus
				WHERE
					menu_active = 1';
			$this->db->query($sql);
			
			while ($row = $this->db->fetchrow())
			{
				$menus[$row['menu_alias']] = $row;
			}
			
			$this->db->freeresult();
			$this->driver->set_shared('menus', $menus);
		}
		
		return $menus;
	}

	/**
	* Список пользователей, которые сейчас на сайте
	*/
	public function obtain_online_userlist($language, $online_time = 1800)
	{
		if (false === $data = $this->driver->get("online_userlist_{$language}"))
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
			* в последние $online_time секунд
			*/
			$sql = '
				SELECT
					s.session_time,
					u.user_id,
					u.username,
					u.user_url,
					u.user_colour
				FROM
					site_sessions s
				LEFT JOIN
					site_users u ON (u.user_id = s.user_id)
				WHERE
					s.session_time >= ?
				ORDER BY
					s.session_time DESC';
			$result = $this->db->query($sql, [time() - $online_time]);

			while ($row = $this->db->fetchrow($result))
			{
				/**
				* Для зарегистрированных пользователей формируем ссылки на просмотр профиля
				*/
				if (!isset($prev_id[$row['user_id']]))
				{
					$user_link = $this->user_profile_link('', $row['username'], $row['user_colour'], $row['user_url'], $row['user_id'], $row['session_time']);

					$data['online_userlist'] .= $data['online_userlist'] ? ", {$user_link}" : $user_link;
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
					site_sessions
				WHERE
					user_id = 0
				AND
					session_time >= ?';
			$result = $this->db->query($sql, [time() - $online]);

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

			$this->driver->set("online_userlist_{$language}", $data, 180);
		}

		return $data;
	}

	/**
	* Список званий
	*/
	public function obtain_ranks()
	{
		if (false === $ranks = $this->driver->get_shared('ranks'))
		{
			$sql = '
				SELECT
					*
				FROM
					site_ranks';
			$result = $this->db->query($sql);
			$ranks = $this->db->fetchall($result, 'rank_id');
			$this->db->freeresult($result);
			$this->driver->set_shared('ranks', $ranks);
		}

		return $ranks;
	}

	/**
	* Список сайтов
	*/
	public function obtain_sites()
	{
		static $sites;
		
		if (empty($sites) && (false === $sites = $this->driver->get_shared('sites')))
		{
			$sql = '
				SELECT
					*
				FROM
					site_sites
				ORDER BY
					site_url ASC,
					site_language ASC';
			$this->db->query($sql);
			$sites = $this->db->fetchall(false, 'site_id');
			$this->db->freeresult();
			$this->driver->set_shared('sites', $sites);
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
		
		$this->tree["{$this->row['page_handler']}::{$this->row['handler_method']}"] = $data;
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
