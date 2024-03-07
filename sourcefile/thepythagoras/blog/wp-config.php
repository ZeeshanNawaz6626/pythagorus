<?php

//Begin Really Simple SSL session cookie settings
@ini_set('session.cookie_httponly', true);
@ini_set('session.cookie_secure', true);
@ini_set('session.use_only_cookies', true);
//END Really Simple SSL cookie settings
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

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define('WP_CACHE', true);
define( 'WPCACHEHOME', '/home/thepythagoras/public_html/blog/wp-content/plugins/wp-super-cache/' );
define( 'DB_NAME', 'thepythagoras_blog' );

/** Database username */
define( 'DB_USER', 'thepythagoras_blog' );

/** Database password */
define( 'DB_PASSWORD', 'f*!kHV_J@20pxrf3' );

/** Database hostname */
define( 'DB_HOST', 'localhost:3306' );

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
define('AUTH_KEY', '3||6L2V3h28D6059dvd8+x6H_)5*[wCq6o5Dp680(Qjo7I4Q0(gT:N)ZTa/j1|Bt');
define('SECURE_AUTH_KEY', '!0i]XA1Wf2#kFh@06(0(((c5u#*]td_9+_KK7*XQ~Q5fogr;xIC+5f2dqJ[#8|-a');
define('LOGGED_IN_KEY', '3|(900-7o#)6n1p;:vz#&RO]6gaq:@58(1m%5ME:6/!sU*K/C8o/9*zZ0i|4V%5;');
define('NONCE_KEY', 'N9QlFq64ZN]O94N5D2*Ah:+wMeg5&sk9b*v3g15U6oR02[Dok0S@FehRAds8uc)_');
define('AUTH_SALT', '8cwYWHT_3hBoD_TOJ;_iZG99EZJjitxKo1-0_;]1/y73JX117(a(B4wIsP(S6)A@');
define('SECURE_AUTH_SALT', '37O8+Mm3C4mW_K_2YN/84[bA6O|Lm;0!KF6B8/~@~Oy|%f59Ho]7Hu9I|E[;G1la');
define('LOGGED_IN_SALT', '3]b]O@L:4/5@X!ugV74LE8A_7|76yn9Tma9+M315noL6KxCb3#%~)(C2T+L9a#|]');
define('NONCE_SALT', 'W#ZJoY]i053~_du_+71K6y2cJN@I~+q8S3113:X4n3exrEz2B!)+2+3Nwz55q(em');


/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = '67L3f_';


/* Add any custom values between this line and the "stop editing" line. */

define('WP_ALLOW_MULTISITE', true);
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
if ( ! defined( 'WP_DEBUG' ) ) {
	define( 'WP_DEBUG', false );
}

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
