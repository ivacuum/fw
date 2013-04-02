<?php
/**
* @package fw
* @copyright (c) 2012
*/

namespace fw\modules;

use app\models\page;

/**
* Управление страницами
*/
class pages extends page
{
	public function _setup()
	{
		if (!$this->auth->acl_get('a_'))
		{
			trigger_error('PAGE_NOT_FOUND');
		}
		
		parent::_setup();
	}
	
	public function index()
	{
		$this->template->file = 'pages.html';
		
		$parent_id = $this->request->variable('parent_id', 0);
		$action    = $this->request->variable('action', '');
		$back_url  = $this->url . '?parent_id=' . $parent_id;
		$errors    = [];
		$page_id   = $this->request->variable('pid', 0);
		$submit    = $this->request->is_set_post('submit');
		
		switch ($action)
		{
			case 'delete':

				if (!$page_id)
				{
					trigger_error('NO_PAGE_ID', E_USER_WARNING);
				}

				// Make sure we are not directly within a page
				if ($page_id == $parent_id)
				{
					$sql = '
						SELECT
							parent_id
						FROM
							' . PAGES_TABLE . '
						WHERE
							page_id = ' . $page_id;
					$this->db->query($sql);
					$parent_id = (int) $this->db->fetchfield('parent_id');
					$this->db->freeresult();
				}

				$errors = $this->delete_page($page_id);
					
				if (!sizeof($errors))
				{
					$this->remove_cache_file();
					$this->request->redirect($this->append_link_params(sprintf('parent_id=%d', $parent_id)));
					trigger_error('PAGE_DELETED');
				}

			break;
			case 'enable':
			case 'disable':

				if (!$page_id)
				{
					trigger_error('NO_PAGE_ID', E_USER_WARNING);
				}

				$sql = '
					SELECT
						*
					FROM
						' . PAGES_TABLE . '
					WHERE
						site_id = ' . $this->db->check_value($this->data['site_id']) . '
					AND
						page_id = ' . $page_id;
				$this->db->query($sql);
				$row = $this->db->fetchrow();
				$this->db->freeresult();

				if (!$row)
				{
					trigger_error('NO_PAGE', E_USER_WARNING);
				}

				$sql = '
					UPDATE
						' . PAGES_TABLE . '
					SET
						page_enabled = ' . ($action == 'enable' ? 1 : 0) . '
					WHERE
						site_id = ' . $this->db->check_value($this->data['site_id']) . '
					AND
						page_id = ' . $page_id;
				$this->db->query($sql);

				$this->remove_cache_file();

			break;
			case 'move_up':
			case 'move_down':

				if (!$page_id)
				{
					trigger_error('NO_PAGE_ID', E_USER_WARNING);
				}

				$sql = '
					SELECT
						*
					FROM
						' . PAGES_TABLE . '
					WHERE
						site_id = ' . $this->db->check_value($this->data['site_id']) . '
					AND
						page_id = ' . $page_id;
				$this->db->query($sql);
				$row = $this->db->fetchrow();
				$this->db->freeresult();

				if (!$row)
				{
					trigger_error('NO_PAGE', E_USER_WARNING);
				}

				$move_page_name = $this->move_page_by($row, $action, 1);

				if (false !== $move_page_name)
				{
					$this->remove_cache_file();
				}

			break;
			case 'edit':

				if (!$page_id)
				{
					trigger_error('NO_PAGE_ID', E_USER_WARNING);
				}

				$page_row = $this->get_page_row($page_id);

			// no break
			case 'add':
			
				if ($action == 'add')
				{
					$page_row = [
						'page_name'      => $this->request->variable('page_name', ''),
						'page_title'     => '',
						'page_url'       => seo_url($this->request->variable('page_name', '')),
						'page_formats'   => '',
						'parent_id'      => 0,
						'is_dir'         => 1,
						'page_redirect'  => '',
						'page_enabled'   => 1,
						'page_display'   => 0,
						'page_handler'   => '',
						'handler_method' => '',
						'page_noindex'   => 0,
						'page_image'     => '',
						'page_text'      => ''
					];
				}

				$page_data = [
					'page_name'      => $this->request->variable('page_name', (string) $page_row['page_name']),
					'page_title'     => $this->request->variable('page_title', (string) $page_row['page_title']),
					'page_url'       => $this->request->variable('page_url', (string) $page_row['page_url']),
					'page_formats'   => $this->request->variable('page_formats', (string) $page_row['page_formats']),
					'site_id'        => $this->data['site_id'],
					'parent_id'      => $this->request->variable('parent_id', (int) $page_row['parent_id']),
					'is_dir'         => $this->request->variable('is_dir', (int) $page_row['is_dir']),
					'page_redirect'  => $this->request->variable('page_redirect', (string) $page_row['page_redirect']),
					'page_enabled'   => $this->request->variable('page_enabled', (int) $page_row['page_enabled']),
					'page_display'   => $this->request->variable('page_display', (int) $page_row['page_display']),
					'page_handler'   => $this->request->variable('page_handler', (string) $page_row['page_handler']),
					'handler_method' => $this->request->variable('handler_method', (string) $page_row['handler_method']),
					'page_noindex'   => $this->request->variable('page_noindex', (int) $page_row['page_noindex']),
					'page_image'     => $this->request->variable('page_image', (string) $page_row['page_image']),
					'page_text'      => $this->request->is_set('page_text') ? $_REQUEST['page_text'] : (string) $page_row['page_text']
				];

				if ($submit)
				{
					if (!$page_data['page_name'])
					{
						trigger_error('NO_PAGE_NAME', E_USER_WARNING);
					}

					if ($action == 'edit')
					{
						$page_data['page_id'] = $page_id;
					}

					$errors = $this->update_page_data($page_data);

					if (!sizeof($errors))
					{
						$this->remove_cache_file();

						$this->request->redirect($this->append_link_params(sprintf('parent_id=%d', $parent_id)));
						trigger_error($action == 'add' ? 'PAGE_ADDED' : 'PAGE_EDITED');
					}
				}

				$s_cat_option = '<option value="0"' . ($page_data['parent_id'] == 0 ? ' selected="selected"' : '') . '>' . 'NO_PARENT' . '</option>';

				$this->template->assign(array_merge([
					'S_EDIT_PAGE'   => true,
					'S_IS_DIR'      => $page_data['is_dir'],
					'S_CAT_OPTIONS' => $s_cat_option . $this->make_page_select($page_data['parent_id'], ($action == 'edit') ? $page_row['page_id'] : false, false, true),
					'U_BACK'        => $back_url,
					'U_EDIT_ACTION' => ilink($this->url . '?parent_id=' . $parent_id),

					'PAGENAME' => $page_data['page_name'],
					'ACTION'   => $action,
					'PAGE_ID'  => $page_id,
				],
					array_change_key_case($page_data, CASE_UPPER)
				));

				if (sizeof($errors))
				{
					$this->template->assign([
						'S_ERROR'   => true,
						'ERROR_MSG' => implode('<br>', $errors)
					]);
				}

				return;

			break;
		}

		// Default management page
		if (sizeof($errors))
		{
			$this->template->assign([
				'S_ERROR'   => true,
				'ERROR_MSG' => implode('<br>', $errors)
			]);
		}

		if (!$parent_id)
		{
			$navigation = 'root';
		}
		else
		{
			$navigation = '<a href="' . ilink($this->url) . '">root</a>';

			$pages_nav = $this->get_page_branch($parent_id, 'parents', 'descending');

			foreach ($pages_nav as $row)
			{
				if ($row['page_id'] == $parent_id)
				{
					$navigation .= ' &raquo; ' . $row['page_name'];
				}
				else
				{
					$navigation .= ' &raquo; <a href="' . ilink($this->url . '?parent_id=' . $row['page_id']) . '">' . $row['page_name'] . '</a>';
				}
			}
		}

		$sql = '
			SELECT
				*
			FROM
				' . PAGES_TABLE . '
			WHERE
				site_id = ' . $this->db->check_value($this->data['site_id']) . '
			AND
				parent_id = ' . $parent_id . '
			ORDER BY
				left_id ASC';
		$result = $this->db->query($sql);

		if ($row = $this->db->fetchrow($result))
		{
			do
			{
				// $page_image = $row['page_image'] ? $row['page_image'] : ($row['is_dir'] ? 'folder' : 'blog');
				$page_image = $row['is_dir'] ? 'folder_open' : 'blog';

				$url = ilink($this->url . '?parent_id=' . $parent_id . '&amp;pid=' . $row['page_id']);
				
				$this->template->append('pages', [
					'IS_DIR'         => $row['is_dir'],
					'PAGE_IMAGE'     => $page_image,
					'PAGE_IMG'       => $row['page_image'],
					'PAGE_NAME'      => $row['page_name'],
					'PAGE_TITLE'     => $row['page_title'],
					'PAGE_REDIRECT'  => $row['page_redirect'],
					'PAGE_ENABLED'   => $row['page_enabled'],
					'PAGE_DISPLAYED' => $row['page_display'],
					'PAGE_URL'       => $row['page_url'],
					'PAGE_FORMATS'   => $row['page_formats'],
					'PAGE_NOINDEX'   => $row['page_noindex'],
					'PAGE_HANDLER'   => $row['page_handler'],
					'HANDLER_METHOD' => $row['handler_method'],

					'U_PAGE'      => ilink($this->url . '?parent_id=' . $row['page_id']),
					'U_MOVE_UP'   => $url . '&amp;action=move_up',
					'U_MOVE_DOWN' => $url . '&amp;action=move_down',
					'U_EDIT'      => $url . '&amp;action=edit',
					'U_DELETE'    => $url . '&amp;action=delete',
					'U_ENABLE'    => $url . '&amp;action=enable',
					'U_DISABLE'   => $url . '&amp;action=disable'
				]);
			}
			while ($row = $this->db->fetchrow());

			$this->template->assign('S_NO_PAGES', false);
		}
		elseif ($parent_id)
		{
			$row = $this->get_page_row($parent_id);
			$url = ilink($this->url . '?parent_id=' . $parent_id . '&amp;pid=' . $row['page_id']);

			$this->template->assign([
				'S_NO_PAGES'     => true,
				'PAGE_NAME'      => $row['page_name'],
				'PAGE_ENABLED'   => $row['page_enabled'],
				'PAGE_DISPLAYED' => $row['page_display'],

				'U_EDIT'    => $url . '&amp;action=edit',
				'U_DELETE'  => $url . '&amp;action=delete',
				'U_ENABLE'  => $url . '&amp;action=enable',
				'U_DISABLE' => $url . '&amp;action=disable'
			]);
		}

		$this->db->freeresult($result);

		$this->template->assign([
			'U_SEL_ACTION' => "",
			'U_ACTION'     => ilink($this->url . '?parent_id=' . $parent_id),
			'NAVIGATION'   => $navigation,
			'PARENT_ID'    => $parent_id
		]);
	}
	
