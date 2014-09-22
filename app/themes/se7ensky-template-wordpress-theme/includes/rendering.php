<?php

/*
 * render – render template with passed locals, supports caching
 */
function render($name, $locals = null, $cacheable = false) {
	$filename = dirname(dirname(__FILE__)) . "/$name.php";
	if (file_exists($filename)) {
		if ($cacheable) {
			$key = "render-$name-" . md5(serialize($locals));
			echo memoize($key, function() use ($filename, $locals) {
				ob_start();
				if (is_array($locals)) {
					extract($locals);
					require $filename;
				} else {
					require $filename;
				}
				return ob_get_clean();
			});
		} else {
			if (is_array($locals)) {
				extract($locals);
				require $filename;
			} else {
				require $filename;
			}
		}
	} else {
		echo "[template $name not found]";
	}
}

/*
 * block – render first usable block from args, accepts strings and functions
 */
function block() {
	$args = func_get_args();
	foreach ($args as $arg) {
		if (empty($arg)) continue;
		else {
			if (is_string($arg) || is_numeric($arg))
				echo e($arg);
			elseif (is_callable($arg))
				$arg();
			else echo "Unindentified block() function arg.";
			break;
		} 
	}
}
