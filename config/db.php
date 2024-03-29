<?php namespace fw\config;

/**
* Настройки сайта, хранящиеся в БД
*/
class db extends config
{
	protected $language;
	protected $site_id;
	protected $site_vars;

	protected $cache;
	protected $db;
	
	protected $defaults = [
		'allow_smilies'              => 1,
		'autologin.allow'            => 1,
		'autologin.time'             => 30,
		'confirm.enable'             => 1,
		'confirm.expire'             => 300,
		'confirm.max_chars'          => 6,
		'confirm.min_chars'          => 4,
		'cookie.domain'              => '',
		'cookie.path'                => '/',
		'cookie.prefix'              => 'the_',
		'cookie.secure'              => 0,
		'cron.rss_timeout'           => 5,
		'css.version'                => 1,
		'dateformat'                 => '|D, j F Y|, H:i',
		'email.noreply'              => 'noreply@example.com',
		'email.support'              => 'support@example.com',
		'form.cancel_class'          => 'btn',
		'form.submit_class'          => 'btn btn-large',
		'form.token_lifetime'        => 7200,
		'form.url.default_protocol'  => 'http://',
		'form.url.max_chars'         => 200,
		'form.url.min_chars'         => 5,
		'forwarded_for_check'        => 0,
		'ip_login_limit_max'         => 3,
		'ip_login_limit_time'        => 900,
		'js.version'                 => 1,
		'oauth.facebook.app_id'      => '',
		'oauth.facebook.app_secret'  => '',
		'oauth.github.app_id'        => '',
		'oauth.github.app_secret'    => '',
		'oauth.google.app_id'        => '',
		'oauth.google.app_secret'    => '',
		'oauth.instagram.app_id'     => '',
		'oauth.instagram.app_secret' => '',
		'oauth.twitter.app_id'       => '',
		'oauth.twitter.app_secret'   => '',
		'oauth.vk.app_id'            => '',
		'oauth.vk.app_secret'        => '',
		'oauth.yandex.app_id'        => '',
		'oauth.yandex.app_secret'    => '',
		'session.length'             => 1440,
		'site.root_path'             => '/',
		'site.tz'                    => 4,
		'sitename'                   => 'Название сайта',
		'smtp.host'                  => '',
		'smtp.pass'                  => '',
		'smtp.port'                  => '',
		'smtp.user'                  => '',
	];
	
	function __construct($cache, $db, $site_info)
	{
		$this->cache = $cache;
		$this->db    = $db;
		
		$this->language = $site_info['language'];
		$this->site_id  = $site_info['id'];
		
		parent::__construct(array_merge($this->load_config(0), $this->load_config($this->site_id)));
		
		$this->defaults['cookie.domain'] = ".{$site_info['domain']}";
		$this->setup_defaults();
	}
	
	/**
	* Удаление настройки
	*/
	public function delete($key, $site_id = false)
	{
		$site_id = false !== $site_id ? intval($site_id) : $this->site_id;
		
		$sql = 'DELETE FROM site_config WHERE config_name = ? AND site_id = ?';
		$this->db->query($sql, [$key, $site_id]);
		
		if ($site_id === $this->site_id) {
			/* Настройки текущего сайта */
			unset($this->config[$key]);
			$this->cache->delete("config_{$this->language}");
		} elseif ($site_id === 0) {
			/* Настройки движка */
			if (!isset($this->site_vars[$key])) {
				unset($this->config[$key]);
			}
			
			$this->cache->delete_shared('config');
		} elseif ($site_id > 0 && $site_id !== $this->site_id) {
			/* Настройки другого сайта */
			$site_info = $this->cache->get_site_info_by_id($site_id);
			
			$this->cache->_delete(sprintf('%s_config_%s', $site_info['domain'], $site_info['language']));
		}
	}
	
