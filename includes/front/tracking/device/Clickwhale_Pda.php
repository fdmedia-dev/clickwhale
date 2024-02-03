<?php
namespace clickwhale\includes\front\tracking\device;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Clickwhale_Pda {
	public function __construct( $ua ) {
		$this->detectPDA( $ua );
	}

	private function detectPDA( $u ) {
		$this->type = 'pda';
	}
}