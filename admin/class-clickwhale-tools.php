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
	 * @var      string $plugin_name The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $version The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param string $plugin_name The name of this plugin.
	 * @param string $version The version of this plugin.
	 *
	 * @since    1.0.0
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;

		$this->load_dependencies();
		$this->tools_migration();
		$this->tools_reset_db();

	}

	private function load_dependencies() {
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/migration/class-clickwhale-tools-migration.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/reset/class-clickwhale-tools-reset-db.php';
	}

	public function tools_migration() {
		$migration = new Clickwhale_Tools_Migration();
	}

	public function tools_reset_db() {
		$reset = new Clickwhale_Tools_Reset_DB( $this->plugin_name );
		$reset->init();
	}

}