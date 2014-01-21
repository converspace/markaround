<?php

// TODO: OL
// TODO: Multi line lists and other nested block elements
// TODO: header attributes
// TODO: code block attributes
// TODO: Blockquotes

	function markaround($str) {

		$str = str_replace("\r\n", "\n", $str);
		$str = str_replace("\r", "\n", $str);
		$str = str_replace("\t", str_repeat(' ', 4), $str);
		$lines = explode("\n", $str);

		return block_elements_parser($lines);
	}


	function block_elements_parser($lines) {

		$markaround = '';
		$stack = array();
		$header_levels = array();
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
								$stack = array();
							}
							else {
								$markaround .= "$previous_line<br />\n";
							}
						}

						$markaround .= "\n";
						array_push($stack, '');
					}
					else {
						if (preg_match('/^([=\-~\.`"*+^_:#])\1{0,}$/', $line, $matches) and trim($previous_line)) {

							$level = array_search($matches[1], $header_levels);
							if (false === $level) {
								array_push($header_levels, $matches[1]);
								$level = array_search($matches[1], $header_levels);
							}
							$level = $level + 1;
							$markaround .= "<h$level>$previous_line</h$level>\n";
							$stack = array();
						}
						elseif (preg_match('/^\s*\'\'\s*$/', $line)) {
							if (trim($previous_line)) {
								$previous_line = span_elements_parser($previous_line);
								$markaround .= "<p>$previous_line</p>\n<pre><code>\n";
							}
							else {
								$markaround .= "<pre><code>\n";
							}
							$state = 'CODEBLOCK';

						}
						elseif (preg_match('/^\s*[*]\s+(.+)$/', $line, $matches)) {
							$previous_line = span_elements_parser($previous_line);
							$markaround .= "<p>$previous_line</p>\n<ul>\n<li>{$matches[1]}</li>\n";
							$state = 'UL';
						}
						else {
							if (trim($previous_line)) {


								if ('\\' == substr($previous_line, -1)) {
										$line = substr($previous_line, 0, -1)."\n$line";
								}
								else {
										$previous_line = span_elements_parser($previous_line);
										$markaround .= "$previous_line<br />\n";
										$stack = array();
								}
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
						$state = 'START';
						$markaround .= "</code></pre>\n";
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
			}
		}

		$previous_line = array_pop($stack);
		if (trim($previous_line)) {
			$markaround .= "$previous_line";
		}

		return $markaround;
	}


	function span_elements_parser($str) {

		$markaround = '';
		$token = '';
		$stack = array();
		$state = 'START';

		foreach (str_split($str) as $char) {
			switch ($state) {
				case 'START':
					$previous_char = array_pop($stack);

					$non_word_char_regex = '/[^A-Za-z0-9\-_]/';

					if (('_' == $char) and preg_match($non_word_char_regex, $previous_char)) {
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
				case 'EM':
					if ('_' == $char) {
						$markaround .= "<em>$token</em>";
						$token = '';
						$state = 'START';
					}
					else $token .= $char;
					break;
				case 'STRONG':
					if ('*' == $char) {
						$markaround .= "<strong>$token</strong>";
						$token = '';
						$state = 'START';
					}
					else $token .= $char;
					break;
				case 'DEL':
					if ('-' == $char) {
						$markaround .= "<del>$token</del>";
						$token = '';
						$state = 'START';
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