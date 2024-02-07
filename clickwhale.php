<?php
/**
 * @link              https://fdmedia.io
 * @since             1.0.0
 * @package           Clickwhale
 *
 * @wordpress-plugin
 * Plugin Name:       ClickWhale
 * Plugin URI:        https://clickwhale.pro
 * Description:       Best Link Shortener, Click Tracker & Link Pages Plugin for WordPress.
 * Version:           1.6.0
 * Requires at least: 5.0
 * Requires PHP:      7.4
 * Author:            ClickWhale
 * Author URI:        https://clickwhale.pro
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       clickwhale
 * Domain Path:       /languages
 * @fs_premium_only   /pro/
 */

use clickwhale\includes\{ Clickwhale, Clickwhale_Activator, Clickwhale_Deactivator };

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Note from Freemius docs: The SDK comes with a special mechanism to auto deactivate the free version when activating the paid one. In order for this mechanism to work properly, you'd need to slightly adjust the code of the plugin's main file
if ( function_exists( 'clickwhale_fs' ) ) {
    clickwhale_fs()->set_basename( true, __FILE__ );
} else {
    /**
     * Current plugin version.
     */
    define( 'CLICKWHALE_VERSION', '1.6.0' );
    define( 'CLICKWHALE_NAME', 'clickwhale' );

    /**
     * @since 1.4.1
     */
    define( 'CLICKWHALE_SLUG',    plugin_basename( __DIR__ ) ); // `<plugin-dir>`
    define( 'CLICKWHALE_ID',      plugin_basename( __FILE__ ) ); // `<plugin-dir>/<plugin-file>.php`
    define( 'CLICKWHALE_DIR',     plugin_dir_path( __FILE__ ) );
    define( 'CLICKWHALE_DIR_URL', plugin_dir_url( __FILE__ ) );

    /**
     * @since 1.6.0
     */
    define( 'CLICKWHALE_ADMIN_DIR',         CLICKWHALE_DIR . 'includes/admin' );
    define( 'CLICKWHALE_PUBLIC_DIR',        CLICKWHALE_DIR . 'includes/front' );
    define( 'CLICKWHALE_TEMPLATES_DIR',     CLICKWHALE_DIR . 'templates' );
    define( 'CLICKWHALE_ADMIN_ASSETS_DIR',  CLICKWHALE_DIR_URL . 'assets/admin' );
    define( 'CLICKWHALE_PUBLIC_ASSETS_DIR', CLICKWHALE_DIR_URL . 'assets/public' );

    // DO NOT REMOVE THIS IF, IT IS ESSENTIAL FOR THE `function_exists` CALL ABOVE TO PROPERLY WORK.
    if ( !function_exists( 'clickwhale_fs' ) ) {
        // Create a helper function for easy SDK access.
        function clickwhale_fs() {
            global  $clickwhale_fs ;

            if ( !isset( $clickwhale_fs ) ) {
                // Include Freemius SDK.
                require_once dirname( __FILE__ ) . '/freemius/start.php';
                $clickwhale_fs = fs_dynamic_init( array(
                    'id'                  => '14609',
                    'slug'                => 'clickwhale',
                    'premium_slug'        => 'clickwhale-pro',
                    'type'                => 'plugin',
                    'public_key'          => 'pk_07a5633bd94c00467e7e58c200504',
                    'is_premium'          => true,
                    'premium_suffix'      => 'Pro',
                    'has_premium_version' => true,
                    'has_addons'          => false,
                    'has_paid_plans'      => true,
                    'has_affiliation'     => 'all',
                    'menu'             => array(
                        'slug'    => 'clickwhale',
                        'contact' => false,
                        'pricing' => false,
                    ),
                ) );
            }

            return $clickwhale_fs;
        }

        // Init Freemius.
        clickwhale_fs();
        // Signal that SDK was initiated.
        do_action( 'clickwhale_fs_loaded' );

//        clickwhale_fs()->override_i18n( [
//            'account' => __( 'License', CLICKWHALE_NAME ),
//        ] );

        // Uninstall action
        clickwhale_fs()->add_action( 'after_uninstall', 'clickwhale_uninstall_cleanup' );
    }

    /**
     * The code that runs during plugin activation.
     */
    function activate_clickwhale() {
        require_once CLICKWHALE_DIR . 'includes/Clickwhale_Activator.php';
        Clickwhale_Activator::activate();
    }

    /**
     * The code that runs during plugin deactivation.
     */
    function deactivate_clickwhale() {
        require_once CLICKWHALE_DIR . 'includes/Clickwhale_Deactivator.php';
        Clickwhale_Deactivator::deactivate();
    }

    register_activation_hook( __FILE__, 'activate_clickwhale' );
    register_deactivation_hook( __FILE__, 'deactivate_clickwhale' );

    /**
     * The code for Freemius that runs after the plugin uninstall event.
     */
    function clickwhale_uninstall_cleanup() {
        delete_option( 'clickwhale_version' );

        do_action( 'clickwhale_uninstall_cleanup' );
    }

    /**
     * Traits for Singleton
     */
    require_once CLICKWHALE_DIR . 'includes/helpers/traits/Singleton_Clone.php';
    require_once CLICKWHALE_DIR . 'includes/helpers/traits/Singleton_Wakeup.php';

    /**
     * Core class of plugin
     */
    require CLICKWHALE_DIR . 'includes/Clickwhale.php';

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
        clickwhale()->run();
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
     * @return Clickwhale The one true Clickwhale instance.
     * @since 1.6.0
     */
    function clickwhale(): Clickwhale {
        return Clickwhale::get_instance();
    }

    if ( clickwhale_fs()->is__premium_only() ) {
        /**
         * PRO version
         */
        require_once CLICKWHALE_DIR . 'pro/clickwhale-pro.php';
    }
}
