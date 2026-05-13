<?php
namespace Clickwhale\Front\Tracking;

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
        $this->get_device( $this->ua );
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
        $this->type = $device->device->type ?? $this->type;
        $this->os   = $device->device->os ?? $this->os;
        $this->data = $device->device->data ?? array();
    }
}
