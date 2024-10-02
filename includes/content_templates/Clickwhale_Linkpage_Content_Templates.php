<?php
namespace clickwhale\includes\content_templates;

use clickwhale\includes\helpers\{
    Helper,
    Links_Helper,
    Linkpages_Helper
};

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Clickwhale_Linkpage_Content_Templates {

	public function get_defaults() {
		$default_types = array(
			'cw_link',
			'cw_custom_link',
			'post_type',
			'cw_heading',
			'cw_separator',
			'cw_custom_content'
		);
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
	 * @param string $type
	 * @param bool $echo
	 * @param bool $public
	 * @param array $data
	 *
	 * @return false|mixed
	 */
	public function get_template( string $type, bool $echo, bool $public, array $data = [] ) {
		$callback     = $this->get_defaults();
		$post_types   = Helper::get_post_types();
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
		$error    = '';

		if ( isset( $args['data'] ) && $args['data'] ) {
			$row_id           = $args['data']['id'];
			$defaults['data'] = $args['data'];
			$link             = Links_Helper::get_by_id( intval( $defaults['data']['id'] ) );
			$error            = is_null( $link ) ? 'with-error' : $error;

			if ( ! $link ) {
				return false;
			}
		} else {
			$active                       = true;
			$defaults['data']['id']       = 0;
			$defaults['data']['type']     = $args['type'];
			$defaults['data']['subtitle'] = '';
			$links                        = Links_Helper::get_all( 'title', 'asc', ARRAY_A );
		}

		$data = $defaults['data'];

		if ( is_null( $link ) ) {
			return false;
		}

		ob_start();
		?>
        <div class="linkpage-row row--<?php echo $data['type'] ?> <?php echo $error ?>" id="row-<?php echo $row_id ?>">
            <div class="linkpage-row--top">
				<?php $this->get_template_row_start( $data['id'], $data['is_active'] ?? '' ); ?>
                <div class="linkpage-row--content">
					<?php echo $this->get_template_row_image( $data ) ?>
                    <div class="linkpage-row--link">
						<?php if ( ! $data['id'] ) { ?>
                            <strong><?php _e( 'ClickWhale Link', CLICKWHALE_NAME ) ?></strong>
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
                        <label><?php _e( 'Link', CLICKWHALE_NAME ) ?></label>
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
							_e( 'Nothing found', CLICKWHALE_NAME );
						}
						?>

                    </div>
					<?php
				}

				echo $this->get_template_input_field(
					__( 'Title', CLICKWHALE_NAME ),
					'links[' . $data['id'] . '][title]',
					$data['title'] ?? '',
					$data['id'] ? $link['title'] : __( 'Custom Title', CLICKWHALE_NAME )
				);

				echo $this->get_template_input_field(
					__( 'Subtitle', CLICKWHALE_NAME ),
					'links[' . $data['id'] . '][subtitle]',
					$data['subtitle'] ?? '',
					__( 'e.g. Read more about something', CLICKWHALE_NAME )
				);

				$this->get_template_row_images( $data );
				?>
            </div><!-- ./linkpage-row--bottom -->
        </div>
		<?php
		$result = ob_get_contents();
		ob_end_clean();

		return $result;
	}

	public function template_admin_cw_custom_link( $args ) {

		$defaults = $this->get_template_data_defaults();
		$active   = false;

		if ( isset( $args['data'] ) && $args['data'] ) {
			$defaults['data'] = $args['data'];
		} else {
			$active                       = true;
			$defaults['data']['type']     = $args['type'];
			$defaults['data']['url']      = '';
			$defaults['data']['subtitle'] = '';
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
                            <strong><?php _e( 'Custom Link', CLICKWHALE_NAME ) ?></strong>
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
					__( 'Title', CLICKWHALE_NAME ),
					'links[' . $data['id'] . '][title]',
					$data['title'] ?? '',
					__( 'e.g. My link', CLICKWHALE_NAME ),
					true
				);

				echo $this->get_template_input_field(
					__( 'Subtitle', CLICKWHALE_NAME ),
					'links[' . $data['id'] . '][subtitle]',
					$data['subtitle'] ?? '',
					__( 'e.g. Read more about something', CLICKWHALE_NAME )
				);

				echo $this->get_template_input_field(
					__( 'URL', CLICKWHALE_NAME ),
					'links[' . $data['id'] . '][url]',
					$data['url'] ?? '',
					__( 'e.g. https://mysite.com', CLICKWHALE_NAME ),
					true
				);

				$this->get_template_row_images( $data );
				?>

            </div><!-- ./linkpage-row--bottom -->
        </div>
		<?php
		$result = ob_get_contents();
		ob_end_clean();

		return $result;
	}

	public function template_admin_post_type( $args ) {

		$defaults    = $this->get_template_data_defaults();
		$pt_posts    = [];
		$active      = false;
		$post_status = '';

		if ( isset( $args['data'] ) && $args['data'] ) {
			$defaults['data'] = $args['data'];
			$post             = get_post( $args['data']['post_id'] );

			if ( ! $post ) {
				return false;
			}
			$post_status = $post->post_status;
		} else {
			$active                       = true;
			$defaults['data']['type']     = $args['type'];
			$defaults['data']['subtitle'] = '';

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
						'url'   => get_permalink( $post->ID )
					);
				}
			}
		}

		$post_types         = Helper::get_post_types();
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
                            <strong>
								<?php echo $data['title'] ? wp_unslash( $data['title'] ) : get_the_title( $data['post_id'] ) ?>
								<?php echo $post_status != 'publish' ? '(' . $post_status . ')' : '' ?>
                            </strong>
                            <span>
                                <?php echo __( 'Original', CLICKWHALE_NAME ) . ' ' . $post_type_singular . ': ' ?>
                                <a href="<?php echo esc_url( get_the_permalink( $data['post_id'] ) ) ?>"
                                   target="_blank"
                                   rel="noopener">
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
					__( 'Title', CLICKWHALE_NAME ),
					'links[' . $data['id'] . '][title]',
					$data['title'] ?? '',
					isset( $data['post_id'] ) ? get_the_title( $data['post_id'] ) : __( 'Custom Title', CLICKWHALE_NAME )
				);

				echo $this->get_template_input_field(
					__( 'Subtitle', CLICKWHALE_NAME ),
					'links[' . $data['id'] . '][subtitle]',
					$data['subtitle'] ?? '',
					__( 'e.g. Read more about something', CLICKWHALE_NAME )
				);

				$this->get_template_row_images( $data );
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
                            <strong><?php _e( 'Heading', CLICKWHALE_NAME ) ?></strong>
						<?php } ?>
                    </div><!-- ./linkpage-row--link -->
                </div>
				<?php $this->get_template_row_end( $data['type'] ); ?>
            </div><!-- ./linkpage-row--top -->
            <div class="linkpage-row--bottom <?php echo $active ? 'active' : '' ?>">

				<?php
				// hidden fields
				echo $this->get_template_hidden_field( $data );

				// normal fields
				echo $this->get_template_input_field(
					__( 'Heading', CLICKWHALE_NAME ),
					'links[' . $data['id'] . '][title]',
					$data['title'] ?? '',
					__( 'e.g. My Links Heading', CLICKWHALE_NAME )
				);

				echo $this->get_template_input_field(
					__( 'Description', CLICKWHALE_NAME ),
					'links[' . $data['id'] . '][description]',
					$data['description'] ?? '',
					__( 'e.g. My Links Description', CLICKWHALE_NAME )
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
                        <strong><?php _e( 'Separator', CLICKWHALE_NAME ); ?></strong>
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

	public function template_admin_cw_custom_content( $args ) {
		$defaults = $this->get_template_data_defaults();
		$active   = false;

		if ( isset( $args['data'] ) && $args['data'] ) {
			$defaults['data'] = $args['data'];
		} else {
			$active                       = true;
			$defaults['data']['type']     = $args['type'];
			$defaults['data']['subtitle'] = '';
			$defaults['data']['content']  = '';
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
                        <strong><?php _e( 'Custom Content', CLICKWHALE_NAME ) ?></strong>
                    </div><!-- ./linkpage-link -->
                </div>
				<?php $this->get_template_row_end( $data['type'] ); ?>
            </div><!-- ./linkpage-row--top -->
            <div class="linkpage-row--bottom <?php echo $active ? 'active' : '' ?>">

				<?php
				echo $this->get_template_hidden_field( $data );

				echo $this->get_template_input_field(
					__( 'Title', CLICKWHALE_NAME ),
					'links[' . $data['id'] . '][title]',
					$data['title'] ?? '',
					__( 'e.g. My link', CLICKWHALE_NAME )
				);
				echo $this->get_template_input_field(
					__( 'Subtitle', CLICKWHALE_NAME ),
					'links[' . $data['id'] . '][subtitle]',
					$data['subtitle'] ?? '',
					__( 'e.g. My link', CLICKWHALE_NAME )
				);
				?>
                <hr>
                <textarea id="cw_custom_content_<?php echo $data['id'] ?>"
                          name="links[<?php echo $data['id'] ?>][content]"><?php echo wp_unslash( $data['content'] ) ?></textarea>
            </div><!-- ./linkpage-row--bottom -->
        </div>
		<?php
		$result = ob_get_contents();
		ob_end_clean();

		return $result;
	}

	/* PUBLIC TEMPLATES */

	public function template_public_cw_link( $args ): string {
		$link = Links_Helper::get_by_id( intval( $args['data']['id'] ) );
		if ( ! $link ) {
			return false;
		}

		return $this->get_public_link_template(
			array(
				'title'    => $args['data']['title'] ?: $link['title'],
				'subtitle' => $args['data']['subtitle'] ?? '',
				'url'      => trailingslashit( get_bloginfo( 'url' ) . '/' . $link['slug'] ),
			),
			$args );
	}

	public function template_public_cw_custom_link( $args ): string {

        $url = $args['data']['url'];
        $url_array = parse_url( $url );

		return $this->get_public_link_template(
			array(
				'title'    => $args['data']['title'],
				'subtitle' => $args['data']['subtitle'] ?? '',
				'url'      => ( isset( $url_array['query'] ) ) ? $url : trailingslashit( $url ),
			),
			$args );
	}

	public function template_public_post_type( $args ): string {
		$post = get_post( $args['data']['post_id'] );

		if ( ! $post || $post->post_status != 'publish' ) {
			return false;
		}

		return $this->get_public_link_template(
			array(
				'title'    => $args['data']['title'] ?: get_the_title( $args['data']['post_id'] ),
				'subtitle' => $args['data']['subtitle'] ?? '',
				'url'      => trailingslashit( get_permalink( $args['data']['post_id'] ) ),
			),
			$args );
	}

	public function template_public_cw_heading( $args ): string {
		ob_start();
		?>
        <div class="linkpage-public-row linkpage-public-row--<?php echo $args['type'] ?>"
             data-type="<?php echo $args['type'] ?>">
            <h2><?php echo wp_unslash( $args['data']['title'] ) ?></h2>
			<?php if ( isset( $args['data']['description'] ) && $args['data']['description'] ) { ?>
                <p><?php echo wp_unslash( $args['data']['description'] ) ?></p>
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

	public function template_public_cw_custom_content( $args ): string {
		ob_start();
		if ( ! empty( $args['data']['title'] ) || ! empty( $args['data']['subtitle'] ) ) {
			?>
            <div class="linkpage-public-row linkpage-public-row--cw_heading">
				<?php if ( ! empty( $args['data']['title'] ) ) { ?>
                    <h2><?php echo wp_unslash( $args['data']['title'] ) ?></h2>
				<?php } ?>
				<?php if ( ! empty( $args['data']['subtitle'] ) ) { ?>
                    <p><?php echo wp_unslash( $args['data']['subtitle'] ) ?></p>
				<?php } ?>
            </div>
		<?php } ?>
        <div class="linkpage-public-row linkpage-public-row--<?php echo $args['type'] ?>"
             data-type="<?php echo $args['type'] ?>">
            <div class="linkpage-public-row--content">
				<?php echo wpautop( wp_unslash( $args['data']['content'] ) ); ?>
            </div>
        </div>
		<?php
		$result = ob_get_contents();
		ob_end_clean();

		return $result;
	}

	// OTHER METHODS

	public function get_public_link_template( array $data, array $args ) {
		$target   = 'target="' . esc_attr( $args['target'] ) . '"';
		$title    = $data['title'];
		$subtitle = $data['subtitle'];
		$type     = $args['data']['type'];
		$url      = $data['url'];
		ob_start();
		?>
        <div class="linkpage-public-row linkpage-public-row--<?php echo $type ?>" data-type="<?php echo $type ?>">
            <a class="linkpage-public-row-link cw-track" href="<?php echo esc_url( $url ) ?>"
               <?php echo $target ?>>
				<?php
				if ( isset( $args['data']['image']['type'] ) && isset( $args['data']['image']['image_id'] ) ) {
					echo $this->get_template_row_image( $args['data'] );
				}
				?>
                <div class="linkpage-row--title--wrap">
                    <div class="linkpage-row--title"><?php echo wp_unslash( $title ) ?></div>
					<?php if ( $subtitle ) { ?>
                        <p class="linkpage-row--subtitle"><?php echo wp_unslash( $subtitle ) ?></p>
					<?php } ?>
                </div>
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
		string $value = '',
		string $placeholder = '',
		bool $required = false,
		string $type = 'text'
	): string {
		$label       = '<label for="' . $name . '">' . $label . '</label>';
		$type        = 'type="' . esc_attr( $type ) . '"';
		$name        = 'name="' . esc_attr( $name ) . '"';
		$value       = 'value="' . esc_attr( wp_unslash( $value ) ) . '"';
		$placeholder = 'placeholder="' . esc_attr( $placeholder ) . '"';
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
				$image = '<ion-icon name="' . $src . '"></ion-icon>';
				break;
			case 'emoji':
				$class = 'with-image';
				$image = $data['image']['image_id'];
				break;
			case 'image' :
				$image_alt = get_post_meta( $data['image']['image_id'], '_wp_attachment_image_alt', true );
				$alt       = $image_alt ?: get_the_title( $data['image']['image_id'] );
				$src       = wp_get_attachment_image_src( $data['image']['image_id'] );
				$image     = '<img alt="' . $alt . '" src="' . $src[0] . '"/>';
				break;
			default:
		}

		return '<div class="linkpage-row--image ' . $class . '">' . $image . '</div>';
	}

	public function get_template_row_start( $id, $is_active = '' ) {
		?>
        <div class="linkpage-row--start">
            <div class="linkpage-row--drag" title="<?php _e( 'Change Order', CLICKWHALE_NAME ); ?>">
                <svg class="feather">
                    <use href="<?php echo CLICKWHALE_ADMIN_ASSETS_DIR ?>/images/feather-sprite.svg#drag-2"></use>
                </svg>
            </div>
            <label class="clickwhale-checkbox--toggle">
                <input type="checkbox"
                       name="links[<?php echo $id ?>][is_active]"
                       class="clickwhale_linkpage_active_toggle"
                       value="1"
					<?php checked( $is_active, 1 ) ?>>
                <span class="clickwhale-checkbox--toggle-slider"></span>
            </label>
        </div>
		<?php
	}

	public static function get_template_row_badge( string $type ): string {
		$badge  = '';
        $linkpage = clickwhale()->linkpage;

		foreach ( $linkpage::get_select_values() as $optgroup ) {
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
                            <use href="<?php echo CLICKWHALE_ADMIN_ASSETS_DIR ?>/images/feather-sprite.svg#bar-chart-2"></use>
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
                            <use href="<?php echo CLICKWHALE_ADMIN_ASSETS_DIR ?>/images/feather-sprite.svg#chevron-down"></use>
                        </svg>
                    </button>
				<?php } ?>
                <button type="button" class="linkpage-row--actions--button-remove">
                    <svg class="feather">
                        <use href="<?php echo CLICKWHALE_ADMIN_ASSETS_DIR ?>/images/feather-sprite.svg#trash-2"></use>
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
            <label><?php _e( 'Icon', CLICKWHALE_NAME ) ?></label>

            <div class="linkpage-row--image-select">
                <p class="description">
					<?php
					_e( 'You can select either an image, an icon or an emoji. You cannot have more than one active at the same time.', CLICKWHALE_NAME );
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
									<?php if ( $image_id && $image_type == 'image' ) { ?>
                                        checked
									<?php } ?>
                                >
                                <label for="image-<?php echo $data['id'] ?>-0">
									<?php if ( $image_id && $image_type == 'image' ) {
										echo '<img src="' . wp_get_attachment_image_src( $image_id )[0] . '"/>';
									} ?>
                                </label>
                            </div>
                            <a href="#" class="linkpage-row--image-upload">
								<?php _e( 'Upload image', CLICKWHALE_NAME ) ?>
                            </a>
                            <a href="#" class="linkpage-row--image-remove" style="display: none;">
								<?php _e( 'Remove image', CLICKWHALE_NAME ) ?>
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
                                        <ion-icon name="<?php echo $image_id ?>"></ion-icon>
									<?php } ?>
                                </label>
                            </div>
                            <a id="icon-picker-<?php echo $data['id'] ?>" class="icon-picker" href="#">
								<?php _e( 'Select Icon', CLICKWHALE_NAME ) ?>
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
                            <a class="emoji-picker" href="#"><?php _e( 'Select Emoji', CLICKWHALE_NAME ) ?></a>
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
			'accessibility-outline',
			'add-outline',
			'add-circle-outline',
			'airplane-outline',
			'alarm-outline',
			'albums-outline',
			'alert-outline',
			'alert-circle-outline',
			'american-football-outline',
			'analytics-outline',
			'aperture-outline',
			'apps-outline',
			'archive-outline',
			'arrow-back-outline',
			'arrow-back-circle-outline',
			'arrow-down-outline',
			'arrow-down-circle-outline',
			'arrow-forward-outline',
			'arrow-forward-circle-outline',
			'arrow-redo-outline',
			'arrow-redo-circle-outline',
			'arrow-undo-outline',
			'arrow-undo-circle-outline',
			'arrow-up-outline',
			'arrow-up-circle-outline',
			'at-outline',
			'at-circle-outline',
			'attach-outline',
			'backspace-outline',
			'bag-outline',
			'bag-add-outline',
			'bag-check-outline',
			'bag-handle-outline',
			'bag-remove-outline',
			'balloon-outline',
			'ban-outline',
			'bandage-outline',
			'bar-chart-outline',
			'barbell-outline',
			'barcode-outline',
			'baseball-outline',
			'basket-outline',
			'basketball-outline',
			'battery-charging-outline',
			'battery-dead-outline',
			'battery-full-outline',
			'battery-half-outline',
			'beaker-outline',
			'bed-outline',
			'beer-outline',
			'bicycle-outline',
			'bluetooth-outline',
			'boat-outline',
			'body-outline',
			'bonfire-outline',
			'book-outline',
			'bookmark-outline',
			'bookmarks-outline',
			'bowling-ball-outline',
			'briefcase-outline',
			'browsers-outline',
			'brush-outline',
			'bug-outline',
			'build-outline',
			'bulb-outline',
			'bus-outline',
			'business-outline',
			'cafe-outline',
			'calculator-outline',
			'calendar-outline',
			'calendar-clear-outline',
			'calendar-number-outline',
			'call-outline',
			'camera-outline',
			'camera-reverse-outline',
			'car-outline',
			'car-sport-outline',
			'card-outline',
			'caret-back-outline',
			'caret-back-circle-outline',
			'caret-down-outline',
			'caret-down-circle-outline',
			'caret-forward-outline',
			'caret-forward-circle-outline',
			'caret-up-outline',
			'caret-up-circle-outline',
			'cart-outline',
			'cash-outline',
			'cellular-outline',
			'chatbox-outline',
			'chatbox-ellipses-outline',
			'chatbubble-outline',
			'chatbubble-ellipses-outline',
			'chatbubbles-outline',
			'checkbox-outline',
			'checkmark-outline',
			'checkmark-circle-outline',
			'checkmark-done-outline',
			'checkmark-done-circle-outline',
			'chevron-back-outline',
			'chevron-back-circle-outline',
			'chevron-collapse-outline',
			'chevron-down-outline',
			'chevron-down-circle-outline',
			'chevron-expand-outline',
			'chevron-forward-outline',
			'chevron-forward-circle-outline',
			'chevron-up-outline',
			'chevron-up-circle-outline',
			'clipboard-outline',
			'close-outline',
			'close-circle-outline',
			'cloud-outline',
			'cloud-circle-outline',
			'cloud-done-outline',
			'cloud-download-outline',
			'cloud-offline-outline',
			'cloud-upload-outline',
			'cloudy-outline',
			'cloudy-night-outline',
			'code-outline',
			'code-download-outline',
			'code-slash-outline',
			'code-working-outline',
			'cog-outline',
			'color-fill-outline',
			'color-filter-outline',
			'color-palette-outline',
			'color-wand-outline',
			'compass-outline',
			'construct-outline',
			'contract-outline',
			'contrast-outline',
			'copy-outline',
			'create-outline',
			'crop-outline',
			'cube-outline',
			'cut-outline',
			'desktop-outline',
			'diamond-outline',
			'dice-outline',
			'disc-outline',
			'document-outline',
			'document-attach-outline',
			'document-lock-outline',
			'document-text-outline',
			'documents-outline',
			'download-outline',
			'duplicate-outline',
			'ear-outline',
			'earth-outline',
			'easel-outline',
			'egg-outline',
			'ellipse-outline',
			'ellipsis-horizontal-outline',
			'ellipsis-horizontal-circle-outline',
			'ellipsis-vertical-outline',
			'ellipsis-vertical-circle-outline',
			'enter-outline',
			'exit-outline',
			'expand-outline',
			'extension-puzzle-outline',
			'eye-outline',
			'eye-off-outline',
			'eyedrop-outline',
			'fast-food-outline',
			'female-outline',
			'file-tray-outline',
			'file-tray-full-outline',
			'file-tray-stacked-outline',
			'film-outline',
			'filter-outline',
			'filter-circle-outline',
			'finger-print-outline',
			'fish-outline',
			'fitness-outline',
			'flag-outline',
			'flame-outline',
			'flash-outline',
			'flash-off-outline',
			'flashlight-outline',
			'flask-outline',
			'flower-outline',
			'folder-outline',
			'folder-open-outline',
			'football-outline',
			'footsteps-outline',
			'funnel-outline',
			'game-controller-outline',
			'gift-outline',
			'git-branch-outline',
			'git-commit-outline',
			'git-compare-outline',
			'git-merge-outline',
			'git-network-outline',
			'git-pull-request-outline',
			'glasses-outline',
			'globe-outline',
			'golf-outline',
			'grid-outline',
			'hammer-outline',
			'hand-left-outline',
			'hand-right-outline',
			'happy-outline',
			'hardware-chip-outline',
			'headset-outline',
			'heart-outline',
			'heart-circle-outline',
			'heart-dislike-outline',
			'heart-dislike-circle-outline',
			'heart-half-outline',
			'help-outline',
			'help-buoy-outline',
			'help-circle-outline',
			'home-outline',
			'hourglass-outline',
			'ice-cream-outline',
			'id-card-outline',
			'image-outline',
			'images-outline',
			'infinite-outline',
			'information-outline',
			'information-circle-outline',
			'invert-mode-outline',
			'journal-outline',
			'key-outline',
			'keypad-outline',
			'language-outline',
			'laptop-outline',
			'layers-outline',
			'leaf-outline',
			'library-outline',
			'link-outline',
			'list-outline',
			'list-circle-outline',
			'locate-outline',
			'location-outline',
			'lock-closed-outline',
			'lock-open-outline',
			'log-in-outline',
			'log-out-outline',
			'logo-alipay',
			'logo-amazon',
			'logo-amplify',
			'logo-android',
			'logo-angular',
			'logo-apple',
			'logo-apple-appstore',
			'logo-apple-ar',
			'logo-behance',
			'logo-bitbucket',
			'logo-bitcoin',
			'logo-buffer',
			'logo-capacitor',
			'logo-chrome',
			'logo-closed-captioning',
			'logo-codepen',
			'logo-css3',
			'logo-designernews',
			'logo-deviantart',
			'logo-discord',
			'logo-docker',
			'logo-dribbble',
			'logo-dropbox',
			'logo-edge',
			'logo-electron',
			'logo-euro',
			'logo-facebook',
			'logo-figma',
			'logo-firebase',
			'logo-firefox',
			'logo-flickr',
			'logo-foursquare',
			'logo-github',
			'logo-gitlab',
			'logo-google',
			'logo-google-playstore',
			'logo-hackernews',
			'logo-html5',
			'logo-instagram',
			'logo-ionic',
			'logo-ionitron',
			'logo-javascript',
			'logo-laravel',
			'logo-linkedin',
			'logo-markdown',
			'logo-mastodon',
			'logo-medium',
			'logo-microsoft',
			'logo-no-smoking',
			'logo-nodejs',
			'logo-npm',
			'logo-octocat',
			'logo-paypal',
			'logo-pinterest',
			'logo-playstation',
			'logo-pwa',
			'logo-python',
			'logo-react',
			'logo-reddit',
			'logo-rss',
			'logo-sass',
			'logo-skype',
			'logo-slack',
			'logo-snapchat',
			'logo-soundcloud',
			'logo-stackoverflow',
			'logo-steam',
			'logo-stencil',
			'logo-tableau',
			'logo-tiktok',
			'logo-tumblr',
			'logo-tux',
			'logo-twitch',
			'logo-twitter',
			'logo-usd',
			'logo-venmo',
			'logo-vercel',
			'logo-vimeo',
			'logo-vk',
			'logo-vue',
			'logo-web-component',
			'logo-wechat',
			'logo-whatsapp',
			'logo-windows',
			'logo-wordpress',
			'logo-xbox',
			'logo-xing',
			'logo-yahoo',
			'logo-yen',
			'logo-youtube',
			'magnet-outline',
			'mail-outline',
			'mail-open-outline',
			'mail-unread-outline',
			'male-outline',
			'male-female-outline',
			'man-outline',
			'map-outline',
			'medal-outline',
			'medical-outline',
			'medkit-outline',
			'megaphone-outline',
			'menu-outline',
			'mic-outline',
			'mic-circle-outline',
			'mic-off-outline',
			'mic-off-circle-outline',
			'moon-outline',
			'move-outline',
			'musical-note-outline',
			'musical-notes-outline',
			'navigate-outline',
			'navigate-circle-outline',
			'newspaper-outline',
			'notifications-outline',
			'notifications-circle-outline',
			'notifications-off-outline',
			'notifications-off-circle-outline',
			'nuclear-outline',
			'nutrition-outline',
			'open-outline',
			'options-outline',
			'paper-plane-outline',
			'partly-sunny-outline',
			'pause-outline',
			'pause-circle-outline',
			'paw-outline',
			'pencil-outline',
			'people-outline',
			'people-circle-outline',
			'person-outline',
			'person-add-outline',
			'person-circle-outline',
			'person-remove-outline',
			'phone-landscape-outline',
			'phone-portrait-outline',
			'pie-chart-outline',
			'pin-outline',
			'pint-outline',
			'pizza-outline',
			'planet-outline',
			'play-outline',
			'play-back-outline',
			'play-back-circle-outline',
			'play-circle-outline',
			'play-forward-outline',
			'play-forward-circle-outline',
			'play-skip-back-outline',
			'play-skip-back-circle-outline',
			'play-skip-forward-outline',
			'play-skip-forward-circle-outline',
			'podium-outline',
			'power-outline',
			'pricetag-outline',
			'pricetags-outline',
			'print-outline',
			'prism-outline',
			'pulse-outline',
			'push-outline',
			'qr-code-outline',
			'radio-outline',
			'radio-button-off-outline',
			'radio-button-on-outline',
			'rainy-outline',
			'reader-outline',
			'receipt-outline',
			'recording-outline',
			'refresh-outline',
			'refresh-circle-outline',
			'reload-outline',
			'reload-circle-outline',
			'remove-outline',
			'remove-circle-outline',
			'reorder-four-outline',
			'reorder-three-outline',
			'reorder-two-outline',
			'repeat-outline',
			'resize-outline',
			'restaurant-outline',
			'return-down-back-outline',
			'return-down-forward-outline',
			'return-up-back-outline',
			'return-up-forward-outline',
			'ribbon-outline',
			'rocket-outline',
			'rose-outline',
			'sad-outline',
			'save-outline',
			'scale-outline',
			'scan-outline',
			'scan-circle-outline',
			'school-outline',
			'search-outline',
			'search-circle-outline',
			'send-outline',
			'server-outline',
			'settings-outline',
			'shapes-outline',
			'share-outline',
			'share-social-outline',
			'shield-outline',
			'shield-checkmark-outline',
			'shield-half-outline',
			'shirt-outline',
			'shuffle-outline',
			'skull-outline',
			'snow-outline',
			'sparkles-outline',
			'speedometer-outline',
			'square-outline',
			'star-outline',
			'star-half-outline',
			'stats-chart-outline',
			'stop-outline',
			'stop-circle-outline',
			'stopwatch-outline',
			'storefront-outline',
			'subway-outline',
			'sunny-outline',
			'swap-horizontal-outline',
			'swap-vertical-outline',
			'sync-outline',
			'sync-circle-outline',
			'tablet-landscape-outline',
			'tablet-portrait-outline',
			'telescope-outline',
			'tennisball-outline',
			'terminal-outline',
			'text-outline',
			'thermometer-outline',
			'thumbs-down-outline',
			'thumbs-up-outline',
			'thunderstorm-outline',
			'ticket-outline',
			'time-outline',
			'timer-outline',
			'today-outline',
			'toggle-outline',
			'trail-sign-outline',
			'train-outline',
			'transgender-outline',
			'trash-outline',
			'trash-bin-outline',
			'trending-down-outline',
			'trending-up-outline',
			'triangle-outline',
			'trophy-outline',
			'tv-outline',
			'umbrella-outline',
			'unlink-outline',
			'videocam-outline',
			'videocam-off-outline',
			'volume-high-outline',
			'volume-low-outline',
			'volume-medium-outline',
			'volume-mute-outline',
			'volume-off-outline',
			'walk-outline',
			'wallet-outline',
			'warning-outline',
			'watch-outline',
			'water-outline',
			'wifi-outline',
			'wine-outline',
			'woman-outline'
		);
	}

	private function get_clicks( string $linkpage_id, string $id, bool $is_link = true ) {
		return Linkpages_Helper::get_linkpage_link_clicks( $linkpage_id, $id, $is_link );
	}
}