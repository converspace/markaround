<?php

	ftt_include_files();
	ftt_call_test_functions();


	function should_return($expected_return_value, $params=NULL, $msg=NULL)
	{
		$debug_backtrace = debug_backtrace();
		$function = function_being_tested($debug_backtrace[1]['function']);

		if (!$function) return trigger_error("should_return() can only be called inside a test function");
		if (!function_exists($function)) return trigger_error("Function $function does not exist");

		$returned_value = call_user_func_array($function, $params);
		ftt_incr('assertions');

		$is_expectation_met = ($returned_value === $expected_return_value);
		ftt_assertions(ftt_assertion($function, $expected_return_value, $returned_value, $params, $msg, $debug_backtrace));

		if (!$is_expectation_met)
		{
			ftt_incr('failures');
			ftt_failures(ftt_assertion($function, $expected_return_value, $returned_value, $params, $msg, $debug_backtrace));
		}

		return $is_expectation_met;
	}

		function ftt_assertion($function, $expected_return_value, $returned_value, $params, $msg, $debug_backtrace)
		{
			return array
			(
				'function' => $function,
				'location' => assertion_location($debug_backtrace),
				'message' => ftt_assert_description($function, $expected_return_value, $returned_value, $params, $msg)
			);
		}


	function when_passed()
	{
		return func_get_args();
	}



		function function_being_tested($test_function)
		{
			return preg_replace('/^test_/', '', $test_function);
		}

		function ftt_assert_description($function, $expected_return_value, $returned_value, $passed_arguments, $msg)
		{
			$is_expectation_met = ($returned_value === $expected_return_value);
			if ($is_expectation_met)
			{
				$function_call = "$function".'('.ftt_array_to_argument_list($passed_arguments).')';
				//TODO: reason for %1\$s instead of %s: the $f in $function_call kicks in argument swaping in sprintf :(
				$msg = is_null($msg) ? sprintf("$function_call returns <em>%1\$s</em>", var_export($returned_value, true)) : $msg;
			}
			else
			{
				$function_call = $function.'('.ftt_array_to_argument_list($passed_arguments).')';
				//TODO: reason for %1\$s instead of %s: the $f in $function_call kicks in argument swaping in sprintf :(
				$msg = is_null($msg) ? sprintf("$function_call should have returned %1\$s but was %2\$s", var_export($expected_return_value, true), var_export($returned_value, true)) : $msg;
			}

			return $msg;
		}

			function ftt_array_to_argument_list($arguments)
			{
				$argument_list = '';

				if (is_array($arguments))
				{
					$arguments = array_map('ftt_variable_to_string', $arguments);
					$argument_list = implode(', ', $arguments);
				}

				return $argument_list;
			}
				function ftt_variable_to_string($argument)
				{
					return var_export($argument, true);
				}

		function ftt_assertions($assertion=NULL)
		{
			static $assertions=array();
			if (is_null($assertion)) return $assertions;

			$assertions[$assertion['function']][] = array('location' => $assertion['location'],
			                                              'message' => $assertion['message']);
			return $assertions;

		}

		function ftt_failures($assertion=NULL)
		{
			static $assertions;

			$assertions = isset($assertions) ? $assertions : array();
			if (is_null($assertion)) return $assertions;

			$assertions[$assertion['function']][] = array('location' => $assertion['location'],
			                                              'message' => $assertion['message']);
			return $assertions;
		}

		function assertion_location($debug_backtrace)
		{
			return array('file'=>$debug_backtrace[0]['file'], 'line'=>$debug_backtrace[0]['line']);
		}



		function ftt_include_files()
		{
			foreach (ftt_test_files() as $test_file)
			{
				if ($source_file = ftt_source_file($test_file))
				{
					include_once $source_file;
					ftt_incr('source_files');
					ftt_source_files($source_file);
				}

				include_once $test_file;
				ftt_incr('test_files');
			}
		}

			function ftt_test_files()
			{
				return ftt_globr(dirname(__FILE__), '*.test.php');
			}
				function ftt_globr($dir, $pattern)
				{
					$files = glob($dir.DIRECTORY_SEPARATOR.$pattern);
					foreach (glob($dir.DIRECTORY_SEPARATOR.'*', GLOB_ONLYDIR) as $dirname)
					{
						$files = array_merge($files, ftt_globr($dirname, $pattern));
					}

					return $files;
				}

			function ftt_source_file($test_file)
			{
				$source_file = file_being_tested($test_file);

				if (file_exists($source_file))
				{
					return $source_file;
				}
				else
				{
					$source_file = preg_replace('{'.addslashes(DIRECTORY_SEPARATOR.'tests'.DIRECTORY_SEPARATOR).'}',
												DIRECTORY_SEPARATOR,
												$source_file);
					if (file_exists($source_file)) return $source_file;
				}

				return false;
			}
				function file_being_tested($test_file)
				{
					return preg_replace('/\.test\.php$/', '.php', $test_file);
				}

			function ftt_incr($counter_name)
			{
				return ftt_counter($counter_name, true);
			}

				function ftt_counter($counter_name, $increment=false)
				{
					static $counters;
					if (!isset($counters[$counter_name])) $counters[$counter_name] = 0;
					if ($increment) $counters[$counter_name]++;
					return $counters[$counter_name];
				}

		function ftt_source_files($source_file=NULL)
		{
			static $source_files=array();
			if (is_null($source_file)) return $source_files;
			$source_files[] = $source_file;
			return $source_files;
		}

		function ftt_call_test_functions()
		{
			ftt_source_coverage('start');

			foreach (ftt_test_functions() as $test_function)
			{
				ftt_incr('tests');
				$test_function();
			}

			ftt_source_coverage('stop');
		}
			function ftt_test_functions()
			{
				$all_defined_functions = get_defined_functions();
				$user_defined_functions = $all_defined_functions['user'];
				return array_filter($user_defined_functions, 'restest_is_test_function');
			}
				function restest_is_test_function($function)
				{
					return preg_match('/^test_.*/', $function);
				}

			function ftt_source_coverage($op='')
			{
				static $source_coverage=array();

				if ('start' == $op and function_exists('xdebug_start_code_coverage'))
				{
					xdebug_start_code_coverage(XDEBUG_CC_UNUSED | XDEBUG_CC_DEAD_CODE);
				}

				if ('stop' == $op and function_exists('xdebug_get_code_coverage'))
				{
					$source_coverage = xdebug_get_code_coverage();
					$source_coverage = ftt_filter_code_coverage($source_coverage);
				}

				return $source_coverage;
			}

				function ftt_filter_code_coverage($source_coverage)
				{
					//TODO: rename files created in tests to testfoo... so that they can be filtered out
					foreach ($source_coverage as $file=>$file_coverage)
					{
						if ((substr($file, -9) == '.test.php') or
						    (substr($file, -10) == 'retest.php') or
						    (substr($file, -15) == 'retest.test.php'))
							unset($source_coverage[$file]);
						else $source_coverage[$file] = array_filter($file_coverage, 'ftt_is_negative_value');
					}

					foreach ($source_coverage as $file=>$file_coverage)
					{
						if (file_exists($file))
						{
							$source = file($file);
							foreach ($file_coverage as $line_number=>$value)
							{
								if ((trim($source[($line_number-1)]) == '}') or (trim($source[($line_number-1)]) == '{'))
									unset($source_coverage[$file][$line_number]);
								else $source_coverage[$file][$line_number] = $source[($line_number-1)];
							}
						}
					}

					$source_coverage = array_filter($source_coverage, create_function('$val', 'return !empty($val);'));

					return $source_coverage;
				}
					function ftt_is_negative_value($val)
					{
						return $val < 0;
					}

			function ftt_count_of_untested_lines($source_coverage)
			{
				return array_reduce($source_coverage, 'ftt_accumulator', 0);
			}
				function ftt_accumulator($counter, $value)
				{
					return $counter += count($value);
				}