	/**
	* Удаление страницы
	*/
	function delete_page($page_id)
	{
		$row = $this->get_page_row($page_id);
		$branch = $this->get_page_branch($page_id, 'children', 'descending', false);

		if (sizeof($branch))
		{
			return ['CANNOT_REMOVE_PAGE'];
		}

		$diff = 2;
		$sql = '
			DELETE
			FROM
				' . PAGES_TABLE . '
			WHERE
				site_id = ' . $this->db->check_value($this->data['site_id']) . '
			AND
				page_id = ' . $page_id;
		$this->db->query($sql);

		$row['right_id'] = (int) $row['right_id'];
		$row['left_id'] = (int) $row['left_id'];

		/* Синхронизация дерева */
		$sql = '
			UPDATE
				' . PAGES_TABLE . '
			SET
				right_id = right_id - ' . $diff . '
			WHERE
				site_id = ' . $this->db->check_value($this->data['site_id']) . '
			AND
				left_id < ' . $row['right_id'] . '
			AND
				right_id > ' . $row['right_id'];
		$this->db->query($sql);

		$sql = '
			UPDATE
				' . PAGES_TABLE . '
			SET
				left_id = left_id - ' . $diff . ',
				right_id = right_id - ' . $diff . '
			WHERE
				site_id = ' . $this->db->check_value($this->data['site_id']) . '
			AND
				left_id > ' . $row['right_id'];
		$this->db->query($sql);

		return [];
	}

