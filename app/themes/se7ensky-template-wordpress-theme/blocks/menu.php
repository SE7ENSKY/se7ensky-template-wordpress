<?php if (!function_exists("mixin_menu")) {
	function mixin_menu($prefix = "unknown", $items = array(), $addUlClasses = false, $addLiClasses = false) {
		if (is_array($items) && count($items) > 0) {
			$ulClasses = array($prefix . "-menu");
			if ($addUlClasses) $ulClasses[] = $addUlClasses;

			echo '<ul class="' . join(" ", $ulClasses) . '">';
			foreach ($items as $item) {
				$liClasses = array($prefix . "-menu__item");
				if ($item['children']) $liClasses[] = $prefix . "-menu__item-has_sub";
				if ($addLiClasses) $liClasses[] = $addLiClasses;
				if ($item['active']) $liClasses[] = "active";
				if ($item['activeParent']) $liClasses[] = "active active-parent";
				if ($item['classes']) { $liClasses = array_merge($liClasses, $item['classes']); }
				echo '<li class="' . join(" ", $liClasses) . '">';
				echo '<a href="' . $item['url'] . '" title="' . e($item['title']) . '" class="' . $prefix . '-menu__item-link">' . e($item['title']) . '</a>';
				if ($item['children']) mixin_menu($prefix . "-sub", $item['children']);
				echo '</li>';
			}
			echo '</ul>';
		}
	}
}

mixin_menu($prefix, $items, $addUlClasses, $addLiClasses);
