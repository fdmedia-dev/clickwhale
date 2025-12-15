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
     * @var Clickwhale_Admin
     * @since    1.5.0
     */
    private static Clickwhale_Admin $instance;

    /**
     * @var array
     */
    public array $menus;

    /**
     * @var Clickwhale_WP_User
     */
    private Clickwhale_WP_User $user;

    /**
     * @return Clickwhale_Admin
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
     * @since    1.0.0
     */
    private function __construct() {
        $this->load_dependencies();
        $this->user = clickwhale()->user;
    }

    use Singleton_Clone;
    use Singleton_Wakeup;

    /**
     * Load the required dependencies for the Admin facing functionality.
     * Include the following files that make up the plugin:
     *   Clickwhale_Ajax. Plugin Ajax actions
     *   Clickwhale_Admin_Settings. Registers the admin settings and page.
     *   Clickwhale_Admin_Tools. Registers the admin tools page and its subpages.
     *   Clickwhale_Admin_Migration. Migrate links and categories to our plugin
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

        // ClickWhale REST API
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/Clickwhale_Rest_Controller.php';
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
     * @since    1.0.0
     */
    public function add_plugin_menu() {
        if ( ! $this->user->is_current_user_role_access_granted() ) {
            return;
        }

        $this->menus = apply_filters( 'clickwhale_menus', array(
            'subpages' => array(
                'links'              => __( 'Links', 'clickwhale' ),
                'edit-link'          => __( 'Add New Link', 'clickwhale' ),
                'categories'         => __( 'Categories', 'clickwhale' ),
                'edit-category'      => __( 'Add New Category', 'clickwhale' ),
                'linkpages'          => __( 'Link Pages', 'clickwhale' ),
                'edit-linkpage'      => __( 'Add New Link Page', 'clickwhale' ),
                'tracking-codes'     => __( 'Tracking Codes', 'clickwhale' ),
                'edit-tracking-code' => __( 'Add New Tracking Code', 'clickwhale' )
            ),
            'edit_titles' => array(
                'edit-link'          => __( 'Edit Link', 'clickwhale' ),
                'edit-category'      => __( 'Edit Category', 'clickwhale' ),
                'edit-linkpage'      => __( 'Edit Link Page', 'clickwhale' ),
                'edit-tracking-code' => __( 'Edit Tracking Code', 'clickwhale' )
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
            'toplevel' => array( 'links', 'categories', 'linkpages', 'tracking-codes' )
        ) );

        // Add menu pages
        do_action( 'clickwhale_menu_before_all' );

        add_menu_page(
            __( 'ClickWhale Links', 'clickwhale' ),
            __( 'ClickWhale', 'clickwhale' ),
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

            if ( empty( $_GET['page'] ) ) {
                continue;
            }

            $page = sanitize_key( $_GET['page'] );

            if ( ! strpos( $page, $k ) ) {
                continue;
            }

            $pos = strpos( $page, '-edit-' );

            if ( $pos === false ) {
                continue;
            }

            $instance_slug = substr( $page, $pos + strlen( '-edit-' ) );

            if ( isset( $_GET['id'] ) && intval( $_GET['id'] ) > 0 ) {
                $parent = $this->menus['edit_titles']['edit-' . $instance_slug];
            } else {
                $parent = $this->menus['subpages']['edit-' . $instance_slug];
            }

            $this->add_submenu_page( $parent, $k, $v );
        }

        do_action( 'clickwhale_menu_before_settings' );

        add_submenu_page(
            CLICKWHALE_SLUG,
            __( 'Settings', 'clickwhale' ),
            __( 'Settings', 'clickwhale' ),
            'read',
            CLICKWHALE_SLUG . '-settings',
            array( $this, 'render_settings_page_template' )
        );

        do_action( 'clickwhale_menu_before_tools' );

        add_submenu_page(
            CLICKWHALE_SLUG,
            __( 'Tools', 'clickwhale' ),
            __( 'Tools', 'clickwhale' ),
            'read',
            CLICKWHALE_SLUG . '-tools',
            array( $this, 'render_tools_page_template' )
        );

        do_action( 'clickwhale_menu_after_all' );
    }

    public function show_pro_menu_item() {
        if ( clickwhale_fs()->can_use_premium_code() ) {
            return;
        }

        add_submenu_page(
            CLICKWHALE_SLUG,
            __( 'Upgrade to PRO', 'clickwhale' ),
            __( 'Upgrade to PRO', 'clickwhale' ),
            'read',
            CLICKWHALE_SLUG . '-pro',
            array( $this, 'render_pro_page_template' )
        );
    }

    /**
     * Include Menu Partial.
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
     * @since    1.0.0
     */
    public function enqueue_styles() {
        if ( empty( $_GET['page'] ) ) {
            return;
        }

        if ( 0 !== strpos( sanitize_key( $_GET['page'] ), CLICKWHALE_SLUG ) ) {
            return;
        }

        wp_enqueue_style( 'wp-color-picker' );
        wp_enqueue_style(
            'clickwhale_select2',
            CLICKWHALE_ADMIN_ASSETS_DIR . '/css/select2/select2.min.css',
            array(),
            '4.1.0-rc.0'
        );
        wp_enqueue_style(
            'clickwhale',
            CLICKWHALE_ADMIN_ASSETS_DIR . '/css/clickwhale-admin.css',
            array(),
            CLICKWHALE_VERSION
        );
    }

    /**
     * Register the JavaScript for the admin area.
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        if ( empty( $_GET['page'] ) ) {
            return;
        }

        $page = sanitize_key( $_GET['page'] );

        if ( '' === $page ) {
            return;
        }

        if ( 0 !== strpos( $page, CLICKWHALE_SLUG ) ) {
            return;
        }

        wp_enqueue_script( 'jquery-ui-tabs' );

        if ( $page === CLICKWHALE_SLUG . '-edit-linkpage' ) {
            wp_enqueue_script( 'jquery-ui-droppable' );
            wp_enqueue_script( 'jquery-ui-draggable' );
            wp_enqueue_script( 'jquery-ui-sortable' );
            wp_enqueue_media();
            wp_enqueue_editor();
            wp_enqueue_script( 'wp-color-picker' );

            wp_enqueue_script(
                'clickwhale_picmo',
                CLICKWHALE_ADMIN_ASSETS_DIR . '/js/picmo/picmo.umd.min.js',
                array( 'jquery' ),
                '5.8.1'
            );
            wp_enqueue_script(
                'clickwhale_picmo_popup_picker',
                CLICKWHALE_ADMIN_ASSETS_DIR . '/js/picmo/popup-picker.umd.min.js',
                array( 'clickwhale_picmo' ),
                '5.8.1'
            );
            wp_enqueue_script(
                'clickwhale_ionicons',
                CLICKWHALE_PUBLIC_ASSETS_DIR . '/js/ionicons/ionicons.js',
                array( 'jquery' ),
                '7.1.0'
            );
        }

        if ( $page === CLICKWHALE_SLUG . '-edit-link' ) {
            wp_enqueue_script( 'jquery-ui-sortable' );
        }

        if ( $page === CLICKWHALE_SLUG . '-edit-tracking-code' ) {
            wp_enqueue_code_editor( array( 'type' => 'text/html' ) );
        }

        wp_enqueue_script(
            'clickwhale_select2',
            CLICKWHALE_ADMIN_ASSETS_DIR . '/js/select2/select2.min.js',
            array( 'jquery' ),
            '4.1.0-rc.0'
        );
        wp_enqueue_script(
            'clickwhale',
            CLICKWHALE_ADMIN_ASSETS_DIR . '/js/clickwhale-admin.js',
            array( 'jquery' ),
            CLICKWHALE_VERSION
        );
        wp_localize_script(
            'clickwhale',
            'clickwhale_admin', array(
                'siteurl'     => home_url(),
                'plugin_slug' => CLICKWHALE_SLUG
            )
        );
    }

    /**
     * Extend the stylesheets for Freemius pricing page
     *
     * @param string $template
     * @return string
     */
    public function enqueue_fs_pricing_styles( string $template ): string {
        ob_start();
        ?>
<style>
#root .fs-app-header .fs-page-title,
#fs_pricing_app .fs-app-header .fs-page-title {
    display: block !important;
}
#root .fs-app-header .fs-page-title h1,
#fs_pricing_app .fs-app-header .fs-page-title h1 {
    font-size: 2.5em !important;
}
#root .fs-app-header .fs-page-title h3,
#fs_pricing_app .fs-app-header .fs-page-title h3 {
    color:  #1A1C1D !important;
    font-size: small !important;
    font-weight: 600 !important;
}
#root .fs-app-main .fs-section--plans-and-pricing .fs-section--billing-cycles .fs-billing-cycles li.fs-selected-billing-cycle,
#fs_pricing_app .fs-app-main .fs-section--plans-and-pricing .fs-section--billing-cycles .fs-billing-cycles li.fs-selected-billing-cycle {
    background: #4B7CF7 0 0 no-repeat padding-box !important;
    color: #FFFFFF !important;
}
#root .fs-app-main .fs-section--plans-and-pricing .fs-section--billing-cycles .fs-billing-cycles li:hover,
#fs_pricing_app .fs-app-main .fs-section--plans-and-pricing .fs-section--billing-cycles .fs-billing-cycles li:hover {
    color: #FFFFFF !important;
}
#root .fs-app-main .fs-section--plans-and-pricing .fs-section--billing-cycles .fs-billing-cycles li:not(.fs-selected-billing-cycle):hover,
#fs_pricing_app .fs-app-main .fs-section--plans-and-pricing .fs-section--billing-cycles .fs-billing-cycles li:not(.fs-selected-billing-cycle):hover {
    background-color: #1A1C1D !important;
}
#root .fs-package,
#fs_pricing_app .fs-package {
    margin: 2.8em 0.8em 0 !important;
    border-radius: 20px !important;
    box-shadow: 0 0 1px #00000029 !important;
}
#root .fs-package.fs-featured-plan,
#fs_pricing_app .fs-package.fs-featured-plan {
    background: #FDD231 0 0 no-repeat padding-box !important;
}
#root .fs-package.fs-featured-plan .fs-most-popular,
#fs_pricing_app .fs-package.fs-featured-plan .fs-most-popular {
    background: #4B7CF7 0 0 no-repeat padding-box !important;
    opacity: 1 !important;
    border-radius: 20px 20px 0 0 !important;
    font-size: 1.2em !important;
    text-transform: uppercase !important;
    color: #FFFFFF !important;
    line-height: 2.6em !important;
    margin: -2.5em 0 -1px 0 !important;
}
#root .fs-package.fs-featured-plan .fs-most-popular h4,
#fs_pricing_app .fs-package.fs-featured-plan .fs-most-popular h4 {
    color: #FFFFFF !important;
}
#root .fs-package .fs-plan-title,
#fs_pricing_app .fs-package .fs-plan-title {
    background: #f8f8f9 !important;
    text-transform: capitalize !important;
}
#root .fs-package:not(.fs-featured-plan) .fs-plan-title,
#fs_pricing_app .fs-package:not(.fs-featured-plan) .fs-plan-title {
    border-radius: 20px 20px 0 0 !important;
}
#root .fs-package.fs-featured-plan .fs-plan-title,
#fs_pricing_app .fs-package.fs-featured-plan .fs-plan-title {
    background: #1A1C1D 0 0 no-repeat padding-box !important;
    color: #FFFFFF !important;
    border-color: transparent !important;
    border-radius: 0 !important;
}
#root .fs-package .fs-selected-pricing-cycle,
#fs_pricing_app .fs-package .fs-selected-pricing-cycle {
    text-transform: capitalize !important;
}
#root .fs-package .fs-selected-pricing-license-quantity,
#fs_pricing_app .fs-package .fs-selected-pricing-license-quantity {
    color: #47AED6 !important;
}
#root .fs-package .fs-plan-features li .fs-icon,
#root .fs-package .fs-plan-features li .fs-tooltip,
#fs_pricing_app .fs-package .fs-plan-features li .fs-icon,
#fs_pricing_app .fs-package .fs-plan-features li .fs-tooltip {
    color: #47AED6 !important;
}
#root .fs-package.fs-featured-plan .fs-selected-pricing-license-quantity,
#root .fs-package.fs-featured-plan .fs-tooltip .fs-icon,
#root .fs-package.fs-featured-plan .fs-tooltip .fs-icon,
#root .fs-package.fs-featured-plan .fs-plan-features li .fs-icon,
#fs_pricing_app .fs-package.fs-featured-plan .fs-selected-pricing-license-quantity,
#fs_pricing_app .fs-package.fs-featured-plan .fs-tooltip .fs-icon,
#fs_pricing_app .fs-package.fs-featured-plan .fs-tooltip .fs-icon,
#fs_pricing_app .fs-package.fs-featured-plan .fs-plan-features li .fs-icon {
    color: #4B7CF7 !important;
}
#root .fs-package.fs-featured-plan .fs-tooltip .fs-icon path,
#root .fs-package.fs-featured-plan .fs-tooltip .fs-icon path,
#fs_pricing_app .fs-package.fs-featured-plan .fs-tooltip .fs-icon path,
#fs_pricing_app .fs-package.fs-featured-plan .fs-tooltip .fs-icon path {
    fill: #4B7CF7 !important;
}
#root .fs-package .fs-upgrade-button-container .fs-upgrade-button,
#fs_pricing_app .fs-package .fs-upgrade-button-container .fs-upgrade-button {
    background: #4B7CF7 0 0 no-repeat padding-box !important;
    color: #FFFFFF !important;
    border: 1px solid #4B7CF7 !important;
    border-radius: 10px !important;
}
#root .fs-package .fs-upgrade-button-container .fs-upgrade-button:hover,
#fs_pricing_app .fs-package .fs-upgrade-button-container .fs-upgrade-button:hover {
    background-color: #1A1C1D !important;
    border-color: #1A1C1D !important;
}
</style>
        <?php
        $style = ob_get_clean();
        return $template . $style;
    }

    public function override_fs_plugin_icon() {
        return CLICKWHALE_DIR . 'assets/admin/images/clickwhale.jpg';
    }

    public function admin_banner() {
        $link_logo     = 'https://clickwhale.pro/?utm_source=users&utm_medium=admin+pages&utm_campaign=ClickWhale+-+Free+Version&utm_term=logo-link';
        $link_helpdesk = 'https://clickwhale.pro/docs/?utm_source=users&utm_medium=button&utm_campaign=plugin_admin&utm_content=header_need_help';
        $link_review   = 'https://wordpress.org/support/plugin/clickwhale/reviews/#new-post';
        ?>

        <div class="clickwhale-banner">
            <div class="clickwhale-banner--logo">
                <a href="<?php echo esc_url( $link_logo ); ?>"
                   target="_blank"
                   rel="noopener"
                ><img src="<?php echo esc_url( CLICKWHALE_ADMIN_ASSETS_DIR . '/images/wordmark.svg' ); ?>"
                      alt="clickwhale"
                    ></a>
            </div>
            <div class="clickwhale-banner--links">
                <div class="clickwhale-banner--link-review">
                    <?php echo wp_kses(
                        sprintf(
                            __( 'You like ClickWhale? Then please <a href="%s" target="_blank">leave a review here</a>', 'clickwhale' ),
                            esc_url( $link_review )
                        ),
                        array(
                            'a' => array(
                                'href' => array(),
                                'target' => array( '_blank' )
                            )
                        )
                    );
                    ?>
                    <span class="clickwhale-banner--link-review--rating">
                        <span class="dashicons dashicons-star-filled"></span>
                        <span class="dashicons dashicons-star-filled"></span>
                        <span class="dashicons dashicons-star-filled"></span>
                        <span class="dashicons dashicons-star-filled"></span>
                        <span class="dashicons dashicons-star-filled"></span>
                    </span>
                </div>
                <a href="<?php echo esc_url( $link_helpdesk ); ?>"
                   class="clickwhale-banner--button outlined dark"
                   target="_blank"
                   rel="noopener"
                ><?php esc_html_e( 'Need help?', 'clickwhale' ); ?></a>

                <?php do_action( 'clickwhale_admin_banner_pro_button' ); ?>
            </div>
        </div>
        <?php
    }

    public function admin_banner_pro_button() {
        if ( clickwhale_fs()->can_use_premium_code() ) {
            return;
        }
        ?>
        <a href="<?php echo esc_url( Helper::get_pro_link() ); ?>"
           class="clickwhale-banner--button"
           target="_blank">
            <?php esc_html_e( 'Upgrade to PRO', 'clickwhale' ); ?>
        </a>
        <?php
    }

    public function admin_pro_message() {
        ?>
        <div class="clickwhale-linkpage--message">
            <?php esc_html_e( 'Available only in PRO version', 'clickwhale' ); ?>
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
                <img src="<?php echo esc_url( CLICKWHALE_ADMIN_ASSETS_DIR . '/images/widgets/upgrade_to_pro_widget_hero.svg' ); ?>"
                     alt="clickwhale">
            </div>
            <h3 class="title"><?php esc_attr_e( 'Upgrade to ClickWhale Pro', 'clickwhale' ); ?></h3>
            <div class="inside">
                <ul>
                    <li><span class="text"><?php esc_attr_e( 'Detailed Statistics', 'clickwhale' ); ?></span></li>
                    <li><span class="text"><?php esc_attr_e( 'Keyword Auto Linker', 'clickwhale' ); ?></span></li>
                    <li><span class="text"><?php esc_attr_e( 'UTM Campaign Tracking', 'clickwhale' ); ?></span></li>
                    <li><span class="text"><?php esc_attr_e( 'E-Commerce Conversion Tracking', 'clickwhale' ); ?></span></li>
                    <li><span class="text"><?php esc_attr_e( 'Advanced Customization Options', 'clickwhale' ); ?></span></li>
                    <li><span class="text"><?php esc_attr_e( 'More Blocks for Link Pages', 'clickwhale' ); ?></span></li>
                    <li><span class="text"><?php esc_attr_e( 'Remove Plugin Credits', 'clickwhale' ); ?></span></li>
                </ul>

                <div class="clickwhale-pro-button">
                    <a href="https://clickwhale.pro/upgrade/?utm_source=users&utm_medium=button&utm_campaign=plugin_admin&utm_content=upgrade_to_pro_widget"
                       class="button-get-pro"
                       rel="noopener"><?php esc_attr_e( 'Upgrade Now', 'clickwhale' ); ?> 🚀</a>
                </div>
            </div>
        </div>
        <?php
    }

    public function admin_widget_docs() {
        ?>
        <div class="postbox clickwhale-admin-widget" id="clickwhale-admin-widget__docs">
            <div class="hero">
                <img src="<?php echo esc_url( CLICKWHALE_ADMIN_ASSETS_DIR . '/images/widgets/docs_widget_hero.png' ); ?>"
                     alt="<?php echo 'clickwhale'; ?>">
            </div>
            <h3 class="title"><?php esc_attr_e( 'Plugin Documentation', 'clickwhale' ); ?></h3>
            <div class="inside">
                <ul>
                    <li><a href="https://clickwhale.pro/docs/article/how-to-shorten-links-and-create-redirects/?utm_source=users&utm_medium=button&utm_campaign=plugin_admin&utm_content=widget_documentation"
                           class="text"
                           target="_blank"
                           rel="nofollow"
                           title="<?php esc_attr_e( 'How-To Shorten Links & Create Redirects', 'clickwhale' ); ?>"><?php esc_attr_e( 'How-To Shorten Links & Create Redirects', 'clickwhale' ); ?></a></li>

                    <li><a href="https://clickwhale.pro/docs/article/how-to-import-links/?utm_source=users&utm_medium=button&utm_campaign=plugin_admin&utm_content=widget_documentation"
                           class="text"
                           target="_blank"
                           rel="nofollow"
                           title="<?php esc_attr_e( 'How-To Import Links', 'clickwhale' ); ?>"><?php esc_attr_e( 'How-To Import Links', 'clickwhale' ); ?></a></li>

                    <li><a href="https://clickwhale.pro/docs/article/how-to-use-the-keyword-auto-linker/?utm_source=users&utm_medium=button&utm_campaign=plugin_admin&utm_content=widget_documentation"
                           class="text"
                           target="_blank"
                           rel="nofollow"
                           title="<?php esc_attr_e( 'How-To Use the Keyword Auto Linker', 'clickwhale' ); ?>"><?php esc_attr_e( 'How-To Use the Keyword Auto Linker', 'clickwhale' ); ?></a></li>

                    <li><a href="https://clickwhale.pro/docs/article/creating-your-first-link-page/?utm_source=users&utm_medium=button&utm_campaign=plugin_admin&utm_content=widget_documentation"
                           class="text"
                           target="_blank"
                           rel="nofollow"
                           title="<?php esc_attr_e( 'Creating Your First Link Page', 'clickwhale' ); ?>"><?php esc_attr_e( 'Creating Your First Link Page', 'clickwhale' ); ?></a></li>
                    <li><a href="https://clickwhale.pro/docs/article/add-google-tag-manager-to-wordpress/?utm_source=users&utm_medium=button&utm_campaign=plugin_admin&utm_content=widget_documentation"
                           class="text"
                           target="_blank"
                           rel="nofollow"
                           title="<?php esc_attr_e( 'How-To Add Google Tag Manager To WordPress with ClickWhale', 'clickwhale' ); ?>"><?php esc_attr_e( 'How-To Add Google Tag Manager To WordPress with ClickWhale', 'clickwhale' ); ?></a></li>
                </ul>

                <div class="clickwhale-pro-button">
                    <a href="https://clickwhale.pro/docs/?utm_source=users&utm_medium=button&utm_campaign=plugin_admin&utm_content=widget_documentation"
                       class="button-get-pro"
                       rel="noopener"
                       target="_blank"><?php esc_attr_e( 'View all Articles', 'clickwhale' ); ?></a>
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
        $current_user = $this->user->get_user();
        $url = "https://clickwhale.pro/?fluentcrm=1&route=contact&hash=e2920f25-a285-4568-bea4-ede017a039fb";
        $response = wp_remote_post( $url, array(
                'method' => 'POST',
                'body'   => array(
                    'email'      => sanitize_email( $_POST['email'] ),
                    'first_name' => ( $current_user->exists() ) ? $current_user->first_name : '',
                )
            )
        );

        if ( is_wp_error( $response ) ) {
            $error_message = $response->get_error_message();
            echo "Something went wrong: $error_message";
        } else {
            wp_redirect( admin_url( 'admin.php?page=' . CLICKWHALE_SLUG . '-pro&success=1#clickwhaleSubscribe' ) );
            exit;
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

        $url = esc_url( admin_url( 'admin.php?page=' . CLICKWHALE_SLUG . '-settings' ) );
        $settings_link = '<a href="' . $url . '" rel="noopener">' . __( 'Settings', 'clickwhale' ) . '</a>';
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

        $url = esc_url( admin_url( 'admin.php?page=' . CLICKWHALE_SLUG . '-pro' ) );
        $text = __( 'Upgrade to PRO', 'clickwhale' );
        $settings_link = '<a href="' . $url . '" rel="noopener" style="color: #007AFF; font-weight: 700;">' . $text . '</a>';
        $links[] = $settings_link;

        return $links;
    }

    /**
     * @return void
     * @since 1.4.1
     */
    public function hide_notice_on_upgrade_to_pro_page() {
        if ( isset( $_GET['page'] ) && sanitize_key( $_GET['page'] ) === CLICKWHALE_SLUG . '-pro' ) {
            remove_all_actions( 'user_admin_notices' );
            remove_all_actions( 'admin_notices' );
        }
    }

    public function plugin_meta_links( array $meta, string $file ): array {
        if ( $file !== CLICKWHALE_ID ) {
            return $meta;
        }

        $meta[] = '<a href="https://clickwhale.pro/docs/" target="_blank" rel="nofollow" title="' . esc_attr__( 'Documentation', 'clickwhale' ) . '">' . esc_html__( 'Documentation', 'clickwhale' ) . '</a>';
        $meta[] = '<a href="https://wordpress.org/support/plugin/clickwhale/reviews/?filter=5" rel="nofollow" target="_blank" title="' . esc_attr__( 'Rate ClickWhale on WordPress.org', 'clickwhale' ) . '" style="color: #ffb900">'
            . str_repeat( '<span class="dashicons dashicons-star-filled" style="font-size: 16px; width:16px; height: 16px"></span>', 5 )
            . '</a>';

        return $meta;
    }

    public function admin_scripts() {
        if ( empty( $_GET['page'] ) ) {
            return;
        }

        $page = sanitize_key( $_GET['page'] );

        if ( $page === CLICKWHALE_SLUG || $page === CLICKWHALE_SLUG . '-linkpages' ) {
            ?>
            <script type='text/javascript'>
                jQuery(document).ready(function(){
                    jQuery('.slug-input--btn').on('click', function(e){
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

        if ( $page === CLICKWHALE_SLUG . '-edit-link' || $page === CLICKWHALE_SLUG . '-edit-linkpage' ) {
            ?>
            <script type='text/javascript'>
                jQuery(document).ready(function(){
                    jQuery('#cw-copy-link-url').on('click', function(e){
                        e.preventDefault();

                        // Remove appended message
                        jQuery('.copied').remove();

                        // Copy slug
                        copySlug();

                        // Append message
                        jQuery('<span class="copied">' + <?php echo wp_json_encode( esc_html__( 'Copied!', 'clickwhale' ) ); ?> + '</span>')
                            .insertAfter(jQuery(this));

                        // Hide appended message
                        setTimeout(function(){
                            jQuery('.copied').remove();
                        }, 2000);
                    });

                    jQuery('#cw-slug--text').on('click', function(e){
                        e.preventDefault();

                        // Remove appended message
                        jQuery('.copied').remove();

                        // Copy slug
                        copySlug();

                        // Append message
                        jQuery(this)
                            .append('<span class="copied">' + <?php echo wp_json_encode( esc_html__( 'Copied!', 'clickwhale' ) ); ?> + '</span>');

                        // Hide appended message
                        setTimeout(function(){
                            jQuery('.copied').remove();
                        }, 2000);
                    });

                    function copySlug(){
                        const temp = jQuery('<input>');
                        let textToCopy = jQuery('#cw-slug').val();

                        textToCopy = clickwhale_admin.siteurl + '/' + textToCopy + '/';
                        jQuery('body').append(temp);
                        temp.val(textToCopy);
                        temp[0].select();
                        document.execCommand("copy");
                        temp.remove();
                    }
                });
            </script>
            <?php
        }

        if ( $page === CLICKWHALE_SLUG . '-tracking-codes' ) {
            ?>
            <script type='text/javascript'>
                jQuery(document).ready(function(){
                    jQuery('.clickwhale-checkbox--toggle [type="checkbox"]').on('change', function(){
                        let
                            active = this.checked,
                            id = this.dataset.id;

                        jQuery.post(ajaxurl, {
                            'security': <?php echo wp_json_encode( wp_create_nonce( 'clickwhale_toggle_tracking_code' ) ); ?>,
                            'action': 'clickwhale/admin/tracking_code_toggle_active',
                            'status': active ? 1 : 0,
                            'id': id
                        }, function(response){
                            if (response.data.action_disable_all){
                                jQuery('.clickwhale-checkbox--toggle [type="checkbox"]:not(:checked)').prop('disabled', true);
                                jQuery('#clickwhale_tracking_codes_list_limit_notice').show()
                            } else {
                                jQuery('.clickwhale-checkbox--toggle [type="checkbox"]:not(:checked)').prop('disabled', false);
                                jQuery('#clickwhale_tracking_codes_list_limit_notice').hide()
                            }
                        });
                    });
                });
            </script>
            <?php
        }
    }
}
