<?php
namespace clickwhale\includes\front\tracking\device;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Clickwhale_Mobile {

	public function __construct( $ua ) {
		/* Detect the type based on some common markers */
		$this->detectGenericMobile( $ua );

		/* Look for specific manufacturers and models */
		$this->detectNokia( $ua );
		$this->detectSamsung( $ua );

		/* Try to parse some generic methods to store device information */
		$this->detectGenericMobileModels( $ua );

	}

	/* Generic markers */

	private function detectGenericMobile( $ua ) {
		if ( preg_match( '/(MIDP|CLDC|UNTRUSTED\/|3gpp-gba|[Ww][Aa][Pp]2.0|[Ww][Aa][Pp][ _-]?[Bb]rowser)/u', $ua ) ) {
			$this->type = 'mobile';
		}
	}


	/* Nokia */

	private function detectNokia( $ua ) {

		if ( preg_match( '/Nokia[- \/]?([^\/\);]+)/ui', $ua, $match ) ) {
			if ( $match[1] == 'Browser' ) {
				return;
			}
			$this->type = 'mobile';
			$this->data = array(
				'manufacturer' => 'Nokia',
			);
		}
	}


	/* Samsung */

	private function detectSamsung( $ua ) {
		if ( preg_match( '/(?:SAMSUNG; )?SAMSUNG ?[-\/]?([^;\/\)_,]+)/ui', $ua, $match ) ) {
			if ( $match[1] === 'Browser' ) {
				return;
			}
			$this->type = 'mobile';
			$this->data = array(
				'manufacturer' => 'Samsung',
			);
		}
	}


	/* Generic models */

	private function detectGenericMobileModels( $ua ) {
		if ( preg_match( '/(T-Mobile|Danger|HPiPAQ|Acer|Amoi|AIRNESS|ASUS|BenQ|maui|ALCATEL|Bird|COOLPAD|CELKON|Coship|Cricket|DESAY|Diamond|dopod|Ericsson|FLY|GIONEE|GT-|Haier|HIKe|Hisense|HS|HTC|T[0-9]{4,4}|HUAWEI|Honor|Karbonn|KWC|KONKA|KTOUCH|K-Touch|Nokia|Lenovo|Lephone|LG|Mi|Micromax|MOT|Nexian|NEC|NOKIA|NGM|OPPO|Panasonic|Pantech|Philips|Redmi|Sagem|Samsung|SAMSUNG|Sanyo|Sam|SEC|SGH|SCH|SIE|Sony|SE|SHARP|Spice|Tecno|T-smart|TCL|Tiphone|Toshiba|UTStar|Videocon|vk|Vodafone|VSUN|Wynncom|Xiaomi|YUANDA|Zen|Ziox|ZTE|WAP)/ui',
			$ua ) ) {
			$this->type = 'mobile';
		}
	}

}