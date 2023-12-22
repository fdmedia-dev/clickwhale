<?php

namespace clickwhale\includes\helpers;

class Categories_Helper extends Helper_Abstract {

	protected static string $single = 'category';
	protected static string $plural = 'categories';
	protected static int $limit = 10;

	/**
	 * Return limitation notice string
	 *
	 * @return string
	 * @since 1.4.0
	 */
	public static function get_limitation_notice(): string {
		return sprintf(
			__( 'Currently, a maximum of %d categories can be added.', CLICKWHALE_NAME ),
			self::get_limit(),
		);
	}
}