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

							//$previous_line = inline_parser($previous_line);

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
								//$previous_line = inline_parser($previous_line);
								$markaround .= "<p>$previous_line</p>\n<pre><code>\n";
							}
							else {
								$markaround .= "<pre><code>\n";
							}
							$state = 'CODEBLOCK';

						}
						elseif (preg_match('/^\s*[*]\s+(.+)$/', $line, $matches)) {
							//$previous_line = inline_parser($previous_line);
							$markaround .= "<p>$previous_line</p>\n<ul>\n<li>{$matches[1]}</li>\n";
							$state = 'UL';
						}
						else {
							if (trim($previous_line)) {


								if ('\\' == substr($previous_line, -1)) {
										$line = substr($previous_line, 0, -1)."\n$line";
								}
								else {
										//$previous_line = inline_parser($previous_line);
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

	function inline_parser($str) {

		$markaround = '';
		$token = '';
		$word_start_boundry = array(' ', '"', "'", '(', '{', '[');
		$word_end_boundry = array(' ', '.', ',', ';', ':', '"', "'", '?', '!', ')', '}', ']');
		$state = 'START';

		foreach (str_split($str) as $char) {
			switch ($state) {
				case 'START':
					if ('_' == $char) {
						$state = 'EM_START';
					}
					elseif ('*' == $char) {
						$state = 'STRONG_START';
					}
					elseif ('-' == $char) {
						$state = 'DEL_START';
					}
					elseif ("'" == $char) {
						$state = 'CODE_START_MAYBE';
					}
					else {
						$state = 'START';
						$markaround .= $char;
					}
					break;
				case 'NOFORMAT':
					if (in_array($char, $word_start_boundry)) {
						$state = 'START';
						$markaround .= $char;
					}
					else $markaround .= $char;
					break;
				case 'EM_START':
					if ('_' == $char) {
						$state = 'EM_END_MAYBE';
					}
					else $token .= $char;
					break;
				case 'EM_END_MAYBE':
					if (in_array($char, $word_end_boundry)) {
						$state = 'NOFORMAT';
						$token = str_replace("\\_", "_", $token);
						$markaround .= "<em>$token</em>$char";
						$token = '';
					}
					else {
						$state = 'EM_START';
						$token .= '_'.$char;
					}
					break;
				case 'STRONG_START':
					if ('*' == $char) {
						$state = 'STRONG_END_MAYBE';
					}
					else $token .= $char;
					break;
				case 'STRONG_END_MAYBE':
					if (in_array($char, $word_end_boundry)) {
						$state = 'NOFORMAT';
						$token = str_replace("\\*", "*", $token);
						$markaround .= "<strong>$token</strong>$char";
						$token = '';
					}
					else {
						$state = 'STRONG_START';
						$token .= '*'.$char;
					}
					break;
				case 'DEL_START':
					if ('-' == $char) {
						$state = 'DEL_END_MAYBE';
					}
					else $token .= $char;
					break;
				case 'DEL_END_MAYBE':
					if (in_array($char, $word_end_boundry)) {
						$state = 'NOFORMAT';
						$token = str_replace("\\-", "-", $token);
						$markaround .= "<del>$token</del>$char";
						$token = '';
					}
					else {
						$state = 'DEL_START';
						$token .= '-'.$char;
					}
					break;
				case 'CODE_START_MAYBE':
					if ("'" == $char) {
						$state = 'CODE_START';
					}
					else {
						$state = 'NOFORMAT';
						$markaround .= "'".$char;
					}
					break;
				case 'CODE_START':
					if ("'" == $char) {
						$state = 'CODE_END_START';
					}
					else $token .= $char;
					break;
				case 'CODE_END_START':
					if ("'" == $char) {
						$state = 'CODE_END_MAYBE';
					}
					else {
						$state = 'CODE_START';
						$token .= "'".$char;
					}
					break;
				case 'CODE_END_MAYBE':
					if (in_array($char, $word_end_boundry)) {
						$state = 'NOFORMAT';
						$token = htmlspecialchars($token);
						$token = str_replace("\\'", "'", $token);
						$markaround .= "<code>$token</code>$char";
						$token = '';
					}
					else {
						$state = 'CODE_START';
						$token .= "'".$char;
					}
					break;
			}
		}

		if (preg_match('/([A-Z]+)_END_MAYBE/', $state, $matches)) {
			$tag = strtolower($matches[1]);
			if ('code' == $tag) {
				$token = htmlspecialchars($token);
				$token = str_replace("\\'", "'", $token);
			}
			$markaround .= "<$tag>$token</$tag>";
		}

		return $markaround;
	}

?>