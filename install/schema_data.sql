SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

INSERT INTO `site_auth_options` (`auth_id`, `auth_name`, `auth_sub`, `auth_var`, `auth_global`, `auth_local`, `auth_default`) VALUES
(1, 'AUTH_ADMIN', '', 'a_', 1, 0, 0);

INSERT INTO `site_auth_users` (`user_id`, `local_id`, `auth_option_id`, `auth_role_id`, `auth_value`) VALUES
(1, 0, 1, 0, 1);

INSERT INTO `site_cron` (`cron_id`, `site_id`, `cron_active`, `cron_title`, `cron_script`, `cron_schedule`, `run_order`, `last_run`, `next_run`, `run_counter`) VALUES
(1, 1, 1, 'Чистка устаревших сессий', 'sessions\\purge', '+59 minutes', 10, 0, 0, 0),
(2, 1, 1, 'Пересчет значений динамических переменных', 'config\\sync', 'tomorrow 5am', 20, 0, 0, 0),
(3, 1, 1, 'Чистка ключей для восстановления пароля', 'newpasswd\\purge', 'tomorrow 5am', 30, 0, 0, 0);

INSERT INTO `site_groups` (`group_id`, `group_name`, `group_description`, `group_colour`, `group_sort`, `group_display`, `group_legend`, `group_skip_auth`) VALUES
(1, 'GROUP_ADMINS', 'GROUP_ADMINS_DESC', '0066FF', 10, 1, 1, 0),
(2, 'GROUP_MODERATORS', 'GROUP_MODERATORS_DESC', 'FF0000', 20, 1, 1, 0),
(3, 'GROUP_BOTS', 'GROUP_BOTS_DESC', '9E8DA7', 30, 1, 1, 0);

