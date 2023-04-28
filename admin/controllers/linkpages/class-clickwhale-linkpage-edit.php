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

	public function render_tabs() {
		$tabs = array(
			'contents' => array(
				'name' => __( 'Contents', 'clickwhale' ),
				'url'  => 'contents'
			),
			'settings' => array(
				'name' => __( 'Settings', 'clickwhale' ),
				'url'  => 'settings',
			),
			'styles'   => array(
				'name' => __( 'Styles', 'clickwhale' ),
				'url'  => 'styles'
			),
			'seo'      => array(
				'name' => __( 'SEO', 'clickwhale' ),
				'url'  => 'seo'
			),
		);

		return apply_filters( 'clickwhale_linkpage_tabs', $tabs );
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
		$values = [];

		// ClickWhale Content

		$cw = array(
			'label'   => __( 'ClickWhale Content', 'clickwhale' ),
			'options' => array(
				'cw_link'           => array(
					'name' => __( 'ClickWhale Link', 'clickwhale' ),
					'icon' => 'link'
				),
				'cw_custom_link'    => array(
					'name' => __( 'Custom Link', 'clickwhale' ),
					'icon' => 'link-2'
				),
				'cw_custom_content' => array(
					'name' => __( 'Custom Content', 'clickwhale' ),
					'icon' => 'edit'
				)
			),
		);

		// Post Types

		$post_types       = ClickwhaleLinkpagesHelper::get_post_types();
		$post_types_group = array(
			'label'   => __( 'Post Types', 'clickwhale' ),
			'options' => array()
		);

		foreach ( $post_types as $name => $singular ) {
			$post_types_group['options'][ $name ] ['name'] = $singular;
			$post_types_group['options'][ $name ] ['icon'] = 'file';
		}

		// Formatting

		$formatting = array(
			'label'   => __( 'Formatting', 'clickwhale' ),
			'options' => array(
				'cw_heading'   => array(
					'name' => __( 'Heading', 'clickwhale' ),
					'icon' => 'type'
				),
				'cw_separator' => array(
					'name' => __( 'Separator', 'clickwhale' ),
					'icon' => 'minus'
				)
			),
		);


		$values[] = $cw;
		$values[] = $post_types_group;
		$values[] = $formatting;

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

	public function admin_scripts() {
		$nonce          = wp_create_nonce( 'check_slug' );
		$nonce_add_link = wp_create_nonce( 'clickwhale_add_link_to_linkpage' );

		if ( isset( $_GET['page'] ) && $_GET['page'] === 'clickwhale-edit-linkpage' ) {
			?>
			<script type='text/javascript'>
                jQuery(document).ready(function () {
                    jQuery('#clickwhale-tabs').tabs({
                        activate: function (event, ui) {
                            if (jQuery(ui.newPanel[0]).attr('id') === 'lp-tab-styles') {
                                jQuery('#reset-colors').show();
                            } else {
                                jQuery('#reset-colors').hide();
                            }
                        }
                    });

					<?php if(isset( $_GET['id'] )) { ?>
                    const page_id = '<?php echo sanitize_text_field( intval( $_GET['id'] ) ); ?>';
                    if (localStorage.getItem('tab-' + page_id)) {
                        jQuery('#clickwhale-tabs').tabs({active: localStorage.getItem('tab-' + page_id)});
                    }

                    jQuery('#clickwhale-tabs li').click(function () {
                        localStorage.setItem('tab-' + page_id, jQuery(this).index());
                    });
					<?php } ?>
                })
			</script>
		<?php } ?>

		<script type='text/javascript'>
            const {createPopup} = window.picmoPopup;

            jQuery(document).ready(function () {

                /* vars */
                const limit = parseInt('<?php echo ClickwhaleLinkpagesHelper::get_links_limit() ?>'),
                    linksType = jQuery('#add-links-type'),
                    tinymceOptions = {
                        wpautop: true,
                        plugins: 'charmap colorpicker hr lists paste tabfocus textcolor fullscreen wordpress wpautoresize wpeditimage wpemoji wpgallery wplink wptextpattern',
                        toolbar1: 'bold, italic, strikethrough, bullist, numlist, blockquote, hr, alignleft, aligncenter, alignright, link, unlink, wp_more, spellchecker, fullscreen, wp_adv',
                        toolbar2: 'formatselect, underline, alignjustify, forecolor, pastetext, removeformat, charmap, outdent, indent, undo, redo, wp_help',
                        toolbar3: '',
                        toolbar4: '',
                        textarea_rows: 20
                    },
                    quicktagsOptions = {buttons: 'strong,em,link,block,del,ins,img,ul,ol,li,code,more,close'};

                /* Select2 init */
                linksType.select2({
                    placeholder: '<?php _e( 'Select Content Type', 'clickwhale' ) ?>',
                    width: '100%',
                    minimumResultsForSearch: -1
                });

                /* Color Picker init */
                jQuery('.cw-color-control').wpColorPicker();

                /* Disable add link button if links limit is reached */
                if (jQuery('.linkpage-row').length >= limit) {
                    jQuery('#add-pagelink-link').prop('disabled', true);
                }

                jQuery('.row--cw_custom_content').each(function () {
                    const editorTextareaID = jQuery(this).find('textarea').attr('id');
                    wp.editor.initialize(editorTextareaID, {
                        mediaButtons: true,
                        tinymce: tinymceOptions,
                        quicktags: quicktagsOptions,
                    });
                });

                jQuery('.linkpage-row ').each(function () {
                    const row = this,
                        imageSelect = jQuery(this).find('.linkpage-row--image-select select');
                    if (imageSelect.val() !== 'undefined') {
                        jQuery('[data-tab="' + imageSelect.val() + '"]').show();
                    }
                });

                // Draggable, Droppable, Sortable

                var contentWrap = jQuery('.links-list-wrap'),
                    contentItems = jQuery('.cw-content--items');

                jQuery(".cw-content--item", contentItems).draggable({
                    containment: "document",
                    connectToSortable: ".connectedSortable",
                    helper: "clone",
                    revert: "invalid",
                });

                contentWrap
                    .droppable({
                        accept: ".cw-content--item",
                        drop: function (event, ui) {

                            const el = jQuery(ui.draggable),
                                type = el.data('content');

                            jQuery.post(ajaxurl, {
                                'security': '<?php echo $nonce_add_link ?>',
                                'action': 'clickwhale/admin/add_link_to_linkpage',
                                'type': type,
                            }, function (response) {

                                if (response.success && response.data.template) {
                                    const template = response.data.template;

                                    if (count_links() < limit) {
                                        replace_block(el, template);

                                        jQuery('.select-link').select2({
                                            placeholder: '<?php _e( 'Select Item', 'clickwhale' ) ?>',
                                            width: '100%',
                                            minimumResultsForSearch: 10
                                        });

                                    }
                                }
                            });
                        },
                        over: function () {
                            if ((count_links() + 1) === limit) {
                                links_limit_warning();
                                jQuery('.cw-content--item').addClass('disabled').draggable('disable');
                            }
                        }
                    })
                    .sortable({
                        placeholder: "ui-state-highlight",
                    }).disableSelection();

                /* ----- */

                jQuery(document)
                    .on('click', function () {
                        closeIconPicker();
                    })
                    .on('click', '#icon-picker--wrap, .icon-picker', function (e) {
                        e.stopPropagation();
                    })
                    .on('clickwhale.content.template.replace', '.links-list-wrap', function (e, template) {
                        const templateRowID = jQuery(template).attr('id'),
                            templateID = templateRowID.replace('row-', '');

                        if (jQuery(template).hasClass('row--cw_custom_content')) {

                            wp.editor.initialize('cw_custom_content_' + templateID, {
                                mediaButtons: true,
                                tinymce: tinymceOptions,
                                quicktags: quicktagsOptions,
                            });
                        }
                    })
                    .on('change', '.row-cw_link .select-link', function () {
                        const parent = jQuery(this).closest('.linkpage-row'),
                            title = jQuery(this).find('option:selected').data('title'),
                            url = jQuery(this).find('option:selected').data('url'),
                            value = jQuery(this).val();

                        parent.find('[name^="links[0]"]').each(function () {
                            let name = jQuery(this).attr('name');
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

                    .on('click', '.emoji-picker', function (e) {
                        e.preventDefault();

                        const trigger = e.target,
                            emojiInput = trigger.parentElement.querySelector('input'),
                            emojiLabel = trigger.parentElement.querySelector('label'),
                            picker = createPopup({}, {
                                referenceElement: trigger,
                                triggerElement: trigger,
                                position: 'right-end'
                            });

                        picker.toggle();
                        picker.addEventListener('emoji:select', (selection) => {
                            emojiInput.value = selection.emoji;
                            emojiLabel.innerHTML = selection.emoji;
                            emojiInput.checked = true;

                            change_row_image(e.target, selection.emoji);
                            change_row_image_type(e.target, 'emoji');
                        });
                    })

                    // Icon select
                    .on('click', '.icon-picker', function (e) {
                        e.preventDefault();

                        const icons = [],
                            iconsContainer = jQuery('#icon-picker--wrap'),
                            iconsPicker = jQuery(this),
                            wrapPosition = jQuery('.wrap').offset(),
                            iconsPickerPosition = iconsPicker.offset();

                        iconsContainer
                            .css({
                                'top': iconsPickerPosition.top - jQuery('#wpadminbar').height(),
                                'left': iconsPickerPosition.left - wrapPosition.left + iconsPicker.width() + 10
                            })
                            .data('id', iconsPicker.attr('id'))
                            .show();

                        iconsContainer.find('button').each(function (index, element) {
                            icons.push(jQuery(element).data('icon'));
                        });

                        iconsContainer.find('input').on('keyup', function () {
                            const results = icons.filter(el => el.toLowerCase().includes(this.value.toLowerCase()));

                            iconsContainer.find('button').hide();
                            results.forEach(result => {
                                iconsContainer.find('[data-icon="' + result + '"]').show();
                            })
                        });

                    })
                    .on('click', '#icon-picker--wrap button', function (e) {
                        e.preventDefault();

                        const iconButton = jQuery(this),
                            icon = iconButton.html(),
                            trigger = jQuery('#' + jQuery('#icon-picker--wrap').data('id') + '');

                        trigger.parent().find('input').val(iconButton.data('icon')).prop('checked', true);
                        trigger.prev().addClass('with-image').find('label').html(icon);

                        change_row_image(trigger, icon);
                        change_row_image_type(trigger, 'icon');

                        closeIconPicker();
                    })

                    // Remove added link row
                    .on('click', '.linkpage-row--actions--button-remove', function () {
                        jQuery(this).closest('.linkpage-row').remove();
                        if (count_links() < limit) {
                            jQuery('.links-info').remove();
                            jQuery('.cw-content--item').removeClass('disabled').draggable('enable');
                        }
                    })

                    // LP Logo
                    .on('click', '.linkpage-logo-upload', function (e) {
                        e.preventDefault();

                        const button = jQuery(this),
                            custom_uploader = wp.media({
                                title: 'Insert image',
                                library: {
                                    type: 'image',
                                },
                                button: {
                                    text: '<?php _e( 'Set Link Page Logo', 'clickwhale' ) ?>',
                                },
                                multiple: false
                            }).on('select', function () {
                                const attachment = custom_uploader.state().get('selection').first().toJSON(),
                                    mediaInput = button.parent().find('input'),
                                    url = typeof attachment.sizes.thumbnail !== 'undefined' ? attachment.sizes.thumbnail.url : attachment.url;

                                button.removeClass('button').html('<img src="' + url + '">').next().show();
                                mediaInput.val(attachment.id).trigger("change");
                            }).open();

                    })
                    .on('click', '.linkpage-logo-remove', function (e) {
                        e.preventDefault();

                        const button = jQuery(this);

                        button.next().val(''); // emptying the hidden field
                        button.hide().prev().addClass('button').html('<?php _e( 'Upload image', 'clickwhale' ) ?>');
                    })

                    // Row Image
                    .on('click', '.linkpage-row--image-upload', function (e) {
                        e.preventDefault();

                        const button = jQuery(this),
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
                                const attachment = custom_uploader.state().get('selection').first().toJSON(),
                                    mediaInput = button.parent().find('input'),
                                    mediaLabel = button.parent().find('label'),
                                    mediaRemove = button.parent().find('.linkpage-row--image-remove'),
                                    url = typeof attachment.sizes.thumbnail !== 'undefined' ? attachment.sizes.thumbnail.url : attachment.url;

                                mediaLabel.html('<img src="' + url + '" />');
                                mediaInput.val(attachment.id).trigger("change").prop("checked", true);
                                mediaRemove.show();
                            }).open();

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
                        const imageItemValue = jQuery(this).next().html();

                        change_row_image(jQuery(this), imageItemValue);
                        change_row_image_type(jQuery(this), jQuery(this).data('type'));
                    })
                    .on('click', '.reset-image', function () {
                        jQuery(this).closest('.linkpage-row').find('.linkpage-row--image').removeClass('with-image').html('');
                        jQuery(this).closest('.linkpage-row').find('[name$="[image_id]"]').prop('checked', false);
                        jQuery(this).closest('.linkpage-row').find('[name$="[image][type]"]').val('');
                    });

                /* Change linkpage-row-image (tab image) */
                jQuery('.linkpage-row--image-input').bind("change", function () {
                    const uploadedImage = jQuery(this).parent().find('.linkpage-row--image-upload').html()

                    change_row_image(jQuery(this), uploadedImage);
                });

                /* Remove linkpage-row-image (tab image) when "Remove Image" was clicked */
                jQuery(".linkpage-row--image-remove").click(function () {
                    change_row_image(jQuery(this), '', false);
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

                    const slug = jQuery(this),
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
                    let defaults;
                    if (window.confirm('<?php _e( 'Are you sure? This action will set colors to default. This process cannot be undone!',
						'clickwhale' ) ?>')) {
                        defaults = <?php echo json_encode( $this->get_defaults() ) ?>;

                        jQuery.each(defaults.styles, function (key, val) {
                            jQuery('[name="styles[' + key + ']"').wpColorPicker('color', val);
                        });
                    }
                });

                function count_links() {
                    return jQuery('.linkpage-row').length;
                }

                function replace_block(target, template) {
                    target.replaceWith(template);
                    jQuery('.links-list-wrap').trigger('clickwhale.content.template.replace', template);
                }

                function links_limit_warning() {
                    jQuery('#add-pagelink-link').prop('disabled', true);
                    jQuery('<div class="links-info"><?php printf( 'Currently, a maximum of %d links can be added',
						ClickwhaleLinkpagesHelper::get_links_limit() ); ?></div>').insertAfter('.links-list-wrap');
                }

                function disable_ogpreview_button() {
                    jQuery('#opengraph-live-preview')
                        .addClass('disabled')
                        .next()
                        .text('<?php _e( 'Save page to view Open Graph preview', 'clickwhale' ) ?>');
                }

                function change_row_image(element, image, active = true) {
                    if (active) {
                        jQuery(element).closest('.linkpage-row').find('.linkpage-row--image').addClass('with-image').html(image);
                    } else {
                        jQuery(element).closest('.linkpage-row').find('.linkpage-row--image').removeClass('with-image').html(image);
                    }
                }

                function change_row_image_type(element, type) {
                    jQuery(element).closest('.linkpage-row').find('[name$="[image][type]"]').val(type);
                }

                function closeIconPicker() {
                    jQuery('#icon-picker--wrap').hide().find('button').show();
                    jQuery('[name="icon-picker--search"]').val('');
                }
            });
		</script>
		<?php
	}
}