	/**
	* Увеличение значения настройки (счетчика)
	*/
	public function increment($key, $increment = 1, $site_id = false)
	{
		$site_id = false !== $site_id ? intval($site_id) : $this->site_id;
		
		if ($site_id !== 0 && $site_id !== $this->site_id) {
			trigger_error('Метод increment можно вызывать только для текущего сайта и движка');
		}
		
		if ($site_id === $this->site_id && !isset($this->site_vars[$key])) {
			/* Настройка текущего сайта */
			$this->set($key, 0);
		} elseif ($site_id === 0 && !isset($this->config[$key])) {
			/* Настройка движка */
			$this->set($key, 0, 0);
		}
		
		$sql = 'UPDATE site_config SET config_value = config_value + :increment WHERE config_name = ? AND site_id = ?';
		$this->db->query($sql, [$key, $site_id, ':increment' => (int) $increment]);
		
		if ($site_id > 0) {
			/* Настройки сайта */
			$this->config[$key] += $increment;
			$this->cache->delete("config_{$this->language}");
		} elseif ($site_id === 0) {
			/* Настройки движка */
			if (!isset($this->site_vars[$key])) {
				/**
				* Текущее значение обновляется только если
				* сайт не переопределил настройку
				*/
				$this->config[$key] += $increment;
			}
			
			$this->cache->delete_shared('config');
		}
	}

	/**
	* Установка нового значения настройки
	*/
	public function set($key, $value, $site_id = false)
	{
		$this->set_atomic($key, false, $value, $site_id);
	}
	
	/**
	* Установка нового значения только если предыдущее совпадает или вовсе отсутствует
	*/
	public function set_atomic($key, $old_value, $new_value, $site_id = false)
	{
		$site_id = false !== $site_id ? intval($site_id) : $this->site_id;
		
		$sql = '
			UPDATE
				site_config
			SET
				config_value = ' . $this->db->check_value($new_value) . '
			WHERE
				config_name = ' . $this->db->check_value($key) . '
			AND
				site_id = ' . $this->db->check_value($site_id);
		
		if (false !== $old_value) {
			$sql .= ' AND config_value = ' . $this->db->check_value($old_value);
		}
		
		$this->db->query($sql);
		
		if (!$this->db->affected_rows()) {
			if (($site_id === $this->site_id && isset($this->site_vars[$key])) ||
				($site_id === 0 && isset($this->config[$key]))
			) {
				return false;
			}
		}
		
		if (($site_id === $this->site_id && !isset($this->site_vars[$key])) ||
			($site_id === 0 && !isset($this->config[$key]) && !isset($this->site_vars[$key])) ||
			($site_id > 0 && $site_id !== $this->site_id)
		) {
			$insert = $site_id > 0 && $site_id !== $this->site_id ? 'INSERT IGNORE' : 'INSERT';
			
			$sql = $insert . ' INTO site_config ' . $this->db->build_array('INSERT', [
				'config_name'  => $key,
				'config_value' => $new_value,
				'site_id'      => $site_id
			]);
			$this->db->query($sql);
		}
		
		if ($site_id === $this->site_id) {
			/* Настройки текущего сайта */
			$this->config[$key] = $new_value;
			$this->site_vars[$key] = true;
			$this->cache->delete("config_{$this->language}");
		} elseif ($site_id === 0) {
			/* Настройки движка */
			if (!isset($this->site_vars[$key])) {
				/**
				* Текущее значение обновляется только если
				* сайт не переопределил настройку
				*/
				$this->config[$key] = $new_value;
			}
			
			$this->cache->delete_shared('config');
		} elseif ($site_id > 0 && $site_id !== $this->site_id) {
			/* Настройки другого сайта */
			$site_info = $this->cache->get_site_info_by_id($site_id);
			
			$this->cache->_delete(sprintf('%s_config_%s', $site_info['domain'], $site_info['language']));
		}
		
		return true;
	}
	
	/**
	* Загрузка настроек сайта из БД
	*/
	protected function load_config($site_id)
	{
		$cache_entry = 0 === $site_id ? 'config' : "config_{$this->language}";
		
		if ((0 === $site_id && false === $config = $this->cache->get_shared($cache_entry)) ||
			(0 !== $site_id && false === $config = $this->cache->get($cache_entry))
		) {
			$sql = 'SELECT config_name, config_value FROM site_config WHERE site_id = ?';
			$this->db->query($sql, [$site_id]);
			$config = [];

			while ($row = $this->db->fetchrow()) {
				$config[$row['config_name']] = $row['config_value'];
			}

			$this->db->freeresult();
			
			if (0 === $site_id) {
				$this->cache->set_shared($cache_entry, $config);
			} else {
				$this->cache->set($cache_entry, $config);
			}
		}
		
		if ($site_id) {
			foreach ($config as $key => $value) {
				$this->site_vars[$key] = true;
			}
		}
		
		return $config;
	}
	
	protected function setup_defaults()
	{
		foreach ($this->defaults as $key => $value) {
			if (!isset($this->config[$key])) {
				$this->set($key, $value, 0);
			}
		}
	}
}
