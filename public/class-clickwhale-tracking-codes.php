<?php

class ClickwhaleTrackingCodes {
	public function init() {
		if ( ! is_admin() ) {
			add_action( 'init', [ $this, 'prepare_tracking_codes' ], PHP_INT_MAX );
		}
	}

	private function parse_current_page_path(): string {
		return ltrim( untrailingslashit( parse_url( $_SERVER["REQUEST_URI"], PHP_URL_PATH ) ), '/' );
	}

	private function get_current_page_id_by_url(): int {
		return url_to_postid( get_bloginfo( 'url' ) . '/' . $this->parse_current_page_path() . '/' );
	}

	private function get_tracking_codes() {
		global $wpdb;

		return $wpdb->get_results(
			"SELECT * FROM {$wpdb->prefix}clickwhale_tracking_codes WHERE is_active = '1' OR is_active = 1",
			ARRAY_A
		);
	}

	/**
	 * Do logic for included LP / Posts / Pages
	 *
	 * @param array $position
	 * @param array $tracking_code
	 * @param string $linkpage_id
	 * @param string $post_id
	 * @param string $post_type
	 *
	 * @return void
	 */
	public function do_included_conditional_logic(
		array $position,
		array $tracking_code,
		string $linkpage_id = '',
		string $post_id = '',
		string $post_type = ''
	) {
		if ( $linkpage_id ) {
			if ( isset( $position['post_types_included']['cw_linkpage'] )
			     && ( in_array( $linkpage_id, $position['post_types_included']['cw_linkpage'] ['ids'] )
			          || in_array( 'all', $position['post_types_included']['cw_linkpage'] ['ids'] ) )
			) {
				$this->do_tracking_action( $position['code'], $tracking_code['code'] );
			}
		} elseif ( $post_id ) {
			if ( isset( $position['post_types_included'][ $post_type ]['active'] )
			     && ( in_array( $post_id, $position['post_types_included'][ $post_type ] ['ids'] )
			          || in_array( 'all', $position['post_types_included'][ $post_type ] ['ids'] ) )
			) {
				$this->do_tracking_action( $position['code'], $tracking_code['code'] );
			}
		} else {
			$this->do_tracking_action( $position['code'], $tracking_code['code'] );
		}
	}

	/**
	 * Do logic for excluded LP / Posts / Pages
	 *
	 * @param array $position
	 * @param array $tracking_code
	 * @param string $linkpage_id
	 * @param string $post_id
	 * @param string $post_type
	 *
	 * @return void
	 */
	public function do_excluded_conditional_logic(
		array $position,
		array $tracking_code,
		string $linkpage_id = '',
		string $post_id = '',
		string $post_type = ''
	) {
		if ( $linkpage_id ) {
			if ( ! isset( $position['post_types_excluded']['cw_linkpage'] )
			     || ( ! in_array( $linkpage_id, $position['post_types_excluded']['cw_linkpage'] ['ids'] )
			          && ! in_array( 'all', $position['post_types_excluded']['cw_linkpage'] ['ids'] ) )
			) {
				$this->do_tracking_action( $position['code'], $tracking_code['code'] );
			}
		} elseif ( $post_id ) {
			if ( ! isset( $position['post_types_excluded'][ $post_type ]['active'] )
			     || ( ! in_array( $post_id, $position['post_types_excluded'][ $post_type ] ['ids'] )
			          && ! in_array( 'all', $position['post_types_excluded'][ $post_type ] ['ids'] ) )
			) {
				$this->do_tracking_action( $position['code'], $tracking_code['code'] );
			}
		} else {
			$this->do_tracking_action( $position['code'], $tracking_code['code'] );
		}
	}

	/**
	 * @param string $type
	 * @param array $position
	 * @param array $tracking_code
	 *
	 * @return false|void
	 */
	public function do_conditional_logic( string $type, array $position, array $tracking_code ) {
		if ( ! $position || ! $tracking_code ) {
			return false;
		}

		$current_page_path   = $this->parse_current_page_path();
		$current_linkpage_id = ClickwhaleLinkpagesHelper::get_linkpage_id_by_slug( $current_page_path );
		$current_page_id     = $this->get_current_page_id_by_url();
		$current_post_type   = get_post( $current_page_id ) ? get_post( $current_page_id )->post_type : '';

		if ( $type === 'included' ) {
			$this->do_included_conditional_logic(
				$position,
				$tracking_code,
				$current_linkpage_id,
				$current_page_id,
				$current_post_type );
		}

		if ( $type === 'excluded' ) {
			$this->do_excluded_conditional_logic(
				$position,
				$tracking_code,
				$current_linkpage_id,
				$current_page_id,
				$current_post_type );
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

		foreach ( $tracking_codes as $tracking_code ) {
			$position = maybe_unserialize( $tracking_code['position'] );

			if ( $position['pages'] === 'all' ) {
				if ( isset( $position['post_types_excluded'] ) ) {
					$this->do_conditional_logic( 'excluded', $position, $tracking_code );
				} else {
					$this->do_tracking_action( $position['code'], $tracking_code['code'] );
				}
			} else {
				$this->do_conditional_logic( 'included', $position, $tracking_code );
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

