<?php

/**
 * @since 1.2.0
 */
class ClickwhaleTrackingCodesHelper {

	/**
	 * Count tracking codes in DB
	 *
	 * @return string|null
	 */
	public static function get_count() {
		global $wpdb;

		return intval( $wpdb->get_var( "SELECT count(*) FROM {$wpdb->prefix}clickwhale_tracking_codes" ) );
	}

	/**
	 * Count active tracking codes in DB
	 *
	 * @return string|null
	 */
	public static function get_active_count() {
		global $wpdb;

		return intval( $wpdb->get_var( "SELECT count(*) FROM {$wpdb->prefix}clickwhale_tracking_codes WHERE is_active = 1" ) );
	}

	/**
	 * Filter function
	 * return number of available tracking codes
	 * @return mixed|void
	 */
	public static function get_limit() {
		return apply_filters( 'clickwhale_tracking_codes_limit', 3 );
	}

	/**
	 * Return if active tracking codes reached limit in self::get_limit
	 * @return bool
	 */
	public static function is_limit(): bool {
		return self::get_active_count() >= self::get_limit();
	}

	/**
	 * Return limitation notice string
	 * @return string
	 */
	public static function get_limitation_notice(): string {
		return sprintf( 'Currently, a maximum of %d %s can be active at the same time.', self::get_limit(),
			self::get_limit() === 1 ? 'tracking code' : 'tracking codes' );
	}
}