<?php
namespace clickwhale_pro\includes\admin\linkpages;

use clickwhale\includes\content_templates\Clickwhale_Linkpage_Content_Templates;
use clickwhale\includes\helpers\Helper;
use clickwhale_pro\includes\helpers\Linkpage_Styles_Helper;
use WP_Query;

class Clickwhale_Pro_Linkpage_Edit {

	public function __construct() {
		$this->load_dependencies();

		add_action( 'admin_print_footer_scripts', [ $this, 'admin_scripts' ], 20 );
	}

	public function load_dependencies() {
		require_once CLICKWHALE_PRO_DIR . 'includes/admin/linkpages/Clickwhale_Pro_Linkpage_Preview.php';
	}

	public function get_defaults(): array {
		return array(
			'styles' => '',
			'social' => ''
		);
	}

	public function linkpage_defaults( $fields ) {
		return array_merge( $fields, $this->get_defaults() );
	}

	public function linkpage_select( $select ) {
		$pro = array(
			'label'   => __( 'Pro Blocks', CLICKWHALE_PRO_SLUG ),
			'options' => array(
				'cw_feed'   => array(
					'name' => __( 'Blog Posts Feed', CLICKWHALE_PRO_SLUG ),
					'icon' => 'list'
				),
				'cw_social' => array(
					'name' => __( 'Social Profiles', CLICKWHALE_PRO_SLUG ),
					'icon' => 'share-2'
				),
			),
		);

		$select[] = $pro;

		return $select;
	}

	public function after_settings_fields( $item ) {
		echo Helper::render_control(
			array(
				'row_label'   => __( 'Logo Style', CLICKWHALE_PRO_SLUG ),
				'control'     => 'radio',
				'id'          => 'logo_style',
				'name'        => 'styles[logo_style]',
				'value'       => $item['styles']['logo_style'] ?? '',
				'options'     => array(
					'default' => __( 'Square (Default)', CLICKWHALE_PRO_SLUG ),
					'rounded' => __( 'Rounded', CLICKWHALE_PRO_SLUG ),
					'circle'  => __( 'Circle', CLICKWHALE_PRO_SLUG ),
				),
				'default'     => 'default',
				'placeholder' => __( 'Set Link Page logo style', CLICKWHALE_PRO_SLUG ),
			),
			true
		);
		echo Helper::render_control(
			array(
				'row_label' => __( 'Logo Shadow', CLICKWHALE_PRO_SLUG ),
				'control'   => 'checkbox',
				'id'        => 'logo_shadow',
				'name'      => 'styles[logo_shadow]',
				'value'     => $item['styles']['logo_shadow'] ?? '0',
				'label'     => __( 'Add logo box shadow', CLICKWHALE_PRO_SLUG ),
			),
			true
		);
	}

	public function linkpage_content_defaults( $defaults ) {
		$pro_types = array( 'cw_feed', 'cw_social' );

		foreach ( $pro_types as $pro_type ) {
			$defaults[ $pro_type ] = array(
				'admin'  => array( $this, 'template_admin_' . $pro_type ),
				'public' => array( $this, 'template_public_' . $pro_type )
			);
		}

		return $defaults;
	}

	/**
	 * Filter Link Page Public bottom copyright
	 *
	 * @param string $credits
	 *
	 * @return mixed|string
	 */
	public function linkpage_credits( string $credits ) {
		$linkpages_options = get_option( 'clickwhale_linkpages_options' );

		return isset( $linkpages_options['linkpage_credits'] ) ? '' : $credits;
	}

	/**
	 * See class Clickwhale_Linkpage_Edit method render_tabs()
	 *
	 * @param $tabs
	 *
	 * @return mixed
	 */
	public function linkpage_tabs( $tabs ) {
		$tabs['social'] = array(
			'name' => __( 'Social Profiles', CLICKWHALE_PRO_SLUG ),
			'url'  => 'social'
		);

		return $tabs;
	}

	private function render_social_fields( $slug, $label, $value = '' ) {
		ob_start();
		?>
        <tr class="form-field">
            <th scope="row">
                <div class="linkpage-row--drag" title="Change Order">
                    <svg class="feather">
                        <use href="<?php echo CLICKWHALE_ADMIN_ASSETS_DIR ?>/images/feather-sprite.svg#drag-2"></use>
                    </svg>
                </div>
                <label for="<?php echo $slug ?>"><?php echo $label ?></label>
            </th>
            <td>
				<?php
				$type        = 'url';
				$placeholder = '';
				$description = '';

				switch ( $slug ) {
					case 'email':
						$type = 'email';
						break;
					case 'whatsapp':
						$type        = 'tel';
						$description = __( 'Number in the international format (with + and country code)',
							CLICKWHALE_SLUG );
						break;
					case 'telegram':
						$placeholder = 'e.g. https://t.me/telegram or https://t.me/s/telegram';
						break;
				}

				echo Helper::render_control(
					array(
						'control'     => 'input',
						'id'          => $slug,
						'name'        => 'social[profiles][' . $slug . ']',
						'type'        => $type,
						'value'       => $value,
						'placeholder' => $placeholder,
						'description' => $description
					)
				);
				?>
            </td>
        </tr>
		<?php
		$result = ob_get_contents();
		ob_clean();

		return $result;
	}

	public function linkpage_after_tabs_content( $item ) {
		$defaults = array(
			'email',
			'facebook',
			'instagram',
			'linkedin',
			'pinterest',
			'rss',
			'telegram',
			'threads',
			'tiktok',
			'twitter',
			'whatsapp',
			'youtube'
		);
		$socials  = isset( $item['social']['profiles'] ) ? maybe_unserialize( $item['social']['profiles'] ) : [];
		?>
        <div id="lp-tab-social">
            <table cellspacing="2" cellpadding="5" style="width: 100%;" class="form-table">
                <caption hidden>Link Page Social Options</caption>
                <tbody>

				<?php
				foreach ( $defaults as $profile ) {
					if ( ! array_key_exists( $profile, $socials ) ) {
						$socials[ $profile ] = '';
					}
				}
				foreach ( $socials as $k => $v ) {
					echo $this->render_social_fields( $k, ucfirst( $k ), $v );
				}
				?>
                </tbody>
            </table>
        </div>
		<?php


		return $item;
	}

