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
	private static $instance;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param string $plugin_name The name of this plugin.
	 * @param string $version The version of this plugin.
	 *
	 * @since    1.0.0
	 */
	public function init( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;

	}

	/**
	 * @return Clickwhale_Admin_Settings
	 */
	public static function getInstance() {
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
	public function default_options() {
		return array(
			'general'   => array(
				'name'    => __( 'General', $this->plugin_name ),
				'options' => array(
					'redirect_type' => 301,
					'nofollow'      => 1,
					'sponsored'     => 0,
					'slug'          => 'link'
				)
			),
			'tracking'  => array(
				'name'    => __( 'Tracking', $this->plugin_name ),
				'options' => array(
					'tracking_duration'                  => 30,
					'disable_tracking'             => 0,
					'exclude_user_link_click_by_role'    => [ 'administrator' ],
					'exclude_user_linkpage_view_by_role' => [ 'administrator' ]
				)
			),
			'linkpages' => array(
				'name'    => __( 'Link Pages', $this->plugin_name ),
				'options' => array(
					'linkpage_links_target' => 0
				)
			),
			'other'     => array(
				'name'    => __( 'Other', $this->plugin_name ),
				'options' => array(
					'affiliate_id' => ''
				)
			)
		);
	}

	/**
	 * This function introduces the theme options into the 'Settings' menu and into a top-level
	 * 'Clickwhale' menu.
	 */
	public function add_plugin_menu() {

		$subpages = array(
			array(
				'page_title' => 'Links',
				'menu_title' => 'Links',
				'slug'       => $this->plugin_name,
				'handler'    => '_links',
				'parent'     => $this->plugin_name,
			),
			array(
				'page_title' => 'Add New',
				'menu_title' => 'Add New Link',
				'slug'       => $this->plugin_name . '-edit-link',
				'handler'    => '_link_form'
			),
			array(
				'page_title' => 'Categories',
				'menu_title' => 'Categories',
				'slug'       => $this->plugin_name . '-categories',
				'handler'    => '_categories',
				'parent'     => $this->plugin_name,
			),
			array(
				'page_title' => 'Add New Category',
				'menu_title' => 'Add New Category',
				'slug'       => $this->plugin_name . '-edit-category',
				'handler'    => '_category_form'
			),
			array(
				'page_title' => 'Link Pages',
				'menu_title' => 'Link Pages',
				'slug'       => $this->plugin_name . '-linkpages',
				'handler'    => '_linkpages',
				'parent'     => $this->plugin_name,
			),
			array(
				'page_title' => 'Add New Link Page',
				'menu_title' => 'Add New Link Page',
				'slug'       => $this->plugin_name . '-edit-linkpage',
				'handler'    => '_linkpage_form'
			),
		);

		add_menu_page(
			__( 'Clickwhale Links', $this->plugin_name ),
			__( 'Clickwhale', $this->plugin_name ),
			'edit_pages',
			$this->plugin_name,
			'',
			plugin_dir_url( __FILE__ ) . 'images/click-icon.svg',
			26
		);

		foreach ( $subpages as $subpage ) {
			$parent = isset( $subpage['parent'] ) ? $subpage['parent'] : '';
			add_submenu_page(
				$parent,
				sprintf( __( '%1$s', $this->plugin_name ), $subpage['page_title'] ),
				sprintf( __( '%1$s', $this->plugin_name ), $subpage['menu_title'] ),
				'edit_pages',
				$subpage['slug'],
				array( $this, $this->plugin_name . $subpage['handler'] . '_page_handler' )
			);
		}

		add_submenu_page(
			$this->plugin_name,
			__( 'Settings', $this->plugin_name ),
			__( 'Settings', $this->plugin_name ),
			'manage_options',
			$this->plugin_name . '-settings',
			array( $this, 'render_settings_page_content' )
		);
		add_submenu_page(
			$this->plugin_name,
			__( 'Tools', $this->plugin_name ),
			__( 'Tools', $this->plugin_name ),
			'manage_options',
			$this->plugin_name . '-tools',
			array( $this, 'include_admin_menu_tools_partial' )
		);

	}

	/**
	 * Include Menu Partial
	 *
	 * @since    1.0.0
	 */
	public function clickwhale_links_page_handler() {
		include_once( plugin_dir_path( dirname( __FILE__ ) ) . 'admin/views/links/links-list-table.php' );
	}

	public function clickwhale_link_form_page_handler() {
		include_once( plugin_dir_path( dirname( __FILE__ ) ) . 'admin/views/links/link-edit.php' );
	}

	public function clickwhale_categories_page_handler() {
		include_once( plugin_dir_path( dirname( __FILE__ ) ) . 'admin/views/categories/categories-list-table.php' );
	}

	public function clickwhale_category_form_page_handler() {
		include_once( plugin_dir_path( dirname( __FILE__ ) ) . 'admin/views/categories/category-edit.php' );
	}

	public function clickwhale_linkpages_page_handler() {
		include_once( plugin_dir_path( dirname( __FILE__ ) ) . 'admin/views/linkpages/linkpages-list-table.php' );
	}

	public function clickwhale_linkpage_form_page_handler() {
		include_once( plugin_dir_path( dirname( __FILE__ ) ) . 'admin/views/linkpages/linkpage-edit.php' );
	}

	public function render_settings_page_content() {
		include_once( plugin_dir_path( dirname( __FILE__ ) ) . 'admin/views/settings/settings.php' );
	}

	public function include_admin_menu_tools_partial() {
		include_once( plugin_dir_path( dirname( __FILE__ ) ) . 'admin/views/tools/tools.php' );
	}

	/**
	 * This functions provides a simple description for the Options page.
	 */
	public function general_options_callback() {
		echo '<p>' . __( 'Set up ClickWhale plugin global options.', $this->plugin_name ) . '</p>';
	}

	public function tracking_options_callback() {
		echo '<p>' . __( 'Set up ClickWhale plugin global link tracking options.', $this->plugin_name ) . '</p>';
	}

	public function linkpages_options_callback() {
		echo '<p>' . __( 'Global settings for the Link Pages.', $this->plugin_name ) . '</p>';
	}

	public function other_options_callback() {
		echo '<p>' . __( 'Set up other ClickWhale plugin useful options.', $this->plugin_name ) . '</p>';
	}

	public function add_default_options() {
		if ( $this->default_options() ) {
			foreach ( $this->default_options() as $k => $v ) {
				$option_name = 'clickwhale_' . $k . '_options';
				if ( ! get_option( $option_name ) ) {
					add_option( $option_name, $v['options'] );
				}
			}
		}
	}

	/**
	 * Initializes the plugin settings options page by registering the Sections,
	 * Fields, and Settings.
	 *
	 * This function is registered with the 'admin_init' hook.
	 */
	public function add_settings_fields() {

		$defaults          = $this->default_options();
		$general_options   = get_option( 'clickwhale_general_options' );
		$tracking_options  = get_option( 'clickwhale_tracking_options' );
		$linkpages_options = get_option( 'clickwhale_linkpages_options' );
		$other_options     = get_option( 'clickwhale_other_options' );
		$duration          = apply_filters( 'clickwhale_tracking_duration', array(
			30 => __( '30 days', $this->plugin_name ),
		) );

		if ( $defaults ) {
			foreach ( $defaults as $k => $v ) {

				add_settings_section(
					$k . '_settings_section',
					$v['name'],
					array( $this, $k . '_options_callback' ),
					'clickwhale_' . $k . '_options'
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
				'description' => __( 'Set default redirection type which will be used for new links.', $this->plugin_name ),
			)
		);
		add_settings_field(
			'nofollow',
			__( 'Nofollow links', $this->plugin_name ),
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
			__( 'Sponsored links', $this->plugin_name ),
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
			__( 'Link slug', $this->plugin_name ),
			array( $this, 'render_controls' ),
			'clickwhale_general_options',
			'general_settings_section',
			array(
				'control'     => 'input',
				'id'          => 'slug',
				'name'        => 'clickwhale_general_options[slug]',
				'type'        => 'text',
				'value'       => isset( $general_options['slug'] ) && $general_options['slug'] !== '' ? $general_options['slug'] : $defaults['general']['options']['slug'],
				'placeholder' => 'link',
				'description' => __( '<strong>Important:</strong> Once you change the link slug, all existing links will be updated automatically.<br>You may have to update placed links in your content manually.', $this->plugin_name ),
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
				'value'   => isset( $tracking_options['tracking_duration'] ) ? $tracking_options['tracking_duration'] : $defaults['tracking']['options']['tracking_duration'],
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
				'label'   => __( 'Check to disable tracking', $this->plugin_name ),
			)
		);
		add_settings_field(
			'exclude_user_link_click_by_role',
			__( 'Exclude click tracking on links from roles', $this->plugin_name ),
			array( $this, 'render_controls' ),
			'clickwhale_tracking_options',
			'tracking_settings_section',
			array(
				'control'     => 'checkboxes',
				'id'          => 'exclude_user_link_click_by_role',
				'name'        => 'clickwhale_tracking_options[exclude_user_link_click_by_role][]',
				'value'       => isset( $tracking_options['exclude_user_link_click_by_role'] ) ? $tracking_options['exclude_user_link_click_by_role'] : 0,
				'options'     => Clickwhale_WP_User::get_all_roles(),
				'description' => __( 'Do not track clicks on links for users of specified roles', $this->plugin_name ),
			)
		);
		add_settings_field(
			'exclude_user_linkpage_view_by_role',
			__( 'Exclude view tracking on linkpage from roles', $this->plugin_name ),
			array( $this, 'render_controls' ),
			'clickwhale_tracking_options',
			'tracking_settings_section',
			array(
				'control'     => 'checkboxes',
				'id'          => 'exclude_user_linkpage_view_by_role',
				'name'        => 'clickwhale_tracking_options[exclude_user_linkpage_view_by_role][]',
				'value'       => isset( $tracking_options['exclude_user_linkpage_view_by_role'] ) ? $tracking_options['exclude_user_linkpage_view_by_role'] : 0,
				'options'     => Clickwhale_WP_User::get_all_roles(),
				'description' => __( 'Do not track linkpage views for users of specified roles', $this->plugin_name ),
			)
		);
		add_settings_field(
			'linkpage_links_target',
			__( 'Linkpage Links Target', $this->plugin_name ),
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
		add_settings_field(
			'affiliate_id',
			__( 'Affiliate ID', $this->plugin_name ),
			array( $this, 'render_controls' ),
			'clickwhale_other_options',
			'other_settings_section',
			array(
				'control'     => 'input',
				'id'          => 'affiliate_id',
				'name'        => 'clickwhale_other_options[affiliate_id]',
				'type'        => 'text',
				'value'       => isset( $other_options['affiliate_id'] ) ? $other_options['affiliate_id'] : $defaults['other']['options']['affiliate_id'],
				'placeholder' => '123456',
				'description' => __( 'Enter your Affiliate ID.', $this->plugin_name ),
			)
		);
	}

	/**
	 * This function renders the interface elements for toggling the visibility of the header element.
	 *
	 * It accepts an array or arguments and expects the first element in the array to be the description
	 * to be displayed next to the checkbox.
	 */

	public function render_controls( $args ) {
		echo ClickwhaleHepler::render_contol( $args );
	}
}