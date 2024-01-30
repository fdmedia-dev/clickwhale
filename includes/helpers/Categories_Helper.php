<?php
namespace clickwhale\includes\helpers;

class Categories_Helper extends Helper_Abstract {

    /**
     * @var string
     */
	protected static $single = 'category';

    /**
     * @var string
     */
	protected static $plural = 'categories';

    /**
     * @var int
     */
	protected static $limit = 10;

	/**
	 * Return limitation notice string
	 *
	 * @return string
	 * @since 1.4.0
	 */
	public static function get_limitation_notice(): string {
		return sprintf(
			__( 'Currently, a maximum of %d categories can be added.', CLICKWHALE_SLUG ),
			self::get_limit()
		);
	}
}