<?php

	function test_span_elements_parser() {
		should_return('<strong>bold</strong>', when_passed('*bold*'));
		should_return('<em>italics</em>', when_passed('_italics_'));
		should_return('<del>strikethrough</del>', when_passed('-strikethrough-'));
		should_return('<code>code</code>', when_passed("''code''"));

		// Escaping
		should_return('<strong>bo*ld</strong>', when_passed('*bo\*ld*'));
		should_return('<em>ital_ics</em>', when_passed('_ital\_ics_'));
		should_return('<del>strike-through</del>', when_passed('-strike\-through-'));
		should_return("<code>co''de</code>", when_passed("''co\'\'de''"));
	}

?>