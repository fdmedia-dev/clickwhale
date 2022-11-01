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

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/clickwhale-admin.css', array(), $this->version, 'all' );

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

		wp_enqueue_script( 'jquery-ui-droppable' );
		wp_enqueue_script( 'jquery-ui-draggable' );
		wp_enqueue_script( 'jquery-ui-sortable' );
		wp_enqueue_script( "jquery-ui-tabs" );
		wp_enqueue_media();
		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/clickwhale-admin.js', array( 'jquery' ), $this->version, false );
		wp_localize_script( $this->plugin_name, 'clickwhale_admin', array(
			'siteurl' => home_url(),
		) );
	}

	public function clickwhale_categories_limit_callback( $limit ) {
		return $limit;
	}

	public function clickwhale_admin_banner_callback() {
		$link_helpdesk = 'https://clickwhale.pro/docs/';
		?>

        <div class="clickwhale-banner">
            <div class="clickwhale-banner--logo"><img
                        src="<?php echo esc_attr( plugin_dir_url( __FILE__ ) . 'images/wordmark.svg' ) ?>"
                        alt="<?php echo $this->plugin_name ?>"></div>
            <div class="clickwhale-banner--links">
				<?php if ( $link_helpdesk ) { ?>
                    <a href="<?php echo esc_attr( $link_helpdesk ) ?>" class="clickwhale-banner--link"
                       target="_blank"><?php _e( 'Need help?', $this->plugin_name ) ?></a>
				<?php } ?>
				<?php do_action( 'clickwhale_admin_banner_button_pro' ) ?>
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

	public function admin_scripts() {
		if ( isset( $_GET['page'] ) && ( $_GET['page'] === 'clickwhale' || $_GET['page'] === 'clickwhale-linkpages' ) ) {
			?>
            <script type='text/javascript'>
                jQuery(document).ready(function () {
                    jQuery('.slug-input--btn').click(function (e) {
                        e.preventDefault();
                        var $temp = jQuery('<input>'),
                            textToCopy = jQuery(this).parent().find('input').val();

                        textToCopy = clickwhale_admin.siteurl + '/' + textToCopy;
                        jQuery('body').append($temp);
                        $temp.val(textToCopy).select();
                        document.execCommand("copy");
                        $temp.remove();
                    });
                });
            </script>
			<?php
		}
	}

}
