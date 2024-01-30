<?php
namespace clickwhale\includes\helpers;

use Exception;

class Links_Helper extends Helper_Abstract {

    /**
     * @var string
     */
	protected static $single = 'link';

    /**
     * @var string
     */
	protected static $plural = 'links';

    /**
     * @var int
     */
	protected static $limit = 9999;

	/**
	 * Return limitation notice string
	 * @return string
	 * @since 1.4.0
	 */
	public static function get_limitation_notice(): string {
		return sprintf(
			__( 'Currently, a maximum of %d links can be added.', CLICKWHALE_SLUG ),
			self::get_limit()
		);
	}

	/**
	 * @throws Exception
	 */
	public static function get_link_random_slug( int $length = 6 ): string {
		$characters = 'abcdefghijklmnopqrstuvwxyz';
		$string     = '';

		for ( $i = 0; $i < $length; $i ++ ) {
			$string .= $characters[ random_int( 0, strlen( $characters ) - 1 ) ];
		}

		return $string;
	}

	public static function get_meta( int $link_id, string $meta_key, string $output = "ARRAY_A" ) {
		global $wpdb;

		$table = Helper::get_clickwhale_bd_table_name( 'meta' );

		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $table WHERE link_id=%d AND meta_key=%s",
				$link_id, $meta_key ),
			$output
		);
	}
}