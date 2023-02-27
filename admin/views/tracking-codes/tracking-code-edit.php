<?php
$tracking_code = new ClickwhaleTrackingCodeEdit();
$tracking_code->init();

$item       = $tracking_code->get_item( $_REQUEST );
$linkpages  = $tracking_code->get_linkpages();
$post_types = $tracking_code::get_default_post_types();
$taxonomies = $tracking_code::get_default_terms_tax();

do_action( 'clickwhale_admin_banner' );
?>

<div class="wrap">
	<?php
	echo ClickwhaleHepler::render_heading(
		array(
			'name'         => __( 'Tracking Code', $this->plugin_name ),
			'is_edit'      => isset( $item['id'] ) && $item['id'] !== 0,
			'link_to_list' => 'clickwhale-tracking-codes',
			'link_to_edit' => 'clickwhale-edit-tracking-code',
			'is_limit'     => ClickwhaleTrackingCodesHelper::get_count() >= ClickwhaleTrackingCodesHelper::get_limit(),
		)
	);
	?>

    <form id="form_edit_tracking_code" method="POST" action="<?php echo esc_attr( admin_url( 'admin-post.php' ) ); ?>">
        <input type="hidden" name="action" value="save_update_tracking_code">
        <input type="hidden" name="nonce" value="<?php echo wp_create_nonce( basename( __FILE__ ) ) ?>"/>
        <input type="hidden" name="id" value="<?php echo esc_attr( $item['id'] ) ?>"/>

        <div id="post-body-content">

            <table style="width: 100%;" class="form-table">
                <tbody>

				<?php
				echo ClickwhaleHepler::render_control(
					array(
						'row_label'   => __( 'Title', $this->plugin_name ),
						'control'     => 'input',
						'id'          => 'title',
						'name'        => 'title',
						'type'        => 'text',
						'value'       => esc_attr( wp_unslash( $item['title'] ) ),
						'placeholder' => __( 'E.g. Google Tag Manager Code', $this->plugin_name ),
						'required'    => true,
					),
					true
				);

				// @link https://www.ibenic.com/wordpress-code-editor/
				echo ClickwhaleHepler::render_control(
					array(
						'row_label'   => __( 'Code', $this->plugin_name ),
						'control'     => 'textarea',
						'id'          => 'code',
						'name'        => 'code',
						'value'       => wp_unslash( $item['code'] ),
						'description' => __( 'Paste your code here.', $this->plugin_name ),
					),
					true
				);

				echo ClickwhaleHepler::render_control(
					array(
						'row_label' => __( 'Code Position', $this->plugin_name ),
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
					true
				);
				?>
                <tr class="form-field">
                    <th scope="row">
                        <label for="position"><?php _e( 'In which page do you want to insert this code?',
								$this->plugin_name ) ?></label>
                    </th>
                    <td>
						<?php
						echo ClickwhaleHepler::render_control(
							array(
								'control' => 'radio',
								'id'      => 'position_pages',
								'name'    => 'position[pages]',
								'value'   => $item['position']['pages'] ?? '',
								'options' => array(
									'all'    => 'Whole website',
									'custom' => 'Specific page'
								),
								'default' => 'all'
							)
						);
						?>

                        <br>

						<?php if ( $linkpages ) { ?>
                            <div class="cw-posts-row cw-posts-row--included">
								<?php
								echo ClickwhaleHepler::render_control(
									array(
										'control' => 'checkbox',
										'id'      => 'position_include_cw_linkpage',
										'name'    => 'position[items_included][cw_linkpage][active]',
										'value'   => $item['position']['items_included']['cw_linkpage']['active'] ?? '0',
										'label'   => __( 'Include ClickWhale Link Pages', $this->plugin_name ),
									)
								);
								?>
                                <div class="cw-posts-row--select">
									<?php
									echo ClickwhaleHepler::render_control(
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
								echo ClickwhaleHepler::render_control(
									array(
										'control' => 'checkbox',
										'id'      => 'position_exclude_cw_linkpage',
										'name'    => 'position[items_excluded][cw_linkpage][active]',
										'value'   => $item['position']['items_excluded']['cw_linkpage']['active'] ?? '0',
										'label'   => __( 'Exclude ClickWhale Link Pages', $this->plugin_name ),
									)
								);
								?>
                                <div class="cw-posts-row--select">
									<?php
									echo ClickwhaleHepler::render_control(
										array(
											'control'  => 'select',
											'id'       => 'position_exclude_linkpage_ids',
											'class'    => 'with-select2',
											'name'     => 'position[items_excluded][cw_linkpage][ids][]',
											'value'    => $item['position']['items_excluded']['cw_linkpage']['ids'] ?? '',
											'options'  => $tracking_code->get_linkpages(),
											'default'  => '',
											'multiple' => true
										)
									);
									?>
                                </div>
                            </div>
						<?php } ?>

						<?php
						foreach ( $post_types as $post_type ) {
							$post_type_object = get_post_type_object( $post_type );
							if ( $post_type_object ) {
								?>
                                <div class="cw-posts-row cw-posts-row--included">
									<?php
									echo ClickwhaleHepler::render_control(
										array(
											'control' => 'checkbox',
											'id'      => 'position_include_' . $post_type,
											'name'    => 'position[items_included][' . $post_type . '][active]',
											'value'   => $item['position']['items_included'][ $post_type ]['active'] ?? '0',
											'label'   => sprintf(
												__( 'Include <strong>%s</strong>', $this->plugin_name ),
												$post_type_object->labels->name
											)
										)
									);
									?>
                                    <div class="cw-posts-row--select">
										<?php
										echo ClickwhaleHepler::render_control(
											array(
												'control'  => 'select',
												'id'       => 'position_include_' . $post_type . '_ids',
												'class'    => 'with-select2',
												'name'     => 'position[items_included][' . $post_type . '][ids][]',
												'value'    => $item['position']['items_included'][ $post_type ]['ids'] ?? '',
												'options'  => $tracking_code->get_posts_by_post_type( $post_type ),
												'default'  => 'all',
												'multiple' => true
											)
										);
										?>
                                    </div>
                                </div>
                                <div class="cw-posts-row cw-posts-row--excluded">
									<?php
									echo ClickwhaleHepler::render_control(
										array(
											'control' => 'checkbox',
											'id'      => 'position_exclude_' . $post_type,
											'name'    => 'position[items_excluded][' . $post_type . '][active]',
											'value'   => $item['position']['items_excluded'][ $post_type ]['active'] ?? '0',
											'label'   => sprintf(
												__( 'Exclude <strong>%s</strong>', $this->plugin_name ),
												$post_type_object->labels->name
											)
										)
									);
									?>
                                    <div class="cw-posts-row--select">
										<?php
										echo ClickwhaleHepler::render_control(
											array(
												'control'  => 'select',
												'id'       => 'position_exclude_' . $post_type . '_ids',
												'class'    => 'with-select2',
												'name'     => 'position[items_excluded][' . $post_type . '][ids][]',
												'value'    => $item['position']['items_excluded'][ $post_type ]['ids'] ?? '',
												'options'  => $tracking_code->get_posts_by_post_type( $post_type ),
												'default'  => 'all',
												'multiple' => true
											)
										);
										?>
                                    </div>
                                </div>
							<?php } ?>
						<?php } ?>

						<?php
						foreach ( $taxonomies as $taxonomy ) {
							$taxonomy_object = get_taxonomy( $taxonomy );
							if ( $taxonomy_object ) {
								?>
                                <div class="cw-posts-row cw-posts-row--included">
									<?php
									echo ClickwhaleHepler::render_control(
										array(
											'control' => 'checkbox',
											'id'      => 'position_include_' . $taxonomy,
											'name'    => 'position[items_included][' . $taxonomy . '][active]',
											'value'   => $item['position']['items_included'][ $taxonomy ]['active'] ?? '0',
											'label'   => sprintf(
												__( 'Include <strong>%s</strong>', $this->plugin_name ),
												$taxonomy_object->label
											)
										)
									);
									?>
                                    <div class="cw-posts-row--select">
										<?php
										echo ClickwhaleHepler::render_control(
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
									echo ClickwhaleHepler::render_control(
										array(
											'control' => 'checkbox',
											'id'      => 'position_exclude_' . $taxonomy,
											'name'    => 'position[items_excluded][' . $taxonomy . '][active]',
											'value'   => $item['position']['items_excluded'][ $taxonomy ]['active'] ?? '0',
											'label'   => sprintf(
												__( 'Exclude <strong>%s</strong>', $this->plugin_name ),
												$taxonomy_object->label
											)
										)
									);
									?>
                                    <div class="cw-posts-row--select">
										<?php
										echo ClickwhaleHepler::render_control(
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
				echo ClickwhaleHepler::render_control(
					array(
						'row_label'   => __( 'Description', $this->plugin_name ),
						'control'     => 'textarea',
						'id'          => 'description',
						'name'        => 'description',
						'value'       => esc_html( wp_unslash( $item['description'] ) ),
						'placeholder' => __( 'Your comment here', $this->plugin_name ),
						'description' => __( 'Optional comment to the tracking code.', $this->plugin_name ),
					),
					true
				);

				echo ClickwhaleHepler::render_control(
					array(
						'row_label' => __( 'Active', $this->plugin_name ),
						'control'   => 'checkbox',
						'id'        => 'is_active',
						'name'      => 'is_active',
						'value'     => $item['is_active'],
						'label'     => __( 'Enable Tracking Code', $this->plugin_name ),
					),
					true
				);

				?>
                </tbody>
            </table>

            <input type="hidden" id="created_at" name="created_at"
                   value="<?php echo esc_attr( $item['created_at'] ) ?>">
            <input type="hidden" id="updated_at" name="updated_at" value="">

            <input type="submit" value="<?php _e( 'Save', $this->plugin_name ) ?>" id="submit"
                   class="button-primary"
                   name="submit">

        </div>
    </form>

</div>
