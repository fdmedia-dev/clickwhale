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
        $count = self::get_limit();

        /* translators: %1$d: maximum number of link pages */
        $text = _n(
            'Currently, a maximum of %1$d link page can be added.',
            'Currently, a maximum of %1$d link pages can be added.',
            $count,
            'clickwhale'
        );

        return sprintf( $text, intval( $count ) );
    }

    /**
     * Return link page links limitation notice string
     * @return string
     * @since 1.4.0
     */
    public static function get_links_limitation_notice(): string {
        $count = self::get_linkpage_links_limit();

        /* translators: %1$d: maximum number of links */
        $text = _n(
            'Currently, a maximum of %1$d link can be added.',
            'Currently, a maximum of %1$d links can be added.',
            $count,
            'clickwhale'
        );

        return sprintf( $text, intval( $count ) );
    }

    /**
     * Filter function
     * return number of available links on link page
     * @return int
     */
    public static function get_linkpage_links_limit(): int {
        return apply_filters( 'clickwhale_linkpage_links_limit', 10 );
    }

    /**
     * @return array
     */
    public static function get_current_linkpage(): array {
        if ( empty( $_SERVER['HTTP_REFERER'] ) ) {
            return array();
        }

        $url = esc_url_raw( $_SERVER['HTTP_REFERER'] );

        if ( ! $url ) {
            return array();
        }

        $url = strtolower( $url );
        $home_url = home_url();

        if ( ! str_contains( $url, $home_url ) ) {
            return array();
        }

        $url = strtok( $url, '?' );
        $url = str_replace( $home_url, '', $url );
        $url = sanitize_title( $url );

        if ( empty( $url ) ) {
            return array();
        }

        return self::get_by_slug( $url );
    }

    /**
     * Check by slug if link page exists
     *
     * @param string $slug
     * @return int
     * @since 1.2.0
     */
    public static function is_linkpage( string $slug ): int {
        $slug = sanitize_title( $slug );

        if ( empty( $slug ) ) {
            return 0;
        }

        global $wpdb;
        $table = Helper::get_db_table_name( self::$plural );

        return intval( $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$table} WHERE slug=%s",
                $slug
            )
        ) );
    }

    /**
     * @since 1.3.0
     */
    public static function get_linkpage_link_clicks( string $linkpage_id, string $link_id, bool $is_link = true ): int {
        global $wpdb;
        $table = Helper::get_db_table_name( 'track' );
        $column = ( $is_link ) ? 'link_id' : 'custom_link_id';

        return intval( $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$table} WHERE linkpage_id = %s AND $column = %s AND event_type = 'click'",
                sanitize_text_field( $linkpage_id ),
                sanitize_text_field( $link_id )
            )
        ) );
    }
}
