<?php

/**
 * Do tracking code on the current page
 * @since 1.2.0
 */
class ClickwhaleTrackingCodes {
	public function init() {
		if ( ! is_admin() ) {
			add_action( 'init', [ $this, 'prepare_tracking_codes' ], PHP_INT_MAX );
		}
	}

	/**
	 * Parse URL and return path or URL
	 *
	 * @param string $type
	 *
	 * @return string
	 */
	private function parse_current_page_path( string $type = 'path' ): string {
		$path = untrailingslashit( parse_url( $_SERVER["REQUEST_URI"], PHP_URL_PATH ) );

		if ( $type === 'url' ) {
			return get_bloginfo( 'url' ) . '/' . $path;
		} else {
			$pathFragments = explode( '/', $path );

			return end( $pathFragments );
		}
	}

	/**
	 * Get all active tracking codes from DB
	 *
	 * @return array|object|stdClass[]|null
	 */
	private function get_tracking_codes() {
		global $wpdb;

		return $wpdb->get_results(
			"SELECT * FROM {$wpdb->prefix}clickwhale_tracking_codes WHERE is_active = '1' OR is_active = 1",
			ARRAY_A
		);
	}

	/**
	 * Get current page type (LP/post_type/taxonomy) and id
	 *
	 * @return array
	 */
	private function get_current_page_data(): array {
		$page = [];

		$current_page_path = $this->parse_current_page_path();
		$linkpage_id       = ClickwhaleLinkpagesHelper::get_linkpage_id_by_slug( $current_page_path );
		$post_type_page_id = url_to_postid( $this->parse_current_page_path( 'url' ) );

		if ( $linkpage_id ) {
			$page['type'] = 'cw_linkpage';
			$page['id']   = $linkpage_id;

			return $page;
		}

		if ( $post_type_page_id ) {
			$page['type'] = get_post( $post_type_page_id )->post_type;
			$page['id']   = $post_type_page_id;

			return $page;
		}

		foreach ( ClickwhaleTrackingCodeEdit::get_default_terms_tax() as $taxonomy ) {
			$term_object = get_term_by( 'slug', $current_page_path, $taxonomy );
			if ( $term_object ) {
				$page['type'] = $term_object->taxonomy;
				$page['id']   = $term_object->term_id;
				break;
			}
		}

		return $page;
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
		$tracking_codes = $this->get_tracking_codes();
		if ( ! $tracking_codes ) {
			return false;
		}

		$page = $this->get_current_page_data();

		foreach ( $tracking_codes as $tracking_code ) {
			$position = maybe_unserialize( $tracking_code['position'] );

			if ( $position['pages'] === 'all' ) {
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
		add_action( $position, function () use ( $code ) {
			echo wp_unslash( $code );
		} );
	}

}

