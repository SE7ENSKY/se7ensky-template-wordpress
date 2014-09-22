<header class="header container text-center" role="banner">

	<?php if (is_front_page()) : ?>
		<h1 class="logo">
			<?php bloginfo('name') ?>
			<small><?php bloginfo('description') ?></small>
		</h1>
	<?php else : ?>
		<a class="logo" href="<?php bloginfo('url') ?>">
			<?php bloginfo('name') ?>
			<small><?php bloginfo('description') ?></small>
		</a>
	<?php endif ?>

	<nav role="navigation">
		<?php render("blocks/menu", array(
			"items" => fetchMenuItems("mainMenu"),
			"prefix" => "main"
		)) ?>

		<?php render("blocks/menu", array(
			"items" => fetchLangsAsMenuItems(),
			"prefix" => "langs"
		)) ?>
	</nav>

</header>