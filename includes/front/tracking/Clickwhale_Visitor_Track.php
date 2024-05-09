<?php
namespace clickwhale\includes\front\tracking;

use clickwhale\includes\admin\Clickwhale_WP_User;
use clickwhale\includes\helpers\Helper;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Clickwhale_Visitor_Track {
	protected $parser;
	protected $user;
	protected $ua;
	protected $os;
	protected $device;
	protected $date;
	protected $hash;

	public $visitor_id;

	public function __construct() {
		$this->parser     = new Clickwhale_Parser( $_SERVER['HTTP_USER_AGENT'] );
		$this->user       = new Clickwhale_WP_User();
		$this->ua         = $this->parser->ua;
		$this->os         = $this->parser->os;
		$this->device     = $this->parser->type;
		$this->date       = gmdate( 'Y-m-d H:i:s' );
		$this->hash       = $this->generate_hash();
		$this->visitor_id = $this->proceed_visitor();
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

	public function get_visitor_by_hash( $hash ) {
		global $wpdb;

		$table = Helper::get_db_table_name( 'visitors' );

		return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table WHERE hash=%s",
			$hash ), ARRAY_A );
	}

	/**
	 * @return int
	 */
	public function proceed_visitor() {

		$id = 0;

		if ( ! $this->user->disallow_track() && ! $this->parser->bot ) {
			$visitor_arr      = $this->get_visitor_by_hash( $this->hash );
			$visitor          = end( $visitor_arr );
			$tracking_options = get_option( 'clickwhale_tracking_options' );

			if ( isset( $tracking_options['tracking_duration'] ) && $tracking_options['tracking_duration'] !== '' ) {
				$tracking_duration = $tracking_options['tracking_duration'];
			} else {
				$settings          = clickwhale()->default_options();
				$tracking_duration = $settings['tracking']['options']['tracking_duration'];
			}

			if ( ! $visitor_arr || $visitor['expired_at'] < $this->date ) {
				$id = $this->add_visitor_to_database( $tracking_duration );
			} else {
				$id = $visitor['id'];
			}

		}

		return intval( $id );
	}

	private function add_visitor_to_database( $duration ) {
		global $wpdb;

		$table_visitors        = $wpdb->prefix . 'clickwhale_visitors';
		$visitor               = [];
		$visitor['hash']       = $this->hash;
		$visitor['browser']    = $this->ua;
		$visitor['os']         = $this->os;
		$visitor['device']     = $this->device;
		$visitor['created_at'] = $this->date;
		$visitor['expired_at'] = gmdate( 'Y-m-d H:i:s',
			strtotime( '+' . $duration . ' days', strtotime( $this->date ) ) );

		$wpdb->insert( $table_visitors, $visitor );

		return $wpdb->insert_id;
	}
}