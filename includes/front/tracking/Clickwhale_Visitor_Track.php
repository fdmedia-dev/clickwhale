<?php
namespace Clickwhale\Front\Tracking;

use Clickwhale\Helpers\Helper;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Clickwhale_Visitor_Track {

    /**
     * @var Clickwhale_Parser
     */
    protected Clickwhale_Parser $parser;

    /**
     * @var string
     */
    protected string $ua;

    /**
     * @var string
     */
    protected string $os;

    /**
     * @var string
     */
    protected string $device;

    /**
     * @var string
     */
    protected string $date;

    /**
     * @var string
     */
    protected string $hash;

    /**
     * @var int
     */
    public int $visitor_id;

    public function __construct() {
        $this->visitor_id = 0;
        $this->init_user_agent();
    }

    private function init_user_agent(): void {
        if ( ! isset( $_SERVER['HTTP_USER_AGENT'] ) ) {
            return;
        }

        $user_agent = sanitize_text_field( wp_unslash($_SERVER['HTTP_USER_AGENT']) );

        if ( '' === $user_agent ) {
            return;
        }

        $this->parser = new Clickwhale_Parser( $user_agent );
        $this->ua = $this->parser->ua;
        $this->os = $this->parser->os;
        $this->device = $this->parser->type;
        $this->date = gmdate( 'Y-m-d H:i:s' );
        $this->hash = $this->generate_hash();
        $this->visitor_id = $this->proceed_visitor();
    }

    private function get_user_ip(): string {
        if ( empty( $_SERVER['REMOTE_ADDR'] ) ) {
            return '';
        }

        $ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR']) );

        if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
            return wp_privacy_anonymize_ip( $ip );
        }

        return '';
    }

    private function get_user_salt(): string {
        return $this->ua . $this->os . $this->device;
    }

    private function generate_hash(): string {
        return hash( 'md5', $this->get_user_salt() . $this->get_user_ip() );
    }

    private function get_visitor_by_hash(): array {
        global $wpdb;
        $table = Helper::get_db_table_name( 'visitors' );

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
        return (array) $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE hash=%s", $this->hash ), ARRAY_A );
    }

    /**
     * @return int
     */
    private function proceed_visitor(): int {
        $id = 0;

        if ( ! clickwhale()->user->is_tracking_disabled() && ! $this->parser->bot ) {
            $visitor_arr = $this->get_visitor_by_hash();
            $visitor = end( $visitor_arr );
            $tracking_options = get_option( 'clickwhale_tracking_options' );

            if ( isset( $tracking_options['tracking_duration'] ) && $tracking_options['tracking_duration'] !== '' ) {
                $tracking_duration = $tracking_options['tracking_duration'];
            } else {
                $settings = clickwhale()->default_options();
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

    private function add_visitor_to_database( $duration ): int {
        global $wpdb;
        $table_visitors = $wpdb->prefix . 'clickwhale_visitors';
        $visitor = array(
            'hash'       => $this->hash,
            'browser'    => $this->ua,
            'os'         => $this->os,
            'device'     => $this->device,
            'created_at' => $this->date,
            'expired_at' => gmdate( 'Y-m-d H:i:s', strtotime( '+' . $duration . ' days', strtotime( $this->date ) ) )
        );

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->insert( $table_visitors, $visitor );

        return $wpdb->insert_id;
    }
}
