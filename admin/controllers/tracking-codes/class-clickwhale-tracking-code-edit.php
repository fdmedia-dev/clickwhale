<?php

class ClickwhaleTrackingCodeEdit {
	private static $instance;

	/**
	 * @var
	 * @since 1.3.7
	 */
	public static $conversion;

	public function init() {
		self::$conversion = apply_filters( 'clickwhale_is_tracking_code_conversion', false );
		add_action( 'admin_print_footer_scripts', [ $this, 'admin_scripts' ] );
	}

	public static function getInstance(): ClickwhaleTrackingCodeEdit {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Default values for new link
	 * Could be hooked by filter "clickwhale_tracking_code_defaults"
	 * @return array
	 */
	public function get_defaults(): array {
		return array(
			'id'          => 0,
			'title'       => '',
			'description' => '',
			'type'        => '', //html/css/js
			'code'        => '',
			'position'    => array(), // array(), post/page/CPT/LP // header/footer/body
			'is_active'   => 0,
			'author'      => 0,
			'created_at'  => '',
			'updated_at'  => '',
		);
	}

	public static function get_default_post_types() {
		return apply_filters( 'clickwhale_tracking_code_default_post_types', self::get_post_types() );
	}

	public static function get_default_terms_tax() {
		return apply_filters( 'clickwhale_tracking_code_default_archives', array( 'category' ) );
	}

	public static function conversion_fields( $item ) {
		$is_woo       = class_exists( 'WooCommerce' );
		$is_edd       = function_exists( 'EDD' );
		$mode_options = array(
			'standard' => __( 'Standard code tracking', 'clickwhale-pro' ),
		);

		if ( $is_woo ) {
			$woo_logo                = ADMIN_IMAGES_DIR . '/woocommerce-logo-short-purple.svg';
			$mode_options['product'] = sprintf(
				__( 'Track %s WooCommerce conversion %s', 'clickwhale' ),
				'<img class="checkbox-inline-image" src="' . $woo_logo . '" alt="WooCommerce">',
				ClickwhaleHepler::admin_pro_label()
			);
		}
		if ( $is_edd ) {
			$edd_logo                 = ADMIN_IMAGES_DIR . '/logo-edd-short-dark.svg';
			$mode_options['download'] = sprintf(
				__( 'Track %s Easy Digital Downloads conversion %s',
					'clickwhale' ),
				'<img class="checkbox-inline-image" src="' . $edd_logo . '" alt="Easy Digital Downloads">',
				ClickwhaleHepler::admin_pro_label()
			);
		}

		echo ClickwhaleHepler::render_control(
			array(
				'row_label' => __( 'Where do you want to add this code?', 'clickwhale-pro' ),
				'control'   => 'radio',
				'id'        => 'position_conversion',
				'name'      => 'position[conversion]',
				'value'     => $item['position']['conversion'] ?? '',
				'options'   => $mode_options,
				'default'   => 'standard'
			),
			true
		);

		do_action( 'clickwhale_tracking_code_conversion_fields', $item );
	}

	public function get_item( $request ) {
		global $wpdb;

		$defaults = apply_filters( 'clickwhale_tracking_code_defaults', $this->get_defaults() );

		if ( isset( $request['id'] ) ) {
			$item = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}clickwhale_tracking_codes WHERE id = %d",
				intval( $request['id'] ) ), ARRAY_A );

