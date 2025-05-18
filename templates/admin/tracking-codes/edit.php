<?php

use clickwhale\includes\helpers\{Helper, Tracking_Codes_Helper};

Tracking_Codes_Helper::get_limitation_error( $_GET['id'] );

$tracking_code = clickwhale()->tracking_code;

$item = $tracking_code->get_item( $_GET );
$item_id = intval( $item['id'] );
$linkpages = $tracking_code->get_linkpages();
$post_types = Tracking_Codes_Helper::get_default_post_types();
$taxonomies = Tracking_Codes_Helper::get_default_terms_tax();

do_action( 'clickwhale_admin_banner' );
?>
<div class="wrap">
    <?php
    echo Helper::render_heading(
        array(
            'name'         => __( 'Tracking Code', 'clickwhale' ),
            'is_edit'      => $item_id !== 0,
            'link_to_list' => CLICKWHALE_SLUG . '-tracking-codes',
            'link_to_add'  => CLICKWHALE_SLUG . '-edit-tracking-code'
        )
    );

    $tracking_code->show_message( $item_id );
    ?>

    <?php do_action( 'clickwhale_admin_sidebar_begin' ); ?>

    <form id="form_edit_<?php echo $tracking_code->instance_single; ?>"
          class="clickwhale_form_edit"
          method="POST"
          action="<?php echo esc_attr( admin_url( 'admin-post.php' ) ); ?>"
    >
        <input type="hidden" name="action" value="save_update_clickwhale_<?php echo $tracking_code->instance_single; ?>" />
        <input type="hidden" name="nonce" value="<?php echo wp_create_nonce( basename( __FILE__ ) ); ?>" />
        <input type="hidden" name="id" value="<?php echo $item_id; ?>" />

        <div id="post-body-content">
            <table style="width: 100%;" class="form-table">
                <caption style="display: none"><?php _e( 'Tracking Code Edit Table', 'clickwhale' ); ?></caption>
                <tbody>
                    <?php
                    echo Helper::render_control(
                        array(
                            'row_label'   => __( 'Title', 'clickwhale' ),
                            'control'     => 'input',
                            'id'          => 'title',
                            'name'        => 'title',
                            'type'        => 'text',
                            'value'       => esc_attr( wp_unslash( $item['title'] ) ),
                            'placeholder' => __( 'e.g. Google Tag Manager Code', 'clickwhale' ),
                            'required'    => true
                        ),
                        true
                    );

                    /** @link https://www.ibenic.com/wordpress-code-editor/ */
                    echo Helper::render_control(
                        array(
                            'row_label'   => __( 'Code', 'clickwhale' ),
                            'control'     => 'textarea',
                            'id'          => 'code',
                            'name'        => 'code',
                            'value'       => esc_textarea( wp_unslash( $item['code'] ) ),
                            'description' => __( 'Paste your code here.', 'clickwhale' )
                        ),
                        true
                    );

                    $tracking_code->conversion_fields( $item );

                    echo Helper::render_control(
                        array(
                            'row_label' => __( 'Code Position', 'clickwhale' ),
                            'control'   => 'select',
                            'id'        => 'position_code',
                            'name'      => 'position[code]',
                            'value'     => esc_attr( $item['position']['code'] ?? '' ),
                            'options'   => array(
                                'wp_head'      => 'Before &lt;/head&gt;',
                                'wp_body_open' => 'After &lt;body&gt;',
                                'wp_footer'    => 'Before &lt;/body&gt;'
                            )
                        ),
                        true,
                        'for_mode for_standard_mode'
                    );
                    ?>

                    <tr class="form-field for_mode for_standard_mode">
                        <th scope="row">
                            <label for="position"><?php _e( 'In which page do you want to insert this code?', 'clickwhale' ); ?></label>
                        </th>
                        <td>
                            <?php
                            echo Helper::render_control(
                                array(
                                    'control' => 'radio',
                                    'id'      => 'position_pages',
                                    'name'    => 'position[pages]',
                                    'value'   => esc_attr( $item['position']['pages'] ?? '' ),
                                    'options' => array(
                                        'all'    => __( 'Whole website', 'clickwhale' ),
                                        'custom' => __( 'Specific page', 'clickwhale' )
                                    ),
                                    'default' => 'all'
                                )
                            );
                            ?>

                            <br>

                            <?php if ( $linkpages ) { ?>
                                <div class="cw-posts-row cw-posts-row--included">
                                    <?php
                                    echo Helper::render_control(
                                        array(
                                            'control' => 'checkbox',
                                            'id'      => 'position_include_cw_linkpage',
                                            'name'    => 'position[items_included][cw_linkpage][active]',
                                            'value'   => esc_attr( $item['position']['items_included']['cw_linkpage']['active'] ?? '0' ),
                                            'label'   => wp_kses(
                                                __( 'Include <strong>ClickWhale Link Pages</strong>', 'clickwhale' ),
                                                array(
                                                    'strong' => array()
                                                )
                                            )
                                        )
                                    );
                                    ?>
                                    <div class="cw-posts-row--select">
                                        <?php
                                        if ( isset( $item['position']['items_included']['cw_linkpage']['ids'] ) ) {
                                            $ids = array_map( 'sanitize_text_field', $item['position']['items_included']['cw_linkpage']['ids'] );
                                        } else {
                                            $ids = array();
                                        }

                                        echo Helper::render_control(
                                            array(
                                                'control'  => 'select',
                                                'id'       => 'position_include_linkpage_ids',
                                                'class'    => 'with-select2',
                                                'name'     => 'position[items_included][cw_linkpage][ids][]',
                                                'value'    => $ids,
                                                'options'  => $tracking_code->get_linkpages(),
                                                'default'  => 'all',
                                                'multiple' => true
                                            )
                                        );
                                        ?>
                                    </div>
                                </div>
                                <div class="cw-posts-row cw-posts-row--excluded">
                                    <?php
                                    echo Helper::render_control(
                                        array(
                                            'control' => 'checkbox',
                                            'id'      => 'position_exclude_cw_linkpage',
                                            'name'    => 'position[items_excluded][cw_linkpage][active]',
                                            'value'   => esc_attr( $item['position']['items_excluded']['cw_linkpage']['active'] ?? '0' ),
                                            'label'   => wp_kses(
                                                __( 'Exclude <strong>ClickWhale Link Pages</strong>', 'clickwhale' ),
                                                array(
                                                    'strong' => array()
                                                )
                                            )
                                        )
                                    );
                                    ?>
                                    <div class="cw-posts-row--select">
                                        <?php
                                        if ( isset( $item['position']['items_excluded']['cw_linkpage']['ids'] ) ) {
                                            $ids = array_map( 'sanitize_text_field', $item['position']['items_excluded']['cw_linkpage']['ids'] );
                                        } else {
                                            $ids = array();
                                        }

                                        echo Helper::render_control(
                                            array(
                                                'control'  => 'select',
                                                'id'       => 'position_exclude_linkpage_ids',
                                                'class'    => 'with-select2',
                                                'name'     => 'position[items_excluded][cw_linkpage][ids][]',
                                                'value'    => $ids,
                                                'options'  => $tracking_code->get_linkpages(),
                                                'default'  => 'all',
                                                'multiple' => true
                                            )
                                        );
                                        ?>
                                    </div>
                                </div>
                            <?php }

                            foreach ( $post_types as $post_type => $post_label ) {
                                ?>
                                <div class="cw-posts-row cw-posts-row--included">
                                    <?php
                                    echo Helper::render_control(
                                        array(
                                            'control' => 'checkbox',
                                            'id'      => 'position_include_' . esc_attr( $post_type ),
                                            'name'    => 'position[items_included][' . esc_attr( $post_type ) . '][active]',
                                            'value'   => esc_attr( $item['position']['items_included'][$post_type]['active'] ?? '0' ),
                                            'label'   =>  wp_kses(
                                                sprintf(
                                                    __( 'Include <strong>%s</strong>', 'clickwhale' ),
                                                    esc_html( $post_label )
                                                ),
                                                array(
                                                    'strong' => array()
                                                )
                                            )
                                        )
                                    );
                                    ?>
                                    <div class="cw-posts-row--select">
                                        <?php
                                        if ( isset( $item['position']['items_included'][$post_type]['ids'] ) ) {
                                            $ids = array_map( 'sanitize_text_field', $item['position']['items_included'][$post_type]['ids'] );
                                        } else {
                                            $ids = array();
                                        }

                                        echo Helper::render_control(
                                            array(
                                                'control'  => 'select',
                                                'id'       => 'position_include_' . esc_attr( $post_type ) . '_ids',
                                                'class'    => 'with-select2',
                                                'name'     => 'position[items_included][' . esc_attr( $post_type ) . '][ids][]',
                                                'value'    => $ids,
                                                'options'  => $tracking_code::get_posts_by_post_type( $post_type ),
                                                'default'  => 'all',
                                                'multiple' => true
                                            )
                                        );
                                        ?>
                                    </div>
                                </div>
                                <div class="cw-posts-row cw-posts-row--excluded">
                                    <?php
                                    echo Helper::render_control(
                                        array(
                                            'control' => 'checkbox',
                                            'id'      => 'position_exclude_' . esc_attr( $post_type ),
                                            'name'    => 'position[items_excluded][' . esc_attr( $post_type ) . '][active]',
                                            'value'   => esc_attr( $item['position']['items_excluded'][$post_type]['active'] ?? '0' ),
                                            'label'   => wp_kses(
                                                sprintf(
                                                    __( 'Exclude <strong>%s</strong>', 'clickwhale' ),
                                                    esc_html( $post_label )
                                                ),
                                                array(
                                                    'strong' => array()
                                                )
                                            )
                                        )
                                    );
                                    ?>
                                    <div class="cw-posts-row--select">
                                        <?php
                                        if ( isset( $item['position']['items_excluded'][$post_type]['ids'] ) ) {
                                            $ids = array_map( 'sanitize_text_field', $item['position']['items_excluded'][$post_type]['ids'] );
                                        } else {
                                            $ids = array();
                                        }

                                        echo Helper::render_control(
                                            array(
                                                'control'  => 'select',
                                                'id'       => 'position_exclude_' . esc_attr( $post_type ) . '_ids',
                                                'class'    => 'with-select2',
                                                'name'     => 'position[items_excluded][' . esc_attr( $post_type ) . '][ids][]',
                                                'value'    => $ids,
                                                'options'  => $tracking_code::get_posts_by_post_type( $post_type ),
                                                'default'  => 'all',
                                                'multiple' => true
                                            )
                                        );
                                        ?>
                                    </div>
                                </div>
                            <?php }

                            foreach ( $taxonomies as $taxonomy ) {
                                $taxonomy_object = get_taxonomy( $taxonomy );
                                if ( $taxonomy_object ) {
                                    ?>
                                    <div class="cw-posts-row cw-posts-row--included">
                                        <?php
                                        echo Helper::render_control(
                                            array(
                                                'control' => 'checkbox',
                                                'id'      => 'position_include_' . esc_attr( $taxonomy ),
                                                'name'    => 'position[items_included][' . esc_attr( $taxonomy ) . '][active]',
                                                'value'   => esc_attr( $item['position']['items_included'][$taxonomy]['active'] ?? '0' ),
                                                'label'   => wp_kses(
                                                    sprintf(
                                                        __( 'Include <strong>%s</strong>', 'clickwhale' ),
                                                        esc_html( $taxonomy_object->label )
                                                    ),
                                                    array(
                                                        'strong' => array()
                                                    )
                                                )
                                            )
                                        );
                                        ?>
                                        <div class="cw-posts-row--select">
                                            <?php
                                            if ( isset( $item['position']['items_included'][$taxonomy]['ids'] ) ) {
                                                $ids = array_map( 'sanitize_text_field', $item['position']['items_included'][$taxonomy]['ids'] );
                                            } else {
                                                $ids = array();
                                            }

                                            echo Helper::render_control(
                                                array(
                                                    'control'  => 'select',
                                                    'id'       => 'position_include_' . esc_attr( $taxonomy ) . '_ids',
                                                    'class'    => 'with-select2',
                                                    'name'     => 'position[items_included][' . esc_attr( $taxonomy ) . '][ids][]',
                                                    'value'    => $ids,
                                                    'options'  => $tracking_code->get_terms_by_tax( $taxonomy ),
                                                    'default'  => 'all',
                                                    'multiple' => true
                                                )
                                            );
                                            ?>
                                        </div>
                                    </div>
                                    <div class="cw-posts-row cw-posts-row--excluded">
                                        <?php
                                        echo Helper::render_control(
                                            array(
                                                'control' => 'checkbox',
                                                'id'      => 'position_exclude_' . esc_attr( $taxonomy ),
                                                'name'    => 'position[items_excluded][' . esc_attr( $taxonomy ) . '][active]',
                                                'value'   => esc_attr( $item['position']['items_excluded'][$taxonomy]['active'] ?? '0' ),
                                                'label'   => wp_kses(
                                                    sprintf(
                                                        __( 'Exclude <strong>%s</strong>', 'clickwhale' ),
                                                        esc_html( $taxonomy_object->label )
                                                    ),
                                                    array(
                                                        'strong' => array()
                                                    )
                                                )
                                            )
                                        );
                                        ?>
                                        <div class="cw-posts-row--select">
                                            <?php
                                            if ( isset( $item['position']['items_excluded'][$taxonomy]['ids'] ) ) {
                                                $ids = array_map( 'sanitize_text_field', $item['position']['items_excluded'][$taxonomy]['ids'] );
                                            } else {
                                                $ids = array();
                                            }

                                            echo Helper::render_control(
                                                array(
                                                    'control'  => 'select',
                                                    'id'       => 'position_exclude_' . esc_attr( $taxonomy ) . '_ids',
                                                    'class'    => 'with-select2',
                                                    'name'     => 'position[items_excluded][' . esc_attr( $taxonomy ) . '][ids][]',
                                                    'value'    => $ids,
                                                    'options'  => $tracking_code->get_terms_by_tax( $taxonomy ),
                                                    'default'  => 'all',
                                                    'multiple' => true
                                                )
                                            );
                                            ?>
                                        </div>
                                    </div>
                                <?php }
                            } ?>
                        </td>
                    </tr>

                    <?php
                    if ( isset( $item['position']['exclude_user_by_role'] ) ) {
                        $ids = array_map( 'sanitize_text_field', $item['position']['exclude_user_by_role'] );
                    } else {
                        $ids = array();
                    }

                    echo Helper::render_control(
                        array(
                            'row_label'   => __( 'Exclude User Roles', 'clickwhale' ),
                            'control'     => 'checkboxes',
                            'id'          => 'position_exclude_user_by_role',
                            'name'        => 'position[exclude_user_by_role][]',
                            'value'       => $ids,
                            'options'     => clickwhale()->user->get_all_roles(),
                            'description' => __( 'Check the user roles for which the script should not be executed.', 'clickwhale' )
                        ),
                        true
                    );

                    echo Helper::render_control(
                        array(
                            'row_label'   => __( 'Description', 'clickwhale' ),
                            'control'     => 'textarea',
                            'id'          => 'description',
                            'name'        => 'description',
                            'value'       => esc_html( wp_unslash( $item['description'] ) ),
                            'placeholder' => __( 'Your comment here', 'clickwhale' ),
                            'description' => __( 'Optional comment to the tracking code.', 'clickwhale' )
                        ),
                        true
                    );

                    echo Helper::render_control(
                        array(
                            'row_label'        => __( 'Active', 'clickwhale' ),
                            'control'          => 'checkbox',
                            'id'               => 'is_active',
                            'name'             => 'is_active',
                            'class'            => 'clickwhale_tc_active_toggle',
                            'value'            => esc_attr( $item['is_active'] ),
                            'label'            => __( 'Enable Tracking Code', 'clickwhale' ),
                            'disabled'         => Tracking_Codes_Helper::is_active_limit(),
                            'disabled_message' => Tracking_Codes_Helper::get_limitation_notice()
                        ),
                        true
                    );
                    ?>
                </tbody>
            </table>

            <input type="hidden"
                   id="created_at"
                   name="created_at"
                   value="<?php echo esc_attr( $item['created_at'] ); ?>"
            />
            <input type="hidden" id="updated_at" name="updated_at" value="" />
            <input type="submit"
                   value="<?php _e( 'Save', 'clickwhale' ); ?>"
                   id="submit"
                   class="button-primary"
                   name="submit"
            />
        </div>
    </form>

    <?php do_action( 'clickwhale_admin_sidebar_end' ); ?>

</div>
