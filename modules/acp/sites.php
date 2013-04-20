<?php
/**
* @package fw
* @copyright (c) 2013
*/

namespace fw\modules\acp;

use app\models\page;

class sites extends page
{
	public function index()
	{
		$sql = 'SELECT * FROM site_sites';
		$this->db->query($sql);
		
		while ($row = $this->db->fetchrow())
		{
			$this->template->append('sites', $row);
		}
		
		$this->db->freeresult();
	}
}
