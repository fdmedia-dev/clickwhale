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

namespace clickwhale\includes;

use clickwhale\includes\front\Clickwhale_Public_Ajax;
use clickwhale\includes\helpers\Helper;
use clickwhale\includes\helpers\traits\{Singleton_Clone, Singleton_Wakeup};

use clickwhale\includes\admin\{
	Clickwhale_Admin,
	Clickwhale_Ajax,
	Clickwhale_Settings,
	Clickwhale_Tools,
	Clickwhale_WP_User
};
use clickwhale\includes\admin\reset\Clickwhale_Reset;
use clickwhale\includes\admin\categories\Clickwhale_Category_Edit;
use clickwhale\includes\admin\linkpages\Clickwhale_Linkpage_Edit;
use clickwhale\includes\admin\links\Clickwhale_Link_Edit;
use clickwhale\includes\admin\tracking_codes\Clickwhale_Tracking_Code_Edit;

use clickwhale\includes\front\Clickwhale_Public;

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
	 * @var Clickwhale|null
	 * @since 1.5.0
	 */
	private static ?Clickwhale $instance = null;

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Clickwhale_Loader $loader Maintains and registers all hooks for the plugin.
	 */
	protected Clickwhale_Loader $loader;
	private Clickwhale_i18n $locale;
	public ?Clickwhale_Admin $admin;
	public ?Clickwhale_Settings $settings;
	public Clickwhale_Link_Edit $link;
	public Clickwhale_Category_Edit $category;
	public Clickwhale_Linkpage_Edit $linkpage;
	public ?Clickwhale_Ajax $ajax;
	public Clickwhale_Tracking_Code_Edit $tracking_code;
	public Clickwhale_Tools $tools;
	public ?Clickwhale_Public_Ajax $public_ajax;
	public Clickwhale_Public $public;
	public Clickwhale_WP_User $user;

	/**
	 * Gets an instance of our plugin.
	 *
	 * @return Clickwhale
	 * @since 1.5.0
	 */
	public static function get_instance(): ?Clickwhale {
		if ( empty( self::$instance ) ) {
			self::$instance = new self();

			self::$instance->load_dependencies();

			self::$instance->loader = new Clickwhale_Loader();
			self::$instance->locale = new Clickwhale_i18n();
			self::$instance->user   = new Clickwhale_WP_User();

			self::$instance->set_locale();

			self::$instance->admin         = Clickwhale_Admin::get_instance();
			self::$instance->settings      = Clickwhale_Settings::get_instance();
			self::$instance->tools         = new Clickwhale_Tools();
			self::$instance->ajax          = Clickwhale_Ajax::get_instance();
			self::$instance->link          = new Clickwhale_Link_Edit();
			self::$instance->category      = new Clickwhale_Category_Edit();
			self::$instance->linkpage      = new Clickwhale_Linkpage_Edit();
			self::$instance->tracking_code = new Clickwhale_Tracking_Code_Edit();
			self::$instance->public        = Clickwhale_Public::get_instance();
			self::$instance->public_ajax   = Clickwhale_Public_Ajax::get_instance();

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
		require_once CLICKWHALE_DIR . 'includes/Clickwhale_Loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once CLICKWHALE_DIR . 'includes/Clickwhale_i18n.php';

		/**
		 * Debuggers
		 */
		require_once CLICKWHALE_DIR . 'includes/debuggers/Debugger.php';

		/**
		 * Helpers
		 */
		require_once CLICKWHALE_DIR . 'includes/helpers/Helper_Abstract.php';
		require_once CLICKWHALE_DIR . 'includes/helpers/Helper.php';
		require_once CLICKWHALE_DIR . 'includes/helpers/Links_Helper.php';
		require_once CLICKWHALE_DIR . 'includes/helpers/Categories_Helper.php';
		require_once CLICKWHALE_DIR . 'includes/helpers/Linkpages_Helper.php';
		require_once CLICKWHALE_DIR . 'includes/helpers/Tracking_Codes_Helper.php';

		/**
		 * Templates
		 */
		require_once CLICKWHALE_DIR . 'includes/content_templates/Clickwhale_Linkpage_Content_Templates.php';

		/**
		 * The class responsible for defining user functionality
		 */
		require_once CLICKWHALE_DIR . 'includes/admin/Clickwhale_WP_User.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once CLICKWHALE_DIR . 'includes/admin/Clickwhale_Admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once CLICKWHALE_DIR . 'includes/front/Clickwhale_Public.php';
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
		$this->loader->add_action( 'plugins_loaded', $this->locale, 'load_plugin_textdomain' );
	}

	/**
	 * Register all the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {
		$Clickwhale_Tools_Reset = Clickwhale_Reset::getInstance();

		// ACTIONS
		$this->loader->add_action( 'admin_menu', $this->admin, 'add_plugin_menu' );
		$this->loader->add_action( 'clickwhale_menu_after_all', $this->admin, 'show_pro_menu_item' );
		$this->loader->add_action( 'admin_init', $this->settings, 'add_default_options' );
		$this->loader->add_action( 'admin_init', $this->settings, 'add_settings_fields' );
		$this->loader->add_action( 'admin_head', $this->admin, 'hide_notice_on_upgrade_to_pro_page', 99 );
		if ( isset( $_GET['page'] ) && substr( $_GET['page'], 0, strlen( 'clickwhale' ) ) === 'clickwhale' ) {
			$this->loader->add_action( 'admin_enqueue_scripts', $this->admin, 'enqueue_styles' );
			$this->loader->add_action( 'admin_enqueue_scripts', $this->admin, 'enqueue_scripts' );
		}
		$this->loader->add_action( 'admin_print_footer_scripts', $this->admin, 'admin_scripts' );
		$this->loader->add_action( 'clickwhale_admin_banner', $this->admin, 'admin_banner' );
		$this->loader->add_action( 'clickwhale_admin_banner_pro_button', $this->admin, 'admin_banner_pro_button' );
		$this->loader->add_action( 'clickwhale_admin_pro_message', $this->admin, 'admin_pro_message' );
		// Clickwhale menu in the admin bar in the admin
		if ( ! Helper::get_clickwhale_option( 'general', 'hide_admin_bar_menu' ) ) {
			$this->loader->add_action( 'admin_bar_menu', $this, 'admin_bar_render', 999 );
		}
		$this->loader->add_action( 'admin_post_clickwhale_pro_subscription_action', $this->admin,
			'pro_subscription_action' );
		$this->loader->add_action( 'wp_ajax_clickwhale/admin/migration_notice_hide', $this->ajax,
			'migration_notice_hide' );
		$this->loader->add_action( 'wp_ajax_clickwhale/admin/migration_deactive', $this->ajax, 'migration_deactive' );
		$this->loader->add_action( 'wp_ajax_clickwhale/admin/migration_to_clickwhale', $this->ajax,
			'migration_to_clickwhale' );
		$this->loader->add_action( 'wp_ajax_clickwhale/admin/save_migration_option', $this->ajax,
			'save_migration_option' );
		$this->loader->add_action( 'wp_ajax_clickwhale/admin/migration_reset', $this->ajax, 'migration_reset' );
		$this->loader->add_action( 'wp_ajax_clickwhale/admin/clickwhale_reset', $this->ajax, 'clickwhale_reset' );
		$this->loader->add_action( 'wp_ajax_clickwhale/admin/slug_exists', $this->ajax, 'slug_exists' );
		$this->loader->add_action( 'wp_ajax_clickwhale/admin/get_posts_by_post_type', $this->ajax,
			'get_posts_by_post_type' );
		$this->loader->add_action( 'wp_ajax_clickwhale/admin/get_cw_links', $this->ajax, 'get_cw_links' );
		$this->loader->add_action( 'wp_ajax_clickwhale/admin/tracking_code_toggle_active', $this->ajax,
			'tracking_code_toggle_active' );
		$this->loader->add_action( 'wp_ajax_clickwhale/admin/add_link_to_linkpage', $this->ajax,
			'add_link_to_linkpage' );
		$this->loader->add_action( 'wp_ajax_clickwhale/admin/upload_csv', $this->ajax, 'upload_csv' );
		$this->loader->add_action( 'wp_ajax_clickwhale/admin/map_csv', $this->ajax, 'map_csv' );
		$this->loader->add_action(
			'wp_ajax_clickwhale/admin/check_slug_for_import',
			$this->ajax,
			'check_slug_for_import'
		);
		$this->loader->add_action( 'wp_ajax_clickwhale/admin/import_csv', $this->ajax, 'import_csv' );
		$this->loader->add_action( 'wp_ajax_clickwhale/admin/export_csv', $this->ajax, 'export_csv' );
		$this->loader->add_action( 'admin_init', $Clickwhale_Tools_Reset, 'initialize_reset_settings_options' );
		$this->loader->add_action( 'admin_init', $Clickwhale_Tools_Reset, 'initialize_reset_db_options' );
		$this->loader->add_action( 'admin_init', $Clickwhale_Tools_Reset, 'initialize_reset_stats_options' );
		$this->loader->add_action( 'admin_print_footer_scripts', $Clickwhale_Tools_Reset, 'admin_scripts' );

		$this->loader->add_filter( 'plugin_action_links_' . CLICKWHALE_ID, $this->admin, 'settings_action_link' );
		$this->loader->add_filter( 'plugin_action_links_' . CLICKWHALE_ID, $this->admin, 'upgrade_action_link' );
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {
		// Clickwhale menu in the admin bar
		if ( ! Helper::get_clickwhale_option( 'general', 'hide_admin_bar_menu' ) ) {
			$this->loader->add_action(
				'admin_bar_menu',
				$this,
				'admin_bar_render',
				999
			);
		}
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

		$this->loader->add_action(
			'init',
			$this->public,
			'do_redirect_handler'
		);

		$this->loader->add_action(
			'wp_ajax_clickwhale/public/track_custom_link',
			$this->public_ajax,
			'track_custom_link'
		);

		$this->loader->add_action(
			'wp_ajax_nopriv_clickwhale/public/track_custom_link',
			$this->public_ajax,
			'track_custom_link'
		);

		$this->loader->add_filter(
			'the_content',
			$this->public,
			'add_target_to_clickwhale_link'
		);
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		self::$instance->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @return    string    The name of the plugin.
	 * @since     1.0.0
	 */
//	public function get_plugin_name(): string {
//		return CLICKWHALE_SLUG;
//	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @return    Clickwhale_Loader    Orchestrates the hooks of the plugin.
	 * @since     1.0.0
	 */
	public function get_loader(): Clickwhale_Loader {
		return self::$instance->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @return    string    The version number of the plugin.
	 * @since     1.0.0
	 */
	public function get_version(): string {
		return CLICKWHALE_VERSION;
	}

	/**
	 * Provides default values Options.
	 *
	 * @return array
	 */
	public function default_options(): array {
		return array(
			'general'        => array(
				'name'    => __( 'General', CLICKWHALE_SLUG ),
				'text'    => __( 'Set up ClickWhale plugin global options.', CLICKWHALE_SLUG ),
				'options' => array(
					'access_level'        => [ 'administrator' ],
					'redirect_type'       => 301,
					'nofollow'            => 1,
					'sponsored'           => 0,
					'slug'                => '',
					'random_slug'         => 0,
					'hide_admin_bar_menu' => 0
				)
			),
			'tracking'       => array(
				'name'    => __( 'Tracking', CLICKWHALE_SLUG ),
				'text'    => __( 'Set up ClickWhale plugin global link tracking options.', CLICKWHALE_SLUG ),
				'options' => array(
					'tracking_duration'    => 30,
					'disable_tracking'     => 0,
					'exclude_user_by_role' => [ 'administrator' ]
				)
			),
			'linkpages'      => array(
				'name'    => __( 'Link Pages', CLICKWHALE_SLUG ),
				'text'    => __( 'Global settings for the Link Pages.', CLICKWHALE_SLUG ),
				'options' => array(
					'linkpage_links_target' => 0
				)
			),
			'tracking_codes' => array(
				'name'    => __( 'Tracking Codes', CLICKWHALE_SLUG ),
				'text'    => __( 'Global settings for the Tracking Codes.', CLICKWHALE_SLUG ),
				'options' => array()
			),
			'other'          => array(
				'name'    => __( 'Other', CLICKWHALE_SLUG ),
				'text'    => __( 'Set up other ClickWhale plugin useful options.', CLICKWHALE_SLUG ),
				'options' => array()
			)
		);
	}

	/**
	 * @return void
	 * @since 1.3.0
	 */
	public function admin_bar_render( $wp_admin_bar ) {
		$wp_admin_bar->add_node( array(
				'id'    => CLICKWHALE_SLUG,
				'title' => '<span class="ab-icon"><img src="' . CLICKWHALE_ADMIN_ASSETS_DIR . '/images/click-icon.svg"/></span> ClickWhale',
				'href'  => admin_url( 'admin.php?page=clickwhale' ),
				'meta'  => array(
					'class' => CLICKWHALE_SLUG,
					'title' => 'ClickWhale'
				)
			)
		);

		$wp_admin_bar->add_node( array(
				'id'     => CLICKWHALE_SLUG . '-new-link',
				'title'  => __( 'New Link', CLICKWHALE_SLUG ),
				'href'   => admin_url( 'admin.php?page=clickwhale-edit-link&id=0' ),
				'parent' => CLICKWHALE_SLUG,
				'meta'   => array(
					'class' => CLICKWHALE_SLUG . 'new-link',
					'title' => __( 'Add New Link', CLICKWHALE_SLUG )
				)
			)
		);
		$wp_admin_bar->add_node( array(
				'id'     => CLICKWHALE_SLUG . '-new-category',
				'title'  => __( 'New Category', CLICKWHALE_SLUG ),
				'href'   => admin_url( 'admin.php?page=clickwhale-edit-category&id=0' ),
				'parent' => CLICKWHALE_SLUG,
				'meta'   => array(
					'class' => CLICKWHALE_SLUG . 'new-category',
					'title' => __( 'Add New Category', CLICKWHALE_SLUG )
				)
			)
		);
		$wp_admin_bar->add_node( array(
				'id'     => CLICKWHALE_SLUG . '-new-linkpage',
				'title'  => __( 'New Link Page', CLICKWHALE_SLUG ),
				'href'   => admin_url( 'admin.php?page=clickwhale-edit-linkpage&id=0' ),
				'parent' => CLICKWHALE_SLUG,
				'meta'   => array(
					'class' => CLICKWHALE_SLUG . 'new-linkpage',
					'title' => __( 'Add New Link Page', CLICKWHALE_SLUG )
				)
			)
		);
		$wp_admin_bar->add_node( array(
				'id'     => CLICKWHALE_SLUG . '-new-tracking code',
				'title'  => __( 'New Tracking Code', CLICKWHALE_SLUG ),
				'href'   => admin_url( 'admin.php?page=clickwhale-edit-tracking-code&id=0' ),
				'parent' => CLICKWHALE_SLUG,
				'meta'   => array(
					'class' => CLICKWHALE_SLUG . 'new-tracking-code',
					'title' => __( 'Add New Tracking Code', CLICKWHALE_SLUG )
				)
			)
		);
	}
}
