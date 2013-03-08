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
	* Отправители письма
	*/
	public function set_from()
	{
		call_user_func_array([$this->message, 'setFrom'], func_get_args());
		
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
