<?php

class LinkpageContentTemplates {

	public function get_defaults() {
		$default_types = array( 'cw_link', 'cw_custom', 'post_type', 'cw_heading', 'cw_separator' );
		$defaults      = [];

		foreach ( $default_types as $type ) {
			$defaults[ $type ] = array(
				'admin'  => array( $this, 'template_admin_' . $type ),
				'public' => array( $this, 'template_public_' . $type )
			);
		}

		return apply_filters( 'clickwhale_linkpage_content_defaults', $defaults );
	}

	public static function get_template_data_defaults(): array {
		return array(
			'data' => array(
				'id'        => uniqid(),
				'is_active' => '1',
				'title'     => '',
				'image'     => array()
			),
		);
	}

	/**
	 * $data array structure:
	 * array $data['data'] - DB query result.
	 * bool $data['edit'] - is block editable
	 *
	 *
	 * @param string $type
	 * @param $echo
	 * @param bool $public
	 * @param array $data
	 *
	 * @return false|mixed
	 */
	public function get_template( string $type, $echo, bool $public, array $data = [] ) {
		$callback     = $this->get_defaults();
		$post_types   = ClickwhaleLinkpagesHelper::get_post_types();
		$data['type'] = $type;
		$type         = isset( $post_types[ $type ] ) ? 'post_type' : $type;
		$public       = $public ? 'public' : 'admin';

		if ( ! isset( $callback[ $type ][ $public ] ) ) {
			return false;
		}

		$result = call_user_func_array( $callback[ $type ][ $public ], array( $data ) );

		if ( $echo ) {
			echo $result;
		}

		return $result;
	}

	/* ADMIN TEMPLATES */

	public function template_admin_cw_link( $args ) {

		$defaults = $this->get_template_data_defaults();
		$links    = '';
		$link     = '';
		$active   = false;
		$row_id   = $defaults['data']['id'];

		if ( isset( $args['data'] ) && $args['data'] ) {
			$row_id           = $args['data']['id'];
			$defaults['data'] = $args['data'];
			$link             = Clickwhale_Linkpage_Edit::get_link( $defaults['data']['id'] );

		} else {
			global $wpdb;

			$active                   = true;
			$defaults['data']['id']   = 0;
			$defaults['data']['type'] = $args['type'];
			$links                    = $wpdb->get_results(
				"SELECT id,title,url from {$wpdb->prefix}clickwhale_links",
				ARRAY_A
			);
		}

		$data = $defaults['data'];

		ob_start();
		?>
        <div class="linkpage-row row--<?php echo $data['type'] ?>" id="row-<?php echo $row_id ?>">
            <div class="linkpage-row--top">
				<?php $this->get_template_row_start( $data['id'], $data['is_active'] ?? '' ); ?>
                <div class="linkpage-row--content">
					<?php echo $this->get_template_row_image( $data ) ?>
                    <div class="linkpage-row--link">
						<?php if ( ! $data['id'] ) { ?>
                            <strong><?php _e( 'ClickWhale Link', 'clickwhale' ) ?></strong>
                            <span></span>
						<?php } else { ?>
                            <strong><?php echo $data['title'] ? wp_unslash( $data['title'] ) : $link['title'] ?></strong>
                            <span><?php echo esc_url( $link['url'] ) ?></span>
						<?php } ?>
                    </div>
                </div>
				<?php $this->get_template_row_end(
					$data['type'],
					true,
					$this->get_clicks( $args['linkpage_id'] ?? 0, $data['id'] )
				); ?>
            </div><!-- ./linkpage-row--top -->

            <div class="linkpage-row--bottom <?php echo $active ? 'active' : '' ?>">
				<?php echo $this->get_template_hidden_field( $data ); ?>
				<?php
				if ( ! $data['id'] ) {
					?>
                    <div class="linkpage-row--bottom--control-wrap">
                        <label><?php _e( 'Link', 'clickwhale' ) ?></label>
						<?php if ( $links ) { ?>
                            <div>
                                <select name="links[<?php echo esc_attr( $data['id'] ) ?>][id]"
                                        class="select-link"
                                        required>
                                    <option></option>
									<?php foreach ( $links as $cw_link ) { ?>
                                        <option value="<?php echo esc_attr( $cw_link['id'] ) ?>"
                                                data-title="<?php echo esc_attr( $cw_link['title'] ) ?>"
                                                data-url="<?php echo esc_attr( $cw_link['url'] ) ?>">
											<?php echo $cw_link['title'] . ' (' . $cw_link['url'] . ')' ?>
                                        </option>
									<?php } ?>
                                </select>
                            </div>
							<?php
						} else {
							_e( 'Nothing found', 'clickwhale' );
						}
						?>

                    </div>
					<?php
				}

				echo $this->get_template_input_field(
					__( 'Title', 'clickwhale' ),
					'links[' . $data['id'] . '][title]',
					$data['title'],
					$data['id'] ? $link['title'] : __( 'Custom Title', 'clickwhale' ),
				);

				echo $this->get_template_row_images( $data );
				?>
            </div><!-- ./linkpage-row--bottom -->
        </div>
		<?php
		$result = ob_get_contents();
		ob_end_clean();

		return $result;
	}

