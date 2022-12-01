<?php

class ClickwhaleLinkpagesHelper {

	/**
	 * Count linkpages in DB
	 *
	 * @return string|null
	 */
	public static function get_linkpages_count() {
		global $wpdb;

		return intval( $wpdb->get_var( "SELECT count(*) FROM {$wpdb->prefix}clickwhale_linkpages" ) );
	}

	/**
	 * Filter function
	 * return number of available links on linkpage
	 * @return mixed|void
	 */
	public static function get_links_limit() {
		return apply_filters( 'clickwhale_linkpage_links_limit', 10 );
	}

	/**
	 * Filter function
	 * return number of available linkpages
	 * @return mixed|void
	 */
	public static function get_limit() {
		return apply_filters( 'clickwhale_linkpages_limit', 1 );
	}

}