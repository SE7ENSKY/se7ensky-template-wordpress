<?php

function image($image, $sizeString) {
	error_log("image($image, $sizeString");
	$sizeData = __parseSizeString($sizeString);
	$sizeKey = __makeSizeKey($sizeData);
	$attachment = __resolveAttachment($image);

	if (!isset($attachment['metadata']['sizes'][$sizeKey])) {
		__createImageSize($attachment, $sizeData);
		$attachment['metadata'] = wp_get_attachment_metadata($attachment['id']);
	}

	$ud = wp_upload_dir();
	$a = $attachment['metadata']['sizes'][$sizeKey];
	if (isset($a['url'])) {
		return $a['url'];
	} else {
		$ud = wp_upload_dir();
		return $ud['baseurl'] . '/' . dirname($attachment['metadata']['file']) . '/' . $a['file'];
	}
}

function featuredImage($postId, $size) {
	$url = wp_get_attachment_image_src(get_post_thumbnail_id($postId), 'single-post-thumbnail')[0];
	if ($url) {
		return image($url, $size);
	} else {
		return null;
	}
}

function __resolveAttachment($arg) {
	if (is_numeric($arg)) {
		// numeric -> attachment id
		return array(
			"id" => $arg,
			"metadata" => wp_get_attachment_metadata($arg)
		);
	} elseif (is_string($arg)) {
		if (strpos($arg, '/') === false && strpos($arg, '.') === false) {
			// no slashes and dots -> field name
			// ToDo
			throw new ErrorException("image.__resolveAttachment: no implementation for field name and global post. Field name: $arg.");
		} else {
			global $wpdb;
			$ud = wp_upload_dir();
			$relativePath = str_replace($ud['baseurl'] . '/', '', $arg);
			$possibleAttachmentIds = $wpdb->get_col($wpdb->prepare(
				'SELECT post_id FROM ' . $wpdb->prefix . 'postmeta
				 WHERE meta_key=%s AND meta_value LIKE %s'
			, '_wp_attachment_metadata', '%' . $relativePath . '%'));
			if (count($possibleAttachmentIds) == 1) {
				$id = $possibleAttachmentIds[0];
				return array(
					"id" => $id,
					"metadata" => wp_get_attachment_metadata($id)
				);
			} else {
				throw new ErrorException("image.__resolveAttachment: cannot resolve attachment for $arg. Possible IDs: " . implode(', ', $possibleAttachmentIds));
			}
			// print_r($id); die;
			// $url = parse_url($arg);
			// if (isset($url['scheme'])) {
			// 	// is remote url
			// 	$tmpFile = tempnam(sys_get_temp_dir(), "attachment");
			// 	file_put_contents($tmpFile, file_get_contents($arg));
			// 	list($width, $height, $type) = getimagesize($filename);
			// 	$ext = null;
			// 	switch ($type) {
			// 		case IMAGETYPE_GIF: $ext = ".gif"; break;
			// 		case IMAGETYPE_JPEG: $ext = ".jpg"; break;
			// 		case IMAGETYPE_PNG: $ext = ".png"; break;
			// 	}
			// 	if (!$ext) throw new ErrorException("image.__resolveAttachment: image type not supported");
			// 	$ud = wp_upload_dir();
			// 	print_r($ud);
			// 	// $filename = $ud[];
			// }
		}
	}
}

function __parseSizeString($size) {
	if (preg_match('/^((?<width>\d+)x(?<height>\d+)|((?<onlywidth>\d+)w)|((?<onlyheight>\d+)h))([\s_-](?<mode>crop|resize))?$/', $size, $m)) {
		$result = array();
		if (!empty($m['onlywidth'])) {
			$result['width'] = intval($m['onlywidth']);
		} elseif (!empty($m['onlyheight'])) {
			$result['height'] = intval($m['onlyheight']);
		} else {
			$result['width'] = intval($m['width']);
			$result['height'] = intval($m['height']);
		}
		$result['mode'] = !empty($m['mode']) ? $m['mode'] : "crop";
		return $result;
	} else throw new ErrorException("image.__parseSizeString: could not parse size definition");
}

function __makeSizeKey($sizeData) {
	$result = "";
	if (isset($sizeData['width']) && isset($sizeData['height'])) {
		$result .= $sizeData['width'] . "x" . $sizeData['height'];
	} elseif (isset($sizeData['width'])) {
		$result .= $sizeData['width'] . "w";
	} elseif (isset($sizeData['height'])) {
		$result .= $sizeData['height'] . "h";
	}
	$result .= "-" . $sizeData['mode'];
	return $result;
}

// to be get rid of
function __load_image_to_edit_path($attachment_id, $size = 'full') {
    $filepath = get_attached_file( $attachment_id );
    if ( $filepath && file_exists( $filepath ) ) {
        if ( 'full' != $size && ( $data = image_get_intermediate_size( $attachment_id, $size ) ) ) {
            $filepath = apply_filters( 'load_image_to_edit_filesystempath', path_join( dirname( $filepath ), $data['file'] ), $attachment_id, $size );
        }
    } elseif ( function_exists( 'fopen' ) && function_exists( 'ini_get' ) && true == ini_get( 'allow_url_fopen' ) ) {
        $filepath = apply_filters( 'load_image_to_edit_attachmenturl', wp_get_attachment_url( $attachment_id ), $attachment_id, $size );
    }
    return apply_filters( 'load_image_to_edit_path', $filepath, $attachment_id, $size );
}

function __resizeImage($input, $output, $width, $height, $crop = true) {
	$editor = wp_get_image_editor($input);
	if (!is_wp_error($editor)) {
		$editor->resize($width, $height, $crop);
		$editor->save($output);
	} else {
		error_log("image: wp error on image editor creation");
		error_log($editor->get_error_message());
		throw new ErrorException("image: wp error on image editor creation: " . $editor->get_error_message());
	}
}

function __createImageSize($attachment, $sizeData) {
	$sizeKey = __makeSizeKey($sizeData);
	!isset($sizeData['width']) && $sizeData['width'] = round($attachment['metadata']['width'] / $attachment['metadata']['height'] * $sizeData['height']);
	!isset($sizeData['height']) && $sizeData['height'] = round($attachment['metadata']['height'] / $attachment['metadata']['width'] * $sizeData['width']);
	$filename = preg_replace('/.(png|jpg|gif|bmp)$/', '-' . $sizeKey . '.\\1', $attachment['metadata']['file']);
	$input = __load_image_to_edit_path($attachment['id']);
	$ud = wp_upload_dir();
	$output = $ud['basedir'] . '/' . $filename;
	__resizeImage($input, $output, $sizeData['width'], $sizeData['height'], $sizeData['mode'] == 'crop');
	$type = wp_check_filetype($output);
	$attachment['metadata']['sizes'][$sizeKey] = array(
		'file' => basename($filename),
		'width' => $sizeData['width'],
		'height' => $sizeData['height'],
		'resizeMode' => $sizeData['mode'],
		'mime-type' => $type['type']
	);
	wp_update_attachment_metadata($attachment['id'], $attachment['metadata']);
}
