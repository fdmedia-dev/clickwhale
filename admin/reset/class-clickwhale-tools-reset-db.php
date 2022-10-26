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
			__( 'Reset DB tables and plugin settings', $this->plugin_name ),
			array( $this, 'reset_settings_callback' ),
			'clickwhale_tools_reset_settings'
		);

		register_setting(
			'clickwhale_tools_reset_settings',
			'clickwhale_tools_reset_settings'
		);
	}

	public function reset_settings_callback() {
		echo '<p>' . __( 'Reset all plugin tables (you will lost all links, categories and linkpages) and restore default values.', $this->plugin_name ) . '</p>';
	}

	public function admin_scripts() {
		if ( isset( $_GET['page'] ) && $_GET['page'] === 'clickwhale-tools' ) {
			$nonce = wp_create_nonce( 'clickwhale_reset' );
			?>

            <script type='text/javascript'>

                jQuery(document).ready(function () {
                    jQuery('#clickwhale-tools-reset').on('click', 'button', function (e) {
                        e.preventDefault();

                        var resetContainer = jQuery(this).closest('#clickwhale-tools-reset'),
                            resetButton = jQuery(this),
                            resetSpinner = jQuery(resetContainer).find('.spinner'),
                            resetResult = jQuery(resetContainer).find('.results'),
                            resetConfirm,
                            resetType;

                        jQuery(resetButton).prop('disabled', true);
                        jQuery(resetSpinner).addClass("is-active");
                        jQuery(resetResult).html('');

                        if (resetButton.attr('id') === 'button-reset-db') {
                            resetConfirm = '<?php _e( 'This action will reset plugin tables and delete all existing data. Do you really want to do it?', $this->plugin_name ) ?>';
                            resetType = 'db';
                        } else {
                            resetConfirm = '<?php _e( 'This action restore all plugin settings to default. Do you really want to do it?', $this->plugin_name ) ?>';
                            resetType = 'settings';
                        }
                        if (window.confirm(resetConfirm)) {
                            jQuery.post(ajaxurl, {
                                'security': '<?php echo esc_attr( $nonce ) ?>',
                                'action': 'clickwhale/admin/clickwhale_reset',
                                'reset': resetType,
                            }, function (response) {
                                if (response.success) {
                                    var itemClass = response.data.status ? 'success' : 'error',
                                        itemText = response.data.text;
                                    jQuery(resetResult).append('<div class="notice notice-' + itemClass + '"><p>' + itemText + '</p></div>');

                                    jQuery(resetButton).prop('disabled', false);
                                    jQuery(resetSpinner).removeClass("is-active");
                                }
                            });
                        } else {
                            jQuery(resetButton).prop('disabled', false);
                            jQuery(resetSpinner).removeClass("is-active");
                        }
                    });
                });
            </script>
            }
			<?php
		}
	}
}