	public function linkpage_advanced_background() {
		return true;
	}

	public function after_general_styles( $item ) {
		$styles = $item['styles'];

		echo Helper::render_control(
			array(
				'row_label'   => __( 'Site Background', CLICKWHALE_PRO_SLUG ),
				'control'     => 'radio',
				'id'          => 'bg_style',
				'name'        => 'styles[bg_style]',
				'value'       => $item['styles']['bg_style'] ?? '',
				'options'     => array(
					'color'    => __( 'Solid Color', CLICKWHALE_PRO_SLUG ),
					'gradient' => __( 'Gradient', CLICKWHALE_PRO_SLUG ),
					'pattern'  => __( 'Pattern', CLICKWHALE_PRO_SLUG ),
					'image'    => __( 'Image', CLICKWHALE_PRO_SLUG )
				),
				'default'     => 'color',
				'placeholder' => __( 'Choose page background style', CLICKWHALE_PRO_SLUG )
			),
			true
		);
		?>

        <tr class="form-field for-style for-color-style">
            <th scope="row"></th>
            <td>
                <input name="styles[bg_color]"
                       class="cw-color-control"
                       type="text"
                       value="<?php echo esc_attr( $styles['bg_color'] ) ?>"/>
                <p class="description"><?php _e( 'Set page background color', CLICKWHALE_PRO_SLUG ) ?></p>
            </td>
        </tr>
        <tr class="form-field for-style for-gradient-style">
            <th scope="row"></th>
            <td>
                <div class="clickwhale-styles-grid clickwhale-gradients-grid">
					<?php
					$gradients        = Linkpage_Styles_Helper::get_gradients();
					$current_gradient = $styles['bg_gradient'] ?? '';
					if ( $gradients ) {
						foreach ( $gradients as $k => $gradient ) {
							$gradient_string = '';
							foreach ( $gradient['colors'] as $stop => $color ) {
								$gradient_string .= ', ' . $color . ' ' . $stop . '%';
							}
							$css = $gradient['style'] . '-gradient(' . $gradient['direction'] . $gradient_string . ')';
							?>
                            <div class="clickwhale-style-item clickwhale-gradient-item">
                                <input id="gradientStyle<?php echo $k ?>"
                                       type="radio"
                                       name="styles[bg_gradient]"
                                       value="<?php echo $k ?>"
									<?php checked( $current_gradient, $k ) ?>
                                >
                                <label for="gradientStyle<?php echo $k ?>"
                                       style="background: <?php echo $css ?>"></label>
                            </div>
							<?php
						}
					}
					?>
                    <div class="clickwhale-style-item clickwhale-gradient-item gradient-style-custom">
                        <input id="gradientStyleCustom"
                               type="radio"
                               name="styles[bg_gradient]"
                               value="custom"
							<?php checked( $current_gradient, 'custom' ) ?>
                        >
                        <label for="gradientStyleCustom"></label>
                    </div>
                </div>
                <div id="bgGradientsCustomWrap">
                    <div class="clickwhale-gradients-custom-grid">
                        <div class="gradient-style-custom-start">
                            <p class="description">Start Color</p>
							<?php $bg_gradient_start = $styles['bg_gradient_custom']['start'] ?? '#fdd231' ?>
                            <input name="styles[bg_gradient_custom][start]"
                                   class="cw-color-control"
                                   type="text"
                                   value="<?php echo esc_attr( $bg_gradient_start ) ?>"/>
                        </div>
                        <div class="gradient-style-custom-end">
                            <p class="description">End Color</p>
							<?php $bg_gradient_end = $styles['bg_gradient_custom']['end'] ?? '#efb300' ?>
                            <input name="styles[bg_gradient_custom][end]"
                                   class="cw-color-control"
                                   type="text"
                                   value="<?php echo esc_attr( $bg_gradient_end ) ?>"/>
                        </div>
                        <div class="gradient-style-custom-style">
                            <p class="description">Gradient Style</p>
							<?php
							echo Helper::render_control(
								array(
									'control' => 'select',
									'id'      => 'bg_gradient_custom_style',
									'name'    => 'styles[bg_gradient_custom][style]',
									'value'   => $styles['bg_gradient_custom']['style'] ?? '',
									'options' => array(
										'linear' => __( 'Linear', CLICKWHALE_PRO_SLUG ),
										'radial' => __( 'Radial', CLICKWHALE_PRO_SLUG ),
										'conic'  => __( 'Conic', CLICKWHALE_PRO_SLUG )
									),
									'default' => 'linear'
								)
							);
							?>
                        </div>
                        <div class="gradient-style-custom-direction">
                            <p class="description">Gradient Direction</p>
							<?php
							echo Helper::render_control(
								array(
									'control' => 'select',
									'id'      => 'bg_gradient_custom_direction',
									'name'    => 'styles[bg_gradient_custom][direction]',
									'value'   => $styles['bg_gradient_custom']['direction'] ?? '',
									'options' => array(
										'top'    => __( 'Top', CLICKWHALE_PRO_SLUG ),
										'right'  => __( 'Right', CLICKWHALE_PRO_SLUG ),
										'center' => __( 'Center', CLICKWHALE_PRO_SLUG ),
										'bottom' => __( 'Bottom', CLICKWHALE_PRO_SLUG ),
										'left'   => __( 'Left', CLICKWHALE_PRO_SLUG )
									),
									'default' => 'top'
								)
							);
							?>
                        </div>
                        <div class="gradient-style-custom-preview"></div>
                    </div>
                </div>
            </td>
        </tr>
        <tr class="form-field for-style for-pattern-style">
            <th scope="row"></th>
            <td>
                <div class="clickwhale-styles-grid clickwhale-patterns-grid">
					<?php
					$patterns        = Linkpage_Styles_Helper::get_patterns();
					$current_pattern = $styles['bg_pattern'] ?? '';

					foreach ( $patterns as $pattern ) {
						?>
                        <div class="clickwhale-style-item clickwhale-pattern-item">
                            <input id="<?php echo $pattern['name'] ?>"
                                   type="radio"
                                   name="styles[bg_pattern]"
                                   value="<?php echo $pattern['name'] ?>"
								<?php checked( $current_pattern, $pattern['name'] ) ?>
                            >
                            <label for="<?php echo $pattern['name'] ?>"
                                   style="background-image: url(<?php echo $pattern['url'] ?>);"></label>
                        </div>
					<?php } ?>
                </div>
            </td>
        </tr>
        <tr class="form-field for-style for-image-style">
            <th scope="row"></th>
            <td>
                <div id="bgImageWrap" class="clickwhale-background-image">
                    <div class="background-field image-field">
						<?php
						$bg_id = $styles['bg_image']['image'] ?? '';
						if ( $bg_id ) {
							$bg_image = wp_get_attachment_image_src( $bg_id );
							?>
                            <a href="#" class="linkpage-image-upload">
                                <img alt="linkpage-background-image" src="<?php echo esc_url( $bg_image[0] ) ?>"/>
                            </a>
                            <a href="#"
                               class="button linkpage-image-remove"><?php _e( 'Remove image',
									CLICKWHALE_PRO_SLUG ) ?></a>
                            <input type="hidden" name="styles[bg_image][image]"
                                   value="<?php echo esc_attr( $bg_id ); ?>">
						<?php } else { ?>
                            <a href="#" class="button linkpage-image-upload">
								<?php _e( 'Upload image', CLICKWHALE_PRO_SLUG ) ?>
                            </a>
                            <a href="#" class="button linkpage-image-remove" style="display: none;">
								<?php _e( 'Remove image', CLICKWHALE_PRO_SLUG ) ?>
                            </a>
                            <input type="hidden" name="styles[bg_image][image]" value="">
						<?php } ?>
                    </div>
                    <div class="clickwhale-background-image--controls">
                        <div>
                            <p class="description"><?php _e( 'Horisontal position', CLICKWHALE_PRO_SLUG ) ?></p>
							<?php
							echo Helper::render_control(
								array(
									'control' => 'select',
									'id'      => 'background-position-x',
									'name'    => 'styles[bg_image][x]',
									'value'   => $styles['bg_image']['x'] ?? 'center',
									'options' => array(
										'left'   => __( 'Left', CLICKWHALE_PRO_SLUG ),
										'center' => __( 'Center', CLICKWHALE_PRO_SLUG ),
										'right'  => __( 'Right', CLICKWHALE_PRO_SLUG )
									),
									'default' => 'center'
								)
							);
							?>
                        </div>
                        <div>
                            <p class="description"><?php _e( 'Vertical position', CLICKWHALE_PRO_SLUG ) ?></p>
							<?php
							echo Helper::render_control(
								array(
									'control' => 'select',
									'id'      => 'background-position-y',
									'name'    => 'styles[bg_image][y]',
									'value'   => $styles['bg_image']['y'] ?? 'top',
									'options' => array(
										'top'    => __( 'Top', CLICKWHALE_PRO_SLUG ),
										'center' => __( 'Center', CLICKWHALE_PRO_SLUG ),
										'bottom' => __( 'Bottom', CLICKWHALE_PRO_SLUG )
									),
									'default' => 'center'
								)
							);
							?>
                        </div>
                        <div>
                            <p class="description"><?php _e( 'Repeat', CLICKWHALE_PRO_SLUG ) ?></p>
							<?php
							echo Helper::render_control(
								array(
									'control' => 'select',
									'id'      => 'background-repeat',
									'name'    => 'styles[bg_image][repeat]',
									'value'   => $styles['bg_image']['repeat'] ?? 'no-repeat',
									'options' => array(
										'no-repeat' => __( 'No repeat', CLICKWHALE_PRO_SLUG ),
										'repeat'    => __( 'Repeat', CLICKWHALE_PRO_SLUG )
									),
									'default' => 'no-repeat'
								)
							);
							?>
                        </div>
                        <div>
                            <p class="description"><?php _e( 'Size', CLICKWHALE_PRO_SLUG ) ?></p>
							<?php
							echo Helper::render_control(
								array(
									'control' => 'select',
									'id'      => 'background-size',
									'name'    => 'styles[bg_image][size]',
									'value'   => $styles['bg_image']['size'] ?? 'cover',
									'options' => array(
										'cover'   => __( 'Cover', CLICKWHALE_PRO_SLUG ),
										'contain' => __( 'Contain', CLICKWHALE_PRO_SLUG ),
										'auto'    => __( 'Auto', CLICKWHALE_PRO_SLUG )
									),
									'default' => 'cover'
								)
							);
							?>
                        </div>
                    </div>
                </div>
            </td>
        </tr>
		<?php
	}

