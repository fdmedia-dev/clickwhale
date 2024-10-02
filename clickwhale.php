<?php

/**
 * @link              https://fdmedia.io
 * @since             1.0.0
 * @package           Clickwhale
 *
 * @wordpress-plugin
 * Plugin Name:       ClickWhale
 * Plugin URI:        https://clickwhale.pro
 * Description:       Link Manager, Link Shortener and Click Tracker for Affiliate Links & Link Pages.
 * Version:           2.3.0
 * Requires at least: 5.0
 * Requires PHP:      7.4
 * Author:            ClickWhale
 * Author URI:        https://clickwhale.pro
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       clickwhale
 * Domain Path:       /languages
 */
use clickwhale\includes\{Clickwhale, Clickwhale_Activator, Clickwhale_Deactivator};
if ( !defined( 'ABSPATH' ) ) {
    exit;
}
// Note from Freemius docs: The SDK comes with a special mechanism to auto deactivate the free version when activating the paid one. In order for this mechanism to work properly, you'd need to slightly adjust the code of the plugin's main file
if ( function_exists( 'clickwhale_fs' ) ) {
    clickwhale_fs()->set_basename( false, __FILE__ );
} else {
    /**
     * Current plugin version.
     */
    define( 'CLICKWHALE_VERSION', '2.3.0' );
    define( 'CLICKWHALE_NAME', 'clickwhale' );
    /**
     * @since 1.4.1
     */
    // `<plugin-dir>`
    define( 'CLICKWHALE_SLUG', plugin_basename( __DIR__ ) );
    // `<plugin-dir>/<plugin-file>.php`
    define( 'CLICKWHALE_ID', plugin_basename( __FILE__ ) );
    define( 'CLICKWHALE_DIR', plugin_dir_path( __FILE__ ) );
    define( 'CLICKWHALE_DIR_URL', plugin_dir_url( __FILE__ ) );
    /**
     * @since 1.6.0
     */
    define( 'CLICKWHALE_ADMIN_DIR', CLICKWHALE_DIR . 'includes/admin' );
    define( 'CLICKWHALE_PUBLIC_DIR', CLICKWHALE_DIR . 'includes/front' );
    define( 'CLICKWHALE_TEMPLATES_DIR', CLICKWHALE_DIR . 'templates' );
    define( 'CLICKWHALE_ADMIN_ASSETS_DIR', CLICKWHALE_DIR_URL . 'assets/admin' );
    define( 'CLICKWHALE_PUBLIC_ASSETS_DIR', CLICKWHALE_DIR_URL . 'assets/public' );
    /**
     * @since 2.3.0
     */
    define( 'CLICKWHALE_URL_COLUMN', 'clickwhale_updated_links_table_url_column' );
    // DO NOT REMOVE THIS IF, IT IS ESSENTIAL FOR THE `function_exists` CALL ABOVE TO PROPERLY WORK.
    if ( !function_exists( 'clickwhale_fs' ) ) {
        // Create a helper function for easy SDK access.
        function clickwhale_fs() {
            global $clickwhale_fs;
            if ( !isset( $clickwhale_fs ) ) {
                // Include Freemius SDK.
                require_once dirname( __FILE__ ) . '/freemius/start.php';
                $clickwhale_fs = fs_dynamic_init( array(
                    'id'              => '14609',
                    'slug'            => 'clickwhale',
                    'premium_slug'    => 'clickwhale-pro',
                    'type'            => 'plugin',
                    'public_key'      => 'pk_07a5633bd94c00467e7e58c200504',
                    'is_premium'      => false,
                    'premium_suffix'  => '(Pro)',
                    'has_addons'      => false,
                    'has_paid_plans'  => true,
                    'has_affiliation' => 'all',
                    'menu'            => array(
                        'slug'    => CLICKWHALE_SLUG,
                        'contact' => false,
                        'pricing' => false,
                    ),
                    'is_live'         => true,
                ) );
            }
            return $clickwhale_fs;
        }

        // Init Freemius
        clickwhale_fs();
        // Signal that SDK was initiated
        do_action( 'clickwhale_fs_loaded' );
        clickwhale_fs()->override_i18n( [
            'account' => __( 'License', CLICKWHALE_NAME ),
        ] );
    }
    function clickwhale_activate() {
        require_once CLICKWHALE_DIR . 'includes/Clickwhale_Activator.php';
        Clickwhale_Activator::activate();
    }

    function clickwhale_deactivate() {
        require_once CLICKWHALE_DIR . 'includes/Clickwhale_Deactivator.php';
        Clickwhale_Deactivator::deactivate();
    }

    function clickwhale_uninstall_cleanup() {
        delete_option( 'clickwhale_version' );
        delete_option( CLICKWHALE_URL_COLUMN );
    }

    /**
     * returns:
     *  `true` if:
     *    `clickwhale_version` was not stored in DB at all. calls `add_option`
     *     OR
     *    `clickwhale_version` is stored with old value less than `CLICKWHALE_VERSION`. calls `update_option`
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

    function clickwhale_maybe_add_or_update_url_column() {
        if ( version_compare( CLICKWHALE_VERSION, '2.1.3', '<=' ) ) {
            return;
        }
        global $wpdb;
        $updated_url_column = get_option( CLICKWHALE_URL_COLUMN );
        if ( !$updated_url_column ) {
            $action = 'add';
        } elseif ( version_compare( $updated_url_column, CLICKWHALE_VERSION, '!=' ) ) {
            $action = 'update';
        } else {
            return;
        }
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $query = $wpdb->query( "ALTER TABLE {$wpdb->prefix}clickwhale_links MODIFY COLUMN url varchar(1000) DEFAULT '' NOT NULL" );
        if ( !$query ) {
            return;
        }
        $function = $action . '_option';
        $function( CLICKWHALE_URL_COLUMN, CLICKWHALE_VERSION );
    }

    register_activation_hook( __FILE__, 'clickwhale_activate' );
    register_deactivation_hook( __FILE__, 'clickwhale_deactivate' );
    // Uninstall action
    clickwhale_fs()->add_action( 'after_uninstall', 'clickwhale_uninstall_cleanup' );
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
     * @since    1.0.0
     */
    function run_clickwhale() {
        clickwhale()->run();
        /* @since 2.3.0 */
        $maybe_update_version = clickwhale_maybe_add_or_update_version();
        if ( $maybe_update_version ) {
            clickwhale_maybe_add_or_update_url_column();
        }
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
    function clickwhale() : Clickwhale {
        return Clickwhale::get_instance();
    }

}