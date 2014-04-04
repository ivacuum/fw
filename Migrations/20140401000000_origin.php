<?php

use Phinx\Migration\AbstractMigration;

class Origin extends AbstractMigration
{
	public function up()
	{
		$this->import_sql_dump(FW_DIR . 'Migrations/schema.sql');
		$this->import_sql_dump(FW_DIR . 'Migrations/schema_data.sql');
	}
	
	public function down()
	{
		$this->dropTable('site_auth_groups');
		$this->dropTable('site_auth_options');
		$this->dropTable('site_auth_roles');
		$this->dropTable('site_auth_roles_data');
		$this->dropTable('site_auth_users');
		$this->dropTable('site_banlist');
		$this->dropTable('site_bots');
		$this->dropTable('site_config');
		$this->dropTable('site_confirm');
		$this->dropTable('site_cron');
		$this->dropTable('site_groups');
		$this->dropTable('site_i18n');
		$this->dropTable('site_languages');
		$this->dropTable('site_login_attempts');
		$this->dropTable('site_logs');
		$this->dropTable('site_menus');
		$this->dropTable('site_news');
		$this->dropTable('site_openid_identities');
		$this->dropTable('site_pages');
		$this->dropTable('site_sessions');
		$this->dropTable('site_sessions_keys');
		$this->dropTable('site_sites');
		$this->dropTable('site_users');
		$this->dropTable('site_user_groups');
	}
	
	protected function import_sql_dump($file)
	{
		$sql_ary = file_get_contents($file);
		$sql_ary = $this->split_sql_file($sql_ary, ';');
	
		foreach ($sql_ary as $sql) {
			$this->execute($sql);
		}
	}
	
	protected function split_sql_file($sql, $delimiter)
	{
		$sql = str_replace("\r" , '', $sql);
		$data = preg_split('/' . preg_quote($delimiter, '/') . '$/m', $sql);
		$data = array_map('trim', $data);

		$end_data = end($data);

		if (empty($end_data)) {
			unset($data[key($data)]);
		}

		return $data;
	}
}
