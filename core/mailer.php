<?php
/**
* @package fw
* @copyright (c) 2013
*/

namespace fw\core;

/**
* Почтовик
*/
class mailer
{
	protected $failures;
	
	protected $config;
	protected $mailer;
	protected $message;
	protected $template;
	protected $transport;
	
	function __construct($config, $template)
	{
		$this->config   = $config;
		$this->template = $template;

		/**
		* TODO Настройки нужно перенести в $config
		*/
		$this->transport = \Swift_SmtpTransport::newInstance('localhost', 25);
		$this->mailer    = \Swift_Mailer::newInstance($this->transport);
		$this->message   = \Swift_Message::newInstance();
	}

	/**
	* Почтовые адреса, до которых не дошли письма
	*/
	public function get_failures()
	{
		return $this->failures;
	}

	/**
	* Отложенная отправка письма
	*/
	public function postpone($template = '', $subject = '', $content_type = 'text/html')
	{
		register_shutdown_function([$this, 'send'], $template, $subject, $content_type);
	}

	/**
	* Отправка письма
	*/
	public function send($template = '', $subject = '', $content_type = 'text/html')
	{
		$template = $template ? "email/{$template}" : "email/{$this->template->file}";
		
		if ($subject)
		{
			$this->message->setSubject($subject);
		}
		
		$this->message->setBody($this->template->render($template), $content_type);
		$this->mailer->send($this->message, $this->failures);
	}
	
	/**
	* Скрытые получатели копии письма
	*/
	public function set_bcc()
	{
		call_user_func_array([$this->message, 'setBcc'], func_get_args());
		
		return $this;
	}
	
	/**
	* Получатели копии письма
	*/
	public function set_cc()
	{
		call_user_func_array([$this->message, 'setCc'], func_get_args());
		
		return $this;
	}
	
	/**
	* Авторы письма (может быть несколько)
	*/
	public function set_from()
	{
		call_user_func_array([$this->message, 'setFrom'], func_get_args());
		
		return $this;
	}
	
	/**
	* Кому следует отвечать на письмо
	*/
	public function set_reply_to()
	{
		call_user_func_array([$this->message, 'setReplyTo'], func_get_args());
		
		return $this;
	}
	
	/**
	* Адрес, на который вернуть недошедшее письмо
	*/
	public function set_return_path()
	{
		call_user_func_array([$this->message, 'setReturnPath'], func_get_args());
		
		return $this;
	}
	
	/**
	* Отправитель письма (не более одного)
	*/
	public function set_sender()
	{
		call_user_func_array([$this->message, 'setSender'], func_get_args());
		
		return $this;
	}
	
	/**
	* Тема письма
	*/
	public function set_subject()
	{
		call_user_func_array([$this->message, 'setSubject'], func_get_args());
		
		return $this;
	}

	/**
	* Получатели письма
	*/
	public function set_to()
	{
		call_user_func_array([$this->message, 'setTo'], func_get_args());
		
		return $this;
	}
}
