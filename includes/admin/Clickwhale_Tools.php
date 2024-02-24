<?php
namespace clickwhale\includes\admin;

use clickwhale\includes\admin\migration\Clickwhale_Migration;
use clickwhale\includes\admin\reset\Clickwhale_Reset;
use clickwhale\includes\admin\import\Clickwhale_Import;
use clickwhale\includes\admin\export\Clickwhale_Export;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Setting tools of the plugin.
 *
 * @link       #
 * @since      1.0.0
 *
 * @package    Clickwhale
 * @subpackage Clickwhale/admin
 */
class Clickwhale_Tools {
	/**
	 * @var Clickwhale_Migration
	 */
	public $migration;

	/**
	 * @var Clickwhale_Reset
	 */
	public $reset;

	/**
	 * @var Clickwhale_Import
	 */
	public $import;

	/**
	 * @var Clickwhale_Export
	 */
	public $export;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {

		$this->load_dependencies();

		$this->migration = new Clickwhale_Migration();
		$this->reset     = new Clickwhale_Reset();
		$this->import    = new Clickwhale_Import();
		$this->export    = new Clickwhale_Export();
	}

	private function load_dependencies() {
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/migration/Clickwhale_Migration.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/migration/Clickwhale_Migration_Abstract.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/migration/Clickwhale_Migration_Notice.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/reset/Clickwhale_Reset.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/import/Clickwhale_Import.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/export/Clickwhale_Export.php';
	}
}