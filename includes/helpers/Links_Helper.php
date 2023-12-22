<?php

namespace clickwhale\includes\helpers;

use Exception;

class Links_Helper extends Helper_Abstract {

	protected static string $single = 'link';
	protected static string $plural = 'links';
	protected static int $limit = 9999;

	/**
	 * Return limitation notice string
	 * @return string
	 * @since 1.4.0
	 */
	public static function get_limitation_notice(): string {
		return sprintf(
			__( 'Currently, a maximum of %d links can be added.', CLICKWHALE_NAME ),
			self::get_limit(),
		);
	}

	/**
	 * @throws Exception
	 */
	public static function get_link_random_slug( int $length = 6 ): string {
		$characters = 'abcdefghijklmnopqrstuvwxyz';
		$string     = '';

		for ( $i = 0; $i < $length; $i ++ ) {
			$string .= $characters[ random_int( 0, strlen( $characters ) - 1 ) ];
		}

		return $string;
	}

}