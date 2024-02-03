<?php
/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://fdmedia.io/
 * @since      1.0.0
 *
 * @package    Clickwhale_Pro
 * @subpackage Clickwhale_Pro/includes
 */

namespace clickwhale_pro\includes;

use clickwhale\includes\admin\Clickwhale_Admin;
use clickwhale_pro\includes\admin\{Clickwhale_Pro_Admin, Clickwhale_Pro_Settings};
use clickwhale_pro\includes\front\Clickwhale_Pro_Public;
use clickwhale\includes\helpers\traits\{Singleton_Clone, Singleton_Wakeup};

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * The core Pro version class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the Pro version.
 *
 * @since      1.0.0
 * @package    Clickwhale_Pro
 * @subpackage Clickwhale_Pro/includes
 * @author     fdmedia <dev@krapan.net>
 */
final class Clickwhale_Pro {

	/**
	 * The unique instance of the Pro version.
	 *
	 * @var Clickwhale_Pro
     *
	 * @since 1.0.3
	 */
	private static $instance;

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @var      Clickwhale_Pro_Loader $loader Maintains and registers all hooks for the plugin.
	 * @access   protected
	 * @since    1.0.0
	 */
	protected $loader;
	private $locale;
	public $admin;
	public $settings;
	public $public;

	public static function get_instance(): Clickwhale_Pro {

		if ( empty( self::$instance ) ) {
			self::$instance = new self();

			self::$instance->load_dependencies();

			self::$instance->loader = new Clickwhale_Pro_Loader();
			self::$instance->locale = new Clickwhale_Pro_i18n();

			self::$instance->set_locale();

			self::$instance->admin    = Clickwhale_Pro_Admin::get_instance();
			self::$instance->settings = Clickwhale_Pro_Settings::get_instance();
			self::$instance->public   = Clickwhale_Pro_Public::get_instance();

			self::$instance->define_admin_hooks();
			self::$instance->define_public_hooks();
		}

		return self::$instance;
	}

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	private function __construct() {}

	use Singleton_Clone;
	use Singleton_Wakeup;

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Clickwhale_Pro_Loader. Orchestrates the hooks of the plugin.
	 * - Clickwhale_Pro_i18n. Defines internationalization functionality.
	 * - Clickwhale_Pro_Admin. Defines all hooks for the admin area.
	 * - Clickwhale_Pro_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once CLICKWHALE_PRO_DIR . 'includes/Clickwhale_Pro_Loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once CLICKWHALE_PRO_DIR . 'includes/Clickwhale_Pro_i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once CLICKWHALE_PRO_DIR . 'includes/admin/Clickwhale_Pro_Admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once CLICKWHALE_PRO_DIR . 'includes/front/Clickwhale_Pro_Public.php';

