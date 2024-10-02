<?php
namespace clickwhale\includes\admin;

use clickwhale\includes\helpers\Helper;
use clickwhale\includes\helpers\traits\{Singleton_Clone, Singleton_Wakeup};

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

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
final class Clickwhale_Admin {

    /**
     * @var array
     */
    public $menus;

	/**
	 * @var Clickwhale_Admin
     *
     * @since    1.5.0
     */
	private static $instance;

	/**
	 * @return Clickwhale_Admin
     *
	 * @since    1.5.0
	 */
	public static function get_instance(): Clickwhale_Admin {
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
		$this->load_dependencies();
	}

    use Singleton_Clone;
    use Singleton_Wakeup;

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

		// Controllers
		if ( ! class_exists( 'WP_List_Table' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
		}

		// Abstract for all instances
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/Clickwhale_Instance_Edit.php';

		// Child classes
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/categories/Clickwhale_Categories_List_Table.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/categories/Clickwhale_Category_Edit.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/linkpages/Clickwhale_Linkpages_List_Table.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/linkpages/Clickwhale_Linkpage_Edit.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/links/Clickwhale_Links_List_Table.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/links/Clickwhale_Links_Bulk_Edit.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/links/Clickwhale_Link_Edit.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/tracking_codes/Clickwhale_Tracking_Codes_List_Table.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/tracking_codes/Clickwhale_Tracking_Code_Edit.php';
	}

    private function add_submenu_page( $parent, $k, $v ): void {
        add_submenu_page(
            $parent,
            $v,
            $v,
            'read',
            $k !== 'links' ? CLICKWHALE_SLUG . '-' . $k : CLICKWHALE_SLUG,
            array( $this, 'get_template' )
        );
    }

	/**
	 * Register plugin menus.
     * Introduces theme options into the 'Settings' menu and into a top-level 'Clickwhale' menu.
     *
     * @since    1.0.0
	 */
	public function add_plugin_menu() {

        $user = clickwhale()->user;
		$this->menus = apply_filters( 'clickwhale_menus', array(
			'subpages'  => array(
				'links'              => __( 'Links',                 CLICKWHALE_NAME ),
				'edit-link'          => __( 'Add New Link',          CLICKWHALE_NAME ),
				'categories'         => __( 'Categories',            CLICKWHALE_NAME ),
				'edit-category'      => __( 'Add New Category',      CLICKWHALE_NAME ),
				'linkpages'          => __( 'Link Pages',            CLICKWHALE_NAME ),
				'edit-linkpage'      => __( 'Add New Link Page',     CLICKWHALE_NAME ),
				'tracking-codes'     => __( 'Tracking Codes',        CLICKWHALE_NAME ),
				'edit-tracking-code' => __( 'Add New Tracking Code', CLICKWHALE_NAME )
			),
			'templates' => array(
                'toplevel_page_' . CLICKWHALE_SLUG                       => 'links/list',
                'admin_page_' . CLICKWHALE_SLUG . '-edit-link'           => 'links/edit',
                'clickwhale_page_' . CLICKWHALE_SLUG . '-categories'     => 'categories/list',
                'admin_page_' . CLICKWHALE_SLUG . '-edit-category'       => 'categories/edit',
                'clickwhale_page_' . CLICKWHALE_SLUG . '-linkpages'      => 'linkpages/list',
                'admin_page_' . CLICKWHALE_SLUG . '-edit-linkpage'       => 'linkpages/edit',
                'clickwhale_page_' . CLICKWHALE_SLUG . '-tracking-codes' => 'tracking-codes/list',
                'admin_page_' . CLICKWHALE_SLUG . '-edit-tracking-code'  => 'tracking-codes/edit'
			),
			'toplevel'  => array( 'links', 'categories', 'linkpages', 'tracking-codes' )
		) );

		// Add menu pages
		do_action( 'clickwhale_menu_before_all' );

		add_menu_page(
			__( 'ClickWhale Links', CLICKWHALE_NAME ),
			__( 'ClickWhale', CLICKWHALE_NAME ),
			'read',
			CLICKWHALE_SLUG,
			'',
			CLICKWHALE_ADMIN_ASSETS_DIR . '/images/click-icon.svg',
			26
		);

		foreach ( $this->menus['subpages'] as $k => $v ) {

            if ( in_array( $k, $this->menus['toplevel'] ) ) {
                $parent = CLICKWHALE_SLUG;
                $this->add_submenu_page( $parent, $k, $v );
                continue;
            }

            if ( empty( $_REQUEST['page'] ) ) {
                continue;
            }

            $page = $_REQUEST['page'];

            if ( ! strpos( $page, $k ) ) {
                continue;
            }

            $pos = strpos( $page, '-edit-' );

            if ( $pos === false ) {
                continue;
            }

            $instance_slug = substr( $page, $pos + strlen( '-edit-' ) );
            $parent = $this->menus['subpages']['edit-' . $instance_slug];

            if ( ! empty( $_REQUEST['id'] ) ) {
                $parent = 'Edit' . str_replace( 'Add New', '', $parent );
            }

			$this->add_submenu_page( $parent, $k, $v );
		}

		do_action( 'clickwhale_menu_before_settings' );

		if ( $user::is_current_user_role_access_granted() ) {
            add_submenu_page(
                CLICKWHALE_SLUG,
                __( 'Settings', CLICKWHALE_NAME ),
                __( 'Settings', CLICKWHALE_NAME ),
                'read',
                CLICKWHALE_SLUG . '-settings',
                array( $this, 'render_settings_page_template' )
            );
		}

		do_action( 'clickwhale_menu_before_tools' );

        if ( $user::is_current_user_role_access_granted() ) {
            add_submenu_page(
                CLICKWHALE_SLUG,
                __( 'Tools', CLICKWHALE_NAME ),
                __( 'Tools', CLICKWHALE_NAME ),
                'read',
                CLICKWHALE_SLUG . '-tools',
                array( $this, 'render_tools_page_template' )
            );
        }

		do_action( 'clickwhale_menu_after_all' );
	}

