<?php
/**
* @package fw
* @copyright (c) 2012
*/

namespace fw\core;

/**
* Комментарии
*/
class comments
{
	public $minor_id;
	public $page_id;
	
	protected $auth;
	protected $cache;
	protected $config;
	protected $db;
	protected $request;
	protected $template;
	protected $user;
	
	function __construct($page_id, $minor_id)
	{
		global $auth, $cache, $config, $db, $request, $template, $user;
		
		$this->minor_id = (int) $minor_id;
		$this->page_id  = (int) $page_id;
		
		$this->auth     =& $auth;
		$this->cache    =& $cache;
		$this->config   =& $config;
		$this->db       =& $db;
		$this->request  =& $request;
		$this->template =& $template;
		$this->user     =& $user;
	}
	
	/**
	* Добавление комментария
	*/
	public function add()
	{
		$post_text = $this->request->variable('post_text', '');
		
		if (!$post_text)
		{
			trigger_error('EMPTY_MESSAGE');
		}
		
		$this->db->transaction('begin');
		
		$sql = 'INSERT INTO ' . COMMENTS_TABLE . ' ' .
			$this->db->build_array('INSERT', [
				'page_id'   => $this->page_id,
				'user_id'   => $this->user['user_id'],
				'minor_id'  => $this->minor_id,
				'comm_time' => $this->user->ctime,
				'comm_text' => prepare_text_for_db($post_text)
			]);
		$this->db->query($sql);
		
		$comment_id = $this->db->insert_id();
		
		$sql = '
			UPDATE
				' . USERS_TABLE . '
			SET
				user_posts = user_posts + 1,
				user_money = user_money + 1
			WHERE
				user_id = ' . $this->db->check_value($this->user['user_id']);
		$this->db->query($sql);
		
		$this->db->transaction('commit');
		
		return true;
	}
	
	/**
	* Удаление комментария
	*/
	public function delete()
	{
		$comment_id = $this->request->variable('comment_id', 0);
		
		$sql = '
			SELECT
				user_id
			FROM
				' . COMMENTS_TABLE . '
			WHERE
				comment_id = ' . $comment_id;
		$this->db->query($sql);
		$row = $this->db->fetchrow($result);
		$this->db->freeresult($result);
		
		if (!$row)
		{
			trigger_error('DATA_NOT_FOUND');
		}
		
		$this->db->transaction('begin');
		
		$sql = '
			DELETE
			FROM
				' . COMMENTS_TABLE . '
			WHERE
				comment_id = ' . $comment_id;
		$this->db->query($sql);
		
		$sql = '
			UPDATE
				' . USERS_TABLE . '
			SET
				user_posts = user_posts - 1,
				user_money = user_money - 2
			WHERE
				user_id = ' . $row['user_id'];
		$this->db->query($sql);
		$this->db->transaction('commit');
	}
	
	/**
	* Вывод комментариев
	*/
	public function obtain($on_page, $offset = 0, $order = 'DESC')
	{
		$ranks = $this->cache->obtain_ranks();
		
		$sql = '
			SELECT
				c.comm_id,
				c.user_id,
				c.comm_time,
				c.comm_text,
				u.username,
				u.user_url,
				u.user_session_time,
				u.user_posts,
				u.user_rank,
				u.user_colour
			FROM
				' . COMMENTS_TABLE . ' c
			LEFT JOIN
				' . USERS_TABLE . ' u ON (u.user_id = c.user_id)
			WHERE
				c.page_id = ' . $this->page_id . '
			AND
				c.minor_id = ' . $this->minor_id . '
			ORDER BY
				c.comm_time ' . $order;
		$this->db->query_limit($sql, $on_page, $offset);
		
		while ($row = $this->db->fetchrow())
		{
			$this->template->append('comments', [
				'ID'         => $row['comm_id'],
				'ONLINE'     => ($this->user->ctime - $row['user_session_time']) < $this->config['load_online_time'],
				'POSTS'      => $row['user_posts'],
				'RANK_IMG'   => $ranks[$row['user_rank']]['rank_image'],
				'RANK_TITLE' => $ranks[$row['user_rank']]['rank_title'],
				'TEXT'       => prepare_text_for_print(nl2br($row['comm_text'])),
				'TIME'       => $this->user->create_date($row['comm_time']),
				'USERNAME'   => profile_link('full', $row['username'], $row['user_colour'], $row['user_url'], $row['user_id']),
				'USER_ID'    => $row['user_id'])
			]);
		}
		
		$this->db->freeresult();
	}
	
	/**
	* Быстрое редактирование
	*/
	public function quickedit()
	{
		/**
		* Определяем необходимые переменные
		*/
		$button     = getvar('button', '');
		$comment_id = getvar('comment_id', 0);
		$post_text  = getvar('post_text', '');

		if ($button == 'cancel')
		{
			/**
			* Отмена правки - возвращаем текст коммента
			*/
			$sql = '
				SELECT
					*
				FROM
					' . COMMENTS_TABLE . '
				WHERE
					comm_id = ' . $db->check_value($comment_id);
			$result = $db->query($sql);
			$row = $db->fetchrow($result);
			$db->freeresult($result);

			$template->vars([
				'EDIT_FORM' => false,
				'SEND_TEXT' => true,
				'TEXT'      => prepare_text_for_print(nl2br($row['comm_text']))
			]);

			$template->go('comments_quickedit.html');
			exit;
		}
		elseif ($button == 'submit')
		{
			/**
			* Сохраняем изменённый текст
			*/
			$sql = '
				UPDATE
					' . COMMENTS_TABLE . '
				SET
					comm_text = ' . $db->check_value(prepare_text_for_db($post_text)) . '
				WHERE
					comm_id = ' . $db->check_value($comment_id);
			$db->query($sql);

			$sql = '
				UPDATE
					' . USERS_TABLE . '
				SET
					user_money = (user_money + ' . $db->check_value(strlen($post_text) / 200) . ')
				WHERE
					user_id =' . $db->check_value($user['user_id']);
			$db->query($sql);

			$template->vars([
				'EDIT_FORM' => false,
				'SEND_TEXT' => true,
				'TEXT'      => prepare_text_for_print(nl2br(prepare_text_for_db($post_text)))
			]);

			$template->go('comments_quickedit.html');
			exit;
		}
		elseif ($comment_id)
		{
			/**
			* Текст для правки
			*/
			$sql = '
				SELECT
					*
				FROM
					' . COMMENTS_TABLE . '
				WHERE
					comm_id = ' . $db->check_value($comment_id);
			$result = $db->query($sql);
			$row = $db->fetchrow($result);
			$db->freeresult($result);

			$template->vars([
				'COMMENT_ID'   => $row['comm_id'],
				'COMMENT_TEXT' => prepare_text_for_edit($row['comm_text']),
				'EDIT_FORM'    => true,
				'SEND_TEXT'    => false,
			]);

			$template->go('comments_quickedit.html');
			exit;
		}
	}
}