	/**
	* Данные раздела (ветви дерева страниц)
	*/
	function get_page_branch($page_id, $type = 'all', $order = 'descending', $include_self = true)
	{
		switch ($type)
		{
			case 'parents':  $condition = 'p1.left_id BETWEEN p2.left_id AND p2.right_id'; break;
			case 'children': $condition = 'p2.left_id BETWEEN p1.left_id AND p1.right_id'; break;
			default:         $condition = 'p2.left_id BETWEEN p1.left_id AND p1.right_id OR p1.left_id BETWEEN p2.left_id AND p2.right_id';
		}

		$rows = [];

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
				p2.left_id ' . ($order == 'descending' ? 'ASC' : 'DESC');
		$this->db->query($sql);

		while ($row = $this->db->fetchrow())
		{
			if (!$include_self && $row['page_id'] == $page_id)
			{
				continue;
			}

			$rows[] = $row;
		}

		$this->db->freeresult();

		return $rows;
	}

	/**
	* Данные страницы
	*/
	function get_page_row($page_id)
	{
		$sql = '
			SELECT
				*
			FROM
				' . PAGES_TABLE . '
			WHERE
				site_id = ' . $this->db->check_value($this->data['site_id']) . '
			AND
				page_id = ' . $this->db->check_value($page_id);
		$this->db->query($sql);
		$row = $this->db->fetchrow();
		$this->db->freeresult();

		if (!$row)
		{
			trigger_error('NO_PAGE', E_USER_WARNING);
		}

		return $row;
	}
	