	public function show_pro_menu_item() {

        if ( clickwhale_fs()->can_use_premium_code() ) {
            return;
        }

        $user = clickwhale()->user;
        if ( $user::is_current_user_role_access_granted() ) {
            add_submenu_page(
                CLICKWHALE_SLUG,
                __( 'Upgrade to PRO', CLICKWHALE_NAME ),
                __( 'Upgrade to PRO', CLICKWHALE_NAME ),
                'read',
                CLICKWHALE_SLUG . '-pro',
                array( $this, 'render_pro_page_template' )
            );
        }
	}

	/**
	 * Include Menu Partial
	 *
	 * @since    1.0.0
	 */
	public function render_settings_page_template() {
		include_once( CLICKWHALE_TEMPLATES_DIR . '/admin/settings/settings.php' );
	}

	public function render_tools_page_template() {
		include_once( CLICKWHALE_TEMPLATES_DIR . '/admin/tools/tools.php' );
	}

	public function render_pro_page_template() {
		include_once( CLICKWHALE_TEMPLATES_DIR . '/admin/settings/pro.php' );
	}

	/**
	 * @return void
	 * @since 1.3.0
	 */
	public function get_template() {
		$current_template = $this->menus['templates'][current_filter()];
		include_once( CLICKWHALE_TEMPLATES_DIR . '/admin/' . $current_template . '.php' );
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_style(
            CLICKWHALE_NAME . '_select2',
			CLICKWHALE_ADMIN_ASSETS_DIR . '/css/select2/select2.min.css',
			array(),
			'4.1.0-rc.0'
		);
		wp_enqueue_style(
            CLICKWHALE_NAME,
			CLICKWHALE_ADMIN_ASSETS_DIR . '/css/clickwhale-admin.css',
			array(),
			CLICKWHALE_VERSION
		);
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		wp_enqueue_script( "jquery-ui-tabs" );

		if ( ! empty( $_GET['page'] ) && $_GET['page'] === CLICKWHALE_SLUG . '-edit-linkpage' ) {
			wp_enqueue_script( 'jquery-ui-droppable' );
			wp_enqueue_script( 'jquery-ui-draggable' );
			wp_enqueue_script( 'jquery-ui-sortable' );
			wp_enqueue_media();
			wp_enqueue_editor();
			wp_enqueue_script( 'wp-color-picker' );

			wp_enqueue_script(
                CLICKWHALE_NAME . '_picmo',
				CLICKWHALE_ADMIN_ASSETS_DIR . '/js/picmo/picmo.umd.min.js',
				array( 'jquery' ),
				'5.8.1'
			);
			wp_enqueue_script(
                CLICKWHALE_NAME . '_picmo_popup_picker',
				CLICKWHALE_ADMIN_ASSETS_DIR . '/js/picmo/popup-picker.umd.min.js',
				array( CLICKWHALE_NAME . '_picmo' ),
				'5.8.1'
			);
			wp_enqueue_script(
                CLICKWHALE_NAME . '_ionicons',
				CLICKWHALE_PUBLIC_ASSETS_DIR . '/js/ionicons/ionicons.js',
				array( 'jquery' ),
				'7.1.0'
			);
		}

		if ( ! empty( $_GET['page'] ) && $_GET['page'] === CLICKWHALE_SLUG . '-edit-link' ) {
			wp_enqueue_script( 'jquery-ui-sortable' );
		}

		if ( ! empty( $_GET['page'] ) && $_GET['page'] === CLICKWHALE_SLUG . '-edit-tracking-code' ) {
			wp_enqueue_code_editor( array( 'type' => 'text/html' ) );
		}

		wp_enqueue_script(
            CLICKWHALE_NAME . '_select2',
			CLICKWHALE_ADMIN_ASSETS_DIR . '/js/select2/select2.min.js',
			array( 'jquery' ),
			'4.1.0-rc.0'
		);
		wp_enqueue_script(
            CLICKWHALE_NAME,
			CLICKWHALE_ADMIN_ASSETS_DIR . '/js/clickwhale-admin.js',
			array( 'jquery' ),
			CLICKWHALE_VERSION
		);
		wp_localize_script(
            CLICKWHALE_NAME,
			'clickwhale_admin', array(
				'siteurl'     => home_url(),
				'plugin_slug' => CLICKWHALE_SLUG
			)
		);
	}

