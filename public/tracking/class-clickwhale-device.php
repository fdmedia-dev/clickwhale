<?php

class Clickwhale_Device {

	public function __construct( $ua ) {
		$this->load_dependencies();
		$this->get_device_type( $ua );
	}

	private function load_dependencies() {
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'tracking/device/class-clickwhale-appliance.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'tracking/device/class-clickwhale-ereader.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'tracking/device/class-clickwhale-gaming.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'tracking/device/class-clickwhale-media.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'tracking/device/class-clickwhale-mobile.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'tracking/device/class-clickwhale-pda.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'tracking/device/class-clickwhale-tablet.php';
	}

	public function get_device_type( $ua ) {
		if ( preg_match( '/I-Opener [0-9.]+; Netpliance/u', $ua ) || preg_match( '/KOMATSU.*WL\//u', $ua ) ) {
			$this->device = new Clickwhale_Appliance( $ua );
		}
		if ( preg_match( '/(Kindle|Nook|Bookeen|Kobo|EBRD|PocketBook|Iriver)/ui', $ua ) ) {
			$this->device = new Clickwhale_Ereader( $ua );
		}
		if ( preg_match( '/(Nintendo|Nitro|PlayStation|PLAYSTATION|PS[0-9]|Sega|Dreamcast|Xbox)/ui', $ua ) ) {
			$this->device = new Clickwhale_Gaming( $ua );
		}
		if ( preg_match( '/(Archos|Zune|Walkman)/ui', $ua ) ) {
			$this->device = new Clickwhale_Media( $ua );
		}
		if ( preg_match( '/(T-Mobile|Danger|HPiPAQ|Acer|Amoi|AIRNESS|ASUS|BenQ|maui|ALCATEL|Bird|COOLPAD|CELKON|Coship|Cricket|DESAY|Diamond|dopod|Ericsson|FLY|GIONEE|GT-|Haier|HIKe|Hisense|HS|HTC|T[0-9]{4,4}|HUAWEI|Honor|Karbonn|KWC|KONKA|KTOUCH|K-Touch|Lenovo|Lephone|LG|Mi|Micromax|MOT|Nexian|NEC|NOKIA|NGM|OPPO|Panasonic|Pantech|Philips|Redmi|Sagem|Sanyo|Sam|SEC|SGH|SCH|SIE|Sony|SE|SHARP|Spice|Tecno|T-smart|TCL|Tiphone|Toshiba|UTStar|Videocon|vk|Vodafone|VSUN|Wynncom|Xiaomi|YUANDA|Zen|Ziox|ZTE|WAP)/ui', $ua )
		     || preg_match( '/(MIDP|CLDC|UNTRUSTED\/|3gpp-gba|[Ww][Aa][Pp]2.0|[Ww][Aa][Pp][ _-]?[Bb]rowser)/u', $ua )
		     || preg_match( '/Nokia[- \/]?([^\/\);]+)/ui', $ua )
		     || preg_match( '/(?:SAMSUNG; )?SAMSUNG ?[-\/]?([^;\/\)_,]+)/ui', $ua ) ) {
			$this->device = new Clickwhale_Mobile( $ua );
		}
		if ( preg_match( '/(CASIO|Palm|Psion|pdQ|COM|airboard|sharp|pda|POCKET-E|OASYS|NTT\/PI)/ui', $ua ) ) {
			$this->device = new Clickwhale_Pda( $ua );
		}
		if ( preg_match( '/WeTab-Browser /ui', $ua ) ) {
			$this->device = new Clickwhale_Tablet( $ua );
		}
	}
}