	public function template_admin_cw_custom( $args ) {

		$defaults = $this->get_template_data_defaults();
		$active   = false;

		if ( isset( $args['data'] ) && $args['data'] ) {
			$defaults['data'] = $args['data'];
		} else {
			$active                   = true;
			$defaults['data']['type'] = $args['type'];
			$defaults['data']['url']  = '';
		}

		$data = $defaults['data'];

		ob_start();
		?>
        <div class="linkpage-row row--<?php echo $data['type'] ?>" id="row-<?php echo $data['id'] ?>">
            <div class="linkpage-row--top">
				<?php $this->get_template_row_start( $data['id'], $data['is_active'] ?? '' ); ?>
                <div class="linkpage-row--content">
					<?php echo $this->get_template_row_image( $data ) ?>
                    <div class="linkpage-row--link">
						<?php if ( isset( $data['title'] ) && $data['title'] ) { ?>
                            <strong><?php echo wp_unslash( $data['title'] ) ?></strong>
                            <span><?php echo esc_url( $data['url'] ) ?></span>
						<?php } else { ?>
                            <strong><?php _e( 'Custom Link', 'clickwhale' ) ?></strong>
						<?php } ?>
                    </div><!-- ./linkpage-link -->
                </div>
				<?php $this->get_template_row_end(
					$data['type'],
					true,
					$this->get_clicks( $args['linkpage_id'] ?? 0, $data['id'] )
				); ?>
            </div><!-- ./linkpage-row--top -->

            <div class="linkpage-row--bottom <?php echo $active ? 'active' : '' ?>">

				<?php
				// hidden fields
				echo $this->get_template_hidden_field( $data );

				// normal fields
				echo $this->get_template_input_field(
					__( 'Title', 'clickwhale' ),
					'links[' . $data['id'] . '][title]',
					$data['title'],
					__( 'e.g. My link', 'clickwhale' ),
					true
				);

				echo $this->get_template_input_field(
					__( 'URL', 'clickwhale' ),
					'links[' . $data['id'] . '][url]',
					$data['url'],
					__( 'e.g. https://mysite.com', 'clickwhale' ),
					true
				);

				echo $this->get_template_row_images( $data );
				?>

            </div><!-- ./linkpage-row--bottom -->
        </div>
		<?php
		$result = ob_get_contents();
		ob_end_clean();

		return $result;
	}

