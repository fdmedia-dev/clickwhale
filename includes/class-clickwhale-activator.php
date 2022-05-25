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

	private function add_test_data_to_database(){
		global $wpdb;

		$table_name = $wpdb->prefix . 'clickwhale_links';
	
		$wpdb->insert($table_name, array(
			'title' 		=> 'Link to Amazon Store',
			'created_at'	=> '2022-01-03 12:24:24',
			'updated_at'	=> '2022-01-04 13:34:34',
			'url'			=> 'https://amazon.com',
			'slug' 			=> 'amazon',
			'redirection' 	=> 301,
			'description' 	=> 'Link to Amazon Store Homepage',
			'categories' 	=> '',
		));
		$wpdb->insert($table_name, array(
			'title' 		=> 'Link to Ebay Store',
			'created_at'	=> '2022-01-03 12:24:24',
			'updated_at'	=> '2022-01-04 13:34:34',
			'url'			=> 'https://ebay.com',
			'slug' 			=> 'ebay',
			'redirection' 	=> 302,
			'description' 	=> 'Link to Ebay Store Homepage or another text',
			'categories' 	=> '',
		));
		$wpdb->insert($table_name, array(
			'title' 		=> 'Link to Rozetka Marketplace',
			'created_at'	=> '2022-01-03 12:24:24',
			'updated_at'	=> '2022-01-04 13:34:34',
			'url'			=> 'https://rozetka.com.ua',
			'slug' 			=> 'rozetka',
			'redirection' 	=> 302,
			'description' 	=> 'Our biggest and finest marketplace',
			'categories' 	=> '',
		));
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
		(new self)->add_test_data_to_database(); //test data
	}

}
