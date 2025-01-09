<?php
namespace clickwhale\includes\helpers;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Linkpages_Helper extends Helper_Abstract {


    /**
     * @var string
     */
	protected static string $single = 'linkpage';

    /**
     * @var string
     */
	protected static string $plural = 'linkpages';

    /**
     * @var int
     */
	protected static int $limit = 2;

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
     * @return int
     * @since 1.2.0
     */
    public static function is_linkpage( string $slug ): int {
        global $wpdb;
        $table = Helper::get_db_table_name( self::$plural );

        return intval( $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM $table WHERE slug=%s",
                sanitize_text_field( $slug )
            )
        ) );
    }

    /**
     * @since 1.3.0
     */
    public static function get_linkpage_link_clicks( string $linkpage_id, string $link_id, bool $is_link = true ): int {
        global $wpdb;
        $table = Helper::get_db_table_name( 'track' );

        if ( $is_link ) {
            return intval( $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM $table WHERE linkpage_id = %s AND link_id=%s AND event_type = 'click'",
                    sanitize_text_field( $linkpage_id ),
                    sanitize_text_field( $link_id )
                )
            ) );
        } else {
            return intval( $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM $table WHERE linkpage_id = %s AND custom_link_id=%s AND event_type = 'click'",
                    sanitize_text_field( $linkpage_id ),
                    sanitize_text_field( $link_id )
                )
            ) );
        }
    }

    /**
     * Check if Link page slug already exists
     *
     * @param string $slug
     * @return bool
     */
    public static function check_slug( string $slug ): bool {
        global $wpdb;

        if ( ! $slug ) {
            return false;
        }

        return (bool) $wpdb->get_row(
            $wpdb->prepare(
                "SELECT post_name FROM {$wpdb->prefix}posts WHERE post_name=%s",
                sanitize_text_field( $slug )
            )
        );
    }
}