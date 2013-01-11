<?php
/**
* @package src.ivacuum.ru
* @copyright (c) 2012
*/

namespace engine\template;

/**
* Класс обработчика шаблонов
*/
class template
{
	/**
	* Глобальные переменные класса
	*/
	var $cachepath, $filename, $path;
	var $block_names = array();
	var $block_else_level = array();
	var $re = array();
	var $vars = array('.' => array());

	/**
	* Конструктор
	* Устанавливает путь к папке с шаблонами (в т.ч. и для acp)
	*/
	function __construct()
	{
		global $site_root_path;

		if( defined('IN_ACP') )
		{
			/**
			* Пути для админки
			*/
			global $acp_root_path;

			if( file_exists($acp_root_path . 'templates') )
			{
				$this->cachepath = $acp_root_path . 'templates/cache';
				$this->path = $acp_root_path . 'templates';
			}
			else
			{
				trigger_error('$template->template(): Templates folder does not exist', E_USER_ERROR);
			}
		}
		else
		{
			/**
			* Пути к сайту
			*/
			if( file_exists($site_root_path . 'templates') )
			{
				$this->cachepath = $site_root_path . 'engine/cache/templates';
				$this->path = $site_root_path . 'templates';
			}
			else
			{
				trigger_error('$template->template(): Templates folder does not exist', E_USER_ERROR);
			}
		}

		$this->cachepath = str_replace('//', '/', $this->cachepath);
		$this->path = str_replace('//', '/', $this->path);

		/* Строки в двойных и одинарных кавычках */
		$this->re['qstr'] = '(?:"[^"\\\\]*(?:\\\\.[^"\\\\]*)*"|\'[^\'\\\\]*(?:\\\\.[^\'\\\\]*)*\')';

		/* Модификаторы */
		$this->re['mod'] = '(?:\|\w+(?::(?:\w+|' . $this->re['qstr'] .'))*)';

		return true;
	}

	/**
	* From Smarty
	*/
	function _parse_is_expr($is_arg, $tokens)
	{
		$expr_end = 0;
		$negate_expr = false;

		if( ( $first_token = array_shift($tokens) ) == 'not' )
		{
			$negate_expr = true;
			$expr_type = array_shift($tokens);
		}
		else
		{
			$expr_type = $first_token;
		}

		switch( $expr_type )
		{
			case 'even':
				if( @$tokens[$expr_end] == 'by' )
				{
					$expr_end++;
					$expr_arg = $tokens[$expr_end++];
					$expr = "!( ( $is_arg / $expr_arg ) % $expr_arg )";
				}
				else
				{
					$expr = "!( $is_arg & 1 )";
				}
			break;

			case 'odd':
				if( @$tokens[$expr_end] == 'by' )
				{
					$expr_end++;
					$expr_arg = $tokens[$expr_end++];
					$expr = "( ( $is_arg / $expr_arg ) % $expr_arg )";
				}
				else
				{
					$expr = "( $is_arg & 1 )";
				}
			break;

			case 'div':
				if( @$tokens[$expr_end] == 'by' )
				{
					$expr_end++;
					$expr_arg = $tokens[$expr_end++];
					$expr = "!( $is_arg % $expr_arg )";
				}
			break;

			case 'set':
				$expr = "( isset($is_arg) )";
			break;
		}

		if( $negate_expr )
		{
			$expr = "!( $expr )";
		}

		array_splice($tokens, 0, $expr_end, $expr);

		return $tokens;
	}

