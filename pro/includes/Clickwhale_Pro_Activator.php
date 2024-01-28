<?php

namespace clickwhale_pro\includes;

/**
 * Fired during plugin activation
 *
 * @link       https://fdmedia.io/
 * @since      1.0.0
 *
 * @package    Clickwhale_Pro
 * @subpackage Clickwhale_Pro/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Clickwhale_Pro
 * @subpackage Clickwhale_Pro/includes
 */
class Clickwhale_Pro_Activator {

	private function modify_columns() {}

	/**
	 * @since    1.0.0
	 */
	public static function activate() {

        // modify columns after activation/update
        ( new self )->modify_columns();

        // Add/Update Clickwhale Pro version
        if ( get_option( 'clickwhale_pro_version' ) ) {
            update_option( 'clickwhale_pro_version', CLICKWHALE_PRO_VERSION );
        } else {
            add_option( 'clickwhale_pro_version', CLICKWHALE_PRO_VERSION );
        }
	}
}
