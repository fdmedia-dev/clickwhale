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

	private function add_clickwhale_database(){
		global $wpdb;
        $table_name = $wpdb->prefix . 'clickwhale_links';
        $charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
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
			$this->add_test_data_to_database(); //test data
		}
	}

	private function add_test_data_to_database(){
		global $wpdb;

		$table_name = $wpdb->prefix . 'clickwhale_links';
	
		$wpdb->insert($table_name, array(
			'link_title' 		=> 'Link to Amazon Store',
			'link_url'			=> 'https://amazon.com',
			'link_slug' 		=> '/link/amazon',
			'link_redirection' 	=> 301,
			'link_description' 	=> 'Link to Amazon Store Homepage',
			'link_categories' 	=> '',
		));
		$wpdb->insert($table_name, array(
			'link_title' 		=> 'Link to Ebay Store',
			'link_url'			=> 'https://ebay.com',
			'link_slug' 		=> '/link/ebay',
			'link_redirection' 	=> 302,
			'link_description' 	=> 'Link to Ebay Store Homepage or another text',
			'link_categories' 	=> '',
		));
		$wpdb->insert($table_name, array(
			'link_title' 		=> 'Link to Rozetka Marketplace',
			'link_url'			=> 'https://rozetka.com.ua',
			'link_slug' 		=> '/link/rozetka',
			'link_redirection' 	=> 302,
			'link_description' 	=> 'Our biggest and finest marketplace',
			'link_categories' 	=> '',
		));
	}

	/**
	 * Actions on plugin activation
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		// create a new object inside the static method to access non-static methods inside that class
		(new self)->add_clickwhale_database();
	}

}