	public function template_admin_cw_feed( $args ) {
		$template = new Clickwhale_Linkpage_Content_Templates();
		$defaults = $template->get_template_data_defaults();
		$active   = false;

		if ( isset( $args['data'] ) && $args['data'] ) {
			$defaults['data'] = $args['data'];
		} else {
			$active                          = true;
			$defaults['data']['type']        = $args['type'];
			$defaults['data']['taxonomy']    = 'category';
			$defaults['data']['taxonomy_id'] = 0;
			$defaults['data']['quantity']    = 3;
			$defaults['data']['layout']      = 'list';
			$defaults['data']['is_title']    = '';
			unset( $defaults['title'], $defaults['image'] );
		}
		$data           = $defaults['data'];
		$taxonomy_terms = [];
		$terms          = get_terms( array(
			'taxonomy'   => $data['taxonomy'],
			'hide_empty' => false
		) );

		foreach ( $terms as $term ) {
			$taxonomy_terms[] = array(
				'id'   => $term->term_id,
				'name' => $term->name
			);
		}

		if ( $data['layout'] === 'list-sm' ) {
			$checked  = false;
			$disabled = 'disabled';
		} else {
			$checked  = checked( $data['is_title'] ?? 0, 1, false );
			$disabled = '';
		}

		ob_start();
		?>
        <div class="linkpage-row row--<?php echo $data['type'] ?> no-image" id="row-<?php echo $data['id'] ?>">
            <div class="linkpage-row--top">
				<?php $template->get_template_row_start( $data['id'], $data['is_active'] ?? '' ); ?>
                <div class="linkpage-row--content">
                    <div class="linkpage-row--link">
						<?php if ( isset( $data['taxonomy_id'] ) && $data['taxonomy_id'] ) { ?>
                            <strong>
								<?php _e( 'Feed: ', CLICKWHALE_PRO_SLUG ) ?>
								<?php echo get_term( $data['taxonomy_id'], $data['taxonomy'] )->name; ?>
                            </strong>
                            <span><?php _e( 'Layout: ', CLICKWHALE_PRO_SLUG ) ?><?php echo $data['layout'] ?></span>
						<?php } else { ?>
                            <strong><?php _e( 'Feed', CLICKWHALE_PRO_SLUG ); ?></strong>
						<?php } ?>
                    </div>
                </div>
				<?php $template->get_template_row_end( $data['type'] ); ?>
            </div><!-- ./linkpage-row--top -->
            <div class="linkpage-row--bottom <?php echo $active ? 'active' : '' ?>">
				<?php echo $template->get_template_hidden_field( $data, array( 'taxonomy' ) ); ?>
                <div class="linkpage-row--bottom--control-wrap">
                    <label for="links[<?php echo esc_attr( $data['id'] ) ?>][taxonomy_id]">
						<?php _e( 'Category', CLICKWHALE_PRO_SLUG ); ?>
                    </label>
                    <div>
                        <select name="links[<?php echo esc_attr( $data['id'] ) ?>][taxonomy_id]">
							<?php
							if ( $taxonomy_terms ) {
								foreach ( $taxonomy_terms as $taxonomy_term ) {
									?>
                                    <option value="<?php echo esc_attr( $taxonomy_term['id'] ) ?>"
										<?php echo selected( $data['taxonomy_id'], $taxonomy_term['id'] ) ?>>
										<?php echo $taxonomy_term['name'] ?>
                                    </option>
									<?php
								}
							}
							?>
                        </select>
                    </div>
                </div>
                <div class="linkpage-row--bottom--control-wrap">
                    <label for="links[<?php echo esc_attr( $data['id'] ) ?>][quantity]">
						<?php _e( 'Number of Posts', CLICKWHALE_PRO_SLUG ); ?>
                    </label>
                    <div>
                        <input name="links[<?php echo esc_attr( $data['id'] ) ?>][quantity]"
                               value="<?php echo $data['quantity'] ?? 3 ?>"
                               type="number"
                               min="-1"
                               max="100"
                               required/>
                    </div>
                </div>
                <div class="linkpage-row--bottom--control-wrap">
                    <label for="links[<?php echo esc_attr( $data['id'] ) ?>][layout]">
						<?php _e( 'Layout', CLICKWHALE_PRO_SLUG ); ?>
                    </label>
                    <div>
                        <select name="links[<?php echo esc_attr( $data['id'] ) ?>][layout]">
                            <option value="list-lg" <?php echo selected( $data['layout'], 'list-lg' ) ?>>
								<?php _e( 'List with large thumbnail', CLICKWHALE_PRO_SLUG ) ?>
                            </option>
                            <option value="list-sm" <?php echo selected( $data['layout'], 'list-sm' ) ?>>
								<?php _e( 'List with small thumbnail', CLICKWHALE_PRO_SLUG ) ?>
                            </option>
                            <option value="grid-2" <?php echo selected( $data['layout'], 'grid-2' ) ?>>
								<?php _e( 'Grid (2 columns)', CLICKWHALE_PRO_SLUG ) ?>
                            </option>
                            <option value="grid-3" <?php echo selected( $data['layout'], 'grid-3' ) ?>>
								<?php _e( 'Grid (3 columns)', CLICKWHALE_PRO_SLUG ) ?>
                            </option>
                        </select>
                    </div>
                </div>

                <div class="linkpage-row--bottom--control-wrap">
                    <label>
						<?php _e( 'Hide Title', CLICKWHALE_PRO_SLUG ); ?>
                    </label>
                    <div>
                        <input type="checkbox"
                               id="is_<?php echo esc_attr( $data['id'] ) ?>_title"
                               name="links[<?php echo esc_attr( $data['id'] ) ?>][is_title]"
                               value="1"
							<?php echo $checked . ' ' . $disabled ?>>
                        <label for="is_<?php echo esc_attr( $data['id'] ) ?>_title">
							<?php _e( 'Check to hide title on the frontend', CLICKWHALE_PRO_SLUG ); ?>
                        </label>
                    </div>
                </div>
            </div><!-- ./linkpage-row--bottom -->
        </div>
		<?php
		$result = ob_get_contents();
		ob_end_clean();

		return $result;
	}

