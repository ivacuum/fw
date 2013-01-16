<?php
/**
* @package fw
* @copyright (c) 2013
*/

namespace fw;

/**
* Константы
* apc_delete($acm_prefix . '_constants');
*/
if (false === $app->load_constants($app['acm.prefix']))
{
	$app->set_constants($app['acm.prefix'], [
		/* Способы уведомления */
		'NOTIFY_EMAIL' => 0,
		'NOTIFY_IM'    => 1,
		'NOTIFY_BOTH'  => 2,

		/* Приоритеты доставки писем */
		'MAIL_LOW_PRIORITY'    => 4,
		'MAIL_NORMAL_PRIORITY' => 3,
		'MAIL_HIGH_PRIORITY'   => 2,

		/* Таблицы сайта */
		'AUTH_GROUPS_TABLE'       => 'site_auth_groups',
		'AUTH_OPTIONS_TABLE'      => 'site_auth_options',
		'AUTH_ROLES_TABLE'        => 'site_auth_roles',
		'AUTH_ROLES_DATA_TABLE'   => 'site_auth_roles_data',
		'AUTH_USERS_TABLE'        => 'site_auth_users',
		'BANLIST_TABLE'           => 'site_banlist',
		'BOTS_TABLE'              => 'site_bots',
		'COMMENTS_TABLE'          => 'site_comments',
		'CONFIG_TABLE'            => 'site_config',
		'CONFIRM_TABLE'           => 'site_confirm',
		'CRON_TABLE'              => 'site_cron',
		'GROUPS_TABLE'            => 'site_groups',
		'I18N_TABLE'              => 'site_i18n',
		'IMAGE_WATERMARKS_TABLE'  => 'site_image_watermarks',
		'LANGUAGES_TABLE'         => 'site_languages',
		'NEWS_TABLE'              => 'site_news',
		'OPENID_IDENTITIES_TABLE' => 'site_openid_identities',
		'PAGES_TABLE'             => 'site_pages_tree',
		'SESSIONS_TABLE'          => 'site_sessions',
		'SESSIONS_KEYS_TABLE'     => 'site_sessions_keys',
		'SITES_TABLE'             => 'site_sites',
		'USERS_TABLE'             => 'site_users',
		'USER_GROUPS_TABLE'       => 'site_user_groups'
	]);
}
