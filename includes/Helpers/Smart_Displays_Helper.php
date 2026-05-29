<?php
namespace Clickwhale\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Smart_Displays_Helper extends Helper_Abstract {

	/**
	 * @var string
	 */
	protected static string $single = 'smart_display';

	/**
	 * @var string
	 */
	protected static string $plural = 'smart_displays';

	/**
	 * @var int
	 */
	protected static int $limit = 10;

	/**
	 * Return limitation notice string
	 *
	 * @return string
	 */
	public static function get_limitation_notice(): string {
		return sprintf(
			__( 'Currently, a maximum of %d smart displays can be added.', 'clickwhale' ),
			self::get_limit()
		);
	}

	/**
	 * Return shortcode string
	 *
	 * @param int $id
	 * @return string
	 */
	public static function get_shortcode( int $id ): string {
		return sprintf( '[cw_smart_display id="%d"]', $id );
	}

	/**
	 * @return array
	 */
	public static function get_border_styles(): array {
		return array(
			'none'   => 'none',
			'hidden' => 'hidden',
			'dotted' => 'dotted',
			'dashed' => 'dashed',
			'solid'  => 'solid',
			'double' => 'double',
			'groove' => 'groove',
			'ridge'  => 'ridge',
			'inset'  => 'inset',
			'outset' => 'outset'
		);
	}

	public static function get_font_size_labels(): array {
		return array(
			'lg' => __( 'Large', 'clickwhale' ),
			'md' => __( 'Standard', 'clickwhale' ),
			'sm' => __( 'Small', 'clickwhale' )
		);
	}

	public static function get_title_font_sizes(): array {
		return array(
			'lg' => '2rem',
			'md' => '1.5rem',
			'sm' => '1.125rem'
		);
	}

	public static function prepare_link_data( $link ): array {
		if ( empty( $link['id'] ) ) {
			return array();
		}

		return array(
			'id'   => (int) $link['id'],
			'source' => esc_url( trailingslashit( home_url( wp_unslash( $link['slug'] ) ) ) ),
			'destination' => esc_url( wp_unslash( $link['url'] ) )
		);
	}
}