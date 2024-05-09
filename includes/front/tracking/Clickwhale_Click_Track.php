<?php
namespace clickwhale\includes\front\tracking;

use clickwhale\includes\helpers\Linkpages_Helper;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Clickwhale_Click_Track {
	/**
	 * Link ID
	 *
	 * @since    1.0.0
	 * @access   protected
	 */
	protected $link_id;
	protected $user;
	protected $visitor;
	protected $is_custom;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param string $link_id Link id.
	 *
	 * @since    1.0.0
	 */
	public function __construct( string $link_id = '', bool $is_custom = false ) {
		$this->link_id   = $link_id;
		$this->is_custom = $is_custom;
		$this->visitor   = new Clickwhale_Visitor_Track();

		if ( $this->visitor->visitor_id ) {
			$this->update_track_database( $this->visitor->visitor_id );
		}
	}

	private function get_link_referer() {
		return ! empty( $_SERVER['HTTP_REFERER'] ) ? $_SERVER['HTTP_REFERER'] : '';
	}

	private function get_linkpage_id(): int {
		$url = strtolower( $this->get_link_referer() );

		if ( ! $url && ! str_contains( $url, get_bloginfo( 'url' ) ) ) {
			return false;
		}
		$url    = strtok( $url, '?' );
		$url    = str_replace( get_bloginfo( 'url' ), '', $url );
		$url    = str_replace( '/', '', $url );
		$result = Linkpages_Helper::get_by_slug( $url );

		return $result['id'] ?? 0;
	}

	private function update_track_database( $visitor_id ) {
		global $wpdb;

		$table_name             = $wpdb->prefix . 'clickwhale_track';
		$item                   = [];
		$item['event_type']     = 'click';
		$item['link_id']        = ! $this->is_custom ? $this->link_id : 0;
		$item['custom_link_id'] = $this->is_custom ? $this->link_id : '';
		$item['linkpage_id']    = $this->get_linkpage_id();
		$item['visitor_id']     = $visitor_id;
		$item['referer']        = $this->get_link_referer();
		$item['created_at']     = gmdate( 'Y-m-d H:i:s' );

		return $wpdb->insert( $table_name, $item );
	}

}