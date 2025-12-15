<?php
define( 'WP_CACHE', true );
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the web site, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * Localized language
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */
define('WP_HOME', 'http://mthfrsupport.local/');
define('WP_SITEURL', 'http://mthfrsupport.local/');

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'local' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', 'root' );

/** Database hostname */
define( 'DB_HOST', 'localhost:/Users/bot/Library/Application Support/Local/run/X7kPppFz5/mysql/mysqld.sock' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY',         '|D?}5OD#yh+0oM/>8$^nFAk_w{!/:1=.@H@C{fyyQs<51@O}O3R`WV[m[_-f~i-O');
define('SECURE_AUTH_KEY',  '+RO!p=k-j`v0) ){JuF,4&m{zT<r1HQtA.;3sdR67w0{Xp&j.Rl[|K53iBB!HRK)');
define('LOGGED_IN_KEY',    '&U!hX+x5,)bFZbs_3LVAB@:6YZxe-enmZ{5i#qbxXwQMoVL82~M!@^NrNwhM-3Q(');
define('NONCE_KEY',        'l?3%K.&F4dR$RmYiev~NXx(IG`f>ZIf:$|5E5/Ak-W+(ak!^?/?j=fR`Y8jMe+N5');
define('AUTH_SALT',        'E$ W$W@Ikd#H.ZeZg !T:W)i|J]d -1e8I!BwH[62+TT/>0f2*HbB{qE^|%T+-/1');
define('SECURE_AUTH_SALT', ']8h|whC>#8UFH{3/x?Mp*#_[L|j8+Ry!tU/+|NreyNaJQA)D4|u|`gy8+B9JzD2a');
define('LOGGED_IN_SALT',   '?)p DWEAr%CxJ2TjF|bws?eSvQAn6Ux~:dw3r#>o,GDxnlzRIi}L)Q!?D)/Fa-0{');
define('NONCE_SALT',       'sGYmTA9+-||]GAR*#UA;=7Y$7PN8S&q2x3{i$r9:JR#Gh.4Cq;  a+0,RZ]S*i&i');


/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wpub_';


/* Add any custom values between this line and the "stop editing" line. */



/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://wordpress.org/support/article/debugging-in-wordpress/
 */
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', true);


define( 'WP_ENVIRONMENT_TYPE', 'local' );
/* That's all, stop editing! Happy publishing. */
// define('WP_MEMORY_LIMIT', '9048M');  // Or 2G
// define('WP_MAX_MEMORY_LIMIT', '9048M'); // For admin-side operations
/** Absolute path to the WordPress directory. */
define('WP_MEMORY_LIMIT', '1024M');
define('WP_MAX_MEMORY_LIMIT', '1024M');

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
