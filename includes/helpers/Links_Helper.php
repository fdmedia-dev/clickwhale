<?php
namespace clickwhale\includes\helpers;

use Exception;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

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
     * Links limitation
     * @var int
     */
	protected static $limit = 9999;

	/**
	 * Return links limitation notice string
	 * @return string
	 * @since 1.4.0
	 */
	public static function get_limitation_notice(): string {
		return sprintf(
            _n(
                'Currently, a maximum of 1 link can be added.',
                'Currently, a maximum of %d links can be added.',
                self::get_limit(),
                CLICKWHALE_NAME
            ),
			self::get_limit()
		);
	}

	public static function get_link_random_slug( int $length = 6 ): string {
		$characters = 'abcdefghijklmnopqrstuvwxyz';
		$string     = '';

        try {
            for ( $i = 0; $i < $length; $i ++ ) {
                $string .= $characters[ random_int( 0, strlen( $characters ) - 1 ) ];
            }

        } catch ( Exception $e ) {
            return $string;
        }

		return $string;
	}

	public static function get_meta( int $link_id, string $meta_key, string $output = "ARRAY_A" ) {
		global $wpdb;

		$table = Helper::get_db_table_name( 'meta' );

		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $table WHERE link_id=%d AND meta_key=%s",
				$link_id, $meta_key ),
			$output
		);
	}
}