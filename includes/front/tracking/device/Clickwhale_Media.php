<?php
namespace clickwhale\includes\front\tracking\device;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Clickwhale_Media {

	public function __construct( $ua ) {
		$this->detectArchos( $ua );
		$this->detectZune( $ua );
		$this->detectWalkman( $ua );
	}

	/* Archos Generation 4, 5 and 6 */

	private function detectArchos( $ua ) {
		/* Generation 4 */

		if ( preg_match( '/Archos A([67]04)WIFI\//u', $ua, $match ) ) {
			$this->type = 'media';
			$this->data = array(
				'manufacturer' => 'Archos',
				'model'        => $match[1] . ' WiFi',
			);
		}

		/* Generation 5 */

		if ( preg_match( '/ARCHOS; GOGI; a([67]05f?);/u', $ua, $match ) ) {
			$this->type = 'media';
			$this->data = array(
				'manufacturer' => 'Archos',
				'model'        => $match[1] . ' WiFi',
			);
		}

		/* Generation 6 without Android */

		if ( preg_match( '/ARCHOS; GOGI; G6-?(S|H|L|3GP);/u', $ua, $match ) ) {
			$this->type = 'media';
			$this->data = array(
				'manufacturer' => 'Archos',
			);

			switch ( $match[1] ) {
				case '3GP':
					$this->data->model = '5 3G+';
					break;
				case 'S':
				case 'H':
					$this->data->model = '5';
					break;
				case 'L':
					$this->data->model = '7';
					break;
			}
		}

		/* Generation 6 with Android */

		if ( preg_match( '/ARCHOS; GOGI; A5[SH]; Version ([0-9]\.[0-9])/u', $ua, $match ) ) {
			$this->type = 'media';
			$this->data = array(
				'manufacturer' => 'Archos',
				'model'        => '5',
			);
		}
	}


	/* Microsoft Zune */

	private function detectZune( $ua ) {
		if ( preg_match( '/Microsoft ZuneHD/u', $ua ) ) {
			$this->type = 'media';
			$this->data = array(
				'manufacturer' => 'Microsoft',
				'model'        => 'Zune HD',
			);
		}
	}


	/* Sony Walkman */

	private function detectWalkman( $ua ) {
		if ( preg_match( '/Walkman/u', $ua, $match ) || preg_match( '/WALKMAN/u', $ua, $match ) ) {
			$this->type = 'media';
			$this->data = array(
				'manufacturer' => 'Sony',
			);
		}
	}
}