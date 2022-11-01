<?php

class Clickwhale_Visitor_Track {
	protected $parser;
	protected $user;
	protected $ua;
	protected $os;
	protected $device;
	protected $hash;

	public $visitor_id;
	public $event;

	public function __construct( $event ) {
		$this->parser = new Clickwhale_Parser( $_SERVER['HTTP_USER_AGENT'] );
		$this->user   = new Clickwhale_WP_User();
		$this->ua     = $this->parser->ua;
		$this->os     = $this->parser->os;
		$this->device = $this->parser->type;
		$this->hash   = $this->generate_hash();
		$this->event  = $event;

		if ( ! $this->user->disallow_track($this->event) && ! $this->parser->bot ) {
			$visitor = $this->get_visitor_id( $this->hash );
			if ( ! $visitor ) {
				$this->visitor_id = $this->update_visitors_database();
			} else {
				$this->visitor_id = intval( $visitor[0]['id'] );
			}
		}
	}

	private function get_user_ip() {
		return wp_privacy_anonymize_ip( $_SERVER['REMOTE_ADDR'] );
	}

	private function get_user_salt() {
		return $this->ua . $this->os . $this->device;
	}

	private function generate_hash() {
		return hash( 'md5', $this->get_user_salt() . $this->get_user_ip() );
	}

	public function get_visitor_id( $hash ) {
		global $wpdb;

		return $wpdb->get_results( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}clickwhale_visitors WHERE hash=%s", $hash ), ARRAY_A );
	}

	private function update_visitors_database() {
		global $wpdb;

		$table_visitors        = $wpdb->prefix . 'clickwhale_visitors';
		$visitor               = [];
		$visitor['hash']       = $this->hash;
		$visitor['browser']    = $this->ua;
		$visitor['os']         = $this->os;
		$visitor['device']     = $this->device;
		$visitor['created_at'] = gmdate( 'Y-m-d H:m:s' );

		$wpdb->insert( $table_visitors, $visitor );

		return $wpdb->insert_id;
	}
}