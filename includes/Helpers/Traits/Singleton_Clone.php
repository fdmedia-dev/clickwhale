<?php
namespace Clickwhale\Helpers\Traits;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

trait Singleton_Clone {

    /**
     * Throw error on object clone.
     *
     * The whole idea of the singleton design pattern is that there is a single
     * object therefore, we don't want the object to be cloned.
     *
     * @return void
     * @since 1.0.3
     * @access protected
     */
    public function __clone() {
        // Cloning instances of the class is forbidden.
        _doing_it_wrong( __FUNCTION__, esc_html__( 'Cheatin&#8217; huh?', 'clickwhale' ), esc_html( CLICKWHALE_VERSION ) );
    }
}
