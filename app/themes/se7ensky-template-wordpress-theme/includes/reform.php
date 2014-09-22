<?php

function reform($schema, $o) {
	$params = reform_process_params($schema);
	if (!$params['valid']) {
		unset($params['valid']);
		reform_validation_error($params);
		die;
	} else {
		if (is_callable($o)) {
			try {
				reform_respond($o($params));
			} catch (Exception $e) {
				reform_error(500, $e);
			}
		}
	}
}

function reform_process_params($schema) {
	$timestampValidator = function($value, $format) {
		$parseResult = date_parse_from_format($format, $value);
		return count($parseResult['errors']) == 0;
	};
	
	$validators = array(
		'required' => function($value) {
			return $value === '0' || !empty($value);
		},
		// 'phone' => function($value) {
		// 	return strlen($value) <= 15
		// 		&& preg_match('/^(\+38\s+|38\s+)?0\d{2}\s*\d{3}\s*\d{2}\s*\d{2}$/', $value);
		// },
		'phone' => function(&$value) {
			$value = preg_replace('/[^\d]/', '', $value);
			return !!preg_match('/^(0\d{2})?\d{7}$/', $value);
		},
		'email' => function($value) {
			return strlen($value) < 256
				&& filter_var($value, FILTER_VALIDATE_EMAIL);
		},
		'url' => function($value) {
			return strlen($value) < 256
				&& filter_var($value, FILTER_VALIDATE_URL);
		},
		'ip' => function($value) {
			return strlen($value) <= 15
				&& filter_var($value, FILTER_VALIDATE_IP);
		},
		'int' => function($value, $options = null) {
			return strlen($value) <= 15
				&& filter_var($value, FILTER_VALIDATE_INT, $options);
		},
		'float' => function($value, $options = null) {
			return strlen($value) <= 15
				&& filter_var($value, FILTER_VALIDATE_FLOAT, $options);
		},
		'string' => function($value) {
			return is_string($value);
		},
		'object' => function($value) {
			return is_object($value);
		},
		'array' => function($value) {
			return is_array($value);
		},
		'in' => function($value, $values) {
			return in_array($value, $values);
		},
		'timestamp' => $timestampValidator,
		'date' => $timestampValidator,
		'time' => $timestampValidator
	);

	$result = array();
	$validationResult = array();
	foreach ($schema as $param => $validations) {
		$result[$param] = reform_read_param($param);
		if (!is_array($validations)) $validations = array($validations);
		if ($result[$param] === null && !in_array('required', $validations)) {
			continue;
		}
		foreach ($validations as $validation => $options) {
			if (is_integer($validation) && is_string($options)) {
				$validation = $options;
				$options = true;
			}
			if (array_key_exists($validation, $validators)) { // standard validator
				$_ = $validators[$validation]($result[$param], $options);
				if ($_ !== true) {
					if ($_ === false) $_ = true;
					if (!is_array($validationResult[$param])) $validationResult[$param] = array();
					$validationResult[$param][$validation] = $_;
				}
			} else if (is_callable($options)) { // callable validator
				$_ = $options($result[$param]);
				if ($_ !== true) {
					if ($_ === false) $_ = true;
					if (!is_array($validationResult[$param])) $validationResult[$param] = array();
					$validationResult[$param][$validation] = $_;
				}
			} else { // unknown validator
				if (!is_array($result[$param])) $result[$param] = array();
				$validationResult[$param][$validation] = 'unknown validation';
			}
			if ($validationResult[$param][$validation]) break; // stop field validations on first failed validation
		}
	}
	if (count($validationResult) > 0) {
		$validationResult['valid'] = false;
		return $validationResult;
	} else {
		$result['valid'] = true;
		return $result;
	}
}

function reform_read_param($name, $o = '_POST') {
	if ($o == '_POST') $o = $_POST;
	if (preg_match('/^(?<left>[^\\.]+)\\.(?<right>.+)$/', $name, $m)) {
		return reform_read_param($m['right'], $o[$m['left']]);
	} else {
		return $o[$name];
	}
}

$reform_http_status_codes = array(100 => "Continue", 101 => "Switching Protocols", 102 => "Processing", 200 => "OK", 201 => "Created", 202 => "Accepted", 203 => "Non-Authoritative Information", 204 => "No Content", 205 => "Reset Content", 206 => "Partial Content", 207 => "Multi-Status", 300 => "Multiple Choices", 301 => "Moved Permanently", 302 => "Found", 303 => "See Other", 304 => "Not Modified", 305 => "Use Proxy", 306 => "(Unused)", 307 => "Temporary Redirect", 308 => "Permanent Redirect", 400 => "Bad Request", 401 => "Unauthorized", 402 => "Payment Required", 403 => "Forbidden", 404 => "Not Found", 405 => "Method Not Allowed", 406 => "Not Acceptable", 407 => "Proxy Authentication Required", 408 => "Request Timeout", 409 => "Conflict", 410 => "Gone", 411 => "Length Required", 412 => "Precondition Failed", 413 => "Request Entity Too Large", 414 => "Request-URI Too Long", 415 => "Unsupported Media Type", 416 => "Requested Range Not Satisfiable", 417 => "Expectation Failed", 418 => "I'm a teapot", 419 => "Authentication Timeout", 420 => "Enhance Your Calm", 422 => "Unprocessable Entity", 423 => "Locked", 424 => "Failed Dependency", 424 => "Method Failure", 425 => "Unordered Collection", 426 => "Upgrade Required", 428 => "Precondition Required", 429 => "Too Many Requests", 431 => "Request Header Fields Too Large", 444 => "No Response", 449 => "Retry With", 450 => "Blocked by Windows Parental Controls", 451 => "Unavailable For Legal Reasons", 494 => "Request Header Too Large", 495 => "Cert Error", 496 => "No Cert", 497 => "HTTP to HTTPS", 499 => "Client Closed Request", 500 => "Internal Server Error", 501 => "Not Implemented", 502 => "Bad Gateway", 503 => "Service Unavailable", 504 => "Gateway Timeout", 505 => "HTTP Version Not Supported", 506 => "Variant Also Negotiates", 507 => "Insufficient Storage", 508 => "Loop Detected", 509 => "Bandwidth Limit Exceeded", 510 => "Not Extended", 511 => "Network Authentication Required", 598 => "Network read timeout error", 599 => "Network connect timeout error");

function reform_respond($object = true) {
	header('Content-Type: application/json');
	echo json_encode($object);
}

function reform_error($code = 500, $o = null) {
	global $reform_http_status_codes;
	header("HTTP/1.0 $code " . $reform_http_status_codes[$code]);
	header('Content-Type: application/json');
	if ($o) {
		echo json_encode($o);
	}
}

function reform_validation_error($params) {
	reform_error(409, array(
		'message' => 'ValidationError',
		'code' => 'ValidationError',
		'errors' => $params
	));
}

