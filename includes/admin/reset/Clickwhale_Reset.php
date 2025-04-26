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
     * @var      string The ID of this plugin.
     */
    private string $plugin_name = '';

    /**
     * @var Clickwhale_Reset
     */
    private static Clickwhale_Reset $instance;

    /**
     * @return Clickwhale_Reset
     */
    public static function get_instance(): Clickwhale_Reset {
        if ( empty( self::$instance ) ) {
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
            __( 'Reset plugin options', 'clickwhale' ),
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
            __( 'Delete all plugin data', 'clickwhale' ),
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
            __( 'Reset all statistics', 'clickwhale' ),
            array( $this, 'reset_stats_settings_callback' ),
            'clickwhale_tools_reset_stats_settings'
        );

        register_setting(
            'clickwhale_tools_reset_stats_settings',
            'clickwhale_tools_reset_stats_settings'
        );
    }

    public function reset_settings_callback() {
        echo '<p>' . __( 'At this point you can reset plugin settings to default values.', 'clickwhale' ) . '</p>';
    }

    public function reset_db_settings_callback() {
        echo '<p>' . __( 'At this point you can delete all entries (links, categories and stats) from the database tables of our plugin.', 'clickwhale' ) . '</p>';
    }

    public function reset_stats_settings_callback() {
        echo '<p>' . __( 'In case you want to clean up your stats, you can remove all previously counted clicks from the database here.', 'clickwhale' ) . '</p>';
    }

    public function admin_scripts() {
        if ( empty( $_GET['page'] ) ) {
            return;
        }

        if ( sanitize_key( $_GET['page'] ) !== CLICKWHALE_SLUG . '-tools' ) {
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
                            resetConfirm = '<?php echo esc_js( __( 'Are you sure? This action restore all plugin settings to default. This process cannot be undone!', 'clickwhale' ) ); ?>';
                            resetType = 'settings';
                            break;
                        case 'button-reset-db':
                            resetConfirm = '<?php echo esc_js( __( 'Are you sure? This action will reset plugin tables and delete all existing data. This process cannot be undone!', 'clickwhale' ) ); ?>';
                            resetType = 'db';
                            break;
                        case 'button-reset-stats':
                            resetConfirm = '<?php echo esc_js( __( 'Are you sure? This action will reset all statistic. This process cannot be undone!', 'clickwhale' ) ); ?>';
                            resetType = 'stats';
                            break;
                    }

                    if (window.confirm(resetConfirm)) {
                        jQuery.post(ajaxurl, {
                            'security': '<?php echo $nonce; ?>',
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
