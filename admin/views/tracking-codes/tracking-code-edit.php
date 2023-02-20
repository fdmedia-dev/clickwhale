<?php
$tracking_code = new ClickwhaleTrackingCodeEdit();
$tracking_code->init();

$item = $tracking_code->get_item( $_REQUEST );

$linkpages = $tracking_code->get_linkpages();
$pages     = $tracking_code->get_posts_by_post_type( 'page' );
$posts     = $tracking_code->get_posts_by_post_type( 'post' );

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
							),
							false
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
										'name'    => 'position[post_types_included][cw_linkpage][active]',
										'value'   => $item['position']['post_types_included']['cw_linkpage']['active'] ?? '0',
										'label'   => __( 'Include ClickWhale Link Pages', $this->plugin_name ),
									),
									false
								);
								?>
                                <div class="cw-posts-row--select">
									<?php
									echo ClickwhaleHepler::render_control(
										array(
											'control'  => 'select',
											'id'       => 'position_include_linkpage_ids',
											'name'     => 'position[post_types_included][cw_linkpage][ids][]',
											'value'    => $item['position']['post_types_included']['cw_linkpage']['ids'] ?? '',
											'options'  => $tracking_code->get_linkpages(),
											'default'  => 'all',
											'multiple' => true
										),
										false
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
										'name'    => 'position[post_types_excluded][cw_linkpage][active]',
										'value'   => $item['position']['post_types_excluded']['cw_linkpage']['active'] ?? '0',
										'label'   => __( 'Exclude ClickWhale Link Pages', $this->plugin_name ),
									),
									false
								);
								?>
                                <div class="cw-posts-row--select">
									<?php
									echo ClickwhaleHepler::render_control(
										array(
											'control'  => 'select',
											'id'       => 'position_exclude_linkpage_ids',
											'name'     => 'position[post_types_excluded][cw_linkpage][ids][]',
											'value'    => $item['position']['post_types_excluded']['cw_linkpage']['ids'] ?? '',
											'options'  => $tracking_code->get_linkpages(),
											'default'  => '',
											'multiple' => true
										),
										false
									);
									?>
                                </div>
                            </div>
						<?php } ?>

						<?php if ( $posts ) { ?>
                            <div class="cw-posts-row cw-posts-row--included">
								<?php
								echo ClickwhaleHepler::render_control(
									array(
										'control' => 'checkbox',
										'id'      => 'position_include_post',
										'name'    => 'position[post_types_included][post][active]',
										'value'   => $item['position']['post_types_included']['post']['active'] ?? '0',
										'label'   => __( 'Include Posts', $this->plugin_name ),
									),
									false
								);
								?>
                                <div class="cw-posts-row--select">
									<?php
									echo ClickwhaleHepler::render_control(
										array(
											'control'  => 'select',
											'id'       => 'position_include_post_ids',
											'name'     => 'position[post_types_included][post][ids][]',
											'value'    => $item['position']['post_types_included']['post']['ids'] ?? '',
											'options'  => $posts,
											'default'  => 'all',
											'multiple' => true
										),
										false
									);
									?>
                                </div>
                            </div>
                            <div class="cw-posts-row cw-posts-row--excluded">
								<?php
								echo ClickwhaleHepler::render_control(
									array(
										'control' => 'checkbox',
										'id'      => 'position_exclude_post',
										'name'    => 'position[post_types_excluded][post][active]',
										'value'   => $item['position']['post_types_excluded']['post']['active'] ?? '0',
										'label'   => __( 'Exclude Posts', $this->plugin_name ),
									),
									false
								);
								?>
                                <div class="cw-posts-row--select">
									<?php
									echo ClickwhaleHepler::render_control(
										array(
											'control'  => 'select',
											'id'       => 'position_exclude_post_ids',
											'name'     => 'position[post_types_excluded][post][ids][]',
											'value'    => $item['position']['post_types_excluded']['post']['ids'] ?? '',
											'options'  => $posts,
											'default'  => '',
											'multiple' => true
										),
										false
									);
									?>
                                </div>
                            </div>
						<?php } ?>

						<?php if ( $pages ) { ?>
                            <div class="cw-posts-row cw-posts-row--included">
								<?php
								echo ClickwhaleHepler::render_control(
									array(
										'control' => 'checkbox',
										'id'      => 'position_include_page',
										'name'    => 'position[post_types_included][page][active]',
										'value'   => $item['position']['post_types_included']['page']['active'] ?? '0',
										'label'   => __( 'Include Pages', $this->plugin_name ),
									),
									false
								);
								?>
                                <div class="cw-posts-row--select">
									<?php
									echo ClickwhaleHepler::render_control(
										array(
											'control'  => 'select',
											'id'       => 'position_include_page_ids',
											'name'     => 'position[post_types_included][page][ids][]',
											'value'    => $item['position']['post_types_included']['page']['ids'] ?? '',
											'options'  => $pages,
											'default'  => 'all',
											'multiple' => true
										),
										false
									);
									?>
                                </div>
                            </div>
                            <div class="cw-posts-row cw-posts-row--excluded">
								<?php
								echo ClickwhaleHepler::render_control(
									array(
										'control' => 'checkbox',
										'id'      => 'position_exclude_page',
										'name'    => 'position[post_types_excluded][page][active]',
										'value'   => $item['position']['post_types_excluded']['page']['active'] ?? '0',
										'label'   => __( 'Exclude Pages', $this->plugin_name ),
									),
									false
								);
								?>
                                <div class="cw-posts-row--select">
									<?php
									echo ClickwhaleHepler::render_control(
										array(
											'control'  => 'select',
											'id'       => 'position_exclude_page_ids',
											'name'     => 'position[post_types_excluded][page][ids][]',
											'value'    => $item['position']['post_types_excluded']['page']['ids'] ?? '',
											'options'  => $pages,
											'default'  => '',
											'multiple' => true
										),
										false
									);
									?>
                                </div>
                            </div>
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
