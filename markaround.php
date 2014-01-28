<?php

// TODO: Links
// TODO: single line list items shouldn't be wrapped in a <p> tag.
// TODO: line break (<br />) handling inside blockquotes
// TODO: handle indented code blocks
// TODO: trim spaces on newline escape
// TODO: Nested span elements like  *_bold italics_*
// TODO: generate header id
// TODO: header attributes {#level4 .class1 .class2}
// TODO: code block attributes {#example1 .php} => <pre id="example1" class="php">
// TODO: images and vid embeds
// TODO: ad hoc span elements


	define('HEADER',         '/^(=+)\s+(.+)$/');
	define('CODEBLOCK',      '/^\s*\'\'\s*$/');
	define('LIST_START',     '/^([*#])\s(.+)$/');
	define('LIST_CONTINUED', '/^\|\s*(.*)$/');
	define('BLOCKQUOTE',     '/^\s*[>]\s*(.*)$/');
	define('HR',             '/^-+\s*$/');


	function markaround($str) {
		$str = str_replace("\r\n", "\n", $str);
		$str = str_replace("\r", "\n", $str);
		$str = str_replace("\t", str_repeat(' ', 4), $str);
		return block_elements_parser($str);
	}


	function block_elements_parser($str) {

		$lines = explode("\n", $str);

		$markaround = '';
		$paragraph = '';
		$state = 'PARAGRAPH';

		foreach($lines as $line) {

			switch ($state) {

				case 'PARAGRAPH':
					if (_is_blank($line)) {
						if (!empty($paragraph)) {
							$paragraph = span_elements_parser($paragraph);
							$markaround .= "<p>$paragraph</p>\n";
						}
						$markaround .= "\n";
						$paragraph = '';
						break;
					}

					BLOCK_START:

					if (preg_match(HEADER, $line, $matches)) {

						// All block level elements will have this since they end the previous paragraph.
						if (!empty($paragraph)) {
							$paragraph = span_elements_parser($paragraph);
							$markaround .= "<p>$paragraph</p>\n";
						}

						$level = strlen($matches[1]);
						$markaround .= "<h$level>{$matches[2]}</h$level>\n";
						$paragraph = '';
						break;
					}

					if (preg_match(HR, $line, $matches)) {

						if (!empty($paragraph)) {
							$paragraph = span_elements_parser($paragraph);
							$markaround .= "<p>$paragraph</p>\n";
						}

						$markaround .= "<hr />\n";
						$paragraph = '';
						break;
					}


					if (preg_match(CODEBLOCK, $line)) {

						if (!empty($paragraph)) {
							$paragraph = span_elements_parser($paragraph);
							$markaround .= "<p>$paragraph</p>\n";
						}

						$paragraph = '';
						$state = 'CODEBLOCK';
						$markaround .= "<pre><code>\n";
						break;
					}

					if (preg_match(BLOCKQUOTE, $line, $matches)) {
						if (!empty($paragraph)) {
							$paragraph = span_elements_parser($paragraph);
							$markaround .= "<p>$paragraph</p>\n";
						}

						$paragraph = $matches[1];
						$state = 'BLOCKQUOTE';
						$markaround .= "<blockquote>";
						break;
					}

					if (preg_match(LIST_START, $line, $matches)) {

						if (!empty($paragraph)) {
							$paragraph = span_elements_parser($paragraph);
							$markaround .= "<p>$paragraph</p>\n";
						}

						$paragraph = $matches[2];
						$state = 'LIST';
						$list_type = ('*' == $matches[1]) ? 'ul' : 'ol';
						$markaround .= "<$list_type>";
						break;
					}

					$line = htmlspecialchars($line);

					if (empty($paragraph)) {
						$paragraph = $line;
						break;
					}

					if ('\\' == substr($paragraph, -1)) {
						$paragraph = substr($paragraph, 0, -1)."\n$line";
						break;
					}

					$paragraph .= "<br />\n$line";
					break;


				case 'CODEBLOCK':
					if (preg_match(CODEBLOCK, $line)) {
						$markaround .= "</code></pre>\n";
						$state = 'PARAGRAPH';
					}
					else {
						$line = htmlspecialchars($line);
						$line = str_replace("\\'", "'", $line);
						$markaround .= "$line\n";
					}
					break;

				case 'BLOCKQUOTE':
					if ('\\' == substr($paragraph, -1)) {
						$paragraph .= "\n$line";
					}
					elseif (preg_match(BLOCKQUOTE, $line, $matches)) {
						$paragraph .= "\n{$matches[1]}";
					}
					else {
						$paragraph = block_elements_parser($paragraph);
						if ("\n" == substr($paragraph, -1)) $paragraph = substr($paragraph, 0, -1);
						$markaround .= "$paragraph</blockquote>\n";
						$paragraph = '';
						$state = 'PARAGRAPH';
						if (_is_blank($line)) {
							$markaround .= "\n";
						}
						else {
							goto BLOCK_START;
						}
					}
					break;

				case 'LIST':
					if ('\\' == substr($paragraph, -1)) {
						$paragraph .= "\n$line";
					}
					elseif (preg_match(LIST_CONTINUED, $line, $matches)) {
						$paragraph .= isset($matches[1]) ? "\n{$matches[1]}" : "\n";
					}
					elseif (preg_match(LIST_START, $line, $matches)) {
						$paragraph = block_elements_parser($paragraph);
						$markaround .= "<li>$paragraph</li>\n";
						$paragraph = $matches[2];
					}
					elseif (_is_blank($line)) {
						$paragraph = block_elements_parser($paragraph);
						$markaround .= "<li>$paragraph</li>";
						$markaround .= "</$list_type>\n";
						$markaround .= "\n";
						$paragraph = '';
						$state = 'PARAGRAPH';
					}
					else {
						$paragraph = block_elements_parser($paragraph);
						$markaround .= "<li>$paragraph</li>";
						$markaround .= "</$list_type>";
						$paragraph = '';
						$state = 'PARAGRAPH';
						goto BLOCK_START;
					}
					break;
			}
		}

		if (('PARAGRAPH' == $state) and !empty($paragraph)) {
			$paragraph = span_elements_parser($paragraph);
			$markaround .= "<p>$paragraph</p>";
		}
		if (('CODEBLOCK' == $state) and !empty($paragraph)) {
			$markaround .= "</code></pre>";
		}
		elseif (('BLOCKQUOTE' == $state) and !empty($paragraph)) {
			$paragraph = block_elements_parser($paragraph);
			$markaround .= "$paragraph</blockquote>";
		}
		elseif (('LIST' == $state) and !empty($paragraph)) {
			$paragraph = block_elements_parser($paragraph);
			$markaround .= "<li>$paragraph</li>";
			$markaround .= "</$list_type>";
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
				$token = str_replace("\\'", "'", $token);
			}
			$markaround .= "<$tag>$token</$tag>";
		}

		return $markaround;
	}


		function _is_blank($str) { return (!trim($str)); }

?>