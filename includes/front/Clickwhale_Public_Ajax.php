<?php

namespace clickwhale\includes\front;

class Clickwhale_Public_Ajax {
	private static ?Clickwhale_Public_Ajax $instance = null;

	/**
	 * @return Clickwhale_Public_Ajax
	 */
	public static function get_instance(): ?Clickwhale_Public_Ajax {
		if ( empty( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * @return void
	 * @since 1.1.0
	 */
	public function track_custom_link() {
		check_ajax_referer( 'track_custom_link', 'security' );

		if ( ! isset( $_POST['id'] ) || ! $_POST['id'] ) {
			wp_send_json_error( 'Track Error!' );

			wp_die();
		}

		// Track click on link
		$track = new Clickwhale_Click_Track( $_POST['id'], true );

		wp_send_json_success();

		wp_die();
	}
}