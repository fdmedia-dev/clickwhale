<?php
namespace clickwhale\includes\helpers;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Categories_Helper extends Helper_Abstract {

    /**
     * @var string
     */
    protected static string $single = 'category';

    /**
     * @var string
     */
    protected static string $plural = 'categories';

    /**
     * @var int
     */
    protected static int $limit = 10;

    /**
     * Return limitation notice string
     *
     * @return string
     * @since 1.4.0
     */
    public static function get_limitation_notice(): string {
        $count = self::get_limit();

        /* translators: %1$d: maximum number of categories */
        $text = _n(
            'Currently, a maximum of %1$d category can be added.',
            'Currently, a maximum of %1$d categories can be added.',
            $count,
            'clickwhale'
        );

        return sprintf( $text, intval( $count ) );
    }
}