			$item['position'] = maybe_unserialize( $item['position'] );
			if ( ! $item ) {
				$item = $defaults;
			}
		} else {
			$item = $defaults;
		}

		return $item;
	}

	public static function get_post_types(): array {
		$posts      = [];
		$args       = array(
			'public' => true,
		);
		$post_types = get_post_types( $args, 'objects' );
		unset( $post_types['attachment'] );

		foreach ( $post_types as $post_type ) {
			$posts[] = $post_type->name;
		}

		return $posts;
	}

	public function get_linkpages(): array {
		global $wpdb;

		$result    = [];
		$linkpages = $wpdb->get_results(
			"SELECT id,title from {$wpdb->prefix}clickwhale_linkpages",
			ARRAY_A
		);
		if ( $linkpages ) {
			$result['all'] = __( 'All', 'clickwhale' );
			foreach ( $linkpages as $linkpage ) {
				$result[ $linkpage['id'] ] = $linkpage['title'];
			}
		}

		return $result;
	}

	public static function get_posts_by_post_type( $post_type ): array {
		$result = [];
		$args   = array(
			'numberposts' => - 1,
			'post_type'   => $post_type,
			'orderby'     => 'title',
			'order'       => 'ASC',
			'post_status' => 'publish'
		);
		$posts  = get_posts( $args );

		if ( $posts ) {
			$result['all'] = __( 'All', 'clickwhale' );
			foreach ( $posts as $post ) {
				$result[ $post->ID ] = $post->post_title;
			}
		}

		return $result;
	}

	public function get_terms_by_tax( $taxonomy ): array {
		$result = [];
		$args   = array(
			'taxonomy'   => $taxonomy,
			'hide_empty' => false,
		);
		$terms  = get_terms( $args );
		if ( $terms ) {
			$result['all'] = __( 'All', 'clickwhale' );
			foreach ( $terms as $term ) {
				$result[ $term->term_id ] = $term->name;
			}
		}

		return $result;

	}

	public function save_update_tracking_code() {
		global $wpdb;

		$tracking_codes_table = $wpdb->prefix . 'clickwhale_tracking_codes';
		$item                 = array_intersect_key( $_POST, $this->get_defaults() );
		$item['description']  = esc_html( $item['description'] );
		$item['author']       = get_current_user_id();

		if ( isset( $item['position']['conversion'] ) && $item['position']['conversion'] !== 'standard' ) {
			unset( $item['position']['items_included'] );
			unset( $item['position']['items_excluded'] );
			unset( $item['position']['code'] );
			unset( $item['position']['pages'] );
			foreach ( $item['position']['conversion_items'] as $k => $v ) {
				if ( $k !== $item['position']['conversion'] ) {
					unset( $item['position']['conversion_items'][ $k ] );
				}
			}
		} else {
			unset( $item['position']['conversion_items'] );
		}

		// handle CW Link Pages
		if ( ! isset( $item['position']['items_included']['cw_linkpage']['active'] ) ) {
			unset( $item['position']['items_included']['cw_linkpage'] );
		}
		if ( ! isset( $item['position']['items_excluded']['cw_linkpage']['active'] ) ) {
			unset( $item['position']['items_excluded']['cw_linkpage'] );
		}

		// Handle Post Types
		foreach ( $this->get_default_post_types() as $post_type ) {
			if ( ! isset( $item['position']['items_included'][ $post_type ]['active'] ) ) {
				unset( $item['position']['items_included'][ $post_type ] );
			}
			if ( ! isset( $item['position']['items_excluded'][ $post_type ]['active'] ) ) {
				unset( $item['position']['items_excluded'][ $post_type ] );
			}
		}

		// Handle Taxonomies
		foreach ( $this->get_default_terms_tax() as $taxonomy ) {
			if ( ! isset( $item['position']['items_included'][ $taxonomy ]['active'] ) ) {
				unset( $item['position']['items_included'][ $taxonomy ] );
			}
			if ( ! isset( $item['position']['items_excluded'][ $taxonomy ]['active'] ) ) {
				unset( $item['position']['items_excluded'][ $taxonomy ] );
			}
		}

		$item['position']  = maybe_serialize( $item['position'] );
		$item['is_active'] = $item['is_active'] ?? 0;

		$item = apply_filters( 'clickwhale_tracking_code_data_before_save', $item );

		$result = $wpdb->update(
			$tracking_codes_table,
			$item,
			array( 'id' => $item['id'] )
		);

		if ( false === $result || $result < 1 ) {
			$wpdb->insert(
				$tracking_codes_table,
				$item
			);
			$item['id'] = $wpdb->insert_id;
			set_transient( 'tracking-code-' . $item['id'], 'tracking_code_added', 45 );
		} else {
			set_transient( 'tracking-code-' . $item['id'], 'tracking_code_updated', 45 );
		}

		$url = 'admin.php?page=clickwhale-edit-tracking-code&id=' . $item['id'];
		wp_redirect( admin_url( $url ) );
		die;
	}

	public function set_edit_tracking_code_page_title( $admin_title, $title ): string {
		return 'Edit Tracking Code' . $admin_title;
	}

	public function set_add_tracking_code_page_title( $admin_title, $title ): string {
		return 'Add Tracking Code' . $admin_title;
	}

	public function admin_scripts() {
		?>
        <script type='text/javascript'>
            jQuery(document).ready(function () {
                jQuery('#position_code').select2({
                    placeholder: '<?php _e( 'Select Code position', 'clickwhale' ) ?>',
                    width: '100%',
                    minimumResultsForSearch: -1
                });
                jQuery('.with-select2').select2({
                    placeholder: '<?php _e( 'Select', 'clickwhale' ) ?>',
                    width: '100%',
                    multiple: true,
                    minimumResultsForSearch: 10
                });


                if (jQuery('#code').length) {
                    var editorSettings = wp.codeEditor.defaultSettings ? _.clone(wp.codeEditor.defaultSettings) : {};
                    editorSettings.codemirror = _.extend(
                        {},
                        editorSettings.codemirror,
                        {
                            indentUnit: 2,
                            tabSize: 2,
                        }
                    );
                    var editor = wp.codeEditor.initialize(jQuery('#code'), editorSettings);
                }

                // Toggle pages select
                if (jQuery('[name="position[pages]"]:checked').val() !== 'all') {
                    jQuery('.cw-posts-row--included').show();
                    jQuery('.cw-posts-row--excluded').hide();
                } else {
                    jQuery('.cw-posts-row--included').hide();
                    jQuery('.cw-posts-row--excluded').show();
                }

                // Toggle page select
                jQuery('.cw-posts-row').each(function () {
                    var checkbox = jQuery(this).find('[type="checkbox"]'),
                        selectWrap = jQuery(this).find('.cw-posts-row--select');

                    if (checkbox.is(':checked')) {
                        selectWrap.show();
                    } else {
                        selectWrap.hide();
                    }
                });

                // Will be enabled with PRO
                jQuery('[name="position[conversion]"]').prop('disabled', true);
                jQuery('[name="position[conversion]"][value="standard"]').prop('disabled', false);

                jQuery(document)
                    .on('change', '[name="position[pages]"]', function () {
                        if (jQuery(this).val() !== 'all') {
                            jQuery('.cw-posts-row--included').show();
                            jQuery('.cw-posts-row--excluded').hide();
                        } else {
                            jQuery('.cw-posts-row--included').hide();
                            jQuery('.cw-posts-row--excluded').show();
                        }
                    })
                    .on('change', '.cw-posts-row [type="checkbox"]', function () {
                        var parent = jQuery(this).closest('.cw-posts-row');
                        if (jQuery(this).is(':checked')) {
                            jQuery(parent).find('.cw-posts-row--select').show()
                        } else {
                            jQuery(parent).find('.cw-posts-row--select').hide()
                        }
                    })

            });
        </script>
		<?php
	}
}