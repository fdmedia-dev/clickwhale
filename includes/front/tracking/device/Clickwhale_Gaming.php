<?php
namespace clickwhale\includes\front\tracking\device;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Clickwhale_Gaming {

	public function __construct( $ua ) {
		$this->detectNintendo( $ua );
		$this->detectPlaystation( $ua );
		$this->detectXbox( $ua );
		$this->detectSega( $ua );
	}

	/* Nintendo Wii and DS */

	private function detectNintendo( $ua ) {
		/* Switch */

		if ( preg_match( '/Nintendo Switch/u', $ua ) ) {
			$this->type = 'gaming';
			$this->data = array(
				'manufacturer' => 'Nintendo',
				'model'        => 'Switch',
				'subtype'      => 'console',
			);
		}

		/* Wii */

		if ( preg_match( '/Nintendo Wii/u', $ua ) ) {
			$this->type = 'gaming';
			$this->data = array(
				'manufacturer' => 'Nintendo',
				'model'        => 'Wii',
				'subtype'      => 'console',
			);
		}

		/* Wii U */

		if ( preg_match( '/Nintendo Wii ?U/u', $ua ) || preg_match( '/Nintendo WiiU/u', $ua ) ) {
			$this->type = 'gaming';
			$this->data = array(
				'manufacturer' => 'Nintendo',
				'model'        => 'Wii U',
				'subtype'      => 'console',
			);
		}

		/* DS */

		if ( preg_match( '/Nintendo DS/u', $ua ) || preg_match( '/Nitro.*Opera/u', $ua ) ) {
			$this->type = 'gaming';
			$this->data = array(
				'manufacturer' => 'Nintendo',
				'model'        => 'DS',
				'subtype'      => 'portable',
			);
		}

		/* DSi */

		if ( preg_match( '/Nintendo DSi/u', $ua ) ) {
			$this->type = 'gaming';
			$this->data = array(
				'manufacturer' => 'Nintendo',
				'model'        => 'DSi',
				'subtype'      => 'portable',
			);
		}

		/* 3DS */

		if ( preg_match( '/Nintendo 3DS/u', $ua ) ) {
			$this->type = 'gaming';
			$this->data = array(
				'manufacturer' => 'Nintendo',
				'model'        => '3DS',
				'subtype'      => 'portable',
			);
		}

		/* New 3DS */

		if ( preg_match( '/New Nintendo 3DS/u', $ua ) ) {
			$this->type = 'gaming';
			$this->data = array(
				'manufacturer' => 'Nintendo',
				'model'        => 'New 3DS',
				'subtype'      => 'portable',
			);
		}
	}


	/* Sony PlayStation */

	private function detectPlaystation( $ua ) {
		/* PlayStation Portable */

		if ( preg_match( '/PlayStation Portable/u', $ua ) ) {
			$this->type = 'gaming';
			$this->data = array(
				'manufacturer' => 'Sony',
				'model'        => 'PlayStation Portable',
				'subtype'      => 'portable',
			);
		}

		/* PlayStation Vita */

		if ( preg_match( '/PlayStation Vita/ui', $ua ) ) {
			$this->type = 'gaming';
			$this->data = array(
				'manufacturer' => 'Sony',
				'model'        => 'PlayStation Vita',
				'subtype'      => 'portable',
			);

			if ( preg_match( '/VTE\//u', $ua ) ) {
				$this->data->model   = 'PlayStation TV';
				$this->data->subtype = 'console';
			}
		}

		/* PlayStation 2 */

		if ( preg_match( '/PlayStation2/ui', $ua ) || preg_match( '/\(PS2/u', $ua ) ) {
			$this->type = 'gaming';
			$this->data = array(
				'manufacturer' => 'Sony',
				'model'        => 'PlayStation 2',
				'subtype'      => 'console',
			);
		}

		/* PlayStation 3 */

		if ( preg_match( '/PlayStation 3/ui', $ua )
		     || preg_match( '/PLAYSTATION 3/ui', $ua )
		     || preg_match( '/\(PS3/u', $ua ) ) {
			$this->type = 'gaming';
			$this->data = array(
				'manufacturer' => 'Sony',
				'model'        => 'PlayStation 3',
				'subtype'      => 'console',
			);
		}

		/* PlayStation 4 */

		if ( preg_match( '/PlayStation 4/ui', $ua ) || preg_match( '/\(PS4/u', $ua ) ) {
			$this->type = 'gaming';
			$this->data = array(
				'manufacturer' => 'Sony',
				'model'        => 'PlayStation 4',
				'subtype'      => 'console',
			);
		}

		/* PlayStation 5 */

		if ( preg_match( '/PlayStation 5/ui', $ua ) || preg_match( '/\(PS5/u', $ua ) ) {
			$this->type = 'gaming';
			$this->data = array(
				'manufacturer' => 'Sony',
				'model'        => 'PlayStation 5',
				'subtype'      => 'console',
			);
		}
	}


	/* Microsoft Xbox */

	private function detectXbox( $ua ) {
		/* Xbox One */
		if ( preg_match( '/Xbox One\)/u', $ua, $match ) ) {
			$this->type = 'gaming';
			$this->data = array(
				'manufacturer' => 'Microsoft',
				'model'        => 'Xbox One',
				'subtype'      => 'console',
			);

			/* Xbox Series X */
		} elseif ( preg_match( '/Xbox Series X\)/u', $ua, $match ) ) {
			$this->type = 'gaming';
			$this->data = array(
				'manufacturer' => 'Microsoft',
				'model'        => 'Xbox Series X',
				'subtype'      => 'console',
			);

			/* Xbox 360 */
		} elseif ( preg_match( '/Xbox\)$/u', $ua, $match ) ) {
			$this->type = 'gaming';
			$this->data = array(
				'manufacturer' => 'Microsoft',
				'model'        => 'Xbox 360',
				'subtype'      => 'console',
			);
		}
	}


	/* Sega */

	private function detectSega( $ua ) {
		/* Sega Saturn */

		if ( preg_match( '/SEGASATURN/u', $ua, $match ) ) {
			$this->type = 'gaming';
			$this->data = array(
				'manufacturer' => 'Sega',
				'model'        => 'Saturn',
				'subtype'      => 'console',
			);
		}

		/* Sega Dreamcast */

		if ( preg_match( '/Dreamcast/u', $ua, $match ) ) {
			$this->type = 'gaming';
			$this->data = array(
				'manufacturer' => 'Sega',
				'model'        => 'Dreamcast',
				'subtype'      => 'console',
			);
		}
	}
}