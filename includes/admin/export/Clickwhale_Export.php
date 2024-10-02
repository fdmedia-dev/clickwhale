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
			__( 'Export links to a CSV file', CLICKWHALE_NAME ),
			array( $this, 'settings_section_callback' ),
			'clickwhale_tools_export_settings',
			array(
				'text' => __(
					'This tool allows you to generate and download a CSV file containing a list of all lists.', CLICKWHALE_NAME ),
			)
		);

		add_settings_field(
			'export_columns',
			__( 'Filter columns', CLICKWHALE_NAME ),
			array( $this, 'export_columns_callback' ),
			'clickwhale_tools_export_settings',
			'clickwhale_tools_export_section'
		);

		add_settings_field(
			'export_categories',
			__( 'Filter categories', CLICKWHALE_NAME ),
			array( $this, 'export_categories_callback' ),
			'clickwhale_tools_export_settings',
			'clickwhale_tools_export_section'
		);

		register_setting(
			'clickwhale_tools_export_settings',
			'clickwhale_tools_export_settings'
		);
	}

	public function export_columns_callback() {
		$select = '<select id="select_columns" class="clickwhale-select" multiple>';
		$select .= '<option selected value="0">' . __( 'Export all columns', CLICKWHALE_NAME ) . '</option>';
		foreach ( Helper::get_import_default_columns() as $option ) {
			$select .= '<option value="' . $option . '">' . $option . '</option>';
		}
		$select .= '</select>';

		echo $select;
	}

	public function export_categories_callback() {
		$categories = Categories_Helper::get_all( 'title', 'asc', 'ARRAY_A' );

		if ( $categories ) {
			$select = '<select id="select_categories" class="clickwhale-select" multiple>';
			$select .= '<option selected value="0">' . __( 'Export all categories', CLICKWHALE_NAME ) . '</option>';
			foreach ( $categories as $category ) {
				$select .= '<option value="' . $category['id'] . '">' . $category['title'] . '</option>';
			}
			$select .= '</select>';

			echo $select;
		} else {
			_e( 'No categories', CLICKWHALE_NAME );
		}
	}

	public static function settings_section_callback( $args ) {
		echo '<p>' . $args['text'] . '</p>';
	}

	public function admin_scripts() {
        if ( empty( $_GET['page'] ) ) {
            return;
        }

        if ( $_GET['page'] !== CLICKWHALE_SLUG . '-tools' ) {
			return;
		}

		$nonce_export_csv = wp_create_nonce( 'export_csv' );
		?>

        <script type='text/javascript'>
            jQuery(document).ready(function() {
                const
                    selectColumns = jQuery('#select_columns'),
                    selectCategories = jQuery('#select_categories');

                selectColumns.select2({
                    placeholder: "Select columns you want to export"
                });

                selectCategories.select2({
                    placeholder: "Select categories you want to export"
                });

                jQuery('#clickwhale_tools_export select').on('select2:select', function(e) {
                    const
                        select = jQuery(this),
                        data = e.params.data,
                        selected = select.val();

                    if (data.id !== '0') {
                        selected.splice(selected.indexOf('0'), 1);
                        selected.push(data.id);

                        select.val(selected);
                        select.trigger('change');
                    } else {
                        select.val('0');
                        select.trigger('change');
                    }
                });

                jQuery('#export_form').on('submit', function(e) {
                    e.preventDefault();

                    const
                        selectedColumns = selectColumns.val(),
                        selectedCategories = selectCategories.val();

                    let
                        columns = 'all',
                        categories = 'all';

                    if (selectedColumns.length > 0 && selectedColumns.indexOf('0') === -1) {
                        columns = selectedColumns;
                    }

                    if (selectCategories.length > 0 && selectedCategories.indexOf('0') === -1) {
                        categories = selectedCategories;
                    }

                    jQuery.post(ajaxurl, {
                        'security': '<?php echo $nonce_export_csv ?>',
                        'action': 'clickwhale/admin/export_csv',
                        'columns': columns,
                        'categories': categories
                    }, function(response) {
                        if (response.success) {
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
                    }).fail(function(xhr, textStatus, errorThrown) {
                        alert('<?php _e( 'An error occurred, try changing the request', CLICKWHALE_NAME ) ?>')
                    });
                })
            });
        </script>
		<?php
	}
}