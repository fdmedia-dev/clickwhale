<?php

class Clickwhale_Click_Track {
	/**
	 * Link ID
	 *
	 * @since    1.0.0
	 * @access   protected
	 */
	protected $link_id;
	protected $parser;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param string $link_id Link id.
	 *
	 * @since    1.0.0
	 */
	public function __construct( $link_id = 0 ) {
		$this->link_id = (int) $link_id;
		$this->parser  = new Clickwhale_Parser( $_SERVER['HTTP_USER_AGENT'] );
	}

	private function get_user_ip() {
		return wp_privacy_anonymize_ip( $_SERVER['REMOTE_ADDR'] );
	}

	private function get_user_salt() {
		return date( 'Y-m-d' );
	}

	private function get_link_referer() {
		$referer = isset( $_SERVER['HTTP_REFERER'] ) ? $_SERVER['HTTP_REFERER'] : '';

		return $referer;
	}

	private function generate_hash() {
		$ip      = $this->get_user_ip();
		$date    = $this->get_user_salt();
		$ua = $this->parser->ua;
		$hash    = $date . $ip . $ua;

		return hash( 'md5', $hash );
	}

	private function update_clicks_database() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'clickwhale_clicks';
		//var_dump($device);
		if ( ! $this->parser->bot ) {

			$id   = $this->link_id;
			$hash = $this->generate_hash();
			//$check  = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE link_id=$id AND visitor_hash='$hash'");

			//if(!$check){
			$item                 = [];
			$item['link_id']      = $id;
			$item['visitor_hash'] = $hash;
			$item['browser']      = $this->parser->ua;
			$item['os']           = $this->parser->os;
			$item['device']       = $this->parser->type;
			$item['referer']      = $this->get_link_referer();
			$item['created_at']   = date( 'Y-m-d H:m:s' );

			$result = $wpdb->insert( $table_name, $item );
			//}
		} else {
			return false;
		}
	}

	public function track() {

		$this->update_clicks_database();

	}

}