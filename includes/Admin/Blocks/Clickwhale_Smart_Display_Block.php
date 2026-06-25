<?php

namespace Clickwhale\Admin\Blocks;

use Clickwhale\Front\SmartDisplays\Clickwhale_Smart_Display_Template;
use Clickwhale\Helpers\Smart_Displays_Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Gutenberg block: Smart Display
 *
 * @since 2.8.0
 */
class Clickwhale_Smart_Display_Block {

	public function register(): void {
		register_block_type(
			'clickwhale/smart-display',
			array(
				'attributes'      => array(
					'id'    => array(
						'type'    => 'number',
						'default' => 0,
					),
					'align' => array(
						'type'    => 'string',
						'default' => '',
					),
				),
				'supports'        => array(
					'align' => array( 'wide' ),
				),
				'render_callback' => array( $this, 'render' ),
			)
		);
	}

	public function register_block_category( array $categories ): array {
		return array_merge(
			array(
				array(
					'slug'  => 'clickwhale',
					'title' => 'ClickWhale',
					'icon'  => null,
				),
			),
			$categories
		);
	}

	public function enqueue_block_editor_assets(): void {
		$handle = 'clickwhale-smart-display-block';

		wp_enqueue_script(
			$handle,
			CLICKWHALE_ADMIN_ASSETS_DIR . '/js/blocks/cw-smart-display-block.js',
			array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n', 'wp-server-side-render' ),
			CLICKWHALE_VERSION,
			true
		);

		$smart_displays = Smart_Displays_Helper::get_all( 'title', 'asc', ARRAY_A );

		wp_localize_script(
			$handle,
			'clickwhaleBlockData',
			array(
				'smartDisplays' => array_values( array_filter( array_map(
					function ( $sd ) {
						if ( empty( $sd['title'] ) ) {
							return null;
						}
						$label = ! empty( $sd['name'] ) ? $sd['name'] : $sd['title'];

						return array(
							'id'    => intval( $sd['id'] ),
							'label' => $label . ' (#' . intval( $sd['id'] ) . ')',
						);
					},
					$smart_displays
				) ) ),
			)
		);
	}

	public function render( array $attrs ): string {
		$id = intval( $attrs['id'] ?? 0 );

		if ( ! $id ) {
			return '';
		}

		$data = Smart_Displays_Helper::get_by_id( $id );

		if ( ! $data || empty( $data['title'] ) ) {
			return '';
		}

		$template = new Clickwhale_Smart_Display_Template( $data );

		$inner = '';

		// Include styles inline when rendering for the block editor preview (REST request)
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			$inner .= '<style>' . Clickwhale_Smart_Display_Template::get_smart_display_styles() . '</style>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		ob_start();
		include CLICKWHALE_DIR . 'includes/Front/templates/smart-display.php';
		$inner .= ob_get_clean();

		$wrapper_attributes = get_block_wrapper_attributes();

		return '<div ' . $wrapper_attributes . '>' . $inner . '</div>';
	}
}
