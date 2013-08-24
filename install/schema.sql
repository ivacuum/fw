SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

CREATE TABLE IF NOT EXISTS `site_auth_groups` (
  `group_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `local_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `auth_option_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `auth_role_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `auth_value` tinyint(1) NOT NULL DEFAULT '0',
  KEY (`group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

CREATE TABLE IF NOT EXISTS `site_auth_options` (
  `auth_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `auth_name` varchar(255) COLLATE utf8_bin NOT NULL,
  `auth_sub` varchar(30) COLLATE utf8_bin NOT NULL,
  `auth_var` varchar(30) COLLATE utf8_bin NOT NULL,
  `auth_global` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `auth_local` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `auth_default` tinyint(1) unsigned NOT NULL,
  PRIMARY KEY (`auth_id`),
  UNIQUE KEY `auth_var` (`auth_var`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=2 ;

CREATE TABLE IF NOT EXISTS `site_auth_roles` (
  `role_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `role_name` varchar(255) COLLATE utf8_bin NOT NULL,
  `role_description` varchar(255) COLLATE utf8_bin NOT NULL,
  `role_type` varchar(10) COLLATE utf8_bin NOT NULL,
  `role_sort` mediumint(5) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`role_id`),
  KEY `role_type` (`role_type`),
  KEY `role_sort` (`role_sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `site_auth_roles_data` (
  `role_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `auth_option_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `auth_value` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`role_id`,`auth_option_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

CREATE TABLE IF NOT EXISTS `site_auth_users` (
  `user_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `local_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `auth_option_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `auth_role_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `auth_value` tinyint(1) NOT NULL DEFAULT '0',
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

CREATE TABLE IF NOT EXISTS `site_banlist` (
  `ban_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `ban_ip` varchar(40) COLLATE utf8_bin NOT NULL DEFAULT '',
  `ban_email` varchar(100) COLLATE utf8_bin NOT NULL DEFAULT '',
  `ban_start` int(11) unsigned NOT NULL DEFAULT '0',
  `ban_end` int(11) unsigned NOT NULL DEFAULT '0',
  `ban_exclude` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `ban_reason` varchar(255) COLLATE utf8_bin NOT NULL DEFAULT '',
  PRIMARY KEY (`ban_id`),
  KEY `ban_end` (`ban_end`),
  KEY `ban_user` (`user_id`,`ban_exclude`),
  KEY `ban_email` (`ban_email`,`ban_exclude`),
  KEY `ban_ip` (`ban_ip`,`ban_exclude`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `site_bots` (
  `bot_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `bot_user_id` mediumint(8) unsigned NOT NULL,
  `bot_enabled` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `bot_name` varchar(255) COLLATE utf8_bin NOT NULL,
  `bot_agent` varchar(255) COLLATE utf8_bin NOT NULL,
  PRIMARY KEY (`bot_id`),
  KEY `bot_user_id` (`bot_user_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `site_config` (
  `config_name` varchar(255) COLLATE utf8_bin NOT NULL,
  `config_value` varchar(255) COLLATE utf8_bin NOT NULL,
  `site_id` smallint(5) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`config_name`,`site_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

CREATE TABLE IF NOT EXISTS `site_confirm` (
  `session_id` char(32) COLLATE utf8_bin NOT NULL,
  `code` varchar(30) COLLATE utf8_bin NOT NULL,
  `expire` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`session_id`),
  KEY `expired` (`expire`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

CREATE TABLE IF NOT EXISTS `site_cron` (
  `cron_id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `site_id` smallint(5) unsigned NOT NULL DEFAULT '0',
  `cron_active` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `cron_title` varchar(255) COLLATE utf8_bin NOT NULL DEFAULT '',
  `cron_script` varchar(255) COLLATE utf8_bin NOT NULL DEFAULT '',
  `cron_schedule` varchar(255) COLLATE utf8_bin NOT NULL DEFAULT '',
  `run_order` smallint(5) unsigned NOT NULL DEFAULT '0',
  `last_run` int(11) NOT NULL DEFAULT '0',
  `next_run` int(11) NOT NULL DEFAULT '0',
  `run_counter` bigint(20) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`cron_id`),
  KEY `site_id_active_next_run` (`site_id`,`cron_active`,`next_run`),
  KEY `site_id_order` (`site_id`,`run_order`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=4 ;

CREATE TABLE IF NOT EXISTS `site_groups` (
  `group_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `group_name` varchar(255) COLLATE utf8_bin NOT NULL,
  `group_description` mediumtext COLLATE utf8_bin NOT NULL,
  `group_colour` varchar(6) COLLATE utf8_bin NOT NULL,
  `group_sort` mediumint(5) unsigned NOT NULL DEFAULT '0',
  `group_display` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `group_legend` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `group_skip_auth` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`group_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=4 ;

CREATE TABLE IF NOT EXISTS `site_i18n` (
  `i18n_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `site_id` smallint(5) unsigned NOT NULL DEFAULT '0',
  `i18n_lang` varchar(30) COLLATE utf8_bin NOT NULL,
  `i18n_subindex` varchar(255) COLLATE utf8_bin NOT NULL,
  `i18n_index` varchar(255) COLLATE utf8_bin NOT NULL,
  `i18n_file` varchar(100) COLLATE utf8_bin NOT NULL,
  `i18n_translation` mediumtext COLLATE utf8_bin NOT NULL,
  PRIMARY KEY (`i18n_id`),
  KEY `lang_file` (`i18n_lang`,`i18n_file`),
  KEY `site_id` (`site_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=757 ;

CREATE TABLE IF NOT EXISTS `site_languages` (
  `language_id` tinyint(2) unsigned NOT NULL AUTO_INCREMENT,
  `language_title` varchar(2) COLLATE utf8_bin NOT NULL,
  `language_full_title` varchar(5) COLLATE utf8_bin NOT NULL,
  `language_direction` varchar(3) COLLATE utf8_bin NOT NULL DEFAULT 'ltr',
  `language_name` varchar(30) COLLATE utf8_bin NOT NULL,
  `language_sort` mediumint(5) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`language_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_bin ROW_FORMAT=COMPACT AUTO_INCREMENT=3 ;

CREATE TABLE IF NOT EXISTS `site_login_attempts` (
  `attempt_ip` varchar(40) COLLATE utf8_bin NOT NULL DEFAULT '',
  `attempt_browser` varchar(150) COLLATE utf8_bin NOT NULL DEFAULT '',
  `attempt_time` int(11) unsigned NOT NULL DEFAULT '0',
  `user_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `credential` varchar(255) COLLATE utf8_bin NOT NULL,
  KEY `att_ip` (`attempt_ip`,`attempt_time`),
  KEY `att_time` (`attempt_time`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

CREATE TABLE IF NOT EXISTS `site_logs` (
  `log_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `site_id` mediumint(5) unsigned NOT NULL DEFAULT '0',
  `user_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `log_date` int(11) unsigned NOT NULL DEFAULT '0',
  `log_action` varchar(255) COLLATE utf8_bin NOT NULL,
  `level` varchar(255) COLLATE utf8_bin NOT NULL,
  `message` text COLLATE utf8_bin NOT NULL,
  `context` varchar(500) COLLATE utf8_bin NOT NULL,
  `url` varchar(255) COLLATE utf8_bin NOT NULL,
  `http_code` smallint(3) unsigned NOT NULL DEFAULT '200',
  `http_method` varchar(10) COLLATE utf8_bin NOT NULL,
  `is_ajax` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `ip` varchar(255) COLLATE utf8_bin NOT NULL,
  PRIMARY KEY (`log_id`),
  KEY `user_id` (`user_id`),
  KEY `time` (`log_date`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `site_menus` (
  `menu_id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `menu_alias` varchar(255) COLLATE utf8_bin NOT NULL,
  `menu_title` varchar(255) COLLATE utf8_bin NOT NULL,
  `menu_active` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `menu_sort` smallint(5) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`menu_id`),
  UNIQUE KEY `menu_alias` (`menu_alias`),
  KEY `sort` (`menu_sort`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=3 ;

CREATE TABLE IF NOT EXISTS `site_news` (
  `news_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `site_id` smallint(5) unsigned NOT NULL DEFAULT '0',
  `user_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `news_subject` varchar(255) COLLATE utf8_bin NOT NULL,
  `news_url` varchar(255) COLLATE utf8_bin NOT NULL,
  `news_time` int(11) unsigned NOT NULL DEFAULT '0',
  `news_text` text COLLATE utf8_bin NOT NULL,
  `news_comments` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `news_views` mediumint(8) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`news_id`),
  KEY `news_time` (`news_time`),
  KEY `news` (`news_time`),
  KEY `news_url` (`news_id`,`news_url`),
  KEY `site_id` (`site_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_bin ROW_FORMAT=COMPACT AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `site_openid_identities` (
  `openid_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `openid_time` int(11) unsigned NOT NULL DEFAULT '0',
  `openid_last_use` int(11) unsigned NOT NULL DEFAULT '0',
  `openid_provider` varchar(255) COLLATE utf8_bin NOT NULL,
  `openid_uid` varchar(255) COLLATE utf8_bin NOT NULL,
  `openid_identity` varchar(255) COLLATE utf8_bin NOT NULL,
  `openid_first_name` varchar(255) COLLATE utf8_bin NOT NULL,
  `openid_last_name` varchar(255) COLLATE utf8_bin NOT NULL,
  `openid_dob` varchar(10) COLLATE utf8_bin NOT NULL,
  `openid_gender` varchar(2) COLLATE utf8_bin NOT NULL,
  `openid_email` varchar(255) COLLATE utf8_bin NOT NULL,
  `openid_photo` varchar(255) COLLATE utf8_bin NOT NULL,
  PRIMARY KEY (`openid_id`),
  UNIQUE KEY `uid_provider` (`openid_uid`,`openid_provider`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `site_pages` (
  `page_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `site_id` smallint(5) unsigned NOT NULL DEFAULT '0',
  `parent_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `left_id` smallint(5) unsigned NOT NULL DEFAULT '0',
  `right_id` smallint(5) unsigned NOT NULL DEFAULT '0',
  `is_dir` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `page_enabled` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `page_display` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `page_name` varchar(255) COLLATE utf8_bin NOT NULL,
  `page_title` varchar(255) COLLATE utf8_bin NOT NULL,
  `page_url` varchar(255) COLLATE utf8_bin NOT NULL,
  `page_formats` varchar(255) COLLATE utf8_bin NOT NULL,
  `page_redirect` varchar(255) COLLATE utf8_bin NOT NULL,
  `page_text` text COLLATE utf8_bin NOT NULL,
  `page_handler` varchar(255) COLLATE utf8_bin NOT NULL,
  `handler_method` varchar(255) COLLATE utf8_bin NOT NULL,
  `page_description` varchar(255) COLLATE utf8_bin NOT NULL,
  `page_keywords` varchar(255) COLLATE utf8_bin NOT NULL,
  `page_noindex` tinyint(1) NOT NULL DEFAULT '0',
  `page_comments` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `page_image` varchar(100) COLLATE utf8_bin NOT NULL,
  `display_in_menu_1` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `display_in_menu_2` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`page_id`),
  KEY `page` (`parent_id`),
  KEY `site_id` (`site_id`,`left_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `site_sessions` (
  `session_id` char(32) COLLATE utf8_bin NOT NULL,
  `user_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `openid_provider` varchar(25) COLLATE utf8_bin NOT NULL,
  `session_last_visit` int(11) unsigned NOT NULL DEFAULT '0',
  `session_start` int(11) unsigned NOT NULL DEFAULT '0',
  `session_time` int(11) unsigned NOT NULL DEFAULT '0',
  `session_ip` varchar(40) COLLATE utf8_bin NOT NULL,
  `session_data` varchar(8192) COLLATE utf8_bin NOT NULL,
  `session_browser` varchar(255) COLLATE utf8_bin NOT NULL,
  `session_forwarded_for` varchar(255) COLLATE utf8_bin NOT NULL,
  `session_domain` varchar(255) COLLATE utf8_bin NOT NULL,
  `session_page` varchar(255) COLLATE utf8_bin NOT NULL,
  `session_referer` varchar(255) COLLATE utf8_bin NOT NULL,
  `session_autologin` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `session_admin` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`session_id`),
  KEY `session_user_id` (`session_time`),
  KEY `session_user` (`user_id`,`session_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

CREATE TABLE IF NOT EXISTS `site_sessions_keys` (
  `key_id` char(32) COLLATE utf8_bin NOT NULL,
  `user_id` mediumint(8) unsigned NOT NULL,
  `openid_provider` varchar(25) COLLATE utf8_bin NOT NULL,
  `last_ip` varchar(40) COLLATE utf8_bin NOT NULL,
  `last_login` int(11) unsigned NOT NULL,
  PRIMARY KEY (`key_id`,`user_id`),
  KEY `last_login` (`last_login`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

CREATE TABLE IF NOT EXISTS `site_sites` (
  `site_id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `site_language` varchar(30) COLLATE utf8_bin NOT NULL,
  `site_locale` varchar(30) COLLATE utf8_bin NOT NULL,
  `site_title` varchar(255) COLLATE utf8_bin NOT NULL,
  `site_url` varchar(255) COLLATE utf8_bin NOT NULL,
  `site_aliases` varchar(255) COLLATE utf8_bin NOT NULL,
  `site_default` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`site_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `site_users` (
  `user_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `user_access` text COLLATE utf8_bin NOT NULL,
  `user_active` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `username` varchar(255) COLLATE utf8_bin NOT NULL,
  `username_clean` varchar(255) COLLATE utf8_bin NOT NULL,
  `user_url` varchar(255) COLLATE utf8_bin NOT NULL,
  `user_password` varchar(32) COLLATE utf8_bin NOT NULL,
  `user_salt` varchar(5) COLLATE utf8_bin NOT NULL,
  `user_session_page` varchar(255) COLLATE utf8_bin NOT NULL,
  `user_last_visit` int(11) unsigned NOT NULL DEFAULT '0',
  `user_regdate` int(11) unsigned NOT NULL DEFAULT '0',
  `user_ip` varchar(40) COLLATE utf8_bin NOT NULL,
  `user_money` decimal(6,2) NOT NULL DEFAULT '0.00',
  `user_points` decimal(6,2) NOT NULL DEFAULT '0.00',
  `user_posts` mediumint(6) unsigned NOT NULL DEFAULT '0',
  `user_rank` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `user_colour` varchar(6) COLLATE utf8_bin NOT NULL,
  `user_first_name` varchar(255) COLLATE utf8_bin NOT NULL,
  `user_last_name` varchar(255) COLLATE utf8_bin NOT NULL,
  `user_birth_year` smallint(4) NOT NULL DEFAULT '0',
  `user_birth_month` tinyint(2) NOT NULL DEFAULT '0',
  `user_birth_day` tinyint(2) NOT NULL DEFAULT '0',
  `user_language` varchar(30) COLLATE utf8_bin NOT NULL,
  `user_email` varchar(100) COLLATE utf8_bin NOT NULL,
  `user_icq` varchar(15) COLLATE utf8_bin NOT NULL,
  `user_jid` varchar(100) COLLATE utf8_bin NOT NULL,
  `user_website` varchar(100) COLLATE utf8_bin NOT NULL,
  `user_from` varchar(255) COLLATE utf8_bin NOT NULL,
  `user_occ` varchar(1000) COLLATE utf8_bin NOT NULL,
  `user_interests` varchar(1000) COLLATE utf8_bin NOT NULL,
  `user_login_attempts` tinyint(2) unsigned NOT NULL DEFAULT '0',
  `user_form_salt` varchar(10) COLLATE utf8_bin NOT NULL,
  `user_newpasswd` varchar(32) COLLATE utf8_bin NOT NULL,
  `user_actkey` varchar(32) COLLATE utf8_bin NOT NULL,
  PRIMARY KEY (`user_id`),
  KEY `user_url` (`user_url`),
  KEY `username_clean` (`username_clean`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_bin PACK_KEYS=0 ROW_FORMAT=DYNAMIC AUTO_INCREMENT=2 ;

CREATE TABLE IF NOT EXISTS `site_user_groups` (
  `group_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `user_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `group_leader` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `user_pending` tinyint(1) unsigned NOT NULL DEFAULT '1',
  KEY `group_id` (`group_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
