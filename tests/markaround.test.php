<?php

	function test_span_elements_parser() {
		should_return('<strong>bold</strong>', when_passed('*bold*'));
		should_return('<em>italics</em>', when_passed('_italics_'));
		should_return('<del>strikethrough</del>', when_passed('-strikethrough-'));
		should_return('<code>code</code>', when_passed("''code''"));
	}

?>