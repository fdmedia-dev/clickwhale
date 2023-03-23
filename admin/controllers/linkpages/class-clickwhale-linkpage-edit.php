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
			'social'      => array(
				'networks' => array(),
				'seo'      => array()
			)
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

	/**
	 * @return array
	 * @since 1.1.0
	 */
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

	public static function get_link( $id ) {
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

	/**
	 * @return array
	 * @since 1.3.0
	 */
	public static function get_select_values(): array {
		$values = array(
			array(
				'label'   => __( 'ClickWhale Content', 'clickwhale' ),
				'options' => array(
					'cw_link'   => __( 'ClickWhale Link', 'clickwhale' ),
					'cw_custom' => __( 'Custom Link', 'clickwhale' ),
				),
			)
		);

		$post_types_group = array(
			'label'   => __( 'Post Types', 'clickwhale' ),
			'options' => array()
		);
		$post_types       = ClickwhaleLinkpagesHelper::get_post_types();
		foreach ( $post_types as $name => $singular ) {
			$post_types_group['options'][ $name ] = $singular;
		}

		$values[] = $post_types_group;

		return apply_filters( 'clickwhale_linkpage_select', $values );
	}

	public function save_update_linkpage() {
		global $wpdb;
		$linkpages_table = $wpdb->prefix . 'clickwhale_linkpages';
		$defaults        = apply_filters( 'clickwhale_linkpage_defaults', $this->get_defaults() );
		$item            = array_intersect_key( $_POST, $defaults );
		$item['slug']    = sanitize_title( $item['slug'] );
		$item['links']   = isset( $item['links'] ) ? maybe_serialize( $item['links'] ) : '';
		$item['styles']  = isset( $item['styles'] ) ? maybe_serialize( $item['styles'] ) : '';
		$item['social']  = isset( $item['social'] ) ? maybe_serialize( $item['social'] ) : '';
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
			set_transient( 'linkpage-' . $item['id'], 'linkpage_updated', 45 );
		} else {
			$wpdb->insert(
				$linkpages_table,
				$item
			);
			$item['id'] = $wpdb->insert_id;
			set_transient( 'linkpage-' . $item['id'], 'linkpage_added', 45 );
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
			$link  = self::get_link( $id );
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
		$nonce_add_link     = wp_create_nonce( 'clickwhale_add_link_to_linkpage' );

		if ( isset( $_GET['page'] ) && $_GET['page'] === 'clickwhale-edit-linkpage' && isset( $_GET['id'] ) ) {
			?>
            <script type='text/javascript'>
                jQuery(document).ready(function () {
                    var page_id = '<?php echo sanitize_text_field( intval( $_GET['id'] ) ); ?>';

                    jQuery('#clickwhale-tabs').tabs({
                        activate: function (event, ui) {
                            if (jQuery(ui.newPanel[0]).attr('id') === 'lp-tab-colors') {
                                jQuery('#reset-colors').show();
                            } else {
                                jQuery('#reset-colors').hide();
                            }
                        }
                    });

                    if (localStorage.getItem('tab-' + page_id)) {
                        jQuery('#clickwhale-tabs').tabs({active: localStorage.getItem('tab-' + page_id)});
                    }

                    jQuery('#clickwhale-tabs li').click(function () {
                        localStorage.setItem('tab-' + page_id, jQuery(this).index());
                    });
                })
            </script>
		<?php } ?>

        <script type='text/javascript'>
            jQuery(document).ready(function () {

                /* vars */
                var wrap = jQuery('.links-list-wrap'),
                    limit = parseInt('<?php echo ClickwhaleLinkpagesHelper::get_links_limit() ?>'),
                    linksType = jQuery('#add-links-type'),
                    linksButton = jQuery('#add-links-button');

                /* Select2 init */
                linksType.select2({
                    placeholder: '<?php _e( 'Select link Type', 'clickwhale' ) ?>',
                    width: '100%',
                    minimumResultsForSearch: -1
                });

                /* jQuery UI Sortable init*/
                jQuery(wrap).sortable({
                    placeholder: "ui-state-highlight"
                });
                wrap.disableSelection();

                /* Color Picker init */
                jQuery('.cw-color-control').wpColorPicker();

                /* Disable add link button if links limit is reached */
                if (jQuery('.linkpage-row').length >= limit) {
                    jQuery('#add-pagelink-link').prop('disabled', true);
                }

                jQuery('.linkpage-row ').each(function () {
                    var row = this,
                        imageSelect = jQuery(this).find('.linkpage-row--image-select select');
                    if (imageSelect.val() !== 'undefined') {
                        jQuery('[data-tab="' + imageSelect.val() + '"]').show();
                    }
                });

                /* ----- */

                jQuery(document)
                    // Add link row
                    .on('click', '#add-links-button', function () {
                        var type = jQuery(linksType).val();

                        jQuery.post(ajaxurl, {
                            'security': '<?php echo $nonce_add_link ?>',
                            'action': 'clickwhale/admin/add_link_to_linkpage',
                            'type': type,
                        }, function (response) {

                            if (response.success && response.data.template) {
                                var template = response.data.template;

                                if (count_links() < limit) {
                                    append_link(wrap, template);

                                    jQuery('.select-link').select2({
                                        placeholder: '<?php _e( 'Select Item', 'clickwhale' ) ?>',
                                        width: '100%',
                                        minimumResultsForSearch: 10
                                    });

                                }
                                if ((count_links() + 1) === limit) {
                                    links_limit_warning();
                                    jQuery('#add-links-button').attr('disabled', true);
                                }
                            }

                        });
                    })
                    .on('change', '.row-cw_link .select-link', function () {
                        var parent = jQuery(this).closest('.linkpage-row'),
                            title = jQuery(this).find('option:selected').data('title'),
                            url = jQuery(this).find('option:selected').data('url'),
                            value = jQuery(this).val();

                        parent.find('[name^="links[0]"]').each(function () {
                            var name = jQuery(this).attr('name');
                            name = name.replace('links[0]', 'links[' + value + ']');
                            jQuery(this).attr('name', name);
                        });
                        parent.find('[name$="[post_id]"]').val(value);
                        parent.find('.linkpage-row--link strong').text(title);
                        parent.find('.linkpage-row--link span').text(url);

                    })
                    // Show Edit section
                    .on('click', '.linkpage-row--actions--button-edit', function () {
                        jQuery(this).closest('.linkpage-row').find('.linkpage-row--bottom').toggleClass('active');
                    })

                    // Remove added link row
                    .on('click', '.linkpage-row--actions--button-remove', function () {
                        jQuery(this).closest('.linkpage-row').remove();
                        if (jQuery('.linkpage-row').length < limit) {
                            jQuery('.links-info').remove();
                            jQuery('#add-pagelink-link').prop('disabled', false);
                        }
                    })

                    // Logo upload
                    .on('click', '.linkpage-logo-upload, .linkpage-row--image-upload', function (e) {
                        e.preventDefault();

                        var button = jQuery(this),
                            custom_uploader = wp.media({
                                title: 'Insert image',
                                library: {
                                    // uploadedTo : wp.media.view.settings.post.id, // attach to the current post?
                                    type: 'image',
                                },
                                button: {
                                    text: 'Use this image' // button label text
                                },
                                multiple: false
                            }).on('select', function () { // it also has "open" and "close" events
                                var attachment = custom_uploader.state().get('selection').first().toJSON(),
                                    mediaInput = button.parent().find('input'),
                                    mediaLabel = button.parent().find('label'),
                                    mediaRemove = button.parent().find('.linkpage-row--image-remove'),
                                    url = typeof attachment.sizes.thumbnail !== 'undefined' ? attachment.sizes.thumbnail.url : attachment.url;
                                mediaLabel.html('<img src="' + url + '" />');
                                mediaInput.val(attachment.id).trigger("change").prop("checked", true);
                                mediaRemove.show();
                            }).open();

                    })
                    .on('click', '.linkpage-logo-remove', function (e) {
                        e.preventDefault();

                        var button = jQuery(this);

                        button.next().val(''); // emptying the hidden field
                        button.hide().prev().html('Upload image');
                    })
                    .on('click', '.linkpage-row--image-remove', function (e) {
                        e.preventDefault();

                        jQuery(this).parent().find('input').val('').prop("checked", false);
                        jQuery(this).parent().find('label').html('');
                        jQuery(this).hide();
                    })

                    // disable OpenGraph Preview button on input change
                    .on('keyup change blur', 'input', function () {
                        disable_ogpreview_button();
                    })

                    // toggle image type tabs
                    .on('change', '.linkpage-row--image-select select', function () {
                        jQuery('.linkpage-row--image-select--tab').hide();
                        jQuery('.linkpage-row--image-select--tab[data-tab="' + this.value + '"]').show();
                    })

                    // toggle .linkpage-row-image content with selected image/icon/emoji
                    .on('change', '.image-item [type="radio"]', function () {
                        var imageItemValue = jQuery(this).next().html(),
                            rowImage = jQuery(this).closest('.linkpage-row').find('.linkpage-row--image');
                        change_row_image(rowImage, imageItemValue);
                        jQuery(this).closest('.linkpage-row').find('[name$="[image][type]"]').val(jQuery(this).data('type'));
                    });

                /* Change linkpage-row-image (tab image) */
                jQuery('.linkpage-row--image-input').bind("change", function () {
                    var uploadedImage = jQuery(this).parent().find('.linkpage-row--image-upload').html(),
                        rowImage = jQuery(this).closest('.linkpage-row').find('.linkpage-row--image');

                    change_row_image(rowImage, uploadedImage);
                });
                /* Remove linkpage-row-image (tab image) when "Remove Image" was clicked */
                jQuery(".linkpage-row--image-remove").click(function () {
                    var rowImage = jQuery(this).closest('.linkpage-row').find('.linkpage-row--image');

                    change_row_image(rowImage, '', false);
                });

                jQuery('input[name="hidden"]').bind("change", function () {
                    disable_ogpreview_button();
                });
                jQuery(".linkpage-logo-remove").click(function () {
                    disable_ogpreview_button();
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

                function init_select($element) {
                    jQuery($element).select2({
                        placeholder: '<?php _e( 'Select Item', 'clickwhale' ) ?>',
                        width: '100%',
                        minimumResultsForSearch: 10
                    });
                }

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
                        post_id: postId
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

                function enable_links_select($element, $enable = true) {
                    if ($enable) {
                        jQuery($element)
                            .prop('disabled', false)
                            .find('option')
                            .remove();
                    } else {
                        jQuery($element)
                            .prop('disabled', true)
                            .find('option')
                            .remove();
                        jQuery($element).append('<option><?php _e( 'Nothing found', 'clickwhale' ) ?></option>')
                    }
                }

                function disable_ogpreview_button() {
                    jQuery('#opengraph-live-preview')
                        .addClass('disabled')
                        .next()
                        .text('<?php _e( 'Save page to view Open Graph preview', 'clickwhale' ) ?>');
                }

                function change_row_image(element, image, active = true) {
                    if (active) {
                        jQuery(element).addClass('with-image').html(image);
                    } else {
                        jQuery(element).removeClass('with-image').html(image);
                    }
                }
            });
        </script>
		<?php
	}
}