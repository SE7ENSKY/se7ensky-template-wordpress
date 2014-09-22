<?php

/* getCurrentUrl – returns current rendering full URL */
function getCurrentUrl() {
	return get_bloginfo('url') . $_SERVER["REQUEST_URI"];
}

/* isActive – return if passed object (post or taxonomy) is currently queried object */
function isActive($o) {
	if (isset($o['id'])) {
		$q = get_queried_object();
		return $o['id'] == $q->ID || $o['id'] == $q->term_id;
	}
	return false;
}

function isActiveParent($o) {
	if (isset($o['id'])) {
		$q = get_queried_object();
		if ($q->post_type == 'post' && $o['type'] == 'page' && $o['slug'] == 'blog') {
			return true;
		}
	}
	return false;
}

/*
 * fetchLangsAsMenuItems
 */
function fetchLangsAsMenuItems() {
	if (function_exists('icl_get_languages')) {
		$langs = icl_get_languages("skip_missing=N&orderby=KEY&order=DIR&link_empty_to=str");
		// url, active, country_flag_url, native_name
		foreach ($langs as &$lang) {
			$lang['title'] = $lang['native_name'];
		}
		return $langs;
	} else {
		return null;
	}
}

/*
 * fetchMenuItems
 */
function fetchMenuItems($name, $opts = array()) {
	$key = "fetchMenuItems-$name-" . md5(serialize($opts));
	$wpMenuItems = memoize($key, function() use ($name) {
		$locations = get_nav_menu_locations();
		if (isset($locations[$name])) {
			$menu = wp_get_nav_menu_object($locations[$name]);
			return wp_get_nav_menu_items($menu->term_id);
		}
	});
	$o = get_queried_object();
	$currentUrl = getCurrentUrl();
	$items = _collectMenuItemsByParent($wpMenuItems, 0, $o, $currentUrl, $opts);
	if (isset($opts['startDepth'])) {
		$items = _limitStartDepth($items, $opts['startDepth']);
	}
	if (isset($opts['depth'])) {
		$items = _limitDepth($items, $opts['depth']);
	}
	return $items;
}

$date_translate_table = array(
	'Январь' => 'января',
	'Февраль' => 'февраля',
	'Март' => 'марта',
	'Апрель' => 'апреля',
	'Май' => 'мая',
	'Июнь' => 'июня',
	'Июль' => 'июля',
	'Август' => 'августа',
	'Сентябрь' => 'сентября',
	'Ноябрь' => 'ноября',
	'Октябрь' => 'октября',
	'Декабрь' => 'декабря',

	'Січень' => 'січня',
	'Лютий' => 'лютого',
	'Березень' => 'березня',
	'Квітень' => 'квітня',
	'Травень' => 'травня',
	'Червень' => 'червня',
	'Липень' => 'липня',
	'Серпень' => 'серпня',
	'Вересень' => 'вересня',
	'Жовтень' => 'жовтня',
	'Листопад' => 'листопада',
	'Грудень' => 'грудня',
);

/* findBySlug */
function findBySlug($slug, $type = "page") {
	$o = buildPost(array("type" => $type, "slug" => $slug));
	return $o;
}

/* slug2url */
function slug2url($slug, $type = "page") {
	$o = findBySlug($slug, $type);
	return $o['url'];
}