	public function admin_banner() {
		$link_logo     = 'https://clickwhale.pro/?utm_source=user+site&utm_medium=admin+pages&utm_campaign=ClickWhale+-+Free+Version&utm_term=logo-link';
		$link_helpdesk = 'https://clickwhale.pro/docs/?utm_source=users&utm_medium=button&utm_campaign=plugin_admin&utm_content=header_need_help';
		$link_review   = 'https://wordpress.org/support/plugin/clickwhale/reviews/#new-post';
		?>

        <div class="clickwhale-banner">
            <div class="clickwhale-banner--logo">
                <a href="<?php echo $link_logo ?>"
                   target="_blank"
                   rel="noopener">
                    <img src="<?php echo esc_attr( CLICKWHALE_ADMIN_ASSETS_DIR . '/images/wordmark.svg' ) ?>"
                         alt="<?php echo CLICKWHALE_NAME ?>">
                </a>
            </div>
            <div class="clickwhale-banner--links">
				<?php if ( $link_review ) { ?>
                    <div class="clickwhale-banner--link-review">
						<?php printf(
                            wp_kses(
                                __( 'You like ClickWhale? Then please <a href="%1$s" target="_blank">leave a review here</a>', CLICKWHALE_NAME ),
                                array(
                                    'a' => array(
                                        'href' => array(),
                                        'target' => array( '_blank' )
                                    )
                                )
                            ),
                            esc_url( $link_review )
                        ); ?>
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
						<?php _e( 'Need help?', CLICKWHALE_NAME ) ?>
                    </a>
				<?php } ?>

				<?php do_action( 'clickwhale_admin_banner_pro_button' ) ?>
            </div>
        </div>
		<?php
	}

