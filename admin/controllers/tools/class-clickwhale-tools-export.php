<?php

class Clickwhale_Tools_Export {
	public array $columns;

	public function __construct() {
		$this->columns = ClickwhaleHelper::get_import_default_columns();

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
					'This tool allows you to generate and download a CSV file containing a list of all lists.',
					CLICKWHALE_NAME
				),
			)
		);

		add_settings_field(
			'export_columns',
			__( 'Columns for export', CLICKWHALE_NAME ),
			array( $this, 'export_columns_callback' ),
			'clickwhale_tools_export_settings',
			'clickwhale_tools_export_section'
		);

		add_settings_field(
			'export_categories',
			__( 'Category for export', CLICKWHALE_NAME ),
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
		foreach ( $this->columns as $option ) {
			$select .= '<option value="' . $option . '">' . $option . '</option>';
		}
		$select .= '</select>';

		echo $select;
	}

	public function export_categories_callback() {
		global $wpdb;

		$categories = $wpdb->get_results( "SELECT id, title FROM {$wpdb->prefix}clickwhale_categories", ARRAY_A );

		if ( ! $categories ) {
			_e( 'No categories', CLICKWHALE_NAME );
		} else {
			$select = '<select id="select_categories" class="clickwhale-select" multiple>';
			foreach ( $categories as $category ) {
				$select .= '<option value="' . $category['id'] . '">' . $category['title'] . '</option>';
			}
			$select .= '</select>';

			echo $select;
		}
	}

	public static function settings_section_callback( $args ) {
		echo '<p>' . $args['text'] . '</p>';
	}

	public function admin_scripts() {
		if ( ! empty( $_GET['page'] ) && $_GET['page'] !== 'clickwhale-tools' ) {
			return false;
		}

		$nonce_export_csv = wp_create_nonce( 'export_csv' );
		?>

        <script type='text/javascript'>
            jQuery(document).ready(function () {
                const
                    selectColumns = jQuery('#select_columns'),
                    selectCategories = jQuery('#select_categories');

                selectColumns.select2({
                    placeholder: "Export all columns"
                });

                selectCategories.select2({
                    placeholder: "Export all categories"
                });

                jQuery('#export_form').on('submit', function (e) {
                    e.preventDefault();

                    let
                        columns,
                        categories;

                    if (selectColumns.val().length > 0) {
                        columns = selectColumns.val();
                    } else {
                        columns = <?php echo json_encode( $this->columns ); ?>;
                    }

                    if (selectCategories.val().length > 0) {
                        categories = selectCategories.val();
                    }

                    jQuery.post(ajaxurl, {
                        'security': '<?php echo $nonce_export_csv ?>',
                        'action': 'clickwhale/admin/export_csv',
                        'columns': columns,
                        'categories': categories
                    }, function (response) {

                        if (response.success && response.data) {
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
                        }
                    })
                        .fail(function () {
                            alert('<?php _e( 'An error occurred, try changing the request', CLICKWHALE_NAME ) ?>')
                        });
                })
            });
        </script>
		<?php
	}
}