<?php

/**
 * @link              https://fdmedia.io
 * @since             1.0.0
 * @package           Clickwhale
 *
 * @wordpress-plugin
 * Plugin Name:       ClickWhale
 * Plugin URI:        https://clickwhale.pro
 * Description:       Link Manager, Link Shortener, Click Tracker for Affiliate Links & Link Pages.
 * Version:           2.8.2
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            ClickWhale
 * Author URI:        https://clickwhale.pro
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       clickwhale
 * Domain Path:       /languages
 */
// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
use Clickwhale\{Clickwhale, Clickwhale_Activator, Clickwhale_Deactivator};
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;
/**
 * Note from Freemius docs:
 * The SDK comes with a special mechanism to auto deactivate the free version when activating the paid one.
 * In order for this mechanism to work properly, you'd need to slightly adjust the code of the plugin's main file
 */
if ( function_exists( 'clickwhale_fs' ) ) {
    clickwhale_fs()->set_basename( false, __FILE__ );
} else {
    /**
     * Current plugin version.
     */
    define( 'CLICKWHALE_VERSION', '2.8.2' );
    /**
     * @since 1.4.1
     */
    // `<plugin-dir>`
    define( 'CLICKWHALE_SLUG', 'clickwhale' );
    // `<plugin-dir>/<plugin-file>.php`
    define( 'CLICKWHALE_ID', plugin_basename( __FILE__ ) );
    define( 'CLICKWHALE_DIR', plugin_dir_path( __FILE__ ) );
    define( 'CLICKWHALE_DIR_URL', plugin_dir_url( __FILE__ ) );
    /**
     * @since 1.6.0
     */
    define( 'CLICKWHALE_ADMIN_DIR', CLICKWHALE_DIR . 'includes/admin' );
    define( 'CLICKWHALE_PUBLIC_DIR', CLICKWHALE_DIR . 'includes/Front' );
    define( 'CLICKWHALE_TEMPLATES_DIR', CLICKWHALE_DIR . 'templates' );
    define( 'CLICKWHALE_ADMIN_ASSETS_DIR', CLICKWHALE_DIR_URL . 'assets/admin' );
    define( 'CLICKWHALE_PUBLIC_ASSETS_DIR', CLICKWHALE_DIR_URL . 'assets/public' );
    require_once __DIR__ . '/vendor/autoload.php';
    require_once __DIR__ . '/vendor/woocommerce/action-scheduler/action-scheduler.php';
    /**
     * DO NOT REMOVE THIS IF, IT IS ESSENTIAL FOR THE
     * `function_exists` CALL ABOVE TO PROPERLY WORK.
     */
    if ( !function_exists( 'clickwhale_fs' ) ) {
        // Create a helper function for easy SDK access.
        function clickwhale_fs() {
            global $clickwhale_fs;
            if ( !isset( $clickwhale_fs ) ) {
                // Include Freemius SDK.
                // SDK is auto-loaded through Composer
                $clickwhale_fs = fs_dynamic_init( array(
                    'id'               => '14609',
                    'slug'             => 'clickwhale',
                    'premium_slug'     => 'clickwhale-pro',
                    'type'             => 'plugin',
                    'public_key'       => 'pk_07a5633bd94c00467e7e58c200504',
                    'is_premium'       => false,
                    'premium_suffix'   => '(Pro)',
                    'has_addons'       => false,
                    'has_paid_plans'   => true,
                    'is_org_compliant' => false,
                    'has_affiliation'  => 'all',
                    'menu'             => array(
                        'slug'    => 'clickwhale',
                        'contact' => false,
                        'pricing' => false,
                        'support' => false,
                    ),
                    'is_live'          => true,
                ) );
            }
            return $clickwhale_fs;
        }

        // Init Freemius
        clickwhale_fs();
        // Signal that SDK was initiated
        do_action( 'clickwhale_fs_loaded' );
        /**
         * Hooked on `init` due to WordPress v6.7 translation logic updates
         * @link https://make.wordpress.org/core/2024/10/21/i18n-improvements-6-7/
         */
        add_action( 'init', function () {
            clickwhale_fs()->override_i18n( [
                'account' => esc_html__( 'License', 'clickwhale' ),
            ] );
        } );
    }
    // Freemius registers FS_Plugin_Updater during dynamic_init() (fires on 'init' hook).
    // We remove its transient filters on 'wp_loaded' (after dynamic_init() completes)
    // so that PUC exclusively handles update checks for the free version.
    add_action( 'wp_loaded', function () {
        $updater = FS_Plugin_Updater::instance( clickwhale_fs() );
        $basename = clickwhale_fs()->get_plugin_basename();
        // Remove update transient hooks so PUC handles update detection.
        remove_filter( 'pre_set_site_transient_update_plugins', [$updater, 'pre_set_site_transient_update_plugins_filter'] );
        remove_filter( 'pre_set_site_transient_update_themes', [$updater, 'pre_set_site_transient_update_plugins_filter'] );
        // Remove hooks that replace the standard update row with "Buy license".
        remove_action( "after_plugin_row_{$basename}", [$updater, 'catch_plugin_update_row'], 9 );
        remove_action( "after_plugin_row_{$basename}", [$updater, 'edit_and_echo_plugin_update_row'], 11 );
        // Remove Freemius plugin-information dialog override so PUC's "View details" works normally.
        remove_filter( 'plugins_api', [$updater, 'plugins_api_filter'], 10 );
        remove_action( 'admin_head', [$updater, 'catch_plugin_information_dialog_contents'] );
    } );
    $ClickWhaleUpdateChecker = PucFactory::buildUpdateChecker( 'https://github.com/fdmedia-dev/clickwhale', __FILE__, CLICKWHALE_SLUG );
    function clickwhale_activate() {
        Clickwhale_Activator::activate();
    }

    function clickwhale_deactivate() {
        Clickwhale_Deactivator::deactivate();
    }

    function clickwhale_uninstall_cleanup() {
        delete_option( 'clickwhale_version' );
        delete_option( 'clickwhale_updated_links_table_url_column' );
    }

    /**
     * returns:
     *  `true` if:
     *    `clickwhale_version` was not stored in DB at all. calls `add_option`
     *     OR
     *    `clickwhale_version` is stored with old value less than current `CLICKWHALE_VERSION`. calls `update_option`
     *
     *  `false` if:
     *    `clickwhale_version` value is equal to `CLICKWHALE_VERSION`
     *
     * @return bool
     */
    function clickwhale_maybe_add_or_update_version() : bool {
        $db_cw_version = get_option( 'clickwhale_version' );
        if ( !$db_cw_version ) {
            add_option( 'clickwhale_version', CLICKWHALE_VERSION );
            return true;
        } elseif ( version_compare( CLICKWHALE_VERSION, $db_cw_version, '>' ) ) {
            update_option( 'clickwhale_version', CLICKWHALE_VERSION );
            return true;
        } else {
            return false;
        }
    }

    register_activation_hook( __FILE__, 'clickwhale_activate' );
    register_deactivation_hook( __FILE__, 'clickwhale_deactivate' );
    /**
     * Run DB migrations on plugin update (activation hook does not fire on updates).
     *
     * @since 2.7.1
     */
    add_action( 'plugins_loaded', function () {
        $db_version = get_option( 'clickwhale_version' );
        if ( $db_version && version_compare( CLICKWHALE_VERSION, $db_version, '>' ) ) {
            Clickwhale_Activator::activate();
        }
    }, 1 );
    // Uninstall action
    clickwhale_fs()->add_action( 'after_uninstall', 'clickwhale_uninstall_cleanup' );
    /**
     * Begins execution of the plugin.
     *
     * @since    1.0.0
     */
    function clickwhale_run() {
        clickwhale()->run();
    }

    add_action( 'plugins_loaded', 'clickwhale_run' );
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
    function clickwhale() : Clickwhale {
        return Clickwhale::get_instance();
    }

}