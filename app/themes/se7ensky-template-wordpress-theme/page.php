<?php

$page = buildPost();

render("layouts/page", array(
	"page" => $page
));