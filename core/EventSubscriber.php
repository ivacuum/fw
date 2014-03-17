<?php namespace fw\core;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use fw\Events\GenericEvent;
use fw\Traits\Injection;

class EventSubscriber implements EventSubscriberInterface
{
	use Injection;
	
	public static function getSubscribedEvents()
	{
		return [
			'error.404'         => 'onError404',
			'error.crit'        => 'onErrorCrit',
			'error.fatal'       => 'onErrorFatal',
			'error.notice'      => 'onErrorNotice',
			'error.page.inform' => 'onErrorPageInform',
			'error.sql'         => 'onErrorSql',
			'openid.data.saved' => 'onOpenidDataSaved',
		];
	}
	
	public function onError404(GenericEvent $event)
	{
		http_response_code(404);

		$text = $event->data['text'];

		$this->logger->error(
			"Page http://{$this->request->hostname}{$this->request->url} not found", [
				'title' => '404 Not Found',
				'to'    => $this->app['errorhandler.options']['email.404'],
			]
		);

		/* Ошибка в api-сервисе */
		// if (!$this->router->handler->format || $this->router->handler->format == 'json') {
		// 	$error = ['code' => $text];
		// 
		// 	json_output(['errors' => [$error]]);
		// 	exit;
		// }

		$this->template->assign([
			'MESSAGE_TEXT'  => isset($this->user->lang[$text]) ? $this->user->lang[$text] : $text,
			'MESSAGE_TITLE' => $this->user->lang['SITE_MESSAGE'],
		]);
		
		$this->template->file = 'message_body.html';
		$this->router->load_page_inform('404 Not Found', $text);
	}
	
	public function onErrorCrit(GenericEvent $event)
	{
		/* Service Unavailable */
		http_response_code(503);

		$this->logger->error(
			$event->data['text'], [
				'to' => $this->app['errorhandler.options']['email.error'],
			]
		);
		
		$this->template->display('error_crit.html');
	}
	
	public function onErrorFatal(GenericEvent $event)
	{
		$this->logger->error(
			"Fatal error: {$event->data['message']} on line {$event->data['line']} in file {$event->data['file']}", [
				'to' => $this->app['errorhandler.options']['email.error'],
			]
		);
	}
	
	public function onErrorNotice(GenericEvent $event)
	{
		$this->profiler->log_error($event->data['text'], $event->data['line'], $event->data['file']);
		
		$this->logger->error(
			"{$event->data['text']} on line {$event->data['line']} in file {$event->data['file']}", [
				'to' => $this->app['errorhandler.options']['email.error'],
			]
		);
	}
	
	public function onErrorPageInform(GenericEvent $event)
	{
		$text = $event->data['text'];
		
		$this->template->assign([
			'MESSAGE_TEXT'  => isset($this->user->lang[$text]) ? $this->user->lang[$text] : $text,
			'MESSAGE_TITLE' => $this->user->lang['SITE_MESSAGE'],
		]);
		
		$this->template->file = 'message_body.html';
		
		$this->router->handler->page_header()
			->page_footer();
	}
	
	public function onErrorSql(GenericEvent $event)
	{
		/* Service Unavailable */
		http_response_code(503);

		$this->logger->error(
			print_r($event->data, true), [
				'to' => $this->app['errorhandler.options']['email.error'],
			]
		);
		
		if (in_array($_SERVER['REMOTE_ADDR'], $this->app['errorhandler.options']['debug.ips'])) {
			$this->template->assign('error', $event->data);
		}
		
		$this->template->display('error_crit.html');
	}

	/**
	* Пришли данные пользователя от социального сервиса
	*/
	public function onOpenidDataSaved(GenericEvent $event)
	{
		$this->logger->error(
			print_r($event->data['json'], true) . print_r($event->data['sql_ary'], true), [
				'title' => 'OpenID data saved',
				'to'    => $this->app['errorhandler.options']['email.error'],
			]
		);
	}
}