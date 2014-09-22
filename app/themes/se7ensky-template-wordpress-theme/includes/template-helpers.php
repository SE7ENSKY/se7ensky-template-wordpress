<?php

/*
 * tr – translation helper
 * examples:
 * tr("Hello") -> "Привіт" // simply translates given string
 * tr("%s products", $category) -> "Продукти категорії: Тунець" // translates format and uses sprintf for substitutions
 * tr(array("%d comment", "%d comments"), $commentCount) -> "1 коментар" // pluralized format version
 */

define("TR_CONTEXT", basename(get_stylesheet_directory()));

function tr() {
	$args = func_get_args();
	if (count($args) == 0) throw new ErrorException("Incorrect tr() usage.");
	$phrase = array_shift($args);

	if (is_string($phrase)) {
		$phrase = __($phrase, TR_CONTEXT);
	} elseif (is_array($phrase) && count($phrase) == 2 && count($argv) > 0) {
		$phrase = _n($phrase, TR_CONTEXT, $args[0]);
	}

	if (count($args) > 0) {
		return call_user_func_array('sprintf', array_merge(array($phrase), $args));
	} else {
		return $phrase;
	}
}

/* td – get resource url inside template directory */
function td($name) {
	return get_template_directory_uri() . "/" . $name;
}

/* asset – get asset url */
function asset($name) {
	return td("assets/$name");
	// return "http://cdn/assets/$name";
}

/* e – escape string for html output */
function e($s) {
	return htmlspecialchars($s);
}

/*
 * alt – smart alternatives for output
 * returns first suitable alternative
 * examples:
 * alt($object, "field1", "field2")
 * alt($object1, "field1", $object2, "field21", "field22", null, "default output")
 * alt($var1, $var2)
 */
function alt() {
	$i = 0;
	$argCount = func_num_args();
	$o = null;
	while ($i < $argCount) {
		$arg = func_get_arg($i);
		$i++;

		if (is_array($arg)) {
			$o = $arg;
		} elseif (is_string($arg) && is_array($o) && !empty($o[$arg])) {
			return $o[$arg];
		} elseif (is_string($arg) && is_null($o) && !empty($arg)) {
			return $arg;
		} elseif (is_null($arg)) {
			$o = null;
		}
	}
	return "";
}

/* rich – apply wp's rich content filters to passed arg */
function rich($content) {
	return apply_filters('the_content', $content);
}
