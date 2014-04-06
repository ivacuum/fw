<?php namespace fw\Api;

class Sites extends AbstractApi
{
	protected $delete_tables = ['site_config', 'site_cron', 'site_i18n', 'site_news', 'site_pages'];

	public function add(array $row)
	{
		$row = $this->processInput($row);
		
		$sql = 'INSERT INTO site_sites ' . $this->db->build_array('INSERT', $row);
		$this->db->query($sql);
		$this->purgeCache();
		
		return $this->db->insert_id();
	}

	public function delete($id)
	{
		$this->db->transaction('begin');
		$this->delete_tables[] = 'site_sites';
		
		foreach ($this->delete_tables as $table) {
			$sql = 'DELETE FROM :table WHERE site_id = ?';
			$this->db->query($sql, [$id, ':table' => $table]);
		}
		
		$this->db->transaction('commit');
		$this->purgeCache();

		return (bool) $this->db->affected_rows();
	}
	
	public function get(array $filter = [])
	{
		$sql = 'SELECT * FROM site_sites ORDER BY site_url ASC, site_language ASC';
		$this->db->query($sql);
		$rows = $this->db->fetchall();
		$this->db->freeresult();
		
		return $rows;
	}

	public function getById($id)
	{
		$sql = 'SELECT * FROM site_sites WHERE site_id = ?';
		$this->db->query($sql, [$id]);
		$row = $this->db->fetchrow();
		$this->db->freeresult();
		
		if (!$row) {
			throw new \Exception('SITE_NOT_FOUND');
		}
		
		return $row;
	}
	
	public function getCount(array $filter = [])
	{
	}
	
	public function getDataToDelete($id)
	{
		$data_to_delete = [];
		
		foreach ($this->delete_tables as $table) {
			$sql = 'SELECT COUNT(*) AS total FROM :table WHERE site_id = ?';
			$this->db->query($sql, [$id, ':table' => $table]);
			$data_to_delete[$table] = $this->db->fetchfield('total');
			$this->db->freeresult();
		}
		
		$data_to_delete = array_filter($data_to_delete, function ($value) {
			return !empty($value);
		});
		
		return $data_to_delete;
	}
	
	public function update($id, array $row)
	{
		$row = array_merge($this->getById($id), $row);
		$row = $this->db->build_array('UPDATE', $this->processInput($row));
		
		$sql = 'UPDATE site_sites SET :row WHERE site_id = ?';
		$this->db->query($sql, [$id, ':row' => $row]);
		$this->purgeCache();
	
		return true;
	}

	protected function processInput(array $row)
	{
		if (empty($row['site_url'])) {
			throw new \Exception('SITE_URL_IS_EMPTY');
		}
		
		$row['site_language'] = empty($row['site_language']) ? $this->request->language : $row['site_language'];
		
		if (empty($row['site_locale'])) {
			switch ($row['site_language']) {
				case 'en': $row['site_locale'] = 'en_US.UTF-8'; break;
				default:   $row['site_locale'] = 'ru_RU.UTF-8';
			}
		}
		
		if (empty($row['site_title'])) {
			$row['site_title'] = "{$row['site_url']} ({$row['site_language']})";
		}
		
		return $row;
	}
	
	protected function processFilterParams(array $f)
	{
		return $f;
	}
	
	protected function purgeCache()
	{
		$this->cache->delete_shared('hostnames');
		$this->cache->delete_shared('sites');
	}
}
