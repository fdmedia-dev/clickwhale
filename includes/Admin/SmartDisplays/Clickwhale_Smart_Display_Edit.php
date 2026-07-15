<?php

namespace Clickwhale\Admin\SmartDisplays;

use Clickwhale\Admin\Clickwhale_Instance_Edit;
use Clickwhale\Helpers\{
        Helper,
        Integrations_Helper,
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
                'name'        => '',
                'title'       => '',
                'image'       => 0,
                'link_id'     => '',
                'description' => '',
                'disclosure'  => esc_textarea( wp_unslash( $smart_displays_options['disclosure'] ?? $smart_displays_defaults['disclosure'] ) ),
                'options'     => array(
                        'is_title_linked' => 0,
                        'primary'         => array(
                                'text' => ''
                        )
                ),
                'created_at'  => ''
        );
    }

    public function save_update() {
        global $wpdb;
        $table      = Helper::get_db_table_name( $this->instance_plural );
        $data_table = Helper::get_db_table_name( 'smart_display_data' );
        $item       = array_intersect_key(
                $_POST,
                apply_filters( 'clickwhale_smart_display_defaults', $this->get_defaults() )
        );

        // Name
        $item['name'] = sanitize_text_field( wp_unslash( $item['name'] ?? '' ) );

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

        // Image URL and Link URL (populated from integrations like AMZ Connect)
        if ( isset( $options['image_url'] ) ) {
            $options['image_url'] = esc_url_raw( wp_unslash( $options['image_url'] ) );
        }
        if ( isset( $options['link_url'] ) ) {
            $options['link_url'] = esc_url_raw( wp_unslash( $options['link_url'] ) );
        }

        // If ASIN removed from a previously Set display — clear all AMZ-populated fields, keep only name
        $submitted_asin = sanitize_text_field( wp_unslash( $_POST['sd_integrations']['amz_connect']['asin'] ?? '' ) );
        $current_id     = intval( $item['id'] ?? 0 );
        if ( $current_id && empty( $submitted_asin ) ) {
            $existing_amz = Smart_Displays_Helper::get_integration_data( $current_id, 'amz_connect' );
            if ( ! empty( $existing_amz['is_active'] ) ) {
                $item['title']       = '';
                $item['description'] = '';
                $item['image']       = 0;
                $options['image_url'] = '';
                $options['link_url']  = '';
                $options['price']     = '';
            }
        }

        // Handle original data snapshot (kept while AMZ is active, removed when reverting)
        $is_amz_set        = ! empty( $_POST['sd_integrations']['amz_connect']['is_set'] );
        $original_json_raw = wp_unslash( $_POST['sd_original_json'] ?? '' );
        if ( $is_amz_set && ! empty( $original_json_raw ) ) {
            $original_decoded = json_decode( $original_json_raw, true );
            if ( is_array( $original_decoded ) ) {
                $options['original'] = $original_decoded;
            }
        } else {
            unset( $options['original'] );
        }

        // Override flags — only persist when AMZ is active
        if ( $is_amz_set ) {
            $options['override']['title']       = ! empty( $_POST['sd_override']['title'] );
            $options['override']['description'] = ! empty( $_POST['sd_override']['description'] );
        } else {
            unset( $options['override'] );
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

        $this->save_integration_data( $id );

        $url = 'admin.php?page=' . CLICKWHALE_SLUG . '-edit-smart-display&id=' . $id;
        wp_redirect( esc_url_raw( admin_url( $url ) ) );
        exit;
    }

    private function save_integration_data( int $sd_id ): void {
        $sd_integrations = $_POST['sd_integrations'] ?? array();

        if ( empty( $sd_integrations ) || ! is_array( $sd_integrations ) ) {
            return;
        }

        foreach ( $sd_integrations as $integration => $data ) {
            $integration = sanitize_key( $integration );
            $asin        = sanitize_text_field( wp_unslash( $data['asin'] ?? '' ) );
            $store       = sanitize_text_field( wp_unslash( $data['store'] ?? 'com' ) );
            $is_set      = ! empty( $data['is_set'] );

            if ( empty( $asin ) ) {
                $this->delete_integration_row( $sd_id, $integration );
                continue;
            }

            if ( ! $is_set ) {
                $this->deactivate_integration_row( $sd_id, $integration );
                continue;
            }

            $params        = array( 'asin' => $asin, 'store' => $store );
            $raw_data_post = wp_unslash( $data['raw_data'] ?? '' );
            $raw_data      = ( ! empty( $raw_data_post ) && null !== json_decode( $raw_data_post ) )
                    ? $raw_data_post
                    : $this->fetch_integration_raw_data( $integration, $params );

            $this->upsert_integration_row( $sd_id, $integration, $params, $raw_data, 1 );
        }
    }

    private function fetch_integration_raw_data( string $integration, array $params ): ?string {
        $api_key = Integrations_Helper::get_integration_api_key( $integration );

        if ( empty( $api_key ) ) {
            return null;
        }

        switch ( $integration ) {
            case 'amz_connect':
                $result = Integrations_Helper::amz_connect_get_product( $api_key, $params['asin'], $params['store'] );

                return ! is_wp_error( $result ) ? wp_json_encode( $result ) : null;
        }

        return null;
    }

    private function upsert_integration_row( int $sd_id, string $integration, array $params, ?string $raw_data, int $is_active ): void {
        global $wpdb;
        $table    = Helper::get_db_table_name( 'smart_display_data' );
        $existing = Smart_Displays_Helper::get_integration_data( $sd_id, $integration );

        $data = array(
                'is_active'  => $is_active,
                'params'     => wp_json_encode( $params ),
                'raw_data'   => $raw_data,
                'updated_at' => current_time( 'mysql' ),
        );

        if ( ! empty( $existing['id'] ) ) {
            $wpdb->update( $table, $data, array( 'id' => $existing['id'] ) );
        } else {
            $data['smart_display_id'] = $sd_id;
            $data['integration']      = $integration;
            $data['created_at']       = current_time( 'mysql' );
            $wpdb->insert( $table, $data );
        }
    }

    private function deactivate_integration_row( int $sd_id, string $integration ): void {
        global $wpdb;
        $table    = Helper::get_db_table_name( 'smart_display_data' );
        $existing = Smart_Displays_Helper::get_integration_data( $sd_id, $integration );

        if ( ! empty( $existing['id'] ) ) {
            $wpdb->update(
                    $table,
                    array( 'is_active' => 0, 'updated_at' => current_time( 'mysql' ) ),
                    array( 'id' => $existing['id'] )
            );
        }
    }

    private function delete_integration_row( int $sd_id, string $integration ): void {
        global $wpdb;
        $table = Helper::get_db_table_name( 'smart_display_data' );
        $wpdb->delete( $table, array( 'smart_display_id' => $sd_id, 'integration' => $integration ) );
    }

    public function admin_scripts(): void {
        $integrations_options = get_option( 'clickwhale_integrations_options', array() );
        $amz_tracking_id      = $integrations_options['amz_connect']['fields']['tracking_id'] ?? '';
        $amz_button_text      = ! empty( $integrations_options['amz_connect']['fields']['button_text'] )
                ? $integrations_options['amz_connect']['fields']['button_text']
                : 'Buy on Amazon';
        $item_id              = intval( $_GET['id'] ?? 0 );
        $sd_amz_data          = $item_id ? Smart_Displays_Helper::get_integration_data( $item_id, 'amz_connect' ) : array();
        $sd_amz_is_set        = ! empty( $sd_amz_data['is_active'] );
        $sd_item              = $item_id ? Smart_Displays_Helper::get_by_id( $item_id ) : array();
        $sd_options           = maybe_unserialize( $sd_item['options'] ?? '' ) ?: array();
        $sd_override_title    = ! empty( $sd_options['override']['title'] );
        $sd_override_desc     = ! empty( $sd_options['override']['description'] );
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

                const
                    amzStores = <?php echo wp_json_encode( Integrations_Helper::get_amz_connect_stores() ); ?>,
                    amzTrackingId = '<?php echo esc_js( $amz_tracking_id ); ?>',
                    amzButtonText = '<?php echo esc_js( $amz_button_text ); ?>',
                    amzIsSet = <?php echo wp_json_encode( $sd_amz_is_set ); ?>,
                    amzMaxListItems = <?php echo intval( $integrations_options['amz_connect']['fields']['max_list_items'] ?? 3 ); ?>,
                    amzOverflowBehavior = '<?php echo esc_js( $integrations_options['amz_connect']['fields']['overflow_behavior'] ?? 'truncate' ); ?>'
                ;

                let
                    currentLinkData = <?php echo wp_json_encode( $this->get_current_link_data() ); ?>,
                    currentImageUrl = null,
                    amzIsSetActive = amzIsSet,
                    amzOverrideTitle = <?php echo wp_json_encode( $sd_override_title ); ?>,
                    amzOverrideDesc = <?php echo wp_json_encode( $sd_override_desc ); ?>,
                    originalData = {};

                clickwhaleUnsavedChanges.track($form);

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
                        clickwhaleUnsavedChanges.markDirty();
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

                    clickwhaleUnsavedChanges.markDirty();

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
                        updatePreviewWrapVisibility();

                        // Init fetch button state based on ASIN validity
                        const initAsin = jQuery('#amz-asin').val() || '';
                        jQuery('#amz-fetch-btn').prop('disabled', !/^[A-Z0-9]{10}$/i.test(initAsin.trim()));

                        // Load saved original data snapshot (present when AMZ is active)
                        const originalJsonEl = jQuery('#sd-original-json');
                        if (originalJsonEl.val()) {
                            try {
                                const parsed = JSON.parse(originalJsonEl.val());
                                if (parsed && typeof parsed === 'object') {
                                    originalData = parsed;
                                }
                            } catch (e) {}
                        }

                        const rawData = $form.find('#amz-raw-data').val();
                        if (rawData && amzIsSet) {
                            try {
                                fillFieldsFromAmz(
                                    JSON.parse(rawData),
                                    $form.find('#amz-asin').val().trim(),
                                    $form.find('#amz-store').val()
                                );
                            } catch (e) {}
                            setTableDisabled(true);
                            amzIsSetActive = true;
                            $primaryBtnText.attr('placeholder', amzButtonText);
                            renderPreviewPrimaryButton();
                            showAmzToggle(true);
                        } else if (amzIsSet) {
                            // AMZ key removed but display was set — restore from saved hidden inputs
                            const savedLinkUrl = jQuery('#amz-link-url').val();
                            if (savedLinkUrl) {
                                currentLinkData = {source: savedLinkUrl};
                                renderPreviewPrimaryButton();
                            }
                            updatePreviewLinkableElements();
                            setTableDisabled(true);
                            showAmzToggle(true);
                        }
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

                    $title.removeClass('error').next().text('');

                    // Re-enable disabled fields so they submit with the form
                    if (amzIsSetActive) {
                        $form.find('#sd-table').find('input, select, textarea').not('#sd-name').prop('disabled', false);
                        const editorSubmit = tinymce.get(descEditorTextareaID);
                        if (editorSubmit) {
                            editorSubmit.setMode('design');
                            editorSubmit.save();
                        }
                    }

                    clickwhaleUnsavedChanges.markClean();
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

                function hasPreviewData() {
                    if ($title.val().trim()) return true;
                    if (currentImageUrl || jQuery('#amz-image-url').val()) return true;
                    if ($price.val().trim()) return true;
                    const editor = tinymce.get(descEditorTextareaID);
                    const desc = editor && !editor.isHidden() ? editor.getContent() : jQuery(`#${descEditorTextareaID}`).val();
                    return !!(desc && desc.trim());
                }

                function updatePreviewWrapVisibility() {
                    const hasData = hasPreviewData();
                    if (hasData) {
                        jQuery('.cw-smart-display-preview--wrap').show();
                        jQuery('#cw-preview-placeholder').hide();
                    } else {
                        jQuery('.cw-smart-display-preview--wrap').hide();
                        jQuery('#cw-preview-placeholder').show();
                    }
                }

                function updatePreviewTitle() {
                    const titleText = $title.val();

                    if (!titleText) {
                        $previewTitle.text('');
                        updatePreviewWrapVisibility();
                        return;
                    }

                    const linkUrl = currentLinkData?.source || '';

                    if (linkUrl) {
                        $previewTitle.html(`<a href="${linkUrl}" target="_blank">${titleText}</a>`);
                    } else {
                        $previewTitle.text(titleText);
                    }
                    updatePreviewWrapVisibility();
                }

                function updatePreviewLinkableElements() {
                    updatePreviewTitle();
                    showPreviewImage();
                }

                function showPreviewImage() {
                    const imgUrl = currentImageUrl || jQuery('#amz-image-url').val() || '';

                    if (!imgUrl) {
                        hidePreviewImage();
                        return;
                    }

                    const
                        img = `<img src="${imgUrl}" alt="preview-image" />`,
                        linkUrl = currentLinkData?.source || ''
                    ;

                    $previewImage.removeClass('hidden');

                    if (linkUrl) {
                        $previewImage.html(`<a href="${linkUrl}" target="_blank">${img}</a>`);
                    } else {
                        $previewImage.html(img);
                    }
                    updatePreviewWrapVisibility();
                }

                function hidePreviewImage() {
                    $previewImage
                        .addClass('hidden')
                        .empty();
                    updatePreviewWrapVisibility();
                }

                function applyDescListLimit(html) {
                    if (amzOverrideDesc || amzMaxListItems <= 0 || !html) return html;

                    const $tmp = jQuery('<div>').html(html);
                    const $ul = $tmp.find('ul').first();
                    if (!$ul.length) return html;

                    const $items = $ul.children('li');
                    if ($items.length <= amzMaxListItems) return html;

                    const $visible = $items.slice(0, amzMaxListItems);
                    const $excess  = $items.slice(amzMaxListItems);

                    $ul.empty().append($visible);

                    if (amzOverflowBehavior === 'show_more') {
                        const $details  = jQuery('<details class="cw-sd-show-more">');
                        const $summary  = jQuery('<summary>').html(
                            '<span class="cw-show-more-label"><?php echo esc_js( __( 'Show more', 'clickwhale' ) ); ?></span>' +
                            '<span class="cw-show-less-label"><?php echo esc_js( __( 'Show less', 'clickwhale' ) ); ?></span>'
                        );
                        const $excessUl = jQuery('<ul>').append($excess);
                        $details.append($summary, $excessUl);
                        $tmp.append($details);
                    }

                    return $tmp.html();
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
                        $previewDesc.html(applyDescListLimit(content));
                    } else {
                        $previewDesc.empty();
                    }
                    updatePreviewWrapVisibility();
                }

                function updatePreviewPrice() {
                    const text = $price.val().trim();

                    if (!text) {
                        $previewPrice.text('');
                        updatePreviewWrapVisibility();
                        return;
                    }

                    $previewPrice.text(text);
                    updatePreviewWrapVisibility();
                }

                function renderPreviewPrimaryButton() {
                    const linkUrl = currentLinkData?.source || '';

                    if (!linkUrl) {
                        removePreviewPrimaryButton();
                        return;
                    }

                    const text = $primaryBtnText.val() || (amzIsSetActive ? amzButtonText : smartDisplaysOptions?.primary?.text);

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
                        .attr('href', linkUrl)
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

                function setTableDisabled(disabled) {
                    $form.find('#sd-table').find('input, select, textarea, button, a.button')
                        .not('#sd-name, #sd-override-title, #sd-override-description, #primary_text')
                        .prop('disabled', disabled);
                    $form.find('.cw-smart-display-image-upload, .cw-smart-display-image-remove').css({
                        'pointer-events': disabled ? 'none' : '',
                        'opacity': disabled ? 0.5 : ''
                    });
                    const editor = tinymce.get(descEditorTextareaID);
                    if (editor) {
                        editor.setMode(disabled ? 'readonly' : 'design');
                    }
                    if (disabled) {
                        applyOverrideFieldStates();
                    }
                }

                function applyOverrideFieldStates() {
                    if (amzOverrideTitle) {
                        $form.find('#title').prop('disabled', false);
                    }
                    if (amzOverrideDesc) {
                        $form.find('#smart-display-description').prop('disabled', false);
                        jQuery('#wp-' + descEditorTextareaID + '-wrap').find('button, a.button, input[type="button"]').prop('disabled', false);
                        const editor = tinymce.get(descEditorTextareaID);
                        if (editor) {
                            editor.setMode('design');
                        }
                    }
                }

                function captureOriginalData() {
                    if (Object.keys(originalData).length > 0) return;
                    const editor = tinymce.get(descEditorTextareaID);
                    const desc = editor && !editor.isHidden() ? editor.getContent() : jQuery(`#${descEditorTextareaID}`).val();
                    originalData = {
                        title: $title.val(),
                        description: desc,
                        price: $price.val(),
                        imageId: $form.find('#image').val(),
                        imageUrl: currentImageUrl || '',
                        linkId: $linksSelect.val() || '0',
                        primaryText: $primaryBtnText.val()
                    };
                    jQuery('#sd-original-json').val(JSON.stringify(originalData));
                }

                function restoreOriginalData() {
                    if (!Object.keys(originalData).length) return;

                    $title.val(originalData.title || '').removeClass('error').next().text('');
                    updatePreviewTitle();

                    const editorRestore = tinymce.get(descEditorTextareaID);
                    if (editorRestore && !editorRestore.isHidden()) {
                        editorRestore.setContent(originalData.description || '');
                        editorRestore.save();
                    } else {
                        jQuery(`#${descEditorTextareaID}`).val(originalData.description || '');
                    }
                    updatePreviewDesc();

                    $price.val(originalData.price || '');
                    updatePreviewPrice();

                    currentImageUrl = originalData.imageUrl || null;
                    $form.find('#image').val(originalData.imageId || '');
                    if (originalData.imageId && originalData.imageUrl) {
                        $imageUpload.removeClass('button').html(`<img src="${originalData.imageUrl}" />`);
                        $imageRemove.show();
                    } else {
                        $imageRemove.hide();
                        $imageUpload.addClass('button').html('<?php echo esc_js( __( 'Upload image', 'clickwhale' ) ); ?>');
                    }
                    showPreviewImage();

                    $linksSelect.val(originalData.linkId || '0').trigger('change.select2');
                    $primaryBtnText.val(originalData.primaryText || '');

                    jQuery('#amz-image-url').val('');
                    jQuery('#amz-link-url').val('');
                    jQuery('#amz-link-url-row').hide();

                    originalData = {};
                    jQuery('#sd-original-json').val('');
                }

                function fillFieldsFromAmz(product, asin, store, fromFetch = false) {
                    const
                        domain = (amzStores[store] && amzStores[store].domain) ? amzStores[store].domain : `amazon.${store}`,
                        tag = amzTrackingId ? `?tag=${amzTrackingId}` : '',
                        amazonUrl = `https://${domain}/dp/${asin}${tag}`
                    ;

                    if (product.title && !amzOverrideTitle) {
                        $title.val(product.title).removeClass('error').next().text('');
                        updatePreviewTitle();
                    }

                    if (!amzOverrideDesc) {
                        let descHtml = '';
                        if (product.feature_bullets && product.feature_bullets.length) {
                            const lis = product.feature_bullets.map(b => `<li>${b}</li>`).join('');
                            descHtml = `<ul>${lis}</ul>`;
                        }
                        const editorFill = tinymce.get(descEditorTextareaID);
                        if (editorFill && !editorFill.isHidden()) {
                            editorFill.setContent(descHtml);
                            editorFill.save();
                        } else {
                            jQuery(`#${descEditorTextareaID}`).val(descHtml);
                        }
                        updatePreviewDesc();
                    }

                    const priceRaw = product.buybox_winner?.price?.raw || product.buybox_winner?.rrp?.raw || '';
                    $price.val(priceRaw);
                    updatePreviewPrice();

                    currentImageUrl = null;
                    $form.find('#image').val('');
                    $imageRemove.hide();
                    $imageUpload.addClass('button').html('<?php echo esc_js( __( 'Upload image', 'clickwhale' ) ); ?>');
                    jQuery('#amz-image-url').val(product.main_image?.link || '');
                    showPreviewImage();

                    currentLinkData = {source: amazonUrl};
                    jQuery('#amz-link-url').val(amazonUrl);
                    showLinkUrlRow(amazonUrl);

                    if (fromFetch) {
                        $primaryBtnText.val(amzButtonText);
                    }
                    $linksSelect.val('0').trigger('change.select2');
                    renderPreviewPrimaryButton();

                    updatePreviewWrapVisibility();
                }

                function applyAmzMode(enable, fromFetch = false) {
                    if (enable) {
                        captureOriginalData();
                        const rawData = $form.find('#amz-raw-data').val();
                        if (!rawData) return;
                        try {
                            fillFieldsFromAmz(
                                JSON.parse(rawData),
                                $form.find('#amz-asin').val().trim(),
                                $form.find('#amz-store').val(),
                                fromFetch
                            );
                        } catch (e) {
                            console.error('AMZ apply failed:', e);
                            return;
                        }
                        setTableDisabled(true);
                        amzIsSetActive = true;
                        jQuery('#amz-is-set').val('1');
                        jQuery('#amz-use-toggle').prop('checked', true);
                        jQuery('#sd-override-title-row, #sd-override-description-row').show();
                        $primaryBtnText.attr('placeholder', amzButtonText);
                        renderPreviewPrimaryButton();
                    } else {
                        restoreOriginalData();
                        setTableDisabled(false);
                        amzIsSetActive = false;
                        jQuery('#amz-is-set').val('0');
                        jQuery('#amz-use-toggle').prop('checked', false);
                        $primaryBtnText.attr('placeholder', smartDisplaysOptions?.primary?.text || '');
                        amzOverrideTitle = false;
                        amzOverrideDesc = false;
                        jQuery('#sd-override-title').prop('checked', false);
                        jQuery('#sd-override-description').prop('checked', false);
                        jQuery('#sd-override-title-row, #sd-override-description-row').hide();
                    }
                }

                function showAmzToggle(show) {
                    jQuery('#amz-use-toggle-row').toggle(show);
                }

                function showLinkUrlRow(url) {
                    jQuery('#amz-link-url-display').val(url);
                    jQuery('#amz-link-url-row').show();
                }

                function setStatus(status, element) {
                    switch (status) {
                        case 'fetching':
                            element.removeClass('connected disconnected').addClass('fetching');
                            break;
                        case 'connected':
                            element.removeClass('fetching disconnected').addClass('connected');
                            break;
                        case 'disconnected':
                            element.removeClass('fetching connected').addClass('disconnected');
                            break;
                    }
                }

                /**
                 * AMZ Connect
                 */
                jQuery('#amz-use-toggle').on('change', function () {
                    applyAmzMode(jQuery(this).is(':checked'));
                });

                jQuery('#amz-asin').on('change input', function () {
                    const isValidAsin = /^[A-Z0-9]{10}$/i.test(jQuery(this).val().trim());
                    jQuery('#amz-fetch-btn').prop('disabled', !isValidAsin);
                });

                jQuery('#amz-asin, #amz-store').on('change input', function () {
                    if (amzIsSetActive) {
                        applyAmzMode(false);
                    }
                    jQuery('#amz-raw-data').val('');
                    showAmzToggle(false);
                    jQuery('#amz-link-url-row').hide();
                });

                jQuery('#sd-override-title').on('change', function () {
                    amzOverrideTitle = this.checked;
                    if (!amzIsSetActive) return;
                    if (amzOverrideTitle) {
                        $form.find('#title').prop('disabled', false);
                    } else {
                        const rawData = $form.find('#amz-raw-data').val();
                        if (rawData) {
                            try {
                                const product = JSON.parse(rawData);
                                if (product.title) {
                                    $title.val(product.title).removeClass('error').next().text('');
                                    updatePreviewTitle();
                                }
                            } catch (e) {}
                        }
                        $form.find('#title').prop('disabled', true);
                    }
                });

                jQuery('#sd-override-description').on('change', function () {
                    amzOverrideDesc = this.checked;
                    if (!amzIsSetActive) return;
                    if (amzOverrideDesc) {
                        $form.find('#smart-display-description').prop('disabled', false);
                        jQuery('#wp-' + descEditorTextareaID + '-wrap').find('button, a.button, input[type="button"]').prop('disabled', false);
                        const editor = tinymce.get(descEditorTextareaID);
                        if (editor) editor.setMode('design');
                        updatePreviewDesc();
                    } else {
                        const rawData = $form.find('#amz-raw-data').val();
                        if (rawData) {
                            try {
                                const product = JSON.parse(rawData);
                                let descHtml = '';
                                if (product.feature_bullets && product.feature_bullets.length) {
                                    const lis = product.feature_bullets.map(b => `<li>${b}</li>`).join('');
                                    descHtml = `<ul>${lis}</ul>`;
                                }
                                const editorRestore = tinymce.get(descEditorTextareaID);
                                if (editorRestore && !editorRestore.isHidden()) {
                                    editorRestore.setContent(descHtml);
                                    editorRestore.save();
                                } else {
                                    jQuery(`#${descEditorTextareaID}`).val(descHtml);
                                }
                                updatePreviewDesc();
                            } catch (e) {}
                        }
                        $form.find('#smart-display-description').prop('disabled', true);
                        jQuery('#wp-' + descEditorTextareaID + '-wrap').find('button, a.button, input[type="button"]').prop('disabled', true);
                        const editor = tinymce.get(descEditorTextareaID);
                        if (editor) editor.setMode('readonly');
                    }
                });

                jQuery('#amz-fetch-btn').on('click', function () {
                    const
                        asin = jQuery('#amz-asin').val().trim(),
                        store = jQuery('#amz-store').val(),
                        $btn = jQuery(this),
                        $status = jQuery('#amz-fetch-status'),
                        $rawData = jQuery('#amz-raw-data')
                    ;

                    if (!asin) {
                        $status.text('<?php echo esc_js( __( 'Please enter an ASIN', 'clickwhale' ) ); ?>');
                        setStatus('disconnected', $status);
                        return;
                    }

                    $btn.prop('disabled', true);
                    $status.text('<?php echo esc_js( __( 'Fetching', 'clickwhale' ) ); ?>');
                    setStatus('fetching', $status);
                    $rawData.val('');

                    jQuery.post(ajaxurl, {
                        action: 'clickwhale/admin/amz_connect_get_product',
                        asin: asin,
                        store: store,
                        security: clickwhale_admin.nonce_amz_connect
                    })
                        .done(function (response) {
                            if (response.success) {
                                $status.text('<?php echo esc_js( __( 'Done', 'clickwhale' ) ); ?>');
                                setStatus('connected', $status);
                                $rawData.val(JSON.stringify(response.data));
                                applyAmzMode(true, true);
                                showAmzToggle(true);
                            } else {
                                $status.text(response.data?.message || '<?php echo esc_js( __( 'Error', 'clickwhale' ) ); ?>');
                                setStatus('disconnected', $status);
                            }
                        })
                        .fail(function () {
                            $status.text('<?php echo esc_js( __( 'Request failed', 'clickwhale' ) ); ?>');
                            setStatus('disconnected', $status);
                        })
                        .always(function () {
                            $btn.prop('disabled', false);
                        });
                });
            });
        </script>
        <?php
    }
}