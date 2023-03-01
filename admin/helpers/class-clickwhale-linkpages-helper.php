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

	/**
	 * Check if linkpage with slug exists
	 *
	 * @param string $slug
	 *
	 * @return string|null
	 * @since 1.2.0
	 */
	public static function is_linkpage( string $slug ) {
		global $wpdb;

		return $wpdb->get_var(
			$wpdb->prepare( "SELECT count(*) FROM {$wpdb->prefix}clickwhale_linkpages WHERE slug=%s", $slug )
		);

	}

	/**
	 * Return Linkpage ID by its slug
	 *
	 * @param string $slug
	 *
	 * @return string|null
	 * @since 1.2.0
	 */
	public static function get_linkpage_id_by_slug( string $slug ) {
		global $wpdb;

		if ( ! $slug ) {
			return 0;
		}

		$result = $wpdb->get_var(
			$wpdb->prepare( "SELECT id FROM {$wpdb->prefix}clickwhale_linkpages WHERE slug=%s", $slug )
		);

		return $result ?? 0;
	}

}