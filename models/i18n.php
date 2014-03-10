<?php
/**
* @package fw
* @copyright (c) 2014
*/

namespace fw\models;

class i18n extends base
{
	protected $aliases = [
		'site_id'       => '',
		'i18n_lang'     => 'lang',
		'i18n_subindex' => 'subindex',
		'i18n_index'    => 'index',
		'i18n_file'     => 'file',
	];
	
	protected $site_id;

	function __construct($site_id)
	{
		$this->site_id = $site_id;
	}

	public function add($ary)
	{
		$sql = 'INSERT INTO site_i18n ' . $this->db->build_array('INSERT', $this->process_input_array($ary));
		$this->db->query($sql);
		
		return $this->db->insert_id();
	}
	
	public function delete($id)
	{
		$sql = 'DELETE FROM site_i18n WHERE i18n_id = ? AND site_id = ?';
		$this->db->query($sql, [$id, $this->site_id]);
		
		return (bool) $this->db->affected_rows();
	}
	
	public function get_by_id($id)
	{
		$sql = 'SELECT * FROM site_i18n WHERE i18n_id = ? AND site_id = ?';
		$this->db->query($sql, [$id, $this->site_id]);
		$row = $this->db->fetchrow();
		$this->db->freeresult();
		
		if (!$row) {
			throw new \Exception('Translation entry is not found.');
		}
		
		return $row;
	}
	
	public function get_count()
	{
		$sql = 'SELECT COUNT(*) AS total FROM site_i18n WHERE site_id = ?';
		$this->db->query($sql, [$this->site_id]);
		$total = (int) $this->db->fetchfield('total');
		$this->db->freeresult();
		
		return $total;
	}
	
	public function get_entries(array $filter = [])
	{
		$sql = [
			'SELECT' => '*',
			'FROM'   => 'site_i18n',
			'WHERE'  => [],
		];
		
		foreach ($this->aliases as $key => $alias) {
			if (isset($filter[$key])) {
				$sql['WHERE'][] = $this->db->placehold("{$key} = ?", [$filter[$key]]);
				continue;
			}
			
			if ($alias && isset($filter[$alias])) {
				$sql['WHERE'][] = $this->db->placehold("{$key} = ?", [$filter[$alias]]);
			}
		}
		
		$this->db->query($this->db->build_query('SELECT', $sql));
		$rows = $this->db->fetchall();
		$this->db->freeresult();
		
		return $rows;
	}
	
	protected function process_input_array($ary)
	{
		if (empty($ary['i18n_lang'])) {
			throw new \Exception('Language is not specified.');
		} elseif (empty($ary['i18n_index'])) {
			throw new \Exception('Index is specified.');
		} elseif (empty($ary['i18n_file'])) {
			throw new \Exception('File is not specified.');
		}
		
		if (empty($ary['site_id'])) {
			$ary['site_id'] = $this->site_id;
		}
		
		return $ary;
	}
}
