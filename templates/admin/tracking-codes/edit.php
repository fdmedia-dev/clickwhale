<?php

use clickwhale\includes\helpers\{Helper, Tracking_Codes_Helper};

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$clickwhale_get_id = (int) filter_input( INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT );
Tracking_Codes_Helper::get_limitation_error( $clickwhale_get_id );

$clickwhale_tracking_code = clickwhale()->tracking_code;

$clickwhale_item = $clickwhale_tracking_code->get_item( array( 'id' => $clickwhale_get_id ) );
$clickwhale_item_id = intval( $clickwhale_item['id'] );
$clickwhale_linkpages = $clickwhale_tracking_code->get_linkpages();
$clickwhale_post_types = Tracking_Codes_Helper::get_default_post_types();
$clickwhale_taxonomies = Tracking_Codes_Helper::get_default_terms_tax();

do_action( 'clickwhale_admin_banner' );
?>
<div class="wrap">
    <?php
    echo wp_kses(
        Helper::render_heading(
            array(
                'name'         => esc_html__( 'Tracking Code', 'clickwhale' ),
                'is_edit'      => $clickwhale_item_id !== 0,
                'link_to_list' => esc_attr( CLICKWHALE_SLUG ) . '-tracking-codes',
                'link_to_add'  => esc_attr( CLICKWHALE_SLUG ) . '-edit-tracking-code'
            )
        ),
        Helper::get_allowed_tags()
    );

    $clickwhale_tracking_code->show_message( $clickwhale_item_id );
    ?>

    <?php do_action( 'clickwhale_admin_sidebar_begin' ); ?>

    <form id="form_edit_<?php echo esc_attr( $clickwhale_tracking_code->instance_single ); ?>"
          class="clickwhale_form_edit"
          method="POST"
          action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
    >
        <input type="hidden" name="action" value="save_update_clickwhale_<?php echo esc_attr( $clickwhale_tracking_code->instance_single ); ?>" />
        <input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce( basename( __FILE__ ) ) ); ?>" />
        <input type="hidden" name="id" value="<?php echo intval( $clickwhale_item_id ); ?>" />

        <div id="post-body-content">
            <table style="width: 100%;" class="form-table">
                <caption style="display: none"><?php esc_html_e( 'Tracking Code Edit Table', 'clickwhale' ); ?></caption>
                <tbody>
                    <?php
                    echo wp_kses(
                        Helper::render_control(
                            array(
                                'row_label'   => esc_html__( 'Title', 'clickwhale' ),
                                'control'     => 'input',
                                'id'          => 'title',
                                'name'        => 'title',
                                'type'        => 'text',
                                'value'       => esc_attr( wp_unslash( $clickwhale_item['title'] ) ),
                                'placeholder' => esc_attr__( 'e.g. Google Tag Manager Code', 'clickwhale' ),
                                'required'    => true
                            ),
                            true
                        ),
                        Helper::get_allowed_tags()
                    );

                    /** @link https://www.ibenic.com/wordpress-code-editor/ */
                    echo wp_kses(
                        Helper::render_control(
                            array(
                                'row_label'   => esc_html__( 'Code', 'clickwhale' ),
                                'control'     => 'textarea',
                                'id'          => 'code',
                                'name'        => 'code',
                                'value'       => esc_textarea( wp_unslash( $clickwhale_item['code'] ) ),
                                'description' => esc_html__( 'Paste your code here.', 'clickwhale' )
                            ),
                            true
                        ),
                        Helper::get_allowed_tags()
                    );

                    $clickwhale_tracking_code->conversion_fields( $clickwhale_item );

                    echo wp_kses(
                        Helper::render_control(
                            array(
                                'row_label' => esc_html__( 'Code Position', 'clickwhale' ),
                                'control'   => 'select',
                                'id'        => 'position_code',
                                'name'      => 'position[code]',
                                'value'     => esc_attr( $clickwhale_item['position']['code'] ?? '' ),
                                'options'   => array(
                                    'wp_head'      => esc_html__( 'Before </head>', 'clickwhale' ),
                                    'wp_body_open' => esc_html__( 'After <body>', 'clickwhale' ),
                                    'wp_footer'    => esc_html__( 'Before </body>', 'clickwhale' )
                                )
                            ),
                            true,
                            'for_mode for_standard_mode'
                        ),
                        Helper::get_allowed_tags()
                    );
                    ?>

                    <tr class="form-field for_mode for_standard_mode">
                        <th scope="row">
                            <label for="position"><?php esc_html_e( 'In which page do you want to insert this code?', 'clickwhale' ); ?></label>
                        </th>
                        <td>
                            <?php
                            echo wp_kses(
                                Helper::render_control(
                                    array(
                                        'control' => 'radio',
                                        'id'      => 'position_pages',
                                        'name'    => 'position[pages]',
                                        'value'   => esc_attr( $clickwhale_item['position']['pages'] ?? '' ),
                                        'options' => array(
                                            'all'    => esc_html__( 'Whole website', 'clickwhale' ),
                                            'custom' => esc_html__( 'Specific page', 'clickwhale' )
                                        ),
                                        'default' => 'all'
                                    )
                                ),
                                Helper::get_allowed_tags()
                            );
                            ?>

                            <br>

                            <?php if ( $clickwhale_linkpages ) { ?>
                                <div class="cw-posts-row cw-posts-row--included">
                                    <?php
                                    echo wp_kses(
                                        Helper::render_control(
                                            array(
                                                'control' => 'checkbox',
                                                'id'      => 'position_include_cw_linkpage',
                                                'name'    => 'position[items_included][cw_linkpage][active]',
                                                'value'   => esc_attr( $clickwhale_item['position']['items_included']['cw_linkpage']['active'] ?? '0' ),
                                                'label'   => wp_kses(
                                                    __( 'Include <strong>ClickWhale Link Pages</strong>', 'clickwhale' ),
                                                    array(
                                                        'strong' => array()
                                                    )
                                                )
                                            )
                                        ),
                                        Helper::get_allowed_tags()
                                    );
                                    ?>
                                    <div class="cw-posts-row--select">
                                        <?php
                                        if ( isset( $clickwhale_item['position']['items_included']['cw_linkpage']['ids'] ) ) {
                                            $clickwhale_ids = array_map( 'sanitize_text_field', $clickwhale_item['position']['items_included']['cw_linkpage']['ids'] );
                                        } else {
                                            $clickwhale_ids = array();
                                        }

                                        echo wp_kses(
                                            Helper::render_control(
                                                array(
                                                    'control'  => 'select',
                                                    'id'       => 'position_include_linkpage_ids',
                                                    'class'    => 'with-select2',
                                                    'name'     => 'position[items_included][cw_linkpage][ids][]',
                                                    'value'    => array_map( 'intval', $clickwhale_ids ),
                                                    'options'  => $clickwhale_tracking_code->get_linkpages(),
                                                    'default'  => 'all',
                                                    'multiple' => true
                                                )
                                            ),
                                            Helper::get_allowed_tags()
                                        );
                                        ?>
                                    </div>
                                </div>
                                <div class="cw-posts-row cw-posts-row--excluded">
                                    <?php
                                    echo wp_kses(
                                        Helper::render_control(
                                            array(
                                                'control' => 'checkbox',
                                                'id'      => 'position_exclude_cw_linkpage',
                                                'name'    => 'position[items_excluded][cw_linkpage][active]',
                                                'value'   => esc_attr( $clickwhale_item['position']['items_excluded']['cw_linkpage']['active'] ?? '0' ),
                                                'label'   => wp_kses(
                                                    __( 'Exclude <strong>ClickWhale Link Pages</strong>', 'clickwhale' ),
                                                    array(
                                                        'strong' => array()
                                                    )
                                                )
                                            )
                                        ),
                                        Helper::get_allowed_tags()
                                    );
                                    ?>
                                    <div class="cw-posts-row--select">
                                        <?php
                                        if ( isset( $clickwhale_item['position']['items_excluded']['cw_linkpage']['ids'] ) ) {
                                            $clickwhale_ids = array_map( 'sanitize_text_field', $clickwhale_item['position']['items_excluded']['cw_linkpage']['ids'] );
                                        } else {
                                            $clickwhale_ids = array();
                                        }

                                        echo wp_kses(
                                            Helper::render_control(
                                                array(
                                                    'control'  => 'select',
                                                    'id'       => 'position_exclude_linkpage_ids',
                                                    'class'    => 'with-select2',
                                                    'name'     => 'position[items_excluded][cw_linkpage][ids][]',
                                                    'value'    => array_map( 'intval', $clickwhale_ids ),
                                                    'options'  => $clickwhale_tracking_code->get_linkpages(),
                                                    'default'  => 'all',
                                                    'multiple' => true
                                                )
                                            ),
                                            Helper::get_allowed_tags()
                                        );
                                        ?>
                                    </div>
                                </div>
                            <?php }

                            foreach ( $clickwhale_post_types as $post_type => $clickwhale_post_label ) {
                                ?>
                                <div class="cw-posts-row cw-posts-row--included">
                                    <?php
                                    echo wp_kses(
                                        Helper::render_control(
                                            array(
                                                'control' => 'checkbox',
                                                'id'      => 'position_include_' . esc_attr( $post_type ),
                                                'name'    => 'position[items_included][' . esc_attr( $post_type ) . '][active]',
                                                'value'   => esc_attr( $clickwhale_item['position']['items_included'][$post_type]['active'] ?? '0' ),
                                                'label'   =>  wp_kses(
                                                    sprintf(
                                                        /* translators: %s: post type label */
                                                        __( 'Include <strong>%s</strong>', 'clickwhale' ),
                                                        esc_html( $clickwhale_post_label )
                                                    ),
                                                    array(
                                                        'strong' => array()
                                                    )
                                                )
                                            )
                                        ),
                                        Helper::get_allowed_tags()
                                    );
                                    ?>
                                    <div class="cw-posts-row--select">
                                        <?php
                                        if ( isset( $clickwhale_item['position']['items_included'][$post_type]['ids'] ) ) {
                                            $clickwhale_ids = array_map( 'sanitize_text_field', $clickwhale_item['position']['items_included'][$post_type]['ids'] );
                                        } else {
                                            $clickwhale_ids = array();
                                        }

                                        echo wp_kses(
                                            Helper::render_control(
                                                array(
                                                    'control'  => 'select',
                                                    'id'       => 'position_include_' . esc_attr( $post_type ) . '_ids',
                                                    'class'    => 'with-select2',
                                                    'name'     => 'position[items_included][' . esc_attr( $post_type ) . '][ids][]',
                                                    'value'    => array_map( 'intval', $clickwhale_ids ),
                                                    'options'  => $clickwhale_tracking_code::get_posts_by_post_type( $post_type ),
                                                    'default'  => 'all',
                                                    'multiple' => true
                                                )
                                            ),
                                            Helper::get_allowed_tags()
                                        );
                                        ?>
                                    </div>
                                </div>
                                <div class="cw-posts-row cw-posts-row--excluded">
                                    <?php
                                    echo wp_kses(
                                        Helper::render_control(
                                            array(
                                                'control' => 'checkbox',
                                                'id'      => 'position_exclude_' . esc_attr( $post_type ),
                                                'name'    => 'position[items_excluded][' . esc_attr( $post_type ) . '][active]',
                                                'value'   => esc_attr( $clickwhale_item['position']['items_excluded'][$post_type]['active'] ?? '0' ),
                                                'label'   => wp_kses(
                                                    sprintf(
                                                        /* translators: %s: post type label */
                                                        __( 'Exclude <strong>%s</strong>', 'clickwhale' ),
                                                        esc_html( $clickwhale_post_label )
                                                    ),
                                                    array(
                                                        'strong' => array()
                                                    )
                                                )
                                            )
                                        ),
                                        Helper::get_allowed_tags()
                                    );
                                    ?>
                                    <div class="cw-posts-row--select">
                                        <?php
                                        if ( isset( $clickwhale_item['position']['items_excluded'][$post_type]['ids'] ) ) {
                                            $clickwhale_ids = array_map( 'sanitize_text_field', $clickwhale_item['position']['items_excluded'][$post_type]['ids'] );
                                        } else {
                                            $clickwhale_ids = array();
                                        }

                                        echo wp_kses(
                                            Helper::render_control(
                                                array(
                                                    'control'  => 'select',
                                                    'id'       => 'position_exclude_' . esc_attr( $post_type ) . '_ids',
                                                    'class'    => 'with-select2',
                                                    'name'     => 'position[items_excluded][' . esc_attr( $post_type ) . '][ids][]',
                                                    'value'    => array_map( 'intval', $clickwhale_ids ),
                                                    'options'  => $clickwhale_tracking_code::get_posts_by_post_type( $post_type ),
                                                    'default'  => 'all',
                                                    'multiple' => true
                                                )
                                            ),
                                            Helper::get_allowed_tags()
                                        );
                                        ?>
                                    </div>
                                </div>
                            <?php }

                            foreach ( $clickwhale_taxonomies as $taxonomy ) {
                                $clickwhale_taxonomy_object = get_taxonomy( $taxonomy );
                                if ( $clickwhale_taxonomy_object ) {
                                    ?>
                                    <div class="cw-posts-row cw-posts-row--included">
                                        <?php
                                        echo wp_kses(
                                            Helper::render_control(
                                                array(
                                                    'control' => 'checkbox',
                                                    'id'      => 'position_include_' . esc_attr( $taxonomy ),
                                                    'name'    => 'position[items_included][' . esc_attr( $taxonomy ) . '][active]',
                                                    'value'   => esc_attr( $clickwhale_item['position']['items_included'][$taxonomy]['active'] ?? '0' ),
                                                    'label'   => wp_kses(
                                                        sprintf(
                                                            /* translators: %s: taxonomy label */
                                                            __( 'Include <strong>%s</strong>', 'clickwhale' ),
                                                            esc_html( $clickwhale_taxonomy_object->label )
                                                        ),
                                                        array(
                                                            'strong' => array()
                                                        )
                                                    )
                                                )
                                            ),
                                            Helper::get_allowed_tags()
                                        );
                                        ?>
                                        <div class="cw-posts-row--select">
                                            <?php
                                            if ( isset( $clickwhale_item['position']['items_included'][$taxonomy]['ids'] ) ) {
                                                $clickwhale_ids = array_map( 'sanitize_text_field', $clickwhale_item['position']['items_included'][$taxonomy]['ids'] );
                                            } else {
                                                $clickwhale_ids = array();
                                            }

                                            echo wp_kses(
                                                Helper::render_control(
                                                    array(
                                                        'control'  => 'select',
                                                        'id'       => 'position_include_' . esc_attr( $taxonomy ) . '_ids',
                                                        'class'    => 'with-select2',
                                                        'name'     => 'position[items_included][' . esc_attr( $taxonomy ) . '][ids][]',
                                                        'value'    => array_map( 'intval', $clickwhale_ids ),
                                                        'options'  => $clickwhale_tracking_code->get_terms_by_tax( $taxonomy ),
                                                        'default'  => 'all',
                                                        'multiple' => true
                                                    )
                                                ),
                                                Helper::get_allowed_tags()
                                            );
                                            ?>
                                        </div>
                                    </div>
                                    <div class="cw-posts-row cw-posts-row--excluded">
                                        <?php
                                        echo wp_kses(
                                            Helper::render_control(
                                                array(
                                                    'control' => 'checkbox',
                                                    'id'      => 'position_exclude_' . esc_attr( $taxonomy ),
                                                    'name'    => 'position[items_excluded][' . esc_attr( $taxonomy ) . '][active]',
                                                    'value'   => esc_attr( $clickwhale_item['position']['items_excluded'][$taxonomy]['active'] ?? '0' ),
                                                    'label'   => wp_kses(
                                                        sprintf(
                                                            /* translators: %s: taxonomy label */
                                                            __( 'Exclude <strong>%s</strong>', 'clickwhale' ),
                                                            esc_html( $clickwhale_taxonomy_object->label )
                                                        ),
                                                        array(
                                                            'strong' => array()
                                                        )
                                                    )
                                                )
                                            ),
                                            Helper::get_allowed_tags()
                                        );
                                        ?>
                                        <div class="cw-posts-row--select">
                                            <?php
                                            if ( isset( $clickwhale_item['position']['items_excluded'][$taxonomy]['ids'] ) ) {
                                                $clickwhale_ids = array_map( 'sanitize_text_field', $clickwhale_item['position']['items_excluded'][$taxonomy]['ids'] );
                                            } else {
                                                $clickwhale_ids = array();
                                            }

                                            echo wp_kses(
                                                Helper::render_control(
                                                    array(
                                                        'control'  => 'select',
                                                        'id'       => 'position_exclude_' . esc_attr( $taxonomy ) . '_ids',
                                                        'class'    => 'with-select2',
                                                        'name'     => 'position[items_excluded][' . esc_attr( $taxonomy ) . '][ids][]',
                                                        'value'    => array_map( 'intval', $clickwhale_ids ),
                                                        'options'  => $clickwhale_tracking_code->get_terms_by_tax( $taxonomy ),
                                                        'default'  => 'all',
                                                        'multiple' => true
                                                    )
                                                ),
                                                Helper::get_allowed_tags()
                                            );
                                            ?>
                                        </div>
                                    </div>
                                <?php }
                            } ?>
                        </td>
                    </tr>

                    <?php
                    if ( isset( $clickwhale_item['position']['exclude_user_by_role'] ) ) {
                        $clickwhale_ids = array_map( 'sanitize_text_field', $clickwhale_item['position']['exclude_user_by_role'] );
                    } else {
                        $clickwhale_ids = array();
                    }

                    echo wp_kses(
                        Helper::render_control(
                            array(
                                'row_label'   => esc_html__( 'Exclude User Roles', 'clickwhale' ),
                                'control'     => 'checkboxes',
                                'id'          => 'position_exclude_user_by_role',
                                'name'        => 'position[exclude_user_by_role][]',
                                'value'       => array_map( 'intval', $clickwhale_ids ),
                                'options'     => array_map( 'esc_html', clickwhale()->user->get_all_roles() ),
                                'description' => esc_html__( 'Check the user roles for which the script should not be executed.', 'clickwhale' )
                            ),
                            true
                        ),
                        Helper::get_allowed_tags()
                    );

                    echo wp_kses(
                        Helper::render_control(
                            array(
                                'row_label'   => esc_html__( 'Description', 'clickwhale' ),
                                'control'     => 'textarea',
                                'id'          => 'description',
                                'name'        => 'description',
                                'value'       => esc_html( wp_unslash( $clickwhale_item['description'] ) ),
                                'placeholder' => esc_attr__( 'Your comment here', 'clickwhale' ),
                                'description' => esc_html__( 'Optional comment to the tracking code.', 'clickwhale' )
                            ),
                            true
                        ),
                        Helper::get_allowed_tags()
                    );

                    echo wp_kses(
                        Helper::render_control(
                            array(
                                'row_label'        => esc_html__( 'Active', 'clickwhale' ),
                                'control'          => 'checkbox',
                                'id'               => 'is_active',
                                'name'             => 'is_active',
                                'class'            => 'clickwhale_tc_active_toggle',
                                'value'            => intval( $clickwhale_item['is_active'] ),
                                'label'            => esc_html__( 'Enable Tracking Code', 'clickwhale' ),
                                'disabled'         => Tracking_Codes_Helper::is_active_limit(),
                                'disabled_message' => esc_html( Tracking_Codes_Helper::get_limitation_notice() ),
                            ),
                            true
                        ),
                        Helper::get_allowed_tags()
                    );
                    ?>
                </tbody>
            </table>

            <input type="hidden"
                   id="created_at"
                   name="created_at"
                   value="<?php echo esc_attr( $clickwhale_item['created_at'] ); ?>"
            />
            <input type="hidden" id="updated_at" name="updated_at" value="" />
            <input type="submit"
                   value="<?php esc_attr_e( 'Save', 'clickwhale' ); ?>"
                   id="submit"
                   class="button-primary"
                   name="submit"
            />
        </div>
    </form>

    <?php do_action( 'clickwhale_admin_sidebar_end' ); ?>

</div>
