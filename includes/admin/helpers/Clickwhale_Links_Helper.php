<?php

namespace clickwhale\includes\admin\helpers;

class Clickwhale_Links_Helper {

	/**
	 * Filter function
	 * return number of available links
	 * @return mixed|void
	 */
	public static function get_limit() {
		return apply_filters( 'clickwhale_links_limit', 9999 );
	}

	/**
	 * @throws Exception
	 */
	public static function get_random_slug( int $length = 6 ): string {
		$characters = 'abcdefghijklmnopqrstuvwxyz';
		$string     = '';

		for ( $i = 0; $i < $length; $i ++ ) {
			$string .= $characters[ random_int( 0, strlen( $characters ) - 1 ) ];
		}

		return $string;
	}

	/**
	 * Get ClickWhale Link by ID
	 *
	 * @param $id
	 *
	 * @return array|object|stdClass|null
	 * @since 1.5.0
	 */
	public static function get_link_by_id( $id ) {
		global $wpdb;

		return $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$wpdb->prefix}clickwhale_links WHERE id=%d", $id ),
			ARRAY_A
		);
	}

	/**
	 * Get ClickWhale links categories
	 * @return array|object|stdClass[]|void
	 * @since 1.5.0
	 */
	public static function get_link_categories( $output = "OBJECT", $orderby = 'title', $order = "asc" ) {
		global $wpdb;

		$results = $wpdb->get_results(
			"SELECT * FROM {$wpdb->prefix}clickwhale_categories order by $orderby $order",
			$output );
		if ( ! empty( $results ) ) {
			return $results;
		}
	}

}