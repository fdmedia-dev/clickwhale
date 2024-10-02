<?php
namespace clickwhale\includes\admin\linkpages;

use clickwhale\includes\admin\Clickwhale_Instance_Edit;
use clickwhale\includes\helpers\{Helper, Linkpages_Helper};

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Clickwhale_Linkpage_Edit extends Clickwhale_Instance_Edit {

	public function __construct() {
		parent::__construct( 'linkpages', 'linkpage' );
	}

	/**
	 * Default values for new linkpage
	 * Could be hooked by filter "clickwhale_linkpage_defaults"
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
				'name' => __( 'Settings', CLICKWHALE_NAME ),
				'url'  => 'settings',
			),
			'contents' => array(
				'name' => __( 'Contents', CLICKWHALE_NAME ),
				'url'  => 'contents'
			),
			'styles'   => array(
				'name' => __( 'Styles', CLICKWHALE_NAME ),
				'url'  => 'styles'
			),
			'seo'      => array(
				'name' => __( 'SEO', CLICKWHALE_NAME ),
				'url'  => 'seo'
			),
		);

		return apply_filters( 'clickwhale_linkpage_tabs', $tabs );
	}

	/**
	 * @return array
	 * @since 1.3.0
	 */
	public static function get_select_values(): array {
		$values = [];

		// ClickWhale Content

		$cw = array(
			'label'   => __( 'ClickWhale Content', CLICKWHALE_NAME ),
			'options' => array(
				'cw_link'           => array(
					'name' => __( 'ClickWhale Link', CLICKWHALE_NAME ),
					'icon' => 'link'
				),
				'cw_custom_link'    => array(
					'name' => __( 'Custom Link', CLICKWHALE_NAME ),
					'icon' => 'link-2'
				),
				'cw_custom_content' => array(
					'name' => __( 'Custom Content', CLICKWHALE_NAME ),
					'icon' => 'edit'
				)
			),
		);

		// Post Types

		$post_types       = Helper::get_post_types();
		$post_types_group = array(
			'label'   => __( 'Post Types', CLICKWHALE_NAME ),
			'options' => array()
		);

		foreach ( $post_types as $name => $singular ) {
			$post_types_group['options'][ $name ] ['name'] = $singular;
			$post_types_group['options'][ $name ] ['icon'] = 'file';
		}

		// Formatting

		$formatting = array(
			'label'   => __( 'Formatting', CLICKWHALE_NAME ),
			'options' => array(
				'cw_heading'   => array(
					'name' => __( 'Heading', CLICKWHALE_NAME ),
					'icon' => 'type'
				),
				'cw_separator' => array(
					'name' => __( 'Separator', CLICKWHALE_NAME ),
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
		$result[''] = __( 'No Menu', CLICKWHALE_NAME );
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

	public function save_update() {
		global $wpdb;

		$table = Helper::get_db_table_name( $this->instance_plural );
		$item  = array_intersect_key(
			$_POST,
			apply_filters( "clickwhale_linkpage_defaults", $this->get_defaults() )
		);

		$item['slug']  = sanitize_title( $item['slug'] );
		$item['links'] = isset( $item['links'] ) ? maybe_serialize( $item['links'] ) : '';

        if ( isset( $item['styles'] ) ) {
            $styles = apply_filters( 'clickwhale_linkpage_styles_before_save', $item['styles'] );
            $item['styles'] = maybe_serialize( $styles );
        } else {
            $item['styles'] = '';
        }

		$item['social'] = isset( $item['social'] ) ? maybe_serialize( $item['social'] ) : '';
		$item['author'] = get_current_user_id();

		// Data fot meta table
		$legals_menu_id = $item['meta__legals_menu_id'];
		unset( $item['meta__legals_menu_id'] );

		$item = apply_filters( 'clickwhale_linkpage_data_before_save', $item );

		// Check if linkpage exists and then update or insert
		// in some cases default check (not false and < 0) goes wrong
		$linkpage = Linkpages_Helper::get_by_id( intval( $item['id'] ) );

		if ( $linkpage ) {
			$wpdb->update(
				$table,
				$item,
				array( 'id' => $item['id'] )
			);
			$this->set_transient( $item['id'], 'updated' );

		} else {
			$wpdb->insert(
				$table,
				$item
			);
			$item['id'] = $wpdb->insert_id;
			$this->set_transient( $item['id'], 'added' );
		}

		if ( $this->get_link_meta( $item['id'], 'legals_menu_id' ) ) {
			$this->save_linkpage_meta( $item['id'], 'legals_menu_id', $legals_menu_id, 'update' );
		} else {
			$this->save_linkpage_meta( $item['id'], 'legals_menu_id', $legals_menu_id, 'insert' );
		}

		// Redirect to new record
		$url = 'admin.php?page=' . CLICKWHALE_SLUG . '-edit-' . $this->instance_single . '&id=' . $item['id'];
		wp_redirect( admin_url( $url ) );
	}

	public function admin_scripts(): void {
		$nonce = wp_create_nonce( 'slug_exists' );
		$nonce_add_link = wp_create_nonce( 'clickwhale_add_link_to_linkpage' );

		if ( isset( $_GET['page'] ) && $_GET['page'] === CLICKWHALE_SLUG . '-edit-linkpage' ) { ?>
            <script type='text/javascript'>
                jQuery(document).ready(function() {
                    jQuery('#clickwhale-tabs').tabs({
                        activate: function(event, ui) {
                            if (jQuery(ui.newPanel[0]).attr('id') === 'lp-tab-styles') {
                                jQuery('#reset-styles').show();
                            } else {
                                jQuery('#reset-styles').hide();
                            }

                            if (jQuery(ui.newPanel[0]).attr('id') === 'lp-tab-contents'){ // If tab is `Contents`
                                // Hide Clickwhale Widget Sidebar
                                jQuery('#poststuff > #post-body.metabox-holder.columns-2 #postbox-container-1').hide();
                                jQuery('#poststuff > #post-body.metabox-holder.columns-2').removeClass('columns-2');
                            }
                        }
                    });

					<?php if (isset( $_GET['id'] )) { ?>
                        const page_id = '<?php echo sanitize_text_field( intval( $_GET['id'] ) ); ?>';
                        if (localStorage.getItem('tab-' + page_id)) {
                            jQuery('#clickwhale-tabs').tabs({active: localStorage.getItem('tab-' + page_id)});
                        }

                        jQuery('#clickwhale-tabs li').on('click', function() {
                            localStorage.setItem('tab-' + page_id, jQuery(this).index());

                            // If tab is not `Contents`
                            if ('1' !== localStorage.getItem('tab-' + page_id)){
                                // Show Clickwhale Widget Sidebar
                                jQuery('#poststuff > #post-body.metabox-holder #postbox-container-1').show();
                                jQuery('#poststuff > #post-body.metabox-holder').addClass('columns-2');
                            }
                        });
					<?php } ?>
                });
            </script>
		<?php } ?>

        <script type='text/javascript'>
            const {createPopup} = window.picmoPopup;

            jQuery(document).ready(function() {

                /* Vars */
                const
                    title = jQuery('#title'),
                    slug = jQuery('#cw-slug'),
                    limit = parseInt('<?php echo Linkpages_Helper::get_linkpage_links_limit() ?>'),
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
                    placeholder: '<?php _e( 'Select Content Type', CLICKWHALE_NAME ) ?>',
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
                jQuery('.row--cw_custom_content').each(function() {
                    const editorTextareaID = jQuery(this).find('textarea').attr('id');
                    wp.editor.initialize(editorTextareaID, {
                        mediaButtons: true,
                        tinymce: tinymceOptions,
                        quicktags: quicktagsOptions,
                    });
                });

                /* Draggable, Droppable, Sortable */
                let
                    contentWrap = jQuery('.links-list-wrap'),
                    contentItems = jQuery('.cw-content--items');

                jQuery(".cw-content--item", contentItems).draggable({
                    containment: "document",
                    connectToSortable: ".connectedSortable",
                    helper: "clone",
                    revert: "invalid",
                });

                contentWrap
                    .droppable({
                        accept: ".cw-content--item"
                    })
                    .sortable({
                        accept: ".cw-content--item",
                        placeholder: "ui-state-highlight",
                        handle: '.linkpage-row--drag',
                        receive: function(event, ui) {
                            const
                                el = jQuery(ui.helper),
                                type = el.data('content');

                            jQuery.post(ajaxurl, {
                                'security': '<?php echo $nonce_add_link ?>',
                                'action': 'clickwhale/admin/add_link_to_linkpage',
                                'type': type,
                            }, function(response) {

                                if (response.success && response.data.template) {
                                    const template = response.data.template;

                                    replace_block(el, template);

                                    jQuery('.select-link').select2({
                                        placeholder: '<?php _e( 'Select Item', CLICKWHALE_NAME ) ?>',
                                        width: '100%',
                                        minimumResultsForSearch: 10
                                    });

                                    if (count_links() >= limit) {
                                        links_limit_warning();
                                        jQuery('.cw-content--item').addClass('disabled').draggable('disable');
                                    }
                                }
                            });
                        },
                    }).disableSelection();

                /* ----- */

                jQuery(document)
                    // Close icons select on document click
                    .on('click', function() {
                        closeIconPicker();
                    })

                    // Prevent close the icons select on document click when target is the icons select container
                    .on('click', '#icon-picker--wrap, .icon-picker', function(e) {
                        e.stopPropagation();
                    })

                    // Init tinymce (wp.editor) on custom content block drag
                    .on('clickwhale.content.template.replace', '.links-list-wrap', function(e, template) {
                        const
                            templateRowID = jQuery(template).attr('id'),
                            templateID = templateRowID.replace('row-', '');

                        if (jQuery(template).hasClass('row--cw_custom_content')) {
                            wp.editor.initialize('cw_custom_content_' + templateID, {
                                mediaButtons: true,
                                tinymce: tinymceOptions,
                                quicktags: quicktagsOptions,
                            });
                        }
                    })

                    // Change hidden fields value on CW link select
                    .on('change', '.row--cw_link .select-link', function() {
                        const
                            parent = jQuery(this).closest('.linkpage-row'),
                            title = jQuery(this).find('option:selected').data('title'),
                            url = jQuery(this).find('option:selected').data('url'),
                            value = jQuery(this).val();

                        parent.find('[name^="links[0]"]').each(function() {
                            let name = jQuery(this).attr('name');
                            name = name.replace('links[0]', 'links[' + value + ']');
                            jQuery(this).attr('name', name);
                        });
                        parent.find('[name$="[post_id]"]').val(value);
                        parent.find('.linkpage-row--link strong').text(title);
                        parent.find('.linkpage-row--link span').text(url);
                    })

                    // Show Edit section
                    .on('click', '.linkpage-row--actions--button-edit', function() {
                        jQuery(this).closest('.linkpage-row').find('.linkpage-row--bottom').toggleClass('active');
                    })

                    // Init emoji picker and its actions
                    .on('click', '.emoji-picker', function(e) {
                        e.preventDefault();

                        const
                            trigger = e.target,
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
                    .on('click', '.icon-picker', function(e) {
                        e.preventDefault();

                        const
                            icons = [],
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

                        iconsContainer.find('button').each(function(index, element) {
                            icons.push(jQuery(element).data('icon'));
                        });

                        iconsContainer.find('input').on('keyup', function() {
                            const results = icons.filter(el => el.toLowerCase().includes(this.value.toLowerCase()));

                            iconsContainer.find('button').hide();
                            results.forEach(result => {
                                iconsContainer.find('[data-icon="' + result + '"]').show();
                            })
                        });

                    })
                    .on('click', '#icon-picker--wrap button', function(e) {
                        e.preventDefault();

                        const
                            iconButton = jQuery(this),
                            icon = iconButton.html(),
                            trigger = jQuery('#' + jQuery('#icon-picker--wrap').data('id') + '');

                        trigger.parent().find('input').val(iconButton.data('icon')).prop('checked', true);
                        trigger.prev().addClass('with-image').find('label').html(icon);

                        change_row_image(trigger, icon);
                        change_row_image_type(trigger, 'icon');

                        closeIconPicker();
                    })

                    // Remove added link row
                    .on('click', '.linkpage-row--actions--button-remove', function() {
                        jQuery(this).closest('.linkpage-row').remove();
                        if (count_links() < limit) {
                            jQuery('.links-info').remove();
                            jQuery('.cw-content--item').removeClass('disabled').draggable('enable');
                        }
                    })

                    // LP Logo
                    .on('click', '.linkpage-image-upload', function(e) {
                        e.preventDefault();

                        const
                            button = jQuery(this),
                            custom_uploader = wp.media({
                                title: 'Insert image',
                                library: {
                                    type: 'image',
                                },
                                button: {
                                    text: '<?php _e( 'Select Image', CLICKWHALE_NAME ) ?>',
                                },
                                multiple: false
                            }).on('select', function() {
                                const
                                    attachment = custom_uploader.state().get('selection').first().toJSON(),
                                    mediaInput = button.parent().find('input'),
                                    url = typeof attachment.sizes.thumbnail !== 'undefined' ? attachment.sizes.thumbnail.url : attachment.url;

                                button.removeClass('button').html('<img src="' + url + '">').next().show();
                                mediaInput.val(attachment.id).trigger("change");
                            }).open();
                    })

                    .on('click', '.linkpage-image-remove', function(e) {
                        e.preventDefault();

                        const button = jQuery(this);

                        button.next().val(''); // emptying the hidden field
                        button.hide().prev().addClass('button').html('<?php _e( 'Upload image', CLICKWHALE_NAME ) ?>');

                        disable_ogpreview_button();
                    })

                    // Row Image
                    .on('click', '.linkpage-row--image-upload', function(e) {
                        e.preventDefault();

                        const
                            button = jQuery(this),
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
                            }).on('select', function() { // it also has "open" and "close" events
                                const
                                    attachment = custom_uploader.state().get('selection').first().toJSON(),
                                    mediaInput = button.parent().find('input'),
                                    mediaLabel = button.parent().find('label'),
                                    mediaRemove = button.parent().find('.linkpage-row--image-remove'),
                                    url = typeof attachment.sizes.thumbnail !== 'undefined' ? attachment.sizes.thumbnail.url : attachment.url;

                                mediaLabel.html('<img src="' + url + '" />');
                                mediaInput.val(attachment.id).trigger("change").prop("checked", true);
                                mediaRemove.show();
                            }).open();

                    })
                    .on('click', '.linkpage-row--image-remove', function(e) {
                        e.preventDefault();

                        jQuery(this).parent().find('input').val('').prop("checked", false);
                        jQuery(this).parent().find('label').html('');
                        jQuery(this).hide();

                        /* Remove linkpage-row-image (tab image) when "Remove Image" was clicked */
                        change_row_image(jQuery(this), '', false);
                    })

                    // Toggle image type tabs
                    .on('change', '.linkpage-row--image-select select', function() {
                        jQuery('.linkpage-row--image-select--tab').hide();
                        jQuery('.linkpage-row--image-select--tab[data-tab="' + this.value + '"]').show();
                    })

                    // Toggle .linkpage-row-image content with selected image/icon/emoji
                    .on('change', '.image-item [type="radio"]', function() {
                        const imageItemValue = jQuery(this).next().html();

                        change_row_image(jQuery(this), imageItemValue);
                        change_row_image_type(jQuery(this), jQuery(this).data('type'));
                    })
                    .on('click', '.reset-image', function() {
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
                jQuery('td > input, td > textarea').on('keyup change blur', function() {
                    disable_ogpreview_button();
                })
                jQuery('input[type="hidden"]').bind('change', function() {
                    disable_ogpreview_button();
                });

                /**
                 * Submit action
                 * 1. Check title (not null)
                 * 2. Check slug (not null)
                 * 3. Check slug (exists as post/page slug)
                 */
                jQuery('#submit').on('click', function(e) {
                    jQuery('#clickwhale-tabs').tabs('option', 'active', 0);

                    if (!title.val()) {
                        e.preventDefault();
                        title.addClass('error')
                            .next().text('<?php _e( 'Please enter title', CLICKWHALE_NAME ) ?>');
                    } else {
                        title.removeClass('error').next().text('');
                    }

                    if (!slug.val()) {
                        e.preventDefault();
                        slug.addClass('error')
                            .next().text('<?php _e( 'Please enter slug', CLICKWHALE_NAME ) ?>');
                    } else {
                        slug.removeClass('error').next().text('');
                    }

                    if (slugExists() === true) {
                        e.preventDefault();
                        slug.addClass('error');
                        jQuery('#cw-slug--description').text('<?php _e( 'This slug is already in use! Please enter another slug', CLICKWHALE_NAME ) ?>');
                    }
                });

                /**
                 *  Reset styles to default
                 */
                jQuery('#reset-styles').on('click', function(e) {
                    e.preventDefault();

                    let defaults;

                    if (window.confirm('<?php _e( 'Are you sure? This action will set CSS styles to default. This process cannot be undone!', CLICKWHALE_NAME ) ?>')) {
                        defaults = <?php echo json_encode( $this->get_defaults() ); ?>;

                        jQuery.each(defaults.styles, function(key, val) {
                            jQuery('[name="styles[' + key + ']"').wpColorPicker('color', val);
                        });

                        <?php do_action( 'clickwhale_linkpage_reset_styles' ); ?>
                    }
                });

                /**
                 * FUNCTIONS
                 */

                function slugExists() {
                    let result = null;
                    jQuery.ajax({
                        async: false,
                        type: 'post',
                        dataType: 'json',
                        url: ajaxurl,
                        data: {
                            'security': '<?php echo $nonce ?>',
                            'action': 'clickwhale/admin/slug_exists',
                            'type': 'linkpage',
                            'slug': slug.val(),
                            'id': <?php echo esc_attr( intval( $_GET['id'] ?? 0 ) ); ?>
                        }, success: function(response) {
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
                    jQuery('<div class="links-info"><?php echo Linkpages_Helper::get_links_limitation_notice() . Helper::get_pro_message();  ?></div>').insertAfter('.links-list-wrap');
                }

                function disable_ogpreview_button() {
                    jQuery('#opengraph-live-preview')
                        .addClass('disabled')
                        .next()
                        .text('<?php _e( 'Save page to view Open Graph preview', CLICKWHALE_NAME ) ?>');
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