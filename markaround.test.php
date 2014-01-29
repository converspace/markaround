<?php

	function test_block_elements_parser() {

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

	}

	function test_span_elements_parser() {

		should_return('<strong>bold</strong>', when_passed('*bold*'));
		should_return('<em>italics</em>', when_passed('_italics_'));
		should_return('<del>strikethrough</del>', when_passed('-strikethrough-'));
		should_return('<code>code</code>', when_passed("''code''"));

		// Text styling should not work for partial words.
		should_return('shouldnot*bold*', when_passed('shouldnot*bold*'));
		// TODO: Pass this test:
		should_return('*bold*face', when_passed('*bold*face'));


		// Escaping
		should_return('<strong>bo*ld</strong>', when_passed('*bo\*ld*'));
		should_return('*bold*', when_passed('\*bold\*'));

		should_return('<em>ital_ics</em>', when_passed('_ital\_ics_'));
		should_return('_italics_', when_passed('\_italics\_'));

		should_return('<del>strike-through</del>', when_passed('-strike\-through-'));
		should_return('-strikethrough-', when_passed('\-strikethrough\-'));

		should_return("<code>co''de</code>", when_passed("''co\'\'de''"));
		should_return("''code''", when_passed("\'\'code\'\'"));

		//TODO: Why does this pass?
		should_return("''code'", when_passed("\''code\''"));

	}

?>