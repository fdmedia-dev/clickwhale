<?php

namespace clickwhale\includes\admin;

use clickwhale\includes\admin\helpers\Clickwhale_Helper;
use stdClass;

/**
 * Methods for getting Clickwhale data
 *
 * @since 1.5.0
 */
class Clickwhale_Methods {
	/**
	 * get array with all links
	 *
	 * @return array|object|stdClass[]|null
	 */

	public static function get_table_name( $name ) {

	}

	public static function get_clickwhale_links() {
		global $wpdb;

		$table = Clickwhale_Helper::get_clickwhale_bd_table_name( 'links' );

		return $wpdb->get_results( "SELECT * FROM $table", ARRAY_A );
	}

	public static function get_clickwhale_link_by_id( string $id ) {
		global $wpdb;

		if ( ! $id ) {
			return;
		}

		$id = intval( $id );
	}
}