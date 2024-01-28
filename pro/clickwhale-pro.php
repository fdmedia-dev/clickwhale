<?php

if ( ! defined( 'WPINC' ) ) {
	die;
}

use clickwhale_pro\includes\{
    Clickwhale_Pro,
    Clickwhale_Pro_Activator,
    Clickwhale_Pro_Deactivator
};

define( 'CLICKWHALE_PRO_VERSION', '1.0.3' );
define( 'CLICKWHALE_PRO_NAME', 'clickwhale-pro' );
define( 'CLICKWHALE_PRO_SLUG', 'clickwhale-pro' );
define( 'CLICKWHALE_PRO_DIR', CLICKWHALE_DIR . 'pro/' );
define( 'CLICKWHALE_PRO_DIR_URL', CLICKWHALE_DIR_URL . 'pro/' );

define( 'CLICKWHALE_PRO_ADMIN_DIR',         CLICKWHALE_PRO_DIR . 'includes/admin' );
define( 'CLICKWHALE_PRO_PUBLIC_DIR',        CLICKWHALE_PRO_DIR . 'includes/front' );
define( 'CLICKWHALE_PRO_TEMPLATES_DIR',     CLICKWHALE_PRO_DIR . 'templates' );
define( 'CLICKWHALE_PRO_ADMIN_ASSETS_DIR',  CLICKWHALE_PRO_DIR_URL . 'assets/admin' );
define( 'CLICKWHALE_PRO_PUBLIC_ASSETS_DIR', CLICKWHALE_PRO_DIR_URL . 'assets/front' );

register_activation_hook( CLICKWHALE_DIR . 'clickwhale.php', 'activate_clickwhale_pro' );
register_deactivation_hook( CLICKWHALE_DIR . 'clickwhale.php', 'deactivate_clickwhale_pro' );

add_action( 'plugins_loaded', 'run_clickwhale_pro', 11 );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-clickwhale-pro-activator.php
 */
function activate_clickwhale_pro() {
    require_once CLICKWHALE_PRO_DIR . 'includes/Clickwhale_Pro_Activator.php';
    Clickwhale_Pro_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-clickwhale-pro-deactivator.php
 */
function deactivate_clickwhale_pro() {
    require_once CLICKWHALE_PRO_DIR . 'includes/Clickwhale_Pro_Deactivator.php';
    Clickwhale_Pro_Deactivator::deactivate();
}

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
/**
 * Traits for Singleton
 */
require_once CLICKWHALE_PRO_DIR . 'includes/helpers/traits/Singleton_Clone.php';
require_once CLICKWHALE_PRO_DIR . 'includes/helpers/traits/Singleton_Wakeup.php';

/**
 * Plugin
 */
require CLICKWHALE_PRO_DIR . 'includes/Clickwhale_Pro.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_clickwhale_pro() {
    Clickwhale_Pro::get_instance()->run();
}

/**
 * Returns the instance of Clickwhale_Pro.
 *
 * The main function responsible for returning the one true Clickwhale_Pro
 * instance to functions everywhere.
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * Example: <?php $clickwhale_pro = Clickwhale_Pro(); ?>
 *
 * @return Clickwhale_Pro|null The one true Clickwhale_Pro instance.
 *
 * @since 1.6.0
 */
function clickwhale_pro(): ?Clickwhale_Pro {
	return Clickwhale_Pro::get_instance();
}
