<?php
namespace clickwhale\includes\front\tracking;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Clickwhale_Bot {
	/**
	 * @var bool
	 */
	public $is_bot;

	public function __construct( $ua ) {
		$this->is_bot = false;
		$this->detectBot( $ua );
	}

	public function detectBot( $ua ): Clickwhale_Bot {

		if ( preg_match( '/\+https?:\/\//iu', $ua )
		     || ( preg_match( '/(?:Bot|Robot|Spider|Crawler)([\/\);]|$)/iu', $ua )
		          && ! preg_match( '/CUBOT/iu', $ua ) ) ) {
			/* Detect bots based on url in the UA string */
			/* Detect bots based on common markers */
			$this->is_bot = true;
		} else {
			/* Detect based on a predefined list or markers
			    https://github.com/monperrus/crawler-user-agents/blob/master/crawler-user-agents.json
			*/
			$url     = plugin_dir_url( __FILE__ ) . 'library/crawler-user-agents.json';
			$request = wp_remote_get( $url );

			if ( 200 === wp_remote_retrieve_response_code( $request ) ) {
				$body = wp_remote_retrieve_body( $request );
				$bots = json_decode( $body, true );

				foreach ( $bots as $bot ) {
					if ( preg_match( '/' . $bot['pattern'] . '/', $ua ) ) {
						$this->is_bot = true;
						break;
					}
				}
			}
		}

		return $this;
	}
}