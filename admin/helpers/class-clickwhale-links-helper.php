<?php

class ClickwhaleLinksHelper {

	/**
	 * Filter function
	 * return number of available links
	 * @return mixed|void
	 */
	public static function get_limit() {
		return apply_filters( 'clickwhale_links_limit', 1 );
	}

	/**
	 * @throws Exception
	 */
	public static function get_random_slug( int $length = 6 ): string {
		$characters = 'abcdefghijklmnopqrstuvwxyz';
		$string     = '';

		for ( $i = 0; $i < $length; $i ++ ) {
			$string .= $characters[ random_int( 0, strlen( $characters ) - 1 ) ];
		}

		return $string;
	}

}