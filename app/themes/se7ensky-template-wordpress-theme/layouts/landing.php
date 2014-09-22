<?php

render("layouts/main", array(
	"main" => function() use ($posts) { ?>
		<h1>Posts:</h1>
		<ul>
			<?php foreach ($posts as $post) : ?>
				<li>
					<a href="<?=$post['url']?>" title="<?=$post['title']?>">
						<?=$post['title']?>
					</a>
				</li>
			<?php endforeach ?>
		</ul>
	<?php }
));