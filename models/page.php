<?php
/**
* @package fw
* @copyright (c) 2012
*/

namespace fw\models;

/**
* Страница сайта
*/
class page
{
	public $data;
	public $format;
	public $full_url;
	public $handlers_urls = array();
	public $method;
	public $page;
	public $params;
	public $url;
	public $urls = array();
	
	protected $auth;
	protected $cache;
	protected $config;
	protected $db;
	protected $request;
	protected $template;
	protected $user;
	
	function __construct()
	{
		global $auth, $cache, $config, $db, $request, $template, $user;
		
		$this->auth     =& $auth;
		$this->cache    =& $cache;
		$this->config   =& $config;
		$this->db       =& $db;
		$this->request  =& $request;
		$this->template =& $template;
		$this->user     =& $user;
	}
	
	/**
	* Добавление параметров к ссылке
	*
	* /ссылка/ + id=100              => /ссылка/?id=100
	* /ссылка.html + id=100          => /ссылка.html?id=100
	* /ссылка/?goto=%2F + id=100     => /ссылка/?goto=%2F&id=100
	* /ссылка.html?goto=%2F + id=100 => /ссылка.html?goto=%2F&id=100
	*/
	public function append_link_params($query_string, $url = false)
	{
		$url = ( $url ) ? ilink($url) : ilink($this->url);
		
		if( false !== strpos($url, '?') )
		{
			return sprintf('%s&%s', $url, $query_string);
		}
		
		return sprintf('%s?%s', $url, $query_string);
	}
	
	/**
	* Ссылка на прямого родственника данной страницы
	*
	* Папка это или страница - необходимо проверять и учитывать
	*/
	public function descendant_link($row)
	{
		static $base_url;
		
		if( !$base_url )
		{
			$ary = pathinfo($this->url);
			$base_url = isset($ary['extension']) ? $ary['dirname'] : $this->url;
		}
		
		$url = ( $row['is_dir'] ) ? $row['page_url'] : (($row['page_url'] != $this->config['router_directory_index']) ? (($this->format) ? sprintf('%s.%s', $row['page_url'], $this->format) : $row['page_url']) : '');
		
		return ilink(sprintf('%s/%s', $base_url, $url));
	}

	/**
	* Данные раздела (ветви дерева страниц)
	*/
	public function get_page_branch($page_id, $type = 'all', $order = 'descending', $include_self = true)
	{
		switch( $type )
		{
			case 'parents':  $condition = 'p1.left_id BETWEEN p2.left_id AND p2.right_id'; break;
			case 'children': $condition = 'p2.left_id BETWEEN p1.left_id AND p1.right_id'; break;
			default:         $condition = 'p2.left_id BETWEEN p1.left_id AND p1.right_id OR p1.left_id BETWEEN p2.left_id AND p2.right_id';
		}

		$rows = array();

		$sql = '
			SELECT
				p2.*
			FROM
				' . PAGES_TABLE . ' p1
			LEFT JOIN
				' . PAGES_TABLE . ' p2 ON (' . $condition . ')
			WHERE
				p1.site_id = ' . $this->db->check_value($this->data['site_id']) . '
			AND
				p2.site_id = ' . $this->db->check_value($this->data['site_id']) . '
			AND
				p1.page_id = ' . $this->db->check_value($page_id) . '
			ORDER BY
				p2.left_id ' . (($order == 'descending') ? 'ASC' : 'DESC');
		$this->db->query($sql);

		while( $row = $this->db->fetchrow() )
		{
			if( !$include_self && $row['page_id'] == $page_id )
			{
				continue;
			}

			$rows[] = $row;
		}

		$this->db->freeresult();

		return $rows;
	}
	
	/**
	* Прямые родственники страницы (второстепенное меню)
	*/
	public function get_page_descendants($page_id = false)
	{
		if( $page_id === false )
		{
			$page_id = ( $this->data['is_dir'] ) ? $this->data['page_id'] : $this->data['parent_id'];
		}
		
		$page_id = (int) $page_id;
		$rows = array();
		
		$sql = '
			SELECT
				*
			FROM
				' . PAGES_TABLE . '
			WHERE
				parent_id = ' . $page_id . '
			AND
				site_id = ' . $this->data['site_id'] . '
			AND
				page_display > 0
			ORDER BY
				left_id ASC';
		$this->db->query($sql);
		$rows = $this->db->fetchall();
		$this->db->freeresult();
		
		return $rows;
	}
	
