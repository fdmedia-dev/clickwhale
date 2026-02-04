<?php
namespace clickwhale\includes\admin\import;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Clickwhale_Import {

    public function __construct() {
        add_action( 'admin_init', array( $this, 'import_settings' ) );
        add_action( 'admin_print_footer_scripts', array( $this, 'admin_scripts' ) );
    }

    public function import_settings() {
        add_settings_section(
            'clickwhale_tools_import_section',
            esc_html__( 'Import links from a CSV file', 'clickwhale' ),
            array( $this, 'settings_section_callback' ),
            'clickwhale_tools_import_settings',
            array(
                'text' => $this->get_import_description_text()
            )
        );

        add_settings_field(
            'import_file',
            esc_html__( 'Choose a CSV file from your computer', 'clickwhale' ),
            array( $this, 'import_file_callback' ),
            'clickwhale_tools_import_settings',
            'clickwhale_tools_import_section'
        );

        register_setting(
            'clickwhale_tools_import_settings',
            'clickwhale_tools_import_settings',
            array( 'sanitize_callback' => '__return_empty_string' )
        );
    }

    public static function settings_section_callback( $args ) {
        echo '<p>' . wp_kses_post( $args['text'] ) . '</p>';
    }

    private function get_import_description_text(): string {
        $text = esc_html__( 'This tool allows you to import links to your site from a CSV file.', 'clickwhale' );
        $link = sprintf(
            '<a href="%1$s" rel="noopener">%2$s</a>',
            esc_url( CLICKWHALE_ADMIN_ASSETS_DIR ) . '/images/clickwhale-example-import.csv',
            esc_html__( 'Download Example CSV', 'clickwhale' )
        );

        return $text . ' ' . $link;
    }

    public function import_file_callback() {
        echo '<input type="file" id="import_file" name="import_file" accept=".csv" />';
    }