	/**
	* Компилим циклы
	*/
	function compile_cycle($tag_args)
	{
		$no_nesting = false;

		/* Вызов цикла в цикле */
		if( strpos($tag_args, '!') === 0 )
		{
			// Count the number if ! occurrences (not allowed in vars)
			$no_nesting = substr_count($tag_args, '!');
			$tag_args = substr($tag_args, $no_nesting);
		}

		// Allow for control of looping (indexes start from zero):
		// foo(2)    : Will start the loop on the 3rd entry
		// foo(-2)   : Will start the loop two entries from the end
		// foo(3,4)  : Will start the loop on the fourth entry and end it on the fifth
		// foo(3,-4) : Will start the loop on the fourth entry and end it four from last
		if( preg_match('#^([^()]*)\(([\-\d]+)(?:,([\-\d]+))?\)$#', $tag_args, $match) )
		{
			$tag_args = $match[1];

			if( $match[2] < 0 )
			{
				$loop_start = '( $_' . $tag_args . '_count ' . $match[2] . ' < 0 ? 0 : $_' . $tag_args . '_count ' . $match[2] . ' )';
			}
			else
			{
				$loop_start = '( $_' . $tag_args . '_count < ' . $match[2] . ' ? $_' . $tag_args . '_count : ' . $match[2] . ' )';
			}

			if( strlen($match[3]) < 1 || $match[3] == -1 )
			{
				$loop_end = '$_' . $tag_args . '_count';
			}
			elseif( $match[3] >= 0 )
			{
				$loop_end = '( ' . ( $match[3] + 1 ) . ' > $_' . $tag_args . '_count ? $_' . $tag_args . '_count : ' . ($match[3] + 1) . ' )';
			}
			else //if ($match[3] < -1)
			{
				$loop_end = '$_' . $tag_args . '_count' . ( $match[3] + 1 );
			}
		}
		else
		{
			$loop_start = 0;
			$loop_end = '$_' . $tag_args . '_count';
		}

		$tag_template_php = '';
		array_push($this->block_names, $tag_args);

		if( $no_nesting !== false )
		{
			// We need to implode $no_nesting times from the end...
			$block = array_slice($this->block_names, -$no_nesting);
		}
		else
		{
			$block = $this->block_names;
		}

		if( sizeof($block) < 2 )
		{
			// Block is not nested.
			$tag_template_php = '$_' . $tag_args . "_count = ( isset(\$this->vars['$tag_args']) ) ? sizeof(\$this->vars['$tag_args']) : 0;";
			$varref = "\$this->vars['$tag_args']";
		}
		else
		{
			// This block is nested.
			// Generate a namespace string for this block.
			$namespace = implode('.', $block);

			// Get a reference to the data array for this block that depends on the
			// current indices of all parent blocks.
			$varref = $this->cycle_vars_name($namespace, false);

			// Create the for loop code to iterate over this block.
			$tag_template_php = '$_' . $tag_args . '_count = ( isset(' . $varref . ') ) ? sizeof(' . $varref . ') : 0;';
		}

		$tag_template_php .= 'if( $_' . $tag_args . '_count ) {';

		/**
		* The following uses foreach for iteration instead of a for loop, foreach is faster but requires PHP to make a copy of the contents of the array which uses more memory
		* <code>
		*	if (!$offset)
		*	{
		*		$tag_template_php .= 'foreach (' . $varref . ' as $_' . $tag_args . '_i => $_' . $tag_args . '_val){';
		*	}
		* </code>
		*/

		$tag_template_php .= 'for( $_' . $tag_args . '_i = ' . $loop_start . '; $_' . $tag_args . '_i < ' . $loop_end . '; ++$_' . $tag_args . '_i ) {';
		$tag_template_php .= '$_'. $tag_args . '_val = &' . $varref . '[$_'. $tag_args. '_i];';

		return $tag_template_php;
	}

	/**
	* Ссылка на используемый цикл
	*/
	function compile_cycle_vars($namespace, $varname, $echo = true, $modifiers_string = '')
	{
		/* Удаляем последнюю точку */
		$namespace = substr($namespace, 0, -1);

		/* Название цикла и ссылка на его переменные */
		$varref = $this->cycle_vars_name($namespace, true);

		$new = $this->compile_modifiers($modifiers_string);

		/* Выводим ссылку */
		$varref .= "['$varname']";
		$varref = ( $echo ) ? "<?php echo {$new['begin']}$varref{$new['end']}; ?>" : ( ( isset($varref) ) ? $varref : '' );

		return $varref;
	}

