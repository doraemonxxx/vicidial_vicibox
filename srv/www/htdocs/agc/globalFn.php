<?php

if (!function_exists('testEcho')) {

	function testEcho($v, $identifier)
	{
		if (is_array($v)) {
			// return echo string with print_r
			echo '<pre><b>' . $identifier . ': ' . print_r($v, true) . '</b></pre><br>';
		} else {
			// echo all paramaters
			echo '<pre><b>' . $identifier . ': ' . $v . '</b></pre><br>';
		}
	}
}