	public function template_public_cw_feed( $args ): string {
		ob_start();
		$layout    = $args['data']['layout'];
		$thumbSize = $args['type'] === 'list-lg' ? 'large' : 'medium';
		$feedQuery = new WP_Query( array(
			'posts_per_page' => $args['data']['quantity'],
			'tax_query'      => array(
				array(
					'taxonomy' => $args['data']['taxonomy'],
					'terms'    => $args['data']['taxonomy_id'],
				)
			),
			'status'         => 'publish'
		) );
		if ( $feedQuery->have_posts() ) {
			?>
            <div class="linkpage-public-row linkpage-public-row--<?php echo $args['type'] ?>"
                 data-type="<?php echo $args['type'] ?>">
                <div class="linkpage-feed-layout--<?php echo $layout ?>">
					<?php while ( $feedQuery->have_posts() ) {
						$feedQuery->the_post(); ?>
                        <div class="linkpage-feed--entry">
							<?php if ( has_post_thumbnail() ) { ?>
                                <a class="linkpage-feed--image" href="<?php the_permalink() ?>" target="_blank">
									<?php the_post_thumbnail( $thumbSize ); ?>
                                </a>
								<?php if ( $layout === 'list-sm' || ! isset( $args['data']['is_title'] ) ) { ?>
                                    <a class="linkpage-feed--title" href="<?php the_permalink() ?>" target="_blank">
										<?php the_title(); ?>
                                    </a>
								<?php } ?>
							<?php } ?>
                        </div>
					<?php } ?>
                </div>
            </div>
			<?php
			wp_reset_postdata();
		}
		$result = ob_get_contents();
		ob_end_clean();

		return $result;
	}

