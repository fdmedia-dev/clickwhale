<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       #
 * @since      1.0.0
 *
 * @package    Clickwhale
 * @subpackage Clickwhale/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Clickwhale
 * @subpackage Clickwhale/public
 * @author     fdmedia <https://fdmedia.io>
 */
class Clickwhale_Public {

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
	 * @param string $plugin_name The name of the plugin.
	 * @param string $version The version of this plugin.
	 *
	 * @since    1.0.0
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;
		$this->load_dependencies();

		$pages = new Clickwhale_Linkpages();
		$pages->init();
	}

	/**
	 * Load the required dependencies for the Admin facing functionality.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Clickwhale_Admin_Settings. Registers the admin settings and page.
	 *
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/tracking/class-clickwhale-parser.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/tracking/class-clickwhale-visitor-track.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/tracking/class-clickwhale-click-track.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/tracking/class-clickwhale-view-track.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-clickwhale-wp-user.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-clickwhale-linkpages.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-clickwhale-linkpage.php';

	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
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

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/clickwhale-public.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
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

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/clickwhale-public.js', array( 'jquery' ), $this->version, false );
		wp_localize_script( $this->plugin_name, 'clickwhale_public_js',
			array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
	}

	public function clickwhale_url_params_callback( $url ) {
		return $url;
	}

	public function do_redirect_handler() {
		global $wpdb;

		// if PHP Warning: Undefined array key "HTTP_HOST"
		if ( ! isset( $_SERVER['HTTP_HOST'] ) ) {
			$_SERVER['HTTP_HOST'] = 'localhost';
		}
		$url  = "//{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";
		$path = untrailingslashit( parse_url( $url, PHP_URL_PATH ) );
		if ( ! is_admin() && $path ) {
			$path    = ltrim( str_replace( $_SERVER['HTTP_HOST'], '', $path ), '/' );
			$results = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}clickwhale_links WHERE slug = '%s'", $path ) );
		};

		if ( ! empty( $results ) ) {

			$id = intval( $results[0]->id );

			// Track click on link
			new Clickwhale_Click_Track( $id );

			// Set headers
			$nofollow  = '';
			$sponsored = '';
			$sep       = '';

			if ( $results[0]->nofollow ) {
				$nofollow = 'noindex, nofollow';
			}
			if ( $results[0]->sponsored ) {
				$sponsored = 'sponsored';
			}
			if ( $results[0]->nofollow && $results[0]->sponsored ) {
				$sep = ', ';
			}
			if ( $results[0]->nofollow || $results[0]->sponsored ) {
				header( 'X-Robots-Tag: ' . $nofollow . $sep . $sponsored );
			}

			$link_url = apply_filters( 'clickwhale_url_params', $results[0]->url, $id );
			wp_redirect( $link_url, $results[0]->redirection );
			exit;
		}

	}
}