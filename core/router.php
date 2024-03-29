<?php namespace fw\core;

use fw\traits\breadcrumbs;
use fw\traits\injection;

/**
* Маршрутизатор запросов
*/
class router
{
	use breadcrumbs, injection;
	
	public $format;
	public $handler;
	public $method;
	public $page;
	public $page_link = [];
	public $page_row;
	public $site_id;
	public $url;
	
	protected $options = [
		'allowed_extensions' => 'html',
		'default_extension'  => 'html',
		'directory_index'    => 'index',
		'send_status_code'   => false,
	];
	
	protected $namespace;
	protected $params = [];
	protected $params_count;

	function __construct(array $options = [])
	{
		$this->options = array_merge($this->options, $options);
		
		$this->page = $this->options['directory_index'];
	}
	
	public function _init($url = '', $namespace = '\\app\\')
	{
		$this->format    = $this->options['default_extension'];
		$this->namespace = $namespace;
		$this->page      = $this->options['directory_index'];
		$this->site_id   = $this->site_info['id'];
		$this->url       = $url ?: $this->request->url;
		
		if (0 === strpos($this->url, "/{$this->request->language}/")) {
			if ($this->site_info['default']) {
				/* Если выбрана локализация по умолчанию, то убираем язык из URL */
				$this->request->redirect(ilink(mb_substr($this->request->url, 3)));
			}
			
			$this->url = mb_substr($this->url, 3);
		}
		
		if (false !== $query_string_pos = strpos($this->url, '?')) {
			$this->url = substr($this->url, 0, $query_string_pos);
		}
		
		$ary = pathinfo(trim($this->url, '/'));
		
		if (isset($ary['extension'])) {
			/* Обращение к странице */
			if (!in_array($ary['extension'], explode(';', $this->options['allowed_extensions']), true)) {
				trigger_error('PAGE_NOT_FOUND');
			}
			
			$this->format = $ary['extension'];
			$this->params = $ary['dirname'] != '.' ? explode('/', $ary['dirname']) : [];
			$this->page   = $ary['filename'];
			$this->url    = $ary['dirname'] != '.' ? $ary['dirname'] : '';
		} elseif (substr($this->url, -1) != '/') {
			/**
			* Обращение к странице без расширения
			* Проверяем, можно ли обращаться к страницам без расширения
			*/
			if (!in_array('', explode(';', $this->options['allowed_extensions']), true)) {
				/* Перенаправление на одноименный каталог */
				$this->request->redirect(ilink($this->url), 301);
			}

			$this->params = $ary['dirname'] != '.' ? explode('/', $ary['dirname']) : [];
			$this->page   = $ary['filename'];
		} elseif ($this->url && $this->url != '/') {
			/* Обращение к каталогу */
			$this->params = explode('/', trim($this->url, '/'));
		}
		
		$this->params_count = sizeof($this->params);
		
		return $this;
	}
	
	/**
	* Параметры URL
	*/
	public function get_params()
	{
		return $this->params;
	}
	
	/**
	* Количество параметров в URL
	*/
	public function get_params_count()
	{
		return $this->params_count;
	}
	
	/**
	* Обработка URL и загрузка необходимого обработчика
	*/
	public function handle_request()
	{
		$handler_name = $handler_method = '';
		$parent_id = 0;
		$redirect = '';

		/**
		* /[игры/diablo2]/скриншоты.html
		* /[users/a/admin]/posts.html
		*/
		for ($i = 0; $i < $this->params_count; $i++) {
			if (false == $row = $this->get_page_row_by_url($this->params[$i], true, $parent_id)) {
				if ($redirect) {
					errorhandler::log_mail("Page http://{$this->request->server_name}{$this->request->url} not found and redirected to {$redirect}", "404 Not Found", 404);
					
					/* Выход для использования редиректа родительской страницы */
					break;
				}
				
				trigger_error('PAGE_NOT_FOUND');
			}
			
			/**
			* Если редирект установлен у родительской страницы,
			* то автоматически становятся недоступны все вложенные
			*/
			if (!empty($row) && $row['page_redirect']) {
				$redirect = $row['page_redirect'];
			}

			if ($row['page_handler'] && $row['handler_method']) {
				$handler_name   = $row['page_handler'];
				$handler_method = $row['handler_method'];
			} else {
				$handler_method = 'static_page';
			}
			
			$this->page_link[] = $this->params[$i];
			
			$parent_id = (int) $row['page_id'];
			
			if ($row['page_url'] != '*') {
				$this->breadcrumbs($row['page_name'], ilink(implode('/', $this->page_link)), $row['page_image']);
				
				unset($this->params[$i]);
			}
		}
		
		/**
		* /[index.html]
		* /[объявление.html]
		*
		* или
		*
		* /ucp/[login.html]
		*/
		for (;;) {
			if (!$this->params_count || ($this->params_count > 0 && $this->page != $this->options['directory_index'])) {
				if (false == $row = $this->get_page_row_by_url($this->page, false, $parent_id)) {
					if ($redirect) {
						errorhandler::log_mail("Page http://{$this->request->server_name}{$this->request->url} not found and redirected to {$redirect}", "404 Not Found", 404);

						/* Выход для использования редиректа родительской страницы */
						break;
					}
					
					trigger_error('PAGE_NOT_FOUND');
				}
			
				if (!empty($row) && $row['page_redirect']) {
					$redirect = $row['page_redirect'];
				}

				if ($row['page_handler'] && $row['handler_method']) {
					$handler_name   = $row['page_handler'];
					$handler_method = $row['handler_method'];
				} elseif ($this->params_count > 0) {
					$handler_method = 'static_page';
				}
			
				if ($this->page != $this->options['directory_index']) {
					$this->page_link[] = $this->format ? "{$this->page}.{$this->format}" : $this->page;
				}

				if ($row['page_url'] != '*') {
					$this->breadcrumbs($row['page_name'], ilink(implode('/', $this->page_link)), $row['page_image']);
				}
			}
			
			break;
		}

		if ($redirect) {
			$this->request->redirect(ilink($redirect), 301);
		}

		if (!$row) {
			/* На сайте еще нет ни одной страницы */
			trigger_error('PAGE_NOT_FOUND');
		}
		
		if (!in_array($this->format, explode(';', $row['page_formats']), true)) {
			trigger_error('PAGE_NOT_FOUND');
		}

		$row['site_id'] = (int) $row['site_id'];

		/* Сбрасывание счетчика индексов */
		$this->params = array_values($this->params);
		$this->params_count = sizeof($this->params);
		
		$this->page_row = $row;
		
		/* Статичная страница */
		if (!$handler_name || !$handler_method) {
			return $this->load_handler('models\\page', 'static_page');
		}
		
		return $this->load_handler($handler_name, $handler_method, $this->params);
	}

