<?php

add_action('wp_head', 'ajaxurl');

function ajaxurl() { ?>

<script type="text/javascript">
var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
</script>

<?php
}