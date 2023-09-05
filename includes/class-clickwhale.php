<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       #
 * @since      1.0.0
 *
 * @package    Clickwhale
 * @subpackage Clickwhale/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Clickwhale
 * @subpackage Clickwhale/includes
 * @author     fdmedia <https://fdmedia.io>
 */
final class Clickwhale {

	/**
	 * The unique instance of the plugin.
	 *
	 * @var Clickwhale
	 * @since 1.5.0
	 */
	private static $instance;

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Clickwhale_Loader $loader Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string $plugin_name The string used to uniquely identify this plugin.
	 */
	protected string $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string $version The current version of the plugin.
	 */
	protected string $version;

	/**
	 * Gets an instance of our plugin.
	 *
	 * @return Clickwhale
	 * @since 1.5.0
	 */
	public static function get_instance(): Clickwhale {
		if ( null === self::$instance ) {
			self::$instance = new self();
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
	private function __construct() {
		if ( defined( 'CLICKWHALE_VERSION' ) ) {
			$this->version = CLICKWHALE_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = CLICKWHALE_NAME;

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();
	}

	/**
	 * Throw error on object clone.
	 *
	 * The whole idea of the singleton design pattern is that there is a single
	 * object therefore, we don't want the object to be cloned.
	 *
	 * @return void
	 * @since 1.5
	 * @access protected
	 */
	public function __clone() {
		// Cloning instances of the class is forbidden.
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', $this->plugin_name ), '1.5' );
	}

	/**
	 * Disable un-serializing of the class.
	 *
	 * @return void
	 * @since 1.5
	 * @access protected
	 */
	public function __wakeup() {
		// Unserializing instances of the class is forbidden.
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', $this->plugin_name ), '1.5' );
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Clickwhale_Loader. Orchestrates the hooks of the plugin.
	 * - Clickwhale_i18n. Defines internationalization functionality.
	 * - Clickwhale_Admin. Defines all hooks for the admin area.
	 * - Clickwhale_Public. Defines all hooks for the public side of the site.
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
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-clickwhale-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-clickwhale-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-clickwhale-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-clickwhale-public.php';

		$this->loader = new Clickwhale_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Clickwhale_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Clickwhale_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {
		global $Clickwhale_Admin; // for add_/remove_action - https://www.forumming.com/question/354/remove-action-from-a-plugin-class-forced-to-use-global-instance
		//global $Clickwhale_Admin_Settings;

		$Clickwhale_Admin              = Clickwhale_Admin::get_instance();
		$Clickwhale_Admin_Settings     = Clickwhale_Admin_Settings::get_instance();
		$Clickwhale_Admin_Tools        = new Clickwhale_Admin_Tools( $this->get_plugin_name(), $this->get_version() );
		$Clickwhale_Ajax               = new Clickwhale_Ajax( $this->get_plugin_name(), $this->get_version() );
		$Clickwhale_Link_Edit          = Clickwhale_Link_Edit::getInstance();
		$Clickwhale_Category_Edit      = Clickwhale_Category_Edit::getInstance();
		$Clickwhale_Linkpage_Edit      = Clickwhale_Linkpage_Edit::getInstance();
		$Clickwhale_Tracking_Code_Edit = ClickwhaleTrackingCodeEdit::getInstance();
		$Clickwhale_Tools_Reset        = ClickwhaleToolsResetDB::getInstance();

		// ACTIONS
		$this->loader->add_action( 'admin_menu', $Clickwhale_Admin_Settings, 'add_plugin_menu' );
		$this->loader->add_action( 'admin_init', $Clickwhale_Admin_Settings, 'add_default_options' );
		$this->loader->add_action( 'admin_init', $Clickwhale_Admin_Settings, 'add_settings_fields' );
		$this->loader->add_action( 'clickwhale_menu_after_tools', $Clickwhale_Admin_Settings, 'show_pro_menu_item' );
		$this->loader->add_action( 'admin_head', $Clickwhale_Admin, 'hide_notice_on_upgrade_to_pro_page', 99 );
		$this->loader->add_action( 'admin_enqueue_scripts', $Clickwhale_Admin, 'enqueue_styles' );
		if ( isset( $_GET['page'] ) && substr( $_GET['page'], 0, strlen( 'clickwhale' ) ) === 'clickwhale' ) {
			$this->loader->add_action( 'admin_enqueue_scripts', $Clickwhale_Admin, 'enqueue_scripts' );
		}
		$this->loader->add_action( 'admin_print_footer_scripts', $Clickwhale_Admin, 'admin_scripts' );
		$this->loader->add_action( 'clickwhale_admin_banner', $Clickwhale_Admin, 'admin_banner' );
		$this->loader->add_action( 'clickwhale_admin_banner_pro_button', $Clickwhale_Admin, 'admin_banner_pro_button' );
		$this->loader->add_action( 'clickwhale_admin_pro_message', $Clickwhale_Admin, 'admin_pro_message' );
		$this->loader->add_action( 'admin_bar_menu', $Clickwhale_Admin, 'admin_bar_render', 999 );
		$this->loader->add_action( 'admin_post_clickwhale_pro_subscription_action', $Clickwhale_Admin, 'pro_subscription_action' );
		$this->loader->add_action( 'admin_post_save_update_link', $Clickwhale_Link_Edit, 'save_update_link' );
		$this->loader->add_action( 'admin_post_save_update_linkpage', $Clickwhale_Linkpage_Edit, 'save_update_linkpage' );
		$this->loader->add_action( 'admin_post_save_update_tracking_code', $Clickwhale_Tracking_Code_Edit, 'save_update_tracking_code' );
		$this->loader->add_action( 'wp_ajax_clickwhale/admin/migration_notice_hide', $Clickwhale_Ajax, 'migration_notice_hide' );
		$this->loader->add_action( 'wp_ajax_clickwhale/admin/migration_deactive', $Clickwhale_Ajax, 'migration_deactive' );
		$this->loader->add_action( 'wp_ajax_clickwhale/admin/migration_to_clickwhale', $Clickwhale_Ajax, 'migration_to_clickwhale' );
		$this->loader->add_action( 'wp_ajax_clickwhale/admin/save_migration_option', $Clickwhale_Ajax, 'save_migration_option' );
		$this->loader->add_action( 'wp_ajax_clickwhale/admin/migration_reset', $Clickwhale_Ajax, 'migration_reset' );
		$this->loader->add_action( 'wp_ajax_clickwhale/admin/clickwhale_reset', $Clickwhale_Ajax, 'clickwhale_reset' );
		$this->loader->add_action( 'wp_ajax_clickwhale/admin/check_slug', $Clickwhale_Ajax, 'check_slug' );
		$this->loader->add_action( 'wp_ajax_clickwhale/admin/get_posts_by_post_type', $Clickwhale_Ajax, 'get_posts_by_post_type' );
		$this->loader->add_action( 'wp_ajax_clickwhale/admin/get_cw_links', $Clickwhale_Ajax, 'get_cw_links' );
		$this->loader->add_action( 'wp_ajax_clickwhale/admin/tracking_code_toggle_active', $Clickwhale_Ajax, 'tracking_code_toggle_active' );
		$this->loader->add_action( 'wp_ajax_clickwhale/admin/add_link_to_linkpage', $Clickwhale_Ajax, 'add_link_to_linkpage' );
		$this->loader->add_action( 'wp_ajax_clickwhale/admin/upload_csv', $Clickwhale_Ajax, 'upload_csv' );
		$this->loader->add_action( 'wp_ajax_clickwhale/admin/map_csv', $Clickwhale_Ajax, 'map_csv' );
		$this->loader->add_action( 'wp_ajax_clickwhale/admin/check_slug_for_import', $Clickwhale_Ajax, 'check_slug_for_import' );
		$this->loader->add_action( 'wp_ajax_clickwhale/admin/import_csv', $Clickwhale_Ajax, 'import_csv' );
		$this->loader->add_action( 'wp_ajax_clickwhale/admin/export_csv', $Clickwhale_Ajax, 'export_csv' );
		$this->loader->add_action( 'admin_init', $Clickwhale_Tools_Reset, 'initialize_reset_settings_options' );
		$this->loader->add_action( 'admin_init', $Clickwhale_Tools_Reset, 'initialize_reset_db_options' );
		$this->loader->add_action( 'admin_init', $Clickwhale_Tools_Reset, 'initialize_reset_stats_options' );
		$this->loader->add_action( 'admin_print_footer_scripts', $Clickwhale_Tools_Reset, 'admin_scripts' );

		if ( isset( $_GET['page'] ) ) {
			if ( $_GET['page'] === 'clickwhale-edit-link' ) {
				if ( isset( $_GET['id'] ) && $_GET['id'] !== '0' ) {
					$this->loader->add_filter( 'admin_title', $Clickwhale_Link_Edit, 'set_edit_link_page_title', 10, 2 );
				} else {
					$this->loader->add_filter( 'admin_title', $Clickwhale_Link_Edit, 'set_add_link_page_title', 10, 2 );
				}
			}
			if ( $_GET['page'] === 'clickwhale-edit-category' ) {
				if ( isset( $_GET['id'] ) && $_GET['id'] !== '0' ) {
					$this->loader->add_filter( 'admin_title', $Clickwhale_Category_Edit, 'set_edit_category_page_title', 10, 2 );
				} else {
					$this->loader->add_filter( 'admin_title', $Clickwhale_Category_Edit, 'set_add_category_page_title', 10, 2 );
				}
			}
			if ( $_GET['page'] === 'clickwhale-edit-linkpage' ) {
				if ( isset( $_GET['id'] ) && $_GET['id'] !== '0' ) {
					$this->loader->add_filter( 'admin_title', $Clickwhale_Linkpage_Edit, 'set_edit_linkpage_page_title', 10, 2 );
				} else {
					$this->loader->add_filter( 'admin_title', $Clickwhale_Linkpage_Edit, 'set_add_linkpage_page_title', 10, 2 );
				}
			}
			if ( $_GET['page'] === 'clickwhale-edit-tracking-code' ) {
				if ( isset( $_GET['id'] ) && $_GET['id'] !== '0' ) {
					$this->loader->add_filter( 'admin_title', $Clickwhale_Tracking_Code_Edit, 'set_edit_tracking_code_page_title', 10, 2 );
				} else {
					$this->loader->add_filter( 'admin_title', $Clickwhale_Tracking_Code_Edit, 'set_add_tracking_code_page_title', 10, 2 );
				}
			}
		}

		$this->loader->add_filter( 'plugin_action_links_' . CLICKWHALE_ID, $Clickwhale_Admin_Settings, 'settings_action_link' );
		$this->loader->add_filter( 'plugin_action_links_' . CLICKWHALE_ID, $Clickwhale_Admin_Settings, 'upgrade_action_link' );
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$Clickwhale_Public = new Clickwhale_Public( $this->get_plugin_name(), $this->get_version() );
		$Clickwhale_Ajax   = new Clickwhale_Ajax( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'wp_enqueue_scripts', $Clickwhale_Public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $Clickwhale_Public, 'enqueue_scripts' );
		$this->loader->add_action( 'init', $Clickwhale_Public, 'do_redirect_handler' );
		$this->loader->add_action( 'wp_ajax_clickwhale/public/track_custom_link', $Clickwhale_Ajax, 'track_custom_link' );
		$this->loader->add_action( 'wp_ajax_nopriv_clickwhale/public/track_custom_link', $Clickwhale_Ajax, 'track_custom_link' );

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
	public function get_plugin_name(): string {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @return    Clickwhale_Loader    Orchestrates the hooks of the plugin.
	 * @since     1.0.0
	 */
	public function get_loader(): Clickwhale_Loader {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @return    string    The version number of the plugin.
	 * @since     1.0.0
	 */
	public function get_version(): string {
		return $this->version;
	}

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
	 * @return Clickwhale The one true Easy_Digital_Downloads instance.
	 * @since 1.5
	 */
	function Clickwhale(): Clickwhale {
		return Clickwhale::get_instance();
	}
}
