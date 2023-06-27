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
			'id'                   => 0,
			'created_at'           => '',
			'title'                => '',
			'slug'                 => '',
			'description'          => '',
			'links'                => '',
			'logo'                 => '',
			'styles'               => array(
				'bg_color'            => '#fdd231',
				'text_color'          => '#1a1c1d',
				'link_bg_color'       => '#fee06f',
				'link_color'          => '#1a1c1d',
				'link_bg_color_hover' => '#ffffff',
				'link_color_hover'    => '#397eff',
			),
			'social'               => array(
				'networks' => array(),
				'seo'      => array()
			),
			'meta__legals_menu_id' => 0
		);
	}

	public function render_tabs() {
		$tabs = array(
			'settings' => array(
				'name' => __( 'Settings', 'clickwhale' ),
				'url'  => 'settings',
			),
			'contents' => array(
				'name' => __( 'Contents', 'clickwhale' ),
				'url'  => 'contents'
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

	/**
	 * @return array
	 * @since 1.3.2
	 */
	public static function get_nav_menus() {
		$menus      = wp_get_nav_menus();
		$result     = array();
		$result[''] = __( 'No Menu', 'clickwhale' );
		foreach ( $menus as $menu ) {
			$result[ $menu->term_id ] = $menu->name;
		}

		return $result;
	}

	public function get_link_meta( $id, $key ) {
		global $wpdb;
		$table_links_meta = $wpdb->prefix . 'clickwhale_meta';

		return $wpdb->get_row( "SELECT * FROM $table_links_meta WHERE linkpage_id='$id' AND meta_key='$key'", ARRAY_A );
	}

	private function save_linkpage_meta( $id, $key, $value, $action ) {
		global $wpdb;
		$links_meta_table = $wpdb->prefix . 'clickwhale_meta';

		switch ( $action ) {
			case 'insert':
				$result = $wpdb->insert(
					$links_meta_table,
					array(
						'meta_key'    => $key,
						'meta_value'  => $value,
						'linkpage_id' => $id
					)
				);
				break;
			case 'update':
				$result = $wpdb->update(
					$links_meta_table,
					array(
						'meta_value' => $value
					),
					array(
						'linkpage_id' => $id,
						'meta_key'    => $key,
					)
				);
				break;
		}

		return $result;
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

		// data fot meta table
		$legals_menu_id = $item['meta__legals_menu_id'];
		unset( $item['meta__legals_menu_id'] );

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
			//set_transient( 'linkpage-' . $item['id'], 'linkpage_updated', 45 );

		} else {
			$wpdb->insert(
				$linkpages_table,
				$item
			);
			$item['id'] = $wpdb->insert_id;
			//set_transient( 'linkpage-' . $item['id'], 'linkpage_added', 45 );

		}

		//exit( var_dump( $wpdb->last_query ) );

		if ( $this->get_link_meta( $item['id'], 'legals_menu_id' ) ) {
			$this->save_linkpage_meta( $item['id'], 'legals_menu_id', $legals_menu_id, 'update' );
		} else {
			$this->save_linkpage_meta( $item['id'], 'legals_menu_id', $legals_menu_id, 'insert' );
		}
		//exit( var_dump( $wpdb->last_query ) );


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
                const
                    linkpageSubmit = jQuery('#form_edit_linkpage').find('[type="submit"]'),
                    title = jQuery('#title'),
                    slug = jQuery('#cw-slug'),
                    limit = parseInt('<?php echo ClickwhaleLinkpagesHelper::get_links_limit() ?>'),
                    linksType = jQuery('#add-links-type'),
                    tinymceOptions = {
                        wpautop: true,
                        plugins: 'charmap colorpicker hr lists paste tabfocus textcolor fullscreen wordpress wpautoresize wpeditimage wpemoji wpgallery wplink wptextpattern',
                        toolbar1: 'formatselect, bold, italic, underline, strikethrough, bullist, numlist, alignleft, aligncenter, alignright, link, unlink, pastetext, removeformat, charmap, undo, redo',
                        toolbar2: '',
                        toolbar3: '',
                        toolbar4: '',
                        textarea_rows: 20
                    },
                    quicktagsOptions = {buttons: 'strong,em,link,block,del,ins,img,ul,ol,li,code,close'};

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

                /* Init wp.editor */
                jQuery('.row--cw_custom_content').each(function () {
                    const editorTextareaID = jQuery(this).find('textarea').attr('id');
                    wp.editor.initialize(editorTextareaID, {
                        mediaButtons: true,
                        tinymce: tinymceOptions,
                        quicktags: quicktagsOptions,
                    });
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

                            const
                                el = jQuery(ui.draggable),
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

                                    } else {
                                        links_limit_warning();
                                        jQuery('.cw-content--item').addClass('disabled').draggable('disable');
                                    }
                                }
                            });
                        },
                        over: function () {
                            // on drag over
                        }
                    })
                    .sortable({
                        placeholder: "ui-state-highlight",
                        handle: '.linkpage-row--drag',
                    }).disableSelection();

                /* ----- */

                jQuery(document)
                    // close theicons select on document click
                    .on('click', function () {
                        closeIconPicker();
                    })

                    // prevent close the icons select on document click when target is the icons select container
                    .on('click', '#icon-picker--wrap, .icon-picker', function (e) {
                        e.stopPropagation();
                    })

                    // init tintmce (wp.editor) on custom content block drag
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

                    // change hidden fields value on CW link select
                    .on('change', '.row--cw_link .select-link', function () {
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

                    // init emoji picker and its actions
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
                    .on('click', '.linkpage-image-upload', function (e) {
                        e.preventDefault();

                        const button = jQuery(this),
                            custom_uploader = wp.media({
                                title: 'Insert image',
                                library: {
                                    type: 'image',
                                },
                                button: {
                                    text: '<?php _e( 'Select Image', 'clickwhale' ) ?>',
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
                    .on('click', '.linkpage-image-remove', function (e) {
                        e.preventDefault();

                        const button = jQuery(this);

                        button.next().val(''); // emptying the hidden field
                        button.hide().prev().addClass('button').html('<?php _e( 'Upload image', 'clickwhale' ) ?>');

                        disable_ogpreview_button();
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

                        /* Remove linkpage-row-image (tab image) when "Remove Image" was clicked */
                        change_row_image(jQuery(this), '', false);
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

                /**
                 * Disable OpenGraph Preview button
                 *
                 * 1. Only top level input/textarea
                 * 2. On hidden input change
                 */
                jQuery('td > input, td > textarea').on('keyup change blur', function () {
                    disable_ogpreview_button();
                })
                jQuery('input[type="hidden"]').bind("change", function () {
                    disable_ogpreview_button();
                });

                jQuery('#check-slug').click(function () {
                    alert(check_slug());
                });


                /**
                 * Submit action
                 * 1. Check title (not null)
                 * 2. Check slug (not null)
                 * 3. Check slug (exists as post/page slug)
                 */
                jQuery('#submit').click(function (e) {

                    if (!title.val() || !slug.val() || check_slug() !== false) {

                        e.preventDefault();
                        jQuery('#clickwhale-tabs').tabs('option', 'active', 0);

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

                /**
                 *  Reset selected colors
                 */
                jQuery('#reset-colors').on('click', function (e) {
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


                /**
                 *
                 * JS FUNCTIONS
                 *
                 */

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
                            'type': 'linkpage',
                            'slug': slug.val(),
                            'id': <?php echo esc_attr( intval( $_GET['id'] ?? 0 ) ); ?>
                        }, success: function (response) {
                            result = response.data;
                        }
                    });

                    return result;
                }

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
            })
            ;
		</script>
		<?php
	}
}