	public function template_admin_post_type( $args ) {

		$defaults = $this->get_template_data_defaults();
		$pt_posts = [];
		$active   = false;

		if ( isset( $args['data'] ) && $args['data'] ) {
			$defaults['data'] = $args['data'];
		} else {
			$active                   = true;
			$defaults['data']['type'] = $args['type'];

			$args  = array(
				'numberposts' => - 1,
				'post_type'   => $args['type'],
				'orderby'     => 'title',
				'order'       => 'ASC',
				'post_status' => 'publish'
			);
			$posts = get_posts( $args );

			if ( $posts ) {
				foreach ( $posts as $post ) {
					$pt_posts[] = array(
						'id'    => $post->ID,
						'title' => $post->post_title,
						'url'   => get_permalink( $post->ID ),
					);
				}
			}
		}

		$post_types         = ClickwhaleLinkpagesHelper::get_post_types();
		$post_type_singular = $post_types[ $defaults['data']['type'] ];
		$data               = $defaults['data'];

		ob_start();
		?>
        <div class="linkpage-row row--<?php echo $data['type'] ?>" id="row-<?php echo $data['id'] ?>">
            <div class="linkpage-row--top">
				<?php $this->get_template_row_start( $data['id'], $data['is_active'] ?? '' ); ?>
                <div class="linkpage-row--content">
					<?php echo $this->get_template_row_image( $data ) ?>
                    <div class="linkpage-row--link">
						<?php if ( ! isset( $data['post_id'] ) ) { ?>
                            <strong><?php echo $post_type_singular ?></strong>
						<?php } else { ?>
                            <strong><?php echo $data['title'] ? wp_unslash( $data['title'] ) : get_the_title( $data['post_id'] ) ?></strong>
                            <span>
                                <?php echo __( 'Original', 'clickwhale' ) . ' ' . $post_type_singular . ': ' ?>
                                <a href="<?php echo esc_url( get_the_permalink( $data['post_id'] ) ) ?>"
                                   target="_blank">
                                    <?php echo get_the_title( $data['post_id'] ) ?>
                                </a>
                            </span>
						<?php } ?>
                    </div><!-- ./linkpage-link -->
                </div>
				<?php $this->get_template_row_end(
					$data['type'],
					true,
					$this->get_clicks( $args['linkpage_id'] ?? 0, $data['id'] )
				); ?>
            </div><!-- ./linkpage-row--top -->

            <div class="linkpage-row--bottom <?php echo $active ? 'active' : '' ?>">

				<?php echo $this->get_template_hidden_field( $data, array( 'post_id' ) ); ?>

				<?php if ( ! isset( $data['post_id'] ) ) { ?>
                    <div class="linkpage-row--bottom--control-wrap">
                        <label for="links[<?php echo esc_attr( $data['id'] ) ?>][post_id]">
							<?php echo $post_type_singular ?>
                        </label>
                        <div>
                            <select name="links[<?php echo esc_attr( $data['id'] ) ?>][post_id]"
                                    class="select-link"
                                    required>
                                <option></option>
								<?php
								if ( $pt_posts ) {
									foreach ( $pt_posts as $pt_post ) {
										?>
                                        <option value="<?php echo esc_attr( $pt_post['id'] ) ?>"
                                                data-title="<?php echo esc_attr( $pt_post['title'] ) ?>"
                                                data-url="<?php echo esc_attr( $pt_post['url'] ) ?>">
											<?php echo $pt_post['title'] ?>
                                        </option>
										<?php
									}
								}
								?>
                            </select>
                        </div>
                    </div>
				<?php } ?>

				<?php
				// normal fields
				echo $this->get_template_input_field(
					__( 'Title', 'clickwhale' ),
					'links[' . $data['id'] . '][title]',
					$data['title'],
					isset( $data['post_id'] ) ? get_the_title( $data['post_id'] ) : __( 'Custom Title', 'clickwhale' ),
				);

				echo $this->get_template_row_images( $data );
				?>

            </div><!-- ./linkpage-row--bottom -->
        </div>
		<?php
		$result = ob_get_contents();
		ob_end_clean();

		return $result;
	}

