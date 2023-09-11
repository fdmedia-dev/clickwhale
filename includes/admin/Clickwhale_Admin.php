<?php

namespace clickwhale\includes\admin;

use clickwhale\includes\admin\helpers\Clickwhale_Helper;

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       #
 * @since      1.0.0
 *
 * @package    Clickwhale
 * @subpackage Clickwhale/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Clickwhale
 * @subpackage Clickwhale/admin
 * @author     fdmedia <https://fdmedia.io>
 */
class Clickwhale_Admin {
	/**
	 * @since    1.5.0
	 * @var Clickwhale_Admin|null
	 */
	private static ?Clickwhale_Admin $instance = null;

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $plugin_name The ID of this plugin.
	 */
	private string $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $version The current version of this plugin.
	 */
	private string $version;

	/**
	 * @return Clickwhale_Admin
	 * @since    1.5.0
	 */
	public static function get_instance(): ?Clickwhale_Admin {
		if ( empty( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	private function __construct() {

		$this->plugin_name = CLICKWHALE_NAME;
		$this->version     = CLICKWHALE_VERSION;

		$this->load_dependencies();
	}

	/**
	 * Throw error on object clone.
	 *
	 * The whole idea of the singleton design pattern is that there is a single
	 * object therefore, we don't want the object to be cloned.
	 *
	 * @return void
	 * @since 1.5
	 * @access protected
	 */
	public function __clone() {
		// Cloning instances of the class is forbidden.
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', $this->plugin_name ), '1.5' );
	}

	/**
	 * Disable un-serializing of the class.
	 *
	 * @return void
	 * @since 1.5
	 * @access protected
	 */
	public function __wakeup() {
		// Unserializing instances of the class is forbidden.
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', $this->plugin_name ), '1.5' );
	}

	/**
	 * Load the required dependencies for the Admin facing functionality.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Clickwhale_Ajax. Plugin Ajax actions
	 * - Clickwhale_Admin_Settings. Registers the admin settings and page.
	 * - Clickwhale_Admin_Tools. Registers the admin tools page and its subpages.
	 * - Clickwhale_WP_User. Get info about current user and its tracking ability
	 *
	 * - Clickwhale_Admin_Tools/Clickwhale_Admin_Migration. Migrate links and categories to our plugin
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {
		// Settings
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/Clickwhale_Ajax.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/Clickwhale_Settings.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/Clickwhale_Tools.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/Clickwhale_WP_User.php';

		// Helpers
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/helpers/Clickwhale_Helper.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/helpers/Clickwhale_Links_Helper.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/helpers/Clickwhale_Categories_Helper.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/helpers/Clickwhale_Linkpages_Helper.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/helpers/Clickwhale_Tracking_Codes_Helper.php';

		// Controllers
		if ( ! class_exists( 'WP_List_Table' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
		}

		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/categories/Clickwhale_Categories_List_Table.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/categories/Clickwhale_Category_Edit.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/linkpages/Clickwhale_Linkpages_List_Table.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/linkpages/Clickwhale_Linkpage_Edit.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/linkpages/Clickwhale_Linkpage_Content_Templates.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/links/Clickwhale_Links_List_Table.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/links/Clickwhale_Links_Bulk_Edit.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/links/Clickwhale_Link_Edit.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/tracking_codes/Clickwhale_Tracking_Codes_List_Table.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/tracking_codes/Clickwhale_Tracking_Code_Edit.php';

		// Migration
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/migration/Clickwhale_Migration.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/migration/class-clickwhale-migration-interface.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/migration/Clickwhale_Migration_Notice.php';

		// Other
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_style(
			$this->plugin_name . '_select2',
			CLICKWHALE_ADMIN_CSS_DIR . '/select2/select2.min.css',
			array(),
			'4.1.0-rc.0'
		);
		wp_enqueue_style(
			$this->plugin_name,
			CLICKWHALE_ADMIN_CSS_DIR . '/clickwhale-admin.css',
			array(),
			$this->version
		);
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		wp_enqueue_script( "jquery-ui-tabs" );

		if ( isset( $_GET['page'] ) && $_GET['page'] === 'clickwhale-edit-linkpage' ) {
			wp_enqueue_script( 'jquery-ui-droppable' );
			wp_enqueue_script( 'jquery-ui-draggable' );
			wp_enqueue_script( 'jquery-ui-sortable' );
			wp_enqueue_media();
			wp_enqueue_editor();
			wp_enqueue_script( 'wp-color-picker' );

			wp_enqueue_script(
				$this->plugin_name . '_picmo',
				CLICKWHALE_ADMIN_JS_DIR . '/picmo/picmo.umd.min.js',
				array( 'jquery' ),
				'5.8.1',
			);
			wp_enqueue_script(
				$this->plugin_name . '_picmo_popup_picker',
				CLICKWHALE_ADMIN_JS_DIR . '/picmo/popup-picker.umd.min.js',
				array( $this->plugin_name . '_picmo' ),
				'5.8.1'
			);
			wp_enqueue_script(
				$this->plugin_name . '_ionicons',
				CLICKWHALE_PUBLIC_JS_DIR . '/ionicons/ionicons.js',
				array( 'jquery' ),
				'7.1.0'
			);
		}
		if ( isset( $_GET['page'] ) && $_GET['page'] === 'clickwhale-edit-tracking-code' ) {
			wp_enqueue_code_editor( array( 'type' => 'text/html' ) );
		}

		wp_enqueue_script(
			$this->plugin_name . '_select2',
			CLICKWHALE_ADMIN_JS_DIR . '/select2/select2.min.js',
			array( 'jquery' ),
			'4.1.0-rc.0'
		);
		wp_enqueue_script(
			$this->plugin_name,
			CLICKWHALE_ADMIN_JS_DIR . '/clickwhale-admin.js',
			array( 'jquery' ),
			$this->version
		);
		wp_localize_script(
			$this->plugin_name,
			'clickwhale_admin', array(
				'siteurl' => home_url(),
			)
		);
	}

	public function admin_banner() {
		$link_logo     = 'https://clickwhale.pro?utm_source=user+site&utm_medium=admin+pages&utm_campaign=ClickWhale+-+Free+Version&utm_term=logo-link';
		$link_helpdesk = 'https://clickwhale.pro/contact/?utm_source=user+site&utm_medium=admin+pages&utm_campaign=ClickWhale+-+Free+Version&utm_term=help-link';
		$link_review   = 'https://wordpress.org/support/plugin/clickwhale/reviews/#new-post';
		?>

        <div class="clickwhale-banner">
            <div class="clickwhale-banner--logo">
                <a href="<?php echo $link_logo ?>"
                   target="_blank"
                   rel="noopener">
                    <img src="<?php echo esc_attr( CLICKWHALE_ADMIN_IMAGES_DIR . '/wordmark.svg' ) ?>"
                         alt="<?php echo $this->plugin_name ?>">
                </a>
            </div>
            <div class="clickwhale-banner--links">
				<?php if ( $link_review ) { ?>
                    <div class="clickwhale-banner--link-review">
						<?php printf( __( 'You like ClickWhale? Then please <a href="%1$s" target="_blank">leave a review here</a>',
							$this->plugin_name ), esc_url( $link_review ) ); ?>
                        <span class="clickwhale-banner--link-review--raiting">
                            <span class="dashicons dashicons-star-filled"></span>
                            <span class="dashicons dashicons-star-filled"></span>
                            <span class="dashicons dashicons-star-filled"></span>
                            <span class="dashicons dashicons-star-filled"></span>
                            <span class="dashicons dashicons-star-filled"></span>
                        </span>
                    </div>
				<?php } ?>
				<?php if ( $link_helpdesk ) { ?>
                    <a href="<?php echo esc_attr( $link_helpdesk ) ?>"
                       class="clickwhale-banner--button outlined dark"
                       target="_blank"
                       rel="noopener">
						<?php _e( 'Need help?', $this->plugin_name ) ?>
                    </a>
				<?php } ?>

				<?php do_action( 'clickwhale_admin_banner_pro_button' ) ?>

            </div>
        </div>
		<?php
	}

	public function admin_banner_pro_button() {
		?>
        <a href="<?php echo esc_attr( Clickwhale_Helper::get_pro_link() ) ?>"
           class="clickwhale-banner--button"
           target="_blank">
			<?php _e( 'Upgrade to PRO', $this->plugin_name ) ?>
        </a>
		<?php
	}

	public function admin_pro_message() {
		?>
        <div class="clickwhale-linkpage--message">
			<?php _e( 'Available only in PRO version', 'clickwhale' ); ?>
        </div>
		<?php
	}

	/**
	 * @return void
	 * @since 1.3.0
	 */
	public function admin_bar_render( $wp_admin_bar ) {
		$wp_admin_bar->add_node( array(
				'id'    => $this->plugin_name,
				'title' => '<span class="ab-icon"><img src="' . plugin_dir_url( __FILE__ ) . 'images/click-icon.svg"/></span> ClickWhale',
				'href'  => admin_url( 'admin.php?page=clickwhale' ),
				'meta'  => array(
					'class' => $this->plugin_name,
					'title' => 'ClickWhale'
				)
			)
		);

		$wp_admin_bar->add_node( array(
				'id'     => $this->plugin_name . '-new-link',
				'title'  => __( 'New Link', $this->plugin_name ),
				'href'   => admin_url( 'admin.php?page=clickwhale-edit-link' ),
				'parent' => $this->plugin_name,
				'meta'   => array(
					'class' => $this->plugin_name . 'new-link',
					'title' => __( 'Add New Link', $this->plugin_name )
				)
			)
		);
		$wp_admin_bar->add_node( array(
				'id'     => $this->plugin_name . '-new-category',
				'title'  => __( 'New Category', $this->plugin_name ),
				'href'   => admin_url( 'admin.php?page=clickwhale-edit-category' ),
				'parent' => $this->plugin_name,
				'meta'   => array(
					'class' => $this->plugin_name . 'new-category',
					'title' => __( 'Add New Category', $this->plugin_name )
				)
			)
		);
		$wp_admin_bar->add_node( array(
				'id'     => $this->plugin_name . '-new-linkpage',
				'title'  => __( 'New Link Page', $this->plugin_name ),
				'href'   => admin_url( 'admin.php?page=clickwhale-edit-linkpage' ),
				'parent' => $this->plugin_name,
				'meta'   => array(
					'class' => $this->plugin_name . 'new-linkpage',
					'title' => __( 'Add New Link Page', $this->plugin_name )
				)
			)
		);
		$wp_admin_bar->add_node( array(
				'id'     => $this->plugin_name . '-new-tracking code',
				'title'  => __( 'New Tracking Code', $this->plugin_name ),
				'href'   => admin_url( 'admin.php?page=clickwhale-edit-tracking-code' ),
				'parent' => $this->plugin_name,
				'meta'   => array(
					'class' => $this->plugin_name . 'new-tracking-code',
					'title' => __( 'Add New Tracking Code', $this->plugin_name )
				)
			)
		);
	}

	/**
	 * @return void
	 * @since 1.4.0
	 */
	public function pro_subscription_action() {
		$user     = wp_get_current_user();
		$url      = "https://clickwhale.pro/?fluentcrm=1&route=contact&hash=e2920f25-a285-4568-bea4-ede017a039fb";
		$response = wp_remote_post( $url, array(
				'method' => 'POST',
				'body'   => array(
					'email'      => sanitize_email( $_POST['email'] ),
					'first_name' => $user ? $user->first_name : '',
				)
			)
		);

		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();
			echo "Something went wrong: $error_message";
		} else {
			wp_redirect( admin_url( 'admin.php?page=clickwhale-pro&success=1#clickwhaleSubscribe' ) );
		}
	}

	/**
	 * @return void
	 * @since 1.4.1
	 */
	public function hide_notice_on_upgrade_to_pro_page() {
		if ( isset( $_GET['page'] ) && $_GET['page'] === 'clickwhale-pro' ) {
			remove_all_actions( 'user_admin_notices' );
			remove_all_actions( 'admin_notices' );
		}
	}

	public function admin_scripts() {
		if ( isset( $_GET['page'] ) ) {
			if ( $_GET['page'] === 'clickwhale' || $_GET['page'] === 'clickwhale-linkpages' ) {
				?>
                <script type='text/javascript'>
                    jQuery(document).ready(function () {
                        jQuery('.slug-input--btn').click(function (e) {
                            e.preventDefault();
                            var $temp = jQuery('<input>'),
                                textToCopy = jQuery(this).parent().find('input').val();

                            textToCopy = clickwhale_admin.siteurl + '/' + textToCopy + '/';
                            jQuery('body').append($temp);
                            $temp.val(textToCopy).select();
                            document.execCommand("copy");
                            $temp.remove();
                        });
                    });
                </script>
				<?php
			}
			if ( $_GET['page'] === 'clickwhale-edit-link' || $_GET['page'] === 'clickwhale-edit-linkpage' ) {
				?>
                <script type='text/javascript'>
                    jQuery(document).ready(function () {
                        jQuery('#copy-link-url, #cw-slug--text').click(function (e) {
                            e.preventDefault();
                            var $temp = jQuery('<input>'),
                                textToCopy = jQuery('#cw-slug').val();

                            textToCopy = clickwhale_admin.siteurl + '/' + textToCopy + '/';
                            jQuery('body').append($temp);
                            $temp.val(textToCopy).select();
                            document.execCommand("copy");
                            jQuery(this).find('em').hide();
                            jQuery(this).append('<span class="copied"><?php _e( 'Copied!',
								$this->plugin_name ) ?></span>');

                            $temp.remove();

                            setTimeout(function () {
                                jQuery('#cw-slug--text').find('em').show();
                                jQuery('#cw-slug--text').find('.copied').remove();
                            }, 2000);
                        });
                    });
                </script>
				<?php
			}
			if ( $_GET['page'] === 'clickwhale-tracking-codes' ) {
				$nonce = wp_create_nonce( 'clickwhale_toggle_tracking_code' );
				?>
                <script type='text/javascript'>
                    jQuery(document).ready(function () {
                        jQuery('.clickwhale-checkbox--toggle [type="checkbox"]').change(function () {
                            var active = this.checked,
                                id = this.dataset.id;

                            jQuery.post(ajaxurl, {
                                'security': '<?php echo $nonce ?>',
                                'action': 'clickwhale/admin/tracking_code_toggle_active',
                                'status': active ? 1 : 0,
                                'id': id
                            }, function (response) {
                                if (response.data.action_disable_all) {
                                    jQuery('.clickwhale-checkbox--toggle [type="checkbox"]:not(:checked)').prop('disabled', true);
                                    jQuery('#clickwhale_tracking_codes_list_limit_notice').show()
                                } else {
                                    jQuery('.clickwhale-checkbox--toggle [type="checkbox"]:not(:checked)').prop('disabled', false);
                                    jQuery('#clickwhale_tracking_codes_list_limit_notice').hide()
                                }
                            })
                        });
                    });
                </script>
				<?php
			}
		}
	}
}
