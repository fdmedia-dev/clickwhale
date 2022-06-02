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
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

    	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

    /**
	 * This function introduces the theme options into the 'Settings' menu and into a top-level
	 * 'Clickwhale' menu.
	 */
	public function setup_plugin_options_menu() {

        // add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $function, $icon_url, $position );
        add_menu_page( 
            'ClickWhale Links', 
            'ClickWhale', 
            'manage_options', 
            $this->plugin_name,
            '',
            plugin_dir_url( __FILE__ ) . 'images/clickwhale-reduced.svg',
            26
        );
        add_submenu_page(
            $this->plugin_name, 
            'ClickWhale Links', 
            'Links', 
            'manage_options', 
            $this->plugin_name, 
            array( $this, $this->plugin_name . '_links_page_handler' )
        );
        add_submenu_page(
            '',  
            'Add New', 
            'Add New Link', 
            'manage_options', 
            $this->plugin_name . '-edit-link', 
            array( $this, $this->plugin_name . '_link_form_page_handler' )
        );
        add_submenu_page(
            $this->plugin_name, 
            'ClickWhale Categories', 
            'Categories', 
            'manage_options', 
            $this->plugin_name . '-categories', 
            array( $this, $this->plugin_name . '_categories_page_handler' )
        );
        add_submenu_page(
            '',  
            'Add New Category', 
            'Add New Category', 
            'manage_options', 
            $this->plugin_name . '-edit-category', 
            array( $this, $this->plugin_name . '_category_form_page_handler' )
        );
        add_submenu_page(
            $this->plugin_name, 
            'ClickWhale Settings', 
            'Settings', 
            'manage_options', 
            $this->plugin_name . '-settings', 
            array( $this, 'render_settings_page_content' )
        );
        add_submenu_page(
            $this->plugin_name, 
            'ClickWhale Tools', 
            'Tools', 
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
		if (!class_exists('WP_List_Table')) {
			require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
		}
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/settings/class-clickwhale-links-list-table.php';
		include_once( plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/clickwhale-admin-links-list-table.php' );
	}
	public function clickwhale_link_form_page_handler() {
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/settings/class-clickwhale-link-edit.php';
		include_once( plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/clickwhale-admin-link-edit.php' );
	}
	public function clickwhale_categories_page_handler() {
		if (!class_exists('WP_List_Table')) {
			require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
		}
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/settings/class-clickwhale-categories-list-table.php';
		include_once( plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/clickwhale-admin-categories-list-table.php' );
	}
	public function clickwhale_category_form_page_handler() {
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/settings/class-clickwhale-category-edit.php';
		include_once( plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/clickwhale-admin-category-edit.php' );
	}

	// empty pages
	public function render_settings_page_content() {
		include_once( plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/clickwhale-admin-settings-display.php' );
	}
	public function include_admin_menu_tools_partial() {
		include_once( plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/clickwhale-admin-menu-tools.php' );
	}


    /**
	 * Provides default values for the General Options.
	 *
	 * @return array
	 */
	public function default_general_options() {

		$defaults = array(
			'redirect_type'		    =>	301,
			'show_nofollow'		    =>	true,
			'show_sponsored'		=>	false,
		);

		return $defaults;

	}

    public function default_tracking_options() {

		$defaults = array(
			'disable_click_tracking'		    =>	false,
			'exclude_users_by_role'		        =>	['administrator'],
		);

		return $defaults;

	}

    /**
	 * This function provides a simple description for the General Options page.
	 *
	 * It's called from the 'wppb-initialize_settings_options' function by being passed as a parameter
	 * in the add_settings_section function.
	 */
	public function general_options_callback() {
		$options = get_option('clickwhale_general_options');
		//var_dump($options);
		echo '<p>' . __( 'Set up ClickWhale plugin global options.', 'clickwhale' ) . '</p>';
	}

    /**
	 * This function provides a simple description for the Tracking Options page.
	 *
	 * It's called from the 'wppb-initialize_trcking_options' function by being passed as a parameter
	 * in the add_settings_section function.
	 */
	public function tracking_options_callback() {
		$options = get_option('clickwhale_tracking_options');
		//var_dump($options);
		echo '<p>' . __( 'Set up ClickWhale plugin global link tracking options.', 'clickwhale' ) . '</p>';
	}

    /**
	 * Initializes the plugin settings options page by registering the Sections,
	 * Fields, and Settings.
	 *
	 * This function is registered with the 'admin_init' hook.
	 */
	public function initialize_settings_options() {

		// If the options don't exist, create them.
		if( false == get_option( 'clickwhale_general_options' ) ) {
			$default_array = $this->default_general_options();
			add_option( 'clickwhale_general_options', $default_array );
		}


		add_settings_section(
			'general_settings_section',			        // ID used to identify this section and with which to register options
			__( 'General', 'clickwhale' ),		        // Title to be displayed on the administration page
			array( $this, 'general_options_callback'),	// Callback used to render the description of the section
			'clickwhale_general_options'		        // Page on which to add this section of options
		);

		// Next, we'll introduce the fields for toggling the visibility of content elements.
		add_settings_field(
			'redirection',						        // ID used to identify the field throughout the theme
			__( 'Redirection Type', 'clickwhale' ),		// The label to the left of the option interface element
			array( $this, 'set_redirection_callback'),	// The name of the function responsible for rendering the option interface
			'clickwhale_general_options',	            // The page on which this option will be displayed
			'general_settings_section',			        // The name of the section to which this field belongs
			array(								        // The array of arguments to pass to the callback. In this case, just a description.
				__( 'Set default redirection type which will be used for new links.', 'clickwhale' ),
			)
		);

		add_settings_field(
			'show_nofollow',
			__( 'Nofollow links', 'clickwhale' ),
			array( $this, 'toggle_nofollow_callback'),
			'clickwhale_general_options',
			'general_settings_section',
			array(
				__( 'Check to mark links as nofollow & noindex by default', 'clickwhale' ),
			)
		);

		add_settings_field(
			'show_sponsored',
			__( 'Sponsored links', 'clickwhale' ),
			array( $this, 'toggle_sponsored_callback'),
			'clickwhale_general_options',
			'general_settings_section',
			array(
				__( 'Check to mark links as sponsored by default.', 'clickwhale' ),
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
		if( false == get_option( 'clickwhale_tracking_options' ) ) {
			$default_array = $this->default_tracking_options();
			add_option( 'clickwhale_tracking_options', $default_array );
		}


		add_settings_section(
			'tracking_settings_section',			        // ID used to identify this section and with which to register options
			__( 'Tracking', 'clickwhale' ),		            // Title to be displayed on the administration page
			array( $this, 'tracking_options_callback'),	    // Callback used to render the description of the section
			'clickwhale_tracking_options'		            // Page on which to add this section of options
		);

		// Next, we'll introduce the fields for toggling the visibility of content elements.
		add_settings_field(
			'redirection',						        // ID used to identify the field throughout the theme
			__( 'Disable Click Tracking', 'clickwhale' ),		// The label to the left of the option interface element
			array( $this, 'toggle_click_tracking'),	// The name of the function responsible for rendering the option interface
			'clickwhale_tracking_options',	            // The page on which this option will be displayed
			'tracking_settings_section',			        // The name of the section to which this field belongs
			array(								        // The array of arguments to pass to the callback. In this case, just a description.
				__( 'Check to disable click tracking for affiliate links.', 'clickwhale' ),
			)
		);

		add_settings_field(
			'show_nofollow',
			__( 'Exclude Users by Role', 'clickwhale' ),
			array( $this, 'exclude_users_by_role'),
			'clickwhale_tracking_options',
			'tracking_settings_section',
			array(
				__( 'Remove clicks by logged-in users with these roles.', 'clickwhale' ),
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
	public function set_redirection_callback($args) {

		// First, we read the options collection
		$options = get_option('clickwhale_general_options');

		// Next, we update the name attribute to access this element's ID in the context of the display options array
		// We also access the redirect_type element of the options collection in the call to the selected() helper function
		$html = '';
        $html .= '<select id="redirect_type" name="clickwhale_general_options[redirect_type]">';
        $html .= '<option value="301" ' . selected( $options['redirect_type'], 301, false ) . ' >' . __( '301 redirect: Moved permanently', 'clickwhale' ) . '</option>';
        $html .= '<option value="302" ' . selected( $options['redirect_type'], 302, false ) . ' >' . __( '302 redirect: Found / Moved temporarily', 'clickwhale' ) . '</option>';
        $html .= '<option value="303" ' . selected( $options['redirect_type'], 303, false ) . ' >' . __( '303 redirect: See Other', 'clickwhale' ) . '</option>';
        $html .= '<option value="307" ' . selected( $options['redirect_type'], 307, false ) . ' >' . __( '307 redirect: Temporarily Redirect', 'clickwhale' ) . '</option>';
        $html .= '<option value="308" ' . selected( $options['redirect_type'], 308, false ) . ' >' . __( '308 redirect: Permanent Redirect', 'clickwhale' ) . '</option>';
        $html .= '</select>';

		// Here, we'll take the first argument of the array and add it to a label next to the checkbox
		$html .= '<p>'  . $args[0] . '</p>';

		echo $html;

	}

	public function toggle_nofollow_callback($args) {

		$options = get_option('clickwhale_general_options');

		$html = '<input type="checkbox" id="show_nofollow" name="clickwhale_general_options[show_nofollow]" value="1" ' . checked( 1, isset( $options['show_nofollow'] ) ? $options['show_nofollow'] : 0, false ) . '/>';
		$html .= '<label for="show_nofollow">&nbsp;'  . $args[0] . '</label>';

		echo $html;

	}

	public function toggle_sponsored_callback($args) {

		$options = get_option('clickwhale_general_options');

		$html = '<input type="checkbox" id="show_sponsored" name="clickwhale_general_options[show_sponsored]" value="1" ' . checked( 1, isset( $options['show_sponsored'] ) ? $options['show_sponsored'] : 0, false ) . '/>';
		$html .= '<label for="show_sponsored">&nbsp;'  . $args[0] . '</label>';
        $html .= '<p>Recommended for affiliate links.</p>';

		echo $html;

	}

    public function toggle_click_tracking($args) {

		$options = get_option('clickwhale_tracking_options');

		$html = '<input type="checkbox" id="disable_click_tracking" name="clickwhale_tracking_options[disable_click_tracking]" value="1" ' . checked( 1, isset( $options['disable_click_tracking'] ) ? $options['disable_click_tracking'] : 0, false ) . '/>';
		$html .= '<label for="disable_click_tracking">&nbsp;'  . $args[0] . '</label>';

		echo $html;

	}

    public function exclude_users_by_role($args) {

		$options = get_option('clickwhale_tracking_options');
        
        $html = '';
		$html .= '<input type="checkbox" id="exclude_administrator" name="clickwhale_tracking_options[exclude_users_by_role][]" value="administrator" ' . checked( ( in_array('administrator', $options['exclude_users_by_role']) ), 1, false ) . '/>';
		$html .= '<label for="exclude_administrator">&nbsp;Administrator</label><br>';
        $html .= '<input type="checkbox" id="exclude_editor" name="clickwhale_tracking_options[exclude_users_by_role][]" value="editor" ' . checked( ( in_array('editor', $options['exclude_users_by_role']) ), 1, false ) . '/>';
		$html .= '<label for="exclude_editor">&nbsp;Editor</label><br>';
        $html .= '<input type="checkbox" id="exclude_author" name="clickwhale_tracking_options[exclude_users_by_role][]" value="author" ' . checked( ( in_array('author', $options['exclude_users_by_role']) ), 1, false ) . '/>';
		$html .= '<label for="exclude_author">&nbsp;Author</label>';
        $html .= '<p>'  . $args[0] . '</p>';

		echo $html;

	}
}