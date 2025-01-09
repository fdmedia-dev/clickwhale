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
    public string $ua;

    /**
     * @var string
     */
    public string $type;

    /**
     * @var string
     */
    public string $os;

    /**
     * @var bool
     */
    public bool $bot = false;

    /**
     * @var array
     */
    public array $data;

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

        $os = new Clickwhale_OS( $ua );
        $name       = $os->name ?? 'Unknown OS';
        $version    = isset( $os->version ) ? ' ' . $os->version : '';
        $this->os   = $name . $version;
        $this->type = $os->type ?? 'Unknown Device';

        $device = new Clickwhale_Device( $ua );
        //if ( $device->device ) {
            $this->type = $device->device->type ?? $this->type;
            $this->os   = $device->device->os ?? $this->os;
            $this->data = $device->device->data ?? array();
        //}
	}

}