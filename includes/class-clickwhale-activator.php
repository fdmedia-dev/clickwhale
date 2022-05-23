<?php

/**
 * Fired during plugin activation
 *
 * @link       #
 * @since      1.0.0
 *
 * @package    Clickwhale
 * @subpackage Clickwhale/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Clickwhale
 * @subpackage Clickwhale/includes
 * @author     Rivo <https://rivo.agency>
 */
class Clickwhale_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {

		global $wpdb;
        $table_name = $wpdb->prefix . 'clickwhale_links';
        $charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			link_time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			link_title tinytext NOT NULL,
			link_url varchar(255) DEFAULT '' NOT NULL,
			link_slug varchar(255) DEFAULT '' NOT NULL,
			link_redirection smallint(4) NOT NULL,
			link_description tinytext DEFAULT '' NOT NULL,
			link_categories tinytext NOT NULL,

			PRIMARY KEY  (id)
		) $charset_collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		if(!maybe_create_table( $table_name,  $sql )){
			dbDelta( $sql );
		}

	}

}
