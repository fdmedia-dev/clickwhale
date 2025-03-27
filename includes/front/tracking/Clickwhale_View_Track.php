<?php
namespace clickwhale\includes\front\tracking;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Clickwhale_View_Track {

    /**
     * Link ID
     *
     * @since    1.0.0
     * @access   protected
     */
    protected int $linkpage_id;

    /**
     * @var Clickwhale_Visitor_Track
     */
    protected Clickwhale_Visitor_Track $visitor;

    /**
     * Initialize the class and set its properties.
     *
     * @param int $linkpage_id
     *
     * @since    1.0.0
     */
    public function __construct( int $linkpage_id = 0 ) {
        $this->linkpage_id = $linkpage_id;
        $this->visitor = new Clickwhale_Visitor_Track();

        if ( $this->visitor->visitor_id ) {
            $this->update_track_database( $this->visitor->visitor_id );
        }
    }

    private function get_link_referer() {
        return $_SERVER['HTTP_REFERER'] ?? '';
    }

    private function update_track_database( $visitor_id ) {
        global $wpdb;
        $table_name          = $wpdb->prefix . 'clickwhale_track';
        $item                = array();
        $item['event_type']  = 'view';
        $item['link_id']     = 0;
        $item['linkpage_id'] = $this->linkpage_id;
        $item['visitor_id']  = $visitor_id;
        $item['referer']     = $this->get_link_referer();
        $item['created_at']  = gmdate( 'Y-m-d H:i:s' );

        $wpdb->insert( $table_name, $item );
    }
}
