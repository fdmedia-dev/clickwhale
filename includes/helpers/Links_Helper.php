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
    protected static string $single = 'link';

    /**
     * @var string
     */
    protected static string $plural = 'links';

    /**
     * Links limitation
     * @var int
     */
    protected static int $limit = 9999;

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
                'clickwhale'
            ),
            self::get_limit()
        );
    }

    public static function generate_random_slug( int $length = 6 ): string {
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

    public static function get_meta( int $link_id, string $meta_key ): array {
        global $wpdb;
        $table = Helper::get_db_table_name( 'meta' );

        return (array) $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table
                WHERE link_id=%d
                AND meta_key=%s",
                $link_id,
                sanitize_text_field( $meta_key )
            ),
            ARRAY_A
        );
    }

    public static function get_redirections(): array {
        return array(
            301 => __( '301: Moved permanently', 'clickwhale' ),
            302 => __( '302: Found / Moved temporarily', 'clickwhale' ),
            303 => __( '303: See other', 'clickwhale' ),
            307 => __( '307: Temporarily redirect', 'clickwhale' ),
            308 => __( '308: Permanent redirect', 'clickwhale' )
        );
    }

    public static function get_link_targets(): array {
        return array(
            'blank' => __( 'New tab/window', 'clickwhale' ),
            'self' => __( 'Same tab/window', 'clickwhale' )
        );
    }

    /**
     * Sanitize Link slug.
     * Allowed: `-`, `_`, `/`, a-z, A-Z, 0-9
     *
     * @param string $slug
     * @return string
     */
    public static function sanitize_slug( string $slug ): string {
        $slug = trim( $slug );
        $slug = sanitize_text_field( $slug );
        $slug = str_replace( ' ', '-', $slug ); // replace inner `spaces` with `-`
        $slug = preg_replace( '#[^a-zA-Z0-9_/-]#', '', $slug );
        $slug = preg_replace( '#/{2,}#', '/', $slug ); // replace repeated slashes with a single one
        $slug = trim( $slug, '/' ); // trim leading and lagging `slashes`
        return $slug;
    }
}