?>


<?php //HTML helper

	function ftt_meta_refresh($seconds=NULL)
	{
		if (!is_null($seconds))
		{
			return '<meta http-equiv="Refresh" content="'.$seconds.'; url=retest.php?refresh_in='.$seconds.'" />';
		}
	}

	function ftt_no_tests()
	{
		return (count(ftt_test_functions()) == 0);
	}


	function ftt_status_red()
	{
		return (ftt_counter('failures') > 0);
	}


	function ftt_red_or_green_bar()
	{
		return (ftt_status_red()) ? "red-bar" : "green-bar";
	}

	function ftt_pluralize($str, $no)
	{
		return (1 !== $no) ? $str.'s' : $str;
	}

	function ftt_test_filter($test)
	{
		$test = preg_replace('/([A-Z])/', ' \1', $test);
		$test = preg_replace('/^test_/', '', $test);
		$test = strtolower($test);
		return $test;
//		return strtr(htmlentities($test), array('&shy;'=>'-'));
	}


	if (ftt_no_tests()) echo "Write a test!\n";
	elseif (ftt_status_red()) echo "Some tests failed.\n";
	else echo "All tests passed.\n";


	if (ftt_counter('failures') > 0)
	{
		foreach (ftt_failures() as $function=>$details)
		{
			foreach ($details as $detail)
			{
				echo "{$detail['message']} [in {$detail['location']['file']} on line {$detail['location']['line']}]\n";
			}
		}
	}
?>