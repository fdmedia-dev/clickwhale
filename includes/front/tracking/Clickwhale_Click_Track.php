<?php
namespace clickwhale\includes\front\tracking;

use clickwhale\includes\helpers\Linkpages_Helper;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Clickwhale_Click_Track {

    protected string $link_id;

    protected bool $is_custom;

    /**
     * Initialize the class and set its properties.
     *
     * @param string $link_id
     * @param bool $is_custom
     */
    public function __construct( string $link_id = '', bool $is_custom = false ) {
        $this->link_id = $link_id;
        $this->is_custom = $is_custom;
    }

    /**
     * @return array
     */
    public function proceed_click_track(): array {
        $visitor = new Clickwhale_Visitor_Track();

        if ( empty( $visitor->visitor_id ) ) {
            return array();
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'clickwhale_track';
        $linkpage = Linkpages_Helper::get_current_linkpage();

        $data = array(
            'event_type'     => 'click',
            'link_id'        => ! $this->is_custom ? $this->link_id : 0,
            'custom_link_id' => $this->is_custom ? $this->link_id : '',
            'linkpage_id'    => $linkpage['id'] ?? 0,
            'visitor_id'     => $visitor->visitor_id,
            'referer'        => esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER']) ?? '' ), // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated
            'created_at'     => gmdate( 'Y-m-d H:i:s' )
        );

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $result = $wpdb->insert( $table_name, $data );

        if ( empty( $result ) ) {
            return array();
        }

        return $data;
    }
}
