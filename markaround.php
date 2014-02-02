<?php

// TODO: Links
// TODO: line break (<br />) handling inside blockquotes and lists
// TODO: handle indented code blocks
// TODO: trim spaces on newline escape
// TODO: generate header id
// TODO: header attributes {#level4 .class1 .class2}
// TODO: code block attributes {#example1 .php} => <pre id="example1" class="php">
// TODO: images and vid embeds
// TODO: ad hoc span elements


	define('HEADER',         '/^(={1,6})\s+(.+)$/');
	define('CODEBLOCK',      '/^\s*\'\'\s*$/');
	define('LIST_START',     '/^([*#])\s(.+)$/');
	define('LIST_CONTINUED', '/^\|\s*(.*)$/');
	define('BLOCKQUOTE',     '/^\s*[>]\s*(.*)$/');
	define('HR',             '/^-+\s*$/');
	define('WORD_CHAR',  '/[A-Za-z0-9\-_*]/');


	function markaround($str) {
		$str = str_replace("\r\n", "\n", $str);
		$str = str_replace("\r", "\n", $str);
		$str = str_replace("\t", str_repeat(' ', 4), $str);
		return _block_elements_parser($str);
	}

		function _block_elements_parser($str) {

			$lines = explode("\n", $str);

			$markaround = '';
			$paragraph = '';
			$state = 'PARAGRAPH';
			$first_line = true;

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
							$header = htmlspecialchars($matches[2]);
							$markaround .= "<h$level>$header</h$level>";
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
							$markaround .= "<pre><code>";
							$state = 'CODEBLOCK';
							break;
						}

						if (preg_match(BLOCKQUOTE, $line, $matches)) {
							_end_previous_paragraph_($paragraph, $markaround, $first_line);
							_newline_if_not_first_line_($markaround, $first_line);
							$markaround .= "<blockquote>";
							$state = 'BLOCKQUOTE';
							$paragraph = $matches[1];
							break;
						}

						if (preg_match(LIST_START, $line, $matches)) {

							_end_previous_paragraph_($paragraph, $markaround, $first_line);
							_newline_if_not_first_line_($markaround, $first_line);
							$list_type = ('*' == $matches[1]) ? 'ul' : 'ol';
							$markaround .= "<$list_type>";
							$state = 'LIST';
							$paragraph = $matches[2];
							$multiline_list_item = false;
							break;
						}


						$line = htmlspecialchars($line);
						if (empty($paragraph)) {
							$paragraph = $line;
						}
						elseif ('\\' == substr($paragraph, -1)) {
							$paragraph = substr($paragraph, 0, -1)."\n$line";
						}
						else $paragraph .= "<br />\n$line";
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
							$paragraph = _block_elements_parser($paragraph);
							$markaround .= "$paragraph</blockquote>";
							$paragraph = '';
							$state = 'PARAGRAPH';
							if (_is_blank($line)) $markaround .= "\n";
							else goto BLOCK_START;
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
							$paragraph = ($multiline_list_item) ? _block_elements_parser($paragraph) : $paragraph;
							$markaround .= "<li>$paragraph</li>\n";
							$multiline_list_item = false;
							$paragraph = $matches[2];
						}
						else {
							$paragraph = ($multiline_list_item) ? _block_elements_parser($paragraph) : $paragraph;
							$markaround .= "<li>$paragraph</li>";
							$markaround .= "</$list_type>";
							$paragraph = '';
							$state = 'PARAGRAPH';
							if (_is_blank($line)) $markaround .= "\n";
							else goto BLOCK_START;
						}
						break;
				}
			}

			if (('PARAGRAPH' == $state) and !empty($paragraph)) {
				_end_previous_paragraph_($paragraph, $markaround, $first_line);
			}
			if (('CODEBLOCK' == $state)) {
				$markaround .= "</code></pre>";
			}
			elseif (('BLOCKQUOTE' == $state)) {
				$paragraph = _block_elements_parser($paragraph);
				$markaround .= "$paragraph</blockquote>";
			}
			elseif (('LIST' == $state)) {
				$paragraph = _block_elements_parser($paragraph);
				$markaround .= "<li>$paragraph</li>";
				$markaround .= "</$list_type>";
			}

			return $markaround;
		}


			function _is_blank($str) { return (!trim($str)); }


			function _end_previous_paragraph_(&$paragraph, &$markaround, &$first_line) {
				if (!empty($paragraph)) {
					$paragraph = _span_elements_parser($paragraph);
					_newline_if_not_first_line_($markaround, $first_line);
					$markaround .= "<p>$paragraph</p>";
					$paragraph = '';
				}
			}


			function _newline_if_not_first_line_(&$markaround, &$first_line) {
				if (!$first_line) $markaround .= "\n";
				if ($first_line) $first_line = false;
			}


			function _span_elements_parser($str) {
				$markaround = '';
				$token = '';
				$last_state = '';
				$state = 'NOT_IN_WORD';

				foreach (str_split($str) as $char) {
					switch ($state) {

						case 'NOT_IN_WORD':

							if ('\\' == $char) {
								$state = 'ESCAPE';
								$last_state = 'IN_WORD';
								break;
							}

							if ('_' == $char) {
								$token = $char;
								$state = 'EM_MAYBE';
							}
							elseif ('*' == $char) {
								$token = $char;
								$state = 'STRONG_MAYBE';
							}
							elseif ('-' == $char) {
								$token = $char;
								$state = 'DEL_MAYBE';
							}
							elseif ("'" == $char) {
								$token = $char;
								$state = 'CODE_START_MAYBE';
							}
							elseif (preg_match(WORD_CHAR, $char)) {
								$markaround .= $char;
								$state = 'IN_WORD';
							}
							else {
								$markaround .= $char;
							}
							break;

						case 'IN_WORD':

							if ('\\' == $char) {
								$state = 'ESCAPE';
								$last_state = 'IN_WORD';
								break;
							}

							if (!preg_match(WORD_CHAR, $char)) {
								$state = 'NOT_IN_WORD';
							}
							$markaround .= $char;
							break;

						case 'EM_MAYBE':
							$token .= $char;
							if ('\\' == $char) {
								$state = 'ESCAPE';
								$last_state = 'EM_MAYBE';
								break;
							}
							if ('_' == $char) $state = 'EM';
							break;
						case 'EM':
							if (!preg_match(WORD_CHAR, $char)) {
								$token = substr($token, 1, -1);
								$token = _span_elements_parser($token);
								$markaround .= "<em>$token</em>";
								$markaround .= $char;
								$token = '';
								$state = 'NOT_IN_WORD';
							}
							else {
								$token .= $char;
								$state = 'EM_MAYBE';

							}
							break;

						case 'STRONG_MAYBE':
							$token .= $char;
							if ('\\' == $char) {
								$state = 'ESCAPE';
								$last_state = 'STRONG_MAYBE';
								break;
							}
							if ('*' == $char) $state = 'STRONG';
							break;
						case 'STRONG':
							if (!preg_match(WORD_CHAR, $char)) {
								$token = substr($token, 1, -1);
								$token = _span_elements_parser($token);
								$markaround .= "<strong>$token</strong>";
								$markaround .= $char;
								$token = '';
								$state = 'NOT_IN_WORD';
							}
							else {
								$token .= $char;
								$state = 'STRONG_MAYBE';
							}
							break;

						case 'DEL_MAYBE':
							$token .= $char;
							if ('\\' == $char) {
								$state = 'ESCAPE';
								$last_state = 'DEL_MAYBE';
								break;
							}
							if ('-' == $char) $state = 'DEL';
							break;
						case 'DEL':
							if (!preg_match(WORD_CHAR, $char)) {
								$token = substr($token, 1, -1);
								$token = _span_elements_parser($token);
								$markaround .= "<del>$token</del>";
								$markaround .= $char;
								$token = '';
								$state = 'NOT_IN_WORD';
							}
							else {
								$token .= $char;
								$state = 'DEL_MAYBE';
							}
							break;

						case 'CODE_START_MAYBE':
							if ("'" == $char) {
								$state = 'CODE_MAYBE';
								$token .= $char;
							}
							elseif (preg_match(WORD_CHAR, $char)) {
								$markaround .= $char;
								$state = 'IN_WORD';
							}
							else {
								$markaround .= $char;
								$state = 'NOT_IN_WORD';
							}
							break;
						case 'CODE_MAYBE':
							if ('\\' == $char) {
								$state = 'ESCAPE';
								$last_state = 'CODE_MAYBE';
								break;
							}
							$token .= $char;
							if ("'" == $char) $state = 'CODE_END_MAYBE';
							break;
						case 'CODE_END_MAYBE':
							$token .= $char;
							if ("'" == $char) $state = 'CODE';
							else $state = 'CODE_MAYBE';
							break;
						case 'CODE':
							if (!preg_match(WORD_CHAR, $char)) {
								$token = substr($token, 2, -2);
								$markaround .= "<code>$token</code>";
								$markaround .= $char;
								$token = '';
								$state = 'NOT_IN_WORD';
							}
							else {
								$markaround .= $token;
								$markaround .= $char;
								$token = '';
								$state = 'IN_WORD';
							}
							break;

						case 'ESCAPE':
							if (('NOT_IN_WORD' == $last_state) or ('IN_WORD' == $last_state)) $markaround .= $char;
							else $token .= $char;
							$state = $last_state;
							break;
					}
				}

				if (in_array($state, array('EM', 'STRONG', 'DEL', 'CODE'))) {
					$tag = strtolower($state);
					if ('CODE' == $state) {
						$token = substr($token, 2, -2);
					}
					else {
						$token = substr($token, 1, -1);
						$token = _span_elements_parser($token);
					}
					$markaround .= "<$tag>$token</$tag>";
				}
				else $markaround .= $token;

				return $markaround;
			}

?>