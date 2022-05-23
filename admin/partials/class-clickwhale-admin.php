<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       #
 * @since      1.0.0
 *
 * @package    Clickwhale
 * @subpackage Clickwhale/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Clickwhale
 * @subpackage Clickwhale/admin
 * @author     Rivo <https://rivo.agency>
 */
class Clickwhale_Admin {

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
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Clickwhale_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Clickwhale_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/clickwhale-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Clickwhale_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Clickwhale_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/clickwhale-admin.js', array( 'jquery' ), $this->version, false );

	}

	/**
	 * Add Admin Menu
	 *
	 * @since    1.0.0
	 */
	public function add_menu() {
        // add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $function, $icon_url, $position );
        add_menu_page( 
			'ClickWhale Links', 
			'ClickWhale', 
			'manage_options', 
			$this->plugin_name
		);
		add_submenu_page(
			$this->plugin_name, 
			'ClickWhale Links', 
			'Links', 
			'manage_options', 
			$this->plugin_name, 
			array( $this, 'include_admin_menu_linls_partial' )
		);
		add_submenu_page(
			$this->plugin_name, 
			'ClickWhale Categories', 
			'Categories', 
			'manage_options', 
			$this->plugin_name . '-categories', 
			array( $this, 'include_admin_menu_categories_partial' )
		);
		add_submenu_page(
			$this->plugin_name, 
			'ClickWhale Settings', 
			'Settings', 
			'manage_options', 
			$this->clickwhale . '-settings', 
			array( $this, 'include_admin_menu_settings_partial' )
		);
		add_submenu_page(
			$this->plugin_name, 
			'ClickWhale Tools', 
			'Tools', 
			'manage_options', 
			$this->plugin_name . '-tools', 
			array( $this, 'include_admin_menu_tools_partial' )
		);
		add_submenu_page(
			'', 
			'Edit link', 
			'Edit link', 
			'manage_options', 
			$this->plugin_name . '-edit-link', 
			array( $this, 'include_admin_edit_link_partial' )
		);
	}
	/**
	* Include Menu Partial
	*
	* @since    1.0.0
	*/
	public function include_admin_menu_linls_partial() {
		include_once( plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/clickwhale-admin-menu-links.php' );
	}
	public function include_admin_menu_categories_partial() {
		include_once( plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/clickwhale-admin-menu-categories.php' );
	}
	public function include_admin_menu_settings_partial() {
		include_once( plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/clickwhale-admin-menu-settings.php' );
	}
	public function include_admin_menu_tools_partial() {
		include_once( plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/clickwhale-admin-menu-tools.php' );
	}
	public function include_admin_edit_link_partial() {
		include_once( plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/clickwhale-admin-edit-link.php' );
	}

}