		/**
		 * Helpers
		 */
		require_once CLICKWHALE_PRO_DIR . 'includes/helpers/Linkpage_Styles_Helper.php';
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Clickwhale_Pro_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {
		$this->loader->add_action( 'plugins_loaded', $this->locale, 'load_plugin_textdomain' );
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$base_admin_class = get_class( Clickwhale_Admin::get_instance() );

		/* ACTIONS */
		/* ADD */
		//if ( isset( $_GET['page'] ) && substr( $_GET['page'], 0, strlen( 'clickwhale' ) ) === 'clickwhale' ) {
        if ( isset( $_GET['page'] ) && strpos( $_GET['page'], CLICKWHALE_SLUG ) === 0 ) {
			$this->loader->add_action(
				'admin_enqueue_scripts',
				$this->admin,
				'enqueue_styles'
			);

			$this->loader->add_action(
				'admin_enqueue_scripts',
				$this->admin,
				'enqueue_scripts'
			);
		}

		$this->loader->add_action(
			'clickwhale_menu_before_settings',
			$this->admin,
			'add_menu_items_before_settings'
		);

		$this->loader->add_action(
			'clickwhale_link_edit_fields',
			$this->admin->link,
			'link_edit_fields',
			20
		);

		$this->loader->add_action( 'clickwhale_insert_link_meta',
			$this->admin->link,
			'insert_link_meta',
			20,
			2
		);

		$this->loader->add_action(
			'clickwhale_update_link_meta',
			$this->admin->link,
			'update_link_meta',
			20,
			2
		);

		$this->loader->add_action(
			'clickwhale_linkpage_after_settings_fields',
			$this->admin->linkpage,
			'after_settings_fields',
			20
		);

		$this->loader->add_action(
			'clickwhale_linkpage_after_general_styles',
			$this->admin->linkpage,
			'after_general_styles',
			20
		);

		$this->loader->add_action(
			'clickwhale_linkpage_after_tabs_content',
			$this->admin->linkpage,
			'linkpage_after_tabs_content',
			20
		);

		$this->loader->add_action(
			'clickwhale_linkpage_after_styles_tables',
			$this->admin->linkpage,
			'linkpage_after_styles_tables',
			20
		);

		$this->loader->add_action(
			'clickwhale_tracking_code_conversion_fields',
			$this->admin->tracking_code,
			'tracking_code_conversion_fields',
			20
		);

		/* REMOVE */
		// Remove pro button from the plugin's banner
		$this->loader->remove_action(
			'clickwhale_admin_banner_pro_button',
			$base_admin_class,
			'admin_banner_pro_button'
		);

		$this->loader->remove_action(
			'clickwhale_admin_pro_message',
			$base_admin_class,
			'clickwhale_admin_pro_message_callback'
		);

		// hide pro promo page from the menu
		if ( $_SERVER['HTTP_HOST'] !== 'dev.clickwhale.pro' ) {
			$this->loader->remove_action(
				'clickwhale_menu_after_all',
				$base_admin_class,
				'show_pro_menu_item'
			);
		}

		/* FILTERS */
		$this->loader->add_filter(
			'clickwhale_settings_defaults',
			$this->settings,
			'settings_defaults'
		);

		$this->loader->add_filter(
			'clickwhale_settings_fields',
			$this->settings,
			'settings_fields'
		);

		$this->loader->add_filter(
			'clickwhale_settings_tabs',
			$this->settings,
			'settings_tabs'
		);

		$this->loader->add_filter(
			'plugin_action_links_' . CLICKWHALE_ID,
			$this->admin,
			'add_action_links'
		);

		$this->loader->add_filter(
			'clickwhale_link_defaults',
			$this->admin->link,
			'link_defaults_utm'
		);

		$this->loader->add_filter(
			'clickwhale_admin_pro_label',
			$this->admin,
			'remove_admin_pro_label'
		);

		$this->loader->add_filter(
			'clickwhale_categories_limit',
			$this->admin,
			'change_categories_limit'
		);

		$this->loader->add_filter(
			'clickwhale_linkpages_limit',
			$this->admin,
			'change_linkpages_limit'
		);

		$this->loader->add_filter(
			'clickwhale_linkpage_links_limit',
			$this->admin,
			'change_linkpage_links_limit'
		);

		$this->loader->add_filter(
			'clickwhale_active_tracking_codes_limit',
			$this->admin,
			'change_active_tracking_codes_limit'
		);

		$this->loader->add_filter(
			'clickwhale_linkpage_defaults',
			$this->admin->linkpage,
			'linkpage_defaults'
		);

		$this->loader->add_filter(
			'clickwhale_linkpage_credits',
			$this->admin->linkpage,
			'linkpage_credits'
		);

		$this->loader->add_filter(
			'clickwhale_linkpage_content_defaults',
			$this->admin->linkpage,
			'linkpage_content_defaults'
		);

		$this->loader->add_filter(
			'clickwhale_linkpage_select',
			$this->admin->linkpage,
			'linkpage_select'
		);

		$this->loader->add_filter(
			'clickwhale_linkpage_tabs',
			$this->admin->linkpage,
			'linkpage_tabs'
		);

		$this->loader->add_filter(
			'clickwhale_linkpage_advanced_background',
			$this->admin->linkpage,
			'linkpage_advanced_background'
		);

		$this->loader->add_filter(
			'clickwhale_tracking_code_default_post_types',
			$this->admin,
			'tracking_code_default_post_types'
		);

		$this->loader->add_filter(
			'clickwhale_tracking_code_credit_before',
			$this->admin,
			'tracking_code_credit_before'
		);

		$this->loader->add_filter(
			'clickwhale_tracking_code_credit_after',
			$this->admin,
			'tracking_code_credit_after'
		);

		$this->loader->add_filter(
			'clickwhale_is_tracking_code_conversion',
			$this->admin->tracking_code,
			'is_tracking_code_conversion'
		);

		// hide pro links on plugins.php
		if ( defined( 'CLICKWHALE_ID' ) ) {
			$this->loader->remove_filter(
				'plugin_action_links_' . CLICKWHALE_ID,
				$base_admin_class,
				'upgrade_action_link' );
		}

		/* AJAX */
		// Statistics
		$this->loader->add_action(
			'wp_ajax_clickwhale_pro/admin/get_total_clicks_for_period',
			$this->admin->ajax,
			'get_total_clicks_for_period'
		);

		$this->loader->add_action(
			'wp_ajax_clickwhale_pro/admin/get_clicks_count_for_day_and_id',
			$this->admin->ajax,
			'get_clicks_count_for_day_and_id'
		);

		$this->loader->add_action(
			'wp_ajax_clickwhale_pro/admin/get_total_views_for_period',
			$this->admin->ajax,
			'get_total_views_for_period'
		);

		$this->loader->add_action(
			'wp_ajax_clickwhale_pro/admin/get_views_count_for_day_and_id',
			$this->admin->ajax,
			'get_views_count_for_day_and_id'
		);
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {
		/* ACTIONS */
		/* ADD */
		$this->loader->add_action(
			'wp_enqueue_scripts',
			$this->public,
			'enqueue_styles'
		);

		$this->loader->add_action(
			'wp_enqueue_scripts',
			$this->public,
			'enqueue_scripts'
		);

		/* REMOVE */


		/* FILTERS */
		/* ADD */
		$this->loader->add_filter(
			'clickwhale_url_params',
			$this->public,
			'clickwhale_pro_url_params',
			10,
			2
		);

		$this->loader->add_filter(
			'clickwhale_linkpage_styles',
			$this->public,
			'linkpage_pro_styles',
			20,
			2
		);

		/* REMOVE */
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @return    string    The name of the plugin.
	 * @since     1.0.0
	 */
//	public function get_plugin_name(): string {
//		return CLICKWHALE_PRO_NAME;
//	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @return    Clickwhale_Pro_Loader    Orchestrates the hooks of the plugin.
	 * @since     1.0.0
	 */
	public function get_loader(): Clickwhale_Pro_Loader {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @return    string    The version number of the plugin.
	 * @since     1.0.0
	 */
	public function get_version(): string {
		return CLICKWHALE_PRO_VERSION;
	}
}
