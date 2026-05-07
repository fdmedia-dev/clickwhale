<?php
use Clickwhale\Helpers\{Helper, Categories_Helper};

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$clickwhale_get_id = (int) filter_input( INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT );
Categories_Helper::get_limitation_error( $clickwhale_get_id );

$clickwhale_category = clickwhale()->category;
$clickwhale_item = $clickwhale_category->get_item( array( 'id' => $clickwhale_get_id ) );
$clickwhale_item_id = intval( $clickwhale_item['id'] );
$clickwhale_count = Categories_Helper::get_count();
$clickwhale_limit = Categories_Helper::get_limit();

do_action( 'clickwhale_admin_banner' );
?>
<div class="wrap">
    <?php
    echo wp_kses(
        Helper::render_heading(
            array(
                'name'         => esc_html__( 'Category', 'clickwhale' ),
                'is_edit'      => $clickwhale_item_id !== 0,
                'link_to_list' => esc_attr( CLICKWHALE_SLUG ) . '-categories',
                'link_to_add'  => esc_attr( CLICKWHALE_SLUG ) . '-edit-category',
                'is_limit'     => Categories_Helper::get_count() >= Categories_Helper::get_limit()
            )
        ),
        Helper::get_allowed_tags()
    );

    $clickwhale_category->show_message( $clickwhale_item_id );
    ?>

    <?php do_action( 'clickwhale_admin_sidebar_begin' ); ?>

    <form id="form_edit_<?php echo esc_attr( $clickwhale_category->instance_single ); ?>"
          class="clickwhale_form_edit"
          method="POST"
          action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">

        <input type="hidden" name="action" value="save_update_clickwhale_<?php echo esc_attr( $clickwhale_category->instance_single ); ?>" />
        <input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce( basename( __FILE__ ) ) ); ?>" />
        <input type="hidden" name="id" value="<?php echo intval( $clickwhale_item_id ); ?>" />

        <div class="metabox-holder" id="poststuff">
            <div id="post-body">
                <div id="post-body-content">
                    <table style="width: 100%;" class="form-table">
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
                                            'placeholder' => esc_attr__( 'e.g. Affiliate links', 'clickwhale' ),
                                            'required'    => true
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
                                <label for="slug"><?php esc_html_e( 'Slug', 'clickwhale' ); ?></label>
                            </th>
                            <td>
                                <?php
                                echo wp_kses(
                                    Helper::render_control(
                                        array(
                                            'control'     => 'input',
                                            'id'          => 'slug',
                                            'name'        => 'slug',
                                            'type'        => 'text',
                                            'value'       => esc_attr( $clickwhale_item['slug'] ),
                                            'placeholder' => esc_attr__( 'e.g. affiliate-links', 'clickwhale' ),
                                            'required'    => false
                                        )
                                    ),
                                    Helper::get_allowed_tags()
                                );
                                ?>
                                <p id="cw-slug--description"></p>
                            </td>
                        </tr>

                        <?php
                        echo wp_kses(
                            Helper::render_control(
                                array(
                                    'row_label'   => esc_html__( 'Description', 'clickwhale' ),
                                    'control'     => 'textarea',
                                    'id'          => 'description',
                                    'name'        => 'description',
                                    'value'       => esc_html( wp_unslash( $clickwhale_item['description'] ) ),
                                    'placeholder' => esc_attr__( 'Your comment here', 'clickwhale' ),
                                    'description' => esc_html__( 'Optional comment for the category', 'clickwhale' )
                                ),
                                true
                            ),
                            Helper::get_allowed_tags()
                        );
                        ?>
                        </tbody>
                    </table>

                    <?php if ( $clickwhale_count < $clickwhale_limit ) { ?>
                        <input type="submit"
                               value="<?php esc_attr_e( 'Save category', 'clickwhale' ); ?>"
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