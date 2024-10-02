<?php
namespace clickwhale\includes\admin\tracking_codes;

use clickwhale\includes\admin\Clickwhale_Instance_Edit;
use clickwhale\includes\helpers\{
	Helper,
	Linkpages_Helper,
	Tracking_Codes_Helper
};

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Clickwhale_Tracking_Code_Edit extends Clickwhale_Instance_Edit {

    /**
     * @var bool
     */
	public $conversion;

	public function __construct() {
		parent::__construct( 'tracking_codes', 'tracking_code' );

		$this->conversion = apply_filters( 'clickwhale_is_tracking_code_conversion', false );
	}

	/**
	 * Default values for new item
	 * Could be hooked by filter "clickwhale_tracking_code_defaults"
	 * @return array
	 */
	public function get_defaults(): array {
		return array(
			'id'          => 0,
			'title'       => '',
			'description' => '',
			'type'        => '', //html/css/js
			'code'        => '',
			'position'    => array(), // array(), post/page/CPT/LP // header/footer/body
			'is_active'   => 0,
			'author'      => 0,
			'created_at'  => '',
			'updated_at'  => '',
		);
	}

	public static function conversion_fields( $item ) {
		$is_woo = class_exists( 'WooCommerce' );
		$is_edd = function_exists( 'EDD' );
		?>

        <tr class="form-field">
            <th scope="row">
                <label for="position">
					<?php _e( 'Where do you want to add this code?', CLICKWHALE_NAME ) ?>
                </label>
            </th>
            <td>
                <fieldset>
					<?php
					$is_pro_label   = Helper::admin_pro_label();
					$radio_class    = $is_pro_label ? 'disabled' : '';
					$is_disabled    = $is_pro_label ? 'disabled="disabled"' : '';
					$conversion_val = $item['position']['conversion'] ?? '';
					?>
                    <div class="radio-cards">
                        <div class="radio-card radio-conversion">
                            <input id="conversionStandard"
                                   type="radio"
                                   name="position[conversion]"
                                   value="standard"
								<?php checked( $item['position']['conversion'] ?? 'standard', 'standard' ); ?>
                            >
                            <label for="conversionStandard">
                                <img src="<?php echo CLICKWHALE_ADMIN_ASSETS_DIR . '/images/vendors/logo-wordpress-dark.svg'; ?>"
                                     alt="WordPress">
                                <span><?php _e( 'Standard code tracking', CLICKWHALE_NAME ) ?></span>
                            </label>
                        </div>

						<?php if ( $is_woo ) { ?>
                            <div class="radio-card radio-conversion <?php echo $radio_class ?>">
								<?php if ( $is_pro_label ) { ?>
                                    <div class="radio-card--lock">
                                        <svg class="feather">
                                            <use href="<?php echo CLICKWHALE_ADMIN_ASSETS_DIR ?>/images/feather-sprite.svg#lock"></use>
                                        </svg>
                                    </div>
                                    <div class="radio-card--pro"><?php echo $is_pro_label ?></div>
								<?php } ?>
                                <input id="conversionProduct"
                                       type="radio"
                                       name="position[conversion]"
                                       value="product"
									<?php
									echo $is_disabled;
									checked( $conversion_val, 'product' );
									?>
                                >
                                <label for="conversionProduct">
                                    <img src="<?php echo CLICKWHALE_ADMIN_ASSETS_DIR . '/images/vendors/logo-woocommerce-short-purple.svg'; ?>"
                                         alt="WooCommerce">
                                    <span><?php _e( 'WooCommerce conversion', CLICKWHALE_NAME ) ?></span>
                                </label>
                            </div>
						<?php } ?>

						<?php if ( $is_edd ) { ?>
                            <div class="radio-card radio-conversion <?php echo $radio_class ?>">
								<?php if ( $is_pro_label ) { ?>
                                    <div class="radio-card--lock">
                                        <svg class="feather">
                                            <use href="<?php echo CLICKWHALE_ADMIN_ASSETS_DIR ?>/images/feather-sprite.svg#lock"></use>
                                        </svg>
                                    </div>
                                    <div class="radio-card--pro"><?php echo $is_pro_label ?></div>
								<?php } ?>
                                <input id="conversionDownload"
                                       type="radio"
                                       name="position[conversion]"
                                       value="download"
									<?php
									echo $is_disabled;
									checked( $conversion_val, 'download' );
									?>
                                >
                                <label for="conversionDownload">
                                    <img src="<?php echo CLICKWHALE_ADMIN_ASSETS_DIR . '/images/vendors/logo-edd-short-dark.svg'; ?>"
                                         alt="Easy Digital Downloads">
                                    <span><?php _e( 'EDD conversion', CLICKWHALE_NAME ) ?></span>
                                </label>
                            </div>
						<?php } ?>
                    </div>
                </fieldset>
            </td>
        </tr>

		<?php
		do_action( 'clickwhale_tracking_code_conversion_fields', $item );
	}

	public function get_item( $request ) {

		if ( ! is_numeric( $request['id'] ) ) {
			$this->no_item();
		}

		// Get default values
		$defaults = apply_filters( "clickwhale_{$this->instance_single}_defaults", $this->get_defaults() );

		// If item id=0 or id doesn't set/exists than use $defaults
		if ( empty( $request['id'] ) ) {
			return $defaults;
		}

		// Get data by id
		$helper = ucfirst( "{$this->instance_plural}_Helper" );

		$item = call_user_func(
			array( "clickwhale\\includes\\helpers\\" . $helper, 'get_by_id' ),
			$request['id'] );
		$item['position'] = maybe_unserialize( $item['position'] );

		// If link with id doesn't exist
		if ( ! $item ) {
			$this->no_item();
		}

		return $item;
	}

	public function get_linkpages(): array {
		$result    = [];
		$linkpages = Linkpages_Helper::get_all( 'title', 'asc', 'ARRAY_A' );
		if ( $linkpages ) {
			$result['all'] = __( 'All', CLICKWHALE_NAME );
			foreach ( $linkpages as $linkpage ) {
				$result[ $linkpage['id'] ] = $linkpage['title'];
			}
		}

		return $result;
	}

	public static function get_posts_by_post_type( $post_type ): array {
		$result = [];
		$args   = array(
			'numberposts' => - 1,
			'post_type'   => $post_type,
			'orderby'     => 'title',
			'order'       => 'ASC',
			'post_status' => 'publish'
		);
		$posts  = get_posts( $args );

		if ( $posts ) {
			$result['all'] = __( 'All', CLICKWHALE_NAME );
			foreach ( $posts as $post ) {
				$result[ $post->ID ] = $post->post_title;
			}
		}

		return $result;
	}

	public function get_terms_by_tax( $taxonomy ): array {
		$result = [];
		$args   = array(
			'taxonomy'   => $taxonomy,
			'hide_empty' => false,
		);
		$terms  = get_terms( $args );
		if ( $terms ) {
			$result['all'] = __( 'All', CLICKWHALE_NAME );
			foreach ( $terms as $term ) {
				$result[ $term->term_id ] = $term->name;
			}
		}

		return $result;

	}

	public function save_update() {
		global $wpdb;

		$tracking_codes_table = Helper::get_db_table_name( $this->instance_plural );
		$item                 = array_intersect_key( $_POST, $this->get_defaults() );
		$item['description']  = esc_html( $item['description'] );
		$item['author']       = get_current_user_id();

		if ( isset( $item['position']['conversion'] ) && $item['position']['conversion'] !== 'standard' ) {
			unset( $item['position']['items_included'] );
			unset( $item['position']['items_excluded'] );
			unset( $item['position']['code'] );
			unset( $item['position']['pages'] );
			foreach ( $item['position']['conversion_items'] as $k => $v ) {
				if ( $k !== $item['position']['conversion'] ) {
					unset( $item['position']['conversion_items'][ $k ] );
				}
			}
		} else {
			unset( $item['position']['conversion_items'] );
		}

		// Handle CW Link Pages
		if ( ! isset( $item['position']['items_included']['cw_linkpage']['active'] ) ) {
			unset( $item['position']['items_included']['cw_linkpage'] );
		}
		if ( ! isset( $item['position']['items_excluded']['cw_linkpage']['active'] ) ) {
			unset( $item['position']['items_excluded']['cw_linkpage'] );
		}

		// Handle Post Types
		foreach ( Tracking_Codes_Helper::get_default_post_types() as $post_type ) {
			if ( ! isset( $item['position']['items_included'][ $post_type ]['active'] ) ) {
				unset( $item['position']['items_included'][ $post_type ] );
			}
			if ( ! isset( $item['position']['items_excluded'][ $post_type ]['active'] ) ) {
				unset( $item['position']['items_excluded'][ $post_type ] );
			}
		}

		// Handle Taxonomies
		foreach ( Tracking_Codes_Helper::get_default_terms_tax() as $taxonomy ) {
			if ( ! isset( $item['position']['items_included'][ $taxonomy ]['active'] ) ) {
				unset( $item['position']['items_included'][ $taxonomy ] );
			}
			if ( ! isset( $item['position']['items_excluded'][ $taxonomy ]['active'] ) ) {
				unset( $item['position']['items_excluded'][ $taxonomy ] );
			}
		}

		$item['position']  = maybe_serialize( $item['position'] );
		$item['is_active'] = $item['is_active'] ?? 0;

		$item = apply_filters( 'clickwhale_tracking_code_data_before_save', $item );

		if ( Tracking_Codes_Helper::get_by_id( intval( $item['id'] ) ) ) {
			$wpdb->update(
				$tracking_codes_table,
				$item,
				array( 'id' => $item['id'] )
			);
			$this->set_transient( $item['id'], 'updated' );
		} else {
			$wpdb->insert(
				$tracking_codes_table,
				$item
			);
			$item['id'] = $wpdb->insert_id;
			$this->set_transient( $item['id'], 'added' );
		}

		$url = 'admin.php?page=' . CLICKWHALE_SLUG . '-edit-tracking-code&id=' . $item['id'];
		wp_redirect( admin_url( $url ) );
	}

	public function admin_scripts(): void {
		?>
        <script type='text/javascript'>
            jQuery(document).ready(function() {
                jQuery('#position_code').select2({
                    placeholder: '<?php _e( 'Select Code position', CLICKWHALE_NAME ) ?>',
                    width: '100%',
                    minimumResultsForSearch: -1
                });
                jQuery('.with-select2').select2({
                    placeholder: '<?php _e( 'Select', CLICKWHALE_NAME ) ?>',
                    width: '100%',
                    multiple: true,
                    minimumResultsForSearch: 10
                });

                if (jQuery('#code').length) {
                    let editorSettings = wp.codeEditor.defaultSettings ? _.clone(wp.codeEditor.defaultSettings) : {};

                    editorSettings.codemirror = _.extend(
                        {},
                        editorSettings.codemirror,
                        {
                            indentUnit: 2,
                            tabSize: 2,
                        }
                    );
                    let editor = wp.codeEditor.initialize(jQuery('#code'), editorSettings);
                }

                // Toggle pages select
                if (jQuery('[name="position[pages]"]:checked').val() !== 'all') {
                    jQuery('.cw-posts-row--included').show();
                    jQuery('.cw-posts-row--excluded').hide();
                } else {
                    jQuery('.cw-posts-row--included').hide();
                    jQuery('.cw-posts-row--excluded').show();
                }

                // Toggle page select
                jQuery('.cw-posts-row').each(function() {
                    let
                        checkbox = jQuery(this).find('[type="checkbox"]'),
                        selectWrap = jQuery(this).find('.cw-posts-row--select');

                    if (checkbox.is(':checked')) {
                        selectWrap.show();
                    } else {
                        selectWrap.hide();
                    }
                });

                // Will be enabled with PRO
                jQuery('[name="position[conversion]"]').prop('disabled', true);
                jQuery('[name="position[conversion]"][value="standard"]').prop('disabled', false);

                jQuery(document)
                    .on('change', '[name="position[pages]"]', function() {
                        if (jQuery(this).val() !== 'all') {
                            jQuery('.cw-posts-row--included').show();
                            jQuery('.cw-posts-row--excluded').hide();
                        } else {
                            jQuery('.cw-posts-row--included').hide();
                            jQuery('.cw-posts-row--excluded').show();
                        }
                    })
                    .on('change', '.cw-posts-row [type="checkbox"]', function() {
                        let parent = jQuery(this).closest('.cw-posts-row');

                        if (jQuery(this).is(':checked')) {
                            jQuery(parent).find('.cw-posts-row--select').show()
                        } else {
                            jQuery(parent).find('.cw-posts-row--select').hide()
                        }
                    })
            });
        </script>
		<?php
	}
}