	/**
	* Загрузка модуля
	*/
	protected function load_handler($handler, $method, $params = [])
	{
		$class_name = 0 !== strpos($handler, '\\') ? $this->namespace . $handler : $handler;
		
		$this->handler = new $class_name($this->options);
		$this->method  = $method;
		
		if (!$this->load_handler_with_params($params)) {
			return false;
		}
		
		return true;
	}
	
	/**
	* Загрузка модуля с параметрами
	*/
 	protected function load_handler_with_params($params = [])
	{
		$concrete_method = "{$this->method}_{$this->request->method}";

		/* Проверка существования необходимого метода у обработчика */
		if (!method_exists($this->handler, $concrete_method) && !method_exists($this->handler, $this->method)) {
			if ($this->options['send_status_code']) {
				/**
				* API-сайт должен отправлять соответствующие коды состояния HTTP
				*/
				if ($this->request->method == 'get' || !method_exists($this->handler, "{$this->method}_get")) {
					/* Not Implemented */
					http_response_code(501);
				} else {
					/* Method Not Allowed */
					http_response_code(405);
				}
				
				return false;
			} else {
				/* Обычный сайт может сразу возвращать 404 Not Found */
				http_response_code(404);
				return false;
			}
		}
		
		$full_url = $this->url . ($this->page != $this->options['directory_index'] ? ($this->format ? "/{$this->page}.{$this->format}" : $this->page) : '');
		
		/* Параметры обработчика */
		$this->handler->data     = $this->page_row;
		$this->handler->format   = $this->format;
		$this->handler->full_url = $full_url;
		$this->handler->method   = $this->method;
		$this->handler->page     = $this->page;
		$this->handler->params   = $params;
		$this->handler->url      = implode('/', $this->page_link);
		
		/* Настройка обработчика */
		$this->handler->_set_app($this->app)
			->additional_tplengine_features()
			->load_translations()
			->obtain_handlers_urls()
			->set_preconfigured_urls($this->app['urls'])
			->set_default_template()
			->set_site_menu()
			->set_page_data()
			->set_appropriate_content_type();
		
		/* Предустановки */
		if (method_exists($this->handler, '_setup')) {
			call_user_func([$this->handler, '_setup']);
		}
		
		if (method_exists($this->handler, $concrete_method)) {
			/**
			* Попытка вызвать метод с суффиксом в виде HTTP метода
			* GET index -> index_get
			* PUT single -> single_put
			*/
			call_user_func_array([$this->handler, $concrete_method], $params);
			$this->call_with_format($concrete_method, $params);
		} else {
			call_user_func_array([$this->handler, $this->method], $params);
			$this->call_with_format($this->method, $params);
		}
		
		$this->handler->page_header()
			->page_footer();
	}
	
	/**
	* Попытка вызвать метод с суффиксом в виде формата документа
	*/
	protected function call_with_format($method, $params)
	{
		if (!$this->format) {
			return;
		}
		
		$method = "{$method}_{$this->format}";
		
		if (method_exists($this->handler, $method)) {
			call_user_func_array([$this->handler, $method], $params);
		}
	}
	
	/**
	* Данные страницы
	*/
	protected function get_page_row_by_url($page_url, $is_dir = 1, $parent_id = 0)
	{
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
				page_url IN (?, ?)
			AND
				is_dir = ?
			AND
				page_enabled = 1
			ORDER BY
				LENGTH(page_url) DESC';
		$this->db->query_limit($sql, [$parent_id, $this->site_id, $page_url, '*', $is_dir], 1);
		$row = $this->db->fetchrow();
		$this->db->freeresult();
		
		/* Загрузка блока */
		if (!$row && !$is_dir && $parent_id && function_exists('get_page_block')) {
			return get_page_block($page_url, $parent_id, 'pages');
		}
		
		return $row;
	}
}
