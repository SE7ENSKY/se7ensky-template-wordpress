<?php


function post_template($name, $params) {
	$q = new WP_Query(array(
		'post_type' => 'email-template',
		'name' => $name
	));
	$result = false;
	if ($q->have_posts()) {
		$q->the_post();
		$title = get_the_title();
		$body = apply_filters('the_content', get_the_content());
		$result = array(
			'title' => strtr($title, $params),
			'body' => strtr($body, $params)
		);
	}
	wp_reset_postdata();
	return $result;
}

function email_template($email, $lang, $template, $data = array()) {
	if ($lang) {
		global $sitepress;
		$sitepress->switch_lang($lang);
	}
	$letter = post_template($template, $data);
	if (!$letter) {
		throw new Error("Email template '$template' not found.");
	} else if (wp_mail($email, $letter['title'], $letter['body'], array('Content-Type: text/html'))) {
		return true;
	} else {
		throw new Error("Couldn't send email message.");
	}
}