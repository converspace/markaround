<?php

// TODO: OL
// TODO: Multi line lists and other nested block elements
// TOOD: <HR />
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
				case 'START';
					if (trim($line)) {
						if (preg_match('/^\s*[*]\s+(.+)$/', $line, $matches)) {
							$state = 'UL_START';
							$markaround .= "<ul>\n<li>{$matches[1]}</li>asdsadas\n";
						}
						elseif (preg_match('/^\s*\'\'\s*$/', $line)) {
							$state = 'CODEBLOCK_START';
							$markaround .= "<pre><code>\n";
						}
						else {
							$state = 'PARA_MAYBE';
							array_push($stack, $line);
						}
					}
					else {
						$state = 'START';
						$markaround .= "\n";
					}
					break;
				case 'PARA_MAYBE':
					$prev = array_pop($stack);
					if (!trim($line)) {
						$state = 'START';
						$prev = inline_parser($prev);
						$markaround .= "<p>$prev</p>\n\n";
					}
					elseif (preg_match('/^\s*[*]\s+(.+)$/', $line, $matches)) {
						$state = 'UL_START';
						$prev = inline_parser($prev);
						$markaround .= "<p>$prev</p>\n<ul>\n<li>{$matches[1]}</li>\n";
					}
					elseif (preg_match('/^\s*\'\'\s*$/', $line)) {
							$state = 'CODEBLOCK_START';
							$prev = inline_parser($prev);
							$markaround .= "<p>$prev</p>\n<pre><code>\n";
					}
					elseif (preg_match('/^(=|-|~|\.|`|"|\*|\+|^|_|:|#)\1{0,}$/', $line, $matches)) {
						$state = 'START';
						$level = array_search($matches[1], $header_levels);
						if (false === $level) {
							array_push($header_levels, $matches[1]);
							$level = array_search($matches[1], $header_levels);
						}

						$level = $level + 1;
						$markaround .= "<h$level>$prev</h$level>\n";

					}
					else {
						$prev = inline_parser($prev);
						if ('\\' == substr($prev, -1)) {
							$line = substr($prev, 0, -1)."\n$line";
						}
						else {
							$markaround .= "$prev<br />\n";
						}
						$state = 'PARA_MAYBE';
						array_push($stack, $line);
					}
					break;
				case 'UL_START':
				case 'UL_CONTINUED':
					if (preg_match('/^\s*[*]\s+(.+)$/', $line, $matches)) {
						$state = 'UL_CONTINUED';
						$markaround .= "<li>{$matches[1]}</li>\n";
					}
					elseif (!trim($line)) {
						$state = 'START';
						$markaround .= "</ul>\n\n";
					}
					break;
				case 'CODEBLOCK_START':
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
			}
		}

		if ('PARA_MAYBE' == $state) {
			$prev = array_pop($stack);
			$markaround .= ('\\' == substr($prev, -1)) ? substr($prev, 0, -1)."\n" : "$prev<br />\n";
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