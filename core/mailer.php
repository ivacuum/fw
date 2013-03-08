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
	protected $message;
	protected $mailer;
	protected $transport;
	
	protected $config;
	protected $template;
	
	function __construct($config, $template)
	{
		$this->config   = $config;
		$this->template = $template;
		
		$this->transport = \Swift_SmtpTransport::newInstance('localhost', 25);
		$this->mailer    = \Swift_Mailer::newInstance($this->transport);
		$this->message   = \Swift_Message::newInstance();
	}
	
	public function get_failures()
	{
		return $this->failures;
	}
	
	public function send($template = '', $subject = '', $content_type = null)
	{
		$template = $template ? "email/{$template}" : "email/{$this->template->file}";
		
		if ($subject)
		{
			$this->message->setSubject($subject);
		}
		
		$this->message->setBody($this->template->render($template));
		
		if ($content_type)
		{
			$this->message->setContentType($content_type);
		}
		
		// $this->mailer->send($message, $this->failures);
		print $this->message->toString();
	}
	
	public function set_from()
	{
		call_user_func_array([$this->message, 'setFrom'], func_get_args());
		
		return $this;
	}
	
	public function set_subject()
	{
		call_user_func_array([$this->message, 'setSubject'], func_get_args());
		
		return $this;
	}

	public function set_to()
	{
		call_user_func_array([$this->message, 'setTo'], func_get_args());
		
		return $this;
	}
}
