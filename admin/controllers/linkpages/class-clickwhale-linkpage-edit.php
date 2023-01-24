<?php

class Clickwhale_Linkpage_Edit {
	private static $instance;

	public function init() {
		add_action( 'admin_print_footer_scripts', [ $this, 'admin_scripts' ] );
	}

	public static function getInstance(): Clickwhale_Linkpage_Edit {
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
	public function get_defaults(): array {
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
			$item = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}clickwhale_linkpages WHERE id = %d",
				intval( $request['id'] ) ), ARRAY_A );
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
			$posts[ $post_type->name ] = $post_type->labels->singular_name;
		}

		return $posts;
	}

	public function get_link( $id ) {
		global $wpdb;

		return $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$wpdb->prefix}clickwhale_links WHERE id=%d", $id ),
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
	public static function check_slug( string $slug ): bool {
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
		$linkpage = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}clickwhale_linkpages WHERE id=%d",
			$item['id'] ) );

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

	public function set_edit_linkpage_page_title( $admin_title, $title ): string {
		return 'Edit Link Page' . $admin_title;
	}

	public function set_add_linkpage_page_title( $admin_title, $title ): string {
		return 'Add Link Page' . $admin_title;
	}

	public function render_link( array $data ): string {
		$output = '';

		$id                = esc_attr( $data['id'] );
		$type              = isset( $data['type'] ) ? esc_attr( $data['type'] ) : 'cw_link';
		$title             = esc_attr( wp_unslash( $data['title'] ) );
		$custom_title      = $title;
		$url               = '';
		$is_post_type      = array_key_exists( $type, Clickwhale_Linkpage_Edit::get_post_types() );
		$title_placeholder = __( 'Custom Title', 'clickwhale' );
		$row_class         = $type ? 'row--' . $type : '';

		if ( $type === 'custom_link' ) {
			$url               = esc_url( $data['url'] );
			$title_placeholder = __( 'Link Title', 'clickwhale' );
			$url_placeholder   = __( 'Link URL', 'clickwhale' );
		} elseif ( $is_post_type ) {
			$post_id        = esc_attr( $data['post_id'] );
			$row_class      .= get_post_status( $post_id ) === 'publish' ? '' : ' unavailable-link';
			$title          = get_the_title( $post_id );
			$post_type_name = get_post_type_object( $type )->labels->singular_name;
			$origin         = __( 'Original', 'clickwhale' ) . ' ' . $post_type_name . ': ';
		} else {
			$link  = $this->get_link( $id );
			$title = esc_attr( wp_unslash( $link['title'] ) );
			$url   = esc_url( $link['url'] );
		}

		//$link   = $this->get_link( $id );

		$output .= '<div class="linkpage-row ' . $row_class . '">';
		$output .= '<input type="hidden" name="links[' . $id . '][type]" value="' . $type . '"/>';
		$output .= '<input type="hidden" name="links[' . $id . '][id]" value="' . $id . '">';

		if ( $is_post_type ) {
			$output .= '<input type="hidden" name="links[' . $id . '][post_id]" value="' . $post_id . '">';
		}
		$output .= '<div class="linkpage-row--drag" title="' . __( 'Change Order', 'clickwhale' ) . '"></div>';

		if ( $type === 'custom_link' ) {
			$output .= '<div class="linkpage-link">';
			$output .= '<input type="text" name="links[' . $id . '][title]" class="regular-text" value="' . $title . '" placeholder="' . $title_placeholder . '" required>';
			$output .= '<input type="url" name="links[' . $id . '][url]" class="regular-text" value="' . $url . '"  placeholder="' . $url_placeholder . '" required>';
			$output .= '</div>';
		} else {
			$output .= '<div class="linkpage-link">';
			$output .= $title;
			$output .= '<span>';
			if ( $is_post_type ) {
				$output .= $origin;
				$output .= '<a href="' . get_permalink( $post_id ) . '" target="_blank">' . get_the_title( $post_id ) . '</a>';
			} else {
				$output .= $url;
			}
			$output .= '</span>';
			$output .= '</div>';

			$output .= '<div class="linkpage-link--title">';
			$output .= '<input type="text" name="links[' . $id . '][title]" value="' . $custom_title . '" placeholder="' . $title_placeholder . '">';
			$output .= '</div>';
		}

		$output .= '<div class="linkpage-row--remove" title="' . __( 'Remove item', 'clickwhale' ) . '"></div>';
		$output .= '</div>';

		return $output;
	}


	public function admin_scripts() {
		$nonce              = wp_create_nonce( 'check_slug' );
		$nonce_get_posts    = wp_create_nonce( 'get_posts_by_post_type' );
		$nonce_get_cw_links = wp_create_nonce( 'get_cw_links' );
		?>
        <script type='text/javascript'>
            jQuery(document).ready(function () {

                var wrap = jQuery('.links-list-wrap'),
                    limit = <?php echo ClickwhaleLinkpagesHelper::get_links_limit() ?>,
                    linksType = jQuery('#add-links-type'),
                    linksSelect = jQuery('#add-links-select'),
                    linksButton = jQuery('#add-links-button');

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

                if (jQuery('.linkpage-row').length >= limit) {
                    jQuery('#add-pagelink-link').prop('disabled', true);
                }

                jQuery(wrap).sortable({
                    placeholder: "ui-state-highlight"
                });
                wrap.disableSelection();

                jQuery(document)
                    .on('change', '#add-links-type', function () {
                        var linksType = jQuery(this).val();

                        jQuery('#links-post-type').show();
                        jQuery('#links-cw-custom').hide();

                        switch (linksType) {
                            case 'cw_link':

                                jQuery.post(ajaxurl, {
                                    'security': '<?php echo $nonce_get_cw_links ?>',
                                    'action': 'clickwhale/admin/get_cw_links'
                                }, function (response) {

                                    if (response.success && response.data.links) {
                                        var linksSelectOptions = '',
                                            links = response.data.links;

                                        enable_links_select();
                                        jQuery(linksButton).prop('disabled', false);

                                        for (var link in links) {
                                            linksSelectOptions += ('<option value="' + links[link].id + '" data-url="' + links[link].url + '">' + links[link].title + '</option>')
                                        }
                                        jQuery(linksSelect).append(linksSelectOptions);
                                    } else {
                                        enable_links_select(false);
                                        jQuery(linksButton).prop('disabled', true);
                                    }
                                });

                                break;
                            case 'cw_custom':
                                jQuery('#links-post-type').hide();
                                jQuery('#links-cw-custom').show();

                                enable_links_select(false);
                                jQuery(linksButton).prop('disabled', false);

                                break;
                            default:
                                jQuery.post(ajaxurl, {
                                    'security': '<?php echo $nonce_get_posts ?>',
                                    'action': 'clickwhale/admin/get_posts_by_post_type',
                                    'post_type': linksType,
                                }, function (response) {

                                    if (response.success && response.data.posts) {
                                        var linksSelectOptions = '',
                                            posts = response.data.posts;

                                        enable_links_select();
                                        jQuery(linksButton).prop('disabled', false);

                                        for (var post in posts) {
                                            linksSelectOptions += ('<option value="' + posts[post].id + '" data-url="' + posts[post].url + '">' + posts[post].title + '</option>')
                                        }
                                        jQuery(linksSelect).append(linksSelectOptions);
                                    } else {
                                        enable_links_select(false);
                                        jQuery(linksButton).prop('disabled', true);
                                    }
                                });
                        }

                    })
                    // Add link row
                    .on('click', '#add-links-button', function (e) {
                        e.preventDefault();

                        var dragTooltip = '<?php _e( 'Change Order', 'clickwhale' ) ?>',
                            removeTooltip = '<?php _e( 'Remove item', 'clickwhale' ) ?>',
                            templatePostTypeName = jQuery(linksType).find('option:selected').text();

                        if (!jQuery(linksSelect).prop('disabled') && jQuery(linksType).val() !== 'cw_custom') {

                            // Add CW Link or Post Type

                            var links = jQuery(linksSelect),
                                link = get_new_link_data(
                                    jQuery(linksType).val(),
                                    links.find('option:selected').text(),
                                    links.find('option:selected').data('url'),
                                    links.find('option:selected').val(),
                                ),
                                templatePostIdInput = link.postId !== '' ? '<input type="hidden" name="links[' + link.id + '][post_id]" value="' + link.postId + '">' : '',
                                originText = '<?php _e( 'Original', 'clickwhale' ); ?>',
                                templateURL = jQuery(linksType).val() === 'cw_link' ? '<span>' + link.url + '</span>' : '<span>' + originText + ' ' + templatePostTypeName + ': <a href="' + link.url + '" target="_blank">' + link.title + '</a></span>',
                                template = '' +
                                    '<div class="linkpage-row">' +
                                    '<input type="hidden" name="links[' + link.id + '][type]" value="' + link.type + '">' +
                                    '<input type="hidden" name="links[' + link.id + '][id]" value="' + link.id + '">' +
                                    templatePostIdInput +
                                    '<div class="linkpage-row--drag" title="' + dragTooltip + '"></div>' +
                                    '<div class="linkpage-link">' + link.title + ' ' +
                                    templateURL +
                                    '</div>' +
                                    '<div class="linkpage-link--title">' +
                                    '<input type="text" name="links[' + link.id + '][title]" placeholder="' + link.placeholder.customTitle + '">' +
                                    '</div>' +
                                    '<div class="linkpage-row--remove" title="' + removeTooltip + '"></div>' +
                                    '</div>';
                        } else {

                            // Add custom link (title + url)

                            var c_link = get_new_link_data(
                                    'custom_link',
                                    jQuery('input[name="custom-link-title"]').val(),
                                    jQuery('input[name="custom-link-url"]').val()
                                ),
                                template = '' +
                                    '<div class="linkpage-row row--custom_link">' +
                                    '<input type="hidden" name="links[' + c_link.id + '][type]" value="' + c_link.type + '">' +
                                    '<input type="hidden" name="links[' + c_link.id + '][id]" value="' + c_link.id + '">' +
                                    '<div class="linkpage-row--drag" title="' + dragTooltip + '"></div>' +
                                    '<div class="linkpage-link">' +
                                    '<input type="text" name="links[' + c_link.id + '][title]" value="' + c_link.title + '" placeholder="' + c_link.placeholder.title + '">' +
                                    '<input type="text" name="links[' + c_link.id + '][url]" value="' + c_link.url + '" placeholder="' + c_link.placeholder.url + '">' +
                                    '</div>' +
                                    '<div class="linkpage-row--remove" title="' + removeTooltip + '"></div>' +
                                    '</div>';

                            jQuery('.custom-links-action-wrap input').val('');
                        }

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
                            jQuery('#cw-slug--description').text('<?php _e( 'This slug is already in use! Please enter another slug',
								'clickwhale' ) ?>');
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
                    var $defaults;
                    if (window.confirm('<?php _e( 'Are you sure? This action will set colors to default. This process cannot be undone!',
						'clickwhale' ) ?>')) {
                        $defaults = <?php echo json_encode( $this->get_defaults() ) ?>;

                        jQuery.each($defaults.styles, function (key, val) {
                            jQuery('[name="styles[' + key + ']"').wpColorPicker('color', val);
                        });
                    }
                });

                function count_links() {
                    return jQuery('.linkpage-row').length;
                }

                function get_new_link_data(type, title = '', url = '', id = '') {
                    var postId = '';

                    switch (type) {
                        case 'cw_link':
                            break;
                        case 'cw_custom':
                            id = link_uniqid()
                            break;
                        default:
                            postId = id;
                            id = link_uniqid()
                            break;
                    }

                    return {
                        type: type,
                        title: title.trim(),
                        url: url,
                        id: id,
                        postId: postId,
                        placeholder: {
                            title: "<?php _e( 'Link Title', 'clickwhale' ); ?>",
                            customTitle: "<?php _e( 'Custom Title', 'clickwhale' ); ?>",
                            url: "<?php _e( 'Link URL', 'clickwhale' ); ?>"
                        }
                    }
                }

                function append_link(target, template) {
                    target.append(template);
                }

                function links_limit_warning() {
                    jQuery('#add-pagelink-link').prop('disabled', true);
                    jQuery('<div class="links-info"><?php printf( 'Currently, a maximum of %d links can be added',
						ClickwhaleLinkpagesHelper::get_links_limit() ); ?></div>').insertAfter('.links-list-wrap');
                }

                function link_uniqid(prefix = "", random = false) {
                    const sec = Date.now() * 1000 + Math.random() * 1000;
                    const id = sec.toString(16).replace(/\./g, "").padEnd(14, "0");
                    return `${prefix}${id}${random ? `.${Math.trunc(Math.random() * 100000000)}` : ""}`;
                };

                function enable_links_select($enable = true) {
                    if ($enable) {
                        jQuery(linksSelect)
                            .prop('disabled', false)
                            .find('option')
                            .remove();
                    } else {
                        jQuery(linksSelect)
                            .prop('disabled', true)
                            .find('option')
                            .remove();
                        jQuery(linksSelect).append('<option><?php _e( 'Nothing found', 'clickwhale' ) ?></option>')
                    }
                }
            });
        </script>
		<?php
	}
}