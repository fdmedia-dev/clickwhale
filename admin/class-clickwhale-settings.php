<?php

/**
 * The settings of the plugin.
 *
 * @link       #
 * @since      1.0.0
 *
 * @package    Clickwhale
 * @subpackage Clickwhale/admin
 */
class Clickwhale_Admin_Settings {
	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $plugin_name The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $version The current version of this plugin.
	 */
	private $version;

	/**
	 * @var Clickwhale_Admin_Settings
	 */
	private static Clickwhale_Admin_Settings $instance;

	public $menus;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {

		$this->plugin_name = CLICKWHALE_NAME;
		$this->version     = CLICKWHALE_VERSION;

	}

	/**
	 * @return Clickwhale_Admin_Settings
	 */
	public static function getInstance(): Clickwhale_Admin_Settings {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Provides default values Options.
	 *
	 * @return array
	 */
	public function default_options(): array {
		return array(
			'general'        => array(
				'name'    => __( 'General', $this->plugin_name ),
				'text'    => __( 'Set up ClickWhale plugin global options.', $this->plugin_name ),
				'options' => array(
					'redirect_type' => 301,
					'nofollow'      => 1,
					'sponsored'     => 0,
					'slug'          => '',
					'random_slug'   => 0,
				)
			),
			'tracking'       => array(
				'name'    => __( 'Tracking', $this->plugin_name ),
				'text'    => __( 'Set up ClickWhale plugin global link tracking options.', $this->plugin_name ),
				'options' => array(
					'tracking_duration'    => 30,
					'disable_tracking'     => 0,
					'exclude_user_by_role' => [ 'administrator' ]
				)
			),
			'linkpages'      => array(
				'name'    => __( 'Link Pages', $this->plugin_name ),
				'text'    => __( 'Global settings for the Link Pages.', $this->plugin_name ),
				'options' => array(
					'linkpage_links_target' => 0
				)
			),
			'tracking_codes' => array(
				'name'    => __( 'Tracking Codes', $this->plugin_name ),
				'text'    => __( 'Global settings for the Tracking Codes.', $this->plugin_name ),
				'options' => array()
			),
			'other'          => array(
				'name'    => __( 'Other', $this->plugin_name ),
				'text'    => __( 'Set up other ClickWhale plugin useful options.', $this->plugin_name ),
				'options' => array()
			)
		);
	}

	public function add_default_options() {
		/* @Since 1.2.1 */
		if ( ! get_option( 'clickwhale_version' ) ) {
			add_option( 'clickwhale_version', CLICKWHALE_VERSION );
		}

		/* @Since 1.0.0 */
		$defaults = apply_filters( 'clickwhale_settings_defaults', $this->default_options() );

		foreach ( $defaults as $k => $v ) {
			$option_name = 'clickwhale_' . $k . '_options';
			if ( ! get_option( $option_name ) ) {
				add_option( $option_name, $v['options'] );
			}
		}
	}

	/**
	 * This function introduces the theme options into the 'Settings' menu and into a top-level
	 * 'Clickwhale' menu.
	 */
	public function add_plugin_menu() {

		$this->menus = array(
			'subpages' => array(
				'links'              => __( 'Links', $this->plugin_name ),
				'edit-link'          => __( 'Add New', $this->plugin_name ),
				'categories'         => __( 'Categories', $this->plugin_name ),
				'edit-category'      => __( 'Add New Category', $this->plugin_name ),
				'linkpages'          => __( 'Link Pages', $this->plugin_name ),
				'edit-linkpage'      => __( 'Add New Link Page', $this->plugin_name ),
				'tracking-codes'     => __( 'Tracking Codes', $this->plugin_name ),
				'edit-tracking-code' => __( 'Add New Tracking Code', $this->plugin_name )
			),
			'views'    => array(
				'toplevel_page_clickwhale'                  => 'links/links-list-table',
				'admin_page_clickwhale-edit-link'           => 'links/link-edit',
				'clickwhale_page_clickwhale-categories'     => 'categories/categories-list-table',
				'admin_page_clickwhale-edit-category'       => 'categories/category-edit',
				'clickwhale_page_clickwhale-linkpages'      => 'linkpages/linkpages-list-table',
				'admin_page_clickwhale-edit-linkpage'       => 'linkpages/linkpage-edit',
				'clickwhale_page_clickwhale-tracking-codes' => 'tracking-codes/tracking-codes-list-table',
				'admin_page_clickwhale-edit-tracking-code'  => 'tracking-codes/tracking-code-edit',
			),
			'toplevel' => array( 'links', 'categories', 'linkpages', 'tracking-codes' ),
		);

		$this->menus = apply_filters( 'clickwhale_menus', $this->menus );

		add_menu_page(
			__( 'ClickWhale Links', $this->plugin_name ),
			__( 'ClickWhale', $this->plugin_name ),
			'edit_pages',
			$this->plugin_name,
			'',
			plugin_dir_url( __FILE__ ) . 'images/click-icon.svg',
			26
		);

		foreach ( $this->menus['subpages'] as $k => $v ) {
			$parent = in_array( $k, $this->menus['toplevel'] ) ? $this->plugin_name : '';

			add_submenu_page(
				$parent,
				$v,
				$v,
				'edit_pages',
				$k !== 'links' ? $this->plugin_name . '-' . $k : $this->plugin_name,
				array( $this, 'get_view' )
			);
		}

		/**
		 * @since 1.3.6
		 */
		do_action( 'clickwhale_menu_before_settings' );

		add_submenu_page(
			$this->plugin_name,
			__( 'Settings', $this->plugin_name ),
			__( 'Settings', $this->plugin_name ),
			'manage_options',
			$this->plugin_name . '-settings',
			array( $this, 'render_settings_page_view' )
		);
		add_submenu_page(
			$this->plugin_name,
			__( 'Tools', $this->plugin_name ),
			__( 'Tools', $this->plugin_name ),
			'manage_options',
			$this->plugin_name . '-tools',
			array( $this, 'render_tools_page_view' )
		);
	}

	/**
	 * This function renders the interface elements.
	 */

	public function render_controls( $args ) {
		echo ClickwhaleHepler::render_control( $args );
	}

	/**
	 * Include Menu Partial
	 *
	 * @since    1.0.0
	 */

	public function render_settings_page_view() {
		include_once( plugin_dir_path( dirname( __FILE__ ) ) . 'admin/views/settings/settings.php' );
	}

	public function render_tools_page_view() {
		include_once( plugin_dir_path( dirname( __FILE__ ) ) . 'admin/views/tools/tools.php' );
	}

	/**
	 * @return void
	 * @since 1.3.0
	 */
	public function get_view() {
		$current_views = $this->menus['views'][ current_filter() ];
		include_once( plugin_dir_path( dirname( __FILE__ ) ) . 'admin/views/' . $current_views . '.php' );
	}

	/**
	 * Initializes the plugin settings options page by registering the Sections,
	 * Fields, and Settings.
	 *
	 * This function is registered with the 'admin_init' hook.
	 * @since 1.0.0
	 */
	public function add_settings_fields() {

		$defaults               = apply_filters( 'clickwhale_settings_defaults', $this->default_options() );
		$general_options        = get_option( 'clickwhale_general_options' );
		$tracking_options       = get_option( 'clickwhale_tracking_options' );
		$linkpages_options      = get_option( 'clickwhale_linkpages_options' );
		$tracking_codes_options = get_option( 'clickwhale_tracking_codes_options' );
		$other_options          = get_option( 'clickwhale_other_options' );
		$duration               = apply_filters( 'clickwhale_tracking_duration', array(
			30 => __( '30 days', $this->plugin_name ),
		) );

		if ( $defaults ) {
			// add settings sections
			// register settings
			foreach ( $defaults as $k => $v ) {

				if ( ! $v['options'] ) {
					continue;
				}

				add_settings_section(
					$k . '_settings_section',
					$v['name'],
					array( $this, 'settings_section_callback' ),
					'clickwhale_' . $k . '_options',
					array( 'text' => $v['text'] )
				);

				register_setting(
					'clickwhale_' . $k . '_options',
					'clickwhale_' . $k . '_options'
				);

			}
		}

		// Add fields
		add_settings_field(
			'redirection',
			__( 'Redirection Type', $this->plugin_name ),
			array( $this, 'render_controls' ),
			'clickwhale_general_options',
			'general_settings_section',
			array(
				'control'     => 'select',
				'id'          => 'redirect_type',
				'name'        => 'clickwhale_general_options[redirect_type]',
				'value'       => isset( $general_options['redirect_type'] ) && $general_options['redirect_type'] !== '' && is_int( $general_options['redirect_type'] ) ? $general_options['redirect_type'] : $defaults['general']['options']['redirect_type'],
				'options'     => array(
					301 => __( '301 redirect: Moved permanently', $this->plugin_name ),
					302 => __( '302 redirect: Found / Moved temporarily', $this->plugin_name ),
					303 => __( '303 redirect: See Other', $this->plugin_name ),
					307 => __( '307 redirect: Temporarily Redirect', $this->plugin_name ),
					308 => __( '308 redirect: Permanent Redirect', $this->plugin_name )
				),
				'description' => __( 'Set default redirection type which will be used for new links.',
					$this->plugin_name ),
			)
		);
		add_settings_field(
			'nofollow',
			__( 'Nofollow Links', $this->plugin_name ),
			array( $this, 'render_controls' ),
			'clickwhale_general_options',
			'general_settings_section',
			array(
				'control' => 'checkbox',
				'id'      => 'nofollow',
				'name'    => 'clickwhale_general_options[nofollow]',
				'value'   => isset( $general_options['nofollow'] ) ? 1 : 0,
				'label'   => __( 'Check to mark links as nofollow & noindex by default', $this->plugin_name ),
			)
		);
		add_settings_field(
			'sponsored',
			__( 'Sponsored Links', $this->plugin_name ),
			array( $this, 'render_controls' ),
			'clickwhale_general_options',
			'general_settings_section',
			array(
				'control'     => 'checkbox',
				'id'          => 'sponsored',
				'name'        => 'clickwhale_general_options[sponsored]',
				'value'       => isset( $general_options['sponsored'] ) ? 1 : 0,
				'label'       => __( 'Check to mark links as sponsored by default.', $this->plugin_name ),
				'description' => __( 'Recommended for affiliate links.', $this->plugin_name ),
			)
		);
		add_settings_field(
			'slug',
			__( 'Link Prefix', $this->plugin_name ),
			array( $this, 'render_controls' ),
			'clickwhale_general_options',
			'general_settings_section',
			array(
				'control'     => 'input',
				'id'          => 'slug',
				'name'        => 'clickwhale_general_options[slug]',
				'type'        => 'text',
				'value'       => $general_options['slug'],
				'placeholder' => '',
				'description' => __( 'Here, you can enter a prefix that will be prepended when creating a new link. For example: <em>link</em>.<br><strong>Important:</strong> If you change the prefix, it will <u>not</u> affect already existing links.',
					$this->plugin_name ),
			)
		);
		add_settings_field(
			'random_slug',
			__( 'Random Slug', $this->plugin_name ),
			array( $this, 'render_controls' ),
			'clickwhale_general_options',
			'general_settings_section',
			array(
				'control' => 'checkbox',
				'id'      => 'random_slug',
				'name'    => 'clickwhale_general_options[random_slug]',
				'value'   => isset( $general_options['random_slug'] ) ? 1 : 0,
				'label'   => __( 'Check to <u>not</u> suggest a random link slug when creating a new link',
					$this->plugin_name ),
			)
		);
		add_settings_field(
			'tracking_duration',
			__( 'Tracking Duration', $this->plugin_name ),
			array( $this, 'render_controls' ),
			'clickwhale_tracking_options',
			'tracking_settings_section',
			array(
				'control' => 'select',
				'id'      => 'tracking_duration',
				'name'    => 'clickwhale_tracking_options[tracking_duration]',
				'value'   => $tracking_options['tracking_duration'] ?? $defaults['tracking']['options']['tracking_duration'],
				'options' => $duration
			)
		);
		add_settings_field(
			'disable_tracking',
			__( 'Disable Tracking', $this->plugin_name ),
			array( $this, 'render_controls' ),
			'clickwhale_tracking_options',
			'tracking_settings_section',
			array(
				'control' => 'checkbox',
				'id'      => 'disable_tracking',
				'name'    => 'clickwhale_tracking_options[disable_tracking]',
				'value'   => isset( $tracking_options['disable_tracking'] ) ? 1 : 0,
				'label'   => __( 'Check to disable tracking of views and clicks', $this->plugin_name ),
			)
		);
		add_settings_field(
			'exclude_user_by_role',
			__( 'Exclude User Roles', $this->plugin_name ),
			array( $this, 'render_controls' ),
			'clickwhale_tracking_options',
			'tracking_settings_section',
			array(
				'control'     => 'checkboxes',
				'id'          => 'exclude_user_by_role',
				'name'        => 'clickwhale_tracking_options[exclude_user_by_role][]',
				'value'       => $tracking_options['exclude_user_by_role'] ?? 0,
				'options'     => Clickwhale_WP_User::get_all_roles(),
				'description' => __( 'Check the user roles that should be excluded from tracking.',
					$this->plugin_name ),
			)
		);
		add_settings_field(
			'linkpage_links_target',
			__( 'Links: Target', $this->plugin_name ),
			array( $this, 'render_controls' ),
			'clickwhale_linkpages_options',
			'linkpages_settings_section',
			array(
				'control' => 'checkbox',
				'id'      => 'linkpage_links_target',
				'name'    => 'clickwhale_linkpages_options[linkpage_links_target]',
				'value'   => isset( $linkpages_options['linkpage_links_target'] ) ? 1 : 0,
				'label'   => __( 'Check to open links in a new tab/window.', $this->plugin_name ),
			)
		);

		apply_filters( 'clickwhale_settings_fields', '' );
	}

	/**
	 * This functions provides a simple description for the Options page.
	 * @since 1.0.0
	 */
	public static function settings_section_callback( $args ) {
		echo '<p>' . $args['text'] . '</p>';
	}

	/**
	 * Render plugin settings tabs
	 * Hook: Filter 'clickwhale_settings_tabs';
	 * @return mixed|null
	 *
	 * @since 1.3.0
	 */
	public static function render_tabs() {
		$defaults = apply_filters( 'clickwhale_settings_defaults', ( new self )->default_options() );
		$tabs     = array(
			'general'        => array(
				'name' => __( 'General Options', ( new self )->plugin_name ),
				'url'  => 'general_options',
			),
			'tracking'       => array(
				'name' => __( 'Tracking Options', ( new self )->plugin_name ),
				'url'  => 'tracking_options'
			),
			'linkpages'      => array(
				'name' => __( 'Link Pages', ( new self )->plugin_name ),
				'url'  => 'linkpages_options'
			),
			'tracking_codes' => array(
				'name' => __( 'Tracking Codes', ( new self )->plugin_name ),
				'url'  => 'tracking_codes_options'
			),
			'other'          => array(
				'name' => __( 'Other Options', ( new self )->plugin_name ),
				'url'  => 'other_options'
			),
		);

		$tabs = apply_filters( 'clickwhale_settings_tabs', $tabs );

		foreach ( $tabs as $k => $v ) {
			if ( ! $defaults[ $k ]['options'] ) {
				unset ( $tabs[ $k ] );
			}
		}

		return $tabs;
	}
}