	public function template_admin_cw_heading( $args ) {
		$defaults = $this->get_template_data_defaults();
		$active   = false;

		if ( isset( $args['data'] ) && $args['data'] ) {
			$defaults['data'] = $args['data'];
		} else {
			$active                   = true;
			$defaults['data']['type'] = $args['type'];
			unset( $defaults['image'] );
		}

		$data = $defaults['data'];

		ob_start();
		?>
        <div class="linkpage-row row--<?php echo $data['type'] ?> no-image" id="row-<?php echo $data['id'] ?>">
            <div class="linkpage-row--top">
				<?php $this->get_template_row_start( $data['id'], $data['is_active'] ?? '' ); ?>
                <div class="linkpage-row--content">
                    <div class="linkpage-row--link">
						<?php if ( isset( $data['title'] ) && $data['title'] ) { ?>
                            <strong><?php echo wp_unslash( $data['title'] ) ?></strong>
						<?php } else { ?>
                            <strong><?php _e( 'Heading', 'clickwhale-pro' ) ?></strong>
						<?php } ?>
                    </div><!-- ./linkpage-link -->
                </div>
				<?php $this->get_template_row_end( $data['type'], ); ?>
            </div><!-- ./linkpage-row--top -->
            <div class="linkpage-row--bottom <?php echo $active ? 'active' : '' ?>">

				<?php
				// hidden fields
				echo $this->get_template_hidden_field( $data );

				// normal fields
				echo $this->get_template_input_field(
					__( 'Heading', 'clickwhale' ),
					'links[' . $data['id'] . '][title]',
					$data['title'],
					__( 'e.g. My Links Heading', 'clickwhale' )
				);

				echo $this->get_template_input_field(
					__( 'Description', 'clickwhale' ),
					'links[' . $data['id'] . '][description]',
					$data['description'] ?? '',
					__( 'e.g. My Links Description', 'clickwhale' )
				);
				?>
            </div><!-- ./linkpage-row--bottom -->
        </div>
		<?php
		$result = ob_get_contents();
		ob_end_clean();

		return $result;
	}

	public function template_admin_cw_separator( $args ) {
		$defaults = $this->get_template_data_defaults();

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
				<?php $this->get_template_row_start( $data['id'], $data['is_active'] ?? '' ); ?>
                <div class="linkpage-row--content">
                    <div class="linkpage-row--link">
                        <strong><?php _e( 'Separator', 'clickwhale-pro' ); ?></strong>
                    </div>
                </div>
				<?php $this->get_template_row_end( $data['type'], false ); ?>
            </div><!-- ./linkpage-row--top -->
            <div class="linkpage-row--bottom">
				<?php echo $this->get_template_hidden_field( $data ); ?>
            </div><!-- ./linkpage-row--bottom -->
        </div>
		<?php
		$result = ob_get_contents();
		ob_end_clean();

		return $result;
	}

	/* PUBLIC TEMPLATES */

	public function template_public_cw_link( $args ): string {
		$link = Clickwhale_Linkpage_Edit::get_link( $args['data']['id'] );

		return $this->get_public_link_template(
			array(
				'title' => $args['data']['title'] ?: $link['title'],
				'url'   => trailingslashit( get_bloginfo( 'url' ) . '/' . $link['slug'] ),
			),
			$args );
	}

	public function template_public_cw_custom( $args ): string {
		return $this->get_public_link_template(
			array(
				'title' => $args['data']['title'],
				'url'   => trailingslashit( $args['data']['url'] ),
			),
			$args );
	}

	public function template_public_post_type( $args ): string {
		return $this->get_public_link_template(
			array(
				'title' => $args['data']['title'] ?: get_the_title( $args['data']['post_id'] ),
				'url'   => trailingslashit( get_permalink( $args['data']['post_id'] ) ),
			),
			$args );
	}

	public function template_public_cw_heading( $args ): string {
		ob_start();
		?>
        <div class="linkpage-public-row linkpage-public-row--<?php echo $args['type'] ?>"
             data-type="<?php echo $args['type'] ?>">
            <h2><?php echo $args['data']['title'] ?></h2>
			<?php if ( isset( $args['data']['description'] ) && $args['data']['description'] ) { ?>
                <p><?php echo $args['data']['description'] ?></p>
			<?php } ?>
        </div>
		<?php
		$result = ob_get_contents();
		ob_end_clean();

		return $result;
	}

