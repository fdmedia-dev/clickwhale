<?php
namespace clickwhale\includes\front;

use clickwhale\includes\helpers\{
    Helper,
    Linkpages_Helper,
    Tracking_Codes_Helper
};

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Do tracking code on the current page
 * @since 1.2.0
 */
class Clickwhale_Public_Tracking_Codes {

    /**
     * @var string
     */
    public string $path;

    public function __construct( $path ) {
        $this->path = $path;

        add_action( 'init', array( $this, 'prepare_tracking_codes' ), 20 );
    }

    /**
     * Get current page type (LP/post_type/taxonomy) and ID
     *
     * @return array
     */
    private function get_current_page_data(): array {
        $fragments = explode( '/', $this->path );
        $current_page_path = end( $fragments );
        $current_page_path = sanitize_title( $current_page_path );

        if ( '' !== $current_page_path ) {
            $linkpage = Linkpages_Helper::get_by_slug( $current_page_path );

            if ( ! empty( $linkpage ) ) {
                $page['type'] = 'cw_linkpage';
                $page['id'] = $linkpage['id'];

                return $page;
            }
        }

        $post_type_page_id = url_to_postid( trailingslashit( home_url( $this->path ) ) );

        if ( ! empty( $post_type_page_id ) ) {
            $page['type'] = get_post( $post_type_page_id )->post_type;
            $page['id']   = $post_type_page_id;

            return $page;
        }

        if ( '' !== $current_page_path ) {
            $taxonomies = Tracking_Codes_Helper::get_default_terms_tax();

            foreach ( $taxonomies as $taxonomy ) {
                $term_object = get_term_by( 'slug', $current_page_path, $taxonomy );
                if ( $term_object ) {
                    $page['type'] = $term_object->taxonomy;
                    $page['id']   = $term_object->term_id;
                    break;
                }
            }
        }

        return $page ?? array();
    }

    private function is_user_untracked( array $position ): bool {
        $current_user_roles = clickwhale()->user->get_current_user_roles();

        if ( isset( $position['exclude_user_by_role'] ) && ! empty( $current_user_roles ) ) {
            return count( array_intersect( $current_user_roles, $position['exclude_user_by_role'] ) ) > 0;
        }

        return false;
    }

    /**
     * Do logic for included LP / Posts / Pages
     *
     * @param array $position
     * @param array $tracking_code
     *
     * @return void
     */
    public function do_included_conditional_logic( array $position, array $tracking_code ) {
        $page = $this->get_current_page_data();

        if ( empty( $page ) ) {
            return;
        }

        if ( isset( $position['items_included'][$page['type']]['active'] ) ) {
            $ids = $position['items_included'][$page['type']]['ids'];

            if ( in_array( $page['id'], $ids ) || in_array( 'all', $ids ) ) {
                $this->do_tracking_action( $position['code'], $tracking_code['code'] );
            }
        }
    }

    /**
     * Do logic for excluded LP / Posts / Pages
     *
     * @param array $position
     * @param array $tracking_code
     *
     * @return void
     */
    public function do_excluded_conditional_logic( array $position, array $tracking_code ) {
        $page = $this->get_current_page_data();

        if ( empty( $page ) ) {
            $this->do_tracking_action( $position['code'], $tracking_code['code'] );
            return;
        }

        if ( ! isset( $position['items_excluded'][$page['type']]['active'] ) ) {
            $this->do_tracking_action( $position['code'], $tracking_code['code'] );
            return;
        }

        $ids = $position['items_excluded'][$page['type']]['ids'];

        if ( ! in_array( $page['id'], $ids ) && ! in_array( 'all', $ids ) ) {
            $this->do_tracking_action( $position['code'], $tracking_code['code'] );
        }
    }

    /**
     * @return void
     */
    public function prepare_tracking_codes(): void {
        if ( is_admin() || ! $this->path ) {
            return;
        }

        $tracking_codes = Tracking_Codes_Helper::get_active();

        if ( ! $tracking_codes ) {
            return;
        }

        foreach ( $tracking_codes as $tracking_code ) {
            $position = maybe_unserialize( $tracking_code['position'] );

            if ( $this->is_user_untracked( $position ) ) {
                continue;
            }

            if ( isset( $position['pages'] ) && $position['pages'] === 'all' ) {
                if ( isset( $position['items_excluded'] ) ) {
                    $this->do_excluded_conditional_logic( $position, $tracking_code );
                } else {
                    $this->do_tracking_action( $position['code'], $tracking_code['code'] );
                }
            } else {
                $this->do_included_conditional_logic( $position, $tracking_code );
            }
        }
    }

    /**
     * @param string $position
     * @param string $code
     *
     * @return void
     */
    public function do_tracking_action( string $position, string $code ) {
        $credit_before = apply_filters(
            'clickwhale_tracking_code_credits',
            ''
        );
        $credit_after  = apply_filters(
            'clickwhale_tracking_code_credits',
            ''
        );

        add_action( $position, function () use ( $code, $credit_before, $credit_after ) {
            if ( $credit_before ) {
                echo PHP_EOL . esc_html( $credit_before ) . PHP_EOL;
            }

            echo wp_kses( wp_unslash( $code ), Helper::get_allowed_tags() );

            if ( $credit_after ) {
                echo PHP_EOL . esc_html( $credit_after ) . PHP_EOL;
            }
        });
    }
}
