<?php

namespace Clickwhale\Admin\SmartDisplays;

use Clickwhale\Admin\Clickwhale_Instance_Edit;
use Clickwhale\Helpers\{
        Helper,
        Smart_Displays_Helper,
        Links_Helper
};

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * @since 2.6.0
 */
class Clickwhale_Smart_Display_Edit extends Clickwhale_Instance_Edit {

    public function __construct() {
        parent::__construct( 'smart_displays', 'smart_display', 'Smart Display' );
    }

    private function get_current_link_data(): array {
        $smart_display = $this->get_item( $_GET );

        if ( empty( $smart_display['id'] ) ) {
            return array();
        }

        if ( empty( $smart_display['link_id'] ) ) {
            return array();
        }

        $link = Links_Helper::get_by_id( $smart_display['link_id'] );

        if ( empty( $link['id'] ) ) {
            return array();
        }

        return Smart_Displays_Helper::prepare_link_data( $link );
    }

    /**
     * Default values for new item
     * Could be hooked by filter "clickwhale_smart_display_defaults"
     * @return array
     */
    public function get_defaults(): array {
        $smart_displays_options  = get_option( 'clickwhale_smart_displays_options' );
        $plugin_defaults         = clickwhale()->settings->default_options();
        $smart_displays_defaults = $plugin_defaults['smart_displays']['options'];
        $primary_defaults        = $smart_displays_defaults['primary'];
        $primary_options         = $smart_displays_options['primary'] ?? $primary_defaults;

        return array(
                'id'          => 0,
                'title'       => '',
                'image'       => 0,
                'link_id'     => '',
                'description' => '',
                'disclosure'  => esc_textarea( wp_unslash( $smart_displays_options['disclosure'] ?? $smart_displays_defaults['disclosure'] ) ),
                'options'     => array(
                        'is_title_linked' => 0,
                        'primary'         => array(
                                'text' => esc_attr( $primary_options['text'] ?? $primary_defaults['text'] )
                        )
                ),
                'created_at'  => ''
        );
    }

    public function save_update() {
        global $wpdb;
        $table = Helper::get_db_table_name( $this->instance_plural );
        $item  = array_intersect_key(
                $_POST,
                apply_filters( 'clickwhale_smart_display_defaults', $this->get_defaults() )
        );

        // Title
        $item['title'] = sanitize_text_field( wp_unslash( $item['title'] ?? '' ) );

        // Description
        $description         = ! empty( $item['description'] ) ? wp_unslash( $item['description'] ) : '';
        $description         = html_entity_decode( $description, ENT_QUOTES, 'UTF-8' );
        $item['description'] = wp_kses_post( $description );

        $options = $item['options'];

        // Primary button text
        if ( isset( $options['primary']['text'] ) ) {
            $options['primary']['text'] = sanitize_text_field( wp_unslash( $options['primary']['text'] ) );
        }

        // Title as link
        $options['is_title_linked'] = ! empty( $options['is_title_linked'] ) ? 1 : 0;

        // Price
        if ( isset( $options['price'] ) ) {
            $options['price'] = sanitize_text_field( wp_unslash( $options['price'] ) );
        }

        $item['options'] = maybe_serialize( $options );
        $item            = apply_filters( 'clickwhale_smart_display_data_before_save', $item );
        $id              = intval( $item['id'] );

        // Check if item exists and then update or insert
        // in some cases default check (not false and < 0) goes wrong
        if ( Smart_Displays_Helper::get_by_id( $id ) ) {
            $wpdb->update(
                    $table,
                    $item,
                    array( 'id' => $id )
            );
            $this->set_transient( $id, 'updated' );

        } else {
            unset( $item['id'] );
            $wpdb->insert(
                    $table,
                    $item
            );
            $id = $wpdb->insert_id;
            $this->set_transient( $id, 'added' );
        }

        $url = 'admin.php?page=' . CLICKWHALE_SLUG . '-edit-smart-display&id=' . $id;
        wp_redirect( esc_url_raw( admin_url( $url ) ) );
        exit;
    }

