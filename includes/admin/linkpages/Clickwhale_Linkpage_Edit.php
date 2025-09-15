<?php
namespace clickwhale\includes\admin\linkpages;

use clickwhale\includes\admin\Clickwhale_Instance_Edit;
use clickwhale\includes\helpers\{Helper, Linkpages_Helper};

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Clickwhale_Linkpage_Edit extends Clickwhale_Instance_Edit {

    public function __construct() {
        parent::__construct( 'linkpages', 'linkpage', 'Link Page' );
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
            'favicon'              => '',
            'styles'               => array(
                'bg_color'            => '#fdd231',
                'text_color'          => '#1a1c1d',
                'link_bg_color'       => '#fee06f',
                'link_bg_color_hover' => '#ffffff',
                'link_color'          => '#1a1c1d',
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
                'url'  => 'settings'
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
            )
        );

        return apply_filters( 'clickwhale_linkpage_tabs', $tabs );
    }

    /**
     * @return array
     * @since 1.3.0
     */
    public static function get_select_values(): array {
        $values = array();

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
            )
        );

        // Post Types

        $post_types       = Helper::get_post_types();
        $post_types_group = array(
            'label'   => __( 'Post Types', 'clickwhale' ),
            'options' => array()
        );

        foreach ( $post_types as $name => $singular ) {
            $post_types_group['options'][$name]['name'] = $singular;
            $post_types_group['options'][$name]['icon'] = 'file';
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
            )
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
    public static function get_nav_menus(): array {
        $menus      = wp_get_nav_menus();
        $result     = array();
        $result[''] = __( 'No Menu', 'clickwhale' );
        foreach ( $menus as $menu ) {
            $result[$menu->term_id] = $menu->name;
        }

        return $result;
    }

    public function get_link_meta( $id, $key ): array {
        global $wpdb;
        $table_links_meta = $wpdb->prefix . 'clickwhale_meta';

        return (array) $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table_links_meta
                WHERE linkpage_id=%d
                AND meta_key=%s",
                intval( $id ),
                sanitize_text_field( $key )
            ),
            ARRAY_A
        );
    }

    private function save_linkpage_meta( $id, $key, $value, $action ) {
        global $wpdb;
        $links_meta_table = $wpdb->prefix . 'clickwhale_meta';

        switch ( $action ) {
            case 'insert':
                $wpdb->insert(
                    $links_meta_table,
                    array(
                        'meta_key'    => sanitize_text_field( $key ),
                        'meta_value'  => sanitize_text_field( $value ),
                        'linkpage_id' => intval( $id )
                    )
                );
                break;

            case 'update':
                $wpdb->update(
                    $links_meta_table,
                    array(
                        'meta_value' => sanitize_text_field( $value )
                    ),
                    array(
                        'linkpage_id' => intval( $id ),
                        'meta_key'    => sanitize_text_field( $key ),
                    )
                );
                break;
        }
    }

    public function save_update() {
        global $wpdb;

        $table = Helper::get_db_table_name( $this->instance_plural );
        $item  = array_intersect_key(
            $_POST,
            apply_filters( 'clickwhale_linkpage_defaults', $this->get_defaults() )
        );

        $item['links'] = isset( $item['links'] ) ? maybe_serialize( $item['links'] ) : '';
        $item['styles'] = isset( $item['styles'] ) ? maybe_serialize( $item['styles'] ) : '';
        $item['social'] = isset( $item['social'] ) ? maybe_serialize( $item['social'] ) : '';
        $item['author'] = get_current_user_id();

        // Data fot meta table
        $legals_menu_id = $item['meta__legals_menu_id'] ?? 0;
        unset( $item['meta__legals_menu_id'] );

        $item = apply_filters( 'clickwhale_linkpage_data_before_save', $item );
        $id = intval( $item['id'] );

        // Check if linkpage exists and then update or insert
        // in some cases default check (not false and < 0) goes wrong
        if ( Linkpages_Helper::get_by_id( $id ) ) {
            $wpdb->update(
                $table,
                $item,
                array( 'id' => $id )
            );
            $this->set_transient( $id, 'updated' );

        } else {
            $wpdb->insert(
                $table,
                $item
            );
            $id = $wpdb->insert_id;
            $this->set_transient( $id, 'added' );
        }

        if ( $this->get_link_meta( $id, 'legals_menu_id' ) ) {
            $this->save_linkpage_meta( $id, 'legals_menu_id', $legals_menu_id, 'update' );
        } else {
            $this->save_linkpage_meta( $id, 'legals_menu_id', $legals_menu_id, 'insert' );
        }

        $url = 'admin.php?page=' . CLICKWHALE_SLUG . '-edit-linkpage&id=' . $id;
        wp_redirect( esc_url_raw( admin_url( $url ) ) );
        exit;
    }

    public function admin_scripts(): void {
        if ( isset( $_GET['page'] ) && sanitize_key( $_GET['page'] ) === CLICKWHALE_SLUG . '-edit-linkpage' ) {
            ?>
            <script type='text/javascript'>
                jQuery(document).ready(function(){
                    jQuery('#clickwhale-tabs').tabs({
                        activate: function(event, ui){
                            if (jQuery(ui.newPanel[0]).attr('id') === 'lp-tab-styles'){
                                jQuery('#reset-styles').show();
                            } else {
                                jQuery('#reset-styles').hide();
                            }
                        }
                    });
                });
            </script>
            <?php
        } ?>

        <script type='text/javascript'>
            /* global wp */

            const {createPopup} = window.picmoPopup;

            jQuery(document).ready(function(){
                const
                    pageID = '<?php echo intval( $_GET['id'] ); ?>',
                    defaults = <?php echo json_encode( $this->get_defaults() ); ?>,
                    postTypes = <?php echo wp_json_encode( array_keys( Helper::get_post_types() ) ); ?>,
                    title = jQuery('#title'),
                    slug = jQuery('#cw-slug'),
                    limit = parseInt('<?php echo Linkpages_Helper::get_linkpage_links_limit(); ?>'),
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
                    quicktagsOptions = {buttons: 'strong,em,link,block,del,ins,img,ul,ol,li,code,close'},
                    textColor = jQuery('[name="styles[text_color]"]'),
                    bgColor = jQuery('[name="styles[bg_color]"]'),
                    linkBgColor = jQuery('[name="styles[link_bg_color]"]'),
                    linkBgColorHover = jQuery('[name="styles[link_bg_color_hover]"]'),
                    linkColor = jQuery('[name="styles[link_color]"]'),
                    linkColorHover = jQuery('[name="styles[link_color_hover]"]'),
                    ogPreview = jQuery('#opengraph-live-preview');

                /* Select2 init */
                linksType.select2({
                    placeholder: '<?php echo esc_js( __( 'Select Content Type', 'clickwhale' ) ); ?>',
                    width: '100%',
                    minimumResultsForSearch: -1
                });

                /* Color Picker init */
                jQuery('.cw-color-control').wpColorPicker();

                /* Disable add link button if links limit is reached */
                if (jQuery('.linkpage-row').length >= limit){
                    jQuery('#add-pagelink-link').prop('disabled', true);
                }

                /* Init wp.editor */
                jQuery('.linkpage-row.row--cw_custom_content').each(function(){
                    const editorTextareaID = jQuery(this).find('textarea').attr('id');

                    wp.editor.initialize(editorTextareaID, {
                        mediaButtons: true,
                        tinymce: tinymceOptions,
                        quicktags: quicktagsOptions
                    });

                    bindEditorEvents(jQuery(this));
                });

                /* Draggable, Droppable, Sortable */
                let
                    contentWrap = jQuery('.links-list-wrap'),
                    contentItems = jQuery('.cw-content--items');

                jQuery(".cw-content--item", contentItems).draggable({
                    containment: "document",
                    connectToSortable: ".connectedSortable",
                    helper: "clone",
                    revert: "invalid"
                });

                contentWrap
                    .droppable({
                        accept: ".cw-content--item"
                    })
                    .sortable({
                        accept: ".cw-content--item",
                        placeholder: "ui-state-highlight",
                        handle: '.linkpage-row--drag',
                        receive: function(event, ui){
                            const
                                el = jQuery(ui.helper),
                                type = el.data('content');

                            jQuery.post(ajaxurl, {
                                'security': <?php echo wp_json_encode( wp_create_nonce( 'clickwhale_add_link_to_linkpage' ) ); ?>,
                                'action': 'clickwhale/admin/add_link_to_linkpage',
                                'type': type
                            }, function(response){
                                if (response.success && response.data.template) {
                                    const template = response.data.template;

                                    replace_block(el, template);

                                    jQuery('.select-link').select2({
                                        placeholder: '<?php echo esc_js( __( 'Select Item', 'clickwhale' ) ); ?>',
                                        width: '100%',
                                        minimumResultsForSearch: 10
                                    });

                                    if (count_links() >= limit){
                                        links_limit_warning();
                                        jQuery('.cw-content--item').addClass('disabled').draggable('disable');
                                    }
                                }
                            });
                        },
                    })
                    .disableSelection();

                if (pageID < 1){
                    disable_ogpreview_button();
                }

                /* ----- */

                jQuery(document)
                    // Close icons select on document click
                    .on('click', function(){
                        closeIconPicker();
                    })

                    // Prevent close the icons select on document click when target is the icons select container
                    .on('click', '#icon-picker--wrap, .icon-picker', function(e){
                        e.stopPropagation();
                    })

                    // Init TinyMCE (wp.editor) on custom content block drag
                    .on('clickwhale.content.template.replace', '.links-list-wrap', function(e, template){
                        const $row = jQuery(template);

                        if ($row.hasClass('row--cw_custom_content')){
                           const
                               rowID = $row.attr('id'),
                               templateID = rowID.replace(/^row-/, ''),
                               editorID = 'cw_custom_content_' + templateID;

                            wp.editor.initialize(editorID, {
                                mediaButtons: true,
                                tinymce: tinymceOptions,
                                quicktags: quicktagsOptions
                            });

                            const tryBind = () => {
                                const
                                    editor = tinymce.get(editorID),
                                    $newRow = jQuery(`#${rowID}`);

                                if (editor){
                                    bindEditorEvents($newRow);
                                } else {
                                    // Wait for TinyMCE to initialize before binding events
                                    setTimeout(tryBind, 100);
                                }
                            };

                            tryBind();
                        }
                    })

                    // `Clickwhale Link`
                    .on('change', '.row--cw_link .select-link', function(){
                        const
                            $row = jQuery(this).closest('.linkpage-row'),
                            title = jQuery(this).find('option:selected').data('title'),
                            url = jQuery(this).find('option:selected').data('url'),
                            value = jQuery(this).val();

                        $row.removeClass('invalid');
                        $row.find('.linkpage-row--bottom .select2-selection').removeClass('invalid');
                        $row.find('[name^="links[0]"]').each(function(){
                            let name = jQuery(this).attr('name');
                            name = name.replace('links[0]', 'links[' + value + ']');
                            jQuery(this).attr('name', name);
                        });
                        $row.find('[name$="[post_id]"]').val(value);
                        $row.find('.linkpage-row--link strong').text(title);
                        $row.find('.linkpage-row--link span').text(url);
                    })

                    // Show Edit section
                    .on('click', '.linkpage-row--actions--button-edit', function(){
                        jQuery(this).closest('.linkpage-row').find('.linkpage-row--bottom').toggleClass('active');
                    })

                    // Init emoji picker and its actions
                    .on('click', '.emoji-picker', function(e){
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
                    .on('click', '.icon-picker', function(e){
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

                        iconsContainer.find('button').each(function(index, element){
                            icons.push(jQuery(element).data('icon'));
                        });

                        iconsContainer.find('input').on('keyup', function(){
                            const results = icons.filter(el => el.toLowerCase().includes(this.value.toLowerCase()));

                            iconsContainer.find('button').hide();
                            results.forEach(result => {
                                iconsContainer.find('[data-icon="' + result + '"]').show();
                            })
                        });
                    })

                    .on('click', '#icon-picker--wrap button', function(e){
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
                    .on('click', '.linkpage-row--actions--button-remove', function(){
                        jQuery(this).closest('.linkpage-row').remove();
                        if (count_links() < limit) {
                            jQuery('.links-info').remove();
                            jQuery('.cw-content--item').removeClass('disabled').draggable('enable');
                        }
                    })

                    // `Upload image` button click
                    .on('click', '.linkpage-image-upload', function(e){
                        e.preventDefault();

                        const
                            button = jQuery(this);

                        // Bail if this is `Upload Site Icon` button
                        if (button.parent('.favicon-field').length){
                            return;
                        }

                        const
                            mediaInput = button.parent().find('input'),
                            uploader = wp.media({
                                title: '<?php echo esc_js( __( 'Insert Image', 'clickwhale' ) ); ?>',
                                library: {
                                    type: 'image'
                                },
                                button: {
                                    text: '<?php echo esc_js( __( 'Select Image', 'clickwhale' ) ); ?>'
                                }
                            });

                        uploader.on('select', function(){
                            const
                                attachment = uploader.state().get('selection').first().toJSON(),
                                url = typeof attachment.sizes.thumbnail !== 'undefined' ? attachment.sizes.thumbnail.url : attachment.url;

                            button
                                .removeClass('button')
                                .html('<img src="' + url + '" />')
                                .next().show();

                            mediaInput
                                .val(attachment.id)
                                .trigger('change');
                        });

                        uploader.open();
                    })

                    /**
                     * `Upload image` button for LP Site Icon.
                     * `Initializes media frame for image selecting or cropping
                     */
                    .on('click', '.favicon-field .linkpage-image-upload', function(e){
                        e.preventDefault();

                        const
                            button = jQuery(this),
                            mediaInput = button.parent().find('input');

                        let uploader = wp.media({
                            button: {
                                text: '<?php echo esc_js( __( 'Select Image', 'clickwhale' ) ); ?>',

                                // Don't close, we might need to crop
                                close: false
                            },
                            states: [
                                new wp.media.controller.Library({
                                    title: '<?php echo esc_js( __( 'Insert Image', 'clickwhale' ) ); ?>',
                                    library: wp.media.query({
                                        type: 'image'
                                    }),
                                    date: false,
                                    suggestedWidth: '512',
                                    suggestedHeight: '512'
                                }),
                                new wp.media.controller.SiteIconCropper({
                                    control: {
                                        params: {
                                            width: '512',
                                            height: '512'
                                        }
                                    },
                                    imgSelectOptions: calculateImageSelectOptions
                                })
                            ]
                        });

                        uploader
                            .on('cropped', function(attachment){
                                button
                                    .removeClass('button')
                                    .html('<img src="' + attachment.url + '" />')
                                    .next().show();

                                mediaInput.val(attachment.id);
                                uploader.close();

                                // Start over with a fresh frame
                                uploader = null;
                            })
                            .on('select', function(){
                                const attachment = uploader.state().get('selection').first();

                                if (attachment.attributes.height === 512 &&
                                    512 === attachment.attributes.width
                                ){
                                    button
                                        .removeClass('button')
                                        .html('<img src="' + attachment.attributes.url + '" />')
                                        .next().show();

                                    mediaInput.val(attachment.id);
                                    uploader.close();
                                } else {
                                    uploader.setState('cropper');
                                }
                            });

                        uploader.open();
                    })

                    // `Remove image` button click
                    .on('click', '.linkpage-image-remove', function(e){
                        e.preventDefault();

                        const
                            button = jQuery(this),
                            uploadBtnText = (button.parent('.favicon-field').length) ?
                                '<?php echo esc_js( __( 'Upload Site Icon', 'clickwhale' ) ); ?>' :
                                '<?php echo esc_js( __( 'Upload image', 'clickwhale' ) ); ?>';

                        button
                            .next()
                            .val(''); // emptying the hidden field

                        button
                            .hide()
                            .prev()
                                .addClass('button')
                                .html(uploadBtnText);

                        if (button.parent('.og-image-field').length){
                            disable_ogpreview_button();
                        }
                    })

                    // `Upload image` button for Row Image
                    .on('click', '.linkpage-row--image-upload', function(e){
                        e.preventDefault();

                        const
                            button = jQuery(this),
                            uploader = wp.media({
                                title: '<?php echo esc_js( __( 'Insert Image', 'clickwhale' ) ); ?>',
                                library: {
                                    // uploadedTo : wp.media.view.settings.post.id, // attach to the current post?
                                    type: 'image',
                                },
                                button: {
                                    text: '<?php echo esc_js( __( 'Use this image', 'clickwhale' ) ); ?>'
                                },
                                multiple: false
                            });

                        uploader.on('select', function(){
                            const
                                attachment = uploader.state().get('selection').first().toJSON(),
                                mediaInput = button.parent().find('input'),
                                mediaLabel = button.parent().find('label'),
                                mediaRemove = button.parent().find('.linkpage-row--image-remove'),
                                url = typeof attachment.sizes.thumbnail !== 'undefined' ? attachment.sizes.thumbnail.url : attachment.url;

                            mediaLabel.html('<img src="' + url + '" />');

                            mediaInput
                                .val(attachment.id)
                                .trigger('change')
                                .prop('checked', true);

                            mediaRemove.show();
                        });

                        uploader.open();
                    })

                    // `Remove image` button for Row Image
                    .on('click', '.linkpage-row--image-remove', function(e){
                        e.preventDefault();

                        jQuery(this).parent().find('input').val('').prop('checked', false);
                        jQuery(this).parent().find('label').html('');
                        jQuery(this).hide();

                        /* Remove linkpage-row-image (tab image) when "Remove Image" was clicked */
                        change_row_image(jQuery(this), '', false);
                    })

                    // Toggle image type tabs
                    .on('change', '.linkpage-row--image-select select', function(){
                        jQuery('.linkpage-row--image-select--tab').hide();
                        jQuery('.linkpage-row--image-select--tab[data-tab="' + this.value + '"]').show();
                    })

                    // Toggle .linkpage-row-image content with selected image/icon/emoji
                    .on('change', '.image-item [type="radio"]', function(){
                        const imageItemValue = jQuery(this).next().html();

                        change_row_image(jQuery(this), imageItemValue);
                        change_row_image_type(jQuery(this), jQuery(this).data('type'));
                    })

                    // `Reset` button for Row Image
                    .on('click', '.reset-image', function(){
                        const $row = jQuery(this).closest('.linkpage-row');

                        $row.find('.linkpage-row--image').removeClass('with-image').html('');
                        $row.find('.image-item').find('label').html('');
                        $row.find('[name$="[image][image_id]"]').prop('checked', false);
                        $row.find('[name$="[image][type]"]').val('');
                    })

                    // `Custom Link`
                    .on('input', '.linkpage-row.row--cw_custom_link .linkpage-row--bottom input[name^="links["][name$="[url]"]', function(){
                        jQuery(this).closest('.linkpage-row').removeClass('invalid');
                        jQuery(this).removeClass('invalid');
                    })

                    // `Heading`
                    .on('input', '.linkpage-row.row--cw_heading .linkpage-row--bottom input[name^="links["][name$="[title]"]', function(){
                        jQuery(this).closest('.linkpage-row').removeClass('invalid');
                        jQuery(this).removeClass('invalid');
                    });

                // WP `post`, `page` and custom post types from `wp_posts` table
                for (const type of postTypes){
                    jQuery(document).on('change', `.linkpage-row.row--${type} .select-link`, function(){
                        const $row = jQuery(this).closest('.linkpage-row');

                        $row.removeClass('invalid');
                        $row.find('.linkpage-row--bottom .select2-selection').removeClass('invalid');
                    });
                }

                /* Page text color */

                // Check if `page text color` hex color is valid on page load
                // because `wpColorPicker` doesn't sometimes
                if (!validateHexColor(textColor.val())){
                    // Fallback to default `page text color` value
                    textColor.val(defaults.styles.text_color);

                    const resultButton = textColor.closest('.wp-picker-container').find('button.wp-color-result');

                    resultButton.css('background', textColor.val());
                }

                // Dynamically change `button.wp-color-result` background for `page text color`
                textColor.on('input', debounce(function(){
                    const
                        inputField = jQuery(this),
                        resultButton = jQuery(this).closest('.wp-picker-container').find('button.wp-color-result');

                    if (inputField.hasClass('iris-error')){
                        resultButton.css('background', 'transparent');
                    } else {
                        // Change `button.wp-color-result` background color when hex color is valid
                        // because `wpColorPicker` doesn't sometimes
                        if (validateHexColor(inputField.val())){
                            resultButton.css('background', inputField.val());
                        }
                    }
                }));

                /* Page background color */

                // Dynamically change `button.wp-color-result` background for `page background color`
                bgColor.on('input', debounce(function(){
                    const
                        inputField = jQuery(this),
                        resultButton = jQuery(this).closest('.wp-picker-container').find('button.wp-color-result');

                    if (inputField.hasClass('iris-error')){
                        resultButton.css('background', 'transparent');
                    } else {
                        // Change `button.wp-color-result` background color when hex color is valid
                        // because `wpColorPicker` doesn't sometimes
                        if (validateHexColor(inputField.val())){
                            resultButton.css('background', inputField.val());
                        }
                    }
                }));

                /* Link background color */

                // Check if `link background color` hex color is valid on page load
                // because `wpColorPicker` doesn't sometimes
                if (!validateHexColor(linkBgColor.val())){
                    const resultButton = linkBgColor.closest('.wp-picker-container').find('button.wp-color-result');

                    resultButton.css('background', 'transparent');
                }

                // Dynamically change `button.wp-color-result` background for `link background color`
                linkBgColor.on('input', debounce(function(){
                    const
                        inputField = jQuery(this),
                        resultButton = jQuery(this).closest('.wp-picker-container').find('button.wp-color-result');

                    if (inputField.hasClass('iris-error')){
                        resultButton.css('background', 'transparent');
                    } else {
                        // Change `button.wp-color-result` background color when hex color is valid
                        // because `wpColorPicker` doesn't sometimes
                        if (validateHexColor(inputField.val())){
                            resultButton.css('background', inputField.val());
                        }
                    }
                }));

                /* Link background color:hover */

                // Dynamically change `button.wp-color-result` background for `link background color:hover`
                linkBgColorHover.on('input', debounce(function(){
                    const
                        inputField = jQuery(this),
                        resultButton = jQuery(this).closest('.wp-picker-container').find('button.wp-color-result');

                    if (inputField.hasClass('iris-error')){
                        resultButton.css('background', 'transparent');
                    } else {
                        // Change `button.wp-color-result` background color when hex color is valid
                        // because `wpColorPicker` doesn't sometimes
                        if (validateHexColor(inputField.val())){
                            resultButton.css('background', inputField.val());
                        }
                    }
                }));

                /* Link text color */

                // Check if `link text color` hex color is valid on page load
                // because `wpColorPicker` doesn't sometimes
                if (!validateHexColor(linkColor.val())){
                    // Fallback to default `link text color` value
                    linkColor.val(defaults.styles.link_color);

                    const resultButton = linkColor.closest('.wp-picker-container').find('button.wp-color-result');

                    resultButton.css('background', linkColor.val());
                }

                // Dynamically change `button.wp-color-result` background for `link text color`
                linkColor.on('input', debounce(function(){
                    const
                        inputField = jQuery(this),
                        resultButton = jQuery(this).closest('.wp-picker-container').find('button.wp-color-result');

                    if (inputField.hasClass('iris-error')){
                        resultButton.css('background', 'transparent');
                    } else {
                        // Change `button.wp-color-result` background color when hex color is valid
                        // because `wpColorPicker` doesn't sometimes
                        if (validateHexColor(inputField.val())){
                            resultButton.css('background', inputField.val());
                        }
                    }
                }));

                /* Link text color:hover */

                // Check if `link text color:hover` hex color is valid on page load
                // because `wpColorPicker` doesn't sometimes
                if (!validateHexColor(linkColorHover.val())){
                    // Fallback to default `link text color` value
                    linkColorHover.val(defaults.styles.link_color_hover);

                    const resultButton = linkColorHover.closest('.wp-picker-container').find('button.wp-color-result');

                    resultButton.css('background', linkColorHover.val());
                }

                // Dynamically change `button.wp-color-result` background for `link text color:hover`
                linkColorHover.on('input', debounce(function(){
                    const
                        inputField = jQuery(this),
                        resultButton = jQuery(this).closest('.wp-picker-container').find('button.wp-color-result');

                    if (inputField.hasClass('iris-error')){
                        resultButton.css('background', 'transparent');
                    } else {
                        // Change `button.wp-color-result` background color when hex color is valid
                        // because `wpColorPicker` doesn't sometimes
                        if (validateHexColor(inputField.val())){
                            resultButton.css('background', inputField.val());
                        }
                    }
                }));

                /**
                 * Disable OpenGraph Preview button
                 *
                 * 1. Only top level input/textarea
                 * 2. On hidden input change
                 */
                jQuery('#lp-tab-seo td > input, #lp-tab-seo td > textarea').on('keyup change blur', function(){
                    disable_ogpreview_button();
                });

                jQuery('#lp-tab-seo .og-image-field input[type="hidden"]').on('change', function(){
                    disable_ogpreview_button();
                });

                /**
                 * Title blur action
                 */
                title
                    .on('blur', function(){
                        const
                            $this = jQuery(this),
                            original = $this.val();

                        if (!original){
                            return false;
                        }
                    })

                    .on('input', function(){
                        jQuery(this).removeClass('error').next().text('');
                    });

                /**
                 * Slug blur action.
                 * Sanitize slug and paste link page slug into `URL Preview`
                 */
                slug
                    .on('blur', function(){
                        const
                            $this = jQuery(this),
                            original = $this.val(),
                            $previewContainer = jQuery('#cw-slug--text');

                        if (!original){
                            $previewContainer.find('span').html('');
                            return false;
                        }

                        const sanitized = sanitizeSlug(original);
                        $this.val(sanitized);
                        $previewContainer.find('span').html(sanitized + '/');

                        if (!sanitized){
                            $this.addClass('error')
                                .next().html(`
                                    <?php echo esc_js( esc_html__( 'Please enter slug', 'clickwhale' ) ); ?>
                                    <br>
                                    <?php echo esc_js( esc_html__( 'Allowed alphanumeric characters (a...z, A...Z, 0...9), underscore (_) and dash (-)', 'clickwhale' ) ); ?>
                                    `.trim()
                            );
                        }
                    })

                    .on('input', function(){
                        jQuery(this).removeClass('error').next().text('');
                    });

                /**
                 * Submit action
                 * 1. Check if title is not empty
                 * 2. Check if slug is not empty
                 * 3. Check if slug is not already used by CW links, CW link pages, WP posts/pages/taxonomies
                 * 4. Check if required fields of content blocks are not empty
                 */
                jQuery('#submit').on('click', function(e){
                    if (!title.val()){
                        tabNotValid(e);
                        title.addClass('error')
                            .next().text('<?php echo esc_js( __( 'Please enter title', 'clickwhale' ) ); ?>');
                        return false;
                    } else {
                        title.removeClass('error').next().text('');
                    }

                    if (!slug.val()){
                        tabNotValid(e);
                        slug.addClass('error')
                            .next().text('<?php echo esc_js( __( 'Please enter slug', 'clickwhale' ) ); ?>');
                        return false;
                    } else {
                        slug.removeClass('error').next().text('');
                    }

                    slug.val(sanitizeSlug(slug.val()));

                    if (!slug.val()){
                        tabNotValid(e);
                        slug.addClass('error')
                            .next().html(`
                                <?php echo esc_js( esc_html__( 'Please enter slug', 'clickwhale' ) ); ?>
                                <br>
                                <?php echo esc_js( esc_html__( 'Allowed alphanumeric characters (a...z, A...Z, 0...9), underscore (_) and dash (-)', 'clickwhale' ) ); ?>
                                `.trim()
                            );
                        return false;
                    } else {
                        slug.removeClass('error').next().text('');
                    }

                    let slug_obj = slugExists();

                    if (undefined !== slug_obj.id){
                        tabNotValid(e);
                        slug.addClass('error')
                            .next().html(`
                                <?php echo esc_js( esc_html__( 'This slug is already used in %1$s (%2$s ID: %d)', 'clickwhale' ) ) . '.'; ?>
                                <?php echo esc_js( esc_html__( 'Please enter another slug', 'clickwhale' ) ); ?>
                                `.trim()
                                .replace('%1$s', `<b>${slug_obj.title}</b>`)
                                .replace('%2$s', slug_obj.type)
                                .replace('%d', slug_obj.id)
                            );
                        return false;
                    } else {
                        slug.removeClass('error').next().text('');
                    }

                    // Content blocks
                    const blocks = validateBlocks();

                    if (blocks.length > 0){
                        const contentsTabIndex = jQuery('#clickwhale-tabs li a[href="#lp-tab-contents"]').closest('li').index();

                        tabNotValid(e, contentsTabIndex);
                        jQuery('#post-body-content .error.linkpage-blocks').remove();
                        jQuery('#post-body-content').prepend('<div class="error linkpage-blocks"><p><?php echo esc_js( __( 'Some content block fields are not valid. Please correct them or remove the blocks.', 'clickwhale' ) ); ?></p></div>');
                        jQuery('.linkpage-row').removeClass('invalid');
                        jQuery('.linkpage-row .linkpage-row--bottom').removeClass('active');

                        blocks.forEach(block => {
                            const
                                $row = jQuery(`#row-${block.id}`),
                                $bottom = $row.find('.linkpage-row--bottom');

                            $row.addClass('invalid');
                            $bottom.addClass('active');

                            /** ClickWhale Content group */

                            // `ClickWhale Link`
                            if ($row.hasClass('row--cw_link')){
                                $bottom.find('.select2-selection').addClass('invalid');
                            }

                            // `Custom Link`
                            else if ($row.hasClass('row--cw_custom_link')){
                                $bottom.find(`input[name="links[${block.id}][url]"]`).addClass('invalid');
                            }

                            // `Custom Content`
                            else if ($row.hasClass('row--cw_custom_content')){
                                $bottom.find('.wp-editor-container').addClass('invalid');
                            }

                            /** Post Types group */

                            // WP `post`, `page` and custom post types from `wp_posts` table
                            else if (postTypes.some(type => $row.hasClass(`row--${type}`))){
                                $bottom.find('.select2-selection').addClass('invalid');
                            }

                            /** Formatting group */

                            // `Heading`
                            else if ($row.hasClass('row--cw_heading')){
                                $bottom.find(`input[name="links[${block.id}][title]"]`).addClass('invalid');
                            }

                            <?php do_action( 'clickwhale_linkpage_invalid_blocks' ); ?>
                        });
                    }
                });

                /**
                 *  Reset styles to default
                 */
                jQuery('#reset-styles').on('click', function(e){
                    e.preventDefault();

                    let defaults;

                    if (window.confirm('<?php echo esc_js( __( 'Are you sure? This action will set CSS styles to default. This process cannot be undone!', 'clickwhale' ) ); ?>')){
                        defaults = <?php echo json_encode( $this->get_defaults() ); ?>;

                        jQuery.each(defaults.styles, function(key, val){
                            jQuery('[name="styles[' + key + ']"').wpColorPicker('color', val);
                        });

                        <?php do_action( 'clickwhale_linkpage_reset_styles' ); ?>
                    }
                });

                /** JS FUNCTIONS */

                // `Custom Content`
                function bindEditorEvents($row){
                    const
                        rowID = $row.attr('id').replace(/^row-/, ''),
                        $bottom = $row.find('.linkpage-row--bottom'),
                        textareaID = $row.find(`textarea[name="links[${rowID}][content]"]`).attr('id'),
                        $textarea = jQuery(`#${textareaID}`),
                        editor = tinymce.get(textareaID),
                        $editorContainer = $bottom.find('.wp-editor-container');

                    // `Code` mode
                    $textarea.on('change input', function(){
                        if ('' !== jQuery(this).val()){
                            $row.removeClass('invalid');
                            $editorContainer.removeClass('invalid');
                        }
                    });

                    // `Visual` mode (TinyMCE)
                    if (editor){
                        editor.on('input', () => {
                            editor.save();

                            if ('' !== $textarea.val()){
                                $row.removeClass('invalid');
                                $editorContainer.removeClass('invalid');
                            }
                        });
                    }
                }

                function validateBlocks(){
                    const
                        results = [],
                        $rows = jQuery('.linkpage-row');

                    for (let i = 0; i < $rows.length; i++){
                        const
                            $row = jQuery($rows[i]),
                            rowID = $row.attr('id').replace(/^row-/, '');

                        /** ClickWhale Content group */

                        // `ClickWhale Link`
                        if ($row.hasClass('row--cw_link')){
                            if (!$row.find('.linkpage-row--link span').text()){
                                results.push({'id': rowID});
                            }
                        }

                        // `Custom Link`
                        else if ($row.hasClass('row--cw_custom_link')){
                            if (!$row.find(`input[name="links[${rowID}][url]"]`).val()){
                                results.push({'id': rowID});
                            }
                        }

                        // `Custom Content`
                        else if ($row.hasClass('row--cw_custom_content')){
                            const
                                textareaID = $row.find(`textarea[name="links[${rowID}][content]"]`).attr('id'),
                                editor = tinymce.get(textareaID);

                            let textValue;

                            if (editor && !editor.isHidden()){
                                // Ensure `Visual` (TinyMCE) and `Code` editor modes are synchronized
                                editor.save();
                                textValue = editor.getContent();
                            } else {
                                textValue = jQuery(`#${textareaID}`).val();
                            }

                            if (!textValue){
                                results.push({'id': rowID});
                            }
                        }

                        /** Post Types group */

                        // WP `post`, `page` and custom post types from `wp_posts` table
                        else if (postTypes.some(type => $row.hasClass(`row--${type}`))){
                            if (!$row.find('.linkpage-row--link span').text()
                             && !$row.find('.select-link').val()
                            ){
                                results.push({'id': rowID});
                            }
                        }

                        /** Formatting group */

                        // Ignore `Separator`
                        else if ($row.hasClass('row--cw_separator')){}

                        // `Heading`
                        else if ($row.hasClass('row--cw_heading')){
                            if (!$row.find(`input[name="links[${rowID}][title]"]`).val()){
                                results.push({'id': rowID});
                            }
                        }

                        <?php do_action( 'clickwhale_linkpage_validate_blocks' ); ?>
                    }

                    return results;
                }

                function sanitizeSlug(){
                    let result = null;
                    jQuery.ajax({
                        async: false,
                        type: 'post',
                        dataType: 'json',
                        url: ajaxurl,
                        data: {
                            'security': <?php echo wp_json_encode( wp_create_nonce( 'sanitize_slug' ) ); ?>,
                            'action': 'clickwhale/admin/sanitize_slug',
                            'type': 'linkpage',
                            'slug': slug.val()
                        }, success: function(response){
                            result = response.data;
                        }
                    });
                    return result;
                }

                function slugExists(){
                    let result = null;
                    jQuery.ajax({
                        async: false,
                        type: 'post',
                        dataType: 'json',
                        url: ajaxurl,
                        data: {
                            'security': <?php echo wp_json_encode( wp_create_nonce( 'slug_exists' ) ); ?>,
                            'action': 'clickwhale/admin/slug_exists',
                            'type': 'linkpage',
                            'slug': slug.val(),
                            'id': <?php echo intval( $_GET['id'] ?? 0 ); ?>
                        }, success: function(response){
                            result = response.data;
                        }
                    });
                    return result;
                }

                function count_links(){
                    return jQuery('.linkpage-row').length;
                }

                function replace_block(target, template){
                    target.replaceWith(template);
                    jQuery('.links-list-wrap').trigger('clickwhale.content.template.replace', template);
                }

                function links_limit_warning(){
                    jQuery('#add-pagelink-link').prop('disabled', true);
                    jQuery('<div class="links-info"><?php echo Linkpages_Helper::get_links_limitation_notice() . Helper::get_pro_message(); ?></div>').insertAfter('.links-list-wrap');
                }

                function disable_ogpreview_button(){
                    ogPreview
                        .addClass('disabled')
                        .next()
                        .text('<?php echo esc_js( __( 'Please save the page to view Open Graph preview', 'clickwhale' ) ); ?>');
                }

                function change_row_image(element, image, active = true){
                    if (active){
                        jQuery(element).closest('.linkpage-row').find('.linkpage-row--image').addClass('with-image').html(image);
                    } else {
                        jQuery(element).closest('.linkpage-row').find('.linkpage-row--image').removeClass('with-image').html(image);
                    }
                }

                function change_row_image_type(element, type){
                    jQuery(element).closest('.linkpage-row').find('[name$="[image][type]"]').val(type);
                }

                function closeIconPicker(){
                    jQuery('#icon-picker--wrap').hide().find('button').show();
                    jQuery('[name="icon-picker--search"]').val('');
                }

                // Debounce function to limit the frequency of function calls
                // e.g. for handling user input events from color picker
                function debounce(func, delay = 300){
                    let timer;
                    return function(...args){
                        clearTimeout(timer);
                        timer = setTimeout(() => func.apply(this, args), delay);
                    };
                }

                // Validate hex color
                function validateHexColor(val){
                    return /^#([0-9A-F]{3}){1,2}$/i.test(val);
                }

                function tabNotValid(e, tab = 0){
                    e.preventDefault();
                    jQuery('.updated').remove();
                    jQuery('#clickwhale-tabs').tabs('option', 'active', tab);
                    jQuery('html, body').animate({scrollTop: 0}, 'fast');
                }

                /**
                 * Calculate image selection options based on the attachment dimensions
                 *
                 * @param {Object} attachment Attachment object representing the image
                 * @return {Object} Image selection options
                 */
                function calculateImageSelectOptions(attachment){
                    const
                        realWidth = attachment.get('width'),
                        realHeight = attachment.get('height');

                    let
                        xInit = 512,
                        yInit = 512,
                        ratio = xInit / yInit,
                        xImg = xInit,
                        yImg = yInit,
                        x1,
                        y1,
                        imgSelectOptions;

                    if (realWidth / realHeight > ratio) {
                        yInit = realHeight;
                        xInit = yInit * ratio;
                    } else {
                        xInit = realWidth;
                        yInit = xInit / ratio;
                    }

                    x1 = (realWidth - xInit) / 2;
                    y1 = (realHeight - yInit) / 2;

                    imgSelectOptions = {
                        aspectRatio: xInit + ':' + yInit,
                        handles: true,
                        keys: true,
                        instance: true,
                        persistent: true,
                        imageWidth: realWidth,
                        imageHeight: realHeight,
                        minWidth: xImg > xInit ? xInit : xImg,
                        minHeight: yImg > yInit ? yInit : yImg,
                        x1: x1,
                        y1: y1,
                        x2: xInit + x1,
                        y2: yInit + y1,
                    };

                    return imgSelectOptions;
                }
            });
        </script>
        <?php
    }
}