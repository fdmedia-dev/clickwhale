<?php

namespace clickwhale_pro\includes\admin\tracking_codes;

use clickwhale\includes\Clickwhale;
use clickwhale\includes\helpers\Helper;

class Clickwhale_Pro_Tracking_Code_Edit {

	public function __construct() {
		add_action( 'admin_print_footer_scripts', [ $this, 'admin_scripts' ], 20 );
	}

	public function is_tracking_code_conversion(): bool {
		return true;
	}

	private static function default_dynamic_values_text(): string {
		$text = __( "See Dynamic conversion values tags below:", CLICKWHALE_PRO_NAME ) . "<br>";
		$text .= '<em>';
		$text .= "<strong>@@ORDERID@@</strong>: " . __( "return the order id", CLICKWHALE_PRO_NAME ) . "<br>";
		$text .= "<strong>@@CURRENCY@@</strong>: " . __( "return the order currency", CLICKWHALE_PRO_NAME ) . "<br>";
		$text .= "<strong>@@FULLNAME@@</strong>: " . __( "return the user's full name", CLICKWHALE_PRO_NAME ) . "<br>";
		$text .= "<strong>@@EMAIL@@</strong>: " . __( "return the user's email", CLICKWHALE_PRO_NAME ) . "<br>";
		$text .= "<strong>@@PRODUCTS@@</strong>: " . __( "return the name of the purchased products separated by a comma",
				CLICKWHALE_PRO_NAME ) . "<br>";
		$text .= "<strong>@@AMOUNT@@</strong>: " . __( "return the amount of the cart payment",
				CLICKWHALE_PRO_NAME ) . " (<strong>" . __( "tax and shipping excluded",
				CLICKWHALE_PRO_NAME ) . "</strong>)<br>";
		$text .= "<strong>@@TAX@@</strong>: " . __( "return the amount of the tax in the purchase",
				CLICKWHALE_PRO_NAME ) . "<br>";
		$text .= "<strong>@@TOTAL@@</strong>: " . __( "return the total amount of the purchase",
				CLICKWHALE_PRO_NAME ) . " (<strong>" . __( "tax included", CLICKWHALE_PRO_NAME ) . "</strong>)";
		$text .= '</em>';

		return $text;
	}

	public function tracking_code_conversion_fields( $item ) {
		$is_woo = class_exists( 'WooCommerce' );
		$is_edd = function_exists( 'EDD' );

		if ( $is_woo ) {
			echo Helper::render_control(
				array(
					'row_label'   => __( 'WooCommerce Products for tracking', CLICKWHALE_PRO_NAME ),
					'control'     => 'select',
					'id'          => 'position_conversion_product_ids',
					'class'       => 'with-select2',
					'name'        => 'position[conversion_items][product][ids][]',
					'value'       => $item['position']['conversion_items']['product']['ids'] ?? '',
					'options'     => Clickwhale::get_instance()->tracking_code::get_posts_by_post_type( 'product' ),
					'default'     => '',
					'multiple'    => true,
					'description' => self::default_dynamic_values_text()
				),
				true,
				'for_mode for_product_mode'
			);
		}

		if ( $is_edd ) {
			echo Helper::render_control(
				array(
					'row_label'   => __( 'Easy Digital Downloads Products for tracking', CLICKWHALE_PRO_NAME ),
					'control'     => 'select',
					'id'          => 'position_conversion_download_ids',
					'class'       => 'with-select2',
					'name'        => 'position[conversion_items][download][ids][]',
					'value'       => $item['position']['conversion_items']['download']['ids'] ?? '',
					'options'     => Clickwhale::get_instance()->tracking_code::get_posts_by_post_type( 'download' ),
					'default'     => '',
					'multiple'    => true,
					'description' => self::default_dynamic_values_text()
				),
				true,
				'for_mode for_download_mode'
			);
		}
	}

	public function admin_scripts() {
		?>
        <script type='text/javascript'>
            jQuery(document).ready(function () {

                jQuery('[name="position[conversion]"]').prop('disabled', false);

                if (jQuery('[name="position[conversion]"]')) {
                    const modeVal = jQuery('[name="position[conversion]"]:checked').val();
                    jQuery('.form-field.for_mode').hide();
                    jQuery(`.form-field.for_${modeVal}_mode`).show();
                }

                jQuery(document)
                    .on('change', '[name="position[conversion]"]', function () {
                        const modeVal = jQuery(this).val();
                        jQuery('.form-field.for_mode').hide();
                        jQuery(`.form-field.for_${modeVal}_mode`).show();
                    })

            });
        </script>
		<?php
	}
}