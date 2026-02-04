<?php

use clickwhale\includes\helpers\{
    Helper,
    Links_Helper,
    Categories_Helper
};

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$id = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;
Links_Helper::get_limitation_error( $id );

$link = clickwhale()->link;
$item = $link->get_item( $_GET );
$item_id = intval( $item['id'] );
$item_categories = Categories_Helper::get_all();
$tabs = $link->render_tabs();

// Slug
if ( $item['slug'] ) {
    $slug = $item['slug'];
} else {
    $random_slug = ! Helper::get_clickwhale_option( 'link_manager', 'random_slug' )
        ? Links_Helper::generate_random_slug()
        : '';

    $slug = Helper::get_clickwhale_option( 'link_manager', 'slug' ) ?
        trailingslashit( Helper::get_clickwhale_option( 'link_manager', 'slug' ) ) . $random_slug
        : $random_slug;
}

// Link target
$link_targets = array_merge(
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
                'is_edit'      => $item_id !== 0,
                'link_to_list' => esc_attr( CLICKWHALE_SLUG ),
                'link_to_add'  => esc_attr( CLICKWHALE_SLUG ) . '-edit-link'
            )
        ),
        Helper::get_allowed_tags()
    );

    $link->show_message( intval( $item_id ) );
    ?>

    <?php do_action( 'clickwhale_admin_sidebar_begin' ); ?>

    <form id="form_edit_<?php echo esc_attr( $link->instance_single ); ?>"
          class="clickwhale_form_edit"
          method="POST"
          action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
    >
        <input type="hidden" name="action" value="save_update_clickwhale_<?php echo esc_attr( $link->instance_single ); ?>" />
        <input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce( basename( __FILE__ ) ) ); ?>" />
        <input type="hidden" name="id" value="<?php echo esc_attr( $item_id ); ?>" />

        <div id="post-body-content">
            <div id="clickwhale-tabs" class="clickwhale-tabs">
                <?php if ( $tabs ) {
                    ?>
                    <ul>
                        <?php foreach ( $tabs as $tab ) { ?>
                            <li>
                                <a href="#link-tab-<?php echo esc_attr( $tab['url'] ); ?>"><?php echo esc_html( $tab['name'] ); ?></a>
                            </li>
                        <?php } ?>
                    </ul>
                <?php } ?>

                <div id="link-tab-general">
                    <?php do_action( 'clickwhale_link_before_general_tab_content', $item_id, $slug ); ?>

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
                                                'value'       => esc_attr( wp_unslash( $item['title'] ) ),
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
                                                'value'       => esc_attr( $slug ),
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
                                        ?><span><?php echo ( $slug ) ? esc_html( trailingslashit( $slug ) ) : ''; ?></span><svg class="feather"><use href="<?php echo esc_url( CLICKWHALE_ADMIN_ASSETS_DIR ); ?>/images/feather-sprite.svg#copy"></use></svg>
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
                                                'value'       => esc_url( $item['url'] ),
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
                                        'value'     => esc_attr( $item['redirection'] )
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
                                        'options'   => $link_targets,
                                        'value'     => $item_id === 0 ? '' : esc_attr( $item['link_target'] )
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
                                        'value'     => $item_id === 0
                                                       && Helper::get_clickwhale_option( 'link_manager', 'nofollow' )
                                            ? 1
                                            : intval( $item['nofollow'] ),
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
                                        'value'     => $item_id === 0
                                                       && Helper::get_clickwhale_option( 'link_manager', 'sponsored' )
                                            ? 1
                                            : intval( $item['sponsored'] ),
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
                                        'value'       => esc_html( wp_unslash( $item['description'] ) ),
                                        'placeholder' => esc_html__( 'Description', 'clickwhale' )
                                    ),
                                    true
                                ),
                                Helper::get_allowed_tags()
                            );

                            if ( $item_categories ) {
                                $categories_for_options = array();
                                $current_categories     = ! empty( $item['categories'] )
                                    ? explode( ',', sanitize_text_field( $item['categories'] ) )
                                    : array();

                                foreach ( $item_categories as $category ) {
                                    $categories_for_options[intval( $category->id )] = esc_html( $category->title );
                                }

                                echo wp_kses(
                                    Helper::render_control(
                                        array(
                                            'row_label' => esc_html__( 'Category', 'clickwhale' ),
                                            'control'   => 'checkboxes',
                                            'id'        => 'category',
                                            'name'      => 'categories[]',
                                            'options'   => $categories_for_options,
                                            'value'     => $current_categories
                                        ),
                                        true
                                    ),
                                    Helper::get_allowed_tags()
                                );
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
                <?php do_action( 'clickwhale_link_after_tabs_content', $item ); ?>
            </div>
            <input type="hidden" id="created_at" name="created_at"
                   value="<?php echo esc_attr( $item['created_at'] ); ?>" />
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