	/**
	* Возврат ссылки на обработчик
	*/
	public function get_handler_url($handler, array $params = array())
	{
		if( 0 === strpos($handler, '\\') )
		{
			/**
			* Обращение по абсолютному адресу
			* Чаще всего к модулям движка
			*
			* \fw\modules\gallery::index
			*/
			/* Разработчик знает, что подключает */
			if( isset($this->handlers_urls[$handler]) )
			{
				return $this->get_url_with_params($this->handlers_urls[$handler], $params);
			}
			
			return '/';
		}
		
		/**
		* Обращение к методу текущего модуля
		*/
		if( false === strpos($handler, '::') )
		{
			if( isset($this->urls[$handler]) )
			{
				return $this->get_url_with_params($this->urls[$handler], $params);
			}
			
			return;
		}
		
		/**
		* Обращение по относительному адресу
		*
		* Если обращение исходит из модуля app\modules\csstats\servers::single
		* к maps::index, то сначала будет произведена попытка загрузить
		* app\modules\csstats\maps::index, а затем app\maps::index
		*/
		$class = get_class($this);
		$class = substr($class, 4);
		$diff = substr_count($class, '\\') - substr_count($handler, '\\');
		
		if( $diff > 0 )
		{
			if( false != $prefix = implode('\\', array_slice(explode('\\', $class), 0, $diff)) )
			{
				$full_handler = $prefix . '\\' . $handler;
				
				if( isset($this->handlers_urls[$full_handler]) )
				{
					return $this->get_url_with_params($this->handlers_urls[$full_handler], $params);
				}
			}
		}
		
		if( isset($this->handlers_urls[$handler]) )
		{
			return $this->get_url_with_params($this->handlers_urls[$handler], $params);
		}
		
		return;
	}
	
	/**
	* Подстановка значений вместо параметров ($n)
	*
	* /проекты/$0/задачи/$1.html => /проекты/www.ru/задачи/важные.html
	*/
	public function get_url_with_params($url, array $params = array())
	{
		if( empty($params) )
		{
			return $url;
		}
		
		$ary = array();
		
		for( $i = 0, $len = sizeof($params); $i < $len; $i++ )
		{
			$ary[] = '$' . $i;
		}
		
		return str_replace($ary, $params, $url);
	}
	
	/**
	* Загрузка переводов
	*
	* app_news
	* app_news_index
	* fw_core_profiler
	*/
	public function load_translations()
	{
		$filename = str_replace('\\', '/', get_class($this));
		
		if( 0 === strpos($filename, 'app/') )
		{
			$filename = substr($filename, 4);
		}
		
		$this->user->load_language($filename);
		$this->user->load_language($filename . '_' . $this->method);
	}
	
	/**
	* Карта ссылок на методы обработчика
	*/
	public function obtain_handlers_urls()
	{
		$handler = get_class($this);
		$this->handlers_urls = $this->cache->obtain_handlers_urls($this->data['site_id']);
		
		if( 0 === strpos($handler, 'app\\') )
		{
			$handler = substr($handler, 4);
		}
		
		$pos = strlen($handler) + 2;

		foreach( $this->handlers_urls as $method => $url )
		{
			if( 0 === strpos($method, $handler . '::') )
			{
				$this->urls[substr($method, $pos)] = $url;
			}
		}
	}

