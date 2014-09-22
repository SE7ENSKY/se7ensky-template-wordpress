<!doctype html>
<html>
	<head>
		<meta charset="UTF-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, minimum-scale=1, maximum-scale=1">
		<title><?php block($title, function(){ wp_title(''); }) ?></title>
		<?php block($fonts) ?>
		<?php block($styles) ?>
		<?php
			wp_deregister_script('jquery');
			wp_deregister_script('jquery-migrate');
			wp_enqueue_script('jquery', "//code.jquery.com/jquery-1.11.1.min.js");
		?>
		<?php wp_head() ?>
		<!--[if lt IE 9]>
			<script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
			<script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
		<![endif]-->
		<?php block($headScripts) ?>
	</head>
	<body>
		<?php block($root, function() use ($content) { echo $content; }) ?>
		<?php wp_footer() ?>
		<?php block($scripts) ?>
	</body>
</html>