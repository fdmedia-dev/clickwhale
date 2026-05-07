<?php
namespace Clickwhale\Front\Tracking\Device;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Clickwhale_Tablet {

    /**
     * @var string
     */
    public string $type;

    /**
     * @var array
     */
    public array $data;

    public function __construct( $ua ) {
        $this->detectWebTab( $ua );
    }

    /* WeTab */

    private function detectWebTab( $ua ) {
        if ( preg_match( '/WeTab-Browser /ui', $ua ) ) {
            $this->type = 'tablet';
            $this->data = array(
                'manufacturer' => 'WeTab',
                'model'        => 'WeTab'
            );
        }
    }
}
