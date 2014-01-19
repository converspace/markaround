<?php

// TODO: bold, italics and strikethrough should only work at word boundaries
// TODO: OL
// TODO: UL and OL nested block elements
// TODO: backslash escaping
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
						$markaround .= "$prev<br />\n";
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
						$markaround .= "$line\n";
					}
					break;
			}
		}

		if ('PARA_MAYBE' == $state) {
			$prev = array_pop($stack);
			$markaround .= "$prev<br />\n";
		}

		return $markaround;
	}

	function inline_parser($str) {
		$markaround = '';
		$token = '';
		$state = 'START';

		foreach (str_split($str) as $char) {
			switch ($state) {
				case 'START':
					if ('_' == $char) {
						$state = 'EMPHASIS_START';
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
					else $markaround .= $char;
					break;
				case 'EMPHASIS_START':
					if ('_' == $char) {
						$state = 'START';
						$markaround .= "<em>$token</em>";
						$token = '';
					}
					else $token .=$char;
					break;
				case 'STRONG_START':
					if ('*' == $char) {
						$state = 'START';
						$markaround .= "<strong>$token</strong>";
						$token = '';
					}
					else $token .=$char;
					break;
				case 'DEL_START':
					if ('-' == $char) {
						$state = 'START';
						$markaround .= "<del>$token</del>";
						$token = '';
					}
					else $token .=$char;
					break;
				case 'CODE_START_MAYBE':
					if ("'" == $char) {
						$state = 'CODE_START';
					}
					else {
						$state = 'START';
						$markaround .= $char;
					}
					break;
				case 'CODE_START':
					if ("'" == $char) {
						$state = 'CODE_END_MAYBE';
					}
					else $token .= $char;
					break;
				case 'CODE_END_MAYBE':
					if ("'" == $char) {
						$state = 'START';
						$token = htmlspecialchars($token);
						$markaround .= "<code>$token</code>";
					}
					else {
						$state = 'CODE_START';
						$token .= "'".$char;
					}
					break;
			}
		}

		return $markaround;
	}

?>