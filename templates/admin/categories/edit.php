<?php
global $wpdb;

use clickwhale\includes\helpers\{Helper, Categories_Helper};

Categories_Helper::get_limitation_error( $_GET['id'] );

$category = clickwhale()->category;
$item     = $category->get_item( $_REQUEST );
$count    = Categories_Helper::get_count();
$limit    = Categories_Helper::get_limit();

do_action( 'clickwhale_admin_banner' );
?>
<div class="wrap">
	<?php
	echo Helper::render_heading(
		array(
			'name'         => __( 'Category', CLICKWHALE_NAME ),
			'is_edit'      => isset( $item['id'] ) && $item['id'] !== 0,
			'link_to_list' => CLICKWHALE_SLUG . '-categories',
			'link_to_add'  => CLICKWHALE_SLUG . '-edit-category',
			'is_limit'     => Categories_Helper::get_count() >= Categories_Helper::get_limit()
		)
	);

	$category->show_message( $item['id'] );
	?>

    <?php do_action( 'clickwhale_admin_sidebar_begin' ); ?>

    <form id="form_edit_<?php echo $category->instance_single ?>"
          class="clickwhale_form_edit"
          method="POST"
          action="<?php echo esc_attr( admin_url( 'admin-post.php' ) ); ?>">

        <input type="hidden" name="action" value="save_update_clickwhale_<?php echo $category->instance_single ?>">
        <input type="hidden" name="nonce" value="<?php echo wp_create_nonce( basename( __FILE__ ) ) ?>"/>
        <input type="hidden" name="id" value="<?php echo esc_attr( $item['id'] ) ?>"/>

        <div class="metabox-holder" id="poststuff">
            <div id="post-body">
                <div id="post-body-content">
                    <table style="width: 100%;" class="form-table">
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
										'placeholder' => __( 'E.g. Affiliate links', CLICKWHALE_NAME ),
										'required'    => true,
									)
								);
								?>
                                <p id="cw-title--description"></p>
                            </td>
                        </tr>

                        <tr class="form-field">
                            <th scope="row">
                                <label for="slug"><?php _e( 'Slug', CLICKWHALE_NAME ) ?></label>
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
										'placeholder' => __( 'E.g. affiliate-links', CLICKWHALE_NAME ),
										'required'    => false,
									)
								);
								?>
                                <p id="cw-slug--description"></p>
                            </td>
                        </tr>

						<?php
						echo Helper::render_control(
							array(
								'row_label'   => __( 'Description', CLICKWHALE_NAME ),
								'control'     => 'textarea',
								'id'          => 'description',
								'name'        => 'description',
								'value'       => esc_html( wp_unslash( $item['description'] ) ),
								'placeholder' => __( 'Your comment here', CLICKWHALE_NAME ),
								'description' => __( 'Optional comment for the category', CLICKWHALE_NAME ),
							),
							true
						);
						?>

                        </tbody>
                    </table>

					<?php if ( $count < $limit ) { ?>
                        <input type="submit"
                               value="<?php _e( 'Save category', CLICKWHALE_NAME ) ?>"
                               id="submit"
                               class="button-primary"
                               name="submit">
					<?php } ?>
                </div>
            </div>
        </div>
    </form>

    <?php do_action( 'clickwhale_admin_sidebar_end' ); ?>

</div>