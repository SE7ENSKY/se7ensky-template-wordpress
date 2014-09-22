<?php

$posts = buildPosts();

render("layouts/landing", array(
	"posts" => $posts
));