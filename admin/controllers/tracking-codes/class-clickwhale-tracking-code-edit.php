<?php

class ClickwhaleTrackingCodeEdit {
	private static $instance;

	public function init() {
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

	public function get_linkpages() {
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

	public function get_posts_by_post_type( $post_type ) {
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

	public function save_update_tracking_code() {
		global $wpdb;

		$tracking_codes_table = $wpdb->prefix . 'clickwhale_tracking_codes';
		$item                 = array_intersect_key( $_POST, $this->get_defaults() );
		$item['author']       = get_current_user_id();
		$item['position']     = maybe_serialize( $item['position'] );
		$item['is_active']    = $item['is_active'] ?? 0;

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

                var postsSelect = jQuery('#position_post_id');

                jQuery('#position_code').select2({
                    placeholder: '<?php _e( 'Select Code position', 'clickwhale' ) ?>',
                    width: '100%',
                    minimumResultsForSearch: -1
                });
                jQuery('#position_linkpage_ids, #position_page_ids, #position_post_ids').select2({
                    placeholder: '<?php _e( 'Select LP', 'clickwhale' ) ?>',
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
                    jQuery('.cw-posts-row').show();
                } else {
                    jQuery('.cw-posts-row').hide();
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

                jQuery(document)
                    .on('change', '[name="position[pages]"]', function () {
                        if (jQuery(this).val() !== 'all') {
                            jQuery('.cw-posts-row').show();
                        } else {
                            jQuery('.cw-posts-row').hide();
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