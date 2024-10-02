<?php

use clickwhale\includes\helpers\{
    Helper,
    Links_Helper,
    Categories_Helper
};

Links_Helper::get_limitation_error( $_GET['id'] );

$link = clickwhale()->link;
$item = $link->get_item( $_REQUEST );
$item_categories = Categories_Helper::get_all();
$tabs = $link->render_tabs();

// SLUG
if ( $item['slug'] ) {
	$slug = $item['slug'];
} else {
	$randomSlug = ! Helper::get_clickwhale_option( 'general', 'random_slug' )
		? Links_Helper::get_link_random_slug()
		: '';

	$slug = Helper::get_clickwhale_option( 'general', 'slug' ) ?
		trailingslashit( Helper::get_clickwhale_option( 'general', 'slug' ) ) . $randomSlug
		: $randomSlug;
}

do_action( 'clickwhale_admin_banner' );
?>
<div class="wrap">
	<?php
	echo Helper::render_heading(
		array(
			'name'         => __( 'Link', CLICKWHALE_NAME ),
			'is_edit'      => ! empty( $item['id'] ),
			'link_to_list' => CLICKWHALE_SLUG,
			'link_to_add'  => CLICKWHALE_SLUG . '-edit-link',
		)
	);

	$link->show_message( $item['id'] );
	?>

    <?php do_action( 'clickwhale_admin_sidebar_begin' ); ?>

    <form id="form_edit_<?php echo $link->instance_single ?>"
          class="clickwhale_form_edit"
          method="POST"
          action="<?php echo esc_attr( admin_url( 'admin-post.php' ) ); ?>">

        <input type="hidden" name="action" value="save_update_clickwhale_<?php echo $link->instance_single ?>">
        <input type="hidden" name="nonce" value="<?php echo wp_create_nonce( basename( __FILE__ ) ) ?>"/>
        <input type="hidden" name="id" value="<?php echo esc_attr( $item['id'] ) ?>"/>

        <div id="post-body-content">
            <div id="clickwhale-tabs" class="clickwhale-tabs">
                <?php if ( $tabs ) { ?>
                    <ul>
                        <?php foreach ( $tabs as $tab ) { ?>
                            <li>
                                <a href="#link-tab-<?php echo $tab['url'] ?>"><?php echo $tab['name'] ?></a>
                            </li>
                        <?php } ?>
                    </ul>
                <?php } ?>

                <div id="link-tab-general">
                    <table style="width: 100%;" class="form-table">
                        <caption hidden><?php _e( 'Link Main Settings', CLICKWHALE_NAME ); ?></caption>
                        <tbody>
                            <tr class="form-field">
                                <th scope="row">
                                    <label for="title"><?php _e( 'Title', CLICKWHALE_NAME ) ?></label>
                                </th>
                                <td>
                                    <?php
                                    echo Helper::render_control(
                                        array(
                                            'control'     => 'input',
                                            'id'          => 'title',
                                            'name'        => 'title',
                                            'type'        => 'text',
                                            'value'       => esc_attr( wp_unslash( $item['title'] ) ),
                                            'placeholder' => __( 'Link Title', CLICKWHALE_NAME ),
                                            'required'    => false // we validate it in Clickwhale_Link_Edit::admin_scripts()
                                        )
                                    );
                                    ?>
                                    <p id="cw-title--description"></p>
                                </td>
                            </tr>

                            <tr class="form-field">
                                <th scope="row">
                                    <label for="cw-slug"><?php _e( 'Slug', CLICKWHALE_NAME ) ?></label>
                                </th>
                                <td>
                                    <?php
                                    echo Helper::render_control(
                                        array(
                                            'control'     => 'input',
                                            'id'          => 'cw-slug',
                                            'name'        => 'slug',
                                            'type'        => 'text',
                                            'value'       => esc_attr( $slug ),
                                            'placeholder' => __( 'E.g. my-link', CLICKWHALE_NAME ),
                                            'required'    => false // we validate it in Clickwhale_Link_Edit::admin_scripts()
                                        )
                                    );
                                    ?>
                                    <p id="cw-slug--description"></p>
                                    <p id="cw-slug--text"
                                       class="code"
                                       title="<?php _e( 'Copy url', CLICKWHALE_NAME ) ?>">
                                        <?php
                                        $url_prefix = __( 'URL Preview', CLICKWHALE_NAME ) . ': ';
                                        $url        = trailingslashit( $url_prefix . get_bloginfo( 'url' ) ); ?>
                                        <?php echo trailingslashit( esc_html( $url ) . '<span>' . esc_html( $slug ) . '</span>' ); ?>
                                        <svg class="feather">
                                            <use href="<?php echo CLICKWHALE_ADMIN_ASSETS_DIR ?>/images/feather-sprite.svg#copy"></use>
                                        </svg>
                                    </p>
                                </td>
                            </tr>

                            <tr class="form-field">
                                <th scope="row">
                                    <label for="url"><?php _e( 'Target URL', CLICKWHALE_NAME ) ?></label>
                                </th>
                                <td>
                                    <?php
                                    echo Helper::render_control(
                                        array(
                                            'control'     => 'input',
                                            'id'          => 'url',
                                            'name'        => 'url',
                                            'type'        => 'url',
                                            'value'       => esc_url( $item['url'] ),
                                            'placeholder' => __( 'Link Target URL', CLICKWHALE_NAME ),
                                            'required'    => false // we validate it in Clickwhale_Link_Edit::admin_scripts()
                                        )
                                    );
                                    ?>
                                    <p id="cw-url--description"></p>
                                </td>
                            </tr>

                            <?php
                            echo Helper::render_control(
                                array(
                                    'row_label' => __( 'Redirection Type', CLICKWHALE_NAME ),
                                    'control'   => 'select',
                                    'id'        => 'redirection',
                                    'name'      => 'redirection',
                                    'options'   => array(
                                        301 => '301 Moved permanently',
                                        302 => '302 Found / Moved temporarily',
                                        303 => '303 See Other',
                                        307 => '307 Temporarily Redirect',
                                        308 => '308 Permanent Redirect',
                                    ),
                                    'value'     => $item['redirection'],
                                ),
                                true
                            );

                            echo Helper::render_control(
                                array(
                                    'row_label' => __( 'Nofollow', CLICKWHALE_NAME ),
                                    'control'   => 'checkbox',
                                    'id'        => 'nofollow',
                                    'name'      => 'nofollow',
                                    'value'     => $item['id'] === 0
                                                   && Helper::get_clickwhale_option( 'general', 'nofollow' )
                                        ? 1
                                        : $item['nofollow'],
                                    'label'     => __( 'Check to mark link as nofollow & noindex', CLICKWHALE_NAME )
                                ),
                                true
                            );

                            echo Helper::render_control(
                                array(
                                    'row_label' => __( 'Sponsored', CLICKWHALE_NAME ),
                                    'control'   => 'checkbox',
                                    'id'        => 'sponsored',
                                    'name'      => 'sponsored',
                                    'value'     => $item['id'] === 0
                                                   && Helper::get_clickwhale_option( 'general', 'sponsored' )
                                        ? 1
                                        : $item['sponsored'],
                                    'label'     => __( 'Check to mark link as sponsored', CLICKWHALE_NAME )
                                ),
                                true
                            );

                            echo Helper::render_control(
                                array(
                                    'row_label'   => __( 'Description', CLICKWHALE_NAME ),
                                    'control'     => 'textarea',
                                    'id'          => 'description',
                                    'name'        => 'description',
                                    'value'       => esc_html( wp_unslash( $item['description'] ) ),
                                    'placeholder' => __( 'Description', CLICKWHALE_NAME )
                                ),
                                true
                            );

                            if ( $item_categories ) {
                                $categories_for_options = [];
                                $current_categories     = ! empty( $item['categories'] )
                                    ? explode( ',', $item['categories'] )
                                    : [];

                                foreach ( $item_categories as $category ) {
                                    $categories_for_options[ $category->id ] = $category->title;
                                }

                                echo Helper::render_control(
                                    array(
                                        'row_label' => __( 'Category', CLICKWHALE_NAME ),
                                        'control'   => 'checkboxes',
                                        'id'        => 'category',
                                        'name'      => 'categories[]',
                                        'options'   => $categories_for_options,
                                        'value'     => $current_categories
                                    ),
                                    true
                                );
                            }
                            ?>
                        </tbody>
                    </table>
                </div>

                <?php do_action( 'clickwhale_link_after_tabs_content', $item ); ?>

            </div>

            <input type="hidden" id="created_at" name="created_at"
                   value="<?php echo esc_attr( $item['created_at'] ) ?>">
            <input type="hidden" id="updated_at" name="updated_at" value="">

            <input type="submit" value="<?php _e( 'Save link', CLICKWHALE_NAME ) ?>" id="submit"
                   class="button-primary"
                   name="submit">
            <button id="copy-link-url"
                    type="button"
                    class="button">
                <?php _e( 'Copy link', CLICKWHALE_NAME ) ?>
            </button>
        </div>
    </form>

    <?php do_action( 'clickwhale_admin_sidebar_end' ); ?>

</div>