	/**
	* Список страниц (Jumpbox)
	*/
	function make_page_select($select_id = false, $ignore_id = false, $ignore_emptycat = true, $ignore_noncat = false)
	{
		$sql = '
			SELECT
				page_id,
				parent_id,
				left_id,
				right_id,
				is_dir,
				page_enabled,
				page_name,
				page_handler
			FROM
				' . PAGES_TABLE . '
			WHERE
				site_id = ' . $this->db->check_value($this->data['site_id']) . '
			ORDER BY
				left_id ASC';
		$this->db->query($sql);

		$right = 0;
		$padding_store = ['0' => ''];
		$page_list = $padding = '';

		while ($row = $this->db->fetchrow())
		{
			if ($row['left_id'] < $right)
			{
				$padding .= '&nbsp; &nbsp;';
				$padding_store[$row['parent_id']] = $padding;
			}
			elseif ($row['left_id'] > $right + 1)
			{
				$padding = isset($padding_store[$row['parent_id']]) ? $padding_store[$row['parent_id']] : '';
			}

			$right = $row['right_id'];

			/* Пропускаем ненужные страницы */
			if ((is_array($ignore_id) && in_array($row['page_id'], $ignore_id)) || $row['page_id'] == $ignore_id)
			{
				continue;
			}

			/* Пустые папки */
			if ($row['is_dir'] && ($row['left_id'] + 1 == $row['right_id']) && $ignore_emptycat)
			{
				continue;
			}
			
			/* Пропускаем страницы, оставляем только папки */
			if (!$row['is_dir'] && $ignore_noncat)
			{
				continue;
			}

			$selected = is_array($select_id) ? (in_array($row['page_id'], $select_id) ? ' selected="selected"' : '') : ($row['page_id'] == $select_id ? ' selected="selected"' : '');

			$page_list .= '<option value="' . $row['page_id'] . '"' . $selected . (!$row['page_enabled'] ? ' class="disabled"' : '') . '>' . $padding . $row['page_name'] . '</option>';
		}

		$this->db->freeresult();

		unset($padding_store);

		return $page_list;
	}
	
