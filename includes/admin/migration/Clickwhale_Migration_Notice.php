<?php
namespace Clickwhale\Admin\Migration;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Show migration admin notice.
 * @since   1.0.0
 */
class Clickwhale_Migration_Notice {

    /**
     * Plugin name
     * Used for actions and classes.
     *
     * @since    1.0.0
     * @access   public
     * @var      string
     */
    public string $migrant;

    /**
     * Plugin full name for messages.
     *
     * @since    1.0.0
     * @access   public
     * @var      string
     */
    public string $migrant_full;

    /**
     * Plugin directory name for deactivation.
     *
     * @since    1.0.0
     * @access   public
     * @var      string
     */
    public string $migrant_file;

    /**
     * Initialize the class and set its properties.
     *
     * @param string $migrant
     * @param string $migrant_full
     * @param string $migrant_file
     *
     * @since    1.0.0
     */
    public function __construct( string $migrant, string $migrant_full, string $migrant_file ) {
        $this->migrant = $migrant;
        $this->migrant_full = $migrant_full;
        $this->migrant_file = $migrant_file;
    }

    /**
     * Add admin notice.
     * @since    1.0.0
     */
    public function init() {
        $get_page = sanitize_key( (string) filter_input( INPUT_GET, 'page' ) );
        if ( ! empty( $get_page ) && $get_page === CLICKWHALE_SLUG . '-tools' ) {
            return;
        }

        $show_admin_scripts = false;
        $options_hide_migrate = get_option( 'clickwhale_hide_notice_migrate' );
        $options_hide_deactive = get_option( 'clickwhale_hide_notice_deactive' );

        if ( empty( $options_hide_migrate[$this->migrant] ) ) {
            add_action( 'admin_notices', array( $this, 'migration_notice' ) );
            $show_admin_scripts = true;
        }

        if ( empty( $options_hide_deactive[$this->migrant] ) ) {
            add_action( 'admin_notices', array( $this, 'deactive_notice' ) );
            $show_admin_scripts = true;
        }

        if ( $show_admin_scripts ) {
            add_action( 'admin_print_footer_scripts', array( $this, 'admin_scripts' ) );
        }
    }

    /**
     * Notice before migration.
     * @since    1.0.0
     */
    public function migration_notice() {
        ?>
        <div class="notice notice-info clickwhale-notice clickwhale-notice-<?php echo esc_attr( $this->migrant ); ?>-migrate">
            <p>
                <span><?php
                    printf(
                        /* translators: %1$s: the full name of the migrated plugin */
                        esc_html__( 'You are already using %1$s on your website. To migrate your %1$s data to Clickwhale, click here.', 'clickwhale' ),
                        esc_html( $this->migrant_full )
                    );
                ?></span>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=' . CLICKWHALE_SLUG . '-tools' ) ); ?>"
                   class="button button-primary"><?php esc_html_e( 'Start Migration', 'clickwhale' ); ?></a>
                <a href="#" class="button button-dismiss"><?php esc_html_e( 'Not now', 'clickwhale' ); ?></a>
            </p>
        </div>
        <?php
    }

    /**
     * Notice after migration for plugin deactivation.
     * @since    1.0.0
     */
    public function deactive_notice() {
        ?>
        <div class="notice notice-error clickwhale-notice clickwhale-notice-<?php echo esc_attr( $this->migrant ); ?>-deactive">
            <p>
                <span><?php
                    printf(
                        /* translators: %1$s: the full name of the migrated plugin */
                        esc_html__( 'All %1$s data have been successfully migrated to Clickwhale. You can now safely deactivate %1$s on your website.', 'clickwhale' ),
                        esc_html( $this->migrant_full )
                    );
                ?></span>
                <a href="#"
                   class="button button-primary deactive"
                ><?php
                    printf(
                        /* translators: %1$s: the full name of the plugin to deactivate */
                        esc_html__( 'Deactivate %1$s', 'clickwhale' ),
                        esc_html( $this->migrant_full )
                    );
                ?></a>
                <a href="#"
                   class="button button-dismiss"
                ><?php esc_html_e( 'Leave it active', 'clickwhale' ); ?></a>
            </p>
        </div>
        <?php
    }

    /**
     * Register the JavaScript for the admin area.
     * @since    1.0.0
     */
    public function admin_scripts() {
        ?>
        <script type='text/javascript'>
            jQuery(document).ready(function(){
                const
                    nonce = <?php echo wp_json_encode( wp_create_nonce( 'clickwhale_' . $this->migrant . '_admin_nonce' ) ); ?>,
                    migrant = <?php echo wp_json_encode( $this->migrant ); ?>,
                    migrantFile = <?php echo wp_json_encode( $this->migrant_file ); ?>;

                jQuery('.clickwhale-notice-' + migrant + '-deactive')
                    .on('click', '.deactive', function(e){
                        e.preventDefault();
                        jQuery(this).closest('.clickwhale-notice').remove();
                        jQuery.post(ajaxurl, {
                            'action': 'clickwhale/admin/migration_deactive',
                            'security': nonce,
                            'plugin': migrant,
                            'target': migrantFile
                        }, function(response){
                            if (response.success){
                                location.reload();
                            } else {
                                console.log('migration_deactive action error');
                            }
                        });
                    })
                    .on('click', '.button-dismiss', function(e){
                        e.preventDefault();
                        jQuery(this).closest('.clickwhale-notice').remove();
                        jQuery.post(ajaxurl, {
                            'action': 'clickwhale/admin/migration_notice_hide',
                            'security': nonce,
                            'plugin': migrant,
                            'type': 'deactive'
                        }, function(response){});
                    });

                jQuery('.clickwhale-notice-' + migrant + '-migrate').on('click', '.button-dismiss', function(e){
                    e.preventDefault();
                    jQuery(this).closest('.clickwhale-notice').remove();
                    jQuery.post(ajaxurl, {
                        'action': 'clickwhale/admin/migration_notice_hide',
                        'security': nonce,
                        'plugin': migrant,
                        'type': 'migrate'
                    }, function(response){});
                });
            });
        </script>
        <?php
    }
}
