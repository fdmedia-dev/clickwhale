<?php
namespace clickwhale\includes\admin\migration;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Show migration admin notice
 *
 * @link       #
 * @since      1.0.0
 */
class Clickwhale_Migration_Notice {

	/**
	 * Plugin name
	 * Used for actions and classes
	 *
	 * @since    1.0.0
	 * @access   public
	 * @var      string
	 */
	public $migrant;

	/**
	 * Plugin full name for messages.
	 *
	 * @since    1.0.0
	 * @access   public
	 * @var      string
	 */
	public $migrant_full;

	/**
	 * Plugin directory/name for deactivation.
	 *
	 * @since    1.0.0
	 * @access   public
	 * @var      string
	 */
	public $migrant_file;

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

		$this->migrant      = $migrant;
		$this->migrant_full = $migrant_full;
		$this->migrant_file = $migrant_file;

	}

	/**
	 * Add admin notice
	 *
	 * @since    1.0.0
	 */
	public function init() {

		$options_hide_migrate   = get_option( 'clickwhale_hide_notice_migrate' );
		$options_hide_deactive  = get_option( 'clickwhale_hide_notice_deactive' );
		$options_last_migration = get_option( 'clickwhale_tools_last_migration_options' );

		$is_tools_page = isset( $_GET['page'] ) && $_GET['page'] == CLICKWHALE_SLUG . '-tools';

		if ( ! $options_hide_migrate[ $this->migrant ] && ! $is_tools_page ) {
			add_action( 'admin_notices', [ $this, 'migration_notice' ] );
			add_action( 'admin_print_footer_scripts', [ $this, 'admin_scripts' ] );
		}

		if ( ! $options_hide_deactive[ $this->migrant ] && ! $is_tools_page ) {
			add_action( 'admin_notices', [ $this, 'deactive_notice' ] );
			add_action( 'admin_print_footer_scripts', [ $this, 'admin_scripts' ] );
		}

	}

	/**
	 * Notice before migration.
	 *
	 * @since    1.0.0
	 */
	public function migration_notice() {
		?>
        <div class="notice notice-info clickwhale-notice clickwhale-notice-<?php echo $this->migrant ?>-migrate">
            <p>
                <span> <?php printf( __( 'You are already using %1$s on your website. To migrate your %1$s data to Clickwhale, click here.', CLICKWHALE_NAME ), $this->migrant_full ); ?></span>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=' . CLICKWHALE_SLUG . '-tools' ) ); ?>"
                   class="button button-primary"><?php _e( 'Start Migration', CLICKWHALE_NAME ); ?></a>
                <a href="#" class="button button-dismiss"><?php _e( 'Not now', CLICKWHALE_NAME ); ?></a>
            </p>
        </div>
		<?php
	}

	/**
	 * Notice after migration for plugin deactivation.
	 *
	 * @since    1.0.0
	 */
	public function deactive_notice() {
		?>
        <div class="notice notice-error clickwhale-notice clickwhale-notice-<?php echo esc_html( $this->migrant ) ?>-deactive">
            <p>
                <span> <?php printf( __( 'All %1$s data have been successfully migrated to Clickwhale. You can now safely deactivate %1$s on your website.', CLICKWHALE_NAME ), $this->migrant_full ); ?></span>
                <a href="#"
                   class="button button-primary deactive"><?php printf( __( 'Deactivate %1$s', CLICKWHALE_NAME ),
						$this->migrant_full ); ?></a>
                <a href="#" class="button button-dismiss"><?php _e( 'Leave it active', CLICKWHALE_NAME ); ?></a>
            </p>
        </div>
		<?php
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function admin_scripts() {
		$nonce = wp_create_nonce( 'clickwhale_' . $this->migrant . '_admin_nonce' );
		?>
        <script type='text/javascript'>
            jQuery(document).ready(function() {

                jQuery('.clickwhale-notice-<?php echo $this->migrant ?>-deactive').on('click', '.deactive', function(e) {
                    e.preventDefault();
                    jQuery(this).closest('.clickwhale-notice').remove();

                    jQuery.post(ajaxurl, {
                        'action': 'clickwhale/admin/migration_deactive',
                        'security': '<?php echo esc_attr( $nonce ); ?>',
                        'plugin': '<?php echo esc_attr( $this->migrant ); ?>',
                        'target': '<?php echo esc_attr( $this->migrant_file ); ?>'
                    }, function(response) {
                        if (response.success) {
                            location.reload(true);
                        }
                    });
                })

                jQuery('.clickwhale-notice-<?php echo esc_attr( $this->migrant ) ?>-migrate').on('click', '.button-dismiss', function(e) {
                    e.preventDefault();
                    jQuery(this).closest('.clickwhale-notice').remove();

                    jQuery.post(ajaxurl, {
                        'action': 'clickwhale/admin/migration_notice_hide',
                        'security': '<?php echo esc_attr( $nonce ); ?>',
                        'plugin': '<?php echo esc_attr( $this->migrant ) ?>',
                        'type': 'migrate'
                    }, function(response) {});
                });

                jQuery('.clickwhale-notice-<?php echo esc_attr( $this->migrant ) ?>-deactive').on('click', '.button-dismiss', function(e) {
                    e.preventDefault();
                    jQuery(this).closest('.clickwhale-notice').remove();

                    jQuery.post(ajaxurl, {
                        'action': 'clickwhale/admin/migration_notice_hide',
                        'security': '<?php echo esc_attr( $nonce ); ?>',
                        'plugin': '<?php echo esc_attr( $this->migrant ) ?>',
                        'type': 'deactive'
                    }, function(response) {});
                });
            });
        </script>
		<?php
	}
}