INSERT INTO `site_i18n` (`i18n_id`, `site_id`, `i18n_lang`, `i18n_subindex`, `i18n_index`, `i18n_file`, `i18n_translation`) VALUES
(1, 0, 'en', '', 'ACP', 'general', 'Admin Control Panel'),
(2, 0, 'ru', '', 'ACP', 'general', 'Админ центр'),
(9, 0, 'en', '', 'CURRENT_TIME', 'general', 'Current time: %s'),
(10, 0, 'ru', '', 'CURRENT_TIME', 'general', 'Текущее время: %s'),
(11, 0, 'en', '', 'INDEX_PAGE', 'general', 'Home'),
(12, 0, 'ru', '', 'INDEX_PAGE', 'general', 'Главная страница'),
(13, 0, 'en', '', 'LAST_VISIT', 'general', 'Last visit: %s'),
(14, 0, 'ru', '', 'LAST_VISIT', 'general', 'Прошлый визит: %s'),
(15, 0, 'en', '', 'LOGIN', 'general', 'Log in'),
(16, 0, 'ru', '', 'LOGIN', 'general', 'Вход'),
(17, 0, 'en', '', 'LOGOUT', 'general', 'Log out [ %s ]'),
(18, 0, 'ru', '', 'LOGOUT', 'general', 'Выход [ %s ]'),
(23, 0, 'en', '', 'REGISTER', 'general', 'Register'),
(24, 0, 'ru', '', 'REGISTER', 'general', 'Регистрация'),
(41, 0, 'en', '', 'AUTHOR', 'general', 'Author'),
(42, 0, 'ru', '', 'AUTHOR', 'general', 'Автор'),
(43, 0, 'en', '', 'AUTOLOGIN', 'ucp_auth', 'Log on automatically'),
(44, 0, 'ru', '', 'AUTOLOGIN', 'ucp_auth', 'Входить автоматически'),
(45, 0, 'en', '', 'COMMENTS', 'general', 'Comments'),
(46, 0, 'ru', '', 'COMMENTS', 'general', 'Комментарии'),
(47, 0, 'en', '', 'GO', 'general', 'Go'),
(48, 0, 'ru', '', 'GO', 'general', 'Вперёд'),
(49, 0, 'en', '', 'HIDEME', 'ucp_auth', 'Hide my online status on this site'),
(50, 0, 'ru', '', 'HIDEME', 'ucp_auth', 'Скрывать моё пребывание на сайте'),
(51, 0, 'en', '', 'LEGEND', 'general', 'Legend'),
(52, 0, 'ru', '', 'LEGEND', 'general', 'Легенда'),
(53, 0, 'en', '', 'LOGIN_HTTP', 'ucp_auth', 'Non-secure login [http]'),
(54, 0, 'ru', '', 'LOGIN_HTTP', 'ucp_auth', 'Обычный вход на сайт [http]'),
(55, 0, 'en', '', 'LOGIN_HTTPS', 'ucp_auth', 'Secure login [https]'),
(56, 0, 'ru', '', 'LOGIN_HTTPS', 'ucp_auth', 'Безопасный вход на сайт [https]'),
(63, 0, 'en', '', 'OF', 'general', 'of'),
(64, 0, 'ru', '', 'OF', 'general', 'из'),
(65, 0, 'en', '', 'PAGE', 'general', 'Page'),
(66, 0, 'ru', '', 'PAGE', 'general', 'Страница'),
(67, 0, 'en', '', 'PAGE_NOT_FOUND', 'general', 'Page not found.'),
(68, 0, 'ru', '', 'PAGE_NOT_FOUND', 'general', 'Страница не найдена.'),
(71, 0, 'en', '', 'SITE_MESSAGE', 'general', 'Site message'),
(72, 0, 'ru', '', 'SITE_MESSAGE', 'general', 'Сообщение сайта'),
(73, 0, 'en', '', 'TIME_ZONE', 'general', 'Time zone'),
(74, 0, 'ru', '', 'TIME_ZONE', 'general', 'Часовой пояс'),
(75, 0, 'en', '', 'GROUP_ADMINS', 'general', 'Administrators'),
(76, 0, 'ru', '', 'GROUP_ADMINS', 'general', 'Администрация'),
(77, 0, 'en', '', 'GROUP_MODERATORS', 'general', 'Moderators'),
(78, 0, 'ru', '', 'GROUP_MODERATORS', 'general', 'Модераторы'),
(81, 0, 'en', '', 'NEWS_NOT_FOUND', 'news', 'News not found.'),
(82, 0, 'ru', '', 'NEWS_NOT_FOUND', 'news', 'Новость не найдена.'),
(83, 0, 'en', '', 'NEWS_POSTED', 'news', 'Posted'),
(84, 0, 'ru', '', 'NEWS_POSTED', 'news', 'Добавлено'),
(87, 0, 'en', '', 'NO_NEWS', 'news', 'There are no english news on this site.'),
(88, 0, 'ru', '', 'NO_NEWS', 'news', 'На сайте нет новостей на русском языке.'),
(89, 0, 'en', '', 'SUBJECT', 'general', 'Subject'),
(90, 0, 'ru', '', 'SUBJECT', 'general', 'Тема'),
(91, 0, 'en', '', 'ONLINE_LIST_EMPTY', 'who_is_online', 'none'),
(92, 0, 'ru', '', 'ONLINE_LIST_EMPTY', 'who_is_online', 'нет'),
(93, 0, 'en', '', 'ONLINE_LIST_TOTAL', 'who_is_online', 'In total the are <b>%d</b> users on this site :: '),
(94, 0, 'ru', '', 'ONLINE_LIST_TOTAL', 'who_is_online', 'Сейчас посетителей на сайте: <b>%d</b> :: '),
(95, 0, 'en', '', 'ONLINE_LIST_REG', 'who_is_online', 'registered: %d, '),
(96, 0, 'ru', '', 'ONLINE_LIST_REG', 'who_is_online', 'зарегистрированных: %d, '),
(97, 0, 'en', '', 'ONLINE_LIST_GUESTS', 'who_is_online', 'guests: %d.'),
(98, 0, 'ru', '', 'ONLINE_LIST_GUESTS', 'who_is_online', 'гостей: %d.'),
(99, 0, 'en', '', 'ONLINE_TIME', 'who_is_online', 'This data based on users active over the past %d minutes.'),
(100, 0, 'ru', '', 'ONLINE_TIME', 'who_is_online', 'Эти данные основаны на активности пользователей за последние %d минут.'),
(101, 0, 'en', '', 'ONLINE_TITLE', 'who_is_online', 'Online stats'),
(102, 0, 'ru', '', 'ONLINE_TITLE', 'who_is_online', 'Онлайн статистика (кто сейчас на сайте)'),
(103, 0, 'en', '', 'ONLINE_USERLIST', 'who_is_online', 'Registered users'),
(104, 0, 'ru', '', 'ONLINE_USERLIST', 'who_is_online', 'Зарегистрированные'),
(105, 0, 'en', '', 'GO_TO_PAGE', 'general', 'Go to page: '),
(106, 0, 'ru', '', 'GO_TO_PAGE', 'general', 'На страницу: '),
(107, 0, 'en', '', 'PAGE_SEPARATOR', 'general', ', '),
(108, 0, 'ru', '', 'PAGE_SEPARATOR', 'general', ', '),
(109, 0, 'en', '', 'NEWEST_USER', 'general', 'Last registered user'),
(110, 0, 'ru', '', 'NEWEST_USER', 'general', 'Последний зарегистрировавшийся'),
(111, 0, 'en', '', 'STAT_COMMENTS', 'stats', 'Total comments'),
(112, 0, 'ru', '', 'STAT_COMMENTS', 'stats', 'Всего комментариев'),
(113, 0, 'en', '', 'STAT_NEWS', 'stats', 'Total news'),
(114, 0, 'ru', '', 'STAT_NEWS', 'stats', 'Всего новостей'),
(115, 0, 'en', '', 'STAT_USERS', 'stats', 'Total users'),
(116, 0, 'ru', '', 'STAT_USERS', 'stats', 'Всего пользователей'),
(123, 0, 'en', 'datetime', 'TODAY', 'general', 'Today'),
(124, 0, 'ru', 'datetime', 'TODAY', 'general', 'Сегодня'),
(125, 0, 'en', 'datetime', 'TOMORROW', 'general', 'Tomorrow'),
(126, 0, 'ru', 'datetime', 'TOMORROW', 'general', 'Завтра'),
(127, 0, 'en', 'datetime', 'YESTERDAY', 'general', 'Yesterday'),
(128, 0, 'ru', 'datetime', 'YESTERDAY', 'general', 'Вчера'),
(129, 0, 'en', 'datetime', 'Monday', 'general', 'Monday'),
(130, 0, 'ru', 'datetime', 'Monday', 'general', 'Понедельник'),
(131, 0, 'en', 'datetime', 'Tuesday', 'general', 'Tuesday'),
(132, 0, 'ru', 'datetime', 'Tuesday', 'general', 'Вторник'),
(133, 0, 'en', 'datetime', 'Wednesday', 'general', 'Wednesday'),
(134, 0, 'ru', 'datetime', 'Wednesday', 'general', 'Среда'),
(135, 0, 'en', 'datetime', 'Thursday', 'general', 'Thursday'),
(136, 0, 'ru', 'datetime', 'Thursday', 'general', 'Четверг'),
(137, 0, 'en', 'datetime', 'Friday', 'general', 'Friday'),
(138, 0, 'ru', 'datetime', 'Friday', 'general', 'Пятница'),
(139, 0, 'en', 'datetime', 'Saturday', 'general', 'Saturday'),
(140, 0, 'ru', 'datetime', 'Saturday', 'general', 'Суббота'),
(141, 0, 'en', 'datetime', 'Sunday', 'general', 'Sunday'),
(142, 0, 'ru', 'datetime', 'Sunday', 'general', 'Воскресенье'),
(143, 0, 'en', 'datetime', 'Mon', 'general', 'Mon'),
(144, 0, 'ru', 'datetime', 'Mon', 'general', 'Пн'),
(145, 0, 'en', 'datetime', 'Tue', 'general', 'Tue'),
(146, 0, 'ru', 'datetime', 'Tue', 'general', 'Вт'),
(147, 0, 'en', 'datetime', 'Wed', 'general', 'Wed'),
(148, 0, 'ru', 'datetime', 'Wed', 'general', 'Ср'),
(149, 0, 'en', 'datetime', 'Thu', 'general', 'Thu'),
(150, 0, 'ru', 'datetime', 'Thu', 'general', 'Чт'),
(151, 0, 'en', 'datetime', 'Fri', 'general', 'Fri'),
(152, 0, 'ru', 'datetime', 'Fri', 'general', 'Пт'),
(153, 0, 'en', 'datetime', 'Sat', 'general', 'Sat'),
(154, 0, 'ru', 'datetime', 'Sat', 'general', 'Сб'),
(155, 0, 'en', 'datetime', 'Sun', 'general', 'Sun'),
(156, 0, 'ru', 'datetime', 'Sun', 'general', 'Вс'),
(157, 0, 'en', 'datetime', 'January', 'general', 'January'),
(158, 0, 'ru', 'datetime', 'January', 'general', 'Января'),
(159, 0, 'en', 'datetime', 'February', 'general', 'February'),
(160, 0, 'ru', 'datetime', 'February', 'general', 'Февраля'),
(161, 0, 'en', 'datetime', 'March', 'general', 'March'),
(162, 0, 'ru', 'datetime', 'March', 'general', 'Марта'),
(163, 0, 'en', 'datetime', 'April', 'general', 'April'),
(164, 0, 'ru', 'datetime', 'April', 'general', 'Апреля'),
(165, 0, 'en', 'datetime', 'May', 'general', 'May'),
(166, 0, 'ru', 'datetime', 'May', 'general', 'Мая'),
(167, 0, 'en', 'datetime', 'June', 'general', 'June'),
(168, 0, 'ru', 'datetime', 'June', 'general', 'Июня'),
(169, 0, 'en', 'datetime', 'July', 'general', 'July'),
(170, 0, 'ru', 'datetime', 'July', 'general', 'Июля'),
(171, 0, 'en', 'datetime', 'August', 'general', 'August'),
(172, 0, 'ru', 'datetime', 'August', 'general', 'Августа'),
(173, 0, 'en', 'datetime', 'September', 'general', 'September'),
(174, 0, 'ru', 'datetime', 'September', 'general', 'Сентября'),
(175, 0, 'en', 'datetime', 'October', 'general', 'October'),
(176, 0, 'ru', 'datetime', 'October', 'general', 'Октября'),
(177, 0, 'en', 'datetime', 'November', 'general', 'November'),
(178, 0, 'ru', 'datetime', 'November', 'general', 'Ноября'),
(179, 0, 'en', 'datetime', 'December', 'general', 'December'),
(180, 0, 'ru', 'datetime', 'December', 'general', 'Декабря'),
(181, 0, 'en', 'datetime', 'Jan', 'general', 'Jan'),
(182, 0, 'ru', 'datetime', 'Jan', 'general', 'Янв'),
(183, 0, 'en', 'datetime', 'Feb', 'general', 'Feb'),
(184, 0, 'ru', 'datetime', 'Feb', 'general', 'Фев'),
(185, 0, 'en', 'datetime', 'Mar', 'general', 'Mar'),
(186, 0, 'ru', 'datetime', 'Mar', 'general', 'Мар'),
(187, 0, 'en', 'datetime', 'Apr', 'general', 'Apr'),
(188, 0, 'ru', 'datetime', 'Apr', 'general', 'Апр'),
(189, 0, 'en', 'datetime', 'Jun', 'general', 'Jun'),
(190, 0, 'ru', 'datetime', 'Jun', 'general', 'Июн'),
(191, 0, 'en', 'datetime', 'Jul', 'general', 'Jul'),
(192, 0, 'ru', 'datetime', 'Jul', 'general', 'Июл'),
(193, 0, 'en', 'datetime', 'Aug', 'general', 'Aug'),
(194, 0, 'ru', 'datetime', 'Aug', 'general', 'Авг'),
(195, 0, 'en', 'datetime', 'Sep', 'general', 'Sep'),
(196, 0, 'ru', 'datetime', 'Sep', 'general', 'Сен'),
(197, 0, 'en', 'datetime', 'Oct', 'general', 'Oct'),
(198, 0, 'ru', 'datetime', 'Oct', 'general', 'Окт'),
(199, 0, 'en', 'datetime', 'Nov', 'general', 'Nov'),
(200, 0, 'ru', 'datetime', 'Nov', 'general', 'Ноя'),
(201, 0, 'en', 'datetime', 'Dec', 'general', 'Dec'),
(202, 0, 'ru', 'datetime', 'Dec', 'general', 'Дек'),
(203, 0, 'en', '', 'NEWS_TEXT', 'news', 'News text'),
(204, 0, 'ru', '', 'NEWS_TEXT', 'news', 'Текст новости'),
(205, 0, 'en', '', 'NEWS_TEXT_HIDE', 'news', 'News text is hidden, press heading («<b>News text</b>») to display it.'),
(206, 0, 'ru', '', 'NEWS_TEXT_HIDE', 'news', 'Текст новости скрыт, нажмите на заголовок («<b>Текст новости</b>»), чтобы отобразить его.'),
(207, 0, 'en', '', 'SAVE', 'general', 'Save'),
(208, 0, 'ru', '', 'SAVE', 'general', 'Сохранить'),
(209, 0, 'en', '', 'CANCEL', 'general', 'Cancel'),
(210, 0, 'ru', '', 'CANCEL', 'general', 'Отмена'),
(237, 0, 'en', '', 'SEND', 'general', 'Send'),
(238, 0, 'ru', '', 'SEND', 'general', 'Отправить'),
(239, 0, 'en', '', 'RESET', 'general', 'Reset'),
(240, 0, 'ru', '', 'RESET', 'general', 'Сбросить'),
(241, 0, 'en', '', 'MESSAGE', 'general', 'Message'),
(242, 0, 'ru', '', 'MESSAGE', 'general', 'Сообщение'),
(243, 0, 'en', '', 'ACCESS_FORBIDDEN', 'general', 'Access forbidden'),
(244, 0, 'ru', '', 'ACCESS_FORBIDDEN', 'general', 'Доступ запрещен'),
(245, 0, 'en', '', 'YES', 'general', 'Yes'),
(246, 0, 'ru', '', 'YES', 'general', 'Да'),
(247, 0, 'en', '', 'NO', 'general', 'No'),
(248, 0, 'ru', '', 'NO', 'general', 'Нет'),
(251, 0, 'en', '', 'ERROR', 'general', 'Error'),
(252, 0, 'ru', '', 'ERROR', 'general', 'Ошибка'),
(253, 0, 'en', '', 'USERNAME', 'general', 'Username'),
(254, 0, 'ru', '', 'USERNAME', 'general', 'Логин'),
(293, 0, 'en', '', 'FILE', 'general', 'File'),
(294, 0, 'ru', '', 'FILE', 'general', 'Файл'),
(299, 0, 'en', '', 'GROUP_BOTS', 'general', 'Bots'),
(300, 0, 'ru', '', 'GROUP_BOTS', 'general', 'Боты'),
(312, 0, 'en', '', 'GUEST', 'general', 'Guest'),
(313, 0, 'ru', '', 'GUEST', 'general', 'Гость'),
(356, 0, 'en', '', 'PASSWORD', 'general', 'Password'),
(357, 0, 'ru', '', 'PASSWORD', 'general', 'Пароль'),
(358, 0, 'en', '', 'FORGOT_PASSWORD', 'general', 'Forgot your password?'),
(359, 0, 'ru', '', 'FORGOT_PASSWORD', 'general', 'Забыли пароль?'),
(360, 0, 'en', '', 'LOGIN_DESCRIPTION', 'ucp_login', 'In order to login you must be registered. Registering takes only a few moments but gives you increased capabilities. After registration you can set up your profile, post comments, etc. The site administrator may also grant additional permissions to registered users.'),
(361, 0, 'ru', '', 'LOGIN_DESCRIPTION', 'ucp_login', 'Чтобы войти на сайт, вы должны быть зарегистрированы. Регистрация занимает всего несколько минут, зато она расширяет ваши возможности. Вы сможете устанавливать собственные настройки, отправлять сообщения, добавлял цитаты и многое другое. Также админстратор сайта может назначить зарегистрированным пользователям особые права доступа.'),
(362, 0, 'en', '', 'SUCCESSFULL_LOGIN', 'ucp_login', 'You successfully logged in.'),
(363, 0, 'ru', '', 'SUCCESSFULL_LOGIN', 'ucp_login', 'Вы успешно вошли на сайт.'),
(366, 0, 'en', '', 'SUCCESSFULL_LOGOUT', 'ucp_login', 'You successfully logged out.'),
(367, 0, 'ru', '', 'SUCCESSFULL_LOGOUT', 'ucp_login', 'Вы успешно вышли с сайта.'),
(368, 0, 'en', '', 'EMAIL', 'general', 'E-mail address'),
(369, 0, 'ru', '', 'EMAIL', 'general', 'Адрес e-mail'),
(370, 0, 'en', '', 'SYMBOLS', 'ucp', 'symbols'),
(371, 0, 'ru', '', 'SYMBOLS', 'ucp', 'символов'),
(372, 0, 'en', '', 'REPEAT', 'ucp', 'repeat'),
(373, 0, 'ru', '', 'REPEAT', 'ucp', 'повторно'),
(374, 0, 'en', '', 'CAPTCHA_CODE', 'ucp', 'Captcha code'),
(375, 0, 'ru', '', 'CAPTCHA_CODE', 'ucp', 'Код подтверждения'),
(376, 0, 'en', '', 'USER_NOT_FOUND', 'ucp_viewprofile', 'User not found.'),
(377, 0, 'ru', '', 'USER_NOT_FOUND', 'ucp_viewprofile', 'Пользователь не найден.'),
(378, 0, 'en', '', 'PROFILE_VIEWING', 'ucp_viewprofile', '%s profile viewing'),
(379, 0, 'ru', '', 'PROFILE_VIEWING', 'ucp_viewprofile', 'Просмотр профиля %s'),
(380, 0, 'en', '', 'EDIT', 'general', 'Edit'),
(381, 0, 'ru', '', 'EDIT', 'general', 'Изменить'),
(382, 0, 'en', '', 'DELETE', 'general', 'Delete'),
(383, 0, 'ru', '', 'DELETE', 'general', 'Удалить'),
(386, 0, 'en', '', 'BACK', 'general', 'Back'),
(387, 0, 'ru', '', 'BACK', 'general', 'Назад'),
(391, 0, 'en', '', 'RETURN', 'general', 'Return'),
(392, 0, 'ru', '', 'RETURN', 'general', 'Вернуться'),
(413, 0, 'en', '', 'DAYS', 'general', 'days'),
(414, 0, 'ru', '', 'DAYS', 'general', 'дней'),
(417, 0, 'en', '', 'DELETE_CONFIRM', 'general', 'Are you sure?'),
(418, 0, 'ru', '', 'DELETE_CONFIRM', 'general', 'Вы уверены?'),
(427, 0, 'en', '', 'VIEWS', 'general', 'Views'),
(428, 0, 'ru', '', 'VIEWS', 'general', 'Просмотров'),
(431, 0, 'en', '', 'POSTED', 'general', 'Posted'),
(432, 0, 'ru', '', 'POSTED', 'general', 'Опубликовано'),
(433, 0, 'en', '', 'POST_ADDED', 'general', 'Post added.'),
(434, 0, 'ru', '', 'POST_ADDED', 'general', 'Сообщение добавлено.'),
(435, 0, 'en', '', 'NO_FILES', 'general', 'No files.'),
(436, 0, 'ru', '', 'NO_FILES', 'general', 'Нет файлов.'),
(437, 0, 'en', '', 'PERMANENTLY', 'general', 'permanently'),
(438, 0, 'ru', '', 'PERMANENTLY', 'general', 'навсегда'),
(439, 0, 'en', '', 'NEVER', 'general', 'never'),
(440, 0, 'ru', '', 'NEVER', 'general', 'никогда'),
(449, 0, 'en', '', 'ADD', 'general', 'Add'),
(450, 0, 'ru', '', 'ADD', 'general', 'Добавить'),
(466, 0, 'en', '', 'UNKNOWN', 'general', 'Unknown'),
(467, 0, 'ru', '', 'UNKNOWN', 'general', 'Неизвестно'),
(481, 0, 'en', '', 'NO_COMMENTS', 'general', 'Comments have not yet posted'),
(482, 0, 'ru', '', 'NO_COMMENTS', 'general', 'Комментариев ещё никто не оставил'),
(483, 0, 'en', '', 'COMMENTS_ADD', 'general', 'Add a comment'),
(484, 0, 'ru', '', 'COMMENTS_ADD', 'general', 'Добавление комментария'),
(499, 0, 'en', '', 'SHOW_NEWS_TEXT', 'news', 'Show news text'),
(500, 0, 'ru', '', 'SHOW_NEWS_TEXT', 'news', 'Показать текст новости'),
(505, 0, 'en', '', 'LOGOUT_CLEAN', 'general', 'Logout'),
(506, 0, 'ru', '', 'LOGOUT_CLEAN', 'general', 'Выход'),
(507, 0, 'en', '', 'SETTINGS', 'general', 'Settings'),
(508, 0, 'ru', '', 'SETTINGS', 'general', 'Настройки'),
(509, 0, 'en', '', 'NEED_LOGIN', 'general', 'You must be <a href="%s">logged in</a> to view this page.'),
(510, 0, 'ru', '', 'NEED_LOGIN', 'general', 'Для просмотра этой страницы необходимо <a href="%s">войти на сайт</a>.'),
(517, 0, 'en', '', 'DISABLE', 'general', 'Disable'),
(518, 0, 'ru', '', 'DISABLE', 'general', 'Отключить'),
(519, 0, 'en', '', 'ENABLE', 'general', 'Enable'),
(520, 0, 'ru', '', 'ENABLE', 'general', 'Включить'),
(555, 0, 'en', '', 'SUBMIT', 'general', 'Submit'),
(556, 0, 'ru', '', 'SUBMIT', 'general', 'Отправить'),
(577, 0, 'en', '', 'REFERERS', 'general', 'Referers'),
(578, 0, 'ru', '', 'REFERERS', 'general', 'Ссылающиеся домены'),
(579, 0, 'en', '', 'FILTER', 'general', 'Filter'),
(580, 0, 'ru', '', 'FILTER', 'general', 'Фильтр'),
(659, 0, 'en', '', 'SIZE_BYTES', 'general', 'bytes'),
(660, 0, 'ru', '', 'SIZE_BYTES', 'general', 'байт'),
(661, 0, 'en', '', 'SIZE_KB', 'general', 'KB'),
(662, 0, 'ru', '', 'SIZE_KB', 'general', 'КБ'),
(663, 0, 'en', '', 'SIZE_MB', 'general', 'MB'),
(664, 0, 'ru', '', 'SIZE_MB', 'general', 'МБ'),
(665, 0, 'en', '', 'SIZE_GB', 'general', 'GB'),
(666, 0, 'ru', '', 'SIZE_GB', 'general', 'ГБ'),
(667, 0, 'en', '', 'SIZE_TB', 'general', 'TB'),
(668, 0, 'ru', '', 'SIZE_TB', 'general', 'ТБ'),
(669, 0, 'en', '', 'SIZE_PB', 'general', 'PB'),
(670, 0, 'ru', '', 'SIZE_PB', 'general', 'ПБ'),
(671, 0, 'en', '', 'SIZE_EB', 'general', 'EB'),
(672, 0, 'ru', '', 'SIZE_EB', 'general', 'ЭБ'),
(673, 0, 'en', '', 'SIZE_ZB', 'general', 'ZB'),
(674, 0, 'ru', '', 'SIZE_ZB', 'general', 'ЗБ'),
(675, 0, 'en', '', 'SIZE_YB', 'general', 'YB'),
(676, 0, 'ru', '', 'SIZE_YB', 'general', 'ЙБ'),
(691, 0, 'en', 'plural', 'VIEWS', 'general', 'view;views'),
(692, 0, 'ru', 'plural', 'VIEWS', 'general', 'просмотр;просмотра;просмотров'),
(693, 0, 'en', 'plural', 'COMMENTS', 'general', 'comment;comments'),
(694, 0, 'ru', 'plural', 'COMMENTS', 'general', 'комментарий;комментария;комментариев'),
(697, 0, 'en', '', 'FORM_INPUT_REQUIREMENTS', 'general', 'Form input requirements'),
(698, 0, 'ru', '', 'FORM_INPUT_REQUIREMENTS', 'general', 'Требования к заполнению формы'),
(699, 0, 'en', '', 'ALL_FIELDS_ARE_REQUIRED', 'general', 'All fields are required to fill.'),
(700, 0, 'ru', '', 'ALL_FIELDS_ARE_REQUIRED', 'general', 'Все поля обязательны для заполнения.'),
(701, 0, 'en', '', 'EXAMPLE', 'general', 'Example'),
(702, 0, 'ru', '', 'EXAMPLE', 'general', 'Пример'),
(703, 0, 'en', '', 'PASSWORDS_MUST_BE_IDENTICAL', 'ucp', 'Passwords must be identical'),
(704, 0, 'ru', '', 'PASSWORDS_MUST_BE_IDENTICAL', 'ucp', 'Пароли должны совпадать'),
(705, 0, 'en', '', 'EMAILS_MUST_BE_IDENTICAL', 'ucp', 'E-mail addresses must be identical'),
(706, 0, 'ru', '', 'EMAILS_MUST_BE_IDENTICAL', 'ucp', 'E-mail адреса должны совпадать'),
(707, 0, 'ru', '', 'CONSOLE', 'profiler', 'Консоль'),
(708, 0, 'en', '', 'CONSOLE', 'profiler', 'Console'),
(709, 0, 'ru', '', 'LOAD_TIME', 'profiler', 'Время загрузки'),
(710, 0, 'en', '', 'LOAD_TIME', 'profiler', 'Load time'),
(711, 0, 'ru', '', 'DATABASE', 'profiler', 'База данных'),
(712, 0, 'en', '', 'DATABASE', 'profiler', 'Database'),
(713, 0, 'ru', '', 'MEMORY_USED', 'profiler', 'Расход памяти'),
(714, 0, 'en', '', 'MEMORY_USED', 'profiler', 'Memory used'),
(715, 0, 'ru', '', 'INCLUDED', 'profiler', 'Загружено'),
(716, 0, 'en', '', 'INCLUDED', 'profiler', 'Included'),
(717, 0, 'ru', 'plural', 'FILES', 'profiler', 'файл;файла;файлов'),
(718, 0, 'en', 'plural', 'FILES', 'profiler', 'file;files'),
(719, 0, 'ru', 'plural', 'QUERIES', 'profiler', 'запрос;запроса;запросов'),
(720, 0, 'en', 'plural', 'QUERIES', 'profiler', 'query;queries'),
(721, 0, 'ru', '', 'DETAILS', 'profiler', 'Подробнее'),
(722, 0, 'en', '', 'DETAILS', 'profiler', 'Details'),
(723, 0, 'ru', '', 'HIDE_PROFILER', 'profiler', 'Скрыть профайлер'),
(724, 0, 'en', '', 'HIDE_PROFILER', 'profiler', 'Hide profiler'),
(725, 0, 'ru', '', 'TOTAL_FILES', 'profiler', 'Всего файлов'),
(726, 0, 'en', '', 'TOTAL_FILES', 'profiler', 'Total files'),
(727, 0, 'ru', '', 'TOTAL_SIZE', 'profiler', 'Общий размер'),
(728, 0, 'en', '', 'TOTAL_SIZE', 'profiler', 'Total size'),
(729, 0, 'ru', '', 'LARGEST', 'profiler', 'Наибольший размер'),
(730, 0, 'en', '', 'LARGEST', 'profiler', 'Largest'),
(731, 0, 'ru', '', 'NO_DATA', 'profiler', 'Этот раздел не содержит данных'),
(732, 0, 'en', '', 'NO_DATA', 'profiler', 'This section does not contain any data'),
(733, 0, 'ru', '', 'TOTAL_QUERIES', 'profiler', 'Всего запросов'),
(734, 0, 'en', '', 'TOTAL_QUERIES', 'profiler', 'Total queries'),
(735, 0, 'ru', '', 'FROM_CACHE', 'profiler', 'Из кэша'),
(736, 0, 'en', '', 'FROM_CACHE', 'profiler', 'From cache'),
(737, 0, 'ru', '', 'EXECUTION_TIME', 'profiler', 'Время выполнения'),
(738, 0, 'en', '', 'EXECUTION_TIME', 'profiler', 'Execution time'),
(739, 0, 'ru', '', 'LANGUAGE', 'general', 'Язык'),
(740, 0, 'en', '', 'LANGUAGE', 'general', 'Language'),
(743, 0, 'ru', '', 'SIGNIN', 'general', 'Вход'),
(744, 0, 'en', '', 'SIGNIN', 'general', 'Sign in'),
(745, 0, 'ru', '', 'SIGNOUT', 'general', 'Выход'),
(746, 0, 'en', '', 'SIGNOUT', 'general', 'Sign out'),
(749, 0, 'ru', '', 'GO_TO_COMMENTS', 'news', 'Перейти к комментариям'),
(750, 0, 'en', '', 'GO_TO_COMMENTS', 'news', 'Go to comments'),
(753, 0, 'ru', '', 'FEEDBACK', 'general', 'Обратная связь'),
(754, 0, 'en', '', 'FEEDBACK', 'general', 'Feedback'),
(755, 0, 'ru', '', 'USERNAME_OR_EMAIL', 'general', 'Логин или Email'),
(756, 0, 'en', '', 'USERNAME_OR_EMAIL', 'general', 'Username or Email');

