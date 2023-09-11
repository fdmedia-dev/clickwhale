<?php

namespace clickwhale\includes\front;

use clickwhale\includes\admin\helpers\Clickwhale_Helper;

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

	private $path;

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
		$this->path        = Clickwhale_Helper::get_public_path( true );

		$this->load_dependencies();
		$this->init_classes();

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
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'front/tracking/class-clickwhale-parser.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'front/tracking/class-clickwhale-visitor-track.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'front/tracking/class-clickwhale-click-track.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'front/tracking/class-clickwhale-view-track.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'front/Clickwhale_Public_Linkpages.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'front/Clickwhale_Public_Linkpage.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'front/Clickwhale_Public_Tracking_Codes.php';
	}

	private function init_classes() {
		$linkpages = $trackingCodes = null;
		if ( ! is_admin() ) {
			$linkpages     = new Clickwhale_Public_Linkpages();
			$trackingCodes = new Clickwhale_Public_Tracking_Codes( $this->path );
		}
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		if ( ! is_admin() && $this->path && \clickwhale\includes\ClickwhaleLinkpagesHelper::is_linkpage( $this->path ) ) {
			wp_enqueue_style(
				$this->plugin_name,
				CLICKWHALE_PUBLIC_CSS_DIR . '/clickwhale-public.css',
				array(),
				$this->version
			);
		}

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		if ( ! is_admin() && $this->path && \clickwhale\includes\ClickwhaleLinkpagesHelper::is_linkpage( $this->path ) ) {
			wp_enqueue_script(
				$this->plugin_name . '_ionicons',
				CLICKWHALE_PUBLIC_JS_DIR . '/ionicons/ionicons.js',
				array( 'jquery' ),
				'7.1.0',
				true
			);

			wp_enqueue_script(
				$this->plugin_name,
				CLICKWHALE_PUBLIC_JS_DIR . '/clickwhale-public.js',
				array( 'jquery' ),
				$this->version,
				true
			);

			wp_localize_script(
				$this->plugin_name,
				'clickwhale_public_js',
				array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) )
			);
		}
	}

	public function do_redirect_handler() {
		global $wpdb;

		if ( ! is_admin() && $this->path ) {
			$results = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}clickwhale_links WHERE slug = '%s'",
				$this->path ) );
		};

		if ( ! empty( $results ) ) {

			$id = intval( $results[0]->id );

			// Track click on link
			new \clickwhale\includes\Clickwhale_Click_Track( $id );

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
