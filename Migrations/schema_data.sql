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

INSERT INTO `site_i18n` (`site_id`, `i18n_lang`, `i18n_subindex`, `i18n_index`, `i18n_file`, `i18n_translation`) VALUES
(0, 'en', '', 'ACP', 'general', 'Admin Control Panel'),
(0, 'ru', '', 'ACP', 'general', 'Админ центр'),
(0, 'en', '', 'CURRENT_TIME', 'general', 'Current time: %s'),
(0, 'ru', '', 'CURRENT_TIME', 'general', 'Текущее время: %s'),
(0, 'en', '', 'INDEX_PAGE', 'general', 'Home'),
(0, 'ru', '', 'INDEX_PAGE', 'general', 'Главная страница'),
(0, 'en', '', 'LAST_VISIT', 'general', 'Last visit: %s'),
(0, 'ru', '', 'LAST_VISIT', 'general', 'Прошлый визит: %s'),
(0, 'en', '', 'LOGIN', 'general', 'Log in'),
(0, 'ru', '', 'LOGIN', 'general', 'Вход'),
(0, 'en', '', 'LOGOUT', 'general', 'Log out [ %s ]'),
(0, 'ru', '', 'LOGOUT', 'general', 'Выход [ %s ]'),
(0, 'en', '', 'REGISTER', 'general', 'Register'),
(0, 'ru', '', 'REGISTER', 'general', 'Регистрация'),
(0, 'en', '', 'AUTHOR', 'general', 'Author'),
(0, 'ru', '', 'AUTHOR', 'general', 'Автор'),
(0, 'en', '', 'AUTOLOGIN', 'ucp_auth', 'Log on automatically'),
(0, 'ru', '', 'AUTOLOGIN', 'ucp_auth', 'Входить автоматически'),
(0, 'en', '', 'COMMENTS', 'general', 'Comments'),
(0, 'ru', '', 'COMMENTS', 'general', 'Комментарии'),
(0, 'en', '', 'GO', 'general', 'Go'),
(0, 'ru', '', 'GO', 'general', 'Вперёд'),
(0, 'en', '', 'HIDEME', 'ucp_auth', 'Hide my online status on this site'),
(0, 'ru', '', 'HIDEME', 'ucp_auth', 'Скрывать моё пребывание на сайте'),
(0, 'en', '', 'LEGEND', 'general', 'Legend'),
(0, 'ru', '', 'LEGEND', 'general', 'Легенда'),
(0, 'en', '', 'LOGIN_HTTP', 'ucp_auth', 'Non-secure login [http]'),
(0, 'ru', '', 'LOGIN_HTTP', 'ucp_auth', 'Обычный вход на сайт [http]'),
(0, 'en', '', 'LOGIN_HTTPS', 'ucp_auth', 'Secure login [https]'),
(0, 'ru', '', 'LOGIN_HTTPS', 'ucp_auth', 'Безопасный вход на сайт [https]'),
(0, 'en', '', 'OF', 'general', 'of'),
(0, 'ru', '', 'OF', 'general', 'из'),
(0, 'en', '', 'PAGE', 'general', 'Page'),
(0, 'ru', '', 'PAGE', 'general', 'Страница'),
(0, 'en', '', 'PAGE_NOT_FOUND', 'general', 'Page not found.'),
(0, 'ru', '', 'PAGE_NOT_FOUND', 'general', 'Страница не найдена.'),
(0, 'en', '', 'SITE_MESSAGE', 'general', 'Site message'),
(0, 'ru', '', 'SITE_MESSAGE', 'general', 'Сообщение сайта'),
(0, 'en', '', 'TIME_ZONE', 'general', 'Time zone'),
(0, 'ru', '', 'TIME_ZONE', 'general', 'Часовой пояс'),
(0, 'en', '', 'GROUP_ADMINS', 'general', 'Administrators'),
(0, 'ru', '', 'GROUP_ADMINS', 'general', 'Администрация'),
(0, 'en', '', 'GROUP_MODERATORS', 'general', 'Moderators'),
(0, 'ru', '', 'GROUP_MODERATORS', 'general', 'Модераторы'),
(0, 'en', '', 'NEWS_NOT_FOUND', 'news', 'News not found.'),
(0, 'ru', '', 'NEWS_NOT_FOUND', 'news', 'Новость не найдена.'),
(0, 'en', '', 'NEWS_POSTED', 'news', 'Posted'),
(0, 'ru', '', 'NEWS_POSTED', 'news', 'Добавлено'),
(0, 'en', '', 'NO_NEWS', 'news', 'There are no english news on this site.'),
(0, 'ru', '', 'NO_NEWS', 'news', 'На сайте нет новостей на русском языке.'),
(0, 'en', '', 'SUBJECT', 'general', 'Subject'),
(0, 'ru', '', 'SUBJECT', 'general', 'Тема'),
(0, 'en', '', 'ONLINE_LIST_EMPTY', 'who_is_online', 'none'),
(0, 'ru', '', 'ONLINE_LIST_EMPTY', 'who_is_online', 'нет'),
(0, 'en', '', 'ONLINE_LIST_TOTAL', 'who_is_online', 'In total the are <b>%d</b> users on this site :: '),
(0, 'ru', '', 'ONLINE_LIST_TOTAL', 'who_is_online', 'Сейчас посетителей на сайте: <b>%d</b> :: '),
(0, 'en', '', 'ONLINE_LIST_REG', 'who_is_online', 'registered: %d, '),
(0, 'ru', '', 'ONLINE_LIST_REG', 'who_is_online', 'зарегистрированных: %d, '),
(0, 'en', '', 'ONLINE_LIST_GUESTS', 'who_is_online', 'guests: %d.'),
(0, 'ru', '', 'ONLINE_LIST_GUESTS', 'who_is_online', 'гостей: %d.'),
(0, 'en', '', 'ONLINE_TIME', 'who_is_online', 'This data based on users active over the past %d minutes.'),
(0, 'ru', '', 'ONLINE_TIME', 'who_is_online', 'Эти данные основаны на активности пользователей за последние %d минут.'),
(0, 'en', '', 'ONLINE_TITLE', 'who_is_online', 'Online stats'),
(0, 'ru', '', 'ONLINE_TITLE', 'who_is_online', 'Онлайн статистика (кто сейчас на сайте)'),
(0, 'en', '', 'ONLINE_USERLIST', 'who_is_online', 'Registered users'),
(0, 'ru', '', 'ONLINE_USERLIST', 'who_is_online', 'Зарегистрированные'),
(0, 'en', '', 'GO_TO_PAGE', 'general', 'Go to page: '),
(0, 'ru', '', 'GO_TO_PAGE', 'general', 'На страницу: '),
(0, 'en', '', 'PAGE_SEPARATOR', 'general', ', '),
(0, 'ru', '', 'PAGE_SEPARATOR', 'general', ', '),
(0, 'en', '', 'NEWEST_USER', 'general', 'Last registered user'),
(0, 'ru', '', 'NEWEST_USER', 'general', 'Последний зарегистрировавшийся'),
(0, 'en', '', 'STAT_COMMENTS', 'stats', 'Total comments'),
(0, 'ru', '', 'STAT_COMMENTS', 'stats', 'Всего комментариев'),
(0, 'en', '', 'STAT_NEWS', 'stats', 'Total news'),
(0, 'ru', '', 'STAT_NEWS', 'stats', 'Всего новостей'),
(0, 'en', '', 'STAT_USERS', 'stats', 'Total users'),
(0, 'ru', '', 'STAT_USERS', 'stats', 'Всего пользователей'),
(0, 'en', 'datetime', 'TODAY', 'general', 'Today'),
(0, 'ru', 'datetime', 'TODAY', 'general', 'Сегодня'),
(0, 'en', 'datetime', 'TOMORROW', 'general', 'Tomorrow'),
(0, 'ru', 'datetime', 'TOMORROW', 'general', 'Завтра'),
(0, 'en', 'datetime', 'YESTERDAY', 'general', 'Yesterday'),
(0, 'ru', 'datetime', 'YESTERDAY', 'general', 'Вчера'),
(0, 'en', 'datetime', 'Monday', 'general', 'Monday'),
(0, 'ru', 'datetime', 'Monday', 'general', 'Понедельник'),
(0, 'en', 'datetime', 'Tuesday', 'general', 'Tuesday'),
(0, 'ru', 'datetime', 'Tuesday', 'general', 'Вторник'),
(0, 'en', 'datetime', 'Wednesday', 'general', 'Wednesday'),
(0, 'ru', 'datetime', 'Wednesday', 'general', 'Среда'),
(0, 'en', 'datetime', 'Thursday', 'general', 'Thursday'),
(0, 'ru', 'datetime', 'Thursday', 'general', 'Четверг'),
(0, 'en', 'datetime', 'Friday', 'general', 'Friday'),
(0, 'ru', 'datetime', 'Friday', 'general', 'Пятница'),
(0, 'en', 'datetime', 'Saturday', 'general', 'Saturday'),
(0, 'ru', 'datetime', 'Saturday', 'general', 'Суббота'),
(0, 'en', 'datetime', 'Sunday', 'general', 'Sunday'),
(0, 'ru', 'datetime', 'Sunday', 'general', 'Воскресенье'),
(0, 'en', 'datetime', 'Mon', 'general', 'Mon'),
(0, 'ru', 'datetime', 'Mon', 'general', 'Пн'),
(0, 'en', 'datetime', 'Tue', 'general', 'Tue'),
(0, 'ru', 'datetime', 'Tue', 'general', 'Вт'),
(0, 'en', 'datetime', 'Wed', 'general', 'Wed'),
(0, 'ru', 'datetime', 'Wed', 'general', 'Ср'),
(0, 'en', 'datetime', 'Thu', 'general', 'Thu'),
(0, 'ru', 'datetime', 'Thu', 'general', 'Чт'),
(0, 'en', 'datetime', 'Fri', 'general', 'Fri'),
(0, 'ru', 'datetime', 'Fri', 'general', 'Пт'),
(0, 'en', 'datetime', 'Sat', 'general', 'Sat'),
(0, 'ru', 'datetime', 'Sat', 'general', 'Сб'),
(0, 'en', 'datetime', 'Sun', 'general', 'Sun'),
(0, 'ru', 'datetime', 'Sun', 'general', 'Вс'),
(0, 'en', 'datetime', 'January', 'general', 'January'),
(0, 'ru', 'datetime', 'January', 'general', 'Января'),
(0, 'en', 'datetime', 'February', 'general', 'February'),
(0, 'ru', 'datetime', 'February', 'general', 'Февраля'),
(0, 'en', 'datetime', 'March', 'general', 'March'),
(0, 'ru', 'datetime', 'March', 'general', 'Марта'),
(0, 'en', 'datetime', 'April', 'general', 'April'),
(0, 'ru', 'datetime', 'April', 'general', 'Апреля'),
(0, 'en', 'datetime', 'May', 'general', 'May'),
(0, 'ru', 'datetime', 'May', 'general', 'Мая'),
(0, 'en', 'datetime', 'June', 'general', 'June'),
(0, 'ru', 'datetime', 'June', 'general', 'Июня'),
(0, 'en', 'datetime', 'July', 'general', 'July'),
(0, 'ru', 'datetime', 'July', 'general', 'Июля'),
(0, 'en', 'datetime', 'August', 'general', 'August'),
(0, 'ru', 'datetime', 'August', 'general', 'Августа'),
(0, 'en', 'datetime', 'September', 'general', 'September'),
(0, 'ru', 'datetime', 'September', 'general', 'Сентября'),
(0, 'en', 'datetime', 'October', 'general', 'October'),
(0, 'ru', 'datetime', 'October', 'general', 'Октября'),
(0, 'en', 'datetime', 'November', 'general', 'November'),
(0, 'ru', 'datetime', 'November', 'general', 'Ноября'),
(0, 'en', 'datetime', 'December', 'general', 'December'),
(0, 'ru', 'datetime', 'December', 'general', 'Декабря'),
(0, 'en', 'datetime', 'Jan', 'general', 'Jan'),
(0, 'ru', 'datetime', 'Jan', 'general', 'Янв'),
(0, 'en', 'datetime', 'Feb', 'general', 'Feb'),
(0, 'ru', 'datetime', 'Feb', 'general', 'Фев'),
(0, 'en', 'datetime', 'Mar', 'general', 'Mar'),
(0, 'ru', 'datetime', 'Mar', 'general', 'Мар'),
(0, 'en', 'datetime', 'Apr', 'general', 'Apr'),
(0, 'ru', 'datetime', 'Apr', 'general', 'Апр'),
(0, 'en', 'datetime', 'Jun', 'general', 'Jun'),
(0, 'ru', 'datetime', 'Jun', 'general', 'Июн'),
(0, 'en', 'datetime', 'Jul', 'general', 'Jul'),
(0, 'ru', 'datetime', 'Jul', 'general', 'Июл'),
(0, 'en', 'datetime', 'Aug', 'general', 'Aug'),
(0, 'ru', 'datetime', 'Aug', 'general', 'Авг'),
(0, 'en', 'datetime', 'Sep', 'general', 'Sep'),
(0, 'ru', 'datetime', 'Sep', 'general', 'Сен'),
(0, 'en', 'datetime', 'Oct', 'general', 'Oct'),
(0, 'ru', 'datetime', 'Oct', 'general', 'Окт'),
(0, 'en', 'datetime', 'Nov', 'general', 'Nov'),
(0, 'ru', 'datetime', 'Nov', 'general', 'Ноя'),
(0, 'en', 'datetime', 'Dec', 'general', 'Dec'),
(0, 'ru', 'datetime', 'Dec', 'general', 'Дек'),
(0, 'en', '', 'NEWS_TEXT', 'news', 'News text'),
(0, 'ru', '', 'NEWS_TEXT', 'news', 'Текст новости'),
(0, 'en', '', 'NEWS_TEXT_HIDE', 'news', 'News text is hidden, press heading («<b>News text</b>») to display it.'),
(0, 'ru', '', 'NEWS_TEXT_HIDE', 'news', 'Текст новости скрыт, нажмите на заголовок («<b>Текст новости</b>»), чтобы отобразить его.'),
(0, 'en', '', 'SAVE', 'general', 'Save'),
(0, 'ru', '', 'SAVE', 'general', 'Сохранить'),
(0, 'en', '', 'CANCEL', 'general', 'Cancel'),
(0, 'ru', '', 'CANCEL', 'general', 'Отмена'),
(0, 'en', '', 'SEND', 'general', 'Send'),
(0, 'ru', '', 'SEND', 'general', 'Отправить'),
(0, 'en', '', 'RESET', 'general', 'Reset'),
(0, 'ru', '', 'RESET', 'general', 'Сбросить'),
(0, 'en', '', 'MESSAGE', 'general', 'Message'),
(0, 'ru', '', 'MESSAGE', 'general', 'Сообщение'),
(0, 'en', '', 'ACCESS_FORBIDDEN', 'general', 'Access forbidden'),
(0, 'ru', '', 'ACCESS_FORBIDDEN', 'general', 'Доступ запрещен'),
(0, 'en', '', 'YES', 'general', 'Yes'),
(0, 'ru', '', 'YES', 'general', 'Да'),
(0, 'en', '', 'NO', 'general', 'No'),
(0, 'ru', '', 'NO', 'general', 'Нет'),
(0, 'en', '', 'ERROR', 'general', 'Error'),
(0, 'ru', '', 'ERROR', 'general', 'Ошибка'),
(0, 'en', '', 'USERNAME', 'general', 'Username'),
(0, 'ru', '', 'USERNAME', 'general', 'Логин'),
(0, 'en', '', 'FILE', 'general', 'File'),
(0, 'ru', '', 'FILE', 'general', 'Файл'),
(0, 'en', '', 'GROUP_BOTS', 'general', 'Bots'),
(0, 'ru', '', 'GROUP_BOTS', 'general', 'Боты'),
(0, 'en', '', 'GUEST', 'general', 'Guest'),
(0, 'ru', '', 'GUEST', 'general', 'Гость'),
(0, 'en', '', 'PASSWORD', 'general', 'Password'),
(0, 'ru', '', 'PASSWORD', 'general', 'Пароль'),
(0, 'en', '', 'FORGOT_PASSWORD', 'general', 'Forgot your password?'),
(0, 'ru', '', 'FORGOT_PASSWORD', 'general', 'Забыли пароль?'),
(0, 'en', '', 'LOGIN_DESCRIPTION', 'ucp_login', 'In order to login you must be registered. Registering takes only a few moments but gives you increased capabilities. After registration you can set up your profile, post comments, etc. The site administrator may also grant additional permissions to registered users.'),
(0, 'ru', '', 'LOGIN_DESCRIPTION', 'ucp_login', 'Чтобы войти на сайт, вы должны быть зарегистрированы. Регистрация занимает всего несколько минут, зато она расширяет ваши возможности. Вы сможете устанавливать собственные настройки, отправлять сообщения, добавлял цитаты и многое другое. Также админстратор сайта может назначить зарегистрированным пользователям особые права доступа.'),
(0, 'en', '', 'SUCCESSFULL_LOGIN', 'ucp_login', 'You successfully logged in.'),
(0, 'ru', '', 'SUCCESSFULL_LOGIN', 'ucp_login', 'Вы успешно вошли на сайт.'),
(0, 'en', '', 'SUCCESSFULL_LOGOUT', 'ucp_login', 'You successfully logged out.'),
(0, 'ru', '', 'SUCCESSFULL_LOGOUT', 'ucp_login', 'Вы успешно вышли с сайта.'),
(0, 'en', '', 'EMAIL', 'general', 'E-mail address'),
(0, 'ru', '', 'EMAIL', 'general', 'Адрес e-mail'),
(0, 'en', '', 'SYMBOLS', 'ucp', 'symbols'),
(0, 'ru', '', 'SYMBOLS', 'ucp', 'символов'),
(0, 'en', '', 'REPEAT', 'ucp', 'repeat'),
(0, 'ru', '', 'REPEAT', 'ucp', 'повторно'),
(0, 'en', '', 'CAPTCHA_CODE', 'ucp', 'Captcha code'),
(0, 'ru', '', 'CAPTCHA_CODE', 'ucp', 'Код подтверждения'),
(0, 'en', '', 'USER_NOT_FOUND', 'ucp_viewprofile', 'User not found.'),
(0, 'ru', '', 'USER_NOT_FOUND', 'ucp_viewprofile', 'Пользователь не найден.'),
(0, 'en', '', 'PROFILE_VIEWING', 'ucp_viewprofile', '%s profile viewing'),
(0, 'ru', '', 'PROFILE_VIEWING', 'ucp_viewprofile', 'Просмотр профиля %s'),
(0, 'en', '', 'EDIT', 'general', 'Edit'),
(0, 'ru', '', 'EDIT', 'general', 'Изменить'),
(0, 'en', '', 'DELETE', 'general', 'Delete'),
(0, 'ru', '', 'DELETE', 'general', 'Удалить'),
(0, 'en', '', 'BACK', 'general', 'Back'),
(0, 'ru', '', 'BACK', 'general', 'Назад'),
(0, 'en', '', 'RETURN', 'general', 'Return'),
(0, 'ru', '', 'RETURN', 'general', 'Вернуться'),
(0, 'en', '', 'DAYS', 'general', 'days'),
(0, 'ru', '', 'DAYS', 'general', 'дней'),
(0, 'en', '', 'DELETE_CONFIRM', 'general', 'Are you sure?'),
(0, 'ru', '', 'DELETE_CONFIRM', 'general', 'Вы уверены?'),
(0, 'en', '', 'VIEWS', 'general', 'Views'),
(0, 'ru', '', 'VIEWS', 'general', 'Просмотров'),
(0, 'en', '', 'POSTED', 'general', 'Posted'),
(0, 'ru', '', 'POSTED', 'general', 'Опубликовано'),
(0, 'en', '', 'POST_ADDED', 'general', 'Post added.'),
(0, 'ru', '', 'POST_ADDED', 'general', 'Сообщение добавлено.'),
(0, 'en', '', 'NO_FILES', 'general', 'No files.'),
(0, 'ru', '', 'NO_FILES', 'general', 'Нет файлов.'),
(0, 'en', '', 'PERMANENTLY', 'general', 'permanently'),
(0, 'ru', '', 'PERMANENTLY', 'general', 'навсегда'),
(0, 'en', '', 'NEVER', 'general', 'never'),
(0, 'ru', '', 'NEVER', 'general', 'никогда'),
(0, 'en', '', 'ADD', 'general', 'Add'),
(0, 'ru', '', 'ADD', 'general', 'Добавить'),
(0, 'en', '', 'UNKNOWN', 'general', 'Unknown'),
(0, 'ru', '', 'UNKNOWN', 'general', 'Неизвестно'),
(0, 'en', '', 'NO_COMMENTS', 'general', 'Comments have not yet posted'),
(0, 'ru', '', 'NO_COMMENTS', 'general', 'Комментариев ещё никто не оставил'),
(0, 'en', '', 'COMMENTS_ADD', 'general', 'Add a comment'),
(0, 'ru', '', 'COMMENTS_ADD', 'general', 'Добавление комментария'),
(0, 'en', '', 'SHOW_NEWS_TEXT', 'news', 'Show news text'),
(0, 'ru', '', 'SHOW_NEWS_TEXT', 'news', 'Показать текст новости'),
(0, 'en', '', 'LOGOUT_CLEAN', 'general', 'Logout'),
(0, 'ru', '', 'LOGOUT_CLEAN', 'general', 'Выход'),
(0, 'en', '', 'SETTINGS', 'general', 'Settings'),
(0, 'ru', '', 'SETTINGS', 'general', 'Настройки'),
(0, 'en', '', 'NEED_LOGIN', 'general', 'You must be <a href="%s">logged in</a> to view this page.'),
(0, 'ru', '', 'NEED_LOGIN', 'general', 'Для просмотра этой страницы необходимо <a href="%s">войти на сайт</a>.'),
(0, 'en', '', 'DISABLE', 'general', 'Disable'),
(0, 'ru', '', 'DISABLE', 'general', 'Отключить'),
(0, 'en', '', 'ENABLE', 'general', 'Enable'),
(0, 'ru', '', 'ENABLE', 'general', 'Включить'),
(0, 'en', '', 'SUBMIT', 'general', 'Submit'),
(0, 'ru', '', 'SUBMIT', 'general', 'Отправить'),
(0, 'en', '', 'REFERERS', 'general', 'Referers'),
(0, 'ru', '', 'REFERERS', 'general', 'Ссылающиеся домены'),
(0, 'en', '', 'FILTER', 'general', 'Filter'),
(0, 'ru', '', 'FILTER', 'general', 'Фильтр'),
(0, 'en', '', 'SIZE_BYTES', 'general', 'bytes'),
(0, 'ru', '', 'SIZE_BYTES', 'general', 'байт'),
(0, 'en', '', 'SIZE_KB', 'general', 'KB'),
(0, 'ru', '', 'SIZE_KB', 'general', 'КБ'),
(0, 'en', '', 'SIZE_MB', 'general', 'MB'),
(0, 'ru', '', 'SIZE_MB', 'general', 'МБ'),
(0, 'en', '', 'SIZE_GB', 'general', 'GB'),
(0, 'ru', '', 'SIZE_GB', 'general', 'ГБ'),
(0, 'en', '', 'SIZE_TB', 'general', 'TB'),
(0, 'ru', '', 'SIZE_TB', 'general', 'ТБ'),
(0, 'en', '', 'SIZE_PB', 'general', 'PB'),
(0, 'ru', '', 'SIZE_PB', 'general', 'ПБ'),
(0, 'en', '', 'SIZE_EB', 'general', 'EB'),
(0, 'ru', '', 'SIZE_EB', 'general', 'ЭБ'),
(0, 'en', '', 'SIZE_ZB', 'general', 'ZB'),
(0, 'ru', '', 'SIZE_ZB', 'general', 'ЗБ'),
(0, 'en', '', 'SIZE_YB', 'general', 'YB'),
(0, 'ru', '', 'SIZE_YB', 'general', 'ЙБ'),
(0, 'en', 'plural', 'VIEWS', 'general', 'view;views'),
(0, 'ru', 'plural', 'VIEWS', 'general', 'просмотр;просмотра;просмотров'),
(0, 'en', 'plural', 'COMMENTS', 'general', 'comment;comments'),
(0, 'ru', 'plural', 'COMMENTS', 'general', 'комментарий;комментария;комментариев'),
(0, 'en', '', 'FORM_INPUT_REQUIREMENTS', 'general', 'Form input requirements'),
(0, 'ru', '', 'FORM_INPUT_REQUIREMENTS', 'general', 'Требования к заполнению формы'),
(0, 'en', '', 'ALL_FIELDS_ARE_REQUIRED', 'general', 'All fields are required to fill.'),
(0, 'ru', '', 'ALL_FIELDS_ARE_REQUIRED', 'general', 'Все поля обязательны для заполнения.'),
(0, 'en', '', 'EXAMPLE', 'general', 'Example'),
(0, 'ru', '', 'EXAMPLE', 'general', 'Пример'),
(0, 'en', '', 'PASSWORDS_MUST_BE_IDENTICAL', 'ucp', 'Passwords must be identical'),
(0, 'ru', '', 'PASSWORDS_MUST_BE_IDENTICAL', 'ucp', 'Пароли должны совпадать'),
(0, 'en', '', 'EMAILS_MUST_BE_IDENTICAL', 'ucp', 'E-mail addresses must be identical'),
(0, 'ru', '', 'EMAILS_MUST_BE_IDENTICAL', 'ucp', 'E-mail адреса должны совпадать'),
(0, 'ru', '', 'CONSOLE', 'profiler', 'Консоль'),
(0, 'en', '', 'CONSOLE', 'profiler', 'Console'),
(0, 'ru', '', 'LOAD_TIME', 'profiler', 'Время загрузки'),
(0, 'en', '', 'LOAD_TIME', 'profiler', 'Load time'),
(0, 'ru', '', 'DATABASE', 'profiler', 'База данных'),
(0, 'en', '', 'DATABASE', 'profiler', 'Database'),
(0, 'ru', '', 'MEMORY_USED', 'profiler', 'Расход памяти'),
(0, 'en', '', 'MEMORY_USED', 'profiler', 'Memory used'),
(0, 'ru', '', 'INCLUDED', 'profiler', 'Загружено'),
(0, 'en', '', 'INCLUDED', 'profiler', 'Included'),
(0, 'ru', 'plural', 'FILES', 'profiler', 'файл;файла;файлов'),
(0, 'en', 'plural', 'FILES', 'profiler', 'file;files'),
(0, 'ru', 'plural', 'QUERIES', 'profiler', 'запрос;запроса;запросов'),
(0, 'en', 'plural', 'QUERIES', 'profiler', 'query;queries'),
(0, 'ru', '', 'DETAILS', 'profiler', 'Подробнее'),
(0, 'en', '', 'DETAILS', 'profiler', 'Details'),
(0, 'ru', '', 'HIDE_PROFILER', 'profiler', 'Скрыть профайлер'),
(0, 'en', '', 'HIDE_PROFILER', 'profiler', 'Hide profiler'),
(0, 'ru', '', 'TOTAL_FILES', 'profiler', 'Всего файлов'),
(0, 'en', '', 'TOTAL_FILES', 'profiler', 'Total files'),
(0, 'ru', '', 'TOTAL_SIZE', 'profiler', 'Общий размер'),
(0, 'en', '', 'TOTAL_SIZE', 'profiler', 'Total size'),
(0, 'ru', '', 'LARGEST', 'profiler', 'Наибольший размер'),
(0, 'en', '', 'LARGEST', 'profiler', 'Largest'),
(0, 'ru', '', 'NO_DATA', 'profiler', 'Этот раздел не содержит данных'),
(0, 'en', '', 'NO_DATA', 'profiler', 'This section does not contain any data'),
(0, 'ru', '', 'TOTAL_QUERIES', 'profiler', 'Всего запросов'),
(0, 'en', '', 'TOTAL_QUERIES', 'profiler', 'Total queries'),
(0, 'ru', '', 'FROM_CACHE', 'profiler', 'Из кэша'),
(0, 'en', '', 'FROM_CACHE', 'profiler', 'From cache'),
(0, 'ru', '', 'EXECUTION_TIME', 'profiler', 'Время выполнения'),
(0, 'en', '', 'EXECUTION_TIME', 'profiler', 'Execution time'),
(0, 'ru', '', 'LANGUAGE', 'general', 'Язык'),
(0, 'en', '', 'LANGUAGE', 'general', 'Language'),
(0, 'ru', '', 'SIGNIN', 'general', 'Вход'),
(0, 'en', '', 'SIGNIN', 'general', 'Sign in'),
(0, 'ru', '', 'SIGNOUT', 'general', 'Выход'),
(0, 'en', '', 'SIGNOUT', 'general', 'Sign out'),
(0, 'ru', '', 'GO_TO_COMMENTS', 'news', 'Перейти к комментариям'),
(0, 'en', '', 'GO_TO_COMMENTS', 'news', 'Go to comments'),
(0, 'ru', '', 'FEEDBACK', 'general', 'Обратная связь'),
(0, 'en', '', 'FEEDBACK', 'general', 'Feedback'),
(0, 'ru', '', 'USERNAME_OR_EMAIL', 'general', 'Логин или Email'),
(0, 'en', '', 'USERNAME_OR_EMAIL', 'general', 'Username or Email');

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
(25, 1, 2, 48, 51, 1, 1, 0, 'Социальные профили', '', 'social', 'html', '', '<p>Вы можете прикрепить к своей учетной записи социальные профили, чтобы с помощью них заходить на сайт в один клик.</p>', 'ucp', 'social', '', '', 0, 0, '', 1, 0),
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