	public function template_public_cw_separator( $args ): string {
		ob_start();
		?>
        <hr class="linkpage-public-row linkpage-public-row--<?php echo $args['type'] ?>"
            data-type="<?php echo $args['type'] ?>"/>
		<?php
		$result = ob_get_contents();
		ob_end_clean();

		return $result;
	}

	// OTHER METHODS

	public function get_public_link_template( array $data, array $args ) {
		$target = 'target="' . esc_attr( $args['target'] ) . '"';
		$title  = $data['title'];
		$type   = $args['data']['type'];
		$url    = $data['url'];
		ob_start();
		?>
        <div class="linkpage-public-row linkpage-public-row--<?php echo $type ?>" data-type="<?php echo $type ?>">
            <a href="<?php echo esc_url( $url ) ?>" class="cw-track" <?php echo $target ?>>
				<?php
				if ( isset( $args['data']['image']['type'] ) && isset( $args['data']['image']['image_id'] ) ) {
					echo $this->get_template_row_image( $args['data'] );
				}
				?>
                <div class="linkpage-row--title"><?php echo wp_unslash( $title ) ?></div>
				<?php if ( isset( $args['data']['image']['type'] ) && isset( $args['data']['image']['image_id'] ) ) { ?>
                    <div class="linkpage-row--end"></div>
				<?php } ?>
            </a>
        </div>
		<?php
		$result = ob_get_contents();
		ob_end_clean();

		return $result;
	}

	public function get_template_hidden_field( array $data, array $fields = [] ): string {
		$result  = '';
		$default = array( 'id', 'type' );
		$default = $fields ? array_merge( $default, $fields ) : $default;

		foreach ( $default as $hidden ) {
			$name   = 'links[' . $data['id'] . '][' . $hidden . ']';
			$result .= '<input type="hidden" name="' . esc_attr( $name ) . '" value="' . esc_attr( $data[ $hidden ] ?? '' ) . '">';
		}

		return $result;
	}

	public function get_template_input_field(
		string $label,
		string $name,
		string $value,
		string $placeholder = '',
		bool $required = false,
		string $type = 'text'
	): string {
		$label       = '<label for="' . $name . '">' . $label . '</label>';
		$type        = 'type="' . esc_attr( $type ) . '"';
		$name        = 'name="' . esc_attr( $name ) . '"';
		$value       = 'value="' . esc_attr( $value ) . '"';
		$placeholder = 'name="' . esc_attr( $placeholder ) . '"';
		$required    = $required ? 'required' : '';
		$input       = "<div><input $type $name $value $placeholder $required /></div>";

		return '<div class="linkpage-row--bottom--control-wrap">' . $label . $input . '</div>';
	}

	public function get_template_row_image( array $data ): string {
		if ( ! isset( $data['image']['type'] ) && ! isset( $data['image']['image_id'] ) ) {
			return '<div class="linkpage-row--image"></div>';
		}

		$image = '';
		$class = '';

		switch ( $data['image']['type'] ) {
			case 'icon':
				$class = 'with-image';
				$src   = $data['image']['image_id'];
				$image = '<svg class="feather linkpage-row--image-placeholder"><use href="' . ADMIN_IMAGES_DIR . '/feather-sprite.svg#' . $src . '"></use></svg>';
				break;
			case 'emoji':
				$class = 'with-image';
				$image = $data['image']['image_id'];
				break;
			case 'image' :
				$src   = wp_get_attachment_image_src( $data['image']['image_id'] );
				$image = '<img src="' . $src[0] . '"/>';
				break;
			default:
		}

		return '<div class="linkpage-row--image ' . $class . '">' . $image . '</div>';
	}

	public function get_template_row_start( $id, $is_active = '' ) {
		?>
        <div class="linkpage-row--start">
            <div class="linkpage-row--drag" title="<?php _e( 'Change Order', 'clickwhale' ); ?>">
                <svg class="feather">
                    <use href="<?php echo ADMIN_IMAGES_DIR ?>/feather-sprite.svg#drag-2"></use>
                </svg>
            </div>
            <label class="clickwhale-checkbox--toggle">
                <input type="checkbox"
                       name="links[<?php echo $id ?>][is_active]"
                       class="clickwhale_tc_active_toggle"
                       value="1"
					<?php checked( $is_active, 1 ) ?>>
                <span class="clickwhale-checkbox--toggle-slider"></span>
            </label>
        </div>
		<?php
	}

