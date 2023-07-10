<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://fdmedia.io
 * @since             1.0.0
 * @package           Clickwhale
 *
 * @wordpress-plugin
 * Plugin Name:       ClickWhale
 * Plugin URI:        https://clickwhale.pro
 * Description:       Best Link Shortener, Click Tracker & Link Pages Plugin for WordPress.
 * Version:           1.3.6
 * Requires at least: 3.8
 * Requires PHP       7.4.0
 * Author:            ClickWhale
 * Author URI:        https://clickwhale.pro
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       clickwhale
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */

const CLICKWHALE_VERSION = '1.3.6';
const CLICKWHALE_NAME = 'clickwhale';

/**
 * @since 1.3.0
 */
define( 'CLICKWHALE_ADMIN_IMAGES_DIR', plugin_dir_url( __FILE__ ) . 'admin/images' );
define( 'CLICKWHALE_ADMIN_CSS_DIR', plugin_dir_url( __FILE__ ) . 'admin/css' );
define( 'CLICKWHALE_ADMIN_JS_DIR', plugin_dir_url( __FILE__ ) . 'admin/js' );
define( 'CLICKWHALE_PUBLIC_IMAGES_DIR', plugin_dir_url( __FILE__ ) . 'public/images' );
define( 'CLICKWHALE_PUBLIC_CSS_DIR', plugin_dir_url( __FILE__ ) . 'public/css' );
define( 'CLICKWHALE_PUBLIC_JS_DIR', plugin_dir_url( __FILE__ ) . 'public/js' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-clickwhale-activator.php
 */
function activate_clickwhale() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-clickwhale-activator.php';
	Clickwhale_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-clickwhale-deactivator.php
 */
function deactivate_clickwhale() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-clickwhale-deactivator.php';
	Clickwhale_Deactivator::deactivate();
}

function clickwhale_update_db_check() {
	if ( version_compare( CLICKWHALE_VERSION, get_option( 'clickwhale_version' ), '>' ) ) {
		activate_clickwhale();
	}
}

register_activation_hook( __FILE__, 'activate_clickwhale' );
register_deactivation_hook( __FILE__, 'deactivate_clickwhale' );

add_action( 'plugins_loaded', 'clickwhale_update_db_check' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-clickwhale.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_clickwhale() {

	$plugin = new Clickwhale();
	$plugin->run();

}
add_action( 'plugins_loaded', 'run_clickwhale', 10 );