	/**
	* Перемещение страницы в дереве
	*/
	function move_page($from_page_id, $to_parent_id)
	{
		$moved_pages = $this->get_page_branch($from_page_id, 'children', 'descending');
		$from_data = $moved_pages[0];
		$diff = sizeof($moved_pages) * 2;

		$moved_ids = [];
		for ($i = 0, $len = sizeof($moved_pages); $i < $len; ++$i)
		{
			$moved_ids[] = $moved_pages[$i]['page_id'];
		}

		/* Синхронизация родителей */
		$sql = '
			UPDATE
				' . PAGES_TABLE . '
			SET
				right_id = right_id - ' . $diff . '
			WHERE
				site_id = ' . $this->db->check_value($this->data['site_id']) . '
			AND
				left_id < ' . (int) $from_data['right_id'] . '
			AND
				right_id > ' . (int) $from_data['right_id'];
		$this->db->query($sql);

		/* Синхронизация правой части дерева */
		$sql = '
			UPDATE
				' . PAGES_TABLE . '
			SET
				left_id = left_id - ' . $diff . ',
				right_id = right_id - ' . $diff . '
			WHERE
				site_id = ' . $this->db->check_value($this->data['site_id']) . '
			AND
				left_id > ' . (int) $from_data['right_id'];
		$this->db->query($sql);

		if ($to_parent_id > 0)
		{
			$to_data = $this->get_page_row($to_parent_id);

			/* Синхронизация новых родителей */
			$sql = '
				UPDATE
					' . PAGES_TABLE . '
				SET
					right_id = right_id + ' . $diff . '
				WHERE
					site_id = ' . $this->db->check_value($this->data['site_id']) . '
				AND
					' . (int) $to_data['right_id'] . ' BETWEEN left_id AND right_id
				AND
					' . $this->db->in_set('page_id', $moved_ids, true);
			$this->db->query($sql);

			/* Синхронизация правой части дерева */
			$sql = '
				UPDATE
					' . PAGES_TABLE . '
				SET
					left_id = left_id + ' . $diff . ',
					right_id = right_id + ' . $diff . '
				WHERE
					site_id = ' . $this->db->check_value($this->data['site_id']) . '
				AND
					left_id > ' . (int) $to_data['right_id'] . '
				AND
					' . $this->db->in_set('page_id', $moved_ids, true);
			$this->db->query($sql);

			/* Синхронизация перемещенной ветви */
			$to_data['right_id'] += $diff;
			
			if ($to_data['right_id'] > $from_data['right_id'])
			{
				$diff = '+ ' . ($to_data['right_id'] - $from_data['right_id'] - 1);
			}
			else
			{
				$diff = '- ' . abs($to_data['right_id'] - $from_data['right_id'] - 1);
			}
		}
		else
		{
			$sql = '
				SELECT
					MAX(right_id) AS right_id
				FROM
					' . PAGES_TABLE . '
				WHERE
					site_id = ' . $this->db->check_value($this->data['site_id']) . '
				AND
					' . $this->db->in_set('page_id', $moved_ids, true);
			$this->db->query($sql);
			$row = $this->db->fetchrow();
			$this->db->freeresult();

			$diff = '+ ' . (int) ($row['right_id'] - $from_data['left_id'] + 1);
		}

		$sql = '
			UPDATE
				' . PAGES_TABLE . '
			SET
				left_id = left_id ' . $diff . ',
				right_id = right_id ' . $diff . '
			WHERE
				site_id = ' . $this->db->check_value($this->data['site_id']) . '
			AND
				' . $this->db->in_set('page_id', $moved_ids);
		$this->db->query($sql);
	}

