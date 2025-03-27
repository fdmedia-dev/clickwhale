<?php
global $wpdb;

use clickwhale\includes\helpers\{Helper, Categories_Helper};

Categories_Helper::get_limitation_error( $_GET['id'] );

$category = clickwhale()->category;
$item = $category->get_item( $_GET );
$item_id = intval( $item['id'] );
$count = Categories_Helper::get_count();
$limit = Categories_Helper::get_limit();

do_action( 'clickwhale_admin_banner' );
?>
<div class="wrap">
    <?php
    echo Helper::render_heading(
        array(
            'name'         => __( 'Category', 'clickwhale' ),
            'is_edit'      => $item_id !== 0,
            'link_to_list' => CLICKWHALE_SLUG . '-categories',
            'link_to_add'  => CLICKWHALE_SLUG . '-edit-category',
            'is_limit'     => Categories_Helper::get_count() >= Categories_Helper::get_limit()
        )
    );

    $category->show_message( $item_id );
    ?>

    <?php do_action( 'clickwhale_admin_sidebar_begin' ); ?>

    <form id="form_edit_<?php echo $category->instance_single; ?>"
          class="clickwhale_form_edit"
          method="POST"
          action="<?php echo esc_attr( admin_url( 'admin-post.php' ) ); ?>">

        <input type="hidden" name="action" value="save_update_clickwhale_<?php echo $category->instance_single; ?>" />
        <input type="hidden" name="nonce" value="<?php echo wp_create_nonce( basename( __FILE__ ) ); ?>" />
        <input type="hidden" name="id" value="<?php echo $item_id; ?>" />

        <div class="metabox-holder" id="poststuff">
            <div id="post-body">
                <div id="post-body-content">
                    <table style="width: 100%;" class="form-table">
                        <tbody>

                        <tr class="form-field">
                            <th scope="row">
                                <label for="title"><?php _e( 'Title', 'clickwhale' ); ?></label>
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
                                        'placeholder' => __( 'e.g. Affiliate links', 'clickwhale' ),
                                        'required'    => true
                                    )
                                );
                                ?>
                                <p id="cw-title--description"></p>
                            </td>
                        </tr>

                        <tr class="form-field">
                            <th scope="row">
                                <label for="slug"><?php _e( 'Slug', 'clickwhale' ); ?></label>
                            </th>
                            <td>
                                <?php
                                echo Helper::render_control(
                                    array(
                                        'control'     => 'input',
                                        'id'          => 'slug',
                                        'name'        => 'slug',
                                        'type'        => 'text',
                                        'value'       => esc_attr( $item['slug'] ),
                                        'placeholder' => __( 'e.g. affiliate-links', 'clickwhale' ),
                                        'required'    => false
                                    )
                                );
                                ?>
                                <p id="cw-slug--description"></p>
                            </td>
                        </tr>

                        <?php
                        echo Helper::render_control(
                            array(
                                'row_label'   => __( 'Description', 'clickwhale' ),
                                'control'     => 'textarea',
                                'id'          => 'description',
                                'name'        => 'description',
                                'value'       => esc_html( wp_unslash( $item['description'] ) ),
                                'placeholder' => __( 'Your comment here', 'clickwhale' ),
                                'description' => __( 'Optional comment for the category', 'clickwhale' )
                            ),
                            true
                        );
                        ?>
                        </tbody>
                    </table>

                    <?php if ( $count < $limit ) { ?>
                        <input type="submit"
                               value="<?php _e( 'Save category', 'clickwhale' ); ?>"
                               id="submit"
                               class="button-primary"
                               name="submit" />
                    <?php } ?>
                </div>
            </div>
        </div>
    </form>

    <?php do_action( 'clickwhale_admin_sidebar_end' ); ?>

</div>