	public function admin_banner_pro_button() {

        if ( clickwhale_fs()->can_use_premium_code() ) {
            return;
        }
		?>
        <a href="<?php echo esc_attr( Helper::get_pro_link() ) ?>"
           class="clickwhale-banner--button"
           target="_blank">
			<?php _e( 'Upgrade to PRO', CLICKWHALE_NAME ) ?>
        </a>
		<?php
	}

	public function admin_pro_message() {
		?>
        <div class="clickwhale-linkpage--message">
			<?php _e( 'Available only in PRO version', CLICKWHALE_NAME ); ?>
        </div>
		<?php
	}

	public function admin_sidebar_begin() {
        ?>
            <div id="poststuff">
                <div id="post-body" class="metabox-holder columns-2">
                    <div id="post-body-content">
        <?php
    }

	public function admin_sidebar_end() {
        ?>
                    </div><!-- /#post-body-content -->
                    <div id="postbox-container-1" class="postbox-container">
                        <?php do_action( 'clickwhale_admin_sidebar_area' ); ?>
                    </div><!-- /.postbox-container -->
                </div><!-- /#post-body -->
            </div><!-- /#poststuff -->
        <?php
    }

	public function admin_widget_upgrade() {
        if ( clickwhale_fs()->can_use_premium_code() ) {
            return;
        } ?>
        <div class="postbox clickwhale-admin-widget" id="clickwhale-admin-widget__upgrade">
            <div class="hero">
                <img src="<?php echo esc_attr( CLICKWHALE_ADMIN_ASSETS_DIR . '/images/widgets/upgrade_to_pro_widget_hero.svg' ) ?>"
                     alt="<?php echo CLICKWHALE_NAME ?>">
            </div>
            <h3 class="title"><?php esc_attr_e( 'Upgrade to ClickWhale Pro', CLICKWHALE_NAME ); ?></h3>
            <div class="inside">
                <ul>
                    <li><span class="text"><?php esc_attr_e( 'Detailed Statistics', CLICKWHALE_NAME ); ?></span></li>
                    <li><span class="text"><?php esc_attr_e( 'UTM Campaign Tracking', CLICKWHALE_NAME ); ?></span></li>
                    <li><span class="text"><?php esc_attr_e( 'E-Commerce Conversion Tracking', CLICKWHALE_NAME ); ?></span></li>
                    <li><span class="text"><?php esc_attr_e( 'Advanced Customization Options', CLICKWHALE_NAME ); ?></span></li>
                    <li><span class="text"><?php esc_attr_e( 'More Blocks for Link Pages', CLICKWHALE_NAME ); ?></span></li>
                    <li><span class="text"><?php esc_attr_e( 'Remove Plugin Credits', CLICKWHALE_NAME ); ?></span></li>
                </ul>

                <div class="clickwhale-pro-button">
                    <a href="https://clickwhale.pro/upgrade/?utm_source=users&utm_medium=button&utm_campaign=plugin_admin&utm_content=upgrade_to_pro_widget"
                       class="button-get-pro"
                       rel="noopener"><?php esc_attr_e( 'View Upgrade', CLICKWHALE_NAME ); ?> 🚀</a>
                </div>
            </div>
        </div>
		<?php
	}

