<?php
namespace clickwhale\includes\front\linkpages;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Linkpage_Template_Loader implements Linkpage_Template_Loader_Interface {

    private array $templates;

    public function init( object $page ) {
        $this->templates = wp_parse_args(
            array( 'page.php', 'index.php' ),
            (array) $page->getTemplate()
        );
    }

    public function wpscap_locate_template( array $template_names, bool $load = false, bool $require_once = true ): string {
        $located = '';

        foreach ( $template_names as $template_name ) {
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

        do_action( 'template_redirect' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
        $template = $this->wpscap_locate_template( array_filter( $this->templates ), true );
        if ( array_key_exists( 'virtualpage', $wp_query->query_vars ) ) {
            if ( '1' == $wp_query->query_vars['clickwhale_virtual_page_template'] ) {
                include 'page-register.php';
            }
            exit();
        }
        $filtered = apply_filters( 'template_include', // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
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