	/**
	 * @since 1.0.2
	 */
	public function template_admin_cw_social( $args ) {
		$template = new Clickwhale_Linkpage_Content_Templates();
		$defaults = $template->get_template_data_defaults();

		if ( isset( $args['data'] ) && $args['data'] ) {
			$defaults['data'] = $args['data'];
		} else {
			$defaults['data']['type'] = $args['type'];
			unset( $defaults['title'], $defaults['image'] );
		}

		$data = $defaults['data'];

		ob_start();
		?>
        <div class="linkpage-row row--<?php echo $data['type'] ?> no-image" id="row-<?php echo $data['id'] ?>">
            <div class="linkpage-row--top">
				<?php $template->get_template_row_start( $data['id'], $data['is_active'] ?? '' ); ?>
                <div class="linkpage-row--content">
                    <div class="linkpage-row--link">
                        <strong><?php _e( 'Social Profiles', 'clickwhale-pro' ); ?></strong>
                    </div>
                </div>
				<?php $template->get_template_row_end( $data['type'], false ); ?>
            </div><!-- ./linkpage-row--top -->
            <div class="linkpage-row--bottom">
				<?php echo $template->get_template_hidden_field( $data ); ?>
            </div><!-- ./linkpage-row--bottom -->
        </div>
		<?php
		$result = ob_get_contents();
		ob_end_clean();

		return $result;
	}

