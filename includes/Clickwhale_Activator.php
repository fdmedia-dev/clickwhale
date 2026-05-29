<?php

namespace Clickwhale;

use Clickwhale\Helpers\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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

	private static function add_clickwhale_categories_table() {
		global $wpdb;
		$table_name      = Helper::get_db_table_name( 'categories' );
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
				id mediumint(9) NOT NULL AUTO_INCREMENT,
				title tinytext NOT NULL,
				slug varchar(255) DEFAULT '' NOT NULL,
				description tinytext DEFAULT '' NOT NULL,
				PRIMARY KEY  (id)
				) $charset_collate;";

		if ( ! maybe_create_table( $table_name, $sql ) ) {
			dbDelta( $sql );
		}
	}

	private static function add_clickwhale_links_table() {
		global $wpdb;
		$table_name      = Helper::get_db_table_name( 'links' );
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
				id mediumint(9) NOT NULL AUTO_INCREMENT,
				title tinytext NOT NULL,
				url varchar(1000) DEFAULT '' NOT NULL,
				slug varchar(255) DEFAULT '' NOT NULL,
				redirection smallint(4) NOT NULL,
				link_target varchar(10) DEFAULT '' NOT NULL,
				nofollow smallint(1),
				sponsored smallint(1),
				description tinytext DEFAULT '' NOT NULL,
				categories tinytext,
				created_by_api TINYINT(1),
				author mediumint(9) DEFAULT 0,
				created_at datetime,
				updated_at datetime,
				PRIMARY KEY  (id)
				) $charset_collate;";

		if ( ! maybe_create_table( $table_name, $sql ) ) {
			dbDelta( $sql );
		}
	}

	private static function add_clickwhale_linkpages_table() {
		global $wpdb;
		$table_name      = Helper::get_db_table_name( 'linkpages' );
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
				id int(11) NOT NULL AUTO_INCREMENT,
				title tinytext NOT NULL,
				description mediumtext DEFAULT '' NOT NULL,
				slug tinytext NOT NULL,
				logo int(11) NOT NULL,
				favicon INT(11) NOT NULL,
				links longtext default NULL,
				styles longtext default NULL,
				social longtext default NULL,
				author mediumint(9) DEFAULT 0,
				created_at datetime,
				PRIMARY KEY  (id)
				) $charset_collate;";

		if ( ! maybe_create_table( $table_name, $sql ) ) {
			dbDelta( $sql );
		}
	}

	private static function add_clickwhale_meta_table() {
		global $wpdb;
		$table_name      = Helper::get_db_table_name( 'meta' );
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
				id int(11) NOT NULL auto_increment,
				meta_key varchar(255) default NULL,
				meta_value longtext default NULL,
				link_id int(11) NOT NULL,
				linkpage_id int(11) NOT NULL,
				PRIMARY KEY  (id)
				) $charset_collate;";

		if ( ! maybe_create_table( $table_name, $sql ) ) {
			dbDelta( $sql );
		}
	}

	private static function add_clickwhale_visitors_table() {
		global $wpdb;
		$table_name      = Helper::get_db_table_name( 'visitors' );
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
				id mediumint(9) NOT NULL AUTO_INCREMENT,
				hash tinytext NOT NULL,
				browser tinytext NOT NULL,
				os tinytext NOT NULL,
				device tinytext NOT NULL,
				created_at datetime,
				expired_at datetime,
				PRIMARY KEY  (id)
				) $charset_collate;";

		if ( ! maybe_create_table( $table_name, $sql ) ) {
			dbDelta( $sql );
		}
	}

	private static function add_clickwhale_track_table() {
		global $wpdb;
		$table_name      = Helper::get_db_table_name( 'track' );
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
				id mediumint(9) NOT NULL AUTO_INCREMENT,
				event_type tinytext NOT NULL,
				link_id mediumint(9) DEFAULT 0,
				custom_link_id tinytext DEFAULT '' NOT NULL,
				linkpage_id mediumint(9) DEFAULT 0,
				visitor_id mediumint(9) NOT NULL,
				referer varchar(255) DEFAULT '' NOT NULL,
				created_at datetime,
				PRIMARY KEY  (id)
				) $charset_collate;";

		if ( ! maybe_create_table( $table_name, $sql ) ) {
			dbDelta( $sql );
		}
	}

	/**
	 * @return void
	 * @since 1.2.0
	 */
	private static function add_clickwhale_tracking_codes_table() {
		global $wpdb;
		$table_name      = Helper::get_db_table_name( 'tracking_codes' );
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
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

		if ( ! maybe_create_table( $table_name, $sql ) ) {
			dbDelta( $sql );
		}
	}

	/**
	 * Add the smart displays table if it doesn't exist.
	 *
	 * @return void
	 * @since 2.7.0
	 */
	private static function add_clickwhale_smart_displays_table(): void {
		if ( version_compare( CLICKWHALE_VERSION, '2.7.0', '<' ) ) {
			return;
		}

		global $wpdb;
		$table_name      = Helper::get_db_table_name( 'smart_displays' );
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
					id INT(9) NOT NULL AUTO_INCREMENT,
					title VARCHAR(255) NOT NULL DEFAULT '',
					image INT(11) NOT NULL DEFAULT 0,
					link_id INT(11) DEFAULT 0,
					description MEDIUMTEXT DEFAULT NULL,
					options longtext default NULL,
					created_at DATETIME,
					PRIMARY KEY (id)
				) $charset_collate;";

		if ( ! maybe_create_table( $table_name, $sql ) ) {
			dbDelta( $sql );
		}
	}

	/**
	 * Modify columns if they don't exist.
	 *
	 * @since    1.1.1
	 */
	private static function modify_columns() {
		global $wpdb;
		$links_table_name     = Helper::get_db_table_name( 'links' );
		$linkpages_table_name = Helper::get_db_table_name( 'linkpages' );
		$track_table_name     = Helper::get_db_table_name( 'track' );
		$meta_table_name      = Helper::get_db_table_name( 'meta' );

		/* @since 1.1.1 */
		if ( version_compare( CLICKWHALE_VERSION, '1.0.0', '>' ) ) {
			maybe_add_column(
				$track_table_name,
				"custom_link_id",
				"ALTER TABLE $track_table_name ADD custom_link_id tinytext DEFAULT '' NOT NULL AFTER link_id"
			);
		}

		/* @since 1.3.2 */
		if ( version_compare( CLICKWHALE_VERSION, '1.3.1', '>=' ) ) {
			maybe_add_column(
				$meta_table_name,
				"linkpage_id",
				"ALTER TABLE $meta_table_name ADD linkpage_id int(11) NOT NULL AFTER link_id"
			);
		}

		/* @since 2.2.0 */
		if ( version_compare( CLICKWHALE_VERSION, '2.1.3', '>' ) ) {
			$query = $wpdb->query( "ALTER TABLE $links_table_name MODIFY COLUMN url varchar(1000) DEFAULT '' NOT NULL" );
		}

		/* @since 2.4.5 */
		if ( version_compare( CLICKWHALE_VERSION, '2.4.5', '>=' ) ) {
			maybe_add_column(
				$links_table_name,
				"link_target",
				"ALTER TABLE $links_table_name ADD link_target varchar(10) DEFAULT '' NOT NULL AFTER redirection"
			);
		}

		/* @since 2.5.0 */
		if ( version_compare( CLICKWHALE_VERSION, '2.5.0', '>=' ) ) {
			maybe_add_column(
				$links_table_name,
				"created_by_api",
				"ALTER TABLE $links_table_name ADD created_by_api TINYINT(1) AFTER categories"
			);
		}

		/* @since 2.5.1 */
		if ( version_compare( CLICKWHALE_VERSION, '2.5.1', '>=' ) ) {
			maybe_add_column(
				$linkpages_table_name,
				"favicon",
				"ALTER TABLE $linkpages_table_name ADD favicon INT(11) NOT NULL AFTER logo"
			);
		}
	}

	/**
	 * Actions on plugin activation
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		$maybe_update_version = clickwhale_maybe_add_or_update_version();

		if ( $maybe_update_version ) {
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

			self::add_clickwhale_categories_table();
			self::add_clickwhale_linkpages_table();
			self::add_clickwhale_links_table();
			self::add_clickwhale_meta_table();
			self::add_clickwhale_track_table();
			self::add_clickwhale_tracking_codes_table();
			self::add_clickwhale_visitors_table();
			self::add_clickwhale_smart_displays_table();
			self::modify_columns();
		}
	}
}
