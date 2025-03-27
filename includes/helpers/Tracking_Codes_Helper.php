<?php
namespace clickwhale\includes\helpers;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * @since 1.2.0
 */
class Tracking_Codes_Helper extends Helper_Abstract {

    /**
     * @var string
     */
    protected static string $single = 'tracking_code';

    /**
     * @var string
     */
    protected static string $plural = 'tracking_codes';

    /**
     * @var int
     */
    protected static int $limit = 9999;

    /**
     * @var int
     */
    protected static int $active_limit = 3;

    /**
     * Filter function
     * return number of available items
     *
     * @return int
     * @since 1.6.0
     */
    public static function get_active_limit(): int {
        return apply_filters( 'clickwhale_active_' . static::$plural . '_limit', static::$active_limit );
    }

    public static function is_active_limit(): bool {
        return count( self::get_active() ) >= self::get_active_limit();
    }

    /**
     * @return array
     */
    public static function get_active(): array {
        global $wpdb;
        $table = Helper::get_db_table_name( 'tracking_codes' );

        return (array) $wpdb->get_results( "SELECT * FROM $table WHERE is_active = '1' OR is_active = 1", ARRAY_A );
    }

    /**
     * Return limitation notice string
     * @return string
     */
    public static function get_limitation_notice(): string {
        return sprintf(
            __( 'Currently, a maximum of %d %s can be active at the same time.', 'clickwhale' ),
            self::get_active_limit(),
            self::get_active_limit() === 1 ? __( 'tracking code', 'clickwhale' ) : __( 'tracking codes', 'clickwhale' )
        );
    }

    public static function get_default_post_types() {
        return apply_filters( 'clickwhale_tracking_code_default_post_types', Helper::get_post_types( 'name' ) );
    }

    public static function get_default_terms_tax() {
        return apply_filters( 'clickwhale_tracking_code_default_archives', array( 'category' ) );
    }
}