/* buildPost */
function buildPost($q = false, $cacheable = false) {
	$fn = function() use ($q) {
		global $post;
		global $page;
		global $date_translate_table;
		if ($q) {
			$query = queryPosts($q);
			if (!$query->have_posts()) return null;
			$oldPost = $post;
			$oldPage = $page;
			$query->the_post();
			$o = $post;
			$o->post_date_formatted = get_the_date();
			$post = $oldPost;
			$page = $oldPage;
		} else {
			$o = $post;
			$o->post_date_formatted = get_the_date();
		}

		$result = array(
			"id" => $o->ID,
			"type" => $o->post_type,
			"order" => $o->menu_order,
			"date" => strtr($o->post_date_formatted, $date_translate_table),
			"timestamp" => $o->post_date,
			"slug" => $o->post_name,
			"url" => get_permalink($o->ID),
			"title" => $o->post_title,
			"content" => rich($o->post_content)
		);
		$meta = get_post_meta($o->ID);
		foreach ($meta as $k => $v) {
			if (count($v) == 1) $v = $v[0];
			if ($k == '_wp_page_template') $result['template'] = $v;
			else if ($k[0] == '_') continue;
			else $result[str_replace('wpcf-', '', $k)] = $v;
		}
		return $result;
	};
	if ($cacheable) {
		global $cache;
		$key = "post-" . md5(serialize($q));
		return memoize($key, function() use ($fn) {
			return $fn();
		});
	} else {
		return $fn();
	}
}

function queryPosts($q = false) {
	global $wp_query;
	if ($q) $q['posts_per_page'] = -1;
	if (!$q) return $wp_query;

	$translate = array(
		"id" => "p",
		"type" => "post_type",
		"slug" => "name",
		"limit" => "posts_per_page",
		"term" => function(&$q, $term) {
			$q[$term['type']] = $term['slug'];
		},
		"parent" => function(&$q, $post) {
			$q['post_parent'] = $post['id'];
			$q['post_type'] = $post['type'];
		},
		"child" => function(&$q, $post) {
			$q['p'] = '' . $post['parent'];
			$q['post_type'] = $post['type'];
		},
		"sibling" => function(&$q, $post) {
			$q['post_type'] = $post['type'];
			$q['post_parent'] = $post['parent'];
			$q['post__not_in'] = array($post['id']);
		},
		"order" => function(&$q, $order) {
			if (preg_match('|^([a-zA-Z_]+)([\-+])?$|', $order, $m)) {
				$q['orderby'] = $m[1] == 'order' ? 'menu_order' : $m[1];
				$q['order'] = $m[2] == '-' ? 'DESC' : 'ASC';
			}
		}
	);

	foreach ($translate as $from => $to) {
		if (isset($q[$from])) {
			$val = $q[$from];
			array_without_key($q, $from);
			if (is_string($to)) {
				$q[$to] = $val;
			} else if (is_callable($to)) {
				$to($q, $val);
			}
		}
	}
	
	return new WP_Query($q);
}

/* buildPosts */
function buildPosts($q = false, &$exportQuery = null) {
	global $post;
	global $page;
	$oldPost = $post;
	$oldPage = $page;
	$query = queryPosts($q);
	$exportQuery = $query;
	$posts = array();
	while ($query->have_posts()) {
		$query->the_post();
		$posts[] = buildPost();
	}
	$post = $oldPost;
	$page = $oldPage;
	return $posts;
}

/* buildTerms */
function buildTerms($type, $opts = array()) {
	$key = "buildTerms-$type-" . md5(serialize($opts));
	return memoize($key, function() use ($type, $opts) {
		$terms = get_terms($type); // term_id, name, slug, term_group, term_taxonomy_id, taxonomy, description, parent, count
		$result = array();
		foreach ($terms as $term) {
			$result[] = array(
				'id' => intval($term->term_id),
				'type' => $type,
				'title' => $term->name,
				'slug' => $term->slug,
				'description' => $term->description,
				'url' => get_term_link($term)
			);
		}
		return $result;
	});
}

/* buildCategories */
function buildCategories($post) {
	return buildTerms('category', array(
		"terms" => wp_get_object_terms($post['id'], 'category')
	));
}

/* buildTags */
function buildTags($post) {
	return buildTerms('post_tag', array(
		"terms" => wp_get_post_tags($post['id'])
	));
}