	/**
	* Перемещение страницы на $steps уровней вверх/вниз
	*/
	function move_page_by($page_row, $action = 'move_up', $steps = 1)
	{
		/**
		* Fetch all the siblings between the page's current spot
		* and where we want to move it to. If there are less than $steps
		* siblings between the current spot and the target then the
		* page will move as far as possible
		*/
		$sql = '
			SELECT
				page_id,
				left_id,
				right_id,
				page_name
			FROM
				' . PAGES_TABLE . '
			WHERE
				site_id = ' . $this->db->check_value($this->data['site_id']) . '
			AND
				parent_id = ' . (int) $page_row['parent_id'] . '
			AND
				' . ($action == 'move_up' ? 'right_id < ' . (int) $page_row['right_id'] . ' ORDER BY right_id DESC' : 'left_id > ' . (int) $page_row['left_id'] . ' ORDER BY left_id ASC');
		$this->db->query_limit($sql, $steps);
		$target = [];

		while ($row = $this->db->fetchrow())
		{
			$target = $row;
		}

		$this->db->freeresult();

		if (!sizeof($target))
		{
			/* Страница уже в самом верху или низу дерева */
			return false;
		}

		/**
		* $left_id and $right_id define the scope of the nodes that are affected by the move.
		* $diff_up and $diff_down are the values to substract or add to each node's left_id
		* and right_id in order to move them up or down.
		* $move_up_left and $move_up_right define the scope of the nodes that are moving
		* up. Other nodes in the scope of ($left_id, $right_id) are considered to move down.
		*/
		if ($action == 'move_up')
		{
			$left_id = (int) $target['left_id'];
			$right_id = (int) $page_row['right_id'];

			$diff_up = (int) ($page_row['left_id'] - $target['left_id']);
			$diff_down = (int) ($page_row['right_id'] + 1 - $page_row['left_id']);

			$move_up_left = (int) $page_row['left_id'];
			$move_up_right = (int) $page_row['right_id'];
		}
		else
		{
			$left_id = (int) $page_row['left_id'];
			$right_id = (int) $target['right_id'];

			$diff_up = (int) ($page_row['right_id'] + 1 - $page_row['left_id']);
			$diff_down = (int) ($target['right_id'] - $page_row['right_id']);

			$move_up_left = (int) ($page_row['right_id'] + 1);
			$move_up_right = (int) $target['right_id'];
		}

		$sql = '
			UPDATE
				' . PAGES_TABLE . '
			SET
				left_id = left_id + CASE
					WHEN left_id BETWEEN ' . $move_up_left . ' AND ' . $move_up_right . '
					THEN -' . $diff_up . '
					ELSE ' . $diff_down . '
				END,
				right_id = right_id + CASE
					WHEN right_id BETWEEN ' . $move_up_left . ' AND ' . $move_up_right . '
					THEN -' . $diff_up . '
					ELSE ' . $diff_down . '
				END
			WHERE
				site_id = ' . $this->db->check_value($this->data['site_id']) . '
			AND
				left_id BETWEEN ' . $left_id . ' AND ' . $right_id . '
			AND
				right_id BETWEEN ' . $left_id . ' AND ' . $right_id;
		$this->db->query($sql);
		$this->remove_cache_file();

		return $target['page_name'];
	}

