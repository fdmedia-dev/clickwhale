<?php
namespace clickwhale\includes\helpers;

use stdClass;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

abstract class Helper_Abstract {

    /**
     * @var string
     */
	protected static $single;

    /**
     * @var string
     */
	protected static $plural;

    /**
     * @var int
     */
	protected static $limit;

	/**
	 * Get items count
	 *
	 * @return int
	 * @since 1.6.0
	 */
	public static function get_count(): int {
		global $wpdb;

		$table = Helper::get_db_table_name( static::$plural );
		$count = $wpdb->get_var( "SELECT count(*) FROM $table" );

		return $count ? intval( $count ) : 0;
	}

	/**
	 * Filter function
	 * return number of available items
	 *
	 * @return mixed|void
	 * @since 1.6.0
	 */
	public static function get_limit() {
		return apply_filters( 'clickwhale_' . static::$plural . '_limit', static::$limit );
	}

	/**
	 * Check if limit was reached
	 *
	 * @return bool
	 * @since 1.6.0
	 */
	public static function is_limit(): bool {
		return self::get_count() >= self::get_limit();
	}

	abstract public static function get_limitation_notice(): string;

	/**
	 * Show message and exit when limit is reached
	 *
	 * @param $id
	 * @return void
	 */
	public static function get_limitation_error( $id ) {
		if ( empty( $id ) && static::is_limit() ) {

			wp_die(
				static::get_limitation_notice(),
				'Error',
				array(
					'back_link' => true,
					'exit'      => true
				)
			);
		}
	}

	/**
	 *  Get all items with order
	 *
	 * @param string $output
	 * @param string $orderby
	 * @param string $order
	 *
	 * @return array|object|stdClass|null
	 * @since 1.6.0
	 */
	public static function get_all( string $orderby = 'title', string $order = "asc", string $output = "OBJECT" ) {
		global $wpdb;

		$table = Helper::get_db_table_name( static::$plural );

		return $wpdb->get_results(
			"SELECT * FROM $table order by $orderby $order",
			$output );
	}

	/**
	 *  Get item by ID
	 *
	 * @param int $id
	 * @param string $output
	 *
	 * @return array|object|stdClass|null
	 * @since 1.6.0
	 */
	public static function get_by_id( int $id, string $output = "ARRAY_A" ) {
		global $wpdb;

		$table = Helper::get_db_table_name( static::$plural );

		return $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM $table WHERE id=%d", $id ),
			$output
		);
	}

	/**
	 * Get item by title
	 *
	 * @param string $title
	 * @param string $output
	 *
	 * @return array|object|stdClass|null
	 * @since 1.6.0
	 */
	public static function get_by_title( string $title, string $output = "ARRAY_A" ) {
		global $wpdb;

		$table = Helper::get_db_table_name( static::$plural );

		return $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM $table WHERE title=%s", $title ),
			$output
		);
	}

	/**
	 * Get item by slug
	 *
	 * @param string $slug
	 * @param string $output
	 *
	 * @return array|object|stdClass|null
	 * @since 1.6.0
	 */
	public static function get_by_slug( string $slug, string $output = "ARRAY_A" ) {
		global $wpdb;

		$table = Helper::get_db_table_name( static::$plural );

		return $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM $table WHERE slug=%s", $slug ),
			$output
		);
	}
}