	public function admin_widget_docs() {
        ?>
        <div class="postbox clickwhale-admin-widget" id="clickwhale-admin-widget__docs">
            <div class="hero">
                <img src="<?php echo esc_attr( CLICKWHALE_ADMIN_ASSETS_DIR . '/images/widgets/docs_widget_hero.png' ) ?>"
                     alt="<?php echo CLICKWHALE_NAME ?>">
            </div>
            <h3 class="title"><?php esc_attr_e( 'Plugin Documentation', CLICKWHALE_NAME ); ?></h3>
            <div class="inside">
                <ul>
                    <li><a href="https://clickwhale.pro/docs/article/how-to-shorten-links-and-create-redirects/?utm_source=users&utm_medium=button&utm_campaign=plugin_admin&utm_content=widget_documentation"
                           class="text"
                           target="_blank"
                           rel="nofollow"
                           title="<?php esc_attr_e( 'How To Shorten Links & Create Redirects', CLICKWHALE_NAME ); ?>"><?php esc_attr_e( 'How To Shorten Links & Create Redirects', CLICKWHALE_NAME ); ?></a></li>

                    <li><a href="https://clickwhale.pro/docs/article/creating-your-first-link-page/?utm_source=users&utm_medium=button&utm_campaign=plugin_admin&utm_content=widget_documentation"
                           class="text"
                           target="_blank"
                           rel="nofollow"
                           title="<?php esc_attr_e( 'Creating Your First Link Page', CLICKWHALE_NAME ); ?>"><?php esc_attr_e( 'Creating Your First Link Page', CLICKWHALE_NAME ); ?></a></li>
                    <li><a href="https://clickwhale.pro/docs/article/add-google-tag-manager-to-wordpress/?utm_source=users&utm_medium=button&utm_campaign=plugin_admin&utm_content=widget_documentation"
                           class="text"
                           target="_blank"
                           rel="nofollow"
                           title="<?php esc_attr_e( 'How To Add Google Tag Manager To WordPress with ClickWhale', CLICKWHALE_NAME ); ?>"><?php esc_attr_e( 'How To Add Google Tag Manager To WordPress with ClickWhale', CLICKWHALE_NAME ); ?></a></li>
                </ul>

                <div class="clickwhale-pro-button">
                    <a href="https://clickwhale.pro/docs/?utm_source=users&utm_medium=button&utm_campaign=plugin_admin&utm_content=widget_documentation"
                       class="button-get-pro"
                       rel="noopener"
                       target="_blank"><?php esc_attr_e( 'View all Articles', CLICKWHALE_NAME ); ?></a>
                </div>
            </div>
        </div>
		<?php
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
			wp_redirect( admin_url( 'admin.php?page=' . CLICKWHALE_SLUG . '-pro&success=1#clickwhaleSubscribe' ) );
		}
	}

	/**
	 * Plugin links
	 * @since 1.4.1
	 */
	public function settings_action_link( $links ) {

        if ( clickwhale_fs()->is_activation_mode() ) {
            return $links;
        }

		$url           = esc_url( admin_url( 'admin.php?page=' . CLICKWHALE_SLUG . '-settings' ) );
		$settings_link = '<a href="' . $url . '" rel="noopener">' . __( 'Settings', CLICKWHALE_NAME ) . '</a>';
		array_unshift( $links, $settings_link );

		return $links;
	}

	public function upgrade_action_link( $links ) {

        if ( clickwhale_fs()->is_activation_mode() ) {
            return $links;
        }

        if ( clickwhale_fs()->can_use_premium_code() ) {
            return $links;
        }

        $url           = esc_url( admin_url( 'admin.php?page=' . CLICKWHALE_SLUG . '-pro' ) );
		$text          = __( 'Upgrade to PRO', CLICKWHALE_NAME );
		$settings_link = '<a href="' . $url . '" rel="noopener" style="color: #007AFF; font-weight: 700;">' . $text . '</a>';
		$links[]       = $settings_link;

		return $links;
	}

	/**
	 * @return void
	 * @since 1.4.1
	 */
	public function hide_notice_on_upgrade_to_pro_page() {
		if ( isset( $_GET['page'] ) && $_GET['page'] === CLICKWHALE_SLUG . '-pro' ) {
			remove_all_actions( 'user_admin_notices' );
			remove_all_actions( 'admin_notices' );
		}
	}

    public function plugin_meta_links( array $meta, string $file ) {

        if ( $file !== CLICKWHALE_ID ) {
            return $meta;
        }

        $meta[] = '<a href="https://clickwhale.pro/docs/" target="_blank" rel="nofollow" title="' . esc_html__( 'Documentation', CLICKWHALE_NAME ) . '">' . esc_html__( 'Documentation', CLICKWHALE_NAME ) . '</a>';
        $meta[] = '<a href="https://wordpress.org/support/plugin/clickwhale/reviews/?filter=5" rel="nofollow" target="_blank" title="' . esc_html__( 'Rate ClickWhale on WordPress.org', CLICKWHALE_NAME ) . '" style="color: #ffb900">'
            . str_repeat( '<span class="dashicons dashicons-star-filled" style="font-size: 16px; width:16px; height: 16px"></span>', 5 )
            . '</a>';

        return $meta;
    }

	public function admin_scripts() {
		if ( empty( $_GET['page'] ) ) {
			return;
		}
		if ( $_GET['page'] === CLICKWHALE_SLUG || $_GET['page'] === CLICKWHALE_SLUG . '-linkpages' ) {
			?>
            <script type='text/javascript'>
                jQuery(document).ready(function() {
                    jQuery('.slug-input--btn').on('click', function(e) {
                        e.preventDefault();
                        let
                            $temp = jQuery('<input>'),
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
		if ( $_GET['page'] === CLICKWHALE_SLUG . '-edit-link' || $_GET['page'] === CLICKWHALE_SLUG . '-edit-linkpage' ) {
			?>
            <script type='text/javascript'>
                jQuery(document).ready(function() {
                    jQuery('#copy-link-url').on('click', function(e) {
                        e.preventDefault();

                        // Remove appended message
                        jQuery('.copied').remove();

                        // Copy slug
                        copySlug();

                        // Append message
                        jQuery('<span class="copied"><?php _e( 'Copied!', CLICKWHALE_NAME ) ?></span>')
                            .insertAfter(jQuery(this));

                        // Hide appended message
                        setTimeout(function() {
                            jQuery('.copied').remove();
                        }, 2000);
                    });

                    jQuery('#cw-slug--text').on('click', function(e) {
                        e.preventDefault();

                        // Remove appended message
                        jQuery('.copied').remove();

                        // Copy slug
                        copySlug();

                        // Append message
                        jQuery(this)
                            .append('<span class="copied"><?php _e( 'Copied!', CLICKWHALE_NAME ) ?></span>');

                        // Hide appended message
                        setTimeout(function() {
                            jQuery('.copied').remove();
                        }, 2000);
                    });

                    function copySlug() {
                        const temp = jQuery('<input>');
                        let textToCopy = jQuery('#cw-slug').val();

                        textToCopy = clickwhale_admin.siteurl + '/' + textToCopy + '/';
                        jQuery('body').append(temp);
                        temp.val(textToCopy).select();
                        document.execCommand("copy");
                        temp.remove();
                    }
                });
            </script>
			<?php
		}
		if ( $_GET['page'] === CLICKWHALE_SLUG . '-tracking-codes' ) {
			$nonce = wp_create_nonce( 'clickwhale_toggle_tracking_code' );
			?>
            <script type='text/javascript'>
                jQuery(document).ready(function() {
                    jQuery('.clickwhale-checkbox--toggle [type="checkbox"]').on('change', function() {
                        let
                            active = this.checked,
                            id = this.dataset.id;

                        jQuery.post(ajaxurl, {
                            'security': '<?php echo $nonce ?>',
                            'action': 'clickwhale/admin/tracking_code_toggle_active',
                            'status': active ? 1 : 0,
                            'id': id
                        }, function(response) {
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
