<?php namespace fw\Events;

use Symfony\Component\EventDispatcher\Event;

class GenericEvent extends Event
{
	public $data = [];
	
	function __construct(array $data)
	{
		$this->data = $data;
	}	
}
