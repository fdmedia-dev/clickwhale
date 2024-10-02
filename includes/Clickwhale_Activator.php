<?php
namespace clickwhale\includes;

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
		$table_name      = $wpdb->prefix . 'clickwhale_categories';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$wpdb->prefix}clickwhale_categories (
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

		if ( ! maybe_create_table( $table_name, $sql ) ) {
			dbDelta( $sql );
		}
	}

	private static function add_clickwhale_linkpages_table() {
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

		if ( ! maybe_create_table( $table_name, $sql ) ) {
			dbDelta( $sql );
		}
	}

	private static function add_clickwhale_meta_table() {
		global $wpdb;
		$table_name      = $wpdb->prefix . 'clickwhale_meta';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$wpdb->prefix}clickwhale_meta (
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

		if ( ! maybe_create_table( $table_name, $sql ) ) {
			dbDelta( $sql );
		}
	}

	private static function add_clickwhale_track_table() {
		global $wpdb;
		$table_name      = $wpdb->prefix . 'clickwhale_track';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$wpdb->prefix}clickwhale_track (
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

		if ( ! maybe_create_table( $table_name, $sql ) ) {
			dbDelta( $sql );
		}
	}

	/**
	 * @since    1.1.1
	 */
	private static function modify_columns() {
        global $wpdb;

		/* @since 1.1.1 */
		if ( version_compare( CLICKWHALE_VERSION, '1.0.0', '>' ) ) {
			maybe_add_column(
				$wpdb->prefix . "clickwhale_track",
				"custom_link_id",
				"ALTER TABLE {$wpdb->prefix}clickwhale_track ADD custom_link_id tinytext DEFAULT '' NOT NULL AFTER link_id"
			);
		}

		/* @since 1.3.2 */
		if ( version_compare( CLICKWHALE_VERSION, '1.3.1', '>=' ) ) {
			maybe_add_column(
				$wpdb->prefix . "clickwhale_meta",
				"linkpage_id",
				"ALTER TABLE {$wpdb->prefix}clickwhale_meta ADD linkpage_id int(11) NOT NULL AFTER link_id"
			);
		}

        /* @since 2.2.0 */
        clickwhale_maybe_add_or_update_url_column();
    }

	/**
     * Data migration
     *
	 * @return void
	 * @since 1.3.0
	 */
	private static function migrate_130_data(): void {
		global $wpdb;

        if ( version_compare( CLICKWHALE_VERSION, '1.3.0', '>=' ) ) {
            return;
        }

        $results = $wpdb->get_results( "SELECT id, links FROM {$wpdb->prefix}clickwhale_linkpages" );

		if ( ! $results ) {
			return;
		}

		foreach ( $results as $result ) {
			$links = maybe_unserialize( $result->links );

			if ( ! $links ) {
				return;
			}

			foreach ( $links as $k => $v ) {
				$links[ $k ]['is_active'] = '1';
				if ( $links[ $k ]['type'] === 'custom_link' ) {
					$links[ $k ]['type'] = 'cw_custom_link';
				}
			}

			$links = maybe_serialize( $links );

			$wpdb->update(
				$wpdb->prefix . 'clickwhale_linkpages',
				array( 'links' => $links ),
				array( 'id' => $result->id )
			);
		}
	}

	private static function drop_tables() {
		global $wpdb;
		if ( version_compare( CLICKWHALE_VERSION, '1.2.0', '>' ) ) {
			$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}clickwhale_links_meta" );
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
            self::modify_columns();
            self::drop_tables();
            self::migrate_130_data();
        }
    }
}
