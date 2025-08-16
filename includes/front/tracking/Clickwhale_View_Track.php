<?php
namespace clickwhale\includes\front\tracking;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Clickwhale_View_Track {

    /**
     * @var Clickwhale_Visitor_Track
     */
    protected Clickwhale_Visitor_Track $visitor;

    /**
     * Initialize the class and set its properties.
     * @since    1.0.0
     */
    public function __construct() {
        $this->visitor = new Clickwhale_Visitor_Track();
    }

    public function maybe_update_track_database( int $linkpage_id = 0 ) {
        if ( empty( $this->visitor->visitor_id ) ) {
            return;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'clickwhale_track';
        $item = array(
            'event_type'  => 'view',
            'link_id'     => 0,
            'linkpage_id' => $linkpage_id,
            'visitor_id'  => $this->visitor->visitor_id,
            'referer'     => esc_url_raw( $_SERVER['HTTP_REFERER'] ?? '' ),
            'created_at'  => gmdate( 'Y-m-d H:i:s' )
        );

        $wpdb->insert( $table_name, $item );
    }
}
