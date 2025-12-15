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
    public bool $conversion;

    public function __construct() {
        $this->instance_plural = 'tracking_codes';
        $this->instance_single = 'tracking_code';
        $this->instance_helper = Tracking_Codes_Helper::class;
        parent::__construct();

        $this->conversion = apply_filters( 'clickwhale_is_tracking_code_conversion', false );
    }

    protected function get_title_i18n(): string {
        return __( 'Tracking Code', 'clickwhale' );
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
                    <?php esc_html_e( 'Where do you want to add this code?', 'clickwhale' ); ?>
                </label>
            </th>
            <td>
                <fieldset>
                    <?php
                    $is_pro_label   = Helper::admin_pro_label();
                    $radio_class    = $is_pro_label ? 'disabled' : '';
                    $is_disabled    = $is_pro_label ? 'disabled="disabled"' : '';
                    $conversion_val = esc_attr( $item['position']['conversion'] ?? '' );
                    ?>
                    <div class="radio-cards">
                        <div class="radio-card radio-conversion">
                            <input id="conversionStandard"
                                   type="radio"
                                   name="position[conversion]"
                                   value="standard"
                                <?php checked( $conversion_val ?? 'standard', 'standard' ); ?>
                            >
                            <label for="conversionStandard">
                                <img src="<?php echo CLICKWHALE_ADMIN_ASSETS_DIR . '/images/vendors/logo-wordpress-dark.svg'; ?>"
                                     alt="WordPress">
                                <span><?php esc_html_e( 'Standard code tracking', 'clickwhale' ); ?></span>
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
                                    <span><?php esc_html_e( 'WooCommerce conversion', 'clickwhale' ); ?></span>
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
                                    <span><?php esc_html_e( 'EDD conversion', 'clickwhale' ); ?></span>
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

    protected function normalize_loaded_item( array $item ): array {
        $item['position'] = maybe_unserialize( $item['position'] );
        return $item;
    }

    public function get_linkpages(): array {
        $result = array();
        $linkpages = Linkpages_Helper::get_all( 'title', 'asc', 'ARRAY_A' );
        if ( $linkpages ) {
            $result['all'] = __( 'All', 'clickwhale' );
            foreach ( $linkpages as $linkpage ) {
                $result[$linkpage['id']] = $linkpage['title'];
            }
        }

        return $result;
    }

    public static function get_posts_by_post_type( $post_type ): array {
        $result = array();
        $args   = array(
            'numberposts' => - 1,
            'post_type'   => $post_type,
            'orderby'     => 'title',
            'order'       => 'ASC',
            'post_status' => 'publish'
        );
        $posts  = get_posts( $args );

        if ( $posts ) {
            $result['all'] = __( 'All', 'clickwhale' );
            foreach ( $posts as $post ) {
                $result[$post->ID] = $post->post_title;
            }
        }

        return $result;
    }

    public function get_terms_by_tax( $taxonomy ): array {
        $result = array();
        $args   = array(
            'taxonomy'   => $taxonomy,
            'hide_empty' => false,
        );
        $terms  = get_terms( $args );
        if ( $terms ) {
            $result['all'] = __( 'All', 'clickwhale' );
            foreach ( $terms as $term ) {
                $result[$term->term_id] = $term->name;
            }
        }

        return $result;
    }

    protected function process_item_before_save( array $item ): array {
        $item['description'] = esc_html( $item['description'] );
        $item['author']      = get_current_user_id();

        if ( isset( $item['position']['conversion'] ) && $item['position']['conversion'] !== 'standard' ) {
            unset( $item['position']['items_included'] );
            unset( $item['position']['items_excluded'] );
            unset( $item['position']['code'] );
            unset( $item['position']['pages'] );
            foreach ( $item['position']['conversion_items'] as $k => $v ) {
                if ( $k !== $item['position']['conversion'] ) {
                    unset( $item['position']['conversion_items'][$k] );
                }
            }
        } else {
            unset( $item['position']['conversion_items'] );
        }

        /** CW Link Pages */

        // Included
        if ( ! isset( $item['position']['items_included']['cw_linkpage']['active'] ) ) {
            unset( $item['position']['items_included']['cw_linkpage'] );

        } elseif ( empty( $item['position']['items_included']['cw_linkpage']['ids'] ) ) {
            $item['position']['items_included']['cw_linkpage']['ids'][] = 'all';
        }

        // Excluded
        if ( ! isset( $item['position']['items_excluded']['cw_linkpage']['active'] ) ) {
            unset( $item['position']['items_excluded']['cw_linkpage'] );

        } elseif ( empty( $item['position']['items_excluded']['cw_linkpage']['ids'] ) ) {
            $item['position']['items_excluded']['cw_linkpage']['ids'][] = 'all';
        }

        /** Post Types */

        $post_types = Tracking_Codes_Helper::get_default_post_types();

        foreach ( $post_types as $post_type => $post_label ) {
            // Included
            if ( ! isset( $item['position']['items_included'][$post_type]['active'] ) ) {
                unset( $item['position']['items_included'][$post_type] );

            } elseif ( empty( $item['position']['items_included'][$post_type]['ids'] ) ) {
                $item['position']['items_included'][$post_type]['ids'][] = 'all';
            }

            // Excluded
            if ( ! isset( $item['position']['items_excluded'][$post_type]['active'] ) ) {
                unset( $item['position']['items_excluded'][$post_type] );

            } elseif ( empty( $item['position']['items_excluded'][$post_type]['ids'] ) ) {
                $item['position']['items_excluded'][$post_type]['ids'][] = 'all';
            }
        }

        /** Taxonomies */

        $taxonomies = Tracking_Codes_Helper::get_default_terms_tax();

        foreach ( $taxonomies as $taxonomy ) {
            // Included
            if ( ! isset( $item['position']['items_included'][$taxonomy]['active'] ) ) {
                unset( $item['position']['items_included'][$taxonomy] );

            } elseif ( empty( $item['position']['items_included'][$taxonomy]['ids'] ) ) {
                $item['position']['items_included'][$taxonomy]['ids'][] = 'all';
            }

            // Excluded
            if ( ! isset( $item['position']['items_excluded'][$taxonomy]['active'] ) ) {
                unset( $item['position']['items_excluded'][$taxonomy] );

            } elseif ( empty( $item['position']['items_excluded'][$taxonomy]['ids'] ) ) {
                $item['position']['items_excluded'][$taxonomy]['ids'][] = 'all';
            }
        }

        $item['position'] = maybe_serialize( $item['position'] );
        $item['is_active'] = ! empty( $item['is_active'] ) ? 1 : 0;
        return $item;
    }

    public function admin_scripts(): void {
        ?>
        <script type='text/javascript'>
            jQuery(document).ready(function(){
                jQuery('#position_code').select2({
                    placeholder: <?php echo wp_json_encode( __( 'Select Code position', 'clickwhale' ) ); ?>,
                    width: '100%',
                    minimumResultsForSearch: -1
                });

                jQuery('.with-select2').select2({
                        placeholder: <?php echo wp_json_encode( __( 'Select', 'clickwhale' ) ); ?>,
                        width: '100%',
                        multiple: true,
                        minimumResultsForSearch: 10
                    })
                    .on('select2:select', function(e){
                        const
                            select = jQuery(this),
                            data = e.params.data,
                            selected = select.val();

                        if (data.id !== 'all'){
                            selected.splice(selected.indexOf('all'), 1);
                            selected.push(data.id);

                            select.val(selected);
                            select.trigger('change');
                        } else {
                            select.val('all');
                            select.trigger('change');
                        }
                    });

                if (jQuery('#code').length){
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
                if (jQuery('[name="position[pages]"]:checked').val() !== 'all'){
                    jQuery('.cw-posts-row--included').show();
                    jQuery('.cw-posts-row--excluded').hide();
                } else {
                    jQuery('.cw-posts-row--included').hide();
                    jQuery('.cw-posts-row--excluded').show();
                }

                // Toggle page select
                jQuery('.cw-posts-row').each(function(){
                    let
                        checkbox = jQuery(this).find('[type="checkbox"]'),
                        selectWrap = jQuery(this).find('.cw-posts-row--select');

                    if (checkbox.is(':checked')){
                        selectWrap.show();
                    } else {
                        selectWrap.hide();
                    }
                });

                // May be enabled in PRO
                jQuery('[name="position[conversion]"]').prop('disabled', true);
                jQuery('[name="position[conversion]"][value="standard"]').prop('disabled', false);

                jQuery(document)
                    .on('change', '[name="position[pages]"]', function(){
                        if (jQuery(this).val() !== 'all'){
                            jQuery('.cw-posts-row--included').show();
                            jQuery('.cw-posts-row--excluded').hide();
                        } else {
                            jQuery('.cw-posts-row--included').hide();
                            jQuery('.cw-posts-row--excluded').show();
                        }
                    })
                    .on('change', '.cw-posts-row [type="checkbox"]', function(){
                        let parent = jQuery(this).closest('.cw-posts-row');

                        if (jQuery(this).is(':checked')){
                            jQuery(parent).find('.cw-posts-row--select').show();
                        } else {
                            jQuery(parent).find('.cw-posts-row--select').hide();
                        }
                    });
            });
        </script>
        <?php
    }
}
