<?php

class Clickwhale_Linkpages {
	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		$this->load_dependencies();
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

		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/linkpages/ClickwhaleLinkPageInterface.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/linkpages/ClickwhaleLinkPageControllerInterface.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/linkpages/ClickwhaleLinkPageTemplateLoaderInterface.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/linkpages/ClickwhaleLinkPage.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/linkpages/ClickwhaleLinkPageController.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/linkpages/ClickwhaleLinkPageTemplateLoader.php';
	}

	public function init() {
		global $wpdb;

		$table_linkpages = $wpdb->prefix . 'clickwhale_linkpages';
		if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_linkpages'" ) != $table_linkpages ) {
			return;
		}

		$controller = new ClickwhaleLinkPageController ( new ClickwhaleLinkPageTemplateLoader );
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
				&& $wp_query->virtual_page instanceof ClickwhaleLinkPage
				&& isset( $post->is_virtual )
				&& $post->is_virtual
			) {
				$plink = home_url( $wp_query->virtual_page->getUrl() );
			}

			return $plink;
		} );

		$linkpages = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}clickwhale_linkpages", ARRAY_A );
		if ( $linkpages ) {
			foreach ( $linkpages as $linkpage ) {
				$controller->addPage( new ClickwhaleLinkPage( $linkpage['slug'] ) )
				           ->setTitle( $linkpage['title'] )
				           ->setLinkpage( $linkpage )
				           ->setTemplate( 'linkpage.php' );
			}
		}
	}
}