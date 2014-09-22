<?php

error_reporting(E_ALL & ~E_NOTICE);

function functions_filename($name) {
	return dirname(__FILE__) . "/includes/$name.php";
}

if (file_exists(functions_filename("memoize"))) {
	require_once functions_filename("memoize");
}

// register menus
if (file_exists(functions_filename("menus"))) {
	add_action('init', 'register_menus');
	function register_menus() {
		add_theme_support("menus");
		require_once functions_filename("menus");
	}
}

// include all includes
$files = glob(functions_filename("*"));
foreach ($files as $file) {
	require_once $file;
}
