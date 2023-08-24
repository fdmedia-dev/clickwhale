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
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {

		$this->load_dependencies();
		$this->tools_migration();
		$this->tools_reset_db();
		$this->tools_import();

	}

	private function load_dependencies() {
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/migration/class-clickwhale-tools-migration.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/reset/class-clickwhale-tools-reset-db.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/controllers/tools/class-clickwhale-tools-import.php';
	}

	public function tools_migration() {
		new Clickwhale_Tools_Migration();
	}

	public function tools_reset_db() {
		$reset = new ClickwhaleToolsResetDB();
		$reset->init( CLICKWHALE_NAME );
	}

	public function tools_import() {
		new Clickwhale_Tools_Import();
	}

}