<?php

render("layouts/main", array(
	"main" => function() use ($code, $message) { ?>
		<h1>Error <?=$code?>: <?=$message?></h1>
	<?php }
));