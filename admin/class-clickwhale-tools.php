<?php

/**
 * The settings of the plugin.
 *
 * @link       #
 * @since      1.0.0
 *
 * @package    Clickwhale
 * @subpackage Clickwhale/admin
 */

/**
 * Class WordPress_Plugin_Template_Settings
 *
 */
class Clickwhale_Admin_Tools {
	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

    	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	public function available_migrations() {

		$defaults = array(
			'betterlinks',
			'thirstyaffiliates',
		);

		return $defaults;

	}

	public function initialize_migrate_options() {
		
		$migrations = $this->available_migrations();

		foreach($migrations as $k=>$v){
			if( false == get_option( 'clickwhale_hide_' . $v . '_notice_migrate' ) ) {
				add_option( 'clickwhale_hide_' . $v . '_notice_migrate', '' );
			}
		}
		

	}

	public function initialize_deactive_options() {

		$migrations = $this->available_migrations();

		foreach($migrations as $k=>$v){
			if( false == get_option( 'clickwhale_hide_' . $v . '_notice_deactive' ) ) {
				add_option( 'clickwhale_hide_' . $v . '_notice_deactive', '' );
			}
		}

	}


}