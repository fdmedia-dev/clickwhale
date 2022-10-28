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
class Clickwhale {

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
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string $version The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( defined( 'CLICKWHALE_VERSION' ) ) {
			$this->version = CLICKWHALE_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'clickwhale';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();
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

		$Clickwhale_Admin          = new Clickwhale_Admin( $this->get_plugin_name(), $this->get_version() );
		$Clickwhale_Admin_Settings = new Clickwhale_Admin_Settings();
		$Clickwhale_Admin_Tools    = new Clickwhale_Admin_Tools( $this->get_plugin_name(), $this->get_version() );
		$Clickwhale_Ajax           = new Clickwhale_Ajax( $this->get_plugin_name(), $this->get_version() );
		$Clickwhale_Link_Edit      = Clickwhale_Link_Edit::getInstance();
		$Clickwhale_Linkpage_Edit  = new Clickwhale_Linkpage_Edit();
		$Clickwhale_Tools_Reset    = ClickwhaleToolsResetDB::getInstance();

		$Clickwhale_Admin_Settings->init( $this->get_plugin_name(), $this->get_version() );

		// ACTIONS
		$this->loader->add_action( 'admin_menu', $Clickwhale_Admin_Settings, 'add_plugin_menu' );
		$this->loader->add_action( 'admin_init', $Clickwhale_Admin_Settings, 'add_default_options' );
		$this->loader->add_action( 'admin_init', $Clickwhale_Admin_Settings, 'add_settings_fields' );

		$this->loader->add_action( 'admin_enqueue_scripts', $Clickwhale_Admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $Clickwhale_Admin, 'enqueue_scripts' );
		$this->loader->add_action( 'admin_print_footer_scripts', $Clickwhale_Admin, 'admin_scripts' );
		$this->loader->add_action( 'clickwhale_admin_banner', $Clickwhale_Admin, 'clickwhale_admin_banner_callback' ); // banner in admin part
		$this->loader->add_action( 'clickwhale_admin_banner_button_pro', $Clickwhale_Admin, 'clickwhale_admin_banner_button_pro_callback' ); // button to pro version
		$this->loader->add_action( 'clickwhale_admin_pro_message', $Clickwhale_Admin, 'clickwhale_admin_pro_message_callback' ); // button to pro version

		$this->loader->add_action( 'admin_post_nopriv_save_update_link', $Clickwhale_Link_Edit, 'save_update_link' );
		$this->loader->add_action( 'admin_post_save_update_link', $Clickwhale_Link_Edit, 'save_update_link' );

		$this->loader->add_action( 'admin_post_nopriv_save_update_linkpage', $Clickwhale_Linkpage_Edit, 'save_update_linkpage' );
		$this->loader->add_action( 'admin_post_save_update_linkpage', $Clickwhale_Linkpage_Edit, 'save_update_linkpage' );

		$this->loader->add_action( 'wp_ajax_clickwhale/admin/migration_notice_hide', $Clickwhale_Ajax, 'migration_notice_hide' );
		$this->loader->add_action( 'wp_ajax_clickwhale/admin/migration_deactive', $Clickwhale_Ajax, 'migration_deactive' );
		$this->loader->add_action( 'wp_ajax_clickwhale/admin/migration_to_clickwhale', $Clickwhale_Ajax, 'migration_to_clickwhale' );
		$this->loader->add_action( 'wp_ajax_clickwhale/admin/save_migration_option', $Clickwhale_Ajax, 'save_migration_option' );
		$this->loader->add_action( 'wp_ajax_clickwhale/admin/migration_reset', $Clickwhale_Ajax, 'migration_reset' );
		$this->loader->add_action( 'wp_ajax_clickwhale/admin/clickwhale_reset', $Clickwhale_Ajax, 'clickwhale_reset' );
		$this->loader->add_action( 'wp_ajax_clickwhale/admin/check_linkpage_slug', $Clickwhale_Ajax, 'check_linkpage_slug' );

		$this->loader->add_action( 'admin_init', $Clickwhale_Tools_Reset, 'initialize_reset_settings_options' );
		$this->loader->add_action( 'admin_init', $Clickwhale_Tools_Reset, 'initialize_reset_db_options' );
		$this->loader->add_action( 'admin_init', $Clickwhale_Tools_Reset, 'initialize_reset_stats_options' );
		$this->loader->add_action( 'admin_print_footer_scripts', $Clickwhale_Tools_Reset, 'admin_scripts' );

		// FILTERS
		$this->loader->add_filter( 'clickwhale_categories_limit', $Clickwhale_Admin, 'clickwhale_categories_limit_callback' );
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

		$this->loader->add_action( 'wp_enqueue_scripts', $Clickwhale_Public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $Clickwhale_Public, 'enqueue_scripts' );
		$this->loader->add_action( 'init', $Clickwhale_Public, 'do_redirect_handler' );

		$this->loader->add_filter( 'clickwhale_url_params', $Clickwhale_Public, 'clickwhale_url_params_callback', 10, 2 );
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
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @return    Clickwhale_Loader    Orchestrates the hooks of the plugin.
	 * @since     1.0.0
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @return    string    The version number of the plugin.
	 * @since     1.0.0
	 */
	public function get_version() {
		return $this->version;
	}

}
