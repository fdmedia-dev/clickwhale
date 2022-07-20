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
 * @author     fdmedia <https://fdmedia.io>
 */
class Clickwhale_Activator {

	private function add_clickwhale_links_table() {
		global $wpdb;
		$table_name      = $wpdb->prefix . 'clickwhale_links';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$wpdb->prefix}clickwhale_links (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			title tinytext NOT NULL,
			url varchar(255) DEFAULT '' NOT NULL,
			slug varchar(255) DEFAULT '' NOT NULL,
			redirection smallint(4) NOT NULL,
			nofollow smallint(1),
			sponsored smallint(1),
			description tinytext DEFAULT '' NOT NULL,
			categories tinytext,
			created_at datetime,
			updated_at datetime,
			
			PRIMARY KEY  (id)
		) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		if ( ! maybe_create_table( $table_name, $sql ) ) {
			dbDelta( $sql );
		}
	}

	private function add_clickwhale_categories_table() {
		global $wpdb;
		$table_name      = $wpdb->prefix . 'clickwhale_categories';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$wpdb->prefix}clickwhale_categories (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			title tinytext NOT NULL,
			slug varchar(255) DEFAULT '' NOT NULL,
			description tinytext DEFAULT '' NOT NULL,
			
			PRIMARY KEY  (id)
		) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		if ( ! maybe_create_table( $table_name, $sql ) ) {
			dbDelta( $sql );
		}
	}

	private function add_clickwhale_clicks_table() {
		global $wpdb;
		$table_name      = $wpdb->prefix . 'clickwhale_clicks';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$wpdb->prefix}clickwhale_clicks (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			link_id mediumint(9) NOT NULL,
			visitor_hash tinytext NOT NULL,
			browser tinytext NOT NULL,
			os tinytext NOT NULL,
			device tinytext NOT NULL,
			referer varchar(255) DEFAULT '' NOT NULL,
			created_at datetime,
			
			PRIMARY KEY  (id)
		) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		if ( ! maybe_create_table( $table_name, $sql ) ) {
			dbDelta( $sql );
		}
	}

	private function add_clickwhale_links_meta_table() {
		global $wpdb;
		$table_name      = $wpdb->prefix . 'clickwhale_links_meta';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$wpdb->prefix}clickwhale_links_meta (
			id int(11) NOT NULL auto_increment,
			meta_key varchar(255) default NULL,
			meta_value longtext default NULL,
			link_id int(11) NOT NULL,
			
			PRIMARY KEY  (id)
		) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		if ( ! maybe_create_table( $table_name, $sql ) ) {
			dbDelta( $sql );
		}
	}

	private function add_clickwhale_linkpages_table() {
		global $wpdb;
		$table_name      = $wpdb->prefix . 'clickwhale_linkpages';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$wpdb->prefix}clickwhale_linkpages (
			id int(11) NOT NULL AUTO_INCREMENT,
			title tinytext NOT NULL,
			description tinytext DEFAULT '' NOT NULL,
			slug tinytext NOT NULL,
			views int(11) NOT NULL,
			links longtext default NULL,
			created_at datetime,
			
			PRIMARY KEY  (id)
		) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		if ( ! maybe_create_table( $table_name, $sql ) ) {
			dbDelta( $sql );
		}
	}

	private function add_clickwhale_linkpages_meta_table() {
		global $wpdb;
		$table_name      = $wpdb->prefix . 'clickwhale_linkpages_meta';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$wpdb->prefix}clickwhale_linkpages_meta (
			id int(11) NOT NULL auto_increment,
			meta_key varchar(255) default NULL,
			meta_value longtext default NULL,
			linkpage_id int(11) NOT NULL,
			
			PRIMARY KEY  (id)
		) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		if ( ! maybe_create_table( $table_name, $sql ) ) {
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
		( new self )->add_clickwhale_links_table();
		( new self )->add_clickwhale_categories_table();
		( new self )->add_clickwhale_clicks_table();
		( new self )->add_clickwhale_links_meta_table();
		( new self )->add_clickwhale_linkpages_table();
		( new self )->add_clickwhale_linkpages_meta_table();
	}

}
