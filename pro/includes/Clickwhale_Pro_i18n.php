<?php
namespace clickwhale_pro\includes;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://fdmedia.io/
 * @since      1.0.0
 *
 * @package    Clickwhale_Pro
 * @subpackage Clickwhale_Pro/includes
 * @author     fdmedia <dev@krapan.net>
 */
class Clickwhale_Pro_i18n {

	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
            CLICKWHALE_PRO_NAME,
			false,
            CLICKWHALE_PRO_SLUG . '/languages/'
		);
	}
}
