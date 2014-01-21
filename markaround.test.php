<?php

	function test_block_elements_parser() {

		should_return("\n<hr />\n\n", when_passed(array('', '-', '')));
		should_return("<hr />\n\n", when_passed(array('-', '')));

		should_return("<h1>HEADER</h1>\n\n", when_passed(array('HEADER', '-')));
		should_return("<h1>HEADER</h1>\n\n\n", when_passed(array('HEADER', '-', '')));
		should_return("<h1>HEADER</h1>\n\n<p>PARA</p>", when_passed(array('HEADER', '-', 'PARA')));
		should_return("<h1>HEADER<br />\nCONTINUED</h1>\n\n<p>PARA</p>", when_passed(array('HEADER', 'CONTINUED', '-', 'PARA')));
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