	public static function get_template_row_badge( string $type ): string {
		$badge  = '';
		$select = Clickwhale_Linkpage_Edit::get_select_values();
		foreach ( $select as $optgroup ) {
			if ( isset( $optgroup['options'][ $type ]['name'] ) ) {
				$badge = $optgroup['label'];
				break;
			}
		}

		return '<div class="linkpage-row--badge"><span>' . $badge . '</span></div>';
	}

	public function get_template_row_end( string $type, bool $edit = true, string $stats = '' ) {
		$row_edit_class  = $edit ? '' : 'no-edit';
		$row_stats_class = $edit ? '' : 'no-stats';
		?>

        <div class="linkpage-row--end <?php echo $row_stats_class ?>">
			<?php if ( $stats ) { ?>
                <div class="linkpage-row--statistics">
                    <span class="linkpage-row--clicks">
                        <svg class="feather">
                            <use href="<?php echo ADMIN_IMAGES_DIR ?>/feather-sprite.svg#bar-chart-2"></use>
                        </svg>
                        Clicks: <?php echo $stats ?: 0 ?>
                    </span>
                </div><!-- ./linkpage-row--statistics -->
			<?php } ?>
			<?php echo $this->get_template_row_badge( $type ); ?>
            <div class="linkpage-row--actions <?php echo $row_edit_class ?>">
				<?php if ( $edit ) { ?>
                    <button type="button" class="linkpage-row--actions--button-edit">
                        <svg class="feather">
                            <use href="<?php echo ADMIN_IMAGES_DIR ?>/feather-sprite.svg#chevron-down"></use>
                        </svg>
                    </button>
				<?php } ?>
                <button type="button" class="linkpage-row--actions--button-remove">
                    <svg class="feather">
                        <use href="<?php echo ADMIN_IMAGES_DIR ?>/feather-sprite.svg#trash-2"></use>
                    </svg>
                </button>
            </div><!-- ./linkpage-row--actions -->
        </div>
		<?php
	}