INSERT INTO `site_languages` (`language_id`, `language_title`, `language_full_title`, `language_direction`, `language_name`, `language_sort`) VALUES
(1, 'ru', 'ru_ru', 'ltr', 'Russian', 20),
(2, 'en', 'en_us', 'ltr', 'English (US)', 10);

INSERT INTO `site_menus` (`menu_id`, `menu_alias`, `menu_title`, `menu_active`, `menu_sort`) VALUES
(1, '2nd_level_menu', 'Меню второго уровня', 1, 1),
(2, '3rd_level_menu', 'Меню третьего уровня', 1, 2);

INSERT INTO `site_pages` (`page_id`, `site_id`, `parent_id`, `left_id`, `right_id`, `is_dir`, `page_enabled`, `page_display`, `page_name`, `page_title`, `page_url`, `page_formats`, `page_redirect`, `page_text`, `page_handler`, `handler_method`, `page_description`, `page_keywords`, `page_noindex`, `page_comments`, `page_image`, `display_in_menu_1`, `display_in_menu_2`) VALUES
(1, 1, 0, 1, 2, 0, 1, 0, 'Главная страница', '', 'index', 'html', '', '', '', '', '', '', 0, 0, '', 0, 0),
(2, 1, 0, 3, 52, 1, 1, 0, 'Личный кабинет', '', 'ucp', 'html', '', '<p>Выберите раздел.</p>', 'ucp', 'index', '', '', 1, 0, '1', 1, 0),
(3, 1, 2, 4, 29, 1, 1, 0, 'OAuth', '', 'oauth', 'html', '', '', '', '', '', '', 0, 0, '', 0, 0),
(4, 1, 3, 5, 8, 1, 1, 0, 'Facebook', '', 'facebook', 'html', '', '', '\\fw\\modules\\ucp\\oauth\\facebook', 'index', '', '', 0, 0, '', 0, 0),
(5, 1, 4, 6, 7, 1, 1, 0, 'Callback', '', 'callback', 'html', '', '', '\\fw\\modules\\ucp\\oauth\\facebook', 'callback', '', '', 0, 0, '', 0, 0),
(6, 1, 3, 9, 12, 1, 1, 0, 'GitHub', '', 'github', 'html', '', '', '\\fw\\modules\\ucp\\oauth\\github', 'index', '', '', 0, 0, '', 0, 0),
(7, 1, 6, 10, 11, 1, 1, 0, 'Callback', '', 'callback', 'html', '', '', '\\fw\\modules\\ucp\\oauth\\github', 'callback', '', '', 0, 0, '', 0, 0),
(8, 1, 3, 13, 16, 1, 1, 0, 'Google', '', 'google', 'html', '', '', '\\fw\\modules\\ucp\\oauth\\google', 'index', '', '', 0, 0, '', 0, 0),
(9, 1, 8, 14, 15, 1, 1, 0, 'Callback', '', 'callback', 'html', '', '', '\\fw\\modules\\ucp\\oauth\\google', 'callback', '', '', 0, 0, '', 0, 0),
(10, 1, 3, 17, 20, 1, 1, 0, 'Twitter', '', 'twitter', 'html', '', '', '\\fw\\modules\\ucp\\oauth\\twitter', 'index', '', '', 0, 0, '', 0, 0),
(11, 1, 10, 18, 19, 1, 1, 0, 'Callback', '', 'callback', 'html', '', '', '\\fw\\modules\\ucp\\oauth\\twitter', 'callback', '', '', 0, 0, '', 0, 0),
(12, 1, 3, 21, 24, 1, 1, 0, 'ВКонтакте', '', 'vk', 'html', '', '', '\\fw\\modules\\ucp\\oauth\\vk', 'index', '', '', 0, 0, '', 0, 0),
(13, 1, 12, 22, 23, 1, 1, 0, 'Callback', '', 'callback', 'html', '', '', '\\fw\\modules\\ucp\\oauth\\vk', 'callback', '', '', 0, 0, '', 0, 0),
(14, 1, 3, 25, 28, 1, 1, 0, 'Яндекс', '', 'yandex', 'html', '', '', '\\fw\\modules\\ucp\\oauth\\yandex', 'index', '', '', 0, 0, '', 0, 0),
(15, 1, 14, 26, 27, 1, 1, 0, 'Callback', '', 'callback', 'html', '', '', '\\fw\\modules\\ucp\\oauth\\yandex', 'callback', '', '', 0, 0, '', 0, 0),
(16, 1, 2, 30, 31, 1, 1, 0, 'Вход на сайт', '', 'signin', 'html', '', '', 'ucp\\auth', 'signin', '', '', 0, 0, '', 0, 0),
(17, 1, 2, 32, 33, 1, 1, 0, 'Выход с сайта', '', 'signout', 'html', '', '', 'ucp\\auth', 'signout', '', '', 0, 0, '', 0, 0),
(18, 1, 2, 34, 39, 1, 1, 0, 'Регистрация', '', 'register', 'html', '', '', 'ucp\\register', 'index', '', '', 0, 0, '', 0, 0),
(19, 1, 18, 35, 36, 1, 1, 0, 'OpenID-регистрация', '', 'openid', 'html', '', '', 'ucp\\register', 'openid', '', '', 0, 0, '', 0, 0),
(20, 1, 18, 37, 38, 1, 1, 0, 'Завершение регистрации', '', 'complete', 'html', '', '', 'ucp\\register', 'complete', '', '', 0, 0, '', 0, 0),
(21, 1, 2, 40, 43, 1, 1, 0, 'Восстановление пароля', '', 'sendpassword', 'html', '', '', 'ucp\\auth', 'sendpassword', '', '', 0, 0, '', 0, 0),
(22, 1, 21, 41, 42, 1, 1, 0, 'Установка нового пароля', '', '*', 'html', '', '', 'ucp\\auth', 'activate_password', '', '', 0, 0, '', 0, 0),
(23, 1, 2, 44, 45, 1, 1, 0, 'Профиль', '', 'profile', 'html', '', '', 'ucp', 'profile', '', '', 0, 0, '', 1, 0),
(24, 1, 2, 46, 47, 1, 1, 0, 'Смена пароля', '', 'password', 'html', '', '', 'ucp', 'password', '', '', 0, 0, '', 1, 0),
(25, 1, 2, 48, 51, 1, 1, 0, 'Социальные профили', '', 'social', 'html', '', '<p>Вы можете добавить прикрепить к своей учетной записи социальные профили, чтобы с помощью них заходить на сайт в один клик.</p>', 'ucp', 'social', '', '', 0, 0, '', 1, 0),
(26, 1, 25, 49, 50, 1, 1, 0, 'Удаление социального профиля', '', 'delete', 'html', '', '', 'ucp', 'social_delete', '', '', 0, 0, '', 0, 0),
(27, 2, 0, 1, 4, 1, 1, 0, 'Ajax', '', 'ajax', 'html', '', '', '', '', '', '', 0, 0, '', 0, 0),
(28, 2, 27, 2, 3, 1, 1, 0, 'set site id', '', 'set_site_id', 'html', '', '', 'ajax\\main', 'set_site_id', '', '', 0, 0, '', 0, 0),
(29, 2, 0, 5, 10, 1, 1, 2, 'Контент', '', 'content', 'html', '', '', '', '', '', '', 0, 0, 'table', 0, 0),
(30, 2, 29, 6, 9, 1, 1, 2, 'Структура сайта', '', 'structure', 'html', '', '', '', '', '', '', 0, 0, '', 0, 0),
(31, 2, 30, 7, 8, 1, 1, 2, 'Страницы', '', 'pages', 'html', '', '', '\\fw\\modules\\acp\\pages', 'index', '', '', 0, 0, 'document_tree', 0, 0),
(32, 2, 0, 11, 12, 0, 1, 0, 'Приветствие', '', 'index', 'html', '/content/', '<p>Добро пожаловать в систему управления сайтом!</p>\r\n\r\n<p>Для начала работы выберите раздел.</p>', '', '', '', '', 0, 0, 'home', 0, 0);