function _collectMenuItemsByParent(&$items, $parentId, &$queriedObject, &$currentUrl, $opts = array()) {
	$o = array();
	if (is_array($items)) foreach ($items as $item) {
		if ($item->menu_item_parent == $parentId) {
			$newItem = array(
				"title" => $item->title,
				"url" => $item->url
			);
			if ($queriedObject->ID == $item->object_id || $item->url == $currentUrl) {
				$newItem["active"] = true;
			}
			if (strpos($currentUrl, $item->url) !== false) {
				$newItem["activeParent"] = true;
			}
			$children = _collectMenuItemsByParent($items, $item->ID, $queriedObject, $currentUrl, $opts);
			if (count($children) > 0) {
				$newItem['children'] = $children;
				foreach ($children as &$child) {
					if ($child['active'] || $child['activeParent']) {
						$newItem['activeParent'] = true;
					}
				}
			} else {
				unset($children);
			}
			$o[] = $newItem;
		}
	}
	return $o;
}

function _limitStartDepth($items, $startDepth = 1) {
	if (!is_array($items) || count($items) == 0) return null;
	if ($startDepth > 0) {
		$nextChildren = null;
		foreach ($items as &$item) {
			if ($item['children'] && ($item['active'] || $item['activeParent'])) {
				return _limitStartDepth($item['children'], $startDepth - 1);
			}
		}
		return null;
	} else {
		return $items;
	}
}

function _limitDepth($items, $depth = 1) {
	if (!is_array($items) || count($items) == 0) return null;
	$depth--;
	foreach ($items as &$item) {
		if ($item['children']) {
			if ($depth == 0) {
				unset($item['children']);
			} else {
				$item['children'] = _limitDepth($item['children'], $depth);
			}
		}
	}
	return $items;
}

/* buildTree */
function buildTree(&$items, $idField, $parentField, $parentValue) {
	$result = array();
	foreach ($items as &$item) {
		if ($item[$parentField] === $parentValue) {
			$item['children'] = buildTree($items, $idField, $parentField, $item[$idField]);
			if (count($item['children']) == 0) unset($item['children']);
			$result[] = $item;
		}
	}
	return $result;
}

/* fillPagesTree */
function fillPagesTree($parent, $level) {
	global $levels;
	$levels = max($levels, $level);

	$children = buildPosts(array("parent" => $parent, "order" => "order+"));
	if (is_array($children) && count($children) > 0) {
		foreach ($children as &$subpage) {
			$subpage = fillPagesTree($subpage, $level + 1);
			if (isActive($subpage)) {
				$subpage['active'] = true;
				$parent['activeParent'] = true;
			} elseif (isActiveParent($subpage)) {
				$subpage['active'] = true; // dumb
				$subpage['activeParent'] = true;
			} elseif ($subpage['activeParent']) {
				$subpage['active'] = true; // dumb
				$parent['activeParent'] = true;
			}
		}
		$parent['children'] = $children;
	}
	return $parent;
}

/* parseGallery – parses generated gallery HTML into semantic data */
function parseGallery($content) {
	if (preg_match_all('|<dl.+</dl>|Usi', $content, $matches)) {
		$photos = array();
		foreach ($matches[0] as $dl) {
			preg_match('|<a.+</a>|U', $dl, $m0);
			$a = $m0[0];
			if (preg_match('|href=\'(.+)\'|U', $a, $m1) && preg_match('|src="(.+)"|U', $a, $m2)) {
				$caption = '';
				if (preg_match('|<dd.*>\s*(.+)\s*</dd>|Usi', $dl, $m3)) {
					$caption = trim($m3[1]);
				}
				$photos[] = array(
					"thumb" => $m2[1],
					"full" => $m1[1],
					"caption" => $caption
				);
			}
		}
		return $photos;
	} else {
		return null;
	}
}

function array_where($arr, $key, $value) {
	return array_filter($arr, function($item) use ($key, $value) {
		return $item[$key] === $value;
	});
}

function array_without_key($arr, $key) {
	$result = array();
	foreach ($arr as $k => $v) {
		if ($k == $key) continue;
		$result[$k] = $v;
	}
	return $result;
}

function array_findOne($arr, $key, $value) {
	$found = array_where($arr, $key, $value);
	return end($found);
}