	public function get_template_row_images( array $data ) {
		$image_type = $data['image']['type'] ?? '';
		$image_id   = $data['image']['image_id'] ?? '';

		$emoji_class = $image_id && $image_type == 'emoji' ? 'with-image' : '';
		$icon_class  = $image_id && $image_type == 'icon' ? 'with-image' : '';
		?>
        <div class="linkpage-row--bottom--control-wrap linkpage-row--image-select--wrap">
            <input type="hidden"
                   name="links[<?php echo $data['id'] ?>][image][type]"
                   value="<?php echo $data['image']['type'] ?? '' ?>">
            <label><?php _e( 'Icon', 'clickwhale' ) ?></label>

            <div class="linkpage-row--image-select">
                <p class="description">
					<?php
					_e( 'You can select either an image, an icon or an emoji. You cannot have more than one active at the same time.',
						'clickwhale' );
					?>
                </p>
                <div class="linkpage-row--image-select--tab">
                    <div class="linkpage-row--image-select--tab-inner tab-multiple">
                        <div class="linkpage-row--image-select--item item-image">
                            <div class="image-item">
                                <input type="radio"
                                       data-type="image"
                                       id="image-<?php echo $data['id'] ?>-0"
                                       name="links[<?php echo $data['id'] ?>][image][image_id]"
                                       value="<?php echo $image_type == 'image' ? $image_id : '' ?>"
                                       checked>
                                <label for="image-<?php echo $data['id'] ?>-0">
									<?php if ( $image_id && $image_type == 'image' ) {
										echo '<img src="' . wp_get_attachment_image_src( $image_id )[0] . '"/>';
									} ?>
                                </label>
                            </div>
                            <a href="#" class="linkpage-row--image-upload">
								<?php _e( 'Upload image', 'clickwhale' ) ?>
                            </a>
                        </div>

                        <div class="linkpage-row--image-select--item item--icon">
                            <div class="image-item <?php echo $icon_class ?>">
                                <input type="radio"
                                       data-type="icon"
                                       id="image-<?php echo $data['id'] ?>-icon"
                                       name="links[<?php echo $data['id'] ?>][image][image_id]"
                                       value="<?php echo $image_id && $image_type == 'icon' ? $image_id : '' ?>"
									<?php if ( $image_id && $image_type == 'icon' ) { ?>
                                        checked
									<?php } ?>
                                >
                                <label for="image-<?php echo $data['id'] ?>-icon">
									<?php if ( $image_id && $image_type == 'icon' ) { ?>
                                        <svg class="feather linkpage-row--image-placeholder">
                                            <use href="<?php echo ADMIN_IMAGES_DIR ?>/feather-sprite.svg#<?php echo $image_id ?>"></use>
                                        </svg>
									<?php } ?>
                                </label>
                            </div>
                            <a id="icon-picker-<?php echo $data['id'] ?>" class="icon-picker" href="#">
								<?php _e( 'Select Icon', 'clickwhale' ) ?>
                            </a>
                        </div>

                        <div class="linkpage-row--image-select--item item--emoji">
                            <div class="image-item <?php echo $emoji_class ?>">
                                <input type="radio"
                                       data-type="emoji"
                                       id="image-<?php echo $data['id'] ?>-emoji"
                                       name="links[<?php echo $data['id'] ?>][image][image_id]"
                                       value="<?php echo $image_id && $image_type == 'emoji' ? $image_id : '' ?>"
									<?php if ( $image_id && $image_type == 'emoji' ) { ?>
                                        checked
									<?php } ?>
                                >
                                <label for="image-<?php echo $data['id'] ?>-emoji">
									<?php echo $image_id && $image_type == 'emoji' ? $image_id : '' ?>
                                </label>
                            </div>
                            <a class="emoji-picker" href="#"><?php _e( 'Select Emoji', 'clickwhale' ) ?></a>
                        </div>

                        <div class="linkpage-row--image-select--reset">
                            <button type="button" class="reset-image">Reset</button>
                        </div>
                    </div>
                </div>
            </div>
        </div><!-- ./linkpage-row--image-select-wrap -->
		<?php
	}

