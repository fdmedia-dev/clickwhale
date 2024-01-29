<?php
namespace clickwhale_pro\includes;

/**
 * Fired during plugin deactivation
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @link       https://fdmedia.io/
 * @since      1.0.0
 *
 * @package    Clickwhale_Pro
 * @subpackage Clickwhale_Pro/includes
 */
class Clickwhale_Pro_Deactivator {

	/**
	 * @since    1.0.0
	 */
	public static function deactivate() {
		delete_option( 'clickwhale_pro_version' );
	}
}
