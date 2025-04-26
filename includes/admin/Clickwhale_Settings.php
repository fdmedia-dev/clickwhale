<?php
namespace clickwhale\includes\admin;

use clickwhale\includes\Clickwhale;
use clickwhale\includes\helpers\{Helper, Links_Helper};
use clickwhale\includes\helpers\traits\{Singleton_Clone, Singleton_Wakeup};

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Settings of the plugin.
 *
 * @link       #
 * @since      1.0.0
 *
 * @package    Clickwhale
 * @subpackage Clickwhale/admin
 */
final class Clickwhale_Settings {

    /**
     * @since    1.5.0
     * @var Clickwhale_Settings
     */
    private static Clickwhale_Settings $instance;

    /**
     * @return Clickwhale_Settings
     */
    public static function get_instance(): Clickwhale_Settings {
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
    private function __construct() {}

    use Singleton_Clone;
    use Singleton_Wakeup;

    /**
     * Provides default values Options.
     *
     * @return array
     */
    public static function default_options(): array {
        return apply_filters( 'clickwhale_default_options', clickwhale()->default_options() );
    }

    public function add_default_options() {
        /* @since 1.0.0 */
        $defaults = self::default_options();

        foreach ( $defaults as $k => $v ) {
            $option_name = 'clickwhale_' . $k . '_options';
            if ( ! get_option( $option_name ) ) {
                add_option( $option_name, $v['options'] );
            }
        }
    }

    /**
     * Initializes the plugin settings options page by registering the Sections,
     * Fields, and Settings.
     *
     * This function is registered with the 'admin_init' hook.
     * @since 1.0.0
     */
    public function add_settings_fields() {
        $defaults             = self::default_options();
        $general_options      = get_option( 'clickwhale_general_options' );
        $tracking_options     = get_option( 'clickwhale_tracking_options' );
        $link_manager_options = get_option( 'clickwhale_link_manager_options' );

        $current_user = Clickwhale::get_instance()->user;
        $current_user_roles = $current_user::get_current_user_roles();
        $always_checked_roles = array( 'administrator' );

        if ( ! in_array( 'administrator', $current_user_roles ) ) {
            foreach ( $current_user_roles as $user_role ) {
                $always_checked_roles[] = $user_role;
            }
        }

        $slug = ( ! empty( $link_manager_options['slug'] ) ) ? esc_attr( wp_unslash( $link_manager_options['slug'] ) ) : $defaults['link_manager']['options']['slug'];

        if ( $defaults ) {
            // Register settings sections
            foreach ( $defaults as $k => $v ) {
                add_settings_section(
                    $k . '_settings_section',
                    $v['name'],
                    array( $this, 'settings_section_callback' ),
                    'clickwhale_' . $k . '_options',
                    array( 'text' => $v['text'] )
                );
                register_setting(
                    'clickwhale_' . $k . '_options',
                    'clickwhale_' . $k . '_options'
                );
            }
        }

        // Add fields

        // General options
        add_settings_field(
            'access_level',
            __( 'Access Level', 'clickwhale' ),
            array( $this, 'render_controls' ),
            'clickwhale_general_options',
            'general_settings_section',
            array(
                'control'        => 'checkboxes',
                'id'             => 'access_level',
                'name'           => 'clickwhale_general_options[access_level][]',
                'value'          => $general_options['access_level'] ?? $defaults['general']['options']['access_level'],
                'options'        => Clickwhale_WP_User::get_all_roles(),
                'always_checked' => $always_checked_roles,
                'description'    => __( 'Decide who can access critical admin pages and the plugin settings.', 'clickwhale' )
            )
        );
        add_settings_field(
            'hide_admin_bar_menu',
            __( 'Hide Admin Bar Menu', 'clickwhale' ),
            array( $this, 'render_controls' ),
            'clickwhale_general_options',
            'general_settings_section',
            array(
                'control' => 'checkbox',
                'id'      => 'hide_admin_bar_menu',
                'name'    => 'clickwhale_general_options[hide_admin_bar_menu]',
                'value'   => ! empty( $general_options['hide_admin_bar_menu'] ) ? 1 : 0,
                'label'   => __( 'Check to hide Clickwhale quick menu from the admin bar.', 'clickwhale' )
            )
        );

        // Tracking options
        add_settings_field(
            'tracking_duration',
            __( 'Tracking Duration', 'clickwhale' ),
            array( $this, 'render_controls' ),
            'clickwhale_tracking_options',
            'tracking_settings_section',
            array(
                'control' => 'select',
                'id'      => 'tracking_duration',
                'name'    => 'clickwhale_tracking_options[tracking_duration]',
                'value'   => $tracking_options['tracking_duration'] ?? $defaults['tracking']['options']['tracking_duration'],
                'options' => apply_filters( 'clickwhale_tracking_duration', array(
                    30 => __( '30 days', 'clickwhale' )
                ) )
            )
        );
        add_settings_field(
            'disable_tracking',
            __( 'Disable Tracking', 'clickwhale' ),
            array( $this, 'render_controls' ),
            'clickwhale_tracking_options',
            'tracking_settings_section',
            array(
                'control' => 'checkbox',
                'id'      => 'disable_tracking',
                'name'    => 'clickwhale_tracking_options[disable_tracking]',
                'value'   => ! empty( $tracking_options['disable_tracking'] ) ? 1 : 0,
                'label'   => __( 'Check to disable tracking of views and clicks.', 'clickwhale' )
            )
        );
        add_settings_field(
            'exclude_user_by_role',
            __( 'Exclude User Roles', 'clickwhale' ),
            array( $this, 'render_controls' ),
            'clickwhale_tracking_options',
            'tracking_settings_section',
            array(
                'control'     => 'checkboxes',
                'id'          => 'exclude_user_by_role',
                'name'        => 'clickwhale_tracking_options[exclude_user_by_role][]',
                'value'       => $tracking_options['exclude_user_by_role'] ?? '',
                'options'     => Clickwhale_WP_User::get_all_roles(),
                'description' => __( 'Check the user roles that should be excluded from tracking.', 'clickwhale' )
            )
        );

        // Link Manager options
        add_settings_field(
            'redirection',
            __( 'Redirection Type', 'clickwhale' ),
            array( $this, 'render_controls' ),
            'clickwhale_link_manager_options',
            'link_manager_settings_section',
            array(
                'control'     => 'select',
                'id'          => 'redirect_type',
                'name'        => 'clickwhale_link_manager_options[redirect_type]',
                'value'       => $link_manager_options['redirect_type'] ?? $defaults['link_manager']['options']['redirect_type'],
                'options'     => Links_Helper::get_redirections(),
                'description' => __( 'Set default redirection type which will be used for new links.', 'clickwhale' )
            )
        );
        add_settings_field(
            'link_target',
            __( 'Link Target', 'clickwhale' ),
            array( $this, 'render_controls' ),
            'clickwhale_link_manager_options',
            'link_manager_settings_section',
            array(
                'control'     => 'select',
                'id'          => 'link_target',
                'name'        => 'clickwhale_link_manager_options[link_target]',
                'value'       => $link_manager_options['link_target'] ?? $defaults['link_manager']['options']['link_target'],
                'options'     => Links_Helper::get_link_targets(),
                'description' => __( 'Set default target which will be used for all links.', 'clickwhale' )
            )
        );
        add_settings_field(
            'nofollow',
            __( 'Nofollow Links', 'clickwhale' ),
            array( $this, 'render_controls' ),
            'clickwhale_link_manager_options',
            'link_manager_settings_section',
            array(
                'control' => 'checkbox',
                'id'      => 'nofollow',
                'name'    => 'clickwhale_link_manager_options[nofollow]',
                'value'   => ! empty( $link_manager_options['nofollow'] ) ? 1 : 0,
                'label'   => __( 'Check to mark links as nofollow & noindex by default.', 'clickwhale' )
            )
        );
        add_settings_field(
            'sponsored',
            __( 'Sponsored Links', 'clickwhale' ),
            array( $this, 'render_controls' ),
            'clickwhale_link_manager_options',
            'link_manager_settings_section',
            array(
                'control'     => 'checkbox',
                'id'          => 'sponsored',
                'name'        => 'clickwhale_link_manager_options[sponsored]',
                'value'       => ! empty( $link_manager_options['sponsored'] ) ? 1 : 0,
                'label'       => __( 'Check to mark links as sponsored by default.', 'clickwhale' ),
                'description' => __( 'Recommended for affiliate links.', 'clickwhale' )
            )
        );
        add_settings_field(
            'slug',
            __( 'Link Prefix', 'clickwhale' ),
            array( $this, 'render_controls' ),
            'clickwhale_link_manager_options',
            'link_manager_settings_section',
            array(
                'control'     => 'input',
                'id'          => 'slug',
                'name'        => 'clickwhale_link_manager_options[slug]',
                'type'        => 'text',
                'value'       => $slug,
                'placeholder' => '',
                'description' => __( 'Here, you can enter a prefix that will be prepended when creating a new link. For example: <em>link</em>.<br><strong>Important:</strong> If you change the prefix, it will <u>not</u> affect already existing links.', 'clickwhale' )
            )
        );
        add_settings_field(
            'random_slug',
            __( 'Random Slug', 'clickwhale' ),
            array( $this, 'render_controls' ),
            'clickwhale_link_manager_options',
            'link_manager_settings_section',
            array(
                'control' => 'checkbox',
                'id'      => 'random_slug',
                'name'    => 'clickwhale_link_manager_options[random_slug]',
                'value'   => ! empty( $link_manager_options['random_slug'] ) ? 1 : 0,
                'label'   => __( 'Check to <u>not</u> suggest a random link slug when creating a new link.', 'clickwhale' )
            )
        );

        do_action( 'clickwhale_settings_fields' );
    }

    /**
     * This functions provides a simple description for the Options page.
     * @since 1.0.0
     */
    public static function settings_section_callback( $args ) {
        echo '<p>' . $args['text'] . '</p>';
    }

    /**
     * Render plugin settings tabs
     * Hook: Filter 'clickwhale_settings_tabs';
     * @return array
     *
     * @since 1.3.0
     */
    public static function render_tabs(): array {
        return apply_filters( 'clickwhale_settings_tabs', array(
            'general'      => array(
                'name' => __( 'General', 'clickwhale' ),
                'url'  => 'general_options',
            ),
            'tracking'     => array(
                'name' => __( 'Tracking', 'clickwhale' ),
                'url'  => 'tracking_options'
            ),
            'link_manager' => array(
                'name' => __( 'Link Manager', 'clickwhale' ),
                'url'  => 'link_manager_options'
            )
        ) );
    }

    /**
     * This function renders the interface elements.
     */
    public static function render_controls( $args ) {
        echo Helper::render_control( $args );
    }

    public function filter_settings_tabs_capability() {
        $tabs = self::render_tabs();
        foreach ( $tabs as $tab ) {
            add_filter( 'option_page_capability_clickwhale_' . $tab['url'], array( self::$instance, 'add_capability' ) );
            add_filter( 'sanitize_option_clickwhale_' . $tab['url'], array( self::$instance, 'remove_capability' ) );
        }
    }

    public function add_capability( $capability ) {
        $current_user = Clickwhale::get_instance()->user;

        if ( $current_user->get_user()->has_cap( 'manage_options' ) ) {
            return $capability;
        }

        $current_user_roles = $current_user::get_current_user_roles();

        if ( in_array( 'administrator', $current_user_roles ) ) {
            return $capability;
        }

        $general_options = get_option( 'clickwhale_general_options' );
        $access_roles = $general_options['access_level'] ?? ['administrator'];

        if ( array_intersect( $access_roles, $current_user_roles ) ) {

            // Cache current user role caps for a few seconds
            set_transient( 'clickwhale_user_' . $current_user->get_user()->ID . '_role_caps', $current_user->get_user()->get_role_caps(), 10 ); // 10 seconds

            // Add higher capability to permitted roles
            $current_user->get_user()->add_cap( 'manage_options' );
        }

        return $capability;
    }

    public function remove_capability( $options ) {
        $current_user = Clickwhale::get_instance()->user;
        $current_user_roles = $current_user::get_current_user_roles();

        if ( in_array( 'administrator', $current_user_roles ) ) {
            return $options;
        }

        $cached_role_caps = get_transient( 'clickwhale_user_' . $current_user->get_user()->ID . '_role_caps' );

        if ( ! $cached_role_caps ) {
            return $options;
        }

        if ( ! isset( $cached_role_caps['manage_options'] ) ) {

            // Remove higher capability to permitted roles
            $current_user->get_user()->remove_cap( 'manage_options' );

            // Delete from cache
            delete_transient( 'clickwhale_user_' . $current_user->get_user()->ID . '_role_caps' );
        }

        return $options;
    }
}
