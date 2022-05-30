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

	private function add_clickwhale_links_database(){
		global $wpdb;
        $table_name = $wpdb->prefix . 'clickwhale_links';
        $charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			title tinytext NOT NULL,
			url varchar(255) DEFAULT '' NOT NULL,
			slug varchar(255) DEFAULT '' NOT NULL,
			redirection smallint(4) NOT NULL,
			description tinytext DEFAULT '' NOT NULL,
			categories tinytext NOT NULL,
			created_at datetime,
			updated_at datetime,
			
			PRIMARY KEY  (id)
		) $charset_collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		if(!maybe_create_table( $table_name,  $sql )){
			dbDelta( $sql );
		}
	}

	private function add_clickwhale_categories_database(){
		global $wpdb;
        $table_name = $wpdb->prefix . 'clickwhale_categories';
        $charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			title tinytext NOT NULL,
			slug varchar(255) DEFAULT '' NOT NULL,
			description tinytext DEFAULT '' NOT NULL,
			
			PRIMARY KEY  (id)
		) $charset_collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		if(!maybe_create_table( $table_name,  $sql )){
			dbDelta( $sql );
		}
	}

	/**
	 * Actions on plugin activation
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		// create a new object inside the static method to access non-static methods inside that class
		(new self)->add_clickwhale_links_database();
		(new self)->add_clickwhale_categories_database();
	}

}