	/**
	* Компилим условие
	* Код из Smarty
	*/
	function compile_if($tag_args, $elseif)
	{
		/**
		* Обработка языковых переменных
		*/
		if( strpos($tag_args, '{L_') !== false )
		{
			$tag_args = preg_replace('#\{L_([a-z0-9\-_]*)\}#is', "(( isset(\$this->vars['.']['L_\\1']) ) ? \$this->vars['.']['L_\\1'] : ( ( isset(\$user->lang['\\1']) ) ? \$user->lang['\\1'] : '\\1'))", $tag_args);
		}

		/* Обычные переменные */
		//$tag_args = preg_replace('#\{([a-z0-9\-_]*)\}#is', "(( isset(\$this->vars['.']['\\1']) ) ? \$this->vars['.']['\\1'] : '')", $tag_args);

		// Tokenize args for 'if' tag.
		preg_match_all('/(?:
			"[^"\\\\]*(?:\\\\.[^"\\\\]*)*"         |
			\'[^\'\\\\]*(?:\\\\.[^\'\\\\]*)*\'     |
			[(),]                                  |
			[^\s(),]+)/x', $tag_args, $match);

		$tokens = $match[0];
		$is_arg_stack = array();
		//print_r($tokens);

		for( $i = 0, $size = sizeof($tokens); $i < $size; $i++ )
		{
			$token = &$tokens[$i];

			switch( $token )
			{
				case '!==':
				case '===':
				case '<<':
				case '>>':
				case '|':
				case '^':
				case '&':
				case '~':
				case ')':
				case ',':
				case '+':
				case '-':
				case '*':
				case '/':
				case '@':
				break;

				case '==':
				case 'eq':
					$token = '==';
				break;

				case '!=':
				case '<>':
				case 'ne':
				case 'neq':
					$token = '!=';
				break;

				case '<':
				case 'lt':
					$token = '<';
				break;

				case '<=':
				case 'le':
				case 'lte':
					$token = '<=';
				break;

				case '>':
				case 'gt':
					$token = '>';
				break;

				case '>=':
				case 'ge':
				case 'gte':
					$token = '>=';
				break;

				case '&&':
				case 'and':
					$token = '&&';
				break;

				case '||':
				case 'or':
					$token = '||';
				break;

				case '!':
				case 'not':
					$token = '!';
				break;

				case '%':
				case 'mod':
					$token = '%';
				break;

				case '(':
					array_push($is_arg_stack, $i);
				break;

				case 'is':
					$is_arg_start = ( $tokens[$i-1] == ')' ) ? array_pop($is_arg_stack) : $i-1;
					$is_arg	= implode('	', array_slice($tokens,	$is_arg_start, $i -	$is_arg_start));

					$new_tokens	= $this->_parse_is_expr($is_arg, array_slice($tokens, $i+1));

					array_splice($tokens, $is_arg_start, sizeof($tokens), $new_tokens);

					$i = $is_arg_start;

				// no break

				default:
					if( preg_match('#^((?:[a-z0-9\-_]+\.)+)?(\$)?(?=[A-Z])([A-Z0-9\-_]+)#s', $token, $varrefs) )
					{
						$token = ( !empty($varrefs[1]) ) ? $this->cycle_vars_name(substr($varrefs[1], 0, -1), true) . '[\'' . $varrefs[3] . '\']' : (( $size === 1 ) ? 'isset($this->vars[\'.\'][\'' . $varrefs[3] . '\']) && $this->vars[\'.\'][\'' . $varrefs[3] . '\']' : '$this->vars[\'.\'][\'' . $varrefs[3] . '\']');
					}
					elseif( preg_match('#^\.((?:[a-z0-9\-_]+\.?)+)$#s', $token, $varrefs) )
					{
						// Allow checking if loops are set with .loopname
						// It is also possible to check the loop count by doing <!-- IF .loopname > 1 --> for example
						$blocks = explode('.', $varrefs[1]);

						// If the block is nested, we have a reference that we can grab.
						// If the block is not nested, we just go and grab the block from _tpldata
						if( sizeof($blocks) > 1 )
						{
							$block = array_pop($blocks);
							$namespace = implode('.', $blocks);
							$varref = $this->cycle_vars_name($namespace, true);

							// Add the block reference for the last child.
							$varref .= "['" . $block . "']";
						}
						else
						{
							$varref = '$this->vars';

							// Add the block reference for the last child.
							$varref .= "['" . $blocks[0] . "']";
						}

						$token = "isset($varref) && sizeof($varref)";
					}
					elseif( !empty($token) )
					{
						$token = '(' . $token . ')';
					}

				break;
			}
		}

		return ( ( $elseif ) ? '} elseif( ' : 'if( ') . (implode(' ', $tokens) . ' ) { ' );
	}

	/**
	* Подключение файлов
	*/
	function compile_include($tag_args)
	{
		return "\$this->go('$tag_args');";
	}

	/**
	* Подстановка значений переменных вместо имён
	*/
	function compile_vars(&$text_blocks)
	{
		$match = array();

		/* Циклические переменные */
		preg_match_all('#\{((?:[a-z0-9\-_]+\.)+)([A-Z0-9\-_]+)(' . $this->re['mod'] . '*)\}#', $text_blocks, $match, PREG_SET_ORDER);

		foreach( $match as $vars )
		{
			$namespace   = $vars[1];
			$varname     = $vars[2];
			$new         = $this->compile_cycle_vars($namespace, $varname, true, $vars[3]);

			$text_blocks = str_replace($vars[0], $new, $text_blocks);
		}

		/**
		* Обработка обычных и языковых переменных
		*/
		preg_match_all('#\{([a-z\d\-_]*)(' . $this->re['mod'] . '*)\}#is', $text_blocks, $match, PREG_SET_ORDER);

		foreach( $match as $vars )
		{
			if( $vars[2] )
			{
				/**
				* Применение модификатора к значению переменной
				*
				* {var|modifier|modifier2:param1:'param2'}
				* {S_LANGUAGE|upper}
				*/
				$new = $this->compile_modifiers($vars[2]);

				if( strpos($vars[1], 'L_') === 0 )
				{
					/* Языковые переменные */
					$vars[1] = substr($vars[1], 2);

					$text_blocks = str_replace($vars[0], "<?php echo ( isset(\$this->vars['.']['L_$vars[1]']) ) ? {$new['begin']}\$this->vars['.']['L_$vars[1]']{$new['end']} : ( ( isset(\$user->lang['$vars[1]']) ) ? {$new['begin']}\$user->lang['$vars[1]']{$new['end']} : '$vars[1]'); ?>", $text_blocks);
				}
				else
				{
					/* Обычные переменные */
					$text_blocks = str_replace($vars[0], "<?php echo ( isset(\$this->vars['.']['$vars[1]']) ) ? {$new['begin']}\$this->vars['.']['$vars[1]']{$new['end']} : ''; ?>", $text_blocks);
				}
			}
			else
			{
				if( strpos($vars[1], 'L_') === 0 )
				{
					/* Простое значение языковой переменной */
					$vars[1] = substr($vars[1], 2);

					$text_blocks = str_replace($vars[0], "<?php echo ( isset(\$this->vars['.']['L_$vars[1]']) ) ? \$this->vars['.']['L_$vars[1]'] : ( ( isset(\$user->lang['$vars[1]']) ) ? \$user->lang['$vars[1]'] : '$vars[1]'); ?>", $text_blocks);
				}
				else
				{
					/* Простое значение обычной переменной */
					$text_blocks = str_replace($vars[0], "<?php echo ( isset(\$this->vars['.']['$vars[1]']) ) ? \$this->vars['.']['$vars[1]'] : ''; ?>", $text_blocks);
				}
			}
		}

		return;
	}

	/**
	* Модификаторы значения переменной
	*/
	function compile_modifiers($modifiers_string)
	{
		$result = array(
			'begin' => '',
			'end'   => ''
		);

		if( !$modifiers_string )
		{
			return $result;
		}

		/* Поиск модификаторов */
		preg_match_all('#\|(@?\w+)((?>:(?:'. $this->re['qstr'] . '|[^|]+))*)#', '|' . $modifiers_string, $match);
		list(, $modifiers, $modifiers_arg_strings) = $match;

		for( $i = 0, $len = sizeof($modifiers); $i < $len; $i++ )
		{
			$mod = $modifiers[$i];

			/* Поиск аргументов модификатора */
			preg_match_all('#:(' . $this->re['qstr'] . '|[^:]+)#', $modifiers_arg_strings[$i], $match);

			$modifier_args = $match[1];

			if( is_callable(array($this, 'compile_modifier_' . $mod)) )
			{
				/* Исполнение модификатора */
				$new = $this->{'compile_modifier_' . $mod}($modifier_args);

				$result['begin'] = $new['begin'] . $result['begin'];
				$result['end'] = $result['end'] . ( (strpos($new['end'], ')') !== 0) ? ', ' : '') . $new['end'];
			}
		}

		return $result;
	}

	/**
	* Перевод первой буквы каждого слова в верхний регистр
	*/
	function compile_modifier_capitalize($args)
	{
		return array(
			'begin' => 'ucwords(',
			'end'   => ')'
		);
	}

	function compile_modifier_escape($args)
	{
		return array(
			'begin' => 'escape(',
			'end'   => implode(', ', $args) . ')'
		);
	}

	/**
	* Перевод в нижний регистр
	*/
	function compile_modifier_lower($args)
	{
		return array(
			'begin' => 'mb_strtolower(',
			'end'   => ')'
		);
	}

	/**
	* Перевод строк становится <br />
	*/
	function compile_modifier_nl2br($args)
	{
		return array(
			'begin' => 'nl2br(',
			'end'   => ')'
		);
	}

	/**
	* Вывод части строки
	*/
	function compile_modifier_truncate($args)
	{
		return array(
			'begin' => 'truncate(',
			'end'   => implode(', ', $args) . ')'
		);
	}

	/**
	* Перевод в верхний регистр
	*/
	function compile_modifier_upper($args)
	{
		return array(
			'begin' => 'mb_strtoupper(',
			'end'   => ')'
		);
	}

	/**
	* Массив циклических переменных
	*/
	function cycle_vars($cycle_name, $vars_array)
	{
		if( strpos($cycle_name, '.') !== false )
		{
			/**
			* Цикл в цикле
			*/
			$cycles       = explode('.', $cycle_name);
			$cycles_count = sizeof($cycles) - 1;

			$str = &$this->vars;

			for( $i = 0; $i < $cycles_count; $i++ )
			{
				$str = &$str[$cycles[$i]];
				$str = &$str[sizeof($str) - 1];
			}

			/**
			* Количество элементов в цикле
			*/
			$s_row_count = isset($str[$cycles[$cycles_count]]) ? sizeof($str[$cycles[$cycles_count]]) : 0;
			$vars_array['S_ROW_COUNT'] = $s_row_count;

			if( !$s_row_count )
			{
				/* Установка первой строки */
				$vars_array['S_FIRST_ROW'] = true;
			}

			/**
			* Установка последней строки
			*/
			$vars_array['S_LAST_ROW'] = true;
			if( $s_row_count > 0 )
			{
				unset($str[$cycles[$cycles_count]][($s_row_count - 1)]['S_LAST_ROW']);
			}

			/* Вставка данных */
			$str[$cycles[$cycles_count]][] = $vars_array;
		}
		else
		{
			/**
			* Обычный цикл
			*/
			$s_row_count = (isset($this->vars[$cycle_name])) ? sizeof($this->vars[$cycle_name]) : 0;
			$vars_array['S_ROW_COUNT'] = $s_row_count;

			if( !$s_row_count )
			{
				/* Установка первой строки */
				$vars_array['S_FIRST_ROW'] = true;
			}

			/**
			* Установка последней строки
			*/
			$vars_array['S_LAST_ROW'] = true;
			if( $s_row_count > 0 )
			{
				unset($this->vars[$cycle_name][($s_row_count - 1)]['S_LAST_ROW']);
			}

			/* Вставка данных */
			$this->vars[$cycle_name][] = $vars_array;
		}

		return true;
	}

	/**
	* Ссылка на циклические переменные
	*/
	function cycle_vars_name($blockname, $include_last_iterator)
	{
		$blocks     = explode('.', $blockname);
		$blockcount = sizeof($blocks) - 1;

		if( $include_last_iterator )
		{
			return '$_'. $blocks[$blockcount] . '_val';
		}
		else
		{
			return '$_'. $blocks[$blockcount - 1] . '_val[\''. $blocks[$blockcount]. '\']';
		}
	}

	/**
	* Обработка и вывод шаблона
	*/
	function go($file = '', $agressive_cache = false, $include = false)
	{
		$file = empty($file) ? $this->file : $file;

		if( empty($file) )
		{
			trigger_error('$template->go() : File not specified', E_USER_ERROR);
		}
		else
		{
			/* Если возможно, то используем кэшированный шаблон */
			if( file_exists($this->cachepath . '/' . $file) && filemtime($this->cachepath . '/' . $file) > filemtime($this->path . '/' . $file) )
			{
				if( $include )
				{
					return trim(@file_get_contents($this->cachepath . '/' . $file));
				}

				global $user;

				include($this->cachepath . '/' . $file);
				return true;
			}

			$filepath = $this->path . '/' . $file;

			/* Проверяем наличие файла */
			if( !file_exists($filepath) )
			{
				trigger_error('$template->go() : File <b>' . $file . '</b> not found', E_USER_ERROR);
			}
			elseif( filesize($filepath) === 0 )
			{
				trigger_error('$template->go() : File <b>' . $file . '</b> is empty', E_USER_ERROR);
			}
		}

		$code = trim(@file_get_contents($filepath));

		/**
		* Подключения файлов
		*/
		preg_match_all('#<!-- include ([a-zA-Z0-9\_\-\+\./]+) -->#', $code, $matches);
		$include_blocks = $matches[1];
		$code = preg_replace('#<!-- include [a-zA-Z0-9\_\-\+\./]+ -->#', '<!-- include -->', $code);

		preg_match_all('#<!-- ([^<].*?) (.*?)? ?-->#', $code, $blocks, PREG_SET_ORDER);

		$text_blocks = preg_split('#<!-- [^<].*? (?:.*?)? ?-->#', $code);

		for( $i = 0, $j = sizeof($text_blocks); $i < $j; $i++ )
		{
			$this->compile_vars($text_blocks[$i]);
		}
		$compile_blocks = array();

		for( $curr_tb = 0, $tb_size = sizeof($blocks); $curr_tb < $tb_size; $curr_tb++ )
		{
			$block_val = &$blocks[$curr_tb];

			switch( $block_val[1] )
			{
				/**
				* Начало цикла
				*/
				case 'cycle':

					$this->block_else_level[] = false;
					$compile_blocks[] = '<?php ' . $this->compile_cycle($block_val[2]) . ' ?>';

				break;

				/**
				* Если цикл пустой
				*/
				case 'cyclelse':

					$this->block_else_level[sizeof($this->block_else_level) - 1] = true;
					$compile_blocks[] = '<?php }} else { ?>';

				break;

				/**
				* Конец цикла
				*/
				case 'endcycle':

					array_pop($this->block_names);
					$compile_blocks[] = '<?php ' . ((array_pop($this->block_else_level)) ? '}' : '}}') . ' ?>';

				break;

				/**
				* Условный оператор "если"
				*/
				case 'if':

					$compile_blocks[] = '<?php ' . $this->compile_if($block_val[2], false) . ' ?>';

				break;

				/**
				* Условный оператор "иначе"
				*/
				case 'else':

					$compile_blocks[] = '<?php } else { ?>';

				break;

				/**
				* Условный оператор "иначе если"
				*/
				case 'elseif':

					$compile_blocks[] = '<?php ' . $this->compile_if($block_val[2], true) . ' ?>';

				break;

				/**
				* Конец условного оператора
				*/
				case 'endif':

					$compile_blocks[] = '<?php } ?>';

				break;

				/**
				* Подключение файла
				*/
				case 'include':

					$temp = array_shift($include_blocks);

					if( $agressive_cache )
					{
						$compile_blocks[] = $this->go($temp, true, true);
					}
					else
					{
						$compile_blocks[] = '<?php ' . $this->compile_include($temp) . ' ?>';
					}

				break;

				/**
				* Обычные переменные
				*/
				default:

					$this->compile_vars($block_val[0]);
					$trim_check = trim($block_val[0]);
					$compile_blocks[] = ( !empty($trim_check) ) ? $block_val[0] : '';

				break;
			}
		}

		$template_php = '';

		for( $i = 0, $size = sizeof($text_blocks); $i < $size; $i++ )
		{
			$trim_check_text = trim($text_blocks[$i]);

			if( $trim_check_text != '' )
			{
				if( isset($compile_blocks[$i]) )
				{
					$template_php .= sprintf('%s%s', rtrim($text_blocks[$i], "\t"), $compile_blocks[$i]);
				}
				else
				{
					$template_php .= sprintf('%s', rtrim($text_blocks[$i], "\t"));
				}
			}
			else
			{
				if( isset($compile_blocks[$i]) )
				{
					$template_php .= sprintf('%s', $compile_blocks[$i]);
				}
			}
		}

		// There will be a number of occasions where we switch into and out of
		// PHP mode instantaneously. Rather than "burden" the parser with this
		// we'll strip out such occurences, minimising such switching
		$template_php = str_replace(' ?><?php ', ' ', $template_php);

		global $user;

		/* Кэширование обработанного шаблона */
		if( substr_count($file, '/') > 0 )
		{
			/**
			* games/left4dead/page_header.html =>
			*
			* $dirs = games/left4dead
			* $file = page_header.html
			*/
			$dir      = explode('/', $file);
			$filename = array_pop($dir);
			$path     = implode('/', $dir);

			if( !file_exists($this->cachepath . '/' . $path) )
			{
				mkdir($this->cachepath . '/' . $path, 0777, true);
			}
		}

		if( $fp = fopen($this->cachepath . '/' . $file, 'wb') )
		{
			flock($fp, LOCK_EX);
			fwrite($fp, $template_php);
			flock($fp, LOCK_UN);
			fclose($fp);

			chmod($this->cachepath . '/' . $file, 0666);
		}

		if( $include )
		{
			return $template_php;
		}

		eval(' ?>' . $template_php . '<?php ');

		return;
	}

	/**
	* Одиночная переменная
	*/
	function setvar($key, $value)
	{
		$this->vars['.'][$key] = $value;

		return true;
	}

	/**
	* Массив переменных
	*/
	function vars($data)
	{
		foreach( $data as $name => $value )
		{
			$this->vars['.'][$name] = $value;
		}

		return true;
	}
}
