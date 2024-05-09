<?php
namespace clickwhale\includes\front\tracking;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Clickwhale_Parser {
	/**
	 * User Agent String
	 * @var string
	 */
	public $ua;

	/** @var string */
	public $type = '';

	/** @var string */
	public $os = '';

	/** @var string */
	public $bot = false;

	/** @var array */
	public $data = [];

	public function __construct( $ua ) {
		$this->ua = $ua;

		$this->load_dependencies();

		$this->get_device( $this->ua );

	}

	private function load_dependencies() {

		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'tracking/Clickwhale_Bot.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'tracking/Clickwhale_Device.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'tracking/Clickwhale_OS.php';

	}

	public function get_device( $ua ) {
		$bot       = new Clickwhale_Bot( $ua );
		$this->bot = $bot->is_bot;

		$device = new Clickwhale_Device( $ua );
		if ( $device ) {
			$this->type = isset( $device->device->type ) ? $device->device->type : 'Unknown Device';
			$this->os   = isset( $device->device->os ) ? $device->device->os : 'Unknown Os';
			$this->data = isset( $device->device->data ) ? $device->device->data : null;
		}

		$os = new Clickwhale_OS( $ua );
		if ( $os ) {
			$name       = isset( $os->name ) ? $os->name : 'Unknown OS';
			$version    = isset( $os->version ) ? ' ' . $os->version : '';
			$this->os   = $name . $version;
			$this->type = isset( $os->type ) ? $os->type : $this->type;
		}
	}

}