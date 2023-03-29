<?php

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
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $plugin_name The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $version The current version of this plugin.
	 */
	private $version;

	/**
	 * @var Clickwhale_Admin
	 */
	private static $instance;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param string $plugin_name The name of this plugin.
	 * @param string $version The version of this plugin.
	 *
	 * @since    1.0.0
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;

		$this->load_dependencies();
		$this->migration();
	}

	/**
	 * @return Clickwhale_Admin
	 */
	public static function getInstance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Load the required dependencies for the Admin facing functionality.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Clickwhale_Ajax. Plugin Ajax actions
	 * - Clickwhale_Admin_Settings. Registers the admin settings and page.
	 * - Clickwhale_Admin_Tools. Registers the admin tools page.
	 * - Clickwhale_Admin_Migration. Migrate links and categories to our plugin
	 *
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		// Helpers
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/helpers/class-clickwhale-helper.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/helpers/class-clickwhale-links-helper.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/helpers/class-clickwhale-categories-helper.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/helpers/class-clickwhale-linkpages-helper.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/helpers/class-clickwhale-tracking-codes-helper.php';

		// Settings
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-clickwhale-ajax.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-clickwhale-settings.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-clickwhale-tools.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-clickwhale-migration.php';

		// Controllers
		if ( ! class_exists( 'WP_List_Table' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
		}
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/controllers/links/class-clickwhale-links-list-table.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/controllers/links/class-clickwhale-link-edit.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/controllers/categories/class-clickwhale-categories-list-table.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/controllers/categories/class-clickwhale-category-edit.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/controllers/linkpages/class-clickwhale-linkpages-list-table.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/controllers/linkpages/class-clickwhale-linkpage-edit.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/controllers/linkpages/LinkpageContentTemplates.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/controllers/tracking-codes/class-clickwhale-tracking-codes-list-table.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/controllers/tracking-codes/class-clickwhale-tracking-code-edit.php';

	}

	public function migration() {
		$migration = new Clickwhale_Migration();
		$migration->init();
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Clickwhale_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Clickwhale_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_style( $this->plugin_name . '_select2', plugin_dir_url( __FILE__ ) . 'css/select2.min.css', array(),
			'4.1.0-rc.0', 'all' );
		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/clickwhale-admin.css', array(),
			$this->version, 'all' );
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Clickwhale_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Clickwhale_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		if ( isset( $_GET['page'] ) && $_GET['page'] === 'clickwhale-edit-linkpage' ) {
			wp_enqueue_script( 'jquery-ui-droppable' );
			wp_enqueue_script( 'jquery-ui-draggable' );
			wp_enqueue_script( 'jquery-ui-sortable' );
			wp_enqueue_script( "jquery-ui-tabs" );
			wp_enqueue_media();
			wp_enqueue_editor();
			wp_enqueue_script( 'wp-color-picker' );

			wp_enqueue_script(
				$this->plugin_name . '_picmo',
				'https://cdn.jsdelivr.net/npm/picmo@latest/dist/umd/index.js',
				array( 'jquery' ),
				'5.8.1',
			);
			wp_enqueue_script(
				$this->plugin_name . '_picmo_popup_picker',
				'https://cdn.jsdelivr.net/npm/@picmo/popup-picker@latest/dist/umd/index.js',
				array( $this->plugin_name . '_picmo' ),
				'5.8.1'
			);
		}
		if ( isset( $_GET['page'] ) && $_GET['page'] === 'clickwhale-edit-tracking-code' ) {
			wp_enqueue_code_editor( array( 'type' => 'text/html' ) );
		}

		wp_enqueue_script(
			$this->plugin_name . '_select2',
			plugin_dir_url( __FILE__ ) . 'js/select2.min.js',
			array( 'jquery' ),
			'4.1.0-rc.0'
		);
		wp_enqueue_script(
			$this->plugin_name,
			plugin_dir_url( __FILE__ ) . 'js/clickwhale-admin.js',
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

	public function clickwhale_categories_limit_callback( $limit ) {
		return $limit;
	}

	public function clickwhale_admin_banner_callback() {
		$link_logo     = 'https://clickwhale.pro?utm_source=user+site&utm_medium=admin+pages&utm_campaign=ClickWhale+-+Free+Version&utm_term=logo-link';
		$link_helpdesk = 'https://clickwhale.pro/contact/?utm_source=user+site&utm_medium=admin+pages&utm_campaign=ClickWhale+-+Free+Version&utm_term=help-link';
		$link_review   = 'https://wordpress.org/support/plugin/clickwhale/reviews/#new-post';
		?>

        <div class="clickwhale-banner">
            <div class="clickwhale-banner--logo">
                <a href="<?php echo $link_logo ?>"
                   target="_blank"
                   rel="noopener">
                    <img src="<?php echo esc_attr( plugin_dir_url( __FILE__ ) . 'images/wordmark.svg' ) ?>"
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
                    <a href="<?php echo esc_attr( $link_helpdesk ) ?>" class="clickwhale-banner--button"
                       target="_blank" rel="noopener"><?php _e( 'Need help?', $this->plugin_name ) ?></a>
				<?php } ?>
				<?php //do_action( 'clickwhale_admin_banner_button_pro' ) ?>
            </div>
        </div>
		<?php
	}

	public function clickwhale_admin_banner_button_pro_callback() {
		$link_pro = 'https://clickwhale.pro';
		?>
        <a href="<?php echo esc_attr( $link_pro ) ?>" class="clickwhale-banner--button" target="_blank">
			<?php _e( 'Update to Pro', $this->plugin_name ) ?>
        </a>
		<?php
	}

	public function clickwhale_admin_pro_message_callback() {
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
                            $temp.remove();
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
