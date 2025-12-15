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
            esc_html__( 'Currently, a maximum of %d %s can be added.', 'clickwhale' ),
            self::get_limit(),
            ( self::get_limit() === 1 ) ? esc_html__( 'link', 'clickwhale' ) : esc_html__( 'links', 'clickwhale' )
        );
    }

    /**
     * @param int $length
     * @return string
     */
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

    /**
     * @param int $link_id
     * @param string $meta_key
     * @return array
     */
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

    /**
     * @return array
     */
    public static function get_redirections(): array {
        return array(
            301 => __( '301: Moved permanently', 'clickwhale' ),
            302 => __( '302: Found / Moved temporarily', 'clickwhale' ),
            303 => __( '303: See other', 'clickwhale' ),
            307 => __( '307: Temporarily redirect', 'clickwhale' ),
            308 => __( '308: Permanent redirect', 'clickwhale' )
        );
    }

    /**
     * @return array
     */
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

    /**
     * @param string $clauses
     * @param array $params
     * @return string
     */
    private static function query_links_with_click_data( string $clauses = '', array $params = [] ): string {
        global $wpdb;
        $table_links = Helper::get_db_table_name( 'links' );
        $table_track = Helper::get_db_table_name( 'track' );;

        return $wpdb->prepare(
            "SELECT links.*, COALESCE(track.clicks,0) AS clicks_count
            FROM $table_links links
            LEFT JOIN (
              SELECT link_id, COUNT(*) clicks
              FROM $table_track
              WHERE event_type='click'
              GROUP BY link_id
            ) track ON links.id = track.link_id
            $clauses",
            ...$params
        );
    }

    /**
     * @return array
     */
    public static function get_links_with_click_data(): array {
        global $wpdb;

        return (array) $wpdb->get_results(
            self::query_links_with_click_data( 'ORDER BY links.id DESC' ),
            ARRAY_A
        );
    }

    /**
     * @param int $id
     * @return array
     */
    public static function get_link_by_id_with_click_data( int $id ): array {
        global $wpdb;

        return (array) $wpdb->get_row(
            self::query_links_with_click_data( 'WHERE links.id = %d LIMIT 1', array( $id ) ),
            ARRAY_A
        );
    }

    /**
     * @param string $slug
     * @return array
     */
    public static function get_link_by_slug_with_click_data( string $slug ): array {
        global $wpdb;

        return (array) $wpdb->get_row(
            self::query_links_with_click_data( 'WHERE links.slug = %s LIMIT 1', array( $slug ) ),
            ARRAY_A
        );
    }

    public static function render_link_rows( array $rows ): string {
        $links_per_row_limit = 5;
        $html = '';

        foreach ( $rows as $row ) {
            $post_id = intval( $row['ID'] );
            $edit = '<a href="' . esc_url( get_edit_post_link( $post_id ) ) . '" target="_blank" rel="noopener" title="' . esc_html__( 'Edit post', 'clickwhale' ) . '"><svg class="feather"><use href="' . CLICKWHALE_ADMIN_ASSETS_DIR . '/images/feather-sprite.svg#edit-2"></use></svg></a>';
            $view = '<a href="' . esc_url( get_permalink( $post_id ) ) . '" target="_blank" rel="noopener" title="' . esc_html__( 'View post', 'clickwhale' ) . '"><svg class="feather"><use href="' . CLICKWHALE_ADMIN_ASSETS_DIR . '/images/feather-sprite.svg#eye"></use></svg></a>';
            $titles = $row['titles'];
            $total = $row['total'];

            $html .= '<tr>';
            $html .= '<td>' . $post_id . '</td>';
            $html .= '<td>' . esc_html( $row['post_type'] ) . '</td>';
            $html .= '<td>' . esc_html( wp_unslash( $row['post_title'] ) ) . '</td>';
            $html .= '<td class="link_scanner-titles">';
            $html .= '<ol>';

            for ( $i = 0; $i < $total; $i++ ) {
                $title = $titles[$i];
                $li_class = ( $i >= $links_per_row_limit ) ? ' class="limited hidden"' : '';
                $html .= '<li' . $li_class . '>' . esc_html( $title ) . '</li>';
            }

            $html .= '</ol>';

            if ( $total > $links_per_row_limit ) {
                $html .= '<a href="#" class="link_scanner-toggle-titles" data-show-all="' . esc_attr__( 'Show all', 'clickwhale' ) . '" data-show-less="' . esc_attr__( 'Show less', 'clickwhale' ) . '">' . esc_html__( 'Show all', 'clickwhale' ) . '</a>';
            }

            $html .= '</td>';
            $html .= '<td class="link_scanner-actions">' . $edit . $view . '</td>';
            $html .= '</tr>';
        }

        return $html;
    }
}
