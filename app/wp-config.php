<?php
/**
 * The base configurations of the WordPress.
 *
 * This file has the following configurations: MySQL settings, Table Prefix,
 * Secret Keys, WordPress Language, and ABSPATH. You can find more information
 * by visiting {@link http://codex.wordpress.org/Editing_wp-config.php Editing
 * wp-config.php} Codex page. You can get the MySQL settings from your web host.
 *
 * This file is used by the wp-config.php creation script during the
 * installation. You don't have to use the web site, you can just copy this file
 * to "wp-config.php" and fill in the values.
 *
 * @package WordPress
 */

// Disable filesystem level changes from WP
define('DISALLOW_FILE_EDIT', true);
define('DISALLOW_FILE_MODS', true);

// Set SSL'ed domain
if ( !empty( $_ENV["SSL_DOMAIN"] ) ) {
	define( 'SSL_DOMAIN_ALIAS', $_ENV["SSL_DOMAIN"] );
	define( 'FORCE_SSL_LOGIN', true );
	define( 'FORCE_SSL_ADMIN', true );
}

/**#@+
 * Memcache settings.
 */
define('WP_CACHE', getenv("WP_CACHE") == "true");
if (WP_CACHE && !empty( $_ENV["MEMCACHIER_SERVERS"] ) ) {
	$_mcsettings = parse_url($_ENV["MEMCACHIER_SERVERS"]);

	$sasl_memcached_config = array(
		'default' => array(
			array(
				'host' => $_mcsettings["host"],
				'port' => $_mcsettings["port"],
				'user' => $_ENV["MEMCACHIER_USERNAME"],
				'pass' => $_ENV["MEMCACHIER_PASSWORD"],
			),
		),
	);

	unset($_mcsettings);
}

/**#@-*/

/**#@+
 * MySQL settings.
 *
 * We are getting Heroku ClearDB settings from Heroku Environment Vars
 */
if (isset($_ENV["CLEARDB_DATABASE_URL"])) {
	$_dbsettings = $_ENV["CLEARDB_DATABASE_URL"];
} elseif (isset($_ENV['DB_URL'])) {
	$_dbsettings = $_ENV["DB_URL"];
} else {
	die("ERROR: Database not configured. Please add cleardb addon or provide DB_URL environment variable");
}
$_dbsettings = parse_url($_dbsettings);

define('DB_NAME',     trim($_dbsettings["path"],"/"));
define('DB_USER',     $_dbsettings["user"]          );
define('DB_PASSWORD', $_dbsettings["pass"]          );
if (isset($_dbsettings["port"])) {
	define('DB_HOST', $_dbsettings["host"] . ':' . $_dbsettings["port"]);
} else {
	define('DB_HOST', $_dbsettings["host"]          );
}
define('DB_CHARSET', 'utf8'                         );
define('DB_COLLATE', ''                             );

unset($_dbsettings);

// Set SSL settings
if ( isset( $_ENV["CLEARDB_SSL"] ) && 'ON' == $_ENV["CLEARDB_SSL"] ) {
	define('MYSQL_CLIENT_FLAGS', MYSQLI_CLIENT_COMPRESS | MYSQLI_CLIENT_SSL);
	define('MYSQL_SSL_KEY',      $_ENV["CLEARDB_SSL_KEY"]                  );
	define('MYSQL_SSL_CERT',     $_ENV["CLEARDB_SSL_CERT"]                 );
	define('MYSQL_SSL_CA',       $_ENV["CLEARDB_SSL_CA"]                   );
} else {
	define('MYSQL_CLIENT_FLAGS', MYSQLI_CLIENT_COMPRESS                    );
}

/**#@-*/

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY',         $_ENV['WP_AUTH_KEY']        );
define('SECURE_AUTH_KEY',  $_ENV['WP_SECURE_AUTH_KEY'] );
define('LOGGED_IN_KEY',    $_ENV['WP_LOGGED_IN_KEY']   );
define('NONCE_KEY',        $_ENV['WP_NONCE_KEY']       );
define('AUTH_SALT',        $_ENV['WP_AUTH_SALT']       );
define('SECURE_AUTH_SALT', $_ENV['WP_SECURE_AUTH_SALT']);
define('LOGGED_IN_SALT',   $_ENV['WP_LOGGED_IN_SALT']  );
define('NONCE_SALT',       $_ENV['WP_NONCE_SALT']      );

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each a unique
 * prefix. Only numbers, letters, and underscores please!
 */
$table_prefix  = 'wp_';

/**
 * WordPress Localized Language, defaults to English.
 *
 * Change this to localize WordPress. A corresponding MO file for the chosen
 * language must be installed to wp-content/languages. For example, install
 * de_DE.mo to wp-content/languages and set WPLANG to 'de_DE' to enable German
 * language support.
 */
define('WPLANG', isset($_ENV['WPLANG']) ? $_ENV['WPLANG'] : '');

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 */
define('WP_DEBUG', getenv("WP_DEBUG") == "true");

// http://codex.wordpress.org/Editing_wp-config.php#Moving_wp-content_folder
define('WP_CONTENT_DIR', dirname(__FILE__));
define('WP_CONTENT_URL', 'http://' . $_SERVER['HTTP_HOST']);

// https://wordpress.org/plugins/root-relative-urls/installation/
define('WP_HOME', 'http://' . $_SERVER['HTTP_HOST']);
define('WP_SITEURL', 'http://' . $_SERVER['HTTP_HOST']);
define('DOMAIN_CURRENT_SITE', $_SERVER['HTTP_HOST']);

/* That's all, stop editing! Happy blogging. */

/** Absolute path to the WordPress directory. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');