INSERT INTO `site_sites` (`site_id`, `site_language`, `site_locale`, `site_title`, `site_url`, `site_aliases`, `site_default`) VALUES
(1, 'ru', 'ru_RU.UTF-8', 'localhost (ru)', 'localhost', '', 1),
(2, 'ru', 'ru_RU.UTF-8', 'acp.localhost (ru)', 'acp.localhost', '', 1);

INSERT INTO `site_users` (`user_id`, `user_access`, `user_active`, `username`, `username_clean`, `user_url`, `user_password`, `user_salt`, `user_session_page`, `user_last_visit`, `user_regdate`, `user_ip`, `user_money`, `user_points`, `user_posts`, `user_rank`, `user_colour`, `user_first_name`, `user_last_name`, `user_birth_year`, `user_birth_month`, `user_birth_day`, `user_language`, `user_email`, `user_icq`, `user_jid`, `user_website`, `user_from`, `user_occ`, `user_interests`, `user_login_attempts`, `user_form_salt`, `user_newpasswd`, `user_actkey`) VALUES
(1, '', 1, 'root', 'root', '', '', '', '', 0, 0, '127.0.0.1', '0.00', '0.00', 0, 0, '', '', '', 0, 0, 0, 'ru', 'root@example.com', '', '', '', '', '', '', 0, '', '', '');

