<?php

use clickwhale\includes\admin\Clickwhale_WP_User;
use clickwhale\includes\helpers\{Helper, Tracking_Codes_Helper};

Tracking_Codes_Helper::get_limitation_error( $_GET['id'] );

$tracking_code = clickwhale()->tracking_code;

$item       = $tracking_code->get_item( $_REQUEST );
$linkpages  = $tracking_code->get_linkpages();
$post_types = Tracking_Codes_Helper::get_default_post_types();
$taxonomies = Tracking_Codes_Helper::get_default_terms_tax();

do_action( 'clickwhale_admin_banner' );
?>
<div class="wrap">
	<?php
	echo Helper::render_heading(
		array(
			'name'         => __( 'Tracking Code', CLICKWHALE_NAME ),
			'is_edit'      => ! empty( $item['id'] ),
			'link_to_list' => CLICKWHALE_SLUG . '-tracking-codes',
			'link_to_add'  => CLICKWHALE_SLUG . '-edit-tracking-code',
		)
	);

	$tracking_code->show_message( $item['id'] );
	?>

    <?php do_action( 'clickwhale_admin_sidebar_begin' ); ?>

    <form id="form_edit_<?php echo $tracking_code->instance_single ?>"
          class="clickwhale_form_edit"
          method="POST"
          action="<?php echo esc_attr( admin_url( 'admin-post.php' ) ); ?>">

        <input type="hidden" name="action" value="save_update_clickwhale_<?php echo $tracking_code->instance_single ?>">
        <input type="hidden" name="nonce" value="<?php echo wp_create_nonce( basename( __FILE__ ) ) ?>"/>
        <input type="hidden" name="id" value="<?php echo esc_attr( $item['id'] ) ?>"/>

        <div id="post-body-content">

            <table style="width: 100%;" class="form-table">
                <caption style="display: none"><?php _e( 'Tracking Code Edit Table', CLICKWHALE_NAME ); ?></caption>
                <tbody>

				<?php
				echo Helper::render_control(
					array(
						'row_label'   => __( 'Title', CLICKWHALE_NAME ),
						'control'     => 'input',
						'id'          => 'title',
						'name'        => 'title',
						'type'        => 'text',
						'value'       => esc_attr( wp_unslash( $item['title'] ) ),
						'placeholder' => __( 'E.g. Google Tag Manager Code', CLICKWHALE_NAME ),
						'required'    => true,
					),
					true
				);

				// @link https://www.ibenic.com/wordpress-code-editor/
				echo Helper::render_control(
					array(
						'row_label'   => __( 'Code', CLICKWHALE_NAME ),
						'control'     => 'textarea',
						'id'          => 'code',
						'name'        => 'code',
						'value'       => wp_unslash( $item['code'] ),
						'description' => __( 'Paste your code here.', CLICKWHALE_NAME ),
					),
					true
				);

				$tracking_code->conversion_fields( $item );

				echo Helper::render_control(
					array(
						'row_label' => __( 'Code Position', CLICKWHALE_NAME ),
						'control'   => 'select',
						'id'        => 'position_code',
						'name'      => 'position[code]',
						'value'     => $item['position']['code'] ?? '',
						'options'   => array(
							'wp_head'      => 'Before &lt;/head&gt;',
							'wp_body_open' => 'After &lt;body&gt;',
							'wp_footer'    => 'Before &lt;/body&gt;',
						)
					),
					true,
					'for_mode for_standard_mode'
				);
				?>

                <tr class="form-field for_mode for_standard_mode">
                    <th scope="row">
                        <label for="position"><?php _e( 'In which page do you want to insert this code?', CLICKWHALE_NAME ) ?></label>
                    </th>
                    <td>
						<?php
						echo Helper::render_control(
							array(
								'control' => 'radio',
								'id'      => 'position_pages',
								'name'    => 'position[pages]',
								'value'   => $item['position']['pages'] ?? '',
								'options' => array(
									'all'    => __( 'Whole website', CLICKWHALE_NAME ),
									'custom' => __( 'Specific page', CLICKWHALE_NAME )
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
										'value'   => $item['position']['items_included']['cw_linkpage']['active'] ?? '0',
										'label'   => __( 'Include ClickWhale Link Pages', CLICKWHALE_NAME ),
									)
								);
								?>
                                <div class="cw-posts-row--select">
									<?php
									echo Helper::render_control(
										array(
											'control'  => 'select',
											'id'       => 'position_include_linkpage_ids',
											'class'    => 'with-select2',
											'name'     => 'position[items_included][cw_linkpage][ids][]',
											'value'    => $item['position']['items_included']['cw_linkpage']['ids'] ?? '',
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
										'value'   => $item['position']['items_excluded']['cw_linkpage']['active'] ?? '0',
										'label'   => __( 'Exclude ClickWhale Link Pages', CLICKWHALE_NAME ),
									)
								);
								?>
                                <div class="cw-posts-row--select">
									<?php
									echo Helper::render_control(
										array(
											'control'  => 'select',
											'id'       => 'position_exclude_linkpage_ids',
											'class'    => 'with-select2',
											'name'     => 'position[items_excluded][cw_linkpage][ids][]',
											'value'    => $item['position']['items_excluded']['cw_linkpage']['ids'] ?? '',
											'options'  => $tracking_code->get_linkpages(),
											'default'  => 'all',
											'multiple' => true
										)
									);
									?>
                                </div>
                            </div>
						<?php } ?>

						<?php foreach ( $post_types as $post_type => $singular ) { ?>
                            <div class="cw-posts-row cw-posts-row--included">
								<?php
								echo Helper::render_control(
									array(
										'control' => 'checkbox',
										'id'      => 'position_include_' . $post_type,
										'name'    => 'position[items_included][' . $post_type . '][active]',
										'value'   => $item['position']['items_included'][ $post_type ]['active'] ?? '0',
										'label'   => sprintf(
											__( 'Include <strong>%s</strong>', CLICKWHALE_NAME ),
											$singular
										)
									)
								);
								?>
                                <div class="cw-posts-row--select">
									<?php
									echo Helper::render_control(
										array(
											'control'  => 'select',
											'id'       => 'position_include_' . $post_type . '_ids',
											'class'    => 'with-select2',
											'name'     => 'position[items_included][' . $post_type . '][ids][]',
											'value'    => $item['position']['items_included'][ $post_type ]['ids'] ?? '',
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
										'id'      => 'position_exclude_' . $post_type,
										'name'    => 'position[items_excluded][' . $post_type . '][active]',
										'value'   => $item['position']['items_excluded'][ $post_type ]['active'] ?? '0',
										'label'   => sprintf(
											__( 'Exclude <strong>%s</strong>', CLICKWHALE_NAME ),
											$singular
										)
									)
								);
								?>
                                <div class="cw-posts-row--select">
									<?php
									echo Helper::render_control(
										array(
											'control'  => 'select',
											'id'       => 'position_exclude_' . $post_type . '_ids',
											'class'    => 'with-select2',
											'name'     => 'position[items_excluded][' . $post_type . '][ids][]',
											'value'    => $item['position']['items_excluded'][ $post_type ]['ids'] ?? '',
											'options'  => $tracking_code::get_posts_by_post_type( $post_type ),
											'default'  => 'all',
											'multiple' => true
										)
									);
									?>
                                </div>
                            </div>
						<?php } ?>

						<?php
						foreach ( $taxonomies as $taxonomy ) {
							$taxonomy_object = get_taxonomy( $taxonomy );
							if ( $taxonomy_object ) {
								?>
                                <div class="cw-posts-row cw-posts-row--included">
									<?php
									echo Helper::render_control(
										array(
											'control' => 'checkbox',
											'id'      => 'position_include_' . $taxonomy,
											'name'    => 'position[items_included][' . $taxonomy . '][active]',
											'value'   => $item['position']['items_included'][ $taxonomy ]['active'] ?? '0',
											'label'   => sprintf(
												__( 'Include <strong>%s</strong>', CLICKWHALE_NAME ),
												$taxonomy_object->label
											)
										)
									);
									?>
                                    <div class="cw-posts-row--select">
										<?php
										echo Helper::render_control(
											array(
												'control'  => 'select',
												'id'       => 'position_include_' . $taxonomy . '_ids',
												'class'    => 'with-select2',
												'name'     => 'position[items_included][' . $taxonomy . '][ids][]',
												'value'    => $item['position']['items_included'][ $taxonomy ]['ids'] ?? '',
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
											'id'      => 'position_exclude_' . $taxonomy,
											'name'    => 'position[items_excluded][' . $taxonomy . '][active]',
											'value'   => $item['position']['items_excluded'][ $taxonomy ]['active'] ?? '0',
											'label'   => sprintf(
												__( 'Exclude <strong>%s</strong>', CLICKWHALE_NAME ),
												$taxonomy_object->label
											)
										)
									);
									?>
                                    <div class="cw-posts-row--select">
										<?php
										echo Helper::render_control(
											array(
												'control'  => 'select',
												'id'       => 'position_exclude_' . $taxonomy . '_ids',
												'class'    => 'with-select2',
												'name'     => 'position[items_excluded][' . $taxonomy . '][ids][]',
												'value'    => $item['position']['items_excluded'][ $taxonomy ]['ids'] ?? '',
												'options'  => $tracking_code->get_terms_by_tax( $taxonomy ),
												'default'  => 'all',
												'multiple' => true
											)
										);
										?>
                                    </div>
                                </div>
							<?php } ?>
						<?php } ?>
                    </td>
                </tr>

				<?php
				echo Helper::render_control(
					array(
						'row_label'   => __( 'Exclude User Roles', CLICKWHALE_NAME ),
						'control'     => 'checkboxes',
						'id'          => 'position_exclude_user_by_role',
						'name'        => 'position[exclude_user_by_role][]',
						'value'       => $item['position']['exclude_user_by_role'] ?? 0,
						'options'     => Clickwhale_WP_User::get_all_roles(),
						'description' => __( 'Check the user roles for which the script should not be executed.', CLICKWHALE_NAME ),
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
						'placeholder' => __( 'Your comment here', CLICKWHALE_NAME ),
						'description' => __( 'Optional comment to the tracking code.', CLICKWHALE_NAME ),
					),
					true
				);

				echo Helper::render_control(
					array(
						'row_label'        => __( 'Active', CLICKWHALE_NAME ),
						'control'          => 'checkbox',
						'id'               => 'is_active',
						'name'             => 'is_active',
						'class'            => 'clickwhale_tc_active_toggle',
						'value'            => $item['is_active'],
						'label'            => __( 'Enable Tracking Code', CLICKWHALE_NAME ),
						'disabled'         => Tracking_Codes_Helper::is_active_limit(),
						'disabled_message' => Tracking_Codes_Helper::get_limitation_notice()
					),
					true
				);

				?>
                </tbody>
            </table>

            <input type="hidden" id="created_at" name="created_at"
                   value="<?php echo esc_attr( $item['created_at'] ) ?>">
            <input type="hidden" id="updated_at" name="updated_at" value="">

            <input type="submit" value="<?php _e( 'Save', CLICKWHALE_NAME ) ?>" id="submit"
                   class="button-primary"
                   name="submit">

        </div>
    </form>

    <?php do_action( 'clickwhale_admin_sidebar_end' ); ?>

</div>
