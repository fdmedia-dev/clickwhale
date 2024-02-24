<?php
namespace clickwhale\includes\front;

use clickwhale\includes\front\linkpages\{
	Linkpage,
	Linkpage_Controller,
	Linkpage_Template_Loader
};
use clickwhale\includes\helpers\Linkpages_Helper;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Clickwhale_Public_Linkpages {
	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		$this->load_dependencies();
		$this->init();
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
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'front/linkpages/Linkpage_Interface.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'front/linkpages/Linkpage_Controller_Interface.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'front/linkpages/Linkpage_Template_Loader_Interface.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'front/linkpages/Linkpage.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'front/linkpages/Linkpage_Controller.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'front/linkpages/Linkpage_Template_Loader.php';
	}

	public function init() {
		$linkpages = Linkpages_Helper::get_all( 'title', 'asc', 'ARRAY_A' );

		if ( ! $linkpages ) {
			return;
		}

		$controller = new Linkpage_Controller( new Linkpage_Template_Loader() );
		add_action( 'init', array( $controller, 'init' ) );
		add_filter( 'do_parse_request', array( $controller, 'dispatch' ), PHP_INT_MAX, 2 );
		add_action( 'loop_end', function ( $query ) {
			if ( isset( $query->virtual_page ) && ! empty( $query->virtual_page ) ) {
				$query->virtual_page = null;
			}
		} );
		add_filter( 'the_permalink', function ( $plink ) {
			global $post, $wp_query;
			if (
				$wp_query->is_page
				&& isset( $wp_query->virtual_page )
				&& $wp_query->virtual_page instanceof Linkpage
				&& isset( $post->is_virtual )
				&& $post->is_virtual
			) {
				$plink = home_url( $wp_query->virtual_page->getUrl() );
			}

			return $plink;
		} );

		foreach ( $linkpages as $linkpage ) {
			$controller->addPage( new Linkpage( $linkpage['slug'] ) )
			           ->setTitle( $linkpage['title'] )
			           ->setLinkpage( $linkpage )
			           ->setTemplate( 'linkpage.php' );
		}
	}
}