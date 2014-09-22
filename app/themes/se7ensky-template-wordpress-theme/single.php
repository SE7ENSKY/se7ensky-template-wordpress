<?php

$post = buildPost();

render("layouts/page", array(
	"page" => $post
));
