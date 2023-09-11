<?php

namespace clickwhale\includes\admin;

use clickwhale\includes\admin\export\Clickwhale_Export;
use clickwhale\includes\admin\import\Clickwhale_Import;
use clickwhale\includes\admin\reset\Clickwhale_Reset;
use clickwhale\includes\admin\migration\{Clickwhale_Migration, Clickwhale_Tools_Migration};

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
class Clickwhale_Tools {
	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {

		$this->load_dependencies();

		$this->migration();
		$this->tools_migration();
		$this->tools_reset_db();
		$this->tools_import();
		$this->tools_export();

	}

	private function load_dependencies() {
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/migration/Clickwhale_Tools_Migration.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/reset/Clickwhale_Reset.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/import/Clickwhale_Import.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/export/Clickwhale_Export.php';
	}

	public function migration() {
		$mirgation = new Clickwhale_Migration();
		$mirgation->init();
	}

	public function tools_migration() {
		new Clickwhale_Tools_Migration();
	}

	public function tools_reset_db() {
		$reset = new Clickwhale_Reset();
		$reset->init( CLICKWHALE_NAME );
	}

	public function tools_import() {
		new Clickwhale_Import();
	}

	public function tools_export() {
		new Clickwhale_Export();
	}

}