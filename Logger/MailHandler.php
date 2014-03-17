<?php namespace fw\Logger;

use Monolog\Logger;
use Monolog\Handler\AbstractProcessingHandler;

class MailHandler extends AbstractProcessingHandler
{
	protected $user;
	
	protected $doc_root;
    protected $headers = ['Content-type: text/plain; charset=utf-8'];

    public function __construct($user, $level = Logger::ERROR, $bubble = true)
    {
		$this->user = $user;
		
		$this->doc_root  = realpath(rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/../../') . '/';
		$this->headers[] = sprintf('From: fw@%s', gethostname());

        parent::__construct($level, $bubble);
    }

    protected function write(array $record)
    {
		if (empty($record['context']['to'])) {
			return false;
		}
		
		$emails = $record['context']['to'];
		$emails = is_array($emails) ? $emails : [$emails];
		
		$call_stack = str_replace($this->doc_root, '', get_call_stack());
        $headers    = implode("\r\n", $this->headers) . "\r\n";
		$title      = $_SERVER['SERVER_NAME'] . ($record['context']['title'] ? ' ' . $record['context']['title'] : '');
		
		if (PHP_SAPI == 'cli') {
			/* В терминале нет пользователя сайта и запроса */
			$text = sprintf("[%s]\n%s\n%s\$_SERVER => %s",
				strftime('%c'),
				$record['message'],
				$call_stack,
				print_r($_SERVER, true)
			);
		} else {
			$text = sprintf("[%s]\n%s\n%s\$app['user']->data => %s\n\$_SERVER => %s\n\$_REQUEST => %s",
				strftime('%c'),
				$record['message'],
				$call_stack,
				!empty($this->user) ? print_r($this->user->data, true) : '',
				print_r($_SERVER, true),
				print_r($_REQUEST, true)
			);
		}

        foreach ($emails as $to) {
            mail($to, $title, $text, $headers);
        }
    }
}
