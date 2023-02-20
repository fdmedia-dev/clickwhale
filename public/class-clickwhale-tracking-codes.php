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

	private function get_current_page_id(): int {
		return url_to_postid( get_bloginfo( 'url' ) . '/' . $this->parse_current_page_path() . '/' );
	}

	private function get_tracking_codes() {
		global $wpdb;

		return $wpdb->get_results(
			"SELECT * FROM {$wpdb->prefix}clickwhale_tracking_codes WHERE is_active = '1' OR is_active = 1",
			ARRAY_A
		);
	}

	public function get_active_post_types( array $array ): array {
		if ( empty( $array ) ) {
			return [];
		}

		$filteredArray = [];
		foreach ( $array as $key => $value ) {
			if ( is_array( $value ) && ! empty( $value ) && isset( $value['active'] ) ) {
				$filteredArray[ $key ] = $value;
			}
		}

		return $filteredArray;
	}

	public function do_conditional_logic( array $position, array $tracking_code ) {
		if ( ! $position || ! $tracking_code ) {
			return false;
		}

		$current_page_path = $this->parse_current_page_path();
		$current_page_id   = $this->get_current_page_id();

		foreach ( $this->get_active_post_types( $position['post_types'] ) as $k => $post_type ) {

			$current_page = ClickwhaleLinkpagesHelper::is_linkpage( $current_page_path )
				? ClickwhaleLinkpagesHelper::get_linkpage_id_by_slug( $current_page_path )
				: $current_page_id;


			if ( in_array( 'all', $post_type['ids'] ) || in_array( $current_page, $post_type['ids'] ) ) {
				$this->do_tracking_action( $position['code'], $tracking_code['code'] );
				break;
			}
		}

	}

	public function prepare_tracking_codes() {
		$tracking_codes = $this->get_tracking_codes();
		if ( ! $tracking_codes ) {
			return false;
		}

		foreach ( $tracking_codes as $tracking_code ) {
			$position = maybe_unserialize( $tracking_code['position'] );

			if ( $position['pages'] === 'all' ) {
				$this->do_tracking_action( $position['code'], $tracking_code['code'] );
			} else {
				$this->do_conditional_logic( $position, $tracking_code );
			}
		}
	}

	public function do_tracking_action( $position, $code ) {
		add_action( $position, function () use ( $code ) {
			echo wp_unslash( $code );
		} );
	}

}

