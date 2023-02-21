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

	public static function get_random_slug( int $length = 6 ): string {
		$characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$string     = '';

		for ( $i = 0; $i < $length; $i ++ ) {
			$string .= $characters[ mt_rand( 0, strlen( $characters ) - 1 ) ];
		}

		return $string;
	}

}