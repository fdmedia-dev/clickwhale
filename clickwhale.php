<?php

/**
 *
 * @link              https://fdmedia.io
 * @since             1.0.0
 * @package           Clickwhale
 *
 * @wordpress-plugin
 * Plugin Name:       ClickWhale
 * Plugin URI:        https://clickwhale.pro
 * Description:       Best Link Shortener, Click Tracker & Link Pages Plugin for WordPress.
 * Version:           1.6.0
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

use clickwhale\includes\{Clickwhale, Clickwhale_Activator, Clickwhale_Deactivator};

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */

define( 'CLICKWHALE_VERSION', '1.6.0' );
define( 'CLICKWHALE_NAME', 'clickwhale' );

/**
 * @since 1.4.1
 */
define( 'CLICKWHALE_SLUG', plugin_basename( __DIR__ ) );
define( 'CLICKWHALE_ID', plugin_basename( __FILE__ ) );

/**
 * @since 1.6.0
 */
define( 'CLICKWHALE_ADMIN_DIR', plugin_dir_path( __FILE__ ) . 'includes/admin' );
define( 'CLICKWHALE_PUBLIC_DIR', plugin_dir_path( __FILE__ ) . 'includes/front' );
define( 'CLICKWHALE_TEMPLATES_DIR', plugin_dir_path( __FILE__ ) . 'templates' );
define( 'CLICKWHALE_ADMIN_ASSETS_DIR', plugin_dir_url( __FILE__ ) . 'assets/admin' );
define( 'CLICKWHALE_PUBLIC_ASSETS_DIR', plugin_dir_url( __FILE__ ) . 'assets/public' );


/**
 * The code that runs during plugin activation.
 */
function activate_clickwhale() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/Clickwhale_Activator.php';
	Clickwhale_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_clickwhale() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/Clickwhale_Deactivator.php';
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
 * The core plugin class
 */
require plugin_dir_path( __FILE__ ) . 'includes/Clickwhale.php';

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

	Clickwhale::get_instance()->run();

}

add_action( 'plugins_loaded', 'run_clickwhale' );

/**
 * Returns the instance of Clickwhale.
 *
 * The main function responsible for returning the one true Clickwhale
 * instance to functions everywhere.
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * Example: <?php $clickwhale = Clickwhale(); ?>
 *
 * @return Clickwhale|null The one true Clickwhale instance.
 * @since 1.6.0
 */
function clickwhale(): ?Clickwhale {
	return Clickwhale::get_instance();
}