	/**
	 * @since 1.0.2
	 */
	public function template_public_cw_social( $args ) {
		$socials = ! empty( $args['post']['social'] ) ? maybe_unserialize( $args['post']['social'] ) : false;
		if ( ! $socials ) {
			return false;
		}

		$social_svg = array(
			'email'     => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 49.707 49.707" fill="currentColor"><path d="M24.853,0A24.853,24.853,0,1,1,0,24.853,24.853,24.853,0,0,1,24.853,0ZM40.63,34.488V16.017l-9.236,9.236Zm-29.579.376H38.657L30.22,26.427,27,29.642a.831.831,0,0,1-.587.243H23.291a.83.83,0,0,1-.587-.243l-3.216-3.215L11.05,34.864ZM9.077,16.016V34.489l9.236-9.236Zm30.378-1.174h-29.2L23.634,28.225h2.438Z" fill-rule="evenodd"/></svg>',
			'facebook'  => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 49.652 49.652" fill="currentColor"><path d="M24.826 0a24.826 24.826 0 1 0 24.826 24.826A24.854 24.854 0 0 0 24.826 0ZM31 25.7h-4.039v14.4h-5.985V25.7h-2.845v-5.088h2.845v-3.291c0-2.357 1.12-6.04 6.04-6.04l4.435.017v4.939h-3.219a1.219 1.219 0 0 0-1.269 1.386v2.99h4.56Z"/></svg>',
			'instagram' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 49.652 49.652" fill="currentColor"><path d="M24.825 29.8a4.962 4.962 0 1 0-4.968-4.971 4.978 4.978 0 0 0 4.968 4.971Z"/><path d="M35.678 18.746V13.96h-.623l-4.164.013.016 4.787Z"/><path d="M24.826 0a24.826 24.826 0 1 0 24.826 24.826A24.854 24.854 0 0 0 24.826 0Zm14.119 21.929v11.56a5.463 5.463 0 0 1-5.457 5.458H16.164a5.462 5.462 0 0 1-5.457-5.458V16.165a5.462 5.462 0 0 1 5.457-5.457h17.323a5.463 5.463 0 0 1 5.458 5.457Z"/><path d="M32.549 24.826a7.723 7.723 0 1 1-14.877-2.9h-4.215v11.56a2.706 2.706 0 0 0 2.706 2.7h17.323a2.707 2.707 0 0 0 2.706-2.7V21.929h-4.217a7.617 7.617 0 0 1 .574 2.9Z"/></svg>',
			'linkedin'  => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 49.652 49.652" fill="currentColor"><path d="M24.826,0A24.826,24.826,0,1,0,49.652,24.826,24.826,24.826,0,0,0,24.826,0ZM17.607,37.892H12.191V20.446h5.416ZM14.9,18.064A3.152,3.152,0,1,1,18.035,14.9,3.152,3.152,0,0,1,14.9,18.064Zm23,19.828H32.48V29.4c0-2.025-.039-4.622-2.816-4.622s-3.267,2.2-3.267,4.475v8.64H21V20.446h5.2v2.378h.075A5.688,5.688,0,0,1,31.4,20c5.481,0,6.491,3.613,6.491,8.3Z"/></svg>',
			'pinterest' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" fill="currentColor"><path d="m256.612 13.686c-137.055 0-248.412 110.5371-248.412 247.8027 0 137.2588 111.357 247.7959 248.412 247.7959s247.188-110.5371 247.188-247.7959c0-137.2656-110.1334-247.8027-247.188-247.8027zm19.579 314.61c-18.3553-1.2168-25.6976-10.9307-40.382-19.4346-7.3422 41.2959-17.1317 80.165-45.277 100.8164-8.5658-61.9473 12.2368-108.1035 22.0267-157.91-17.1322-27.9378 2.4473-86.242 37.9347-71.6678 44.0534 17.0078-37.9347 106.8935 17.1318 117.8242 58.7377 12.1474 81.9881-100.8164 46.501-137.2588-52.62-52.2334-151.7395-1.2168-139.5023 74.0947 3.6709 18.2246 22.0267 24.2949 8.5659 49.8067-34.2639-7.2871-44.0534-34.0157-42.83-68.0245 2.4472-57.0937 51.3955-97.18 101.5675-103.25 62.4087-7.2871 121.1465 23.0782 128.4887 81.3819 9.79 66.8144-28.1453 138.4824-94.2253 133.6221z" fill-rule="evenodd"/></svg>',
			'rss'       => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 152 152" fill="currentColor"><path d="M76 0a76 76 0 1 0 76 76A76 76 0 0 0 76 0zM47.87 116a11.88 11.88 0 0 1 0-23.75 11.88 11.88 0 0 1 0 23.75zm39.89 0H73.69c.06-.78.12-1.55.12-2.34a35.73 35.73 0 0 0-35.47-35.47c-.79 0-1.56.06-2.34.12V64.24c.78 0 1.55-.12 2.34-.12a49.82 49.82 0 0 1 49.54 49.54c0 .79-.09 1.56-.12 2.34zm28.12 0h-14.06c0-.78.12-1.55.12-2.34a63.89 63.89 0 0 0-63.6-63.6c-.79 0-1.56.09-2.34.12V36.12c.79 0 1.55-.12 2.34-.12A77.88 77.88 0 0 1 116 113.66c0 .79-.1 1.55-.12 2.34z"/></svg>',
			'threads'   => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 49.65 49.65" fill="currentColor"><path d="M-1911.175,3581.65a24.673,24.673,0,0,1-9.663-1.951,24.745,24.745,0,0,1-7.891-5.32,24.746,24.746,0,0,1-5.32-7.891,24.672,24.672,0,0,1-1.951-9.663,24.671,24.671,0,0,1,1.951-9.663,24.743,24.743,0,0,1,5.32-7.891,24.745,24.745,0,0,1,7.891-5.32,24.673,24.673,0,0,1,9.663-1.951,24.674,24.674,0,0,1,9.663,1.951,24.741,24.741,0,0,1,7.891,5.32,24.742,24.742,0,0,1,5.32,7.891,24.668,24.668,0,0,1,1.951,9.663,24.67,24.67,0,0,1-1.951,9.663,24.745,24.745,0,0,1-5.32,7.891,24.741,24.741,0,0,1-7.891,5.32A24.674,24.674,0,0,1-1911.175,3581.65Zm.394-40.66a16.242,16.242,0,0,0-7.251,1.6c-3.45,1.8-5.811,5.445-6.648,10.27a23.609,23.609,0,0,0-.145,6.8,22.738,22.738,0,0,0,1.743,6.436,10.483,10.483,0,0,0,4.926,4.861,12.478,12.478,0,0,0,7.121,1.712c3.752,0,6.619-.818,8.521-2.43a10.315,10.315,0,0,0,3.435-5.451,7.577,7.577,0,0,0-1.032-6.016,8.477,8.477,0,0,0-3.875-3.343l-.155-1.183a6.607,6.607,0,0,0-2.337-4.365,6.764,6.764,0,0,0-4.277-1.489,8.064,8.064,0,0,0-3.833.85,5.4,5.4,0,0,0-2.266,2.256l2.357,1.529c.7-1.166,1.982-1.732,3.926-1.732a3.351,3.351,0,0,1,2.526,1.141,4.842,4.842,0,0,1,1.107,2.149l-.14,0h-.143c-.458,0-.94-.015-1.406-.029h-.041c-.452-.014-.964-.03-1.458-.03-3.2,0-4.5.824-5.337,1.631a5.182,5.182,0,0,0-1.713,3.844,4.949,4.949,0,0,0,1.815,3.958,6.458,6.458,0,0,0,4.108,1.375,7.11,7.11,0,0,0,7.183-6.651,5.215,5.215,0,0,1,2.344,3.934,6.33,6.33,0,0,1-2.168,4.95,8.033,8.033,0,0,1-3.362,1.873,15.424,15.424,0,0,1-4.256.5,10.942,10.942,0,0,1-4.939-1.236,9.369,9.369,0,0,1-3.715-3.338,16.922,16.922,0,0,1-1.935-9.228,17.22,17.22,0,0,1,1.44-6.6,9.27,9.27,0,0,1,4.278-4.581,12.721,12.721,0,0,1,5.771-1.034,10.467,10.467,0,0,1,6.645,2.042,10.873,10.873,0,0,1,3.592,5.853l3.029-.865a20.28,20.28,0,0,0-2.658-5.006,12.32,12.32,0,0,0-4.326-3.59A14.552,14.552,0,0,0-1910.781,3540.99Zm-.537,21.494c-1.439,0-3.042-1.046-3.042-2.547a2.266,2.266,0,0,1,1.145-1.969,5.676,5.676,0,0,1,3.073-.7,13.614,13.614,0,0,1,3.206.406,6.033,6.033,0,0,1-.939,3.4C-1908.5,3562.009-1909.657,3562.484-1911.318,3562.484Z" transform="translate(1936 -3532)"/></svg>',
			'telegram'  => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 50 50" fill="currentColor"><path d="M-1911,3582a24.842,24.842,0,0,1-9.731-1.965,24.92,24.92,0,0,1-7.947-5.358,24.92,24.92,0,0,1-5.358-7.947A24.847,24.847,0,0,1-1936,3557a24.847,24.847,0,0,1,1.964-9.731,24.919,24.919,0,0,1,5.358-7.947,24.918,24.918,0,0,1,7.947-5.358A24.842,24.842,0,0,1-1911,3532a24.839,24.839,0,0,1,9.731,1.965,24.916,24.916,0,0,1,7.946,5.358,24.917,24.917,0,0,1,5.358,7.947A24.847,24.847,0,0,1-1886,3557a24.847,24.847,0,0,1-1.964,9.731,24.921,24.921,0,0,1-5.358,7.947,24.919,24.919,0,0,1-7.946,5.358A24.839,24.839,0,0,1-1911,3582Zm-2.086-18.408h0l6.358,4.685a2.151,2.151,0,0,0,1.022.32c.639,0,1.07-.471,1.281-1.4l4.173-19.681a1.956,1.956,0,0,0-.227-1.707,1.11,1.11,0,0,0-.9-.4,1.816,1.816,0,0,0-.635.124l-24.534,9.452c-.829.324-1.3.747-1.283,1.16.012.351.376.664,1,.858l6.272,1.963,14.569-9.174a1.3,1.3,0,0,1,.682-.241c.151,0,.26.05.292.135s-.026.224-.178.36l-11.786,10.65-.456,6.475a1.606,1.606,0,0,0,1.278-.624l3.068-2.95Z" transform="translate(1936 -3532)"/></svg>',
			'tiktok'    => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" fill="currentColor"><path d="m256 0c-141.363 0-256 114.637-256 256s114.637 256 256 256 256-114.637 256-256-114.637-256-256-256zm128.43 195.873v34.663c-16.345.006-32.226-3.197-47.204-9.516-9.631-4.066-18.604-9.305-26.811-15.636l.246 106.693c-.103 24.025-9.608 46.598-26.811 63.601-14 13.84-31.74 22.641-50.968 25.49-4.518.669-9.116 1.012-13.766 1.012-20.583 0-40.124-6.668-56.109-18.97-3.008-2.316-5.885-4.827-8.624-7.532-18.644-18.427-28.258-43.401-26.639-69.674 1.235-19.999 9.242-39.072 22.59-54.021 17.66-19.782 42.366-30.762 68.782-30.762 4.65 0 9.248.349 13.766 1.018v12.816 35.652c-4.284-1.413-8.859-2.19-13.623-2.19-24.134 0-43.659 19.69-43.298 43.842.229 15.453 8.67 28.961 21.12 36.407 5.851 3.5 12.582 5.668 19.765 6.062 5.628.309 11.032-.475 16.036-2.127 17.243-5.696 29.682-21.892 29.682-40.994l.057-71.447v-130.44h47.736c.046 4.73.526 9.345 1.418 13.817 3.603 18.101 13.806 33.805 28.006 44.511 12.382 9.339 27.8 14.875 44.511 14.875.011 0 .149 0 .137-.011v12.861z"/></svg>',
			'twitter'   => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 49.65 49.65" fill="currentColor"><path d="M-1911.175,3581.65a24.673,24.673,0,0,1-9.663-1.951,24.745,24.745,0,0,1-7.891-5.32,24.746,24.746,0,0,1-5.32-7.891,24.672,24.672,0,0,1-1.951-9.663,24.671,24.671,0,0,1,1.951-9.663,24.743,24.743,0,0,1,5.32-7.891,24.745,24.745,0,0,1,7.891-5.32,24.673,24.673,0,0,1,9.663-1.951,24.674,24.674,0,0,1,9.663,1.951,24.741,24.741,0,0,1,7.891,5.32,24.742,24.742,0,0,1,5.32,7.891,24.668,24.668,0,0,1,1.951,9.663,24.67,24.67,0,0,1-1.951,9.663,24.745,24.745,0,0,1-5.32,7.891,24.741,24.741,0,0,1-7.891,5.32A24.674,24.674,0,0,1-1911.175,3581.65Zm-1.4-21.032h0l6.74,8.811h8.607l-10.991-14.531,9.343-10.679h-4.277l-7.049,8.056-6.092-8.056h-8.825l10.544,13.788-9.993,11.422h4.279l7.713-8.811Zm10.291,6.252h-2.37l-15.47-20.225h2.543l15.3,20.223Z" transform="translate(1936 -3532)"/></svg>',
			'youtube'   => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" fill="currentColor"><path d="m224.113281 303.960938 83.273438-47.960938-83.273438-47.960938zm0 0"/><path d="m256 0c-141.363281 0-256 114.636719-256 256s114.636719 256 256 256 256-114.636719 256-256-114.636719-256-256-256zm159.960938 256.261719s0 51.917969-6.585938 76.953125c-3.691406 13.703125-14.496094 24.507812-28.199219 28.195312-25.035156 6.589844-125.175781 6.589844-125.175781 6.589844s-99.878906 0-125.175781-6.851562c-13.703125-3.6875-24.507813-14.496094-28.199219-28.199219-6.589844-24.769531-6.589844-76.949219-6.589844-76.949219s0-51.914062 6.589844-76.949219c3.6875-13.703125 14.757812-24.773437 28.199219-28.460937 25.035156-6.589844 125.175781-6.589844 125.175781-6.589844s100.140625 0 125.175781 6.851562c13.703125 3.6875 24.507813 14.496094 28.199219 28.199219 6.851562 25.035157 6.585938 77.210938 6.585938 77.210938zm0 0"/></svg>',
			'whatsapp'  => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 50 50" fill="currentColor"><path id="Ausschluss_2" data-name="Ausschluss 2" d="M-1911,3582a24.842,24.842,0,0,1-9.731-1.965,24.92,24.92,0,0,1-7.947-5.358,24.92,24.92,0,0,1-5.358-7.947A24.847,24.847,0,0,1-1936,3557a24.847,24.847,0,0,1,1.964-9.731,24.919,24.919,0,0,1,5.358-7.947,24.918,24.918,0,0,1,7.947-5.358A24.842,24.842,0,0,1-1911,3532a24.839,24.839,0,0,1,9.731,1.965,24.916,24.916,0,0,1,7.946,5.358,24.917,24.917,0,0,1,5.358,7.947A24.847,24.847,0,0,1-1886,3557a24.847,24.847,0,0,1-1.964,9.731,24.921,24.921,0,0,1-5.358,7.947,24.919,24.919,0,0,1-7.946,5.358A24.839,24.839,0,0,1-1911,3582Zm.056-40a14.921,14.921,0,0,0-14.93,14.866,14.838,14.838,0,0,0,1.994,7.432l-2.12,7.7,7.919-2.066a15.106,15.106,0,0,0,7.137,1.809h.008a14.924,14.924,0,0,0,14.935-14.87,14.738,14.738,0,0,0-4.369-10.514l.019-.049A15.185,15.185,0,0,0-1910.944,3542Zm.028,27.189h-.028a12.486,12.486,0,0,1-6.319-1.725l-.45-.267-4.687,1.219,1.256-4.557-.3-.468a12.348,12.348,0,0,1-1.9-6.575,12.406,12.406,0,0,1,12.428-12.356,12.332,12.332,0,0,1,8.776,3.638,12.217,12.217,0,0,1,3.636,8.737,12.407,12.407,0,0,1-12.418,12.356Zm-5.309-19.24a1.416,1.416,0,0,0-1,.449l-.078.085a4.1,4.1,0,0,0-1.228,3.009,6.78,6.78,0,0,0,1.491,3.8l.033.045c.012.016.033.046.063.088l.008.012a15.791,15.791,0,0,0,6.3,5.506c.767.323,1.391.535,1.891.705l.009,0,.23.078a4.729,4.729,0,0,0,1.453.22,6.268,6.268,0,0,0,.9-.069,3.866,3.866,0,0,0,2.52-1.782,3.126,3.126,0,0,0,.225-1.782c-.083-.15-.286-.241-.595-.378l-.118-.053-.011.095c-.433-.217-2.211-1.086-2.55-1.208a1.226,1.226,0,0,0-.4-.1.516.516,0,0,0-.44.284c-.287.43-1.172,1.443-1.18,1.453a.517.517,0,0,1-.4.2,1.006,1.006,0,0,1-.407-.111c-.059-.03-.137-.063-.236-.106l-.01,0a9.675,9.675,0,0,1-2.757-1.745,11.282,11.282,0,0,1-2.076-2.587c-.221-.383-.017-.588.163-.769.127-.126.279-.31.412-.473l.151-.182a2.442,2.442,0,0,0,.3-.485l0,0,.067-.13a.683.683,0,0,0-.031-.654c-.053-.107-.318-.748-.573-1.366l0-.009c-.215-.521-.437-1.059-.576-1.383-.232-.564-.469-.642-.691-.642l-.079,0-.07,0C-1915.727,3549.948-1915.98,3549.948-1916.225,3549.948Z" transform="translate(1936 -3532)"/></svg>'
		);

		ob_start();
		?>
        <div class="linkpage-public-row linkpage-public-row--<?php echo $args['type'] ?>"
             data-type="<?php echo $args['type'] ?>">
            <ul class="linkpage-public--social">
				<?php foreach ( $socials['profiles'] as $k => $v ) { ?>
					<?php
					if ( ! $v )
						continue
					?>
                    <li>
						<?php
						switch ( $k ) {
							case 'email':
								$href   = "mailto:{$v}";
								$target = "_self";
								break;
							case 'whatsapp':
								$phone = preg_replace( '/[^A-Za-z0-9]/', '', $v );
								$href  = "https://wa.me/{$phone}";
								break;
							default:
								$href   = $v;
								$target = "_blank";
						}
						?>
                        <a href="<?php echo $href ?>" target="<?php echo $target ?>">
							<?php echo $social_svg[ $k ] ?>
                        </a>
                    </li>
				<?php } ?>
            </ul>
        </div>
		<?php
		$result = ob_get_contents();
		ob_end_clean();

		return $result;
	}

