<?php

namespace Clickwhale\Admin;

use Clickwhale\Helpers\Helper;
use Clickwhale\Helpers\Traits\{Singleton_Clone, Singleton_Wakeup};

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

        // Controllers
        if ( ! class_exists( 'WP_List_Table' ) ) {
            require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
        }

        // Abstract for all instances

        // Child classes

        // ClickWhale REST API
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
        if ( ! clickwhale()->user->is_current_user_role_access_granted() ) {
            return;
        }

        $this->menus = apply_filters( 'clickwhale_menus', array(
                'subpages'    => array(
                        'links'              => __( 'Links', 'clickwhale' ),
                        'edit-link'          => __( 'Add New Link', 'clickwhale' ),
                        'categories'         => __( 'Categories', 'clickwhale' ),
                        'edit-category'      => __( 'Add New Category', 'clickwhale' ),
                        'smart-displays'     => __( 'Smart Displays', 'clickwhale' ),
                        'edit-smart-display' => __( 'Add New Smart Display', 'clickwhale' ),
                        'linkpages'          => __( 'Link Pages', 'clickwhale' ),
                        'edit-linkpage'      => __( 'Add New Link Page', 'clickwhale' ),
                        'tracking-codes'     => __( 'Tracking Codes', 'clickwhale' ),
                        'edit-tracking-code' => __( 'Add New Tracking Code', 'clickwhale' )
                ),
                'edit_titles' => array(
                        'edit-link'          => __( 'Edit Link', 'clickwhale' ),
                        'edit-category'      => __( 'Edit Category', 'clickwhale' ),
                        'edit-smart-display' => __( 'Edit Smart Display', 'clickwhale' ),
                        'edit-linkpage'      => __( 'Edit Link Page', 'clickwhale' ),
                        'edit-tracking-code' => __( 'Edit Tracking Code', 'clickwhale' )
                ),
                'templates'   => array(
                        'toplevel_page_' . CLICKWHALE_SLUG                       => 'links/list',
                        'admin_page_' . CLICKWHALE_SLUG . '-edit-link'           => 'links/edit',
                        'clickwhale_page_' . CLICKWHALE_SLUG . '-categories'     => 'categories/list',
                        'admin_page_' . CLICKWHALE_SLUG . '-edit-category'       => 'categories/edit',
                        'clickwhale_page_' . CLICKWHALE_SLUG . '-smart-displays' => 'smart-displays/list',
                        'admin_page_' . CLICKWHALE_SLUG . '-edit-smart-display'  => 'smart-displays/edit',
                        'clickwhale_page_' . CLICKWHALE_SLUG . '-linkpages'      => 'linkpages/list',
                        'admin_page_' . CLICKWHALE_SLUG . '-edit-linkpage'       => 'linkpages/edit',
                        'clickwhale_page_' . CLICKWHALE_SLUG . '-tracking-codes' => 'tracking-codes/list',
                        'admin_page_' . CLICKWHALE_SLUG . '-edit-tracking-code'  => 'tracking-codes/edit'
                ),
                'toplevel'    => array( 'links', 'categories', 'smart-displays', 'linkpages', 'tracking-codes' )
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

            $get_page = sanitize_key( (string) filter_input( INPUT_GET, 'page' ) );
            if ( empty( $get_page ) ) {
                continue;
            }

            if ( strpos( $get_page, $k ) === false ) {
                continue;
            }

            $pos = strpos( $get_page, '-edit-' );

            if ( $pos === false ) {
                continue;
            }

            $instance_slug = substr( $get_page, $pos + strlen( '-edit-' ) );

            $get_id = (int) filter_input( INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT );
            if ( $get_id > 0 ) {
                $parent = $this->menus['edit_titles'][ 'edit-' . $instance_slug ];
            } else {
                $parent = $this->menus['subpages'][ 'edit-' . $instance_slug ];
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
        $current_template = $this->menus['templates'][ current_filter() ];
        include_once( CLICKWHALE_TEMPLATES_DIR . '/admin/' . $current_template . '.php' );
    }

    /**
     * Register the stylesheets for the admin area.
     * @since    1.0.0
     */
    public function enqueue_styles() {
        $get_page = sanitize_key( (string) filter_input( INPUT_GET, 'page' ) );
        if ( empty( $get_page ) ) {
            return;
        }

        if ( 0 !== strpos( $get_page, CLICKWHALE_SLUG ) ) {
            return;
        }

        wp_enqueue_style(
                'coloris',
                CLICKWHALE_ADMIN_ASSETS_DIR . '/css/coloris.min.css',
                array(),
                CLICKWHALE_VERSION
        );
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
        $page = sanitize_key( (string) filter_input( INPUT_GET, 'page' ) );
        if ( empty( $page ) ) {
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
        }

        if ( $page === CLICKWHALE_SLUG . '-edit-link' ) {
            wp_enqueue_script( 'jquery-ui-sortable' );
        }

        if ( $page === CLICKWHALE_SLUG . '-edit-tracking-code' ) {
            wp_enqueue_code_editor( array( 'type' => 'text/html' ) );
        }

        if ( $page === CLICKWHALE_SLUG . '-settings'
             && isset( $_GET['tab'] ) && sanitize_key( $_GET['tab'] ) === 'linkpages_options'
        ) {
            wp_enqueue_code_editor( array( 'type' => 'text/html' ) );
        }

        if ( $page === CLICKWHALE_SLUG . '-edit-smart-display' ) {
            wp_enqueue_media();
            wp_enqueue_editor();
        }

        wp_enqueue_script(
                'coloris',
                CLICKWHALE_ADMIN_ASSETS_DIR . '/js/coloris.min.js',
                array(),
                CLICKWHALE_VERSION,
                true
        );
        wp_enqueue_script(
                'clickwhale_select2',
                CLICKWHALE_ADMIN_ASSETS_DIR . '/js/select2/select2.min.js',
                array( 'jquery' ),
                '4.1.0-rc.0',
                true
        );
        wp_enqueue_script(
                'clickwhale',
                CLICKWHALE_ADMIN_ASSETS_DIR . '/js/clickwhale-admin.js',
                array( 'jquery' ),
                CLICKWHALE_VERSION,
                true
        );
        wp_localize_script(
                'clickwhale',
                'clickwhale_admin', array(
                        'siteurl'                 => home_url(),
                        'plugin_slug'             => esc_attr( CLICKWHALE_SLUG ),
                        'nonce_amz_connect'       => wp_create_nonce( 'clickwhale_amz_connect_validate' ),
                        'nonce_amz_connect_quota' => wp_create_nonce( 'clickwhale_amz_connect_quota' ),
                        'status_labels'           => array(
                                'connected'   => __( 'Connected', 'clickwhale' ),
                                'disconnected' => __( 'Invalid API Key', 'clickwhale' ),
                                'validating'  => __( 'Validating...', 'clickwhale' ),
                        ),
                )
        );
    }

    /**
     * Extend the stylesheets for Freemius pricing page
     *
     * @param string $template
     *
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
                color: #1A1C1D !important;
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
        ?>

        <div class="clickwhale-banner">
            <div class="clickwhale-banner--logo">
                <a href="<?php echo esc_url( $link_logo ); ?>"
                   target="_blank"
                   rel="noopener"
                ><img src="<?php echo esc_url( CLICKWHALE_ADMIN_ASSETS_DIR ) . '/images/wordmark.svg'; ?>"
                      alt="clickwhale"
                    ></a>
                <span class="clickwhale-banner--version"
                      title="<?php esc_attr_e( 'ClickWhale version', 'clickwhale' ); ?>"
                >v<?php echo esc_html( CLICKWHALE_VERSION ); ?></span>
            </div>
            <div class="clickwhale-banner--links">
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

    /**
     * The Freemius-rendered "License" (account) and "Affiliation" pages don't
     * include our templates, so `clickwhale_admin_banner` never fires on them.
     * Freemius wraps each of its own templates' rendered HTML in a
     * `fs_templates/{template}_{affix}` filter specifically so callers can
     * wrap it, so we prepend our banner there rather than via a WP admin
     * hook: `in_admin_header` fires before `#wpbody` opens, losing the
     * `position: relative` context `#wpbody` provides and leaving the
     * banner positioned relative to `#wpwrap` instead (hidden behind the
     * sidebar); `admin_notices` has that context but Freemius clears it via
     * `remove_all_actions()` on the Affiliation page.
     *
     * @since 2.8.2
     */
    public function prepend_admin_banner( string $html ): string {
        ob_start();
        $this->admin_banner();

        return ob_get_clean() . $html;
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
        }
        ?>
        <div class="postbox clickwhale-admin-widget" id="clickwhale-admin-widget__upgrade">
            <div class="hero">
                <img src="<?php echo esc_url( CLICKWHALE_ADMIN_ASSETS_DIR ) . '/images/widgets/upgrade_to_pro_widget_hero.svg'; ?>"
                     alt="clickwhale">
            </div>
            <h3 class="title"><?php esc_attr_e( 'Upgrade to ClickWhale Pro', 'clickwhale' ); ?></h3>
            <div class="inside">
                <ul>
                    <li><span class="text"><?php esc_attr_e( 'Detailed Statistics', 'clickwhale' ); ?></span></li>
                    <li><span class="text"><?php esc_attr_e( 'Keyword Auto Linker', 'clickwhale' ); ?></span></li>
                    <li><span class="text"><?php esc_attr_e( 'UTM Campaign Tracking', 'clickwhale' ); ?></span></li>
                    <li><span class="text"><?php esc_attr_e( 'E-Commerce Conversion Tracking', 'clickwhale' ); ?></span>
                    </li>
                    <li><span class="text"><?php esc_attr_e( 'Advanced Customization Options', 'clickwhale' ); ?></span>
                    </li>
                    <li><span class="text"><?php esc_attr_e( 'More Blocks for Link Pages', 'clickwhale' ); ?></span>
                    </li>
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
                <img src="<?php echo esc_url( CLICKWHALE_ADMIN_ASSETS_DIR ) . '/images/widgets/docs_widget_hero.png'; ?>"
                     alt="<?php echo 'clickwhale'; ?>">
            </div>
            <h3 class="title"><?php esc_attr_e( 'Plugin Documentation', 'clickwhale' ); ?></h3>
            <div class="inside">
                <ul>
                    <li>
                        <a href="https://clickwhale.pro/docs/article/how-to-shorten-links-and-create-redirects/?utm_source=users&utm_medium=button&utm_campaign=plugin_admin&utm_content=widget_documentation"
                           class="text"
                           target="_blank"
                           rel="nofollow"
                           title="<?php esc_attr_e( 'How-To Shorten Links & Create Redirects', 'clickwhale' ); ?>"><?php esc_attr_e( 'How-To Shorten Links & Create Redirects', 'clickwhale' ); ?></a>
                    </li>

                    <li>
                        <a href="https://clickwhale.pro/docs/article/how-to-import-links/?utm_source=users&utm_medium=button&utm_campaign=plugin_admin&utm_content=widget_documentation"
                           class="text"
                           target="_blank"
                           rel="nofollow"
                           title="<?php esc_attr_e( 'How-To Import Links', 'clickwhale' ); ?>"><?php esc_attr_e( 'How-To Import Links', 'clickwhale' ); ?></a>
                    </li>

                    <li>
                        <a href="https://clickwhale.pro/docs/article/how-to-use-the-keyword-auto-linker/?utm_source=users&utm_medium=button&utm_campaign=plugin_admin&utm_content=widget_documentation"
                           class="text"
                           target="_blank"
                           rel="nofollow"
                           title="<?php esc_attr_e( 'How-To Use the Keyword Auto Linker', 'clickwhale' ); ?>"><?php esc_attr_e( 'How-To Use the Keyword Auto Linker', 'clickwhale' ); ?></a>
                    </li>

                    <li>
                        <a href="https://clickwhale.pro/docs/article/creating-your-first-link-page/?utm_source=users&utm_medium=button&utm_campaign=plugin_admin&utm_content=widget_documentation"
                           class="text"
                           target="_blank"
                           rel="nofollow"
                           title="<?php esc_attr_e( 'Creating Your First Link Page', 'clickwhale' ); ?>"><?php esc_attr_e( 'Creating Your First Link Page', 'clickwhale' ); ?></a>
                    </li>
                    <li>
                        <a href="https://clickwhale.pro/docs/article/add-google-tag-manager-to-wordpress/?utm_source=users&utm_medium=button&utm_campaign=plugin_admin&utm_content=widget_documentation"
                           class="text"
                           target="_blank"
                           rel="nofollow"
                           title="<?php esc_attr_e( 'How-To Add Google Tag Manager To WordPress with ClickWhale', 'clickwhale' ); ?>"><?php esc_attr_e( 'How-To Add Google Tag Manager To WordPress with ClickWhale', 'clickwhale' ); ?></a>
                    </li>
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
        check_admin_referer( 'clickwhale_pro_subscribe', 'nonce' );

        $current_user = wp_get_current_user();
        $url          = "https://clickwhale.pro/?fluentcrm=1&route=contact&hash=e2920f25-a285-4568-bea4-ede017a039fb";
        $email        = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';

        $response = wp_remote_post( $url, array(
                        'method' => 'POST',
                        'body'   => array(
                                'email'      => $email,
                                'first_name' => ( $current_user->exists() ) ? $current_user->first_name : '',
                        )
                )
        );

        if ( is_wp_error( $response ) ) {
            $error_message = $response->get_error_message();
            printf(
            /* translators: %s:the error message */
                    esc_html__( 'Something went wrong: %s', 'clickwhale' ),
                    esc_html( $error_message )
            );
        } else {
            wp_safe_redirect(
                    esc_url_raw(
                            admin_url( 'admin.php?page=' . CLICKWHALE_SLUG . '-pro&success=1#clickwhaleSubscribe' )
                    )
            );
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

        $url           = esc_url( admin_url( 'admin.php?page=' . CLICKWHALE_SLUG . '-settings' ) );
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

        $url           = esc_url( admin_url( 'admin.php?page=' . CLICKWHALE_SLUG . '-pro' ) );
        $text          = __( 'Upgrade to PRO', 'clickwhale' );
        $settings_link = '<a href="' . $url . '" rel="noopener" style="color: #007AFF; font-weight: 700;">' . $text . '</a>';
        $links[]       = $settings_link;

        return $links;
    }

    /**
     * @return void
     * @since 1.4.1
     */
    public function hide_notices_on_clickwhale_pages() {
        $page = sanitize_key( (string) filter_input( INPUT_GET, 'page' ) );
        if ( $page !== '' && strpos( $page, CLICKWHALE_SLUG ) === 0 ) {
            remove_all_actions( 'user_admin_notices' );
            remove_all_actions( 'admin_notices' );
        }
    }

    public function plugin_meta_links( array $meta, string $file ): array {
        if ( $file !== CLICKWHALE_ID ) {
            return $meta;
        }

        $meta[] = '<a href="https://clickwhale.pro/docs/" target="_blank" rel="nofollow" title="' . esc_attr__( 'Documentation', 'clickwhale' ) . '">' . esc_html__( 'Documentation', 'clickwhale' ) . '</a>';

        return $meta;
    }

    public function admin_scripts() {
        $page = sanitize_key( (string) filter_input( INPUT_GET, 'page' ) );
        if ( empty( $page ) ) {
            return;
        }
        ?>
        <script type='text/javascript'>
            jQuery(document).ready(function () {
                <?php if ( $page === CLICKWHALE_SLUG || $page === CLICKWHALE_SLUG . '-linkpages' ) {
                ?>
                jQuery('.slug-input--btn').on('click', function (e) {
                    e.preventDefault();
                    let
                        $temp = jQuery('<input>'),
                        textToCopy = jQuery(this).parent().find('input').val();

                    textToCopy = clickwhale_admin.siteurl + '/' + textToCopy + '/';
                    jQuery('body').append($temp);
                    $temp.val(textToCopy).trigger('select');
                    document.execCommand("copy");
                    $temp.remove();
                });
                <?php
                }

                if ( $page === CLICKWHALE_SLUG . '-edit-link' || $page === CLICKWHALE_SLUG . '-edit-linkpage' ) {
                ?>
                jQuery('#cw-copy-link-url').on('click', function (e) {
                    e.preventDefault();

                    // Remove appended message
                    jQuery('.copied').remove();

                    // Copy slug
                    copySlug();

                    // Append message
                    jQuery('<span class="copied">' + <?php echo wp_json_encode( esc_html__( 'Copied!', 'clickwhale' ) ); ?> +'</span>')
                        .insertAfter(jQuery(this));

                    // Hide appended message
                    setTimeout(function () {
                        jQuery('.copied').remove();
                    }, 2000);
                });

                jQuery('#cw-slug--text').on('click', function (e) {
                    e.preventDefault();

                    // Remove appended message
                    jQuery('.copied').remove();

                    // Copy slug
                    copySlug();

                    // Append message
                    jQuery(this)
                        .append('<span class="copied">' + <?php echo wp_json_encode( esc_html__( 'Copied!', 'clickwhale' ) ); ?> +'</span>');

                    // Hide appended message
                    setTimeout(function () {
                        jQuery('.copied').remove();
                    }, 2000);
                });

                function copySlug() {
                    const temp = jQuery('<input>');
                    let textToCopy = jQuery('#cw-slug').val();

                    textToCopy = clickwhale_admin.siteurl + '/' + textToCopy + '/';
                    jQuery('body').append(temp);
                    temp.val(textToCopy).trigger('select');
                    document.execCommand("copy");
                    temp.remove();
                }
                <?php
                }

                if ( $page === CLICKWHALE_SLUG . '-tracking-codes' ) {
                ?>
                jQuery('.clickwhale-checkbox--toggle [type="checkbox"]').on('change', function () {
                    let
                        active = this.checked,
                        id = this.dataset.id;

                    jQuery.post(ajaxurl, {
                        'security': <?php echo wp_json_encode( wp_create_nonce( 'clickwhale_toggle_tracking_code' ) ); ?>,
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
                    });
                });
                <?php
                }

                if ( $page === CLICKWHALE_SLUG . '-settings'
                     && isset( $_GET['tab'] ) && sanitize_key( $_GET['tab'] ) === 'link_manager_options'
                ) {
                ?>
                jQuery('#cw-shortcode--text').on('click', function (e) {
                    e.preventDefault();

                    jQuery('.copied').remove();

                    const $temp = jQuery('<input>');
                    let textToCopy = jQuery('#cw-shortcode').text();

                    jQuery('body').append($temp);
                    $temp.val(textToCopy).trigger('select');
                    document.execCommand("copy");
                    $temp.remove();

                    jQuery(this).append('<span class="copied"><?php echo esc_js( __( 'Copied!', 'clickwhale' ) ); ?></span>');
                    setTimeout(function () { jQuery('.copied').remove(); }, 2000);
                });
                <?php
                }

                if ( $page === CLICKWHALE_SLUG . '-smart-displays' ) {
                ?>
                jQuery('.shortcode-input--btn').on('click', function (e) {
                    e.preventDefault();
                    const $temp = jQuery('<input>');
                    let textToCopy = jQuery(this).parent().find('input').val();

                    jQuery('body').append($temp);
                    $temp.val(textToCopy).trigger('select');
                    document.execCommand("copy");
                    $temp.remove();
                });
                <?php
                }

                if ( $page === CLICKWHALE_SLUG . '-edit-smart-display' ) {
                ?>
                jQuery('#cw-shortcode--text').on('click', function (e) {
                    e.preventDefault();

                    const $temp = jQuery('<input>');
                    let textToCopy = jQuery('#cw-shortcode').text();

                    // Remove appended message
                    jQuery('.copied').remove();

                    // Copy shortcode
                    jQuery('body').append($temp);
                    $temp.val(textToCopy).trigger('select');
                    document.execCommand("copy");
                    $temp.remove();

                    // Append message
                    jQuery(this)
                        .append('<span class="copied"><?php echo esc_js( __( 'Copied!', 'clickwhale' ) ); ?></span>');

                    // Hide appended message
                    setTimeout(function () {
                        jQuery('.copied').remove();
                    }, 2000);
                });
                <?php
                }


                if ( $page === CLICKWHALE_SLUG . '-settings'
                     && ( empty( $_GET['tab'] ) || sanitize_key( $_GET['tab'] ) === 'smart_displays_options' )
                ){
                ?>
                const
                    defaults = <?php echo json_encode( clickwhale()->settings->default_options() ); ?>,
                    pickers = [
                        {
                            selector: '#container_border_color',
                            defaultColor: defaults.smart_displays.options.container.border.color
                        },
                        {
                            selector: '#container_border_color_hover',
                            defaultColor: defaults.smart_displays.options.container.border.color_hover
                        },
                        {
                            selector: '#primary_color',
                            defaultColor: defaults.smart_displays.options.primary.color
                        },
                        {
                            selector: '#primary_color_hover',
                            defaultColor: defaults.smart_displays.options.primary.color_hover
                        },
                        {
                            selector: '#primary_bg_color',
                            defaultColor: defaults.smart_displays.options.primary.bg_color
                        },
                        {
                            selector: '#primary_bg_color_hover',
                            defaultColor: defaults.smart_displays.options.primary.bg_color_hover
                        },
                        {
                            selector: '#primary_border_color',
                            defaultColor: defaults.smart_displays.options.primary.border.color
                        },
                        {
                            selector: '#primary_border_color_hover',
                            defaultColor: defaults.smart_displays.options.primary.border.color_hover
                        }
                    ];

                const swatches = ['#000000', '#ffffff', '#f8fafc', '#64748b', '#3b82f6', '#22c55e', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#f97316', '#06b6d4'];

                pickers.forEach((picker) => {
                    const
                        $field = jQuery(picker.selector),
                        defaultColor = picker.defaultColor;

                    if (!$field.length) return;

                    if (!validateHexColor($field.val())) {
                        $field.val(defaultColor);
                    }

                    $field.addClass('cw-color-picker');
                    $field.after('<button type="button" class="button-link cw-color-reset" data-default="' + defaultColor + '"><?php echo esc_js( __( 'Reset', 'clickwhale' ) ); ?></button>');
                });

                Coloris({
                    el: '.cw-color-picker',
                    format: 'hex',
                    alpha: true,
                    swatches: swatches,
                });

                jQuery(document).on('click', '.cw-color-reset', function (e) {
                    e.preventDefault();
                    const defaultColor = jQuery(this).data('default');
                    const $wrapper = jQuery(this).prev('.clr-field');
                    $wrapper.find('.cw-color-picker').val(defaultColor);
                    $wrapper.css('color', defaultColor);
                });

                function debounce(func, delay = 300) {
                    let timer;
                    return function (...args) {
                        clearTimeout(timer);
                        timer = setTimeout(() => func.apply(this, args), delay);
                    };
                }

                function validateHexColor(val) {
                    return /^#([0-9A-F]{3,4}){1,2}$/i.test(val);
                }
                <?php
                }

                if ( $page === CLICKWHALE_SLUG . '-settings'
                     && isset( $_GET['tab'] ) && sanitize_key( $_GET['tab'] ) === 'linkpages_options'
                ){
                ?>
                if (jQuery('#linkpages_custom_css').length) {
                    let cssEditorSettings = wp.codeEditor.defaultSettings ? _.clone(wp.codeEditor.defaultSettings) : {};
                    cssEditorSettings.codemirror = _.extend({}, cssEditorSettings.codemirror, {
                        mode: 'text/css',
                        indentUnit: 2,
                        tabSize: 2,
                    });
                    wp.codeEditor.initialize(jQuery('#linkpages_custom_css'), cssEditorSettings);
                }

                if (jQuery('#linkpages_custom_js').length) {
                    let jsEditorSettings = wp.codeEditor.defaultSettings ? _.clone(wp.codeEditor.defaultSettings) : {};
                    jsEditorSettings.codemirror = _.extend({}, jsEditorSettings.codemirror, {
                        mode: 'text/html',
                        indentUnit: 2,
                        tabSize: 2,
                    });
                    wp.codeEditor.initialize(jQuery('#linkpages_custom_js'), jsEditorSettings);
                }
                <?php
                }
                ?>
            });
        </script>
        <?php
    }
}
