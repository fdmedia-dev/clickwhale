<?php
namespace clickwhale\includes\helpers;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Linkpages_Helper extends Helper_Abstract {


    /**
     * @var string
     */
	protected static $single = 'linkpage';

    /**
     * @var string
     */
	protected static $plural = 'linkpages';

    /**
     * @var int
     */
	protected static $limit = 2;

	/**
	 * Return link pages limitation notice string
	 * @return string
	 * @since 1.4.0
	 */
	public static function get_limitation_notice(): string {
		return sprintf(
			__( 'Currently, a maximum of %d link page can be added.', CLICKWHALE_NAME ),
			self::get_limit()
		);
	}

	/**
	 * Return link page links limitation notice string
	 * @return string
	 * @since 1.4.0
	 */
	public static function get_links_limitation_notice(): string {
		return sprintf(
			__( 'Currently, a maximum of %d links can be added.', CLICKWHALE_NAME ),
			self::get_linkpage_links_limit()
		);
	}

	/**
	 * Filter function
	 * return number of available links on linkpage
	 * @return mixed|void
	 */
	public static function get_linkpage_links_limit() {
		return apply_filters( 'clickwhale_linkpage_links_limit', 10 );
	}

	/**
	 * Check by slug if linkpage exists
	 *
	 * @param string $slug
	 *
	 * @return string|null
	 * @since 1.2.0
	 */
	public static function is_linkpage( string $slug ): ?string {
		global $wpdb;

		$table = Helper::get_db_table_name( self::$plural );

		return $wpdb->get_var(
			$wpdb->prepare( "SELECT count(*) FROM $table WHERE slug=%s", $slug )
		);

	}

	/**
	 * @since 1.3.0
	 */
	public static function get_linkpage_link_clicks( string $linkpage_id, string $link_id, bool $is_link = true ) {
		global $wpdb;

		$table = Helper::get_db_table_name( 'track' );

		if ( $is_link ) {
			$result = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM $table WHERE linkpage_id = %s AND link_id=%s AND event_type = 'click'",
					$linkpage_id, $link_id )
			);
		} else {
			$result = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM $table WHERE linkpage_id = %s AND custom_link_id=%s AND event_type = 'click'",
					$linkpage_id, $link_id )
			);
		}

		return $result ?? 0;
	}

	/**
	 * Check if Link page slug already exists
	 *
	 * @param string $slug
	 *
	 * @return bool
	 */
	public static function check_slug( string $slug ): bool {
		global $wpdb;

		if ( ! $slug ) {
			return false;
		}
		$slug = esc_html( $slug );

		return (bool) $wpdb->get_row( "SELECT post_name FROM {$wpdb->prefix}posts WHERE post_name='$slug'", 'ARRAY_A' );
	}

//	public static function get_meta( int $linkpage_id, string $meta_key, string $output = "ARRAY_A" ) {
//		global $wpdb;
//
//		$table = Helper::get_db_table_name( 'meta' );
//
//		return $wpdb->get_row(
//			$wpdb->prepare(
//				"SELECT * FROM $table WHERE linkpage_id=%d AND meta_key=%s",
//				$linkpage_id, $meta_key ),
//			$output
//		);
//	}
}