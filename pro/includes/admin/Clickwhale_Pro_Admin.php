<?php
/**
 * The admin-specific functionality of the plugin.
 *
 *  Defines the plugin name, version, and two examples hooks for how to
 *  enqueue the admin-specific stylesheet and JavaScript.
 *
 * @link       https://fdmedia.io/
 * @since      1.0.0
 *
 * @package    Clickwhale_Pro
 * @subpackage Clickwhale_Pro/admin
 */

namespace clickwhale_pro\includes\admin;

use clickwhale_pro\includes\admin\linkpages\Clickwhale_Pro_Linkpage_Edit;
use clickwhale_pro\includes\admin\links\Clickwhale_Pro_Link_Edit;
use clickwhale_pro\includes\admin\tracking_codes\Clickwhale_Pro_Tracking_Code_Edit;
use clickwhale\includes\helpers\traits\{Singleton_Clone, Singleton_Wakeup};
use Exception;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class Clickwhale_Pro_Admin {
	/**
	 * @since    1.5.0
	 * @var Clickwhale_Pro_Admin
	 */
	private static $instance;

    /**
     * @var Clickwhale_Pro_Ajax
     */
	public $ajax;

    /**
     * @var Clickwhale_Pro_Link_Edit
     */
	public $link;

    /**
     * @var Clickwhale_Pro_Linkpage_Edit
     */
	public $linkpage;

    /**
     * @var Clickwhale_Pro_Tracking_Code_Edit
     */
	public $tracking_code;

	/**
	 * @return Clickwhale_Pro_Admin
	 * @since    1.0.0
	 */
	public static function get_instance(): Clickwhale_Pro_Admin {
		if ( empty( self::$instance ) ) {
			self::$instance = new self();

			self::$instance->ajax          = Clickwhale_Pro_Ajax::get_instance();
			self::$instance->link          = new Clickwhale_Pro_Link_Edit();
			self::$instance->linkpage      = new Clickwhale_Pro_Linkpage_Edit();
			self::$instance->tracking_code = new Clickwhale_Pro_Tracking_Code_Edit();
		}

		return self::$instance;
	}

	use Singleton_Clone;
	use Singleton_Wakeup;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	private function __construct() {
		$this->load_dependencies();
	}


	/**
	 * Load the required dependencies for the Admin facing functionality.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Clickwhale_Admin_Settings. Registers the admin settings and page.
	 *
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		require_once CLICKWHALE_PRO_DIR . 'includes/admin/Clickwhale_Pro_Settings.php';
		require_once CLICKWHALE_PRO_DIR . 'includes/admin/Clickwhale_Pro_Ajax.php';
		require_once CLICKWHALE_PRO_DIR . 'includes/admin/links/Clickwhale_Pro_Link_Edit.php';
		require_once CLICKWHALE_PRO_DIR . 'includes/admin/linkpages/Clickwhale_Pro_Linkpage_Edit.php';
		require_once CLICKWHALE_PRO_DIR . 'includes/admin/tracking_codes/Clickwhale_Pro_Tracking_Code_Edit.php';
		require_once CLICKWHALE_PRO_DIR . 'includes/admin/statistics/Clickwhale_Pro_Statistics.php';
	}

    public function old_license_key_migration() {

        if ( ! clickwhale_fs()->has_api_connectivity() || clickwhale_fs()->is_registered() ) {
            // No connectivity OR the user already opted-in to Freemius.
            return;
        }

        if ( 'pending' != get_option( 'clickwhale_fs_migrated2fs', 'pending' ) ) {
            return;
        }

        $license_options = get_option( 'clickwhale_pro_license' );

        if ( empty( $license_options ) ) {
            return;
        }

        // No key to migrate.
        if ( empty( $license_options['api_key_0'] ) ) {
            return;
        }

        // Get the license key from the previous eCommerce platform's storage.
        $license_key = $license_options['api_key_0'];

        // Get the first 32 characters.
        $license_key = substr( $license_key, 0, 32 );

        try {
            $next_page = clickwhale_fs()->activate_migrated_license( $license_key );

        } catch ( Exception $e ) {
            update_option( 'clickwhale_fs_migrated2fs', 'unexpected_error' );
            return;
        }

        if ( clickwhale_fs()->can_use_premium_code() ) {
            update_option( 'clickwhale_fs_migrated2fs', 'done' );

            if ( is_string( $next_page ) ) {
                fs_redirect( $next_page );
            }

        } else {
            update_option( 'clickwhale_fs_migrated2fs', 'failed' );
        }
    }

	public function add_menu_items_before_settings() {
		add_submenu_page(
            CLICKWHALE_SLUG,
			__( 'Statistics', CLICKWHALE_PRO_NAME ),
			__( 'Statistics', CLICKWHALE_PRO_NAME ),
			'manage_options',
			CLICKWHALE_SLUG . '-statistics',
			array( $this, 'render_statistics_page_template' )
		);
	}

    public function hide_support_menu_item( $is_visible, $menu_id ) {

        if ( 'support' === $menu_id ) {
            return false;
        }
        return $is_visible;
    }

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		wp_enqueue_style(
			CLICKWHALE_PRO_NAME,
			CLICKWHALE_PRO_ADMIN_ASSETS_DIR . '/css/clickwhale-pro-admin.css', array(),
			CLICKWHALE_PRO_VERSION
		);
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		wp_enqueue_script(
            CLICKWHALE_PRO_NAME . '_chartjs',
			CLICKWHALE_PRO_ADMIN_ASSETS_DIR . '/js/chartjs/chart.js',
			'',
			'4.2.1',
			true
		);

		wp_enqueue_script(
            CLICKWHALE_PRO_NAME,
			CLICKWHALE_PRO_ADMIN_ASSETS_DIR . '/js/clickwhale-pro-admin.js',
			array( 'jquery' ),
			CLICKWHALE_PRO_VERSION,
			true
		);
	}

	public function render_statistics_page_template() {
		include_once( CLICKWHALE_PRO_TEMPLATES_DIR . '/admin/statistics/statistics.php' );
	}

	public function tracking_code_default_post_types( $fields ) {
		return $fields;
	}

	public function tracking_code_credit_before( $credit ) {
		return get_option( 'clickwhale_tracking_codes_options' ) ? '' : $credit;
	}

	public function tracking_code_credit_after( $credit ) {
		return get_option( 'clickwhale_tracking_codes_options' ) ? '' : $credit;
	}

	public function remove_admin_pro_label() {
		return '';
	}

	public function change_categories_limit(): int {
		return 9999;
	}

	public function change_linkpages_limit(): int {
		return 9999;
	}

	public function change_linkpage_links_limit(): int {
		return 9999;
	}

	public function change_active_tracking_codes_limit(): int {
		return 9999;
	}

	/**
	 * @since 1.0.2
	 */
	public function add_action_links( $links ) {
		return $links;
	}

}