	public function linkpage_after_styles_tables() {
		return new Clickwhale_Pro_Linkpage_Preview();
	}

	public function linkpage_data_before_save( $data ) {
		return $data;
	}

	public function admin_scripts() {

		if ( isset( $_GET['page'] ) && $_GET['page'] === 'clickwhale-edit-linkpage' ) {
			?>
            <script type='text/javascript'>
                jQuery(document).ready(function () {

                    // Social Profiles Sortable
                    jQuery('#lp-tab-social table tbody').sortable({
                        placeholder: "ui-state-highlight",
                        handle: '.linkpage-row--drag',
                    }).disableSelection();

                    jQuery('#clickwhale-tabs').on('tabsactivate', function (event, ui) {
                        if (jQuery(ui.newPanel[0]).attr('id') === 'lp-tab-social') {
                            jQuery('#lp-tab-social table tbody').sortable({
                                placeholder: "ui-state-highlight",
                                handle: '.linkpage-row--drag',
                            }).disableSelection();
                        }
                    });

                    const
                        customGradientStart = jQuery('[name="styles[bg_gradient_custom][start]"]'),
                        customGradientEnd = jQuery('[name="styles[bg_gradient_custom][end]"]'),
                        customGradientPreview = jQuery('.gradient-style-custom-preview');

                    // set value for background style radio
                    if (jQuery('[name="styles[bg_style]')) {
                        const bgStyle = jQuery('[name="styles[bg_style]"]:checked').val();
                        jQuery('.form-field.for-style').hide();
                        jQuery(`.form-field.for-${bgStyle}-style`).show();
                    }

                    // show custom gradient block if selected
                    if (jQuery('[name="styles[bg_gradient]') && jQuery('[name="styles[bg_gradient]"]:checked').val() === 'custom') {
                        jQuery('#bgGradientsCustomWrap').show();
                        customGradientPreview.css('background', set_custom_gradient());
                    }

                    jQuery(document)
                        .on('change', 'select[name$="[layout]"]', function () {
                            if (this.value === 'list-sm') {
                                jQuery(this)
                                    .closest('.linkpage-row--bottom')
                                    .find('[name$="[is_title]"]')
                                    .prop({
                                        'disabled': true,
                                        'checked': false
                                    });
                            } else {
                                jQuery(this)
                                    .closest('.linkpage-row--bottom')
                                    .find('[name$="[is_title]"]')
                                    .prop('disabled', false);
                            }
                        })
                        .on('change', '[name="styles[bg_style]"]', function () {
                            const bgStyle = jQuery(this).val();
                            jQuery('.form-field.for-style').hide();
                            jQuery(`.form-field.for-${bgStyle}-style`).show();
                        })
                        .on('change', '[name="styles[bg_gradient]"]', function () {
                            if (jQuery(this).val() === 'custom') {
                                jQuery('#bgGradientsCustomWrap').show();
                                customGradientPreview.css('background', set_custom_gradient());
                            } else {
                                jQuery('#bgGradientsCustomWrap').hide();
                            }
                        })
                        .on('change', 'select', function () {
                            customGradientPreview.css('background', set_custom_gradient());
                        });

                    // change event for color picker
                    // See http://automattic.github.io/Iris
                    customGradientStart.wpColorPicker({
                        change: function (event, ui) {
                            customGradientPreview.css('background', set_custom_gradient(ui.color.toString()));
                        }
                    });

                    customGradientEnd.wpColorPicker({
                        change: function (event, ui) {
                            customGradientPreview.css('background', set_custom_gradient('', ui.color.toString()));
                        }
                    });

                    function set_custom_gradient(start = '', end = '') {
                        const
                            colorStart = start ? start : customGradientStart.val(),
                            colorEnd = end ? end : customGradientEnd.val(),
                            style = jQuery('#bg_gradient_custom_style').val();

                        let direction = jQuery('#bg_gradient_custom_direction').val();

                        if (style === 'linear' && direction === 'center') {
                            direction = 'right';
                        }

                        if (style === 'linear') {
                            return `${style}-gradient(to ${direction}, ${colorEnd}, ${colorStart})`;
                        }
                        if (style === 'radial') {
                            return `${style}-gradient(circle at ${direction}, ${colorEnd}, ${colorStart})`;
                        }
                        if (style === 'conic') {
                            return `${style}-gradient(${colorEnd}, ${colorStart})`;
                        }
                    }
                });
            </script>
			<?php
		}
	}
}