<?php
namespace clickwhale\includes\front\tracking\device;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Clickwhale_Appliance {
	public function __construct( $ua ) {
		$this->detectIOpener( $ua );
		$this->detectWebLight( $ua );
	}

	/* Netpliance i-Opener */

	private function detectIOpener( $ua ) {
		if ( preg_match( '/I-Opener [0-9.]+; Netpliance/u', $ua ) ) {
			$this->type = 'desktop';
			$this->os   = 'Unknown';
			$this->data = array(
				'manufacturer' => 'Netpliance',
				'model'        => 'i-Opener'
			);
		}
	}

	/* KOMATSU WebLight */

	private function detectWebLight( $ua ) {
		if ( preg_match( '/KOMATSU.*WL\//u', $ua ) ) {
			$this->type = 'desktop';
			$this->os   = 'Unknown';
			$this->data = array(
				'manufacturer' => 'KOMATSU',
				'model'        => 'WebLight',
			);
		}
	}
}