    public function admin_scripts() {
        if ( empty( $_GET['page'] ) ) {
            return;
        }

        if ( sanitize_key( $_GET['page'] ) !== CLICKWHALE_SLUG . '-tools' ) {
            return;
        }
        ?>
        <script type='text/javascript'>
            jQuery(document).ready(function(){
                const
                    progressWrap = jQuery('#import_progress'),
                    uploadForm = jQuery('#upload_form');

                let file, delimiter;

                jQuery('#import_file').on('change', function(){
                    const uploadedFile = jQuery(this)[0].files[0];

                    if (uploadedFile.type !== 'text/csv'){
                        jQuery(this).val('');
                        alert(<?php echo wp_json_encode( __( 'Please, select .csv file', 'clickwhale' ) ); ?>);
                    }
                });

                uploadForm.on('submit', function(e){
                    e.preventDefault();
                    file = jQuery('#import_file')[0].files[0];

                    if (file){
                        const formData = new FormData();
                        formData.set('action', 'clickwhale/admin/upload_csv');
                        formData.set('security', <?php echo wp_json_encode( wp_create_nonce( 'upload_csv' ) ); ?>);
                        formData.set('file', file);

                        jQuery.ajax({
                            url: ajaxurl,
                            method: "POST",
                            data: formData,
                            dataType: 'json',
                            contentType: false,
                            processData: false,
                            success: function(response){
                                if (response.success){
                                    progressWrap.find('.import-progress--bar').css('width', '50%');
                                    progressWrap.find('#point-02').addClass('active');
                                    jQuery(uploadForm).hide();

                                    delimiter = response.data.delimiter;
                                    jQuery('#mapping_table').prepend(response.data.table).show();
                                } else {
                                    handleError(response);
                                }
                            },
                            error: function(error){
                                console.log(error);
                            }
                        });
                    } else {
                        alert(<?php echo wp_json_encode( __( 'Please, select .csv file', 'clickwhale' ) ); ?>);
                    }
                });

                jQuery(document).on('click', '#mapping_button', function(){
                    const
                        formData = new FormData(),
                        mapped = [],
                        excluded = [];
                    let
                        error = false,
                        message;

                    jQuery('#mapping_table table tbody tr').each(function(index){
                        const
                            select = jQuery(this).find('select'),
                            selected = select.val();

                        select.attr('style', '');
                        select.parent().find('p').remove();

                        if (selected !== '0'){
                            if (!mapped.includes(selected)) {
                                mapped.push(selected);
                            } else {
                                error = true;
                                message = <?php echo wp_json_encode( esc_html__( 'Duplicated field', 'clickwhale' ) ); ?>;
                                select.css('border-color', 'red');
                                select.parent().append('<p style="margin: 3px 0 0; line-height: 1em; color: red;"><small>' + message + '</small></p>');
                            }
                        } else {
                            excluded.push(index);
                        }
                    });

                    if (error) {
                        return;
                    }

                    formData.set('action', 'clickwhale/admin/map_csv');
                    formData.set('security', <?php echo wp_json_encode( wp_create_nonce( 'map_csv' ) ); ?>);
                    formData.set('file', file);
                    formData.set('mapped', mapped);
                    formData.set('excluded', excluded);
                    formData.set('delimiter', delimiter);

                    jQuery.ajax({
                        url: ajaxurl,
                        method: "POST",
                        data: formData,
                        dataType: 'json',
                        contentType: false,
                        processData: false,
                        success: function(response){
                            if (response){
                                progressWrap.find('.import-progress--bar').css('width', '75%');
                                progressWrap.find('#point-03').addClass('active');
                                jQuery('#mapping_table').hide();
                                jQuery('#import_table').prepend(response.data).show();
                            } else {
                                handleError(response);
                            }
                        }
                    });
                });

                jQuery(document).on('click', '#import_table td button.remove', function(e){
                    e.preventDefault();

                    const remove = jQuery(this);
                    remove.closest('tr').css('background-color', 'red');
                    setTimeout(function(){
                        remove.closest('tr').remove();
                    }, 300);
                });

                jQuery(document).on('click', '#import_button', function(e){
                    e.preventDefault();

                    const
                        importTable = jQuery('#import_table table'),
                        importButton = jQuery("#import_button"),
                        importSpinner = jQuery('#import_table .spinner'),
                        importResult = jQuery('#import_result'),
                        importData = [], // rows for import
                        slugs = []; // array for slugs placed in the import file

                    let error = false;

                    importButton.prop('disabled', true);
                    importSpinner.addClass('is-active');

                    // Get slugs
                    jQuery.post(ajaxurl, {
                        'action': 'clickwhale/admin/check_slug_for_import',
                        'security': <?php echo wp_json_encode( wp_create_nonce( 'check_slug' ) ); ?>
                    }).done(function(data){
                        if (data.data){
                            // After we get all slugs we can proceed import
                            jQuery(importTable).find('tbody tr').each(function(){
                                const row = {};

                                jQuery(this).find('td.for_import').each(function(td){
                                    const key = jQuery(importTable).find('thead th').eq(td).text();
                                    let val = '';

                                    // Proceed cell by type
                                    switch (key){
                                        case 'slug':
                                            const slugInput = jQuery(this).find('input');
                                            let incorrectSlug;

                                            val = slugInput.val();
                                            incorrectSlug = data.data.find(s => s.slug === val);

                                            slugInput.attr('style', '');
                                            slugInput.parent().find('p').remove();

                                            if (!val){
                                                error = true;

                                                showErrorMessage(
                                                    slugInput,
                                                    <?php echo wp_json_encode( esc_html__( 'Required field', 'clickwhale' ) ); ?>
                                                );
                                                break;
                                            }

                                            // If checked slug currently exists
                                            if (typeof incorrectSlug !== 'undefined'){
                                                error = true;
                                                showErrorMessage(
                                                    slugInput,
                                                    <?php echo wp_json_encode( esc_html__( 'Slug already exists', 'clickwhale' ) ); ?>
                                                );
                                            }

                                            // If the slug has already been used in the imported file
                                            if (slugs.includes(val)){
                                                error = true;

                                                showErrorMessage(
                                                    slugInput,
                                                    <?php echo wp_json_encode( esc_html__( 'Slug is not unique', 'clickwhale' ) ); ?>
                                                );
                                                break;
                                            } else {
                                                // If not, then add it
                                                slugs.push(val);
                                            }
                                            break;

                                        case 'redirection':
                                            const selectRedirection = jQuery(this).find('select');
                                            val = selectRedirection ? selectRedirection.val() : 301;
                                            break;

                                        case 'link_target':
                                            const selectLinkTarget = jQuery(this).find('select');
                                            val = selectLinkTarget ? selectLinkTarget.val() : 'blank';
                                            break;

                                        case 'nofollow':
                                        case 'sponsored':
                                            const checkbox = jQuery(this).find('input[type="checkbox"]');
                                            val = ( checkbox && checkbox.is(':checked') ) ? 1 : 0;
                                            break;

                                        default:
                                            const input = jQuery(this).find('input');

                                            if (input.length === 0){
                                                break;
                                            }

                                            val = input.val();
                                            input.attr('style', '');
                                            input.parent().find('p').remove();

                                            if (!val){
                                                error = true;
                                                showErrorMessage(
                                                    input,
                                                    <?php echo wp_json_encode( esc_html__( 'Required field', 'clickwhale' ) ); ?>
                                                );
                                                break;
                                            }
                                    }

                                    if (key){
                                        row[key] = val;
                                    }
                                });

                                importData.push(row);
                            });

                            setTimeout(function(){
                                importButton.prop('disabled', false);
                                importSpinner.removeClass('is-active');
                            }, 500);

                            if (!error){
                                jQuery.post(ajaxurl, {
                                    'action': 'clickwhale/admin/import_csv',
                                    'security': <?php echo wp_json_encode( wp_create_nonce( 'import_csv' ) ); ?>,
                                    'data': importData,
                                }, function(response){
                                    if (response.data){
                                        const data = response.data;

                                        progressWrap.find('.import-progress--bar').css('width', '100%');
                                        progressWrap.find('#point-04').addClass('active');
                                        jQuery('#import_table').hide();

                                        importResult.show();

                                        for (let item in data){
                                            importResult.prepend('<p>' + data[item] + '</p>');
                                        }

                                        importResult.find('a').show();
                                    }
                                });
                            }
                        } else {
                            error = true;
                            alert(<?php echo wp_json_encode( __( 'No items', 'clickwhale' ) ); ?>);
                        }
                    }).fail(function(data){
                        error = true;
                        console.log('fail', data);
                    });
                });

                function handleError(response){
                    alert('Error code: ' + response.data[0].code + '. ' + response.data[0].message);
                }

                function showErrorMessage(field, message){
                    field.css('border-color', 'red');
                    field.parent().append('<p style="margin: 3px 0 0; line-height: 1em; color: red;"><small>' + message + '</small></p>');
                }
            });
        </script>
        <?php
    }
}
