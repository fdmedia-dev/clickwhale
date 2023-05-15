<?php

class Clickwhale_Link_Edit {

	private static $instance;

	public function init() {
		add_action( 'admin_print_footer_scripts', [ $this, 'admin_scripts' ] );
	}

	public static function getInstance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Default values for new link
	 * Could be hooked by filter "clickwhale_link_defaults"
	 * @return array
	 */
	public function get_defaults() {
		return array(
			'id'          => 0,
			'created_at'  => '',
			'updated_at'  => '',
			'title'       => '',
			'url'         => '',
			'slug'        => '',
			'redirection' => $this->get_item_option( 'general', 'redirect_type' ),
			'nofollow'    => '',
			'sponsored'   => '',
			'description' => '',
			'categories'  => '',
		);
	}

	public function get_item( $request ) {
		global $wpdb;

		$notice   = '';
		$defaults = apply_filters( 'clickwhale_link_defaults', $this->get_defaults() );

		if ( isset( $request['id'] ) ) {
			$item = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}clickwhale_links WHERE id = %d",
				intval( $request['id'] ) ), ARRAY_A );
			if ( ! $item ) {
				$item   = $defaults;
				$notice = __( 'Item not found', 'clickwhale' );
			}
		} else {
			$item = $defaults;
		}

		return $item;
	}

	public static function get_item_option( $group, $name ) {
		$options = get_option( 'clickwhale_' . $group . '_options' );
		if ( isset( $options[ $name ] ) && $options[ $name ] !== '' ) {
			$option = $options[ $name ];
		} else {
			$settings = Clickwhale_Admin_Settings::getInstance();
			$defaults = $settings->default_options();
			$option   = $defaults[ $group ]['options'][ $name ];
		}

		return $option;
	}

	public function clickwhale_validate_link( $item ) {
		$messages = array();

		if ( empty( $item['title'] ) ) {
			$messages[] = __( 'Title is required', 'clickwhale' );
		}
		if ( empty( $item['url'] ) ) {
			$messages[] = __( 'Target URL is required', 'clickwhale' );
		}
		if ( empty( $item['slug'] ) ) {
			$messages[] = __( 'Slug is required', 'clickwhale' );
		}
		if ( ! ctype_digit( $item['redirection'] ) ) {
			$messages[] = __( 'Wrong redirection code', 'clickwhale' );
		}
		if ( ! empty( $item['redirection'] ) && ! absint( intval( $item['redirection'] ) ) ) {
			$messages[] = __( 'Redirection code can not be less than zero' );
		}
		if ( ! empty( $item['redirection'] ) && ! preg_match( '/[0-9]+/', $item['redirection'] ) ) {
			$messages[] = __( 'Redirection code must be number' );
		}
		if ( empty( $item['slug'] ) ) {
			$messages[] = __( 'Slug is required', 'clickwhale' );
		}
		//if (!empty($item['email']) && !is_email($item['email'])) $messages[] = __('E-Mail is in wrong format', 'clickwhale');

		if ( empty( $messages ) ) {
			return true;
		}

		return implode( '<br />', $messages );
	}

	public function get_link_categories() {
		global $wpdb;

		$results = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}clickwhale_categories" );
		if ( ! empty( $results ) ) {
			return $results;
		}
	}

	public function link_categories_to_string( array $categories ) {
		return implode( ',', $categories );
	}

	/**
	 * Check if Link slug already exists
	 *
	 * @param string $slug
	 *
	 * @return bool
	 */
	public static function check_slug( $slug, $id ) {
		global $wpdb;
		if ( $wpdb->get_row( $wpdb->prepare( "SELECT slug FROM {$wpdb->prefix}clickwhale_links WHERE slug=%s AND id!=%d",
			$slug, $id ), 'ARRAY_A' ) ) {
			return true;
		} else {
			return false;
		}
	}

	public function save_update_link() {
		global $wpdb;
		$links_table        = $wpdb->prefix . 'clickwhale_links';
		$item               = array_intersect_key( $_POST, $this->get_defaults() );
		$item['categories'] = isset( $item['categories'] ) ? $this->link_categories_to_string( $item['categories'] ) : '';
		$item['nofollow']   = isset( $item['nofollow'] ) ? 1 : 0;
		$item['sponsored']  = isset( $item['sponsored'] ) ? 1 : 0;
		$item['author']     = get_current_user_id();

		// Check if linkpage exists and then update or insert
		// in some cases default check (not false and < 0) goes wrong
		$link = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}clickwhale_links WHERE id=%d",
			$item['id'] ) );

		if ( $link ) {
			$wpdb->update(
				$links_table,
				$item,
				array( 'id' => $item['id'] )
			);
			do_action( 'clickwhale_update_link_meta', $item['id'], $_POST );
			set_transient( 'link-' . $item['id'], 'link_updated', 45 );
		} else {
			$wpdb->insert(
				$links_table,
				$item
			);
			$item['id'] = $wpdb->insert_id;
			do_action( 'clickwhale_insert_link_meta', $item['id'], $_POST );
			set_transient( 'link-' . $item['id'], 'link_added', 45 );
		}

		$url = 'admin.php?page=clickwhale-edit-link&id=' . $item['id'];
		wp_redirect( admin_url( $url ) );
		die;
	}

	public function set_edit_link_page_title( $admin_title, $title ) {
		return 'Edit Link' . $admin_title;
	}

	public function set_add_link_page_title( $admin_title, $title ) {
		return 'Add Link' . $admin_title;
	}

	public function admin_scripts() {
		$nonce        = wp_create_nonce( 'check_slug' );
		$nonce_random = wp_create_nonce( 'random_slug' );
		?>
		<script type='text/javascript'>
            jQuery(document).ready(function () {

                var linkSubmit = jQuery('#form_edit_link').find('[type="submit"]'),
                    title = jQuery('#title'),
                    slug = jQuery('#cw-slug');

                /**
                 * Submit action
                 * 1. Check title (not null)
                 * 2. Check slug (not null)
                 * 3. Check slug (exists as post/page slug)
                 */
                jQuery('#submit').click(function (e) {

                    if (!title.val() || !slug.val() || check_slug() !== false) {

                        e.preventDefault();

                        if (!title.val()) {
                            title.addClass('error')
                                .next().text('<?php _e( 'Please enter title', 'clickwhale' ) ?>');
                        } else {
                            title.removeClass('error').next().text('');
                        }

                        if (!slug.val()) {
                            slug.addClass('error')
                                .next().text('<?php _e( 'Please enter slug', 'clickwhale' ) ?>')
                        } else {
                            slug.removeClass('error').next().text('');
                        }

                        if (check_slug() === true) {
                            slug.addClass('error');
                            jQuery('#cw-slug--description').text('<?php _e( 'This slug is already in use! Please enter another slug',
								'clickwhale' ) ?>')
                        }
                    }
                });

                function check_slug() {
                    let result = null;
                    jQuery.ajax({
                        async: false,
                        type: 'post',
                        dataType: 'json',
                        url: ajaxurl,
                        data: {
                            'security': '<?php echo $nonce ?>',
                            'action': 'clickwhale/admin/check_slug',
                            'type': 'link',
                            'slug': slug.val(),
                            'id': <?php echo esc_attr( intval( $_GET['id'] ?? 0 ) ); ?>
                        }, success: function (response) {
                            result = response.data;
                        }
                    });

                    return result;
                }

            });
		</script>
		<?php
	}
}
