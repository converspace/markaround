<?php

// TODO: OL
// TODO: Multi line lists and other nested block elements
// TODO: Nested span elements
// TODO: header attributes and forced level
// TODO: code block attributes

	function markaround($str) {

		$str = str_replace("\r\n", "\n", $str);
		$str = str_replace("\r", "\n", $str);
		$str = str_replace("\t", str_repeat(' ', 4), $str);
		$str = preg_replace("/\s*\\\\\n\s*/m", ' ', $str); // collapse lines ending in \
		$lines = explode("\n", $str);
		return block_elements_parser($lines);
	}


	function block_elements_parser($lines) {

		$markaround = '';
		$stack = array();
		$header_levels = array();
		$blockquote = '';
		$state = 'START';

		foreach($lines as $line) {
			switch ($state) {
				case 'START':
					$previous_line = array_pop($stack);

					if (!trim($line)) {
						$line_before_the_previous_line = array_pop($stack);

						if (preg_match('/^-+\s*$/', $previous_line) and !trim($line_before_the_previous_line)) {
							$markaround .= "<hr />\n\n";
						}
						elseif (trim($previous_line)) {

							$previous_line = span_elements_parser($previous_line);

							if (!is_null($line_before_the_previous_line)) {
								$markaround .= "<p>$previous_line</p>\n";
							}
							else {
								$markaround .= "$previous_line<br />\n";
							}
						}

						$markaround .= "\n";
					}
					else {
						$stack = array();
						if (preg_match('/^([=\-~\.`"*+^_:#])\1{0,}$/', $line, $matches) and trim($previous_line)) {

							$level = array_search($matches[1], $header_levels);
							if (false === $level) {
								array_push($header_levels, $matches[1]);
								$level = array_search($matches[1], $header_levels);
							}
							$level = $level + 1;
							$markaround .= "<h$level>$previous_line</h$level>\n";
						}
						elseif (preg_match('/^\s*\'\'\s*$/', $line)) {
							if (trim($previous_line)) {
								$previous_line = span_elements_parser($previous_line);
								if (trim($previous_line)) $previous_line = "<p>$previous_line</p>\n";
								$markaround .= "$previous_line<pre><code>\n";
							}
							else {
								$markaround .= "<pre><code>\n";
							}
							$state = 'CODEBLOCK';
						}
						elseif (preg_match('/^\s*[*]\s+(.+)$/', $line, $matches)) {
							$previous_line = span_elements_parser($previous_line);
							if (trim($previous_line)) $previous_line = "<p>$previous_line</p>\n";
							$markaround .= "$previous_line<ul>\n<li>{$matches[1]}</li>\n";
							$state = 'UL';
						}
						elseif (preg_match('/^\s*[>]\s+(.+)$/', $line, $matches)) {
							$inside_blockquote = true;
							if (trim($previous_line)) $previous_line = "<p>$previous_line</p>\n";
							$markaround .= "$previous_line<blockquote>\n";
							$blockquote = "{$matches[1]}\n";
							$state = 'BLOCKQUOTE';
						}
						else {
							if (trim($previous_line)) {
								$previous_line = span_elements_parser($previous_line);
								$markaround .= "$previous_line<br />\n";
							}
							else {
								array_push($stack, '');
							}

							array_push($stack, $line);
						}
					}
					break;
				case 'CODEBLOCK':
					if (preg_match('/^\s*\'\'\s*$/', $line)) {
						$markaround .= "</code></pre>\n";
						$state = 'START';
					}
					else {
						$line = htmlspecialchars($line);
						$line = str_replace("\\'", "'", $line);
						$markaround .= "$line\n";
					}
					break;
				case 'UL':
					if (preg_match('/^\s*[*]\s+(.+)$/', $line, $matches)) {
						$markaround .= "<li>{$matches[1]}</li>\n";
					}
					elseif (!trim($line)) {
						$markaround .= "</ul>\n\n";
						$state = 'START';
					}
					break;
				case 'BLOCKQUOTE':
					if (preg_match('/^\s*[>]\s*(.*)$/', $line, $matches)) {
						$blockquote .= "{$matches[1]}\n";
					}
					else {
						$markaround .= block_elements_parser(explode("\n", $blockquote))."</blockquote>\n";
						$blockquote = '';
						$state = 'START';
					}
					break;
			}
		}

		$previous_line = array_pop($stack);
		$line_before_the_previous_line = array_pop($stack);

		if (trim($previous_line)) {
			$previous_line = span_elements_parser($previous_line);
			if (!is_null($line_before_the_previous_line)) {
				$markaround .= "<p>$previous_line</p>";
			}
			else {
				$markaround .= $previous_line;
			}
		}

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