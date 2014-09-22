<?php render("layouts/root", array(
	"fonts" => function() { ?>
	
	<?php },
	"styles" => function() { ?>
		<link rel="stylesheet" href="<?=get_stylesheet_uri()?>" type="text/css" media="screen">
	<?php },
	"scripts" => function() { ?>

	<?php },
	"root" => function() use ($main, $content) { ?>
		<?php render("blocks/header") ?>

		<div class="main container" role="main">
			<?php block($main, $content) ?>
		</div>

		<?php render("blocks/footer") ?>

	<?php }
));
