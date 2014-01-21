<?php

// TODO: OL
// TODO: Multi line lists and other nested block elements
// TODO: Nested span elements
// TODO: header attributes and forced level
// TODO: code block attributes

	define('HEADER_UNDERLINE', '/^([=\-~\.`"*+^_:#])\1{0,}$/');
	define('CODEBLOCK',        '/^\s*\'\'\s*$/');
	define('UL',               '/^\s*[*]\s+(.+)$/');
	define('BLOCKQUOTE',       '/^\s*[>]\s*(.*)$/');
	define('HR',               '/^-+\s*$/');

	function is_blank($str) { return (!trim($str)); }

	function markaround($str) {

		$str = str_replace("\r\n", "\n", $str);
		$str = str_replace("\r", "\n", $str);
		$str = str_replace("\t", str_repeat(' ', 4), $str);
		$lines = explode("\n", $str);
		return block_elements_parser($lines);
	}


	function block_elements_parser($lines) {

		$markaround = '';
		$header_levels = array();
		$paragraph = '';
		$state = 'START';

		foreach($lines as $line) {

			switch ($state) {

				case 'START':
					if (is_blank($line)) {
						if (!empty($paragraph)) {
							$paragraph = span_elements_parser($paragraph);
							$markaround .= "<p>$paragraph</p>\n";
						}
						$markaround .= "\n";
						$paragraph = '';
					}
					else {
						if (preg_match(HR, $line)) {
							if (empty($paragraph)) {
								$state = 'HR';
							}
						}
					NON_BLANK_LINE:
						if (preg_match(HEADER_UNDERLINE, $line, $matches)) {
							if (!empty($paragraph)) {

								$level = array_search($matches[1], $header_levels);
								if (false === $level) {
										array_push($header_levels, $matches[1]);
										$level = array_search($matches[1], $header_levels);
								}
								$level = $level + 1;
								$markaround .= "<h$level>$paragraph</h$level>\n\n";
								$paragraph = '';
							}
						}
						elseif (preg_match(CODEBLOCK, $line, $matches)) {
							if (!empty($paragraph)) $markaround .= "<p>$paragraph</p>\n";
							$paragraph = '';
							$state = 'CODEBLOCK';
							$markaround .= "<pre><code>\n";
						}
						elseif (preg_match(BLOCKQUOTE, $line, $matches)) {
							if (!empty($paragraph)) $markaround .= "<p>$paragraph</p>\n";
							$paragraph = "{$matches[1]}";
							$state = 'BLOCKQUOTE';
							$markaround .= "<blockquote>";
						}
						elseif (preg_match(UL, $line, $matches)) {
							if (!empty($paragraph)) $markaround .= "<p>$paragraph</p>\n";
							$paragraph = '';
							$state = 'UL';
							$markaround .= "<ul><li>{$matches[1]}</li>";
						}
						else {
							if (!empty($paragraph)) {
								if ('\\' == substr($paragraph, -1)) {
									$paragraph = substr($paragraph, 0, -1)."\n$line";
								}
								else {
									$paragraph .= "<br />\n$line";
								}
							}
							else $paragraph .= $line;
						}
					}
					break;

				case 'HR':
					if (is_blank($line)) {
						$markaround .= "<hr />\n\n";
						$state = 'START';
					}
					else {
						$paragraph = '';
						$state = 'START';
						goto NON_BLANK_LINE;
					}
					break;

				case 'CODEBLOCK':
					if (preg_match(CODEBLOCK, $line)) {
						$markaround .= "</code></pre>\n";
						$state = 'START';
					}
					else {
						$line = htmlspecialchars($line);
						$line = str_replace("\\'", "'", $line);
						$markaround .= "$line\n";
					}
					break;

				case 'BLOCKQUOTE':
					if (preg_match(BLOCKQUOTE, $line, $matches)) {
						$paragraph .= "\n{$matches[1]}";
					}
					elseif ('\\' == substr($paragraph, -1)) {
						$paragraph .= "\n$line";
					}
					else {
						$paragraph = block_elements_parser(explode("\n", $paragraph));
						if ("\n" == substr($paragraph, -1)) $paragraph = substr($paragraph, 0, -1);
						$markaround .= "$paragraph</blockquote>\n";
						$paragraph = '';
						$state = 'START';
						if (is_blank($line)) {
							$markaround .= "\n";
						}
						else {
							goto NON_BLANK_LINE;
						}
					}
					break;

				case 'UL':
					if (preg_match(UL, $line, $matches)) {
						$markaround .= "\n<li>{$matches[1]}</li>";
					}
					else {
						$markaround .= "</ul>\n";
						$state = 'START';
						if (is_blank($line)) {
							$markaround .= "\n";
						}
						else {
							goto NON_BLANK_LINE;
						}
					}
					break;
			}
		}

		if (!empty($paragraph)) $markaround .= "<p>$paragraph</p>";

		return $markaround;
	}


	function span_elements_parser($str) {

		$markaround = '';
		$token = '';
		$stack = array();
		$last_state = '';
		$state = 'START';

		foreach (str_split($str) as $char) {
			switch ($state) {
				case 'START':
					$previous_char = array_pop($stack);
					if (is_null($previous_char)) $previous_char = ' ';
					$non_word_char_regex = '/[^A-Za-z0-9\-_]/';

					if ('\\' == $char) {
						$state = 'ESCAPE';
						$last_state = 'START';
					}
					elseif (('_' == $char) and preg_match($non_word_char_regex, $previous_char)) {
						$state = 'EM';
					}
					elseif (('*' == $char) and preg_match($non_word_char_regex, $previous_char)) {
						$state = 'STRONG';
					}
					elseif (('-' == $char) and preg_match($non_word_char_regex, $previous_char)) {
						$state = 'DEL';
					}
					elseif (("'" == $char) and preg_match($non_word_char_regex, $previous_char)) {
						$state = 'CODE_START_MAYBE';
					}
					else {
						$markaround .= $char;
					}
					array_push($stack, $char);
					break;
				case 'ESCAPE':
					if ('START' == $last_state) $markaround .= $char;
					else $token .= $char;
					$state = $last_state;
					break;
				case 'EM':
					if ('_' == $char) {
						$markaround .= "<em>$token</em>";
						$token = '';
						$state = 'START';
					}
					elseif ('\\' == $char) {
						$state = 'ESCAPE';
						$last_state = 'EM';
					}
					else $token .= $char;
					break;
				case 'STRONG':
					if ('*' == $char) {
						$markaround .= "<strong>$token</strong>";
						$token = '';
						$state = 'START';
					}
					elseif ('\\' == $char) {
						$state = 'ESCAPE';
						$last_state = 'STRONG';
					}
					else $token .= $char;
					break;
				case 'DEL':
					if ('-' == $char) {
						$markaround .= "<del>$token</del>";
						$token = '';
						$state = 'START';
					}
					elseif ('\\' == $char) {
						$state = 'ESCAPE';
						$last_state = 'DEL';
					}
					else $token .= $char;
					break;
				case 'CODE_START_MAYBE':
					if ("'" == $char) {
						$state = 'CODE';
					}
					else {
						$state = 'START';
						$markaround .= "'".$char;
					}
					break;
				case 'CODE':
					if ("'" == $char) {
						$state = 'CODE_END_MAYBE';
					}
					elseif ('\\' == $char) {
						$state = 'ESCAPE';
						$last_state = 'CODE';
					}
					else $token .= $char;
					break;
				case 'CODE_END_MAYBE':
					if ("'" == $char) {
						$token = htmlspecialchars($token);
						$token = str_replace("\\'", "'", $token);
						$markaround .= "<code>$token</code>";
						$token = '';
						$state = 'START';
					}
					else {
						$state = 'CODE';
						$token .= "'".$char;
					}
					break;
			}
		}

		if (in_array($state, array('EM', 'STRONG', 'DEL', 'CODE'))) {
			$tag = strtolower($state);
			if ('code' == $tag) {
				$token = htmlspecialchars($token);
				$token = str_replace("\\'", "'", $token);
			}
			$markaround .= "<$tag>$token</$tag>";
		}

		return $markaround;
	}

?>