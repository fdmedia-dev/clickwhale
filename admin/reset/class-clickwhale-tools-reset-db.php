<?php

class ClickwhaleToolsResetDB {
	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $plugin_name The ID of this plugin.
	 */
	private $plugin_name;

	public function __construct( $plugin_name ) {
		$this->plugin_name = $plugin_name;
	}

	public function init() {
		add_action( 'admin_init', [ $this, 'initialize_reset_options' ] );
		add_action( 'admin_print_footer_scripts', [ $this, 'admin_scripts' ] );
	}

	public function initialize_reset_options() {
		add_settings_section(
			'reset_settings_section',
			__( 'Reset BD tables', $this->plugin_name ),
			array( $this, 'reset_options_callback' ),
			'clickwhale_tools_reset_options'
		);

		register_setting(
			'clickwhale_tools_reset_options',
			'clickwhale_tools_reset_options'
		);
	}

	public function reset_options_callback() {
		echo '<p>' . __( 'Reset all plugin tables.', $this->plugin_name ) . '</p>';
	}

	public function admin_scripts() {
		if ( isset( $_GET['page'] ) && $_GET['page'] === 'clickwhale-tools' ) {
			$nonce = wp_create_nonce( 'clickwhale_reset' );
			?>

            <script type='text/javascript'>

                jQuery(document).ready(function () {
                    jQuery('#button-reset-db').click(function (e) {
                        e.preventDefault();

                        var resetDbContainer = jQuery(this).closest('#clickwhale-tools-db-reset'),
                            resetDbButton = jQuery(this),
                            resetDbSpinner = jQuery(resetDbContainer).find('.spinner'),
                            resetDbResult = jQuery(resetDbContainer).find('.results');

                        jQuery(resetDbButton).prop('disabled', true);
                        jQuery(resetDbSpinner).addClass("is-active");
                        jQuery(resetDbResult).html('');

                        if (window.confirm("<?php _e( 'This action will reset plugin tables and delete all existing data. Do you really want to do it?', $this->plugin_name ) ?>")) {
                            jQuery.post(ajaxurl, {
                                'security': '<?php echo esc_attr( $nonce ) ?>',
                                'action': 'clickwhale/admin/clickwhale_reset',
                            }, function (response) {
                                if (response.success) {
                                    var data = response.data;
                                    for (item in data) {
                                        var itemClass = data[item].status ? 'success' : 'error',
                                            itemText = data[item].text;
                                        jQuery(resetDbResult).append('<div class="notice notice-' + itemClass + '"><p>' + itemText + '</p></div>');
                                    }

                                    jQuery(resetDbButton).prop('disabled', false);
                                    jQuery(resetDbSpinner).removeClass("is-active");
                                }
                            });
                        } else {
                            jQuery(resetDbButton).prop('disabled', false);
                            jQuery(resetDbSpinner).removeClass("is-active");
                        }
                    });
                });
            </script>
            }
			<?php
		}
	}
}