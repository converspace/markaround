<?php

// TODO: Links
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
			$first_line = true;
			$state = 'PARAGRAPH';

			foreach($lines as $line) {

				switch ($state) {

					case 'PARAGRAPH':
						if (_is_blank($line)) {

							_end_previous_paragraph_($paragraph, $markaround, $first_line);
							_newline_if_not_first_line_($markaround, $first_line);
							break;
						}

						BLOCK_START:

						if (preg_match(HEADER, $line, $matches)) {
							_end_previous_paragraph_($paragraph, $markaround, $first_line);
							_newline_if_not_first_line_($markaround, $first_line);
							$level = strlen($matches[1]);
							$markaround .= "<h$level>{$matches[2]}</h$level>";
							break;
						}

						if (preg_match(HR, $line, $matches)) {
							_end_previous_paragraph_($paragraph, $markaround, $first_line);
							_newline_if_not_first_line_($markaround, $first_line);
							$markaround .= "<hr />";
							break;
						}


						if (preg_match(CODEBLOCK, $line)) {

							_end_previous_paragraph_($paragraph, $markaround, $first_line);
							_newline_if_not_first_line_($markaround, $first_line);
							$state = 'CODEBLOCK';
							$markaround .= "<pre><code>";
							break;
						}

						if (preg_match(BLOCKQUOTE, $line, $matches)) {
							_end_previous_paragraph_($paragraph, $markaround, $first_line);
							_newline_if_not_first_line_($markaround, $first_line);
							$paragraph = $matches[1];
							$state = 'BLOCKQUOTE';
							$markaround .= "<blockquote>";
							break;
						}

						if (preg_match(LIST_START, $line, $matches)) {

							_end_previous_paragraph_($paragraph, $markaround, $first_line);
							_newline_if_not_first_line_($markaround, $first_line);
							$paragraph = $matches[2];
							$state = 'LIST';
							$list_type = ('*' == $matches[1]) ? 'ul' : 'ol';
							$markaround .= "<$list_type>";
							$multiline_list_item = false;
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
							$markaround .= "\n</code></pre>";
							$state = 'PARAGRAPH';
						}
						else {
							$line = htmlspecialchars($line);
							$line = str_replace("\\'", "'", $line);
							$markaround .= "\n$line";
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
							$markaround .= "$paragraph</blockquote>";
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
							$multiline_list_item = true;
							$paragraph .= isset($matches[1]) ? "\n{$matches[1]}" : "\n";
						}
						elseif (preg_match(LIST_START, $line, $matches)) {
							$paragraph = ($multiline_list_item) ? block_elements_parser($paragraph) : $paragraph;
							$markaround .= "<li>$paragraph</li>\n";
							$multiline_list_item = false;
							$paragraph = $matches[2];
						}
						elseif (_is_blank($line)) {
							$paragraph = ($multiline_list_item) ? block_elements_parser($paragraph) : $paragraph;
							$markaround .= "<li>$paragraph</li>";
							$markaround .= "</$list_type>";
							$markaround .= "\n";
							$paragraph = '';
							$state = 'PARAGRAPH';
						}
						else {
							$paragraph = ($multiline_list_item) ? block_elements_parser($paragraph) : $paragraph;
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
				_end_previous_paragraph_($paragraph, $markaround, $first_line);
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


			function _is_blank($str) { return (!trim($str)); }


			function _end_previous_paragraph_(&$paragraph, &$markaround, &$first_line) {
				if (!empty($paragraph)) {
					$paragraph = span_elements_parser($paragraph);
					_newline_if_not_first_line_($markaround, $first_line);
					$markaround .= "<p>$paragraph</p>";
					$paragraph = '';
				}
			}


			function _newline_if_not_first_line_(&$markaround, &$first_line) {
				if (!$first_line) $markaround .= "\n";
				if ($first_line) $first_line = false;
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

?>