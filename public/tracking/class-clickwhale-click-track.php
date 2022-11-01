<?php

class Clickwhale_Click_Track {
	/**
	 * Link ID
	 *
	 * @since    1.0.0
	 * @access   protected
	 */
	protected $link_id;
	protected $linkage_id;
	protected $user;
	protected $visitor;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param string $link_id Link id.
	 *
	 * @since    1.0.0
	 */
	public function __construct( $link_id = 0 ) {
		$this->link_id = (int) $link_id;
		$this->visitor = new Clickwhale_Visitor_Track( 'click' );

		if ( $this->visitor->visitor_id ) {
			$this->update_track_database( $this->visitor->visitor_id );
		}
	}

	private function get_link_referer() {
		return isset( $_SERVER['HTTP_REFERER'] ) && ! empty( $_SERVER['HTTP_REFERER'] ) ? $_SERVER['HTTP_REFERER'] : '';
	}

	private function get_linkpage_id() {
		global $wpdb;

		$url = strtolower( $this->get_link_referer() );

		if ( ! $url && ! str_contains( $url, get_bloginfo( 'url' ) ) ) {
			return false;
		}
		$url    = strtok( $url, '?' );
		$url    = str_replace( get_bloginfo( 'url' ), '', $url );
		$url    = str_replace( '/', '', $url );
		$result = $wpdb->get_row( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}clickwhale_linkpages WHERE slug=%s", $url ) );

		return $result->id;
	}

	private function update_track_database( $visitor_id ) {
		global $wpdb;

		$table_name          = $wpdb->prefix . 'clickwhale_track';
		$item                = [];
		$item['event_type']  = 'click';
		$item['link_id']     = $this->link_id;
		$item['linkpage_id'] = $this->get_linkpage_id() ? $this->get_linkpage_id() : 0;
		$item['visitor_id']  = $visitor_id;
		$item['referer']     = $this->get_link_referer();
		$item['created_at']  = gmdate( 'Y-m-d H:m:s' );

		return $wpdb->insert( $table_name, $item );
	}

}