	public static function get_images(): array {
		return array(
			'activity',
			'airplay',
			'alert-circle',
			'alert-octagon',
			'alert-triangle',
			'align-center',
			'align-justify',
			'align-left',
			'align-right',
			'anchor',
			'aperture',
			'archive',
			'arrow-down-circle',
			'arrow-down-left',
			'arrow-down-right',
			'arrow-down',
			'arrow-left-circle',
			'arrow-left',
			'arrow-right-circle',
			'arrow-right',
			'arrow-up-circle',
			'arrow-up-left',
			'arrow-up-right',
			'arrow-up',
			'at-sign',
			'award',
			'bar-chart-2',
			'bar-chart',
			'battery-charging',
			'battery',
			'bell-off',
			'bell',
			'bluetooth',
			'bold',
			'book-open',
			'book',
			'bookmark',
			'box',
			'briefcase',
			'calendar',
			'camera-off',
			'camera',
			'cast',
			'check-circle',
			'check-square',
			'check',
			'chevron-down',
			'chevron-left',
			'chevron-right',
			'chevron-up',
			'chevrons-down',
			'chevrons-left',
			'chevrons-right',
			'chevrons-up',
			'chrome',
			'circle',
			'clipboard',
			'clock',
			'cloud-drizzle',
			'cloud-lightning',
			'cloud-off',
			'cloud-rain',
			'cloud-snow',
			'cloud',
			'code',
			'codepen',
			'codesandbox',
			'coffee',
			'columns',
			'command',
			'compass',
			'copy',
			'corner-down-left',
			'corner-down-right',
			'corner-left-down',
			'corner-left-up',
			'corner-right-down',
			'corner-right-up',
			'corner-up-left',
			'corner-up-right',
			'cpu',
			'credit-card',
			'crop',
			'crosshair',
			'database',
			'delete',
			'disc',
			'divide-circle',
			'divide-square',
			'divide',
			'dollar-sign',
			'download-cloud',
			'download',
			'dribbble',
			'droplet',
			'edit-2',
			'edit-3',
			'edit',
			'external-link',
			'eye-off',
			'eye',
			'facebook',
			'fast-forward',
			'feather',
			'figma',
			'file-minus',
			'file-plus',
			'file-text',
			'file',
			'film',
			'filter',
			'flag',
			'folder-minus',
			'folder-plus',
			'folder',
			'framer',
			'frown',
			'gift',
			'git-branch',
			'git-commit',
			'git-merge',
			'git-pull-request',
			'github',
			'gitlab',
			'globe',
			'grid',
			'hard-drive',
			'hash',
			'headphones',
			'heart',
			'help-circle',
			'hexagon',
			'home',
			'image',
			'inbox',
			'info',
			'instagram',
			'italic',
			'key',
			'layers',
			'layout',
			'life-buoy',
			'link-2',
			'link',
			'linkedin',
			'list',
			'loader',
			'lock',
			'log-in',
			'log-out',
			'mail',
			'map-pin',
			'map',
			'maximize-2',
			'maximize',
			'meh',
			'menu',
			'message-circle',
			'message-square',
			'mic-off',
			'mic',
			'minimize-2',
			'minimize',
			'minus-circle',
			'minus-square',
			'minus',
			'monitor',
			'moon',
			'more-horizontal',
			'more-vertical',
			'mouse-pointer',
			'move',
			'music',
			'navigation-2',
			'navigation',
			'octagon',
			'package',
			'paperclip',
			'pause-circle',
			'pause',
			'pen-tool',
			'percent',
			'phone-call',
			'phone-forwarded',
			'phone-incoming',
			'phone-missed',
			'phone-off',
			'phone-outgoing',
			'phone',
			'pie-chart',
			'play-circle',
			'play',
			'plus-circle',
			'plus-square',
			'plus',
			'pocket',
			'power',
			'printer',
			'radio',
			'refresh-ccw',
			'refresh-cw',
			'repeat',
			'rewind',
			'rotate-ccw',
			'rotate-cw',
			'rss',
			'save',
			'scissors',
			'search',
			'send',
			'server',
			'settings',
			'share-2',
			'share',
			'shield-off',
			'shield',
			'shopping-bag',
			'shopping-cart',
			'shuffle',
			'sidebar',
			'skip-back',
			'skip-forward',
			'slack',
			'slash',
			'sliders',
			'smartphone',
			'smile',
			'speaker',
			'square',
			'star',
			'stop-circle',
			'sun',
			'sunrise',
			'sunset',
			'table',
			'tablet',
			'tag',
			'target',
			'terminal',
			'thermometer',
			'thumbs-down',
			'thumbs-up',
			'toggle-left',
			'toggle-right',
			'tool',
			'trash-2',
			'trash',
			'trello',
			'trending-down',
			'trending-up',
			'triangle',
			'truck',
			'tv',
			'twitch',
			'twitter',
			'type',
			'umbrella',
			'underline',
			'unlock',
			'upload-cloud',
			'upload',
			'user-check',
			'user-minus',
			'user-plus',
			'user-x',
			'user',
			'users',
			'video-off',
			'video',
			'voicemail',
			'volume-1',
			'volume-2',
			'volume-x',
			'volume',
			'watch',
			'wifi-off',
			'wifi',
			'wind',
			'x-circle',
			'x-octagon',
			'x-square',
			'x',
			'youtube',
			'zap-off',
			'zap',
			'zoom-in',
			'zoom-out',
		);
	}

	private function get_clicks( string $linkpage_id, string $id, bool $is_link = true ) {
		return ClickwhaleLinkpagesHelper::get_linkpage_link_clicks( $linkpage_id, $id, $is_link );
	}
}