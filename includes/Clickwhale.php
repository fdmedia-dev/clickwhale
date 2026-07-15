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

namespace Clickwhale;

use Clickwhale\Front\{Clickwhale_Public, Clickwhale_Public_Ajax};
use Clickwhale\Helpers\Helper;
use Clickwhale\Helpers\Traits\{Singleton_Clone, Singleton_Wakeup};

use Clickwhale\Admin\{Clickwhale_Admin,
	Clickwhale_Ajax,
	Clickwhale_Settings,
	Clickwhale_Tools,
	Clickwhale_WP_User,
	Clickwhale_Rest_Controller
};

use Clickwhale\Admin\Reset\Clickwhale_Reset;
use Clickwhale\Admin\Blocks\Clickwhale_Smart_Display_Block;
use Clickwhale\Admin\Categories\Clickwhale_Category_Edit;
use Clickwhale\Admin\Linkpages\Clickwhale_Linkpage_Edit;
use Clickwhale\Admin\Links\Clickwhale_Link_Edit;
use Clickwhale\Admin\TrackingCodes\Clickwhale_Tracking_Code_Edit;
use Clickwhale\Admin\SmartDisplays\Clickwhale_Smart_Display_Edit;
use Clickwhale\Admin\SmartDisplays\Clickwhale_Smart_Display_Scheduler;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The core plugin class that is used to define internationalization,
 *  admin-specific hooks, and public-facing site hooks.
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
	 *
	 * @since 1.5.0
	 */
	private static Clickwhale $instance;

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Clickwhale_Loader $loader Maintains and registers all hooks for the plugin.
	 */
	protected Clickwhale_Loader $loader;

	/**
	 * @var Clickwhale_WP_User
	 */
	public Clickwhale_WP_User $user;

	/**
	 * @var Clickwhale_Admin
	 */
	public Clickwhale_Admin $admin;

	/**
	 * @var Clickwhale_Settings
	 */
	public Clickwhale_Settings $settings;

	/**
	 * @var Clickwhale_Tools
	 */
	public Clickwhale_Tools $tools;

	/**
	 * @var Clickwhale_Reset
	 */
	public Clickwhale_Reset $reset;

	/**
	 * @var Clickwhale_Ajax
	 */
	public Clickwhale_Ajax $ajax;

	/**
	 * @var Clickwhale_Link_Edit
	 */
	public Clickwhale_Link_Edit $link;

	/**
	 * @var Clickwhale_Category_Edit
	 */
	public Clickwhale_Category_Edit $category;

	/**
	 * @var Clickwhale_Linkpage_Edit
	 */
	public Clickwhale_Linkpage_Edit $linkpage;

	/**
	 * @var Clickwhale_Tracking_Code_Edit
	 */
	public Clickwhale_Tracking_Code_Edit $tracking_code;

	/**
	 * @var Clickwhale_Smart_Display_Edit
	 */
	public Clickwhale_Smart_Display_Edit $smart_display;

	/**
	 * @var Clickwhale_Smart_Display_Block
	 */
	public Clickwhale_Smart_Display_Block $smart_display_block;

	/**
	 * @var Clickwhale_Smart_Display_Scheduler
	 */
	public Clickwhale_Smart_Display_Scheduler $smart_display_scheduler;

	/**
	 * @var Clickwhale_Public
	 */
	public Clickwhale_Public $public;

	/**
	 * @var Clickwhale_Public_Ajax
	 */
	public Clickwhale_Public_Ajax $public_ajax;

	/**
	 * @var Clickwhale_Rest_Controller
	 */
	public Clickwhale_Rest_Controller $rest_api;

	/**
	 * Gets an instance of our plugin.
	 *
	 * @return Clickwhale
	 *
	 * @since 1.5.0
	 */
	public static function get_instance(): Clickwhale {
		if ( empty( self::$instance ) ) {
			self::$instance                      = new self();
			self::$instance->loader              = new Clickwhale_Loader();
			self::$instance->user                = new Clickwhale_WP_User();
			self::$instance->admin               = Clickwhale_Admin::get_instance();
			self::$instance->settings            = Clickwhale_Settings::get_instance();
			self::$instance->tools               = new Clickwhale_Tools();
			self::$instance->reset               = Clickwhale_Reset::get_instance();
			self::$instance->ajax                = Clickwhale_Ajax::get_instance();
			self::$instance->link                = new Clickwhale_Link_Edit();
			self::$instance->category            = new Clickwhale_Category_Edit();
			self::$instance->linkpage            = new Clickwhale_Linkpage_Edit();
			self::$instance->tracking_code       = new Clickwhale_Tracking_Code_Edit();
			self::$instance->smart_display           = new Clickwhale_Smart_Display_Edit();
			self::$instance->smart_display_block     = new Clickwhale_Smart_Display_Block();
			self::$instance->smart_display_scheduler = new Clickwhale_Smart_Display_Scheduler();
			self::$instance->public              = Clickwhale_Public::get_instance();
			self::$instance->public_ajax         = Clickwhale_Public_Ajax::get_instance();
			self::$instance->rest_api            = new Clickwhale_Rest_Controller();

			self::$instance->define_admin_hooks();
			self::$instance->define_public_hooks();
		}

		return self::$instance;
	}

	/**
	 * Define the core functionality of the plugin.
	 *
	 * @since    1.0.0
	 */
	private function __construct() {
	}

	use Singleton_Clone;
	use Singleton_Wakeup;


	/**
	 * Register all the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {
		/**
		 * ACTIONS
		 */
		$this->loader->add_action( 'admin_menu', $this->admin, 'add_plugin_menu' );
		$this->loader->add_action( 'clickwhale_menu_after_all', $this->admin, 'show_pro_menu_item' );
		$this->loader->add_action( 'admin_init', $this->settings, 'add_default_options' );
		$this->loader->add_action( 'admin_init', $this->settings, 'add_settings_fields' );
		$this->loader->add_action( 'admin_init', $this->settings, 'filter_settings_tabs_capability' );
		$this->loader->add_action( 'admin_head', $this->admin, 'hide_notice_on_upgrade_to_pro_page', 99 );
		$this->loader->add_action( 'admin_enqueue_scripts', $this->admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $this->admin, 'enqueue_scripts' );
		$this->loader->add_action( 'admin_print_footer_scripts', $this->admin, 'admin_scripts' );
		$this->loader->add_action( 'clickwhale_admin_banner', $this->admin, 'admin_banner' );
		$this->loader->add_action( 'clickwhale_admin_banner_pro_button', $this->admin, 'admin_banner_pro_button' );
		$this->loader->add_action( 'clickwhale_admin_pro_message', $this->admin, 'admin_pro_message' );
		$this->loader->add_action( 'clickwhale_admin_sidebar_begin', $this->admin, 'admin_sidebar_begin' );
		$this->loader->add_action( 'clickwhale_admin_sidebar_end', $this->admin, 'admin_sidebar_end' );
		$this->loader->add_action( 'clickwhale_admin_sidebar_area', $this->admin, 'admin_widget_docs' );
		$this->loader->add_action( 'clickwhale_admin_sidebar_area', $this->admin, 'admin_widget_upgrade' );
		$this->loader->add_action( 'admin_bar_menu', $this, 'admin_bar_render', 999 );
		$this->loader->add_action( 'admin_post_clickwhale_pro_subscription_action', $this->admin, 'pro_subscription_action' );
		$this->loader->add_action( 'wp_ajax_clickwhale/admin/migration_notice_hide', $this->ajax, 'migration_notice_hide' );
		$this->loader->add_action( 'wp_ajax_clickwhale/admin/migration_deactive', $this->ajax, 'migration_deactive' );
		$this->loader->add_action( 'wp_ajax_clickwhale/admin/migration_to_clickwhale', $this->ajax, 'migration_to_clickwhale' );
		$this->loader->add_action( 'wp_ajax_clickwhale/admin/save_migration_option', $this->ajax, 'save_migration_option' );
		$this->loader->add_action( 'wp_ajax_clickwhale/admin/migration_reset', $this->ajax, 'migration_reset' );
		$this->loader->add_action( 'wp_ajax_clickwhale/admin/clickwhale_reset', $this->ajax, 'clickwhale_reset' );
		$this->loader->add_action( 'wp_ajax_clickwhale/admin/sanitize_slug', $this->ajax, 'sanitize_slug' );
		$this->loader->add_action( 'wp_ajax_clickwhale/admin/slug_exists', $this->ajax, 'slug_exists' );
		$this->loader->add_action( 'wp_ajax_clickwhale/admin/scan_links', $this->ajax, 'scan_links' );
		$this->loader->add_action( 'wp_ajax_clickwhale/admin/get_posts_by_post_type', $this->ajax, 'get_posts_by_post_type' );
		$this->loader->add_action( 'wp_ajax_clickwhale/admin/get_cw_links', $this->ajax, 'get_cw_links' );
		$this->loader->add_action( 'wp_ajax_clickwhale/admin/tracking_code_toggle_active', $this->ajax, 'tracking_code_toggle_active' );
		$this->loader->add_action( 'wp_ajax_clickwhale/admin/add_link_to_linkpage', $this->ajax, 'add_link_to_linkpage' );
		$this->loader->add_action( 'wp_ajax_clickwhale/admin/upload_csv', $this->ajax, 'upload_csv' );
		$this->loader->add_action( 'wp_ajax_clickwhale/admin/map_csv', $this->ajax, 'map_csv' );
		$this->loader->add_action( 'wp_ajax_clickwhale/admin/check_slug_for_import', $this->ajax, 'check_slug_for_import' );
		$this->loader->add_action( 'wp_ajax_clickwhale/admin/import_csv', $this->ajax, 'import_csv' );
		$this->loader->add_action( 'wp_ajax_clickwhale/admin/export_csv', $this->ajax, 'export_csv' );
		$this->loader->add_action( 'wp_ajax_clickwhale/admin/select_link', $this->ajax, 'select_link' );
		$this->loader->add_action( 'wp_ajax_clickwhale/admin/amz_connect_validate_api_key', $this->ajax, 'amz_connect_validate_api_key' );
		$this->loader->add_action( 'wp_ajax_clickwhale/admin/amz_connect_get_product', $this->ajax, 'amz_connect_get_product' );
		$this->loader->add_action( 'wp_ajax_clickwhale/admin/amz_connect_get_quota', $this->ajax, 'amz_connect_get_quota' );
		$this->loader->add_action( 'admin_init', $this->reset, 'initialize_reset_settings_options' );
		$this->loader->add_action( 'admin_init', $this->reset, 'initialize_reset_db_options' );
		$this->loader->add_action( 'admin_init', $this->reset, 'initialize_reset_stats_options' );
		$this->loader->add_action( 'admin_print_footer_scripts', $this->reset, 'admin_scripts' );
		$this->loader->add_action( 'clickwhale_link_after_tabs_content', $this->link, 'after_tabs_content', 25 );
		$this->loader->add_action( 'rest_api_init', $this->rest_api, 'register_routes' );
		$this->loader->add_action( 'init', $this->smart_display_block, 'register' );
		$this->loader->add_action( 'enqueue_block_editor_assets', $this->smart_display_block, 'enqueue_block_editor_assets' );
		$this->loader->add_filter( 'block_categories_all', $this->smart_display_block, 'register_block_category' );
		$this->smart_display_scheduler->register_hooks();

		/**
		 * FILTERS
		 */
		clickwhale_fs()->add_filter( 'templates/pricing.php', array( $this->admin, 'enqueue_fs_pricing_styles' ) );
		clickwhale_fs()->add_filter( 'plugin_icon', array( $this->admin, 'override_fs_plugin_icon' ) );
		$this->loader->add_filter( 'plugin_action_links_' . CLICKWHALE_ID, $this->admin, 'settings_action_link' );
		$this->loader->add_filter( 'plugin_action_links_' . CLICKWHALE_ID, $this->admin, 'upgrade_action_link' );
		$this->loader->add_filter( 'plugin_row_meta', $this->admin, 'plugin_meta_links', 10, 2 );
		$this->loader->add_filter( 'sanitize_option_clickwhale_link_manager_options', $this->settings, 'sanitize_link_manager_options' );
		$this->loader->add_filter( 'sanitize_option_clickwhale_smart_displays_options', $this->settings, 'sanitize_smart_displays_options' );
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {
		/**
		 * ACTIONS
		 */
		$this->loader->add_action( 'admin_bar_menu', $this, 'admin_bar_render', 999 );
		$this->loader->add_action( 'wp_enqueue_scripts', $this->public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $this->public, 'enqueue_scripts' );
		$this->loader->add_action( 'wp_enqueue_scripts', $this->public, 'enqueue_smart_display_styles' );
		$this->loader->add_action( 'init', $this->public, 'do_redirect_handler' );
		$this->loader->add_action( 'wp_ajax_clickwhale/public/track_custom_link', $this->public_ajax, 'track_custom_link' );
		$this->loader->add_action( 'wp_ajax_nopriv_clickwhale/public/track_custom_link', $this->public_ajax, 'track_custom_link' );

		$this->loader->add_shortcode( 'cw_smart_display', $this->public, 'render_smart_display_shortcode' );
		$this->loader->add_shortcode( 'cw_link_disclosure', $this->public, 'render_disclosure_shortcode' );

		/**
		 * FILTERS
		 */
		$this->loader->add_filter( 'the_content', $this->public, 'add_target_to_clickwhale_link' );
		$this->loader->add_filter( 'the_content', $this->public, 'maybe_add_disclosure_to_content', 12 );
		$this->loader->add_action( 'wp_head', $this->public, 'output_disclosure_tooltip_css' );
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 * @since    1.0.0
	 */
	public function run() {
		self::$instance->loader->run();
	}

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
	 * Provides default options.
	 * @return array
	 */

	public function default_options(): array {
		return array(
			'general'        => array(
				'name'    => __( 'General Options', 'clickwhale' ),
				'text'    => __( 'Set up ClickWhale plugin global options.', 'clickwhale' ),
				'options' => array(
					'access_level'        => array( 'administrator' ),
					'hide_admin_bar_menu' => 0
				)
			),
			'tracking'       => array(
				'name'    => __( 'Tracking Options', 'clickwhale' ),
				'text'    => __( 'Set up ClickWhale plugin global link tracking options.', 'clickwhale' ),
				'options' => array(
					'tracking_duration'    => 30,
					'exclude_user_by_role' => array( 'administrator' )
				)
			),
			'linkpages'      => array(
				'name'    => __( 'Link Pages Options', 'clickwhale' ),
				'text'    => __( 'Global settings for the Link Pages.', 'clickwhale' ),
				'options' => array(
					'show_linkpage_credits' => 0,
					'custom_css'            => '',
					'custom_js'             => ''
				)
			),
			'link_manager'   => array(
				'name'    => __( 'Link Manager Options', 'clickwhale' ),
				'text'    => __( 'Global settings for ClickWhale Links.', 'clickwhale' ),
				'options' => array(
					'redirect_type'        => 301,
					'link_target'          => 'blank',
					'nofollow'             => 1,
					'sponsored'            => 0,
					'slug'                 => '',
					'random_slug'          => 0,
					'show_asterisk'        => 0,
					'disclosure_text'      => 'This post contains affiliate links. I may earn a commission if you click through and make a purchase, at no additional cost to you.',
					'disclosure_position' => 'none'
				)
			),
			/* @since 2.7.0 */
			'smart_displays' => array(
				'name'    => __( 'Smart Displays Options', 'clickwhale' ),
				'text'    => __( 'Global settings for the Smart Displays.', 'clickwhale' ),
				'options' => array(
					'container'  => array(
						'border'     => array(
							'width'       => array(
								'value' => 0,
								'min'   => 0,
								'max'   => 10
							),
							'style'       => 'none',
							'radius'      => array(
								'value' => 8,
								'min'   => 0,
								'max'   => 200
							),
							'color'       => '',
							'color_hover' => ''
						),
						'padding'    => array(
							'value' => 16,
							'min'   => 0,
							'max'   => 200
						),
						'box_shadow' => '0 .25rem 1rem rgba(0, 0, 0, 0.05)'
					),
					'primary'    => array(
						'text'           => esc_html__( 'Primary Button', 'clickwhale' ),
						'color'          => '#ffffff',
						'color_hover'    => '#ffffff',
						'bg_color'       => '#397eff',
						'bg_color_hover' => '#1a1c1d',
						'border'         => array(
							'width'       => array(
								'value' => 0,
								'min'   => 0,
								'max'   => 10
							),
							'style'       => 'none',
							'color'       => '',
							'color_hover' => '',
							'radius'      => array(
								'value' => 10,
								'min'   => 0,
								'max'   => 20
							)
						)
					),
					'disclosure' => '',
				)
			),
			/* @since 2.8.0 */
			'integrations'   => array(
				'type'    => 'multiple',
				'name'    => __( 'Integrations Options', 'clickwhale' ),
				'text'    => __( 'Connect ClickWhale with the third-party services.', 'clickwhale' ),
				'options' => array(
					'amz_connect' => array(
						'name'            => __( 'AMZ Connect', 'clickwhale' ),
						'description'     => __( 'Amazon product data for Smart Displays', 'clickwhale' ),
						'url'             => esc_url( 'https://amzconnect.io/?utm_source=clickwhale&utm_medium=link&utm_campaign=clickwhale_integrations&utm_content=plugin_settings_page' ),
						'image'           => CLICKWHALE_ADMIN_ASSETS_DIR . '/images/integrations/amz-connect-icon.png',
						'entities'        => array( 'smart_displays' ),
						'about'           => array(
							'text'        => __( 'Fetches live Amazon product data (title, image, price, buy link) into your Smart Displays and keeps it up to date automatically.', 'clickwhale' ),
							'description' => '',
						),
						// plugin settings page
						'settings_fields' => array(
							array( 'id' => 'about', 'type' => 'about', 'label' => __( 'About', 'clickwhale' ) ),
							array( 'id' => 'api_key', 'type' => 'api_key', 'label' => __( 'API key', 'clickwhale' ) ),
							array(
								'id'               => 'default_store',
								'type'             => 'select',
								'label'            => __( 'Default store', 'clickwhale' ),
								'options_callback' => 'get_amz_connect_stores'
							),
							array(
								'id'    => 'tracking_id',
								'type'  => 'input',
								'label' => __( 'Tracking ID', 'clickwhale' )
							),
							array(
								'id'    => 'button_text',
								'type'  => 'input',
								'label' => __( 'Default button text', 'clickwhale' )
							),
							array( 'id' => 'quota', 'type' => 'quota' ),
						),
						// templates/[entity]/edit page
						'entity_fields'   => array(
							array( 'id' => 'asin', 'type' => 'input', 'label' => __( 'ASIN', 'clickwhale' ) ),
							array(
								'id'               => 'store',
								'type'             => 'select',
								'label'            => __( 'Store', 'clickwhale' ),
								'options_callback' => 'get_amz_connect_stores'
							),
						),
						// default settings
						'defaults'        => array(
							'default_store'     => 'com',
							'button_text'       => __( 'Buy on Amazon', 'clickwhale' ),
							'tracking_id'       => '',
							'max_list_items'    => 3,
							'overflow_behavior' => 'truncate',
						),
					)
				)
			)
		);
	}

	/**
	 * @return void
	 * @since 1.3.0
	 */
	public function admin_bar_render( $wp_admin_bar ) {
		if ( Helper::get_clickwhale_option( 'general', 'hide_admin_bar_menu' ) ) {
			return;
		}

		if ( ! self::$instance->user->is_current_user_role_access_granted() ) {
			return;
		}

		$wp_admin_bar->add_node( array(
				'id'    => esc_attr( CLICKWHALE_SLUG ),
				'title' => '<span class="ab-icon"><img src="' . CLICKWHALE_ADMIN_ASSETS_DIR . '/images/click-icon.svg"/></span> ClickWhale',
				'href'  => esc_url( admin_url( 'admin.php?page=' . CLICKWHALE_SLUG ) ),
				'meta'  => array(
					'class' => esc_attr( CLICKWHALE_SLUG ),
					'title' => 'ClickWhale'
				)
			)
		);

		$wp_admin_bar->add_node( array(
				'id'     => esc_attr( CLICKWHALE_SLUG ) . '-new-link',
				'title'  => esc_html__( 'New Link', 'clickwhale' ),
				'href'   => esc_url( admin_url( 'admin.php?page=' . CLICKWHALE_SLUG . '-edit-link&id=0' ) ),
				'parent' => esc_attr( CLICKWHALE_SLUG ),
				'meta'   => array(
					'class' => esc_attr( CLICKWHALE_SLUG ) . '-new-link',
					'title' => esc_html__( 'Add New Link', 'clickwhale' )
				)
			)
		);

		$wp_admin_bar->add_node( array(
				'id'     => esc_attr( CLICKWHALE_SLUG ) . '-new-category',
				'title'  => esc_html__( 'New Category', 'clickwhale' ),
				'href'   => esc_url( admin_url( 'admin.php?page=' . CLICKWHALE_SLUG . '-edit-category&id=0' ) ),
				'parent' => esc_attr( CLICKWHALE_SLUG ),
				'meta'   => array(
					'class' => esc_attr( CLICKWHALE_SLUG ) . '-new-category',
					'title' => esc_html__( 'Add New Category', 'clickwhale' )
				)
			)
		);

		$wp_admin_bar->add_node( array(
				'id'     => esc_attr( CLICKWHALE_SLUG ) . '-new-linkpage',
				'title'  => esc_html__( 'New Link Page', 'clickwhale' ),
				'href'   => esc_url( admin_url( 'admin.php?page=' . CLICKWHALE_SLUG . '-edit-linkpage&id=0' ) ),
				'parent' => esc_attr( CLICKWHALE_SLUG ),
				'meta'   => array(
					'class' => esc_attr( CLICKWHALE_SLUG ) . '-new-linkpage',
					'title' => esc_html__( 'Add New Link Page', 'clickwhale' )
				)
			)
		);

		$wp_admin_bar->add_node( array(
				'id'     => esc_attr( CLICKWHALE_SLUG ) . '-new-tracking-code',
				'title'  => esc_html__( 'New Tracking Code', 'clickwhale' ),
				'href'   => esc_url( admin_url( 'admin.php?page=' . CLICKWHALE_SLUG . '-edit-tracking-code&id=0' ) ),
				'parent' => esc_attr( CLICKWHALE_SLUG ),
				'meta'   => array(
					'class' => esc_attr( CLICKWHALE_SLUG ) . '-new-tracking-code',
					'title' => esc_html__( 'Add New Tracking Code', 'clickwhale' )
				)
			)
		);

		$wp_admin_bar->add_node( array(
				'id'     => CLICKWHALE_SLUG . '-new-smart-display',
				'title'  => __( 'New Smart Display', 'clickwhale' ),
				'href'   => esc_url( admin_url( 'admin.php?page=' . CLICKWHALE_SLUG . '-edit-smart-display&id=0' ) ),
				'parent' => CLICKWHALE_SLUG,
				'meta'   => array(
					'class' => CLICKWHALE_SLUG . '-new-smart-display',
					'title' => __( 'Add New Smart Display', 'clickwhale' )
				)
			)
		);
	}
}
