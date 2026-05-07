<?php

use Clickwhale\Helpers\{
    Helper,
    Links_Helper,
    Categories_Helper
};

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$clickwhale_get_id = (int) filter_input( INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT );
Links_Helper::get_limitation_error( $clickwhale_get_id );

$clickwhale_link = clickwhale()->link;
$clickwhale_item = $clickwhale_link->get_item( array( 'id' => $clickwhale_get_id ) );
$clickwhale_item_id = intval( $clickwhale_item['id'] );
$clickwhale_item_categories = Categories_Helper::get_all();
$clickwhale_tabs = $clickwhale_link->render_tabs();

// Slug
if ( $clickwhale_item['slug'] ) {
    $clickwhale_slug = $clickwhale_item['slug'];
} else {
    $clickwhale_random_slug = ! Helper::get_clickwhale_option( 'link_manager', 'random_slug' )
        ? Links_Helper::generate_random_slug()
        : '';

    $clickwhale_slug = Helper::get_clickwhale_option( 'link_manager', 'slug' ) ?
        trailingslashit( Helper::get_clickwhale_option( 'link_manager', 'slug' ) ) . $clickwhale_random_slug
        : $clickwhale_random_slug;
}

// Link target
$clickwhale_link_targets = array_merge(
    array( '' => __( 'Default', 'clickwhale' ) ),
    Links_Helper::get_link_targets()
);

