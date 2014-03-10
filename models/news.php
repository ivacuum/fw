<?php namespace fw\models;

class news extends base
{
	protected $site_id;
	
	function __construct($site_id)
	{
		$this->site_id = $site_id;
	}
	
	public function add($ary)
	{
		$sql = 'INSERT INTO site_news ' . $this->db->build_array('INSERT', $this->process_input_array($ary));
		$this->db->query($sql);
		
		return $this->db->insert_id();
	}
	
	public function delete($id)
	{
		$sql = 'DELETE FROM site_news WHERE news_id = ? AND site_id = ?';
		$this->db->query($sql, [$id, $this->site_id]);
		
		return (bool) $this->db->affected_rows();
	}
	
	public function get_by_id($id)
	{
		$sql = 'SELECT * FROM site_news WHERE news_id = ? AND site_id = ?';
		$this->db->query($sql, [$id, $this->site_id]);
		$row = $this->db->fetchrow();
		$this->db->freeresult();
		
		if (!$row) {
			throw new \Exception('NEWS_NOT_FOUND');
		}
		
		return $row;
	}
	
	public function get_count()
	{
		$sql = 'SELECT COUNT(*) AS total FROM site_news WHERE site_id = ?';
		$this->db->query($sql, [$this->site_id]);
		$total = (int) $this->db->fetchfield('total');
		$this->db->freeresult();
		
		return $total;
	}
	
	public function get_last($count = 10)
	{
		$sql = 'SELECT * FROM site_news WHERE site_id = ? ORDER BY news_time DESC';
		$this->db->query_limit($sql, [$this->site_id], $count);
		$rows = $this->db->fetchall();
		$this->db->freeresult();
		
		return $rows;
	}
	
	public function get_page($count = 10, $pagination_url = false)
	{
		$pagination = ['offset' => 0, 'on_page' => $count];
		
		if (false !== $pagination_url) {
			$pagination = pagination($count, $this->get_count(), $pagination_url);
		}
		
		$sql = 'SELECT * FROM site_news WHERE site_id = ? ORDER BY news_time DESC';
		$this->db->query_limit($sql, [$this->site_id], $pagination['on_page'], $pagination['offset']);
		$rows = $this->db->fetchall();
		$this->db->freeresult();
		
		return $rows;
	}
	
	protected function update($id, $ary)
	{
		$sql = 'UPDATE site_news SET :sql_ary WHERE news_id = ? AND site_id = ?';
		$this->db->query($sql, [$id, $this->site_id, ':sql_ary' => $this->db->build_array('UPDATE', $this->process_input_array($ary))]);
	
		return true;
	}
	
	protected function process_input_array($ary)
	{
		if (empty($ary['news_subject'])) {
			throw new \Exception('TITLE_IS_EMPTY');
		}
		
		if (empty($ary['site_id'])) {
			$ary['site_id'] = $this->site_id;
		}
		
		$language = $this->cache->get_site_info_by_id($ary['site_id'])['language'];
		
		$ary['news_time'] = empty($ary['news_time']) ? time() : $ary['news_time'];
		$ary['news_url'] = empty($ary['news_url']) ? $ary['news_subject'] : $ary['news_url'];
		$ary['news_url'] = seo_url($ary['news_url'], $language);
		
		if (empty($ary['news_url'])) {
			throw new \Exception('URL_GENERATION_FAILED');
		}
		
		return $ary;
	}
}
