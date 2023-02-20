<?php

/**
 * @since 1.2.0
 */
class ClickwhaleTrackingCodesHelper {

	/**
	 * Count linkpages in DB
	 *
	 * @return string|null
	 */
	public static function get_count() {
		global $wpdb;

		return intval( $wpdb->get_var( "SELECT count(*) FROM {$wpdb->prefix}clickwhale_tracking_codes" ) );
	}

	/**
	 * Filter function
	 * return number of available tracking codes
	 * @return mixed|void
	 */
	public static function get_limit() {
		return apply_filters( 'clickwhale_tracking_codes_limit', 3 );
	}

}