<?php namespace fw\Api;

class News extends AbstractApi
{
	protected $site_id;
	
	function __construct($site_id)
	{
		$this->site_id = $site_id;
	}
	
	public function add(array $row)
	{
		$row = $this->processInput($row);
		
		$sql = 'INSERT INTO site_news ' . $this->db->build_array('INSERT', $row);
		$this->db->query($sql);
		
		return $this->db->insert_id();
	}
	
	public function delete($id)
	{
		$sql = 'DELETE FROM site_news WHERE news_id = ? AND site_id = ?';
		$this->db->query($sql, [$id, $this->site_id]);
		
		return (bool) $this->db->affected_rows();
	}
	
	/**
	* Список новостей
	*
	* Поддерживаемые параметры и одноименные переменные:
	*   site_id = 0
	*   user_id = 5
	*   year = 2014
	*   month = 5
	*   day = 14
	*   on_page = 10
	*   order_by = n.news_views DESC
	*/
	public function get(array $filter = [])
	{
		extract($this->processFilterParams($filter));
		
		$pagination = pagination($on_page, $this->getCount(compact('year', 'month', 'day', 'site_id', 'on_page')), $this->router->handler->full_url);
		
		$sql = [
			'SELECT'    => 'n.*, u.username',
			'FROM'      => 'site_news n',
			'LEFT_JOIN' => 'site_users u ON (u.user_id = n.user_id)',
			'WHERE'     => ["n.site_id = {$site_id}"],
			'ORDER_BY'  => $order_by,
		];
		
		/* Новости за определенный интервал времени */
		if (false !== $interval = $this->calculateInterval($year, $month, $day)) {
			$sql['WHERE'][] = "n.news_time BETWEEN {$interval['start']} AND {$interval['end']}";
		}
		
		if (isset($user_id)) {
			$sql['WHERE'][] = $this->db->placehold('u.user_id = ?', [$user_id]);
		}
		
		$this->db->query_limit(['SELECT', $sql], [], $pagination['on_page'], $pagination['offset']);
		$rows = $this->db->fetchall();
		$this->db->freeresult();
		
		return $rows;
	}
	
	public function getById($id)
	{
		$sql = [
			'SELECT'    => 'n.*, u.username',
			'FROM'      => 'site_news n',
			'LEFT_JOIN' => 'site_users u ON (u.user_id = n.user_id)',
			'WHERE'     => [
				'n.news_id = ' . $this->db->check_value($id),
				'n.site_id = ' . $this->db->check_value($this->site_id),
			],
		];
		
		$this->db->query(['SELECT', $sql]);
		$row = $this->db->fetchrow();
		$this->db->freeresult();
		
		if (!$row) {
			throw new \Exception('NEWS_NOT_FOUND');
		}
		
		return $row;
	}
	
	public function getByUrl($url, $year, $month, $day)
	{
		/* Границы дня, в который была опубликована новости */
		if (false === checkdate($month, $day, $year)) {
			throw new \Exception('INCORRECT_DATE');
		}
		
		$day_start = mktime(0, 0, 0, $month, $day, $year);
		$day_end   = mktime(0, 0, 0, $month, $day + 1, $year) - 1;

		$sql = [
			'SELECT'    => 'n.*, u.username',
			'FROM'      => 'site_news n',
			'LEFT_JOIN' => 'site_users u ON (u.user_id = n.user_id)',
			'WHERE'     => [
				"n.news_time BETWEEN {$day_start} AND {$day_end}",
				'n.news_url = ' . $this->db->check_value($url),
				'n.site_id = ' . $this->db->check_value($this->site_id),
			],
		];
		
		$this->db->query(['SELECT', $sql]);
		$row = $this->db->fetchrow();
		$this->db->freeresult();
		
		if (!$row) {
			throw new \Exception('NEWS_NOT_FOUND');
		}
		
		return $row;
	}
	
	public function getCount(array $filter = [])
	{
		extract($this->processFilterParams($filter));
		
		$sql = [
			'SELECT' => 'COUNT(*) AS total',
			'FROM'   => 'site_news',
			'WHERE'  => ['site_id = ' . $this->db->check_value($site_id)],
		];
		
		if (false !== $interval = $this->calculateInterval($year, $month, $day)) {
			$sql['WHERE'][] = "news_time BETWEEN {$interval['start']} AND {$interval['end']}";
		}
		
		$this->db->query(['SELECT', $sql]);
		$total = $this->db->fetchfield('total');
		$this->db->freeresult();
		
		return $total;
	}
	
