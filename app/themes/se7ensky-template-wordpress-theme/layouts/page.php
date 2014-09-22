<?php

render("layouts/main", array(
	"main" => function() use ($page) { ?>
		<article class="page">
			<header class="page__header">
				<?php $img = featuredImage($page['id'], '1280w') ?>
				<?php if ($img) : ?>
					<div class="page__featured-image"><img src="<?=$img?>" alt=""></div>
				<?php endif ?>
				<h1 class="page__header__title"><?=$page['title']?></h1>
			</header>
			
			<div class="page__content">
				<?=$page['content']?>
			</div>
		</article>
	<?php }
));