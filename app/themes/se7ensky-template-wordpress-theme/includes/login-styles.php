<?php

function login_styles() { ?>
	<style type="text/css">
		@import "<?=asset('login-style.css')?>";
	</style>
<?php }
add_action('login_enqueue_scripts', 'login_styles');