	/**
	* Шапка
	*/
	public function page_header()
	{
		if( defined('HEADER_PRINTED') )
		{
			return;
		}
		
		/* Запрет кэширования страниц */
		header('Cache-Control: no-cache, pre-check=0, post-check=0');
		header('Expires: -1');
		header('Pragma: no-cache');

		/* Ссылки на вход-выход */
		$u_login = ( $this->user->is_registered ) ? ilink($this->get_handler_url('ucp::logout')) : ilink($this->get_handler_url('ucp::login'));
		
		/**
		* Выпадающий список языков
		*/
		$languages = $this->cache->obtain_languages();
		$sites     = $this->cache->obtain_sites();

		foreach( $sites as $row )
		{
			if( $this->user->domain != $row['site_url'] )
			{
				continue;
			}
			
			foreach( $languages as $ary )
			{
				if( $ary['language_title'] == $row['site_language'] )
				{
					break;
				}
			}

			$this->template->append('languages', array(
				'IMG'   => $row['site_language'],
				'NAME'  => $ary['language_name'],
				'TITLE' => $ary['language_title'],
				'URL'   => ilink('', $this->config['site_root_path'] . $row['site_language'])
			));
			
			if( $this->user->lang['.'] == $ary['language_title'] )
			{
				$language_ary = $ary;
			}
		}

		$this->template->assign(array(
			'CURRENT_TIME' => sprintf($this->user->lang['CURRENT_TIME'], $this->user->create_date($this->user->ctime, false, true)),
			'LAST_VISIT'   => ( $this->user->is_registered ) ? sprintf($this->user->lang['LAST_VISIT'], $this->user->create_date($this->user['user_last_visit'])) : '',
			'LOGIN'        => ( $this->user->is_registered ) ? sprintf($this->user->lang['LOGOUT'], $this->user['username']) : $this->user->lang['LOGIN'],

			'S_BOT'                => $this->user->is_bot,
			'S_ISP'                => $this->user->isp,
			'S_LANGUAGE'           => $this->user->lang['.'],
			'S_LANGUAGE_DIRECTION' => $language_ary['language_direction'],
			'S_OPENID_PROVIDER'    => $this->user['openid_provider'],
			'S_USER_REGISTERED'    => $this->user->is_registered,
			'S_USERNAME'           => $this->user['username'],
			'S_YEAR'               => $this->user->create_date($this->user->ctime, 'Y', true),

			/* Ссылки */
			'U_INDEX'     => ilink(),
			'U_LOGIN'     => $u_login,
			'U_THIS_PAGE' => $this->user->get_back_url(),
			'U_REGISTER'  => ilink($this->get_handler_url('ucp::register'))
		));

		define('HEADER_PRINTED', true);
	}

	/**
	* Нижняя часть страницы
	*/
	public function page_footer()
	{
		$this->template->assign(array(
			'S_ACP'      => $this->auth->acl_get('a_'),
			'S_INTERNET' => $this->user->isp == 'internet' || $this->user->isp == 'corbina-kaluga',

			'U_COPYRIGHT' => ilink(sprintf('%s/vacuum.html', $this->get_handler_url('users::index')))
		));
		
		if( $this->template->file )
		{
			$this->template->display();
		}
		
		$display_profiler = false;
		
		/* Вывод профайлера только для html-документов */
		if( $this->format == 'html' )
		{
			$display_profiler = true;
		}

		garbage_collection($display_profiler);
		exit;
	}
	
	/**
	* Установка заголовка Content-type согласно запрашиваемому формату
	*/
	public function set_appropriate_content_type()
	{
		switch( $this->format )
		{
			case 'json': $type = 'application/json'; break;
			case 'xml':  $type = 'text/xml'; break;

			/* Веб-сервер по умолчанию устанавливает text/html */
			default: return true;
		}
		
		header('Content-type: ' . $type . '; charset=utf-8');
	}

	/**
	* Установка шаблона по умолчанию
	* При ajax-запросах префикс становится ajax/
	*
	* app\news (index) -> news_index.html
	* app\csstats\playerinfo (chat) -> csstats/playerinfo_chat.html
	*/
	public function set_default_template()
	{
		if( !$this->format )
		{
			return;
		}
		
		$filename = str_replace('\\', '/', get_class($this));
		
		if( 0 === strpos($filename, 'app/') )
		{
			$filename = substr($filename, 4);
		}
		
		if( 0 === strpos($filename, 'fw/modules/') )
		{
			$filename = substr($filename, 15);
		}
		
		$this->template->file = sprintf('%s_%s.%s', $filename, $this->method, $this->format);
		
		if( $this->request->is_ajax )
		{
			$this->template->file = sprintf('ajax/%s_%s.%s', $filename, $this->method, $this->format);
		}
	}
	
	/**
	* Передача данных страницы шаблонизатору
	*/
	public function set_page_data()
	{
		$this->template->assign('page', $this->data);
	}

	/**
	* Передача меню сайта шаблонизатору
	*/
	public function set_site_menu()
	{
		$menu     = $this->cache->obtain_menu($this->data['site_id']);
		$page_url = ilink($this->full_url);
		$root_url = ilink();
		
		foreach( $menu as $row )
		{
			if( $row['URL'] == $root_url )
			{
				if( $page_url == $row['URL'] )
				{
					$row['ACTIVE'] = true;
				}
			}
			else
			{
				if( 0 === mb_strpos($page_url, $row['URL']) )
				{
					$row['ACTIVE'] = true;
					
					if( !empty($row['children']) )
					{
						$this->recursive_set_menu_active_items($row['children'], $row['URL']);
					}
				}
			}
			
			$this->template->append('menu', $row);
		}
	}

