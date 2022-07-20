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

/**
 * Class WordPress_Plugin_Template_Settings
 *
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
	 * Initialize the class and set its properties.
	 *
	 * @param string $plugin_name The name of this plugin.
	 * @param string $version The version of this plugin.
	 *
	 * @since    1.0.0
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;

		// if custom slug doesn't isset we should add default value
		$options = get_option( 'clickwhale_general_options' );
		if ( ! isset( $options['slug'] ) || $options['slug'] === '' ) {
			$options['slug'] = 'link';
			update_option( 'clickwhale_general_options', $options );
		}

	}

	/**
	 * This function introduces the theme options into the 'Settings' menu and into a top-level
	 * 'Clickwhale' menu.
	 */
	public function setup_plugin_options_menu() {

		// add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $function, $icon_url, $position );
		add_menu_page(
			__( 'Clickwhale Links', $this->plugin_name ),   // page_title
			__( 'Clickwhale', $this->plugin_name ),         // menu title
			'manage_options',
			$this->plugin_name,
			'',
			plugin_dir_url( __FILE__ ) . 'images/click-icon.svg',
			26
		);
		add_submenu_page(
			$this->plugin_name,
			__( 'Clickwhale Links', $this->plugin_name ),   // page_title
			__( 'Links', $this->plugin_name ),              // menu title
			'manage_options',
			$this->plugin_name,
			array( $this, $this->plugin_name . '_links_page_handler' )
		);
		add_submenu_page(
			'',
			__( 'Add New', $this->plugin_name ),
			__( 'Add New Link', $this->plugin_name ),
			'manage_options',
			$this->plugin_name . '-edit-link',
			array( $this, $this->plugin_name . '_link_form_page_handler' )
		);
		add_submenu_page(
			$this->plugin_name,
			__( 'ClickWhale Categories', $this->plugin_name ),
			__( 'Categories', $this->plugin_name ),
			'manage_options',
			$this->plugin_name . '-categories',
			array( $this, $this->plugin_name . '_categories_page_handler' )
		);
		add_submenu_page(
			'',
			__( 'Add New Category', $this->plugin_name ),
			__( 'Add New Category', $this->plugin_name ),
			'manage_options',
			$this->plugin_name . '-edit-category',
			array( $this, $this->plugin_name . '_category_form_page_handler' )
		);
		add_submenu_page(
			$this->plugin_name,
			__( 'Clickwhale Link Pages', $this->plugin_name ),
			__( 'Link Pages', $this->plugin_name ),
			'manage_options',
			$this->plugin_name . '-linkpages',
			array( $this, $this->plugin_name . '_linkpages_page_handler' )
		);
		add_submenu_page(
			'',
			__( 'Add New Link Page', $this->plugin_name ),
			__( 'Add New Link Page', $this->plugin_name ),
			'manage_options',
			$this->plugin_name . '-edit-linkpage',
			array( $this, $this->plugin_name . '_linkpage_form_page_handler' )
		);
		add_submenu_page(
			$this->plugin_name,
			__( 'ClickWhale Settings', $this->plugin_name ),
			__( 'Settings', $this->plugin_name ),
			'manage_options',
			$this->plugin_name . '-settings',
			array( $this, 'render_settings_page_content' )
		);
		add_submenu_page(
			$this->plugin_name,
			__( 'ClickWhale Tools', $this->plugin_name ),
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
		include_once( plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/clickwhale-admin-links-list-table.php' );
	}

	public function clickwhale_link_form_page_handler() {
		include_once( plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/clickwhale-admin-link-edit.php' );
	}

	public function clickwhale_categories_page_handler() {
		include_once( plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/clickwhale-admin-categories-list-table.php' );
	}

	public function clickwhale_category_form_page_handler() {
		include_once( plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/clickwhale-admin-category-edit.php' );
	}

	public function clickwhale_linkpages_page_handler() {
		include_once( plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/clickwhale-admin-linkpages-list-table.php' );
	}

	public function clickwhale_linkpage_form_page_handler() {
		include_once( plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/clickwhale-admin-linkpage-edit.php' );
	}

	public function render_settings_page_content() {
		include_once( plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/clickwhale-admin-settings-display.php' );
	}

	public function include_admin_menu_tools_partial() {
		include_once( plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/clickwhale-admin-tools-display.php' );
	}


	/**
	 * Provides default values for the General Options.
	 *
	 * @return array
	 */
	public function default_general_options() {

		$defaults = array(
			'redirect_type' => 301,
			'nofollow'      => true,
			'sponsored'     => false,
		);

		return $defaults;

	}

	public function default_tracking_options() {

		$defaults = array(
			'disable_click_tracking' => false,
			'exclude_users_by_role'  => [ 'administrator' ],
		);

		return $defaults;

	}

	/**
	 * This function provides a simple description for the General Options page.
	 *
	 * It's called from the 'initialize_settings_options' function by being passed as a parameter
	 * in the add_settings_section function.
	 */
	public function general_options_callback() {
		$options = get_option( 'clickwhale_general_options' );
		//var_dump($options);
		echo '<p>' . __( 'Set up ClickWhale plugin global options.', $this->plugin_name ) . '</p>';
	}

	/**
	 * This function provides a simple description for the Tracking Options page.
	 *
	 * It's called from the 'initialize_tracking_options' function by being passed as a parameter
	 * in the add_settings_section function.
	 */
	public function tracking_options_callback() {
		$options = get_option( 'clickwhale_tracking_options' );
		//var_dump($options);
		echo '<p>' . __( 'Set up ClickWhale plugin global link tracking options.', $this->plugin_name ) . '</p>';
	}

	/**
	 * Initializes the plugin settings options page by registering the Sections,
	 * Fields, and Settings.
	 *
	 * This function is registered with the 'admin_init' hook.
	 */
	public function initialize_settings_options() {

		// If the options don't exist, create them.
		if ( false == get_option( 'clickwhale_general_options' ) ) {
			$default_array = $this->default_general_options();
			add_option( 'clickwhale_general_options', $default_array );
		}


		add_settings_section(
			'general_settings_section',                    // ID used to identify this section and with which to register options
			__( 'General', $this->plugin_name ),                // Title to be displayed on the administration page
			array( $this, 'general_options_callback' ),    // Callback used to render the description of the section
			'clickwhale_general_options'                // Page on which to add this section of options
		);

		// Next, we'll introduce the fields for toggling the visibility of content elements.
		add_settings_field(
			'redirection',                                // ID used to identify the field throughout the theme
			__( 'Redirection Type', $this->plugin_name ),        // The label to the left of the option interface element
			array(
				$this,
				'set_redirection_callback'
			),    // The name of the function responsible for rendering the option interface
			'clickwhale_general_options',                // The page on which this option will be displayed
			'general_settings_section',                    // The name of the section to which this field belongs
			array(                                        // The array of arguments to pass to the callback. In this case, just a description.
				__( 'Set default redirection type which will be used for new links.', $this->plugin_name ),
			)
		);

		add_settings_field(
			'nofollow',
			__( 'Nofollow links', $this->plugin_name ),
			array( $this, 'toggle_nofollow_callback' ),
			'clickwhale_general_options',
			'general_settings_section',
			array(
				__( 'Check to mark links as nofollow & noindex by default', $this->plugin_name ),
			)
		);

		add_settings_field(
			'sponsored',
			__( 'Sponsored links', $this->plugin_name ),
			array( $this, 'toggle_sponsored_callback' ),
			'clickwhale_general_options',
			'general_settings_section',
			array(
				__( 'Check to mark links as sponsored by default.', $this->plugin_name ),
				__( 'Recommended for affiliate links.', $this->plugin_name )
			)
		);

		// Finally, we register the fields with WordPress
		register_setting(
			'clickwhale_general_options',
			'clickwhale_general_options'
		);

	}

	/**
	 * Initializes the plugin tracking options page by registering the Sections,
	 * Fields, and Settings.
	 *
	 * This function is registered with the 'admin_init' hook.
	 */
	public function initialize_tracking_options() {

		// If the options don't exist, create them.
		if ( false == get_option( 'clickwhale_tracking_options' ) ) {
			$default_array = $this->default_tracking_options();
			add_option( 'clickwhale_tracking_options', $default_array );
		}


		add_settings_section(
			'tracking_settings_section',                          // ID used to identify this section and with which to register options
			__( 'Tracking', $this->plugin_name ),                    // Title to be displayed on the administration page
			array(
				$this,
				'tracking_options_callback'
			),        // Callback used to render the description of the section
			'clickwhale_tracking_options'                       // Page on which to add this section of options
		);

		// Next, we'll introduce the fields for toggling the visibility of content elements.
		add_settings_field(
			'redirection',                                          // ID used to identify the field throughout the theme
			__( 'Disable Click Tracking', $this->plugin_name ),        // The label to the left of the option interface element
			array(
				$this,
				'toggle_click_tracking'
			),                                                   // The name of the function responsible for rendering the option interface
			'clickwhale_tracking_options',                  // The page on which this option will be displayed
			'tracking_settings_section',                  // The name of the section to which this field belongs
			array(                                               // The array of arguments to pass to the callback. In this case, just a description.
				__( 'Check to disable click tracking for affiliate links.', $this->plugin_name ),
			)
		);

		add_settings_field(
			'nofollow',
			__( 'Exclude Users by Role', $this->plugin_name ),
			array( $this, 'exclude_users_by_role' ),
			'clickwhale_tracking_options',
			'tracking_settings_section',
			array(
				__( 'Remove clicks by logged-in users with these roles.', $this->plugin_name ),
			)
		);

		// Finally, we register the fields with WordPress
		register_setting(
			'clickwhale_tracking_options',
			'clickwhale_tracking_options'
		);

	}

	/**
	 * This function renders the interface elements for toggling the visibility of the header element.
	 *
	 * It accepts an array or arguments and expects the first element in the array to be the description
	 * to be displayed next to the checkbox.
	 */
	public function set_redirection_callback( $args ) {

		// First, we read the options collection
		$options = get_option( 'clickwhale_general_options' );
		?>
        <select id="redirect_type" name="clickwhale_general_options[redirect_type]" class="regular-text">
            <option value="301" <?php selected( $options['redirect_type'], 301, true ) ?>><?php _e( '301 redirect: Moved permanently', $this->plugin_name ) ?></option>
            <option value="302" <?php selected( $options['redirect_type'], 302, true ) ?>><?php _e( '302 redirect: Found / Moved temporarily', $this->plugin_name ) ?></option>
            <option value="303" <?php selected( $options['redirect_type'], 303, true ) ?>><?php _e( '303 redirect: See Other', $this->plugin_name ) ?></option>
            <option value="307" <?php selected( $options['redirect_type'], 307, true ) ?>><?php _e( '307 redirect: Temporarily Redirect', $this->plugin_name ) ?></option>
            <option value="308" <?php selected( $options['redirect_type'], 308, true ) ?>><?php _e( '308 redirect: Permanent Redirect', $this->plugin_name ) ?></option>
        </select>
        <p class="description "><?php echo esc_html( $args[0] ) ?></p>
		<?php
	}

	public function toggle_nofollow_callback( $args ) {

		$options = get_option( 'clickwhale_general_options' );
		?>
        <label for="nofollow">
            <input type="checkbox" id="nofollow" name="clickwhale_general_options[nofollow]"
                   value="1" <?php checked( 1, isset( $options['nofollow'] ) ? $options['nofollow'] : 0, true ) ?>>
			<?php echo esc_html( $args[0] ) ?>
        </label>
		<?php
	}

	public function toggle_sponsored_callback( $args ) {

		$options = get_option( 'clickwhale_general_options' );
		?>
        <label for="sponsored">
            <input type="checkbox" id="sponsored" name="clickwhale_general_options[sponsored]"
                   value="1" <?php checked( 1, isset( $options['sponsored'] ) ? $options['sponsored'] : 0, true ) ?>>
			<?php echo esc_html( $args[0] ) ?>
        </label>
        <p class="description "><?php echo esc_html( $args[1] ) ?></p>
		<?php
	}

	public function toggle_click_tracking( $args ) {

		$options = get_option( 'clickwhale_tracking_options' );
		?>
        <label for="disable_click_tracking">
            <input type="checkbox" id="disable_click_tracking" name="clickwhale_general_options[disable_click_tracking]"
                   value="1" <?php checked( 1, isset( $options['disable_click_tracking'] ) ? $options['disable_click_tracking'] : 0, true ) ?>>
			<?php echo esc_html( $args[0] ) ?>
        </label>
		<?php
	}

	public function exclude_users_by_role( $args ) {

		$options = get_option( 'clickwhale_tracking_options' );
		if ( isset( $options['exclude_users_by_role'] ) ) {
			$check_administrator = checked( ( in_array( 'administrator', $options['exclude_users_by_role'] ) ), 1, false );
			$check_editor        = checked( ( in_array( 'editor', $options['exclude_users_by_role'] ) ), 1, false );
			$check_author        = checked( ( in_array( 'author', $options['exclude_users_by_role'] ) ), 1, false );
		} else {
			$check_administrator = $check_editor = $check_author = '';
		}
		?>
        <label for="exclude_administrator">
            <input type="checkbox" id="exclude_administrator"
                   name="clickwhale_tracking_options[exclude_users_by_role][]"
                   value="administrator" <?php echo esc_attr( $check_administrator ) ?>/>
			<?php _e( 'Administrator', $this->plugin_name ); ?>
        </label><br>
        <label for="exclude_editor">
            <input type="checkbox" id="exclude_editor" name="clickwhale_tracking_options[exclude_users_by_role][]"
                   value="editor" <?php echo esc_attr( $check_editor ) ?>/>
			<?php _e( 'Editor', $this->plugin_name ); ?>
        </label><br>
        <label for="exclude_author">
            <input type="checkbox" id="exclude_author" name="clickwhale_tracking_options[exclude_users_by_role][]"
                   value="author" <?php echo esc_attr( $check_author ) ?>/>
			<?php _e( 'Author', $this->plugin_name ); ?>
        </label>
		<?php
	}
}