<?php
namespace clickwhale\includes\front;

use clickwhale\includes\front\tracking\Clickwhale_Click_Track;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Clickwhale_Public_Ajax {

    /**
     * @var Clickwhale_Public_Ajax
     */
    private static Clickwhale_Public_Ajax $instance;

    /**
     * @return Clickwhale_Public_Ajax
     */
    public static function get_instance(): Clickwhale_Public_Ajax {
        if ( empty( self::$instance ) ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * @return void
     * @since 1.1.0
     */
    public function track_custom_link() {
        check_ajax_referer( 'track_custom_link', 'security' );

        if ( ! isset( $_POST['id'] ) ) {
            wp_send_json_error( 'Track Error!' );
        }

        $id = (int) $_POST['id'];

        if ( ! $id ) {
            wp_send_json_error( 'Track Error!' );
        }

        // Track click on link
        new Clickwhale_Click_Track( $id, true );
        wp_send_json_success();
    }
}