	public function getMostDiscussed()
	{
		return $this->get(['order_by' => 'n.news_comments DESC, n.news_time DESC']);
	}
	
	public function getMostViewed()
	{
		return $this->get(['order_by' => 'n.news_views DESC, n.news_time DESC']);
	}

	public function update($id, array $row)
	{
		$row = $this->db->build_array('UPDATE', $this->processInput($row));
		
		$sql = 'UPDATE site_news SET :row WHERE news_id = ? AND site_id = ?';
		$this->db->query($sql, [$id, $this->site_id, ':row' => $row]);
	
		return true;
	}
	
	/**
	* Интервал времени для SQL-запроса
	*/
	protected function calculateInterval($year, $month, $day)
	{
		if (!$year && !$month && !$day) {
			return false;
		}
		
		/* Новости за день */
		if ($year && $month && $day) {
			return [
				'start' => mktime(0, 0, 0, $month, $day, $year),
				'end'   => mktime(0, 0, 0, $month, $day + 1, $year) - 1,
			];
		}
		
		/* Новости за месяц */
		if ($year && $month) {
			return [
				'start' => mktime(0, 0, 0, $month, 1, $year),
				'end'   => mktime(0, 0, 0, $month + 1, 1, $year) - 1,
			];
		}
		
		/* Новости за год */
		if ($year) {
			return [
				'start' => mktime(0, 0, 0, 1, 1, $year),
				'end'   => mktime(0, 0, 0, 1, 1, $year + 1) - 1,
			];
		}
	}

	/**
	* Проверка даты на корректность
	*/
	protected function checkInputDate($year, $month, $day)
	{
		if (($day && !$this->isNumber($day)) ||
			($month && !$this->isNumber($month)) ||
			($year && !$this->isNumber($year))
		) {
			throw new \Exception('INCORRECT_DATE');
		}
		
		if ($year && $month && $day) {
			/* Новости за день */
			if (false === checkdate((int) $month, (int) $day, (int) $year)) {
				throw new \Exception('INCORRECT_DAY');
			}
		} elseif ($year && $month) {
			/* Новости за месяц */
			if (false === checkdate((int) $month, 1, (int) $year)) {
				throw new \Exception('INCORRECT_MONTH');
			}
		} elseif ($year) {
			/* Новости за год */
			if (false === checkdate(1, 1, (int) $year)) {
				throw new \Exception('INCORRECT_YEAR');
			}
		}
	}
	
	protected function isNumber($n)
	{
		return is_int($n) || ctype_digit(strval($n));
	}

	/**
	* Обработка значений фильтра для методов get и getCount
	*/
	protected function processFilterParams(array $f)
	{
		$f['year']  = @$f['year'] ?: false;
		$f['month'] = @$f['month'] ?: false;
		$f['day']   = @$f['day'] ?: false;
		
		$this->checkInputDate($f['year'], $f['month'], $f['day']);
		
		$f['site_id']  = @$f['site_id'] ?: $this->site_id;
		$f['on_page']  = @$f['on_page'] ?: 10;
		$f['on_page']  = min($f['on_page'], 50);
		$f['order_by'] = @$f['order_by'] ?: 'n.news_time DESC';
		
		return $f;
	}

	protected function processInput(array $row)
	{
		if (empty($row['news_subject'])) {
			throw new \Exception('TITLE_IS_EMPTY');
		}
		
		if (empty($row['site_id'])) {
			$row['site_id'] = $this->site_id;
		}
		
		$language = $this->cache->get_site_info_by_id($row['site_id'])['language'];
		
		$row['news_time'] = empty($row['news_time']) ? time() : $row['news_time'];
		$row['news_url'] = empty($row['news_url']) ? $row['news_subject'] : $row['news_url'];
		$row['news_url'] = seo_url($row['news_url'], $language);
		
		if (empty($row['news_url'])) {
			throw new \Exception('URL_GENERATION_FAILED');
		}
		
		return $row;
	}
}
