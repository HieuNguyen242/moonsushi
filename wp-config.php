<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the
 * installation. You don't have to use the web site, you can
 * copy this file to "wp-config.php" and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * MySQL settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://codex.wordpress.org/Editing_wp-config.php
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'moonsushibar_web' );

/** MySQL database username */
define( 'DB_USER', 'root' );

/** MySQL database password */
define( 'DB_PASSWORD', '' );

/** MySQL hostname */
define( 'DB_HOST', 'localhost' );

/** Database Charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The Database Collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );



/** Secret key for APIs (should be unique key for each environment) */
define( 'API_Key', 'abc34f' );

define( 'Enable_Mobile_App_Notification', false );
define( 'Google_FCM_Authorization', 'key=' );

/** Food Order Open/Close Time - Specific Day 
 * 'Sun' => array ( 'open_time' => '1:00pm', 'close_time' => '9:00pm')
*/
define( 'Specific_Food_Order_Opening_Time', array (
	'Sun' => array ( 'open_time' => '1:00pm', 'close_time' => '9:00pm')
));

define( 'Max_Num_Orders_Per_Slot', 5);

define( 'Max_Total_Amount_Per_Slot', 250);

define( 'Delivery_Time', 30 );

/** Post Code and Fee ship */
define( 'Delivery_Post_Codes', array(
	'44799' => 1,
	'44793' => 1,
	'44787' => 1,
	'44795' => 1,
	'44803' => 1,
	'44801' => 2,
	'44791' => 2,
	'44809' => 2,
	'44869' => 2,
	'44797' => 2,
	'44807' => 3,
	'44866' => 3,
	'44867' => 3,
	'44805' => 3
));

define( 'Google_Analytics_Id', '' );

define( 'APIStrings', array(
	'food_item_group_unknown' => 'UNKATEGORISIERT',
	'discount_validation_failed' => 'Datenvalidierung fehlgeschlagen',
	'discount_invalid_code' => 'Rabattcode ungültig',
	'discount_add_failed' => 'Rabattcode hinzufügen fehlgeschlagen',
	'discount_update_failed' => 'Rabattcode konnte nicht aktualisiert werden',
	'discount_exists' => 'Rabattcode bestehen, bitte ändern Sie einen anderen Code !'
));


+define( 'Point_Settings', array(
	'runWhenStatus' => 'completed',
	'type' => 'amount',
	'numOfItemsOrAmounts' => 10,
	'convertedPoints' => 1
));

define( 'Rank_Settings', array(
	array(
		'id' => 1,
		'title' => 'Newbie',
		'min' => 0,
		'max' => 4,		
		'message' => ''
	),
	array(
		'id' => 2,
		'title' => 'Silber',
		'min' => 5,
		'max' => 99,		
		'message' => 'Herzlichen Glückwunsch ! Du hast die Silber-Mitgliedschaft erreicht. Bei den nächsten Bestellungen erhältst du 5% Rabatt auf den Gesamtbestellwert.'
	),
	array(
		'id' => 3,
		'title' => 'Gold',		
		'min' => 100,
		'max' => 199,
		'message' => 'Wunderbar ! Du hast die Gold - Mitgliedschaft erreicht. Bei den nächsten Bestellungen  erhältst du 10 % Rabatt auf den Gesamtbestellwert.'
	),
	array(
		'id' => 4,
		'title' => 'Diamant',
		'min' => 200,
		'max' => 399,
		'message' => 'Prima ! Du hast die Diamant - Mitgliedschaft erreicht. Bei den nächsten Bestellungen  erhältst du 15 % Rabatt auf den Gesamtbestellwert.'
	),
	array(
		'id' => 5,
		'title' => 'VIP',
		'min' => 400,
		'max' => 999999999999,
		'message' => 'Hervorragend ! Du hast die VIP - Mitgliedschaft erreicht. Bei den nächsten Bestellungen  erhältst du  25 % Rabatt auf den Gesamtbestellwert.'
	)
));

define( 'Promotion_Setting', array(
	array(
		'type' => 'repeat',
		'status' => 'active',
		'each' => 3,
		'messageCode' => 'promotion_repeat_each_5',
		'message' => 'Herzlichen Glückwunsch. Du hast genug Punkte gesammelt, um eine kostenlose Bubble Tea zu erhalten.'
	)
));




/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         '#^-p4r$`g+Xqwdq=fGcJ[vp !d>>VM{9q-4[xN@.Y|BL/1gBpsj#?MaNd6 4{*l[' );
define( 'SECURE_AUTH_KEY',  'BzxyZ~jarp$;igN38`5YR<-B]Vb$yY_#%SIwg_bd3C6>yFJv<=[:GT~e?P9Ne>{V' );
define( 'LOGGED_IN_KEY',    'f>= Ri9>.%]2fq6/diz]%EL{OSQ t#)D/Y>p?/%gA!=h+$sG>e:qg ,L--Jdy0#e' );
define( 'NONCE_KEY',        '*Hhp38Ss&%{@@WQoJ_O/s3rTN&qx~2Mh,`]Tln7|a+i <)`~F_.^jmsIDl!A215(' );
define( 'AUTH_SALT',        '[p$bA[(V:o%O0YPtIts:tibDW.]ULSHk`P+_Iizti-MjBw(~WF 5.$TnS7n6c@2{' );
define( 'SECURE_AUTH_SALT', 'cvl}6$l`{7is,QNj$N_84R15lS)}|1$,+ohSQ8Loj{$9cwc7y*$Y|WKU@C$>,TmB' );
define( 'LOGGED_IN_SALT',   '*5sob}`]*;b6lok8MdLru4c4Cw5@CVNEg(1ZOYEqwX~`7l|(@OI0tcc9D;hx E$_' );
define( 'NONCE_SALT',       ',,wh?=0?Z Q@.d[#k@8M@|YrT(<wqTZ*8}eMj<SmQmKfiO>t*51l][CDZ7s[pZ{Q' );

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'web_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the Codex.
 *
 * @link https://codex.wordpress.org/Debugging_in_WordPress
 */
define( 'WP_DEBUG', false );

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __FILE__ ) . '/' );
}

/** Sets up WordPress vars and included files. */
require_once( ABSPATH . 'wp-settings.php' );