    public function admin_scripts(): void {
        ?>
        <script type='text/javascript'>
            /* global wp */

            jQuery(document).ready(function () {
                const
                    // IDs
                    descEditorTextareaID = 'smart-display-description',
                    previewPrimaryBtnID = 'cw-smart-display-preview--primary-button',

                    // Options, defaults
                    smartDisplaysOptions = <?php echo json_encode( get_option( 'clickwhale_smart_displays_options' ) ); ?>,
                    defaults = <?php echo json_encode( $this->get_defaults() ); ?>,

                    // Form
                    $form = jQuery('#smart-display-edit #form_edit_smart_display'),
                    $submit = $form.find('#submit'),
                    $imageUpload = $form.find('.cw-smart-display-image-upload'),
                    $imageRemove = $form.find('.cw-smart-display-image-remove'),
                    imageId = parseInt($form.find('#image').val(), 10),
                    $title = $form.find('#title'),
                    $titleAsLink = $form.find('#is-title-linked'),
                    $linksSelect = $form.find('#link_id'),
                    $price = $form.find('#price'),

                    // Primary Button
                    $primaryBtnText = $form.find('#primary_text'),

                    // TinyMCE
                    tinymceOptions = {
                        wpautop: true,
                        plugins: 'charmap colorpicker hr lists paste tabfocus textcolor fullscreen wordpress wpautoresize wpeditimage wpemoji wpgallery wplink wptextpattern',
                        toolbar1: 'bold, italic, underline, strikethrough, bullist, numlist, pastetext, removeformat, charmap, undo, redo',
                        toolbar2: '',
                        toolbar3: '',
                        toolbar4: '',
                        textarea_rows: 20
                    },
                    quicktagsOptions = {buttons: 'strong,em,link,block,del,ins,img,ul,ol,li,code,close'},

                    // Preview
                    $preview = jQuery('.cw-smart-display-preview'),
                    $previewImage = $preview.find('.cw-smart-display-preview--image'),
                    $previewTitle = $preview.find('.cw-smart-display-preview--title'),
                    $previewDesc = $preview.find('.cw-smart-display-preview--description'),
                    $previewPrice = $preview.find('.cw-smart-display-preview--price'),
                    $previewButtons = $preview.find('.cw-smart-display-preview--buttons')
                ;

                let
                    currentLinkData = <?php echo wp_json_encode( $this->get_current_link_data() ); ?>,
                    currentImageUrl = null;

                // Rename color picker `Clear` button
                setTimeout(() => {
                    jQuery('.wp-picker-clear')
                        .val('<?php echo esc_js( __( 'Reset', 'clickwhale' ) ); ?>')
                        .attr('aria-label', '<?php echo esc_js( __( 'Reset color', 'clickwhale' ) ); ?>');
                }, 0);

                /**
                 * Image, Title
                 */
                updatePreviewLinkableElements();

                /**
                 * Image
                 */
                if (imageId) {
                    wp.media.attachment(imageId).fetch().then(function (data) {
                        if (data && data.url) {
                            currentImageUrl = data.url;
                            showPreviewImage();
                        }
                    });
                } else {
                    hidePreviewImage();
                }

                $imageUpload.on('click', function (e) {
                    e.preventDefault();

                    const
                        button = jQuery(this),
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

                    uploader.on('select', function () {
                        const
                            attachment = uploader.state().get('selection').first().toJSON(),
                            url = typeof attachment.sizes.thumbnail !== 'undefined' ? attachment.sizes.thumbnail.url : attachment.url
                        ;

                        button
                            .removeClass('button')
                            .html('<img src="' + url + '" />')
                            .next().show();

                        mediaInput
                            .val(attachment.id)
                            .trigger('change');

                        currentImageUrl = attachment.url;
                        showPreviewImage();
                    });

                    uploader.open();
                });

                $imageRemove.on('click', function (e) {
                    e.preventDefault();

                    const
                        button = jQuery(this),
                        uploadBtnText = '<?php echo esc_js( __( 'Upload image', 'clickwhale' ) ); ?>';

                    button
                        .next().val(''); // emptying the hidden field

                    button
                        .hide()
                        .prev()
                        .addClass('button')
                        .html(uploadBtnText);

                    currentImageUrl = null;
                    hidePreviewImage();
                });

                /**
                 * Title
                 */
                $title.on('input', debounce(function () {
                    jQuery(this)
                        .removeClass('error')
                        .next().text('');

                    updatePreviewTitle();
                }));

                $titleAsLink.on('change', updatePreviewLinkableElements);

                /**
                 * Link Dropdown
                 */
                renderPreviewPrimaryButton();

                $linksSelect
                    .select2({
                        width: '100%',
                        minimumResultsForSearch: 1
                    })
                    .on('change', function () {
                        const link_id = jQuery(this).val();

                        if ('0' !== link_id) {
                            jQuery.post(ajaxurl, {
                                security: <?php echo wp_json_encode( wp_create_nonce( 'clickwhale_select_link' ) ); ?>,
                                action: 'clickwhale/admin/select_link',
                                id: link_id
                            }).done(function (response) {
                                if (response.success && response.data) {
                                    currentLinkData = response.data;
                                    renderPreviewPrimaryButton();
                                    updatePreviewLinkableElements();
                                } else {
                                    console.log('CW AJAX response error: ', response);
                                    onLinkReset();
                                }
                            }).fail(function (jqXHR, textStatus, errorThrown) {
                                console.log('CW AJAX fail: ', textStatus, errorThrown);
                                onLinkReset();
                            });
                        } else {
                            onLinkReset();
                        }
                    });

                /**
                 * Description. TinyMCE (wp.editor)
                 */
                wp.editor.initialize(descEditorTextareaID, {
                    tinymce: tinymceOptions,
                    quicktags: quicktagsOptions
                });

                const $descEditor = tinymce.get(descEditorTextareaID);

                $descEditor
                    .on('init', function () {
                        updatePreviewDesc();

                        // Unhide preview after editor is ready
                        $preview.css('opacity', 1);
                    })
                    .on('keyup change', debounce(updatePreviewDesc))
                ;

                jQuery(`#${descEditorTextareaID}`).on('input', debounce(updatePreviewDesc));

                /**
                 * Price
                 */
                $price.on('input', debounce(updatePreviewPrice));

                /**
                 * Primary Button Text
                 */
                $primaryBtnText.on('input', debounce(renderPreviewPrimaryButton));

                /**
                 * Submit
                 * 1. Check title (not null)
                 */
                $submit.on('click', function (e) {
                    if (!$title.val()) {
                        e.preventDefault();
                        jQuery('html, body').animate({scrollTop: 0}, 'fast');
                        $title
                            .addClass('error')
                            .next().text('<?php echo esc_js( __( 'Please enter title', 'clickwhale' ) ); ?>');
                        return;
                    }

                    $title
                        .removeClass('error')
                        .next().text('');
                });

                /**
                 * FUNCTIONS
                 */

                // Debounce function to limit the frequency of function calls
                // e.g. for handling user input events from color picker
                function debounce(func, delay = 300) {
                    let timer;
                    return function (...args) {
                        clearTimeout(timer);
                        timer = setTimeout(() => func.apply(this, args), delay);
                    };
                }

                function updatePreviewTitle() {
                    const titleText = $title.val();

                    if (!titleText) {
                        $previewTitle.text('');
                        return;
                    }

                    const
                        isLinked = $titleAsLink.is(':checked'),
                        linkUrl = currentLinkData?.source || ''
                    ;

                    if (isLinked && linkUrl) {
                        $previewTitle.html(`<a href="${linkUrl}" target="_blank">${titleText}</a>`);
                        return;
                    }

                    $previewTitle.text(titleText);
                }

                function updatePreviewLinkableElements() {
                    updatePreviewTitle();
                    showPreviewImage();
                }

                function showPreviewImage() {
                    if (!currentImageUrl) {
                        hidePreviewImage();
                        return;
                    }

                    const
                        img = `<img src="${currentImageUrl}" alt="preview-image" />`,
                        isLinked = $titleAsLink.is(':checked'),
                        linkUrl = currentLinkData?.source || ''
                    ;

                    $previewImage.removeClass('hidden');

                    if (isLinked && linkUrl) {
                        $previewImage.html(`<a href="${linkUrl}" target="_blank">${img}</a>`);
                        return;
                    }

                    $previewImage.html(img);
                }

                function hidePreviewImage() {
                    $previewImage
                        .addClass('hidden')
                        .empty();
                }

                function updatePreviewDesc() {
                    let content = '';

                    if ($descEditor && !$descEditor.isHidden()) {
                        // Ensure `Visual` (TinyMCE) and `Code` editor modes are synchronized
                        $descEditor.save();
                        content = $descEditor.getContent();
                    } else {
                        content = jQuery(`#${descEditorTextareaID}`).val();
                    }

                    if (content) {
                        $previewDesc.html(content);
                    } else {
                        $previewDesc.empty();
                    }
                }

                function updatePreviewPrice() {
                    const text = $price.val().trim();

                    if (!text) {
                        $previewPrice.text('');
                        return;
                    }

                    $previewPrice.text(text);
                }

                function renderPreviewPrimaryButton() {
                    if (!currentLinkData) {
                        removePreviewPrimaryButton();
                        return;
                    }

                    if ('0' === $linksSelect.val()) {
                        removePreviewPrimaryButton();
                        return;
                    }

                    const text = $primaryBtnText.val() || defaults.options.primary.text;

                    if (!text) {
                        removePreviewPrimaryButton();
                        return;
                    }

                    let $previewPrimaryButton = $previewButtons.find(`#${previewPrimaryBtnID}`);

                    if (!$previewPrimaryButton.length) {
                        $previewPrimaryButton = jQuery(`<a href="#" id="${previewPrimaryBtnID}"></a>`);
                        $previewButtons.append($previewPrimaryButton);
                    }

                    $previewPrimaryButton
                        .text(text)
                        .attr('href', currentLinkData.source)
                        .attr('target', '_blank')
                    ;

                    applyPreviewPrimaryButtonStyles();
                }

                function removePreviewPrimaryButton() {
                    $previewButtons.find(`#${previewPrimaryBtnID}`).remove();
                }

                function applyPreviewPrimaryButtonStyles() {
                    const
                        $btn = $previewButtons.find(`#${previewPrimaryBtnID}`);

                    if (!$btn.length) {
                        return;
                    }

                    const
                        $btnElem = $btn.get(0),
                        color = smartDisplaysOptions.primary.color,
                        colorHover = smartDisplaysOptions.primary.color_hover,
                        bgColor = smartDisplaysOptions.primary.bg_color,
                        bgColorHover = smartDisplaysOptions.primary.bg_color_hover,
                        borderWidth = smartDisplaysOptions.primary.border.width.value,
                        borderStyle = smartDisplaysOptions.primary.border.style,
                        borderRadius = smartDisplaysOptions.primary.border.radius.value,
                        borderColor = smartDisplaysOptions.primary.border.color,
                        borderColorHover = smartDisplaysOptions.primary.border.color_hover
                    ;

                    $btnElem.style.setProperty('--clickwhale-sd-preview-primary-color', color);
                    $btnElem.style.setProperty('--clickwhale-sd-preview-primary-bg-color', bgColor);
                    $btnElem.style.setProperty('--clickwhale-sd-preview-primary-border-width', borderWidth + 'px');
                    $btnElem.style.setProperty('--clickwhale-sd-preview-primary-border-style', borderStyle);
                    $btnElem.style.setProperty('--clickwhale-sd-preview-primary-border-radius', borderRadius + 'px');
                    $btnElem.style.setProperty('--clickwhale-sd-preview-primary-border-color', borderColor);

                    $btn
                        .off('mouseenter mouseleave')
                        .on('mouseenter', function () {
                            this.style.setProperty('--clickwhale-sd-preview-primary-color', colorHover);
                            this.style.setProperty('--clickwhale-sd-preview-primary-bg-color', bgColorHover);
                            this.style.setProperty('--clickwhale-sd-preview-primary-border-color', borderColorHover);
                        })
                        .on('mouseleave', function () {
                            this.style.setProperty('--clickwhale-sd-preview-primary-color', color);
                            this.style.setProperty('--clickwhale-sd-preview-primary-bg-color', bgColor);
                            this.style.setProperty('--clickwhale-sd-preview-primary-border-color', borderColor);
                        })
                    ;
                }

                function onLinkReset() {
                    currentLinkData = {};
                    removePreviewPrimaryButton();
                    updatePreviewLinkableElements();
                }
            });
        </script>
        <?php
    }
}