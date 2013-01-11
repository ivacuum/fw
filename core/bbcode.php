<?php
/**
* @package src.ivacuum.ru
* @copyright (c) 2012 vacuum
*/

namespace engine\core;

class bbcode
{
	var $parse_smilies    = true;
	var $parse_html       = false;
	var $parse_bbcode     = true;
	var $strip_quotes     = false;
	var $parse_wordwrap   = false;
	var $parse_nl2br      = true;
	var $max_embed_quotes = 5;
}
