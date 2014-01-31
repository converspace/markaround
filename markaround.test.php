<?php

	function test__block_elements_parser() {

		// Para
		should_return("<p>Not surrounded by blank lines</p>", when_passed("Not surrounded by blank lines"));
		should_return("\n<p>Blank line before</p>", when_passed("\nBlank line before"));
		should_return("\n<p>Surrounded by blank lines</p>\n", when_passed("\nSurrounded by blank lines\n"));
		should_return("\n<p>Blank line before</p>\n\n<p>Para2</p>", when_passed("\nBlank line before\n\nPara2"));
		should_return("<p>Para1</p>\n\n<p>Para2</p>", when_passed("Para1\n\nPara2"));

		should_return("<h1>Header</h1>", when_passed("= Header"));
		should_return("<h1>Header</h1>\n<p>Paragraph</p>", when_passed("= Header\nParagraph"));


		should_return('<hr />', when_passed("-"));

		should_return("<p>para1</p>\n<ul><li><p>list item 1</p></li></ul>", when_passed("para1\n* list item 1"));

		// HTML escaping tests
		should_return("<p>&lt;strong&gt;Hello&lt;/strong&gt; World</p>", when_passed("<strong>Hello</strong> World"));
		should_return("<blockquote><p>&lt;strong&gt;Hello&lt;/strong&gt; World</p></blockquote>", when_passed("> <strong>Hello</strong> World"));
		should_return("<pre><code>\n&lt;strong&gt;Hello&lt;/strong&gt; World\n</code></pre>", when_passed("''\n<strong>Hello</strong> World\n''"));
		should_return("<pre><code>\n&lt;strong&gt;Hello&lt;/strong&gt; World\n</code></pre>\n<p>Foobar</p>", when_passed("''\n<strong>Hello</strong> World\n''\nFoobar"));
		should_return('<h1>&lt;strong&gt;Hello&lt;/strong&gt;</h1>', when_passed("= <strong>Hello</strong>"));

	}

	function test_span_elements_parser() {

		should_return('<strong>bold</strong>', when_passed('*bold*'));
		should_return('<strong>bold</strong> ', when_passed('*bold* '));
		should_return('<strong>b*old</strong>', when_passed('*b*old*'));

		should_return('<em>italics</em>', when_passed('_italics_'));
		should_return('<em>italics</em> ', when_passed('_italics_ '));
		should_return('<em>ita_lics</em>', when_passed('_ita_lics_'));

		should_return('<del>strikethrough</del>', when_passed('-strikethrough-'));
		should_return('<del>strikethrough</del> ', when_passed('-strikethrough- '));
		should_return('<del>strike-through</del>', when_passed('-strike-through-'));

		should_return('<code>code</code>', when_passed("''code''"));
		should_return('<code>code</code> ', when_passed("''code'' "));
		should_return("<code>cod'e</code>", when_passed("''cod'e''"));
		should_return("''cod''e", when_passed("''cod''e"));

		// Can be nested
		should_return('<strong><em>bold_italics</em></strong>', when_passed('*_bold_italics_*'));
		should_return('<em><strong>italics_bold</strong></em>', when_passed('_*italics_bold*_'));
		should_return('<del><strong>del_bold</strong></del>', when_passed('-*del_bold*-'));
		should_return('<em><del>italics_del</del></em>', when_passed('_-italics_del-_'));

		should_return('<strong>across words</strong>', when_passed('*across words*'));
		should_return('<strong>across <em>words</em></strong>', when_passed('*across _words_*'));
		should_return('<strong>delete <del>word</del></strong>', when_passed('*delete -word-*'));

		should_return('<strong><code>boldcode</code></strong>', when_passed("*''boldcode''*"));


		// Cannot be joined without having a non-word char in between
		should_return('*bold*_italics_', when_passed('*bold*_italics_'));
		should_return('_italics_*foo*', when_passed('_italics_*foo*'));
		should_return('_del_*foo*', when_passed('_del_*foo*'));

		should_return('<strong>strong**foo</strong>', when_passed('*strong**foo*'));
		should_return('<em>italics__foo</em>', when_passed('_italics__foo_'));
		should_return('<del>del--foo</del>', when_passed('-del--foo-'));
		should_return('(<strong>foo</strong>)', when_passed('(*foo*)'));


		// Text styling should not work for partial words.
		should_return('shouldnot*bold*', when_passed('shouldnot*bold*'));
		should_return('*bold*face', when_passed('*bold*face'));
		should_return('some*bold*face', when_passed('some*bold*face'));
		should_return('*notbold', when_passed('*notbold'));
		should_return("''notcode", when_passed("''notcode"));


		// Escaping
		should_return('<strong>bo*ld</strong>', when_passed('*bo\*ld*'));
		should_return('*bold*', when_passed('\*bold\*'));

		should_return('<em>ital_ics</em>', when_passed('_ital\_ics_'));
		should_return('_italics_', when_passed('\_italics\_'));

		should_return('<del>strike-through</del>', when_passed('-strike\-through-'));
		should_return('-strikethrough-', when_passed('\-strikethrough\-'));

		should_return("<code>co''de</code>", when_passed("''co\'\'de''"));
		should_return("''code''", when_passed("\'\'code\'\'"));


		should_return("''code''", when_passed("\''code\''"));

	}

?>