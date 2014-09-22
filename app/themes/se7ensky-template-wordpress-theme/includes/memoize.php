<?php

require_once 'caching.php';
$cache = new MemoryCacheImplementation();
// $cache = new FileCacheImplementation(dirname(__FILE__) . "/cache");
// $cache = new WPDBCacheImplementation($wpdb);

function memoize($key, $fn, $args = array()) {
	global $cache;
	$key = str_replace('/', '-', $key);
	if (defined('ICL_LANGUAGE_CODE')) $key .= "-" . ICL_LANGUAGE_CODE;
	if ($cache->exists($key)) {
		$value = $cache->get($key);
		return $value;
	} else {
		$value = call_user_func_array($fn, $args);
		$cache->set($key, $value);
		return $value;
	}
}

function memoizeOutput($key, $fn) {
	return memoize($key, function() use ($fn) {
		ob_start();
		call_user_func_array($fn);
		return ob_get_clean();
	});
}

add_action("wp_update_nav_menu", "cache_flush_nav_menus", 100, 100);
add_action("wp_changed_nav_menu", "cache_flush_nav_menus", 100, 100);
function cache_flush_nav_menus() {
	global $cache;
	$cache->flush("fetchMenuItems-");
}

add_action("save_post", "cache_flush_posts", 100, 100);
add_action("update_post", "cache_flush_posts", 100, 100);
add_action("wp_insert_post", "cache_flush_posts", 100, 100);
function cache_flush_posts() {
	global $cache;
	$cache->flush();
}

add_action('create_term', 'cache_flush_terms', 100, 100);
add_action('edit_term', 'cache_flush_terms', 100, 100);
add_action('delete_term', 'cache_flush_terms', 100, 100);
function cache_flush_terms() {
	global $cache;
	$cache->flush("buildTerms-");
}