	/**
	* Передача локального меню раздела шаблонизатору
	*/
	public function set_site_submenu()
	{
		$rows = $this->get_page_descendants();
		
		foreach( $rows as $row )
		{
			$this->template->append('submenu', array(
				'ACTIVE' => $this->data['page_id'] == $row['page_id'],
				'IMAGE'  => $row['page_image'],
				'TITLE'  => $row['page_name'],
				
				'U_VIEW' => $this->descendant_link($row)
			));
		}
	}
	
	/**
	* Кто сейчас на сайте
	*/
	public function show_who_is_online()
	{
		$online_userlist = $this->cache->obtain_online_userlist($this->user->lang['.']);

		/* Список групп (для легенды) */
		$groups      = $this->cache->obtain_groups();
		$groups_list = '';

		foreach( $groups as $row )
		{
			if( !$row['group_legend'] )
			{
				continue;
			}

			$groups_link = '<span style="color: #' . $row['group_colour'] . ';">' . $this->user->lang($row['group_name']) . '</span>';

			$groups_list .= ( $groups_list ) ? ', ' . $groups_link : $groups_link;
		}

		$this->template->assign(array(
			'GROUPS_LIST'     => !empty($groups_list) ? $groups_list : '',
			'NEWEST_USER'     => $this->user_profile_link('', $this->config['newest_username'], false, $this->config['newest_user_id']),
			'ONLINE_LIST'     => $online_userlist['online_list'],
			'ONLINE_TIME'     => sprintf($this->user->lang['ONLINE_TIME'], $this->config['load_online_time'] / 60),
			'ONLINE_USERLIST' => $online_userlist['online_userlist'],
			'STAT_COMMENTS'   => num_format($this->config['num_comments']),
			'STAT_NEWS'       => num_format($this->config['num_news']),
			'STAT_USERS'      => num_format($this->config['num_users']),

			'S_WHO_IS_ONLINE' => true,

			'U_WHO_IS_ONLINE' => ilink($this->user->lang['URL_WHO_IS_ONLINE'])
		));
	}
	
	/**
	* Просмотр статичной страницы
	*/
	public function static_page()
	{
		$this->template->file = 'static_page_index.html';
	}
	
	/**
	* Ссылка на просмотр профиля
	*
	* @param	string	$mode		Режим вывода
	* @param	int		$id			ID пользователя
	* @param	string	$username	Ник пользователя
	* @param	string	$colour		Цвет ника
	* @param	int		$time		Время последней активности пользователя
	*
	* @return	string				Цветной ник пользователя (со ссылкой, если нужно)
	*/
	public function user_profile_link($mode, $username, $colour = false, $url = false, $id = false, $time = false)
	{
		switch( $mode )
		{
			/**
			* Строка без ссылки
			*/
			case 'plain':

				$colour = ( $colour ) ? ' style="color: #' . $colour . ';"' : '';

				return sprintf('<b%s>%s</b>', $colour, $username);

			break;
			/**
			* Ссылка на профиль
			*/
			case 'raw':

				return ( $url ) ? $this->get_handler_url('users::profile', array($url)) : $this->get_handler_url('users::profile', array($id));

			break;
			/**
			* Полная строка со ссылкой
			*/
			default:

				$colour = ( $colour ) ? ' style="color: #' . $colour . '; font-weight: bold;"' : '';
				$time   = ( $time ) ? ' title="' . $this->user->create_date($time, 'H:i', true) . '"' : '';
				$url    = ( $url ) ? $this->get_handler_url('users::profile', array($url)) : $this->get_handler_url('users::profile', array($id));

				return sprintf('<a href="%s"%s%s>%s</a>', $url, $colour, $time, $username);

			break;
		}
	}
	
	/**
	* Подсветка активных пунктов меню
	*/
	protected function recursive_set_menu_active_items(&$menu, $section_url)
	{
		static $page_url;
		
		if( !$page_url )
		{
			$page_url = ilink($this->full_url);
		}
		
		for( $i = 0, $len = sizeof($menu); $i < $len; $i++ )
		{
			if( $menu[$i]['URL'] == $section_url )
			{
				if( $page_url == $menu[$i]['URL'] )
				{
					$menu[$i]['ACTIVE'] = true;
					return;
				}
			}
			else
			{
				if( 0 === mb_strpos($page_url, $menu[$i]['URL']) )
				{
					$menu[$i]['ACTIVE'] = true;

					if( !empty($menu[$i]['children']) )
					{
						$this->recursive_set_menu_active_items($menu[$i]['children'], $menu[$i]['URL']);
					}
					
					return;
				}
			}
		}
	}
}
