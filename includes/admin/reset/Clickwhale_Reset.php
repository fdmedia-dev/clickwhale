<?php
namespace clickwhale\includes\admin\reset;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Clickwhale_Reset {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $plugin_name The ID of this plugin.
	 */
	private $plugin_name;

    /**
     * @var Clickwhale_Reset
     */
	private static $instance;

	public static function get_instance(): Clickwhale_Reset {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function init( $plugin_name ) {
		$this->plugin_name = $plugin_name;
	}

	public function initialize_reset_settings_options() {
		add_settings_section(
			'reset_settings_section',
			__( 'Reset plugin options', $this->plugin_name ),
			array( $this, 'reset_settings_callback' ),
			'clickwhale_tools_reset_settings'
		);

		register_setting(
			'clickwhale_tools_reset_db_settings',
			'clickwhale_tools_reset_db_settings'
		);
	}

	public function initialize_reset_db_options() {
		add_settings_section(
			'reset_db_settings_section',
			__( 'Delete all plugin data', $this->plugin_name ),
			array( $this, 'reset_db_settings_callback' ),
			'clickwhale_tools_reset_db_settings'
		);

		register_setting(
			'clickwhale_tools_reset_db_settings',
			'clickwhale_tools_reset_db_settings'
		);
	}

	public function initialize_reset_stats_options() {
		add_settings_section(
			'reset_stats_settings_section',
			__( 'Reset all statistics', $this->plugin_name ),
			array( $this, 'reset_stats_settings_callback' ),
			'clickwhale_tools_reset_stats_settings'
		);

		register_setting(
			'clickwhale_tools_reset_stats_settings',
			'clickwhale_tools_reset_stats_settings'
		);
	}

	public function reset_settings_callback() {
		echo '<p>' . __( 'At this point you can reset plugin setting to default values.', $this->plugin_name ) . '</p>';
	}

	public function reset_db_settings_callback() {
		echo '<p>' . __( 'At this point you can delete all entries (links, categories and stats) from the database tables of our plugin.', $this->plugin_name ) . '</p>';
	}

	public function reset_stats_settings_callback() {
		echo '<p>' . __( 'In case you want to clean up your stats, you can remove all previously counted clicks from the database here.', $this->plugin_name ) . '</p>';
	}

	public function admin_scripts() {
        if ( empty( $_GET['page'] ) ) {
            return;
        }

        if ( $_GET['page'] !== CLICKWHALE_SLUG . '-tools' ) {
            return;
        }

        $nonce = wp_create_nonce( 'clickwhale_reset' );
        ?>
        <script type='text/javascript'>
            jQuery(document).ready(function() {
                jQuery('#clickwhale-tools-reset').on('click', 'button', function(e) {
                    e.preventDefault();

                    let
                        buttonContainer = jQuery(this).parent(),
                        resetButton = jQuery(this),
                        resetSpinner = jQuery(buttonContainer).find('.spinner'),
                        resetResult = jQuery(buttonContainer).find('.results'),
                        resetConfirm,
                        resetType;

                    jQuery(resetButton).prop('disabled', true);
                    jQuery(resetSpinner).addClass("is-active");
                    jQuery(resetResult).html('');

                    switch (resetButton.attr('id')) {
                        case 'button-reset-settings':
                            resetConfirm = '<?php _e( 'Are you sure? This action restore all plugin settings to default. This process cannot be undone!', $this->plugin_name ) ?>';
                            resetType = 'settings';
                            break;
                        case 'button-reset-db':
                            resetConfirm = '<?php _e( 'Are you sure? This action will reset plugin tables and delete all existing data. This process cannot be undone!', $this->plugin_name ) ?>';
                            resetType = 'db';
                            break;
                        case 'button-reset-stats':
                            resetConfirm = '<?php _e( 'Are you sure? This action will reset all statistic. This process cannot be undone!', $this->plugin_name ) ?>';
                            resetType = 'stats';
                            break;
                    }

                    if (window.confirm(resetConfirm)) {
                        jQuery.post(ajaxurl, {
                            'security': '<?php echo esc_attr( $nonce ) ?>',
                            'action': 'clickwhale/admin/clickwhale_reset',
                            'reset': resetType,
                        }, function(response) {
                            if (response.success) {
                                let
                                    itemClass = response.data.status ? 'success' : 'error',
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
        <?php
	}
}