do_action( 'clickwhale_admin_banner' );
?>
<div class="wrap">
    <?php
    echo wp_kses(
        Helper::render_heading(
            array(
                'name'         => esc_html__( 'Link', 'clickwhale' ),
                'is_edit'      => $clickwhale_item_id !== 0,
                'link_to_list' => esc_attr( CLICKWHALE_SLUG ),
                'link_to_add'  => esc_attr( CLICKWHALE_SLUG ) . '-edit-link'
            )
        ),
        Helper::get_allowed_tags()
    );

    $clickwhale_link->show_message( intval( $clickwhale_item_id ) );
    ?>

    <?php do_action( 'clickwhale_admin_sidebar_begin' ); ?>

    <form id="form_edit_<?php echo esc_attr( $clickwhale_link->instance_single ); ?>"
          class="clickwhale_form_edit"
          method="POST"
          action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
    >
        <input type="hidden" name="action" value="save_update_clickwhale_<?php echo esc_attr( $clickwhale_link->instance_single ); ?>" />
        <input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce( basename( __FILE__ ) ) ); ?>" />
        <input type="hidden" name="id" value="<?php echo esc_attr( $clickwhale_item_id ); ?>" />

        <div id="post-body-content">
            <div id="clickwhale-tabs" class="clickwhale-tabs">
                <?php if ( $clickwhale_tabs ) {
                    ?>
                    <ul>
                        <?php foreach ( $clickwhale_tabs as $clickwhale_tab ) { ?>
                            <li>
                                <a href="#link-tab-<?php echo esc_attr( $clickwhale_tab['url'] ); ?>"><?php echo esc_html( $clickwhale_tab['name'] ); ?></a>
                            </li>
                        <?php } ?>
                    </ul>
                <?php } ?>

                <div id="link-tab-general">
                    <?php do_action( 'clickwhale_link_before_general_tab_content', $clickwhale_item_id, $clickwhale_slug ); ?>

                    <table style="width: 100%;" class="form-table">
                        <caption hidden><?php esc_html_e( 'Link Main Settings', 'clickwhale' ); ?></caption>
                        <tbody>
                            <tr class="form-field">
                                <th scope="row">
                                    <label for="title"><?php esc_html_e( 'Title', 'clickwhale' ); ?></label>
                                </th>
                                <td>
                                    <?php
                                    echo wp_kses(
                                        Helper::render_control(
                                            array(
                                                'control'     => 'input',
                                                'id'          => 'title',
                                                'name'        => 'title',
                                                'type'        => 'text',
                                                'value'       => esc_attr( wp_unslash( $clickwhale_item['title'] ) ),
                                                'placeholder' => esc_html__( 'Link Title', 'clickwhale' ),
                                                'required'    => false // we validate title in Clickwhale_Link_Edit::admin_scripts()
                                            )
                                        ),
                                        Helper::get_allowed_tags()
                                    );
                                    ?>
                                    <p id="cw-title--description"></p>
                                </td>
                            </tr>
                            <tr class="form-field">
                                <th scope="row">
                                    <label for="cw-slug"><?php esc_html_e( 'Slug', 'clickwhale' ); ?></label>
                                </th>
                                <td>
                                    <?php
                                    echo wp_kses(
                                        Helper::render_control(
                                            array(
                                                'control'     => 'input',
                                                'id'          => 'cw-slug',
                                                'name'        => 'slug',
                                                'type'        => 'text',
                                                'value'       => esc_attr( $clickwhale_slug ),
                                                'placeholder' => esc_html__( 'e.g. my-link', 'clickwhale' ),
                                                'required'    => false // we validate slug in Clickwhale_Link_Edit::admin_scripts()
                                            )
                                        ),
                                        Helper::get_allowed_tags()
                                    );
                                    ?>
                                    <p id="cw-slug--description"></p>
                                    <p id="cw-slug--text"
                                       class="code"
                                       title="<?php esc_attr_e( 'Copy url', 'clickwhale' ); ?>"
                                    ><?php
                                        echo esc_html__( 'URL Preview', 'clickwhale' ) . ': ' . esc_html( trailingslashit( home_url() ) );
                                        ?><span><?php echo ( $clickwhale_slug ) ? esc_html( trailingslashit( $clickwhale_slug ) ) : ''; ?></span><svg class="feather"><use href="<?php echo esc_url( CLICKWHALE_ADMIN_ASSETS_DIR ); ?>/images/feather-sprite.svg#copy"></use></svg>
                                    </p>
                                </td>
                            </tr>
                            <tr class="form-field">
                                <th scope="row">
                                    <label for="url"><?php esc_html_e( 'Target URL', 'clickwhale' ); ?></label>
                                </th>
                                <td>
                                    <?php
                                    echo wp_kses(
                                        Helper::render_control(
                                            array(
                                                'control'     => 'input',
                                                'id'          => 'url',
                                                'name'        => 'url',
                                                'type'        => 'url',
                                                'value'       => esc_url( $clickwhale_item['url'] ),
                                                'placeholder' => esc_html__( 'Link Target URL', 'clickwhale' ),
                                                'required'    => false // we validate url in Clickwhale_Link_Edit::admin_scripts()
                                            )
                                        ),
                                        Helper::get_allowed_tags()
                                    );
                                    ?>
                                    <p id="cw-url--description"></p>
                                </td>
                            </tr>
                            <?php
                            echo wp_kses(
                                Helper::render_control(
                                    array(
                                        'row_label' => esc_html__( 'Redirection Type', 'clickwhale' ),
                                        'control'   => 'select',
                                        'id'        => 'redirection',
                                        'name'      => 'redirection',
                                        'options'   => Links_Helper::get_redirections(),
                                        'value'     => esc_attr( $clickwhale_item['redirection'] )
                                    ),
                                    true
                                ),
                                Helper::get_allowed_tags()
                            );

                            echo wp_kses(
                                Helper::render_control(
                                    array(
                                        'row_label' => esc_html__( 'Link Target', 'clickwhale' ),
                                        'control'   => 'select',
                                        'id'        => 'link_target',
                                        'name'      => 'link_target',
                                        'options'   => $clickwhale_link_targets,
                                        'value'     => $clickwhale_item_id === 0 ? '' : esc_attr( $clickwhale_item['link_target'] )
                                    ),
                                    true
                                ),
                                Helper::get_allowed_tags()
                            );

                            echo wp_kses(
                                Helper::render_control(
                                    array(
                                        'row_label' => esc_html__( 'Nofollow', 'clickwhale' ),
                                        'control'   => 'checkbox',
                                        'id'        => 'nofollow',
                                        'name'      => 'nofollow',
                                        'value'     => $clickwhale_item_id === 0
                                                       && Helper::get_clickwhale_option( 'link_manager', 'nofollow' )
                                            ? 1
                                            : intval( $clickwhale_item['nofollow'] ),
                                        'label'     => esc_html__( 'Check to mark link as nofollow & noindex', 'clickwhale' )
                                    ),
                                    true
                                ),
                                Helper::get_allowed_tags()
                            );

                            echo wp_kses(
                                Helper::render_control(
                                    array(
                                        'row_label' => esc_html__( 'Sponsored', 'clickwhale' ),
                                        'control'   => 'checkbox',
                                        'id'        => 'sponsored',
                                        'name'      => 'sponsored',
                                        'value'     => $clickwhale_item_id === 0
                                                       && Helper::get_clickwhale_option( 'link_manager', 'sponsored' )
                                            ? 1
                                            : intval( $clickwhale_item['sponsored'] ),
                                        'label'     => esc_html__( 'Check to mark link as sponsored', 'clickwhale' )
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
                                        'placeholder' => esc_html__( 'Description', 'clickwhale' )
                                    ),
                                    true
                                ),
                                Helper::get_allowed_tags()
                            );

                            if ( $clickwhale_item_categories ) {
                                $clickwhale_categories_for_options = array();
                                $clickwhale_current_categories     = ! empty( $clickwhale_item['categories'] )
                                    ? explode( ',', sanitize_text_field( $clickwhale_item['categories'] ) )
                                    : array();

                                foreach ( $clickwhale_item_categories as $clickwhale_category ) {
                                    $clickwhale_categories_for_options[intval( $clickwhale_category->id )] = esc_html( $clickwhale_category->title );
                                }

                                echo wp_kses(
                                    Helper::render_control(
                                        array(
                                            'row_label' => esc_html__( 'Category', 'clickwhale' ),
                                            'control'   => 'checkboxes',
                                            'id'        => 'category',
                                            'name'      => 'categories[]',
                                            'options'   => $clickwhale_categories_for_options,
                                            'value'     => $clickwhale_current_categories
                                        ),
                                        true
                                    ),
                                    Helper::get_allowed_tags()
                                );
                            }
                            ?>
                        </tbody>
                    </table>

                    <?php do_action( 'clickwhale_link_after_general_tab_content', $clickwhale_item_id, $clickwhale_slug ); ?>
                </div>
                <?php do_action( 'clickwhale_link_after_tabs_content', $clickwhale_item ); ?>
            </div>
            <input type="hidden" id="created_at" name="created_at"
                   value="<?php echo esc_attr( $clickwhale_item['created_at'] ); ?>" />
            <input type="hidden" id="updated_at" name="updated_at" value="" />
            <input type="submit"
                   id="submit"
                   name="submit"
                   value="<?php esc_attr_e( 'Save link', 'clickwhale' ); ?>"
                   class="button-primary"
            >
            <button id="cw-copy-link-url"
                    type="button"
                    class="button"
            ><?php esc_html_e( 'Copy link', 'clickwhale' ); ?></button>
        </div>
    </form>
    <?php do_action( 'clickwhale_admin_sidebar_end' ); ?>
</div>
