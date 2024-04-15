<?php
namespace clickwhale\includes\front;

use clickwhale\includes\admin\Clickwhale_WP_User;
use clickwhale\includes\helpers\{Linkpages_Helper, Tracking_Codes_Helper};

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
	public $path;

	public function __construct( $path ) {
		$this->path = $path;
		add_action( 'init', [ $this, 'prepare_tracking_codes' ], 20 );
	}

	/**
	 * Parse URL and return path or URL
	 *
	 * @param string $type
	 *
	 * @return string
	 */
	private function parse_current_page_path( string $type = 'path' ): string {
		$path = $this->path;

		if ( $type === 'url' ) {
			return get_bloginfo( 'url' ) . '/' . $path;
		} else {
			$pathFragments = explode( '/', $path );

			return end( $pathFragments );
		}
	}

	/**
	 * Get current page type (LP/post_type/taxonomy) and id
	 *
	 * @return array
	 */
	private function get_current_page_data(): array {
		$page = [];

		$current_page_path = $this->parse_current_page_path();
		$linkpage          = Linkpages_Helper::get_by_slug( $current_page_path );
		$post_type_page_id = url_to_postid( $this->parse_current_page_path( 'url' ) );

		if ( $linkpage ) {
			$page['type'] = 'cw_linkpage';
			$page['id']   = $linkpage['id'];

			return $page;
		}

		if ( $post_type_page_id ) {
			$page['type'] = get_post( $post_type_page_id )->post_type;
			$page['id']   = $post_type_page_id;

			return $page;
		}

		foreach ( Tracking_Codes_Helper::get_default_terms_tax() as $taxonomy ) {
			$term_object = get_term_by( 'slug', $current_page_path, $taxonomy );
			if ( $term_object ) {
				$page['type'] = $term_object->taxonomy;
				$page['id']   = $term_object->term_id;
				break;
			}
		}

		return $page;
	}

	private function is_user_untracked( array $position ): bool {
		$current_user_roles = Clickwhale_WP_User::get_current_user_roles();

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
	 * @param array $page
	 *
	 * @return void
	 */
	public function do_included_conditional_logic( array $position, array $tracking_code, array $page = [] ) {
		if ( ! $page ) {
			return;
		}

		if ( isset( $position['items_included'][ $page['type'] ]['active'] )
		     && ( in_array( $page['id'], $position['items_included'][ $page['type'] ] ['ids'] )
		          || in_array( 'all', $position['items_included'][ $page['type'] ] ['ids'] ) )
		) {
			$this->do_tracking_action( $position['code'], $tracking_code['code'] );
		}
	}

	/**
	 * Do logic for excluded LP / Posts / Pages
	 *
	 * @param array $position
	 * @param array $tracking_code
	 * @param array $page
	 *
	 * @return void
	 */
	public function do_excluded_conditional_logic( array $position, array $tracking_code, array $page = [] ) {
		if ( ! $page ) {
			$this->do_tracking_action( $position['code'], $tracking_code['code'] );

			return;
		}

		if ( ! isset( $position['items_excluded'][ $page['type'] ]['active'] )
		     || ( ! in_array( $page['id'], $position['items_excluded'][ $page['type'] ] ['ids'] )
		          && ! in_array( 'all', $position['items_excluded'][ $page['type'] ] ['ids'] ) )
		) {
			$this->do_tracking_action( $position['code'], $tracking_code['code'] );
		}
	}

	/**
	 * @return false|void
	 */
	public function prepare_tracking_codes() {
		$tracking_codes = Tracking_Codes_Helper::get_active();

		if ( ! $tracking_codes ) {
			return false;
		}

		$page = $this->get_current_page_data();

		foreach ( $tracking_codes as $tracking_code ) {
			$position = maybe_unserialize( $tracking_code['position'] );

			if ( $this->is_user_untracked( $position ) ) {
				continue;
			}

			if ( isset( $position['pages'] ) && $position['pages'] === 'all' ) {
				if ( isset( $position['items_excluded'] ) ) {
					$this->do_excluded_conditional_logic( $position, $tracking_code, $page );
				} else {
					$this->do_tracking_action( $position['code'], $tracking_code['code'] );
				}
			} else {
				$this->do_included_conditional_logic( $position, $tracking_code, $page );
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
			'clickwhale_tracking_code_credit_before',
			'<!-- START ClickWhale - Tracking Code -->'
		);
		$credit_after  = apply_filters(
			'clickwhale_tracking_code_credit_after',
			'<!-- END ClickWhale - Tracking Code ( https://clickwhale.pro ) -->'
		);

		add_action( $position, function () use ( $code, $credit_before, $credit_after ) {
			if ( $credit_before ) {
				echo PHP_EOL . $credit_before . PHP_EOL;
			}

			echo wp_unslash( $code );

			if ( $credit_after ) {
				echo PHP_EOL . $credit_after . PHP_EOL;
			}
		} );
	}
}
