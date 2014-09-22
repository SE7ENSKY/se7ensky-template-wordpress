<?php

abstract class CacheImplementation {
	abstract public function get($key);
	abstract public function set($key, $value, $ttl);
	abstract public function delete($key);
	abstract public function exists($key);
	abstract public function flush($prefix = false);
}

class MemoryCacheImplementation extends CacheImplementation {
	private $cache;

	public function __construct() {
		$this->cache = array();
	}

	public function get($key) {
		if (isset($this->cache[$key])) {
			return $this->cache[$key];
		} else {
			return null;
		}
	}

	public function set($key, $value = true, $ttl = false) {
		$this->cache[$key] = $value;
	}

	public function delete($key) {
		unset($this->cache[$key]);
	}

	public function exists($key) {
		return isset($this->cache[$key]);
	}

	public function flush($prefix = false) {
		$this->cache = array();
	}
}

class FileCacheImplementation extends CacheImplementation {
	private $dir;

	public function __construct($dir) {
		$this->dir = $dir;
		self::checkDirExist($this->dir);
	}

	static protected function checkDirExist($dir) {
		if (!is_dir($dir)) {
			if (!mkdir($dir, 0777, true)) {
				throw new ErrorException("Cache directory '$dir' does not exist and cannot be created.");
			}
		}
	}

	protected function filename($key) {
		return $this->dir . '/' . $key;
	}
	protected function read($key) {
		return unserialize(file_get_contents($this->filename($key)));
	}
	protected function write($key, $value) {
		file_put_contents($this->filename($key), serialize($value));
	}

	public function exists($key) {
		return file_exists($this->filename($key));
	}

	public function delete($key) {
		unlink($this->filename($key));
	}

	public function get($key) {
		if ($this->exists($key)) {
			return $this->read($key);
		} else {
			return null;
		}
	}

	public function set($key, $value = true, $ttl = false) {
		// ttl is ignored
		$this->write($key, $value);
	}

	public function flush($prefix = false) {
		$files = glob($this->filename("*"));
		foreach ($files as $file) {
			unlink($file);
		}
	}
}

class WPDBCacheImplementation extends CacheImplementation {
	private $wpdb;
	private $table;
	/*
DROP TABLE IF EXISTS `wp_cache`;
CREATE TABLE IF NOT EXISTS `wp_cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
	*/

	public function __construct($wpdb, $table = false) {
		$this->wpdb = $wpdb;
		if (!$table) $table = $wpdb->prefix . 'cache';
		$this->table = $table;
		$this->hitCount = 0;
		$this->missCount = 0;
		$this->setCount = 0;
		$this->deleteCount = 0;
		$this->existsCount = 0;
		$this->totalQueryTime = 0;
		$this->totalSerializationOverhead = 0;
		register_shutdown_function(array($this, 'printStatistics'));
	}

	public function printStatistics() {
		if ($this->missCount + $this->hitCount > 0) { ?>

<!-- WPDBCacheImplementation statistics
	hitRatio: <?=100*$this->hitCount/($this->missCount + $this->hitCount)?>%
	totalQueryTime: <?=$this->totalQueryTime?>s
	totalSerializationOverhead: <?=$this->totalSerializationOverhead?>s
	hitCount: <?=$this->hitCount?>

	missCount: <?=$this->missCount?>

	setCount: <?=$this->setCount?>

	deleteCount: <?=$this->deleteCount?>

	existsCount: <?=$this->existsCount?>

-->
	
	<?php
		} else { ?>
<!-- WPDBCacheImplementation not involved in processing this request -->
		<?php }
	}

	public function get($key) {
		$t = microtime(true);
		$result = $this->wpdb->get_var($this->wpdb->prepare('SELECT `value` FROM ' . $this->table . ' WHERE `key`=%s', $key));
		$this->totalQueryTime += microtime(true) - $t;
		if ($result !== NULL) {
			$this->hitCount++;
			$t = microtime(true);
			$result = unserialize($result);
			$this->totalSerializationOverhead += microtime(true) - $t;
			return $result;
		} else {
			$this->missCount++;
			return null;
		}
	}
	public function set($key, $value, $ttl = 0) {
		// ttl is ignored
		$this->setCount++;
		$t = microtime(true);
		$serializedValue = serialize($value);
		$this->totalSerializationOverhead += microtime(true) - $t;
		$t = microtime(true);
		$this->wpdb->replace($this->table, array("key" => $key, "value" => $serializedValue), array("%s", "%s"));
		$this->totalQueryTime += microtime(true) - $t;
	}
	public function delete($key) {
		$this->deleteCount++;
		$t = microtime(true);
		$this->wpdb->delete($this->table, array("key" => $key), array('%s'));
		$this->totalQueryTime += microtime(true) - $t;
	}
	public function exists($key) {
		$this->existsCount++;
		$t = microtime(true);
		$result = 1 == $this->wpdb->get_var($this->wpdb->prepare('SELECT COUNT(`key`) FROM ' . $this->table . ' WHERE `key`=%s', $key));
		$this->totalQueryTime += microtime(true) - $t;
		return $result;
	}

	public function flush($prefix = false) {
		$q = 'DELETE FROM ' . $this->table;
		if ($prefix) {
			$q .= ' ' . $this->wpdb->prepare('WHERE `key` LIKE %s', "$prefix%");
		}
		$this->wpdb->query($q);
	}
}
