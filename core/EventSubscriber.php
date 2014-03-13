<?php namespace fw\core;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use fw\Events\GenericEvent;
use fw\Traits\Injection;

class EventSubscriber implements EventSubscriberInterface
{
	use Injection;
	
	public static function getSubscribedEvents()
	{
		return ['openid.data.saved' => 'onOpenidDataSaved'];
	}
	
	/**
	* Пришли данные пользователя от социального сервиса
	*/
	public function onOpenidDataSaved(GenericEvent $event)
	{
		errorhandler::log_mail(print_r($event->data['json'], true) . print_r($event->data['sql_ary'], true), 'OpenID data saved');
	}
}