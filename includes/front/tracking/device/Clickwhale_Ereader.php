<?php
namespace clickwhale\includes\front\tracking\device;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Clickwhale_Ereader {

	public function __construct( $ua ) {
		$this->detectKindle( $ua );
		$this->detectNook( $ua );
		$this->detectBookeen( $ua );
		$this->detectKobo( $ua );
		$this->detectSonyreader( $ua );
		$this->detectPocketbook( $ua );
	}

	/* Amazon Kindle */

	private function detectKindle( $ua ) {
		if ( preg_match( '/Kindle/u', $ua ) && ! preg_match( '/Fire/u', $ua ) ) {
			$this->type = 'ereader';
			$this->data = array(
				'manufacturer' => 'Amazon',
				'series'       => 'Kindle',
			);

			if ( preg_match( '/Kindle SkipStone/u', $ua ) ) {
				$this->data->model = 'Kindle Touch or later';
			} elseif ( preg_match( '/Kindle\/3.0\+/u', $ua ) ) {
				$this->data->model = 'Kindle 3 or later';
			} elseif ( preg_match( '/Kindle\/3.0/u', $ua ) ) {
				$this->data->model = 'Kindle 3';
			} elseif ( preg_match( '/Kindle\/2.5/u', $ua ) ) {
				$this->data->model = 'Kindle 2';
			} elseif ( preg_match( '/Kindle\/2.0/u', $ua ) ) {
				$this->data->model = 'Kindle 2';
			} elseif ( preg_match( '/Kindle\/1.0/u', $ua ) ) {
				$this->data->model = 'Kindle 1';
			}
		}
	}


	/* Barnes & Noble Nook */

	private function detectNook( $ua ) {
		if ( preg_match( '/Barnes & Noble/u', $ua ) ) {
			$this->type = 'ereader';
			$this->data = array(
				'manufacturer' => 'Barnes & Noble',
				'series'       => 'NOOK',
			);
		}
	}


	/* Bookeen */

	private function detectBookeen( $ua ) {
		if ( preg_match( '/bookeen\/cybook/u', $ua ) ) {
			$this->type = 'ereader';
			$this->data = array(
				'manufacturer' => 'Bookeen',
				'series'       => 'Cybook',
			);
		}
	}


	/* Kobo */

	private function detectKobo( $ua ) {
		if ( preg_match( '/Kobo (eReader|Touch)/u', $ua, $match ) ) {
			$this->type = 'ereader';
			$this->data = array(
				'manufacturer' => 'Kobo',
				'series'       => 'eReader',
			);
		}
	}


	/* Sony Reader */

	private function detectSonyreader( $ua ) {
		if ( preg_match( '/EBRD([0-9]+)/u', $ua, $match ) ) {
			$this->type = 'ereader';
			$this->data = array(
				'manufacturer' => 'Sony',
				'series'       => 'Reader',
			);
		}
	}

	/* PocketBook */

	private function detectPocketbook( $ua ) {
		if ( preg_match( '/PocketBook/u', $ua, $match ) ) {
			$this->type = 'ereader';
			$this->data = array(
				'manufacturer' => 'PocketBook',
			);
		}
	}
}
