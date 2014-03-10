<?php namespace fw\models;

use fw\helpers\traverse\tree\site_pages;
use fw\traits\breadcrumbs;
use fw\traits\injection;

/**
* Страница сайта
*/
class page
{
	use breadcrumbs, injection;
	
	public $data;
	public $format;
	public $full_url;
	public $handlers_urls = [];
	public $method;
	public $page;
	public $params;
	public $url;
	public $urls = [];
	
	protected $options = [];
	
	function __construct(array $options)
	{
		$this->options = $options;
	}
	
	/**
	* Предустановки
	* Вызов производится перед вызовом запрошенного обработчика
	*/
	public function _setup()
	{
	}
	
	/**
	* Включение дополнительных функций и фильтров для использования в шаблонах
	*/
	public function additional_tplengine_features()
	{
		$this->template->add_filter('declension', [$this->user, 'plural'])
			->add_filter('duration', [$this->user, 'create_time'])
			->add_filter('humn_size', [$this->user, 'humn_size'])
			->add_filter('i18n', [$this->user, 'lang'])
			->add_filter('number_format', [$this->user, 'num_format'])
			->add_filter('url_for', [$this, 'get_handler_url']);
		
		return $this;
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
		$url = $url ? ilink($url) : ilink($this->url);
		
		if (false !== strpos($url, '?')) {
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
		
		if (!$base_url) {
			$ary = pathinfo($this->url);
			$base_url = isset($ary['extension']) ? $ary['dirname'] : $this->url;
		}
		
		$url = $row['is_dir'] ? $row['page_url'] : ($row['page_url'] != $this->config['router.directory_index'] ? ($this->format ? sprintf('%s.%s', $row['page_url'], $this->format) : $row['page_url']) : '');
		
		return ilink(sprintf('%s/%s', $base_url, $url));
	}

	/**
	* Данные раздела (ветви дерева страниц)
	*/
	public function get_page_branch($page_id, $type = 'all', $order = 'descending', $include_self = true)
	{
		switch ($type) {
			case 'parents':  $condition = 'p1.left_id BETWEEN p2.left_id AND p2.right_id'; break;
			case 'children': $condition = 'p2.left_id BETWEEN p1.left_id AND p1.right_id'; break;
			default:         $condition = 'p2.left_id BETWEEN p1.left_id AND p1.right_id OR p1.left_id BETWEEN p2.left_id AND p2.right_id';
		}

		$rows = [];

		$sql = '
			SELECT
				p2.*
			FROM
				site_pages p1
			LEFT JOIN
				site_pages p2 ON (:condition)
			WHERE
				p1.site_id = ?
			AND
				p2.site_id = ?
			AND
				p1.page_id = ?
			ORDER BY
				p2.left_id :order';
		$this->db->query($sql, [$this->data['site_id'], $this->data['site_id'], $page_id, ':condition' => $condition, ':order' => $order == 'descending' ? 'ASC' : 'DESC']);

		while ($row = $this->db->fetchrow()) {
			if (!$include_self && $row['page_id'] == $page_id) {
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
		if (false === $page_id) {
			$page_id = $this->data['is_dir'] ? $this->data['page_id'] : $this->data['parent_id'];
		}
		
		$page_id = (int) $page_id;
		$rows = [];
		
		$sql = '
			SELECT
				*
			FROM
				site_pages
			WHERE
				parent_id = ?
			AND
				site_id = ?
			AND
				page_display > 0
			ORDER BY
				left_id ASC';
		$this->db->query($sql, [$page_id, $this->data['site_id']]);
		$rows = $this->db->fetchall();
		$this->db->freeresult();
		
		return $rows;
	}
	
	/**
	* Возврат ссылки на обработчик
	*/
	public function get_handler_url($handler, array $params = [])
	{
		if (0 === strpos($handler, '\\')) {
			/**
			* Обращение по абсолютному адресу
			* Чаще всего к модулям движка
			*
			* \fw\modules\gallery::index
			*/
			/* Разработчик знает, что подключает */
			if (isset($this->handlers_urls[$handler])) {
				return $this->get_url_with_params($this->handlers_urls[$handler], $params);
			}
			
			return;
		}
		
		/**
		* Обращение к методу текущего модуля
		*/
		if (false === strpos($handler, '::')) {
			if (isset($this->urls[$handler])) {
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
		
		if ($diff > 0) {
			if (false != $prefix = implode('\\', array_slice(explode('\\', $class), 0, $diff))) {
				$full_handler = $prefix . '\\' . $handler;
				
				if (isset($this->handlers_urls[$full_handler])) {
					return $this->get_url_with_params($this->handlers_urls[$full_handler], $params);
				}
			}
		}
		
		if (isset($this->handlers_urls[$handler])) {
			return $this->get_url_with_params($this->handlers_urls[$handler], $params);
		}
		
		return;
	}
	
	/**
	* Подстановка значений вместо параметров ($n)
	*
	* /проекты/$0/задачи/$1.html => /проекты/www.ru/задачи/важные.html
	*/
	public function get_url_with_params($url, array $params = [])
	{
		if (empty($params)) {
			return $url;
		}
		
		$ary = [];
		
		for ($i = 0, $len = sizeof($params); $i < $len; $i++) {
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
		
		if (0 === strpos($filename, 'app/')) {
			$filename = substr($filename, 4);
		}
		
		$this->user->load_language($filename);
		$this->user->load_language("{$filename}_{$this->method}");
		
		return $this;
	}
	
	/**
	* Карта ссылок на методы обработчика
	*/
	public function obtain_handlers_urls()
	{
		$options = [
			'default_extension' => $this->options['default_extension'],
			'directory_index'   => $this->options['directory_index'],
		];
		
		if (false === $this->handlers_urls = $this->cache->obtain_handlers_urls($this->data['site_id'], $this->request->language, $options)) {
			return $this;
		}

		$handler = get_class($this);
		
		if (0 === strpos($handler, 'app\\')) {
			$handler = substr($handler, 4);
		} elseif (0 === strpos($handler, 'fw\\')) {
			$handler = "\\{$handler}";
		}
		
		$pos = strlen($handler) + 2;

		foreach ($this->handlers_urls as $method => $url) {
			if (0 === strpos($method, $handler . '::')) {
				$this->urls[substr($method, $pos)] = $url;
			}
		}
		
		return $this;
	}

	public function page_header()
	{
		if (defined('HEADER_PRINTED')) {
			return $this;
		}
		
		/* Запрет кэширования страниц */
		header('Cache-Control: no-cache, pre-check=0, post-check=0');
		header('Expires: -1');
		header('Pragma: no-cache');

		/* Выпадающий список языков */
		$languages = $this->cache->obtain_languages();
		$sites     = $this->cache->obtain_sites();

		foreach ($sites as $row) {
			if ($this->request->server_name != $row['site_url']) {
				continue;
			}
			
			foreach ($languages as $ary) {
				if ($ary['language_title'] == $row['site_language']) {
					break;
				}
			}

			$this->template->append('languages', [
				'IMG'   => $row['site_language'],
				'NAME'  => $ary['language_name'],
				'TITLE' => $ary['language_title'],
				'URL'   => ilink('', $this->config['site.root_path'] . $row['site_language'])
			]);
			
			if ($this->request->language == $ary['language_title']) {
				$this->template->assign('S_LANGUAGE_DIRECTION', $ary['language_direction']);
			}
		}

		$this->template->assign([
			'S_BOT'             => $this->user->is_bot,
			'S_ISP'             => $this->request->isp,
			'S_LANGUAGE'        => $this->request->language,
			'S_OPENID_PROVIDER' => $this->user['openid_provider'],
			'S_SERVER_NAME'     => $this->request->server_name,
			'S_USER_REGISTERED' => $this->user->is_registered,
			'S_USERNAME'        => $this->user['username'],

			/* Ссылки */
			'U_INDEX'     => ilink(),
			'U_REGISTER'  => ilink($this->urls['_register']),
			'U_THIS_PAGE' => $this->user->get_back_url(),
			'U_SIGNIN'    => ilink($this->urls['_signin']),
			'U_SIGNOUT'   => ilink($this->urls['_signout']),
		]);

		define('HEADER_PRINTED', true);
		
		return $this;
	}

	public function page_footer()
	{
		$display_profiler = false;
		
		/* Вывод профайлера только для html-документов */
		if ($this->format == 'html' && !$this->request->is_ajax && !defined('IN_SQL_ERROR')) {
			$display_profiler = $this->profiler->is_enabled() && ($this->auth->acl_get('a_') || $this->profiler->is_permitted());
		}
		
		if ($this->template->file) {
			$this->template->assign('cfg', $this->config);
			$this->template->display();
			
			if ($display_profiler) {
				$this->user->load_language('profiler');
				$this->template->assign($this->profiler->get_stats());
				$this->template->display('profiler.html');
			}
		}
		
		$this->profiler->send_stats($this->request->hostname, $this->request->url);
		exit;
	}
	
	/**
	* Установка заголовка Content-type согласно запрашиваемому формату
	*/
	public function set_appropriate_content_type()
	{
		switch ($this->format) {
			case 'json': $type = 'application/json'; break;
			case 'xml':  $type = 'text/xml'; break;

			/* Веб-сервер по умолчанию устанавливает text/html */
			default: return true;
		}
		
		header('Content-type: ' . $type . '; charset=utf-8');
		
		return $this;
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
		if (!$this->format) {
			return $this;
		}
		
		$filename = str_replace('\\', '/', get_class($this));
		
		if (0 === strpos($filename, 'app/')) {
			$filename = substr($filename, 4);
		}
		
		if (0 === strpos($filename, 'fw/modules/')) {
			$filename = substr($filename, 11);
		}
		
		$this->template->file = "{$filename}_{$this->method}.{$this->format}";
		
		if ($this->request->is_ajax) {
			$this->template->file = "ajax/{$filename}_{$this->method}.{$this->format}";
		}
		
		return $this;
	}
	
	/**
	* Передача данных страницы шаблонизатору
	*/
	public function set_page_data()
	{
		$this->template->assign('page', $this->data);
		
		return $this;
	}

	/**
	* Задание заготовленных адресов
	* Главным образом: входа, выхода и регистрации
	*/
	public function set_preconfigured_urls($urls)
	{
		foreach ($urls as $key => $value) {
			$this->urls["_{$key}"] = $value;
		}
		
		return $this;
	}

	/**
	* Передача меню сайта шаблонизатору
	*/
	public function set_site_menu()
	{
		$menu = $this->cache->obtain_menu($this->data['site_id'], $this->request->language, [
			'default_extension' => $this->options['default_extension'],
			'directory_index'   => $this->options['directory_index'],
		]);
		
		$page_url = ilink($this->full_url);
		$root_url = ilink();
		
		foreach ($menu as $row) {
			if ($row['URL'] == $root_url) {
				if ($page_url == $row['URL']) {
					$row['ACTIVE'] = true;
				}
			} else {
				if (0 === mb_strpos($page_url, $row['URL'])) {
					$row['ACTIVE'] = true;
					
					if (!empty($row['children'])) {
						$this->recursive_set_menu_active_items($row['children'], $row['URL']);
					}
				}
			}
			
			$this->template->append('menu', $row);
		}
		
		return $this;
	}

	/**
	* Передача локального меню раздела шаблонизатору
	*/
	public function set_site_submenu()
	{
		$rows = $this->get_page_descendants();
		
		foreach ($rows as $row) {
			$this->template->append('submenu', [
				'ACTIVE' => $this->data['page_id'] == $row['page_id'],
				'IMAGE'  => $row['page_image'],
				'TITLE'  => $row['page_name'],
				
				'U_VIEW' => $this->descendant_link($row)
			]);
		}
	}
	
	/**
	* Кто сейчас на сайте
	*/
	public function show_who_is_online()
	{
		$online_userlist = $this->cache->obtain_online_userlist($this->request->language, $this->config['load_online_time']);

		/* Список групп (для легенды) */
		$groups      = $this->cache->obtain_groups();
		$groups_list = '';

		foreach ($groups as $row) {
			if (!$row['group_legend']) {
				continue;
			}

			$groups_link = '<span style="color: #' . $row['group_colour'] . ';">' . $this->user->lang($row['group_name']) . '</span>';

			$groups_list .= $groups_list ? ', ' . $groups_link : $groups_link;
		}

		$this->template->assign([
			'GROUPS_LIST'     => !empty($groups_list) ? $groups_list : '',
			'NEWEST_USER'     => $this->user_profile_link('', $this->config['newest_username'], false, $this->config['newest_user_id']),
			'ONLINE_LIST'     => $online_userlist['online_list'],
			'ONLINE_TIME'     => sprintf($this->user->lang['ONLINE_TIME'], $this->config['load_online_time'] / 60),
			'ONLINE_USERLIST' => $online_userlist['online_userlist'],
			'STAT_COMMENTS'   => $this->config['num_comments'],
			'STAT_NEWS'       => $this->config['num_news'],
			'STAT_USERS'      => $this->config['num_users'],

			'S_WHO_IS_ONLINE' => true,
		]);
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
		switch ($mode) {
			/**
			* Строка без ссылки
			*/
			case 'plain':

				$colour = $colour ? ' style="color: #' . $colour . ';"' : '';

				return sprintf('<b%s>%s</b>', $colour, $username);

			break;
			/**
			* Ссылка на профиль
			*/
			case 'raw':

				return $url ? $this->get_handler_url('users::profile', [$url]) : $this->get_handler_url('users::profile', [$id]);

			break;
			/**
			* Полная строка со ссылкой
			*/
			default:

				$colour = $colour ? ' style="color: #' . $colour . '; font-weight: bold;"' : '';
				$time   = $time ? ' title="' . $this->user->create_date($time, 'H:i', true) . '"' : '';
				$url    = $url ? $this->get_handler_url('users::profile', [$url]) : $this->get_handler_url('users::profile', [$id]);

				return sprintf('<a href="%s"%s%s>%s</a>', $url, $colour, $time, $username);
		}
	}
	
	/**
	* Подключение дополнительного меню
	*/
	protected function append_menu($alias)
	{
		$language = $this->request->language;
		$menu_id  = 0;
		
		foreach ($this->cache->obtain_menus() as $key => $ary) {
			if ($key == $alias) {
				$menu_id = $ary['menu_id'];
				break;
			}
		}
		
		if (!$menu_id) {
			return $this;
		}
		
		if (false === $menu = $this->cache->get("menu_{$menu_id}_{$language}")) {
			$sql = 'SELECT * FROM site_pages WHERE site_id = ? ORDER BY left_id ASC';
			$this->db->query($sql, [$this->data['site_id']]);
			
			$traversal = new traverse_menu([
				'default_extension' => $this->options['default_extension'],
				'directory_index'   => $this->options['directory_index'],
				'menu_id'           => $menu_id,
				'return_as_tree'    => true,
			]);
			
			while ($row = $this->db->fetchrow()) {
				$traversal->process_node($row);
			}
			
			$this->db->freeresult();
			$menu = $traversal->get_tree_data();
			
			$this->cache->set("menu_{$menu_id}_{$language}", $menu);
		}
		
		$page_url = ilink($this->full_url);
		$root_url = ilink();
		
		foreach ($menu as $row) {
			if ($row['URL'] == $root_url) {
				if ($page_url == $row['URL']) {
					$row['ACTIVE'] = true;
				}
			} else {
				if (0 === mb_strpos($page_url, $row['URL'])) {
					$row['ACTIVE'] = true;
					
					if (!empty($row['children'])) {
						$this->recursive_set_menu_active_items($row['children'], $row['URL']);
					}
				}
			}
			
			$this->template->append($alias, $row);
		}
		
		return $this;
	}

	/**
	* Установка SEO-параметров
	*/
	protected function append_seo_params($row)
	{
		$this->data['page_title'] = !empty($row['seo_title']) ? $row['seo_title'] : $this->data['page_title'];
		$this->data['page_keywords'] = !empty($row['seo_keys']) ? $row['seo_keys'] : $this->data['page_keywords'];
		$this->data['page_description'] = !empty($row['seo_desc']) ? $row['seo_desc'] : $this->data['page_description'];
		$this->set_page_data();
		
		return $this;
	}

	/**
	* Подсветка активных пунктов меню
	*/
	protected function recursive_set_menu_active_items(&$menu, $section_url)
	{
		static $page_url;
		
		if (!$page_url) {
			$page_url = ilink($this->full_url);
		}
		
		for ($i = 0, $len = sizeof($menu); $i < $len; $i++) {
			if ($menu[$i]['URL'] == $section_url) {
				if ($page_url == $menu[$i]['URL']) {
					$menu[$i]['ACTIVE'] = true;
					return;
				}
			} else {
				if (0 === mb_strpos($page_url, $menu[$i]['URL'])) {
					$menu[$i]['ACTIVE'] = true;

					if (!empty($menu[$i]['children'])) {
						$this->recursive_set_menu_active_items($menu[$i]['children'], $menu[$i]['URL']);
					}
					
					return;
				}
			}
		}
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
			'ID'       => $this->row['page_id'],
			'IMAGE'    => $this->row['page_image'],
			'TITLE'    => $this->row['page_name'],
			'URL'      => $ary['url'],
			'children' => []
		];
	}
	
	protected function skip_condition()
	{
		return !$this->row['page_enabled'] || $this->row["display_in_menu_{$this->options['menu_id']}"] != 1;
	}
}
