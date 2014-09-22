<?php

function register_ajax_template($template) {
	$handler = function() use ($template) {
		render('ajax-' . $template);
		exit;
	};
	add_action( 'wp_ajax_nopriv_' . $template, $handler );
	add_action( 'wp_ajax_' . $template, $handler );
}

function ajax_template_helper() { ?>
	<script>
		$(function(){
			$("[data-ajax-template]").each(function(){
				var loadUrl = ajaxurl + "?action=" + $(this).data("ajax-template");
				$(this).addClass("loading").load(loadUrl, function() {
					$(this).removeClass("loading")
				})
			})
		})
	</script>
<?php }
add_action("wp_footer", "ajax_template_helper");

register_ajax_template("frontpage-indexes");
register_ajax_template("frontpage-opayz-indexes");
register_ajax_template("submit-contact-form");
register_ajax_template("submit-company-request");
register_ajax_template("submit-company-review");
