<?php


	require 'markaround.php';
	file_put_contents('README.html', markaround(file_get_contents('README.markaround')));

?>