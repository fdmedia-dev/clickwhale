<?php

class ClickwhaleCategoriesHelper {

	/**
	 * Count Categories in DB
	 *
	 * @return string|null
	 */
	public static function get_categories_count() {
		global $wpdb;

		return intval( $wpdb->get_var( "SELECT count(*) FROM {$wpdb->prefix}clickwhale_categories" ) );
	}

	/**
	 * Filter function
	 * return number of available links
	 * @return mixed|void
	 */
	public static function get_limit() {
		return apply_filters( 'clickwhale_categories_limit', 10 );
	}

}