	protected function remove_cache_file()
	{
		$sql = '
			SELECT
				*
			FROM
				' . MENUS_TABLE . '
			WHERE
				menu_active = 1';
		$this->db->query($sql);
		
		while ($row = $this->db->fetchrow())
		{
			$this->cache->delete("menu_{$row['menu_id']}_{$this->request->language}");
		}
		
		$this->db->freeresult();

		$this->cache->delete("handlers_{$this->request->language}");
		$this->cache->delete("menu_{$this->request->language}");
	}
	
	/**
	* Обновление/создание страницы
	*
	* @param bool $run_inline Если true, то возвращать ошибки, а не останавливать работу
	*/
	function update_page_data(&$page_data, $run_inline = false)
	{
		if (!isset($page_data['page_id']))
		{
			/* Если page_id не указан, то создаем новую страницу */
			if ($page_data['parent_id'])
			{
				$sql = '
					SELECT
						left_id,
						right_id
					FROM
						' . PAGES_TABLE . '
					WHERE
						site_id = ' . $this->db->check_value($page_data['site_id']) . '
					AND
						page_id = ' . (int) $page_data['parent_id'];
				$this->db->query($sql);
				$row = $this->db->fetchrow();
				$this->db->freeresult();

				if (!$row)
				{
					if ($run_inline)
					{
						return 'PARENT_NO_EXIST';
					}

					trigger_error('PARENT_NOT_EXIST', E_USER_WARNING);
				}

				// Workaround
				$row['left_id'] = (int) $row['left_id'];
				$row['right_id'] = (int) $row['right_id'];

				$sql = '
					UPDATE
						' . PAGES_TABLE . '
					SET
						left_id = left_id + 2,
						right_id = right_id + 2
					WHERE
						site_id = ' . $this->db->check_value($page_data['site_id']) . '
					AND
						left_id > ' . $row['right_id'];
				$this->db->query($sql);

				$sql = '
					UPDATE
						' . PAGES_TABLE . '
					SET
						right_id = right_id + 2
					WHERE
						site_id = ' . $this->db->check_value($page_data['site_id']) . '
					AND
						' . $row['left_id'] . ' BETWEEN left_id AND right_id';
				$this->db->query($sql);

				$page_data['left_id'] = (int) $row['right_id'];
				$page_data['right_id'] = (int) $row['right_id'] + 1;
			}
			else
			{
				$sql = '
					SELECT
						MAX(right_id) AS right_id
					FROM
						' . PAGES_TABLE . '
					WHERE
						site_id = ' . $this->db->check_value($page_data['site_id']);
				$this->db->query($sql);
				$row = $this->db->fetchrow();
				$this->db->freeresult();

				$page_data['left_id'] = (int) $row['right_id'] + 1;
				$page_data['right_id'] = (int) $row['right_id'] + 2;
			}

			$sql = 'INSERT INTO ' . PAGES_TABLE . ' ' . $this->db->build_array('INSERT', $page_data);
			$this->db->query($sql);

			$page_data['page_id'] = $this->db->insert_id();
		}
		else
		{
			$row = $this->get_page_row($page_data['page_id']);

			if ($page_data['is_dir'] && !$row['is_dir'])
			{
				/* Нельзя сделать папку страницей */
				$branch = $this->get_page_branch($page_data['page_id'], 'children', 'descending', false);

				if (sizeof($branch))
				{
					return ['NO_DIR_TO_PAGE'];
				}
			}

			if ($row['parent_id'] != $page_data['parent_id'])
			{
				$this->move_page($page_data['page_id'], $page_data['parent_id']);
			}

			$update_ary = $page_data;
			unset($update_ary['page_id']);

			$sql = '
				UPDATE
					' . PAGES_TABLE . '
				SET
					' . $this->db->build_array('UPDATE', $update_ary) . '
				WHERE
					site_id = ' . $this->db->check_value($page_data['site_id']) . '
				AND
					page_id = ' . (int) $page_data['page_id'];
			$this->db->query($sql);
		}

		return [];
	}
}
