<?php
namespace clickwhale\includes\helpers\traits;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

trait Singleton_Wakeup {
	/**
	 * Disable un-serializing of the class.
	 *
	 * @return void
	 * @since 1.0.3
	 * @access protected
	 */
	public function __wakeup() {
		// Unserializing instances of the class is forbidden.
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', CLICKWHALE_NAME ), CLICKWHALE_VERSION );
	}
}