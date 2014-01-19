Markaround
==========

Markaround is a plain text formatting syntax for writing for the web.

It is a better Markdown (at least for me) with:
* one and only one way to do something (I'm looking at you Markdown headers, lists and emphasis)
* intuitive emphasis (closer to what's used in plain text email) [^1][^2]
* intuitive line breaks (newline = line break)
* syntax for strikethrough (because you always change your mind and/or make mistakes)
* more readable header syntax
* consistent linking syntax
* code block syntax designed for easy copy-pasting in the common case


Syntax
------


Paragraphs and Line Breaks
~~~~~~~~~~~~~~~~~~~~~~~~~~

Markaround:
	''
	This is a paragraph.

	This is another paragraph. One or more blank lines end paragraphs.
	* Lists also end paragraphs.

	Newlines
	are treated as line breaks. To stop this from happening \
	precede the newline with a backslash.
	''
HTML:
	''
	<p>This is a paragraph.</p>

	<p>This is another paragraph. One or more blank lines end paragraphs.</p>
	<ul>
	<li>Lists also end paragraphs.</li>
	</ul>

	<p>Newlines<br />
	are treated as line breaks. To stop this from happening precede the newline with a backslash.</p>
	''

Bold
~~~~

Markaround: ''*bold*''

HTML: ''<strong>bold<strong>''

Output: *bold*


Italics
~~~~~~~

Markaround: ''_italics_''

HTML: ''<em>italics<em>''

Output: _italics_


Strikethrough
~~~~~~~~~~~~~

Markaround: ''-strikethrough-''

HTML: ''<del>-strikethrough<del>''

Output: -strikethrough-


Code and Code Blocks
~~~~~~~~~~~~~~~~~~~~

Double single quotes are used for both inline code and code blocks.

Markaround: ''Here is some \'\'code\'\' you can use''

HTML: ''Here is some <code>code<code> you can use''

Output: Here is some ''code'' you can use.


Double single quotes on one line by themselves mark the start and end of a clode block. If they are indented then every line in the contained text is stripped of the same number of indents.


Markaround:
	''
	Here is some code:
		\'\'{#example1 .php}
		<?php
			echo "Hello World";
		?>
		\'\'
	''
HTML:
	''
	Here is some code: <br />
	<pre id="example1" class="php"><code>
	This is a code block
	</code></pre>
	''


Fenced code blocks are better than markdown's indentation-based code-blocks because they:
* can begin and end with blank lines
* can be used immediately following a list
* allow for easy copy-pasting by not forcing indents

Double single quotes was chosen over backticks because it looks more natural as a proxy for quoting literals. The double single quotes syntax was borrowed from Dokuwiki [^dokuwiki-syntax].

Using the same syntax for inline and block code was borrowed from Creole 1.0 [^creole-nowiki]


Headers
~~~~~~~~

Any of the following characters can be used as the underline: ''= - ~ ' . ` * + ^ " _ : #''

The first six, in that order, work very well to indicate decreasing levels (see example below). The same character must be used for the same indentation level. The underline must be at least as long as the title text.

Markaround:
	''
	Level 1
	=======

	Level 2
	-------

	Level 3
	~~~~~~~

	Level 4
	'''''''{#level4 .class1 .class2}

	Level 5
	.......

	Level 6
	```````

	Another Level 2 Heading
	-----------------------

	Level 3 Heading
	~~~~~~~~~~~~~~~
	''

HTML:
	''
	<h1 id="level_1">Level 1</h1>
	<h2 id="level_2">Level 2</h2>
	<h3 id="level_3">Level 3</h3>
	<h4 id="level4" class="class1 class2">Level 4</h4>
	<h5 id="level_5">Level 5</h5>
	<h6 id="level_6">Level 6</h6>
	<h2 id="another_level_2_heading">Another Level 2 Heading</h2>
	<h3 id="another_level_3_heading">Another Level 3 Heading</h3>
	''


Horizontal Rule
~~~~~~~~~~~~~~~

4 or more hyphens on a line surounded by blank lines.

Markaround:
	''

	----

	''

HTML:
	''
	<hr />
	''


Blockquotes
~~~~~~~~~~~

Markaround:
	''
	> This is a blockquote with two paragraphs.
	>
	> This is the second paragraph.

	> Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor \
	  incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud \
	  exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute \
	  irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla \
	  pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia \
	  deserunt mollit anim id est laborum.
	>
	> Blockquotes can also contain other block level elements like headers, lists, blockquotes and code blocks.
	>
	> Header
	> ~~~~~~
	>
	> * one
	> * two
	> * three
	>
	> > This is a  blockquote-in-a-blockquote.
	>
	>
	>    This is a code block
	>
	''

HTML:
	''
	<blockquote>
	<p>This is a blockquote with two paragraphs.</p>

	<p>This is the second paragraph.</p>

	<p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.</p>

	<p>Blockquotes can also contain other block level elements like headers, lists, blockquotes and code blocks.</p>

	<h3>Header</h3>

	<ul>
	<li>one</li>
	<li>two</li>
	<li>three</li>
	<ul>

	<blockquote>
	This is a  blockquote-in-a-blockquote.
	</blockquote>

	<pre><code>
	This is a code block
	</code></pre>

	</blockquote>
	''

Lists
~~~~~

Markaround:
	''
	* List items start with a list marker followed by a space.
	* Unordered list items start with an asterisk and ordered list items start with a hash.

	* Blank lines between list items will wrap them in <p> tags.

	* List items may consist of multiple other block level elements like paragraphs, blockquotes, lists and code blocks.

	  Just indent them by the same number of spaces as the previous one.
	  The same also applies to line breaks.

	  > This is a blockquote

	  ''
	  This is a code block
	  ''

	* List items can have multiple levels. To add another level,
		* just indent a bit deeper
		   * and so on
		* items at the same level must be indented by the same number of spaces.
	* Ordered lists can include list style hints that also make them readable when you are referencing an item elsewhere.
		# default same as using a hash
			1. one
			2. two
		# lower alpha
			a. one
			b. two
			c. three
		# upper alph
			A. one
			B. two
			C. three
		# roman numerals [^roman]
			I. one
			II. two
			III. three

	''

HTML:
	''

	''




References
----------

[^1]: http://kb.mozillazine.org/Plain_text_e-mail_-_Thunderbird#Structured_Text
[^2]: http://email.about.com/od/netiquettetips/qt/et070205.htm
[^roman]: http://stackoverflow.com/questions/267399/how-do-you-match-only-valid-roman-numerals-with-a-regular-expression
[^dokuwiki-syntax]: https://www.dokuwiki.org/wiki:syntax#basic_text_formatting
[^creole-nowiki]: http://www.wikicreole.org/wiki/Creole1.0#section-Creole1.0-NowikiPreformatted