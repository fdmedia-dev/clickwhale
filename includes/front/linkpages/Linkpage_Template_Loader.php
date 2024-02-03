<?php
namespace clickwhale\includes\front\linkpages;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Linkpage_Template_Loader implements Linkpage_Template_Loader_Interface {
	public function init( $page ) {
		$this->templates = wp_parse_args(
			array( 'page.php', 'index.php' ), (array) $page->getTemplate()
		);
	}

	public function wpscap_locate_template( $template_names, $load = false, $require_once = true ) {
		$located = '';

		foreach ( (array) $template_names as $template_name ) {
			if ( ! $template_name ) {
				continue;
			}
			if ( file_exists( plugin_dir_path( dirname( __FILE__ ) ) . 'templates/' . $template_name ) ) {

				$located = plugin_dir_path( dirname( __FILE__ ) ) . 'templates/' . $template_name;

				break;
			}
		}

		if ( $load && '' != $located ) {
			load_template( $located, $require_once );
		}

		return $located;
	}

	public function load() {
		global $wp_query;

		do_action( 'template_redirect' );
		$template = $this->wpscap_locate_template( array_filter( $this->templates ), true );
		if ( array_key_exists( 'virtualpage', $wp_query->query_vars ) ) {
			switch ( $wp_query->query_vars['clickwhale_virtual_page_template'] ) {
				case '1' :
					include 'page-register.php';
					break;
			}
			exit();
		}
		$filtered = apply_filters( 'template_include',
			apply_filters( 'clickwhale_virtual_page_template', $template )
		);
		if ( empty( $filtered ) || file_exists( $filtered ) ) {
			$template = $filtered;
		}
		if ( ! empty( $template ) && file_exists( $template ) ) {
			require_once $template;
		}
	}
}