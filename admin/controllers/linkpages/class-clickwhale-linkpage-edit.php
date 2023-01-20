<?php

class Clickwhale_Linkpage_Edit {
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
			'title'       => '',
			'slug'        => '',
			'description' => '',
			'links'       => '',
			'logo'        => '',
			'styles'      => array(
				'bg_color'            => '#fdd231',
				'text_color'          => '#1a1c1d',
				'link_bg_color'       => '#fee06f',
				'link_color'          => '#1a1c1d',
				'link_bg_color_hover' => '#ffffff',
				'link_color_hover'    => '#397eff',
			),
		);
	}

	public function get_item( $request ) {
		global $wpdb;

		$defaults = apply_filters( 'clickwhale_linkpage_defaults', $this->get_defaults() );

		if ( isset( $request['id'] ) ) {
			$item = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}clickwhale_linkpages WHERE id = %d", intval( $request['id'] ) ), ARRAY_A );
			if ( ! $item ) {
				$item   = $defaults;
				$notice = __( 'Item not found', 'clickwhale' );
			}
		} else {
			$item = $defaults;
		}

		return $item;
	}

	public function get_link( $id ) {
		global $wpdb;

		return $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$wpdb->prefix}clickwhale_links WHERE id=%d", $id ),
			ARRAY_A
		);
	}

	public function get_links() {
		global $wpdb;

		return $wpdb->get_results(
			"SELECT id,title,url from {$wpdb->prefix}clickwhale_links",
			ARRAY_A
		);
	}

	/**
	 * Check if Link page slug already exists
	 *
	 * @param string $slug
	 *
	 * @return bool
	 */
	public static function check_slug( $slug ) {
		global $wpdb;
		if ( $wpdb->get_row( "SELECT post_name FROM {$wpdb->prefix}posts WHERE post_name='$slug'", 'ARRAY_A' ) ) {
			return true;
		} else {
			return false;
		}
	}


	public function save_update_linkpage() {
		global $wpdb;
		$linkpages_table = $wpdb->prefix . 'clickwhale_linkpages';
		$defaults        = apply_filters( 'clickwhale_linkpage_defaults', $this->get_defaults() );
		$item            = array_intersect_key( $_POST, $defaults );
		$item['slug']    = sanitize_title( $item['slug'] );
		$item['links']   = isset( $item['links'] ) ? maybe_serialize( $item['links'] ) : '';
		$item['styles']  = isset( $item['styles'] ) ? maybe_serialize( $item['styles'] ) : '';
		$item['author']  = get_current_user_id();

		$item = apply_filters( 'clickwhale_linkpage_data_before_save', $item );

		// Check if linkpage exists and then update or insert
		// in some cases default check (not false and < 0) goes wrong
		$linkpage = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}clickwhale_linkpages WHERE id=%d", $item['id'] ) );

		if ( $linkpage ) {
			$wpdb->update(
				$linkpages_table,
				$item,
				array( 'id' => $item['id'] )
			);
		} else {
			$wpdb->insert(
				$linkpages_table,
				$item
			);
			$item['id'] = $wpdb->insert_id;
		}

		// redirect to new record
		$url = 'admin.php?page=clickwhale-edit-linkpage&id=' . $item['id'];
		wp_redirect( admin_url( $url ) );
		die;
	}

	public function set_edit_linkpage_page_title( $admin_title, $title ) {
		return 'Edit Link Page' . $admin_title;
	}

	public function set_add_linkpage_page_title( $admin_title, $title ) {
		return 'Add Link Page' . $admin_title;
	}

	public function render_cw_link( $data ) {
		$output = '';
		$id     = $data['id'];
		$link   = $this->get_link( $id );

		$output .= '<div class="linkpage-row">';
		$output .= '<input type="hidden" name="links[' . esc_attr( $id ) . '][id]" value="' . esc_attr( $id ) . '"/>';
		$output .= '<input type="hidden" name="links[' . esc_attr( $id ) . '][type]" value="' . esc_attr( $data['type'] ?? 'cw_link' ) . '"/>';
		$output .= '<div class="linkpage-row--drag"></div>';
		$output .= '<div class="linkpage-link">' . esc_html( wp_unslash( $link['title'] ) ) . '<span>' . esc_url( $link['url'] ) . '</span></div>';
		$output .= '<div class="linkpage-link--title"><input type="text" name="links[' . esc_attr( $id ) . '][title]" value="' . esc_html( wp_unslash( $data['title'] ) ) . '" placeholder="' . __( 'Link Title', 'clickwhale' ) . '"></div>';
		$output .= '<div class="linkpage-row--remove"></div>';
		$output .= '</div>';

		return $output;
	}

	public function render_custom_link( $data ) {
		$output = '';
		$id     = $data['id'];

		$output .= '<div class="linkpage-row">';
		$output .= '<input type="hidden" name="links[' . esc_attr( $id ) . '][id]" value="' . esc_attr( $id ) . '">';
		$output .= '<input type="hidden" name="links[' . esc_attr( $id ) . '][type]" value="' . $data['type'] . '">';
		$output .= '<input type="hidden" name="links[' . esc_attr( $id ) . '][title]" value="' . $data['title'] . '">';
		$output .= '<input type="hidden" name="links[' . esc_attr( $id ) . '][url]" value="' . $data['url'] . '">';
		$output .= '<div class="linkpage-row--drag"></div>';
		$output .= '<div class="linkpage-link">' . esc_html( wp_unslash( $data['title'] ) ) . '<span>' . esc_url( $data['url'] ) . '</span></div>';
		$output .= '<div class="linkpage-row--remove"></div>';
		$output .= '</div>';

		return $output;
	}

	public function admin_scripts() {
		$nonce = wp_create_nonce( 'check_slug' );
		?>
        <script type='text/javascript'>
            jQuery(document).ready(function () {

                jQuery('.cw-color-control').wpColorPicker();

                jQuery('#clickwhale-tabs').tabs({
                    activate: function (event, ui) {
                        if (jQuery(ui.newPanel[0]).attr('id') === 'lp-tab-colors') {
                            jQuery('#reset-colors').show();
                        } else {
                            jQuery('#reset-colors').hide();
                        }
                    }
                });

				<?php if ( isset( $_GET['page'] ) && $_GET['page'] === 'clickwhale-edit-linkpage' && isset( $_GET['id'] ) ) { ?>

                var page_id = '<?php echo sanitize_text_field( intval( $_GET['id'] ) ); ?>';

                if (localStorage.getItem('tab-' + page_id)) {
                    jQuery('#clickwhale-tabs').tabs({active: localStorage.getItem('tab-' + page_id)});
                }

                jQuery('#clickwhale-tabs li').click(function () {
                    localStorage.setItem('tab-' + page_id, jQuery(this).index());
                });

				<?php } ?>

                var wrap = jQuery('.linkpage-wrap'),
                    limit = <?php echo ClickwhaleLinkpagesHelper::get_links_limit() ?>;

                if (jQuery('.linkpage-row').length >= limit) {
                    jQuery('#add-pagelink-link').prop('disabled', true);
                }

                jQuery(wrap).sortable({
                    placeholder: "ui-state-highlight"
                });
                wrap.disableSelection();

                jQuery(document)
                    .on('change', '#add-pagelink-select', function (e) {
                        if (this.value === 'custom') {
                            jQuery('#add-pagelink-link').prop('disabled', true);
                            jQuery('.custom-links-action-wrap').show();
                        } else {
                            jQuery('#add-pagelink-link').prop('disabled', false);
                            jQuery('.custom-links-action-wrap').hide();
                        }
                    })
                    // Add link row
                    .on('click', '#add-pagelink-link', function (e) {
                        e.preventDefault();

                        var links = jQuery('#add-pagelink-select'),
                            link = get_new_link_data('cw_link', links.find('option:selected').text(), links.find('option:selected').data('url'), links.find('option:selected').val()),
                            template = '<div class="linkpage-row"><input type="hidden" name="links[' + link.id + '][type]" value="' + link.type + '"><input type="hidden" name="links[' + link.id + '][id]" value="' + link.id + '"><div class="linkpage-row--drag"></div><div class="linkpage-link">' + link.title + ' <span>' + link.url + '</span></div><div class="linkpage-link--title"><input type="text" name="links[' + link.id + '][title]" placeholder="' + link.placeholder + '"></div><div class="linkpage-link--image"></div><div class="linkpage-row--remove"></div></div>';

                        if (count_links() < limit) {
                            append_link(wrap, template);
                        }
                        if ((count_links() + 1) === limit) {
                            links_limit_warning();
                        }
                    })

                    // add custom link
                    .on('click', '#add-custom-link', function (e) {
                        e.preventDefault();

                        var link = get_new_link_data('custom_link', jQuery('input[name="custom-link-title"]').val(), jQuery('input[name="custom-link-url"]').val()),
                            i_type = '<input type="hidden" name="links[' + link.id + '][type]" value="' + link.type + '">',
                            i_id = '<input type="hidden" name="links[' + link.id + '][id]" value="' + link.id + '">',
                            i_title = '<input type="hidden" name="links[' + link.id + '][title]" value="' + link.title + '">',
                            i_url = '<input type="hidden" name="links[' + link.id + '][url]" value="' + link.url + '">',
                            template = '<div class="linkpage-row">' + i_type + i_id + i_title + i_url + '<div class="linkpage-row--drag"></div><div class="linkpage-link">' + link.title + ' <span>' + link.url + '</span></div><div class="linkpage-link--image"></div><div class="linkpage-row--remove"></div></div>';

                        jQuery('.custom-links-action-wrap input').val('');

                        if (count_links() < limit) {
                            append_link(wrap, template);
                        }
                        if ((count_links() + 1) === limit) {
                            links_limit_warning();
                        }
                    })

                    // Remove added link row
                    .on('click', '.linkpage-row--remove', function () {
                        jQuery(this).parent().remove();
                        if (jQuery('.linkpage-row').length < limit) {
                            jQuery('.links-info').remove();
                            jQuery('#add-pagelink-link').prop('disabled', false);
                        }
                    })
                    // Logo upload
                    .on('click', '.linkpage-logo-upload', function (e) {
                        e.preventDefault();

                        var button = jQuery(this),
                            custom_uploader = wp.media({
                                title: 'Insert image',
                                library: {
                                    // uploadedTo : wp.media.view.settings.post.id, // attach to the current post?
                                    type: 'image'
                                },
                                button: {
                                    text: 'Use this image' // button label text
                                },
                                multiple: false
                            }).on('select', function () { // it also has "open" and "close" events
                                var attachment = custom_uploader.state().get('selection').first().toJSON();
                                button.html('<img src="' + attachment.url + '">').next().show().next().val(attachment.id);
                            }).open();

                    })
                    // logo remove
                    .on('click', '.linkpage-logo-remove', function (e) {
                        e.preventDefault();

                        var button = jQuery(this);

                        button.next().val(''); // emptying the hidden field
                        button.hide().prev().html('Upload image');
                    });

                /**
                 * Check slug
                 */
                jQuery('#form_edit_linkpage').on('blur', '#cw-slug', function (e) {

                    var slug = jQuery(this),
                        linkpageSubmit = jQuery('#form_edit_linkpage').find('[type="submit"]');

                    linkpageSubmit.prop('disabled', true);

                    jQuery.post(ajaxurl, {
                        'security': '<?php echo $nonce ?>',
                        'action': 'clickwhale/admin/check_slug',
                        'type': 'linkpage',
                        'slug': slug.val()
                    }, function (response) {
                        // slug exists
                        if (response.data === true) {
                            slug.addClass('error');
                            jQuery('#cw-slug--description').text('<?php _e( 'This slug is already in use! Please enter another slug', 'clickwhale' ) ?>');
                        }
                        // slug doesn't exists
                        if (response.data === false) {
                            slug.removeClass('error');
                            jQuery('#cw-slug--description').text('');
                            linkpageSubmit.prop('disabled', false);
                        }
                        // slug empty or error
                        if (response.data === 'error') {
                            slug.addClass('error');
                            jQuery('#cw-slug--description').text('<?php _e( 'Please enter slug', 'clickwhale' ) ?>');
                        }
                    })
                });

                jQuery('#reset-colors').click(function (e) {
                    e.preventDefault();
                    if (window.confirm('<?php _e( 'Are you sure? This action will set colors to default. This process cannot be undone!', 'clickwhale' ) ?>')) {
                        $defaults = <?php echo json_encode( $this->get_defaults() ) ?>;

                        jQuery.each($defaults.styles, function (key, val) {
                            jQuery('[name="styles[' + key + ']"').wpColorPicker('color', val);
                        });
                    }
                });

                function count_links() {
                    return jQuery('.linkpage-row').length;
                }

                function get_new_link_data(type, title, url, id = link_uniqid()) {
                    return {
                        type: type,
                        id: id,
                        title: title.trim(),
                        url: url,
                        placeholder: "<?php _e( 'Link Title', 'clickwhale' ); ?>"
                    }
                }

                function append_link(target, template) {
                    target.append(template);
                }

                function links_limit_warning() {
                    jQuery('#add-pagelink-link').prop('disabled', true);
                    jQuery('<div class="links-info"><?php printf( 'Currently, a maximum of %d links can be added', ClickwhaleLinkpagesHelper::get_links_limit() ); ?></div>').insertAfter('.linkpage-wrap');
                }

                function link_uniqid(prefix = "", random = false) {
                    const sec = Date.now() * 1000 + Math.random() * 1000;
                    const id = sec.toString(16).replace(/\./g, "").padEnd(14, "0");
                    return `${prefix}${id}${random ? `.${Math.trunc(Math.random() * 100000000)}` : ""}`;
                };

            });
        </script>
		<?php
	}
}