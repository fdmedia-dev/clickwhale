<?php
namespace clickwhale\includes\admin\export;

use clickwhale\includes\helpers\{Helper, Categories_Helper};

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Clickwhale_Export {

    public function __construct() {
        add_action( 'admin_init', array( $this, 'export_settings' ) );
        add_action( 'admin_print_footer_scripts', array( $this, 'admin_scripts' ) );
    }

    public function export_settings() {
        add_settings_section(
            'clickwhale_tools_export_section',
            esc_html__( 'Export links to a CSV file', 'clickwhale' ),
            array( $this, 'settings_section_callback' ),
            'clickwhale_tools_export_settings',
            array(
                'text' => esc_html__( 'This tool allows you to generate and download a CSV file containing a list of all links.', 'clickwhale' )
            )
        );

        add_settings_field(
            'export_columns',
            esc_html__( 'Filter columns', 'clickwhale' ),
            array( $this, 'export_columns_callback' ),
            'clickwhale_tools_export_settings',
            'clickwhale_tools_export_section'
        );

        add_settings_field(
            'export_categories',
            esc_html__( 'Filter categories', 'clickwhale' ),
            array( $this, 'export_categories_callback' ),
            'clickwhale_tools_export_settings',
            'clickwhale_tools_export_section'
        );

        register_setting(
            'clickwhale_tools_export_settings',
            'clickwhale_tools_export_settings',
            array( 'sanitize_callback' => '__return_empty_string' )
        );
    }

    public static function settings_section_callback( $args ) {
        ?>
        <p><?php echo esc_html( $args['text'] ); ?></p>
        <?php
    }

    public function export_columns_callback() {
        $select = '<select id="select_columns" class="clickwhale-select" multiple>';
        $select .= '<option selected value="0">' . esc_html__( 'Export all columns', 'clickwhale' ) . '</option>';
        foreach ( Helper::get_import_default_columns() as $option ) {
            $select .= sprintf(
                '<option value="%1$s">%2$s</option>',
                esc_attr( $option ),
                esc_html( $option )
            );
        }
        $select .= '</select>';

        echo $select;
    }

    public function export_categories_callback() {
        $categories = Categories_Helper::get_all( 'title', 'asc', 'ARRAY_A' );

        if ( $categories ) {
            $select = '<select id="select_categories" class="clickwhale-select" multiple>';
            $select .= '<option selected value="0">' . esc_html__( 'Export all categories', 'clickwhale' ) . '</option>';
            foreach ( $categories as $category ) {
                $select .= sprintf(
                    '<option value="%1$s">%2$s</option>',
                    esc_attr( $category['id'] ),
                    esc_html( $category['title'] )
                );
            }
            $select .= '</select>';

            echo $select;
        } else {
            esc_html_e( 'No categories', 'clickwhale' );
        }
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
                    selectColumns = jQuery('#select_columns'),
                    selectCategories = jQuery('#select_categories');

                selectColumns.select2({
                    placeholder: <?php echo wp_json_encode( __( 'Select columns you want to export', 'clickwhale' ) ); ?>
                });

                selectCategories.select2({
                    placeholder: <?php echo wp_json_encode( __( 'Select categories you want to export', 'clickwhale' ) ); ?>
                });

                jQuery('#clickwhale_tools_export select').on('select2:select', function(e) {
                    const
                        select = jQuery(this),
                        data = e.params.data,
                        selected = select.val();

                    if (data.id !== '0'){
                        selected.splice(selected.indexOf('0'), 1);
                        selected.push(data.id);

                        select.val(selected);
                        select.trigger('change');
                    } else {
                        select.val('0');
                        select.trigger('change');
                    }
                });

                jQuery('#export_form').on('submit', function(e){
                    e.preventDefault();

                    const
                        selectedColumns = selectColumns.val(),
                        selectedCategories = selectCategories.val();

                    let
                        columns = 'all',
                        categories = 'all';

                    if (selectedColumns.length > 0 && selectedColumns.indexOf('0') === -1){
                        columns = selectedColumns;
                    }

                    if (selectCategories.length > 0 && selectedCategories.indexOf('0') === -1){
                        categories = selectedCategories;
                    }

                    jQuery.post(ajaxurl, {
                        'security': <?php echo wp_json_encode( wp_create_nonce( 'export_csv' ) ); ?>,
                        'action': 'clickwhale/admin/export_csv',
                        'columns': columns,
                        'categories': categories
                    }, function(response){
                        if (response.success){
                            const
                                file = response.data.file,
                                filename = response.data.filename
                            // Make CSV in the response downloadable by creating a blob
                            const
                                download_link = document.createElement("a"),
                                fileData = ['\ufeff' + file],
                                blobObject = new Blob(fileData, {
                                    type: "text/csv;charset=utf-8;"
                                });

                            // Actually download the CSV by temporarily creating a link to it and simulating a click
                            download_link.href = URL.createObjectURL(blobObject);
                            download_link.download = filename;
                            document.body.appendChild(download_link);
                            download_link.click();
                            document.body.removeChild(download_link);
                        } else {
                            alert('Error code: ' + response.data[0].code + '. ' + response.data[0].message);
                        }
                    }).fail(function(xhr, textStatus, errorThrown){
                        alert(<?php echo wp_json_encode( __( 'An error occurred, try changing the request', 'clickwhale' ) ); ?>);
                    });
                });
            });
        </script>
        <?php
    }
}
