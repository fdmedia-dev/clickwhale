<?php
namespace clickwhale\includes\debuggers;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Debugger {

    /**
     * Debug
     *
     * @param $args
     * @param bool $title
     */
    public static function debug( $args, $title = false ) {

        if ( $title ) {
            echo '<h3>' . $title . '</h3>';
        }

        if ( $args ) {
            echo '<pre>';
            print_r($args);
            echo '</pre>';
        }
    }

    /**
     * Debug logging
     *
     * @param $message
     */
    public static function debug_log( $message ) {

        if ( WP_DEBUG === true ) {
            if ( is_array( $message ) || is_object( $message ) ) {
                error_log( print_r( $message, true ) );
            } else {
                error_log( $message );
            }
        }
    }
}