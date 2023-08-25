<?php

class Clickwhale_Tools_Import {

	public function __construct() {
		global $wpdb;
		add_action( 'admin_init', array( $this, 'import_settings' ) );
		add_action( 'admin_print_footer_scripts', array( $this, 'admin_scripts' ) );
	}

	public function import_settings() {

		add_settings_section(
			'clickwhale_tools_import_section',
			__( 'Import links from a CSV file', CLICKWHALE_NAME ),
			array( $this, 'settings_section_callback' ),
			'clickwhale_tools_import_settings',
			array(
				'text' => __( 'This tool allows you to import links to your site from a CSV file.', CLICKWHALE_NAME ),
			)
		);

		add_settings_field(
			'import_file',
			__( 'Choose a CSV file from your computer', CLICKWHALE_NAME ),
			array( $this, 'import_file_callback' ),
			'clickwhale_tools_import_settings',
			'clickwhale_tools_import_section'
		);

		register_setting(
			'clickwhale_tools_import_settings',
			'clickwhale_tools_import_settings'
		);
	}

	public function import_file_callback() {
		echo '<input type="file" id="import_file" name="import_file" accept=".csv">';
	}

	public static function settings_section_callback( $args ) {
		echo '<p>' . $args['text'] . '</p>';
	}

	public function admin_scripts() {
		if ( ! empty( $_GET['page'] ) && $_GET['page'] !== 'clickwhale-tools' ) {
			return false;
		}

		$nonce_upload_csv = wp_create_nonce( 'upload_csv' );
		$nonce_map_csv    = wp_create_nonce( 'map_csv' );
		$nonce_import_csv = wp_create_nonce( 'import_csv' );
		$nonce_check_slug = wp_create_nonce( 'check_slug' );
		?>

        <script type='text/javascript'>
            jQuery(document).ready(function () {
                const
                    progressWrap = jQuery('#import_progress'),
                    uploadForm = jQuery('#upload_form'),
                    uploadButton = uploadForm.find('[type="submit"]');

                let
                    file,
                    delimiter;

                uploadForm.on('submit', function (e) {
                    e.preventDefault();

                    file = jQuery('#import_file')[0].files[0];

                    if (file) {
                        const formData = new FormData();
                        formData.set('action', 'clickwhale/admin/upload_csv');
                        formData.set('security', '<?php echo $nonce_upload_csv ?>');
                        formData.set('file', file);

                        jQuery(uploadButton).prop('disabled', true);

                        jQuery.ajax({
                            url: ajaxurl,
                            method: "POST",
                            data: formData,
                            dataType: 'json',
                            contentType: false,
                            processData: false,
                            success: function (response) {

                                progressWrap.find('.import-progress--bar').css('width', '50%');
                                progressWrap.find('#point-02').addClass('active');
                                jQuery(uploadForm).hide();

                                delimiter = response.data.delimiter;
                                jQuery('#mapping_table').prepend(response.data.table).show();

                            }
                        });
                    } else {
                        alert('<?php _e( 'Please, select .csv file', CLICKWHALE_NAME ) ?>');
                    }
                });

                jQuery(document).on('click', '#mapping_button', function () {
                    const
                        formData = new FormData(),
                        mapped = [],
                        excluded = [];
                    let
                        error = false,
                        message;

                    jQuery('#mapping_table table tbody tr').each(function (index) {
                        const
                            select = jQuery(this).find('select'),
                            selected = select.val();

                        select.attr('style', '');
                        select.parent().find('p').remove();

                        if (selected !== '0') {
                            if (!mapped.includes(selected)) {
                                mapped.push(selected);
                            } else {
                                error = true;
                                message = '<?php echo __( 'Duplicated field', CLICKWHALE_NAME ) ?>';
                                select.css('border-color', 'red');
                                select.parent().append('<p style="margin: 3px 0 0; line-height: 1em; color: red;"><small>' + message + '</small></p>');
                            }
                        } else {
                            excluded.push(index);
                        }
                    });

                    formData.set('action', 'clickwhale/admin/map_csv');
                    formData.set('security', '<?php echo $nonce_map_csv ?>');
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
                        success: function (response) {

                            progressWrap.find('.import-progress--bar').css('width', '75%');
                            progressWrap.find('#point-03').addClass('active');
                            jQuery('#mapping_table').hide();
                            jQuery('#import_table').prepend(response.data).show();

                        }
                    });
                });

                jQuery(document).on('click', '#import_table td button', function (e) {
                    e.preventDefault();

                    const remove = jQuery(this);
                    remove.closest('tr').css('background-color', 'red');
                    setTimeout(function () {
                        remove.closest('tr').remove();
                    }, 300);

                });

                jQuery(document).on('click', '#import_button', function (e) {
                    e.preventDefault();

                    const
                        importData = [],
                        importTable = jQuery('#import_table table'),
                        importButton = jQuery("#import_button"),
                        importResult = jQuery('#import_result'),
                        slugs = [];

                    let error = false;

                    importButton.prop('disabled', true);

                    jQuery(importTable).find('tbody tr').each(function () {
                        const row = {};

                        jQuery(this).find('td.for_import').each(function (index) {
                            const key = jQuery(importTable).find('thead th').eq(index).text();
                            let
                                val = '',
                                message = '';

                            switch (key) {
                                case 'redirection':
                                    const select = jQuery(this).find('select');
                                    if (select.length === 0) {
                                        break;
                                    }

                                    val = select.val();
                                    break;

                                case 'nofollow':
                                case 'sponsored':
                                    const checkbox = jQuery(this).find('input[type="checkbox"]');
                                    if (checkbox.length === 0) {
                                        break;
                                    }

                                    val = checkbox.is(":checked") ? 1 : 0;
                                    break;

                                default:
                                    const input = jQuery(this).find('input');
                                    if (input.length === 0) {
                                        break;
                                    }

                                    val = input.val();

                                    input.attr('style', '');
                                    input.parent().find('p').remove();

                                    if (!val) {
                                        error = true;
                                        message = '<?php echo __( 'Required field', CLICKWHALE_NAME ) ?>';
                                        input.css('border-color', 'red');
                                        input.parent().append('<p style="margin: 3px 0 0; line-height: 1em; color: red;"><small>' + message + '</small></p>');
                                        break;
                                    }

                                    if (key === 'slug') {

                                        if (check_slug(val)) {
                                            error = true;
                                            message = '<?php echo __( 'Slug already exists',
												CLICKWHALE_NAME ) ?>';
                                            input.css('border-color', 'red');
                                            input.parent().append('<p style="margin: 3px 0 0; line-height: 1em; color: red;"><small>' + message + '</small></p>');
                                            break;
                                        }

                                        if (slugs.includes(val)) {
                                            error = true;
                                            message = '<?php echo __( 'Slug is not unique', CLICKWHALE_NAME ) ?>';
                                            input.parent().append('<p style="margin: 3px 0 0; line-height: 1em; color: red;"><small>' + message + '</small></p>');
                                            break;
                                        } else {
                                            slugs.push(val);
                                        }

                                    }
                            }
                            if (key) {
                                row[key] = val;
                            }

                        });

                        importData.push(row);

                    });

                    importButton.prop('disabled', false);

                    if (!error) {
                        jQuery.post(ajaxurl, {
                            'action': 'clickwhale/admin/import_csv',
                            'security': '<?php echo $nonce_import_csv ?>',
                            'data': importData,
                        }, function (response) {
                            console.log(response.data);
                            if (response.data) {
                                const data = response.data;

                                progressWrap.find('.import-progress--bar').css('width', '100%');
                                progressWrap.find('#point-04').addClass('active');
                                jQuery('#import_table').hide();

                                importResult.show();

                                for (let item in data) {
                                    importResult.prepend('<p>' + data[item] + '</p>');
                                }

                                importResult.find('a').show();
                            }
                        })
                    }
                });

                function check_slug(slug) {
                    let result = false;

                    jQuery.ajax({
                        async: false,
                        type: 'post',
                        dataType: 'json',
                        url: ajaxurl,
                        data: {
                            'security': '<?php echo $nonce_check_slug ?>',
                            'action': 'clickwhale/admin/check_slug',
                            'type': 'link',
                            'slug': slug,
                            'id': 0
                        }
                    }).done(function (response) {
                        result = response.data;
                    });

                    return result;
                }

            });
        </script>
		<?php
	}
}