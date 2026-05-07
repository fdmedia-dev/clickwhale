<?php
namespace Clickwhale\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

abstract class Helper_Abstract {

    /**
     * @var string
     */
    protected static string $single;

    /**
     * @var string
     */
    protected static string $plural;

    /**
     * @var int
     */
    protected static int $limit;

    /**
     * Get items count
     *
     * @return int
     * @since 1.6.0
     */
    public static function get_count(): int {
        global $wpdb;
        $table = Helper::get_db_table_name( static::$plural );

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
        return intval( $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" ) );
    }

    /**
     * Filter function
     * return number of available items
     *
     * @return int
     * @since 1.6.0
     */
    public static function get_limit(): int {
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
                wp_kses( static::get_limitation_notice(), Helper::get_allowed_tags() ),
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
     * @param string $orderby
     * @param string $order
     * @param string $output
     *
     * @return array
     * @since 1.6.0
     */
    public static function get_all( string $orderby = 'title', string $order = 'asc', string $output = 'OBJECT' ): array {
        global $wpdb;
        $table = Helper::get_db_table_name( static::$plural );
        $orderby = sanitize_text_field( $orderby );
        $order = strtolower( $order ) === 'desc' ? 'DESC' : 'ASC';

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
        return (array) $wpdb->get_results( "SELECT * FROM {$table} order by $orderby $order", $output );
    }

    /**
     *  Get item by ID
     *
     * @param int $id
     *
     * @return array
     * @since 1.6.0
     */
    public static function get_by_id( int $id ): array {
        global $wpdb;
        $table = Helper::get_db_table_name( static::$plural );

        // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
        return (array) $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE id=%d",
                $id
            ),
            ARRAY_A
        );
        // phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    }

    /**
     * Get item by title
     *
     * @param string $title
     *
     * @return array
     * @since 1.6.0
     */
    public static function get_by_title( string $title ): array {
        global $wpdb;
        $table = Helper::get_db_table_name( static::$plural );

        // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
        return (array) $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE title=%s",
                sanitize_text_field( $title )
            ),
            ARRAY_A
        );
        // phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    }

    /**
     * Get item by slug
     *
     * @param string $slug
     * @return array
     * @since 1.6.0
     */
    public static function get_by_slug( string $slug ): array {
        global $wpdb;
        $table = Helper::get_db_table_name( static::$plural );

        // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
        return (array) $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE slug=%s",
                $slug
            ),
            ARRAY_A
        );
        // phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    }
}
