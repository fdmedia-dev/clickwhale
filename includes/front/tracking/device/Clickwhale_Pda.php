<?php
namespace clickwhale\includes\front\tracking\device;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Clickwhale_Pda {

    /**
     * @var string
     */
    public string $type;

	public function __construct() {
		$this->detectPDA();
	}

	private function detectPDA() {
		$this->type = 'pda';
	}
}