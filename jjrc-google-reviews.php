<?php
/**
 * Plugin Name: JJRC Google Reviews
 * Plugin URI:  https://github.com/chichorivera/jjrc-google-reviews
 * Description: Muestra reseñas de Google Maps mediante shortcodes configurables con carousel u owl-carousel.
 * Version:     1.4.3
 * Author:      Javier Rivera
 * License:     GPL-2.0+
 * Text Domain: jjrc-google-reviews
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'JJRC_GR_VERSION',  '1.4.3' );
define( 'JJRC_GR_PATH',     plugin_dir_path( __FILE__ ) );
define( 'JJRC_GR_URL',      plugin_dir_url( __FILE__ ) );
define( 'JJRC_GR_BASENAME', plugin_basename( __FILE__ ) );

require_once JJRC_GR_PATH . 'includes/class-database.php';
require_once JJRC_GR_PATH . 'includes/class-api.php';
require_once JJRC_GR_PATH . 'includes/class-admin.php';
require_once JJRC_GR_PATH . 'includes/class-shortcode.php';

register_activation_hook( __FILE__, [ 'JJRC_GR_Database', 'install' ] );
register_uninstall_hook( __FILE__, [ 'JJRC_GR_Database', 'uninstall' ] );

add_action( 'plugins_loaded', function () {
    JJRC_GR_Database::maybe_upgrade();
    JJRC_GR_Admin::init();
    JJRC_GR_Shortcode::init();
} );
