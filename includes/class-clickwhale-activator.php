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
			author mediumint(9) DEFAULT 0,
			created_at datetime,
			updated_at datetime,
			
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
			description mediumtext DEFAULT '' NOT NULL,
			slug tinytext NOT NULL,
			logo int(11) NOT NULL,
			links longtext default NULL,
			styles longtext default NULL,
			social longtext default NULL,
			author mediumint(9) DEFAULT 0,
			created_at datetime,
			
			PRIMARY KEY  (id)
		) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		if ( ! maybe_create_table( $table_name, $sql ) ) {
			dbDelta( $sql );
		}
	}

	private function add_clickwhale_meta_table() {
		global $wpdb;
		$table_name      = $wpdb->prefix . 'clickwhale_meta';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$wpdb->prefix}clickwhale_meta (
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

	private function add_clickwhale_visitors_table() {
		global $wpdb;
		$table_name      = $wpdb->prefix . 'clickwhale_visitors';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$wpdb->prefix}clickwhale_visitors (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			hash tinytext NOT NULL,
			browser tinytext NOT NULL,
			os tinytext NOT NULL,
			device tinytext NOT NULL,
			created_at datetime,
			expired_at datetime,
			
			PRIMARY KEY  (id)
		) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		if ( ! maybe_create_table( $table_name, $sql ) ) {
			dbDelta( $sql );
		}
	}

	private function add_clickwhale_track_table() {
		global $wpdb;
		$table_name      = $wpdb->prefix . 'clickwhale_track';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$wpdb->prefix}clickwhale_track (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			event_type tinytext NOT NULL,
			link_id mediumint(9) DEFAULT 0,
			linkpage_id mediumint(9) DEFAULT 0,
			visitor_id mediumint(9) NOT NULL,
			referer varchar(255) DEFAULT '' NOT NULL,
			created_at datetime,
			
			PRIMARY KEY  (id)
		) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		if ( ! maybe_create_table( $table_name, $sql ) ) {
			dbDelta( $sql );
		}
	}

	/**
	 * @return void
	 * @since 1.2.0
	 */
	private function add_clickwhale_tracking_codes_table() {
		global $wpdb;
		$table_name      = $wpdb->prefix . 'clickwhale_tracking_codes';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$wpdb->prefix}clickwhale_tracking_codes (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			title tinytext NOT NULL,
			description mediumtext NOT NULL,
			type varchar(255) DEFAULT '' NOT NULL,
			code longtext NOT NULL,
			position longtext NOT NULL,
			is_active tinyint(1) DEFAULT 0 NOT NULL,
			author mediumint(9) DEFAULT 0 NOT NULL,
			created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL ,
			updated_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL ,
			
			PRIMARY KEY  (id)
		) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		if ( ! maybe_create_table( $table_name, $sql ) ) {
			dbDelta( $sql );
		}
	}

	/**
	 * @since    1.1.1
	 */
	private function modify_columns() {
		global $wpdb;

		if ( version_compare( CLICKWHALE_VERSION, '1.0.0', '>' ) ) {
			maybe_add_column(
				$wpdb->prefix . "clickwhale_track",
				"custom_link_id",
				"ALTER TABLE {$wpdb->prefix}clickwhale_track ADD custom_link_id tinytext DEFAULT '' NOT NULL AFTER link_id"
			);
		}
	}


	/**
	 * Actions on plugin activation
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		// create a new object inside the static method to access non-static methods inside that class
		( new self )->add_clickwhale_categories_table();
		( new self )->add_clickwhale_links_table();
		( new self )->add_clickwhale_linkpages_table();
		( new self )->add_clickwhale_meta_table();
		( new self )->add_clickwhale_visitors_table();
		( new self )->add_clickwhale_track_table();
		( new self )->add_clickwhale_tracking_codes_table();
		( new self )->modify_columns();
	}

}
