<?php
namespace clickwhale\includes\helpers\traits;

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
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', CLICKWHALE_NAME ), CLICKWHALE_VERSION );
	}
}