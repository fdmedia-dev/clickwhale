<?php
namespace Clickwhale\Admin;

use Clickwhale\Admin\Migration\Clickwhale_Migration;
use Clickwhale\Admin\Reset\Clickwhale_Reset;
use Clickwhale\Admin\Import\Clickwhale_Import;
use Clickwhale\Admin\Export\Clickwhale_Export;

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
    public Clickwhale_Migration $migration;

    /**
     * @var Clickwhale_Reset
     */
    public Clickwhale_Reset $reset;

    /**
     * @var Clickwhale_Import
     */
    public Clickwhale_Import $import;

    /**
     * @var Clickwhale_Export
     */
    public Clickwhale_Export $export;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     */
    public function __construct() {

        $this->migration = new Clickwhale_Migration();
        $this->reset     = new Clickwhale_Reset();
        $this->import    = new Clickwhale_Import();
        $this->export    = new Clickwhale_Export();
    }

}
