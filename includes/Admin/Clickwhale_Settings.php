<?php

namespace Clickwhale\Admin;

use Clickwhale\Helpers\{Helper, Integrations_Helper, Links_Helper, Smart_Displays_Helper};
use Clickwhale\Helpers\Traits\{Singleton_Clone, Singleton_Wakeup};

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

    private function __construct() {
    }

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
        /* @since 2.7.0 */
        $smart_displays_options  = get_option( 'clickwhale_smart_displays_options' );
        $primary_border_defaults = $defaults['smart_displays']['options']['primary']['border'];
        /* @since 2.8.0 */
        $integrations_options = get_option( 'clickwhale_integrations_options' );

        // User roles
        $current_user_roles   = clickwhale()->user->get_current_user_roles();
        $always_checked_roles = array( 'administrator' );

        // Link prefix slug
        $slug = ( ! empty( $link_manager_options['slug'] ) ) ? esc_attr( wp_unslash( $link_manager_options['slug'] ) ) : $defaults['link_manager']['options']['slug'];

        // Border styles
        $border_styles = Smart_Displays_Helper::get_border_styles();

        if ( $defaults ) {
            // Register settings sections
            foreach ( $defaults as $k => $v ) {
                $option_name = 'clickwhale_' . $k . '_options';
                $callback    = 'sanitize_' . $k . '_options';

                if ( ! method_exists( $this, $callback ) ) {
                    $callback = 'sanitize_passthrough_options';
                }

                add_settings_section(
                        $k . '_settings_section',
                        $v['name'],
                        array( $this, 'settings_section_callback' ),
                        $option_name,
                        array( 'text' => $v['text'] )
                );

                /**
                 * if the option is a multiple, create a separate section for each option
                 * @since 2.8.0
                 */
                if ( ! empty( $v['type'] ) && $v['type'] === 'multiple' ) {
                    $options = $v['options'];
                    foreach ( $options as $option_key => $option_value ) {
                        add_settings_section(
                                $option_key . '_settings_section',
                                $option_value['name'],
                                array( $this, 'settings_subsection_callback' ),
                                $option_name,
                                array(
                                        'before_section' => '<div class="clickwhale-box subsection subsection-' . $k . '">',
                                        'after_section'  => '</div>',
                                        'text'           => $option_value['description'],
                                        'image'          => $option_value['image'],
                                )
                        );
                    }
                }

                register_setting(
                        $option_name,
                        $option_name,
                        array(
                                'type'              => 'array',
                                'sanitize_callback' => array( $this, $callback ),
                                'default'           => array()
                        )
                );
            }
        }

        /** Add fields */

        /** General options */

        if ( in_array( 'administrator', $current_user_roles ) ) {
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
                            'options'        => clickwhale()->user->get_roles_with_upload_cap(),
                            'always_checked' => $always_checked_roles,
                            'description'    => esc_html__( 'Decide who can access plugin admin pages.', 'clickwhale' )
                    )
            );
        }
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
                        'label'   => esc_html__( 'Check to hide Clickwhale quick menu from the admin bar.', 'clickwhale' )
                )
        );

        /** Tracking options */

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
                        'options' => Helper::get_tracking_durations()
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
                        'label'   => esc_html__( 'Check to disable tracking of views and clicks.', 'clickwhale' )
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
                        'options'     => clickwhale()->user->get_all_roles(),
                        'description' => esc_html__( 'Check the user roles that should be excluded from tracking.', 'clickwhale' )
                )
        );

        /** Link Manager options */

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
                        'description' => esc_html__( 'Set default redirection type which will be used for new links.', 'clickwhale' )
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
                        'description' => esc_html__( 'Set default target which will be used for all links.', 'clickwhale' )
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
                        'label'   => esc_html__( 'Check to mark links as nofollow & noindex by default.', 'clickwhale' )
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
                        'label'       => esc_html__( 'Check to mark links as sponsored by default.', 'clickwhale' ),
                        'description' => esc_html__( 'Recommended for affiliate links.', 'clickwhale' )
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
                        'description' => wp_kses(
                                __( 'Here, you can enter a prefix that will be prepended when creating a new link. For example: <em>link</em>.<br><strong>Important:</strong> If you change the prefix, it will <u>not</u> affect already existing links.', 'clickwhale' ),
                                array(
                                        'em'     => array(),
                                        'strong' => array(),
                                        'u'      => array(),
                                        'br'     => array()
                                )
                        )
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
                        'label'   => wp_kses(
                                __( 'Check to <u>not</u> suggest a random link slug when creating a new link.', 'clickwhale' ),
                                array(
                                        'u' => array()
                                )
                        )
                )
        );
        $linkpages_options   = get_option( 'clickwhale_linkpages_options' );
        $credits_description = function_exists( 'clickwhale_fs' ) && clickwhale_fs()->is__premium_only()
                ? wp_kses(
                        sprintf(
                        /* translators: 1: Affiliate program URL, 2: Settings page URL */
                                __( 'As a member of our <a href="%1$s" target="_blank">affiliate program</a>, you can enter your affiliate link in the settings <a href="%2$s">here</a>,<br>which will then be used when the credits are displayed.', 'clickwhale' ),
                                esc_url( Helper::get_affiliates_link() ),
                                esc_url( 'admin.php?page=' . CLICKWHALE_SLUG . '-settings&tab=general_options' )
                        ),
                        array(
                                'a'  => array(
                                        'href'   => array(),
                                        'target' => array( '_blank' )
                                ),
                                'br' => array()
                        )
                )
                : '';
        add_settings_field(
                'show_linkpage_credits',
                __( 'Credits', 'clickwhale' ),
                array( $this, 'render_controls' ),
                'clickwhale_linkpages_options',
                'linkpages_settings_section',
                array(
                        'control'     => 'checkbox',
                        'id'          => 'show_linkpage_credits',
                        'name'        => 'clickwhale_linkpages_options[show_linkpage_credits]',
                        'value'       => ! empty( $linkpages_options['show_linkpage_credits'] ) ? 1 : 0,
                        'label'       => esc_html__( 'Check to show Link Page credits.', 'clickwhale' ),
                        'description' => $credits_description
                )
        );

        /**
         * Smart Displays options
         * @since 2.7.0
         */
        add_settings_field(
                'disclosure',
                __( 'Disclosure Text', 'clickwhale' ),
                array( $this, 'render_controls' ),
                'clickwhale_smart_displays_options',
                'smart_displays_settings_section',
                array(
                        'control'     => 'textarea',
                        'id'          => 'disclosure',
                        'name'        => 'clickwhale_smart_displays_options[disclosure]',
                        'value'       => $smart_displays_options['disclosure'] ?? '',
                        'description' => __( 'Set default disclosure text', 'clickwhale' )
                )
        );
        add_settings_field(
                'primary_button_header',
                __( 'Primary Button', 'clickwhale' ),
                array( $this, 'render_controls' ),
                'clickwhale_smart_displays_options',
                'smart_displays_settings_section',
                array(
                        'control' => 'header',
                        'class'   => 'clickwhale-label-header'
                )
        );
        add_settings_field(
                'primary_text',
                __( 'Text', 'clickwhale' ),
                array( $this, 'render_controls' ),
                'clickwhale_smart_displays_options',
                'smart_displays_settings_section',
                array(
                        'control'     => 'input',
                        'id'          => 'primary_text',
                        'name'        => 'clickwhale_smart_displays_options[primary][text]',
                        'type'        => 'text',
                        'value'       => $smart_displays_options['primary']['text'] ?? $defaults['smart_displays']['options']['primary']['text'],
                        'description' => __( 'Set default text for primary button', 'clickwhale' )
                )
        );
        add_settings_field(
                'primary_color',
                __( 'Text Color', 'clickwhale' ),
                array( $this, 'render_controls' ),
                'clickwhale_smart_displays_options',
                'smart_displays_settings_section',
                array(
                        'control'     => 'input',
                        'id'          => 'primary_color',
                        'class'       => 'cw-color-control',
                        'name'        => 'clickwhale_smart_displays_options[primary][color]',
                        'type'        => 'text',
                        'value'       => $smart_displays_options['primary']['color'] ?? $defaults['smart_displays']['options']['primary']['color'],
                        'description' => __( 'Set default text color (normal state) for primary button', 'clickwhale' )
                )
        );
        add_settings_field(
                'primary_color_hover',
                __( 'Text Color (hover/active)', 'clickwhale' ),
                array( $this, 'render_controls' ),
                'clickwhale_smart_displays_options',
                'smart_displays_settings_section',
                array(
                        'control'     => 'input',
                        'id'          => 'primary_color_hover',
                        'class'       => 'cw-color-control',
                        'name'        => 'clickwhale_smart_displays_options[primary][color_hover]',
                        'type'        => 'text',
                        'value'       => $smart_displays_options['primary']['color_hover'] ?? $defaults['smart_displays']['options']['primary']['color_hover'],
                        'description' => __( 'Set default text color (hover/active) for primary button', 'clickwhale' )
                )
        );
        add_settings_field(
                'primary_bg_color',
                __( 'Background Color', 'clickwhale' ),
                array( $this, 'render_controls' ),
                'clickwhale_smart_displays_options',
                'smart_displays_settings_section',
                array(
                        'control'     => 'input',
                        'id'          => 'primary_bg_color',
                        'class'       => 'cw-color-control',
                        'name'        => 'clickwhale_smart_displays_options[primary][bg_color]',
                        'type'        => 'text',
                        'value'       => $smart_displays_options['primary']['bg_color'] ?? $defaults['smart_displays']['options']['primary']['bg_color'],
                        'description' => __( 'Set default background color (normal state) for primary button', 'clickwhale' )
                )
        );
        add_settings_field(
                'primary_bg_color_hover',
                __( 'Background Color (hover/active)', 'clickwhale' ),
                array( $this, 'render_controls' ),
                'clickwhale_smart_displays_options',
                'smart_displays_settings_section',
                array(
                        'control'     => 'input',
                        'id'          => 'primary_bg_color_hover',
                        'class'       => 'cw-color-control',
                        'name'        => 'clickwhale_smart_displays_options[primary][bg_color_hover]',
                        'type'        => 'text',
                        'value'       => $smart_displays_options['primary']['bg_color_hover'] ?? $defaults['smart_displays']['options']['primary']['bg_color_hover'],
                        'description' => __( 'Set default background color (hover/active) for primary button', 'clickwhale' )
                )
        );
        add_settings_field(
                'primary_border_width',
                __( 'Border Width', 'clickwhale' ),
                array( $this, 'render_controls' ),
                'clickwhale_smart_displays_options',
                'smart_displays_settings_section',
                array(
                        'control'     => 'input',
                        'id'          => 'primary_border_width',
                        'name'        => 'clickwhale_smart_displays_options[primary][border][width][value]',
                        'type'        => 'number',
                        'min'         => $primary_border_defaults['width']['min'],
                        'max'         => $primary_border_defaults['width']['max'],
                        'value'       => intval( $smart_displays_options['primary']['border']['width']['value'] ?? $primary_border_defaults['width']['value'] ),
                        'description' => __( 'Set default border width for primary button', 'clickwhale' )
                )
        );
        add_settings_field(
                'primary_border_style',
                __( 'Border Style', 'clickwhale' ),
                array( $this, 'render_controls' ),
                'clickwhale_smart_displays_options',
                'smart_displays_settings_section',
                array(
                        'control'     => 'select',
                        'id'          => 'primary_border_style',
                        'name'        => 'clickwhale_smart_displays_options[primary][border][style]',
                        'value'       => esc_attr( $smart_displays_options['primary']['border']['style'] ?? $primary_border_defaults['style'] ),
                        'options'     => array_combine( $border_styles, $border_styles ),
                        'description' => __( 'Set default border style for primary button', 'clickwhale' )
                )
        );
        add_settings_field(
                'primary_border_radius',
                __( 'Border Radius', 'clickwhale' ),
                array( $this, 'render_controls' ),
                'clickwhale_smart_displays_options',
                'smart_displays_settings_section',
                array(
                        'control'     => 'input',
                        'id'          => 'primary_border_radius',
                        'name'        => 'clickwhale_smart_displays_options[primary][border][radius][value]',
                        'type'        => 'number',
                        'min'         => $primary_border_defaults['radius']['min'],
                        'max'         => $primary_border_defaults['radius']['max'],
                        'value'       => intval( $smart_displays_options['primary']['border']['radius']['value'] ?? $primary_border_defaults['radius']['value'] ),
                        'description' => __( 'Set default border radius for primary button', 'clickwhale' )
                )
        );
        add_settings_field(
                'primary_border_color',
                __( 'Border Color', 'clickwhale' ),
                array( $this, 'render_controls' ),
                'clickwhale_smart_displays_options',
                'smart_displays_settings_section',
                array(
                        'control'     => 'input',
                        'id'          => 'primary_border_color',
                        'class'       => 'cw-color-control',
                        'name'        => 'clickwhale_smart_displays_options[primary][border][color]',
                        'type'        => 'text',
                        'value'       => $smart_displays_options['primary']['border']['color'] ?? $primary_border_defaults['color'],
                        'description' => __( 'Set default border color (normal state) for primary button', 'clickwhale' )
                )
        );
        add_settings_field(
                'primary_border_color_hover',
                __( 'Border Color (hover/active)', 'clickwhale' ),
                array( $this, 'render_controls' ),
                'clickwhale_smart_displays_options',
                'smart_displays_settings_section',
                array(
                        'control'     => 'input',
                        'id'          => 'primary_border_color_hover',
                        'class'       => 'cw-color-control',
                        'name'        => 'clickwhale_smart_displays_options[primary][border][color_hover]',
                        'type'        => 'text',
                        'value'       => $smart_displays_options['primary']['border']['color_hover'] ?? $primary_border_defaults['color_hover'],
                        'description' => __( 'Set default border color (hover/active) for primary button', 'clickwhale' )
                )
        );

        /**
         * integrations options
         * @since 2.8.0
         */

        $amz_connect_defaults = $defaults['integrations']['options']['amz_connect'];
        $amz_connect_options  = $integrations_options['amz_connect'] ?? [];

        add_settings_field(
                'amz_connect_fields_about',
                __( 'About', 'clickwhale' ),
                array( $this, 'render_controls' ),
                'clickwhale_integrations_options',
                'amz_connect_settings_section',
                array(
                        'control' => 'html',
                        'id'      => 'amz_connect_fields_about',
                        'content' => Integrations_Helper::get_about_settings_filed( $amz_connect_defaults['about'], $amz_connect_defaults['url'] )
                )
        );
        add_settings_field(
                'amz_connect_fields_api_key',
                __( 'API Key', 'clickwhale' ),
                array( $this, 'render_controls' ),
                'clickwhale_integrations_options',
                'amz_connect_settings_section',
                array(
                        'control' => 'html',
                        'id'      => 'amz_connect_fields_api_key',
                        'name'    => 'clickwhale_integrations_options[amz_connect][connection][api_key]',
                        'content' => Integrations_Helper::get_amz_connect_api_key_field( $amz_connect_options['connection']['api_key'] ?? '' )
                )
        );

        add_settings_field(
                'amz_connect_usages_quota',
                __( 'Usage Quota', 'clickwhale' ),
                array( $this, 'render_controls' ),
                'clickwhale_integrations_options',
                'amz_connect_settings_section',
                array(
                        'control' => 'html',
                        'id'      => 'amz_connect_usages_quota',
                        'content' => Integrations_Helper::get_amz_connect_quota_field()
                )
        );

        add_settings_field(
                'amz_connect_fields_default_store',
                __( 'Store', 'clickwhale' ),
                array( $this, 'render_controls' ),
                'clickwhale_integrations_options',
                'amz_connect_settings_section',
                array(
                        'control'     => 'select',
                        'id'          => 'amz_connect_fields_default_store',
                        'name'        => 'clickwhale_integrations_options[amz_connect][fields][default_store]',
                        'value'       => esc_attr( $amz_connect_options['fields']['default_store'] ?? $amz_connect_defaults['defaults']['default_store'] ),
                        'options'     => array_map(
                                fn( $info ) => $info['flag'] . ' ' . $info['domain'] . ' (' . $info['name'] . ')',
                                Integrations_Helper::get_amz_connect_stores()
                        ),
                        'description' => __( 'Used as the default when creating a new Smart Display. Can be overridden per display.', 'clickwhale' )
                )
        );
        add_settings_field(
                'amz_connect_fields_tracking_id',
                __( 'Tracking ID', 'clickwhale' ),
                array( $this, 'render_controls' ),
                'clickwhale_integrations_options',
                'amz_connect_settings_section',
                array(
                        'control'     => 'input',
                        'id'          => 'amz_connect_fields_tracking_id',
                        'name'        => 'clickwhale_integrations_options[amz_connect][fields][tracking_id]',
                        'type'        => 'text',
                        'value'       => esc_attr( $amz_connect_options['fields']['tracking_id'] ?? $amz_connect_defaults['defaults']['tracking_id'] ),
                        'description' => sprintf(
                        /* translators: 1: Amazon Associates tracking ID example */
                                __( "Your Amazon Associates tracking ID (e.g. mysite-21). Without it, your sales won't be attributed to your account.", "clickwhale" ),
                                '<kbd>mysite-123</kbd>'
                        )
                )
        );

        add_settings_field(
                'amz_connect_fields_header_output_settings',
                __( 'Output Settings', 'clickwhale' ),
                array( $this, 'render_controls' ),
                'clickwhale_integrations_options',
                'amz_connect_settings_section',
                array(
                        'control' => 'header',
                )
        );

        add_settings_field(
                'amz_connect_fields_button_text',
                __( 'Button Text', 'clickwhale' ),
                array( $this, 'render_controls' ),
                'clickwhale_integrations_options',
                'amz_connect_settings_section',
                array(
                        'control'     => 'input',
                        'id'          => 'amz_connect_fields_button_text',
                        'name'        => 'clickwhale_integrations_options[amz_connect][fields][button_text]',
                        'type'        => 'text',
                        'value'       => esc_attr( ! empty( $amz_connect_options['fields']['button_text'] )
                                ? $amz_connect_options['fields']['button_text']
                                : $amz_connect_defaults['defaults']['button_text'] ),
                        'description' => __( 'Default text shown on the buy button. Can be overridden per display.', 'clickwhale' )
                )
        );

        $max_list_items_val = intval( $amz_connect_options['fields']['max_list_items'] ?? $amz_connect_defaults['defaults']['max_list_items'] );

        add_settings_field(
                'amz_connect_fields_max_list_items',
                __( 'Description Bullets', 'clickwhale' ),
                array( $this, 'render_controls' ),
                'clickwhale_integrations_options',
                'amz_connect_settings_section',
                array(
                        'control'     => 'input',
                        'type'        => 'number',
                        'id'          => 'amz_connect_fields_max_list_items',
                        'name'        => 'clickwhale_integrations_options[amz_connect][fields][max_list_items]',
                        'value'       => $max_list_items_val,
                        'min'         => 0,
                        'description' => __( 'Maximum number of description bullet points to show. Set to 0 for all.', 'clickwhale' ),
                )
        );

        add_settings_field(
                'amz_connect_fields_overflow_behavior',
                __( 'Long Descriptions', 'clickwhale' ),
                array( $this, 'render_controls' ),
                'clickwhale_integrations_options',
                'amz_connect_settings_section',
                array(
                        'control'     => 'radio',
                        'id'          => 'amz_connect_fields_overflow_behavior',
                        'name'        => 'clickwhale_integrations_options[amz_connect][fields][overflow_behavior]',
                        'value'       => $amz_connect_options['fields']['overflow_behavior'] ?? $amz_connect_defaults['defaults']['overflow_behavior'],
                        'options'     => array(
                                'truncate'  => __( 'Truncate', 'clickwhale' ),
                                'show_more' => __( 'Show "Show more" toggle', 'clickwhale' ),
                        ),
                        'disabled'    => $max_list_items_val === 0,
                        'description' => __( 'Used when a product has more bullet points than your limit.', 'clickwhale' ),
                )
        );

        do_action( 'clickwhale_settings_fields' );
    }

    /**
     * This functions provides a simple description for the Options page.
     * @since 1.0.0
     */
    public static function settings_section_callback( $args ) {
        echo '<p>' . esc_html( $args['text'] ) . '</p>';
    }

    public static function settings_subsection_callback( $args ) {
        ob_start();
        ?>
        <div class="clickwhale-box--header">
            <div class="clickwhale-box--header-left">
                <?php if ( ! empty( $args['image'] ) ) { ?>
                    <div class="clickwhale-box--header-image">
                        <img src="<?php echo esc_url( $args['image'] ); ?>"
                             alt="<?php echo esc_attr( $args['text'] ); ?>"/>
                    </div>
                <?php } ?>
                <div class="clickwhale-box--header-meta">
                    <h2><?php echo esc_html( $args['title'] ); ?></h2>
                    <p><?php echo esc_html( $args['text'] ); ?></p>
                </div>
            </div>
            <div class="clickwhale-box--header-right"></div>
        </div>
        <?php
        $content = ob_get_clean();

        echo $content;
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
                'general'        => array(
                        'name' => __( 'General', 'clickwhale' ),
                        'url'  => 'general_options'
                ),
                'tracking'       => array(
                        'name' => __( 'Tracking', 'clickwhale' ),
                        'url'  => 'tracking_options'
                ),
                'linkpages'      => array(
                        'name' => __( 'Link Pages', 'clickwhale' ),
                        'url'  => 'linkpages_options'
                ),
                'link_manager'   => array(
                        'name' => __( 'Link Manager', 'clickwhale' ),
                        'url'  => 'link_manager_options'
                ),
                'smart_displays' => array(
                        'name' => __( 'Smart Displays', 'clickwhale' ),
                        'url'  => 'smart_displays_options'
                ),
                'integrations'   => array(
                        'name' => __( 'Integrations', 'clickwhale' ),
                        'url'  => 'integrations_options'
                )
        ) );
    }

    /**
     * This function renders the interface elements.
     */
    public static function render_controls( $args ) {
        echo wp_kses( Helper::render_control( $args ), Helper::get_allowed_tags() );
    }

    public function filter_settings_tabs_capability() {
        $option_page = filter_input( INPUT_POST, 'option_page' );
        if ( empty( $option_page ) ) {
            return;
        }

        check_admin_referer( $option_page . '-options' );

        $tabs = self::render_tabs();

        foreach ( $tabs as $tab ) {
            $tab_option_page = 'clickwhale_' . $tab['url'];

            if ( $tab_option_page === sanitize_key( $option_page ) ) {
                add_filter( 'option_page_capability_' . $tab_option_page, array(
                        self::$instance,
                        'extend_capability'
                ) );
                add_filter( 'sanitize_option_' . $tab_option_page, array(
                        self::$instance,
                        'sanitize_option_capability'
                ) );
                break;
            }
        }
    }

    public function extend_capability( $capability ) {
        $current_user = wp_get_current_user();

        if ( ! $current_user->exists() ) {
            return $capability;
        }

        if ( $current_user->has_cap( 'manage_options' ) ) {
            return $capability;
        }

        $current_user_roles = clickwhale()->user->get_current_user_roles();

        if ( in_array( 'administrator', $current_user_roles ) ) {
            return $capability;
        }

        $general_options = get_option( 'clickwhale_general_options' );
        $access_roles    = $general_options['access_level'] ?? [ 'administrator' ];

        if ( array_intersect( $access_roles, $current_user_roles ) ) {
            set_transient( 'clickwhale_user_' . $current_user->ID . '_role_caps', $current_user->get_role_caps(), 10 ); // 10 seconds
            $current_user->add_cap( 'manage_options' );
        }

        return $capability;
    }

    public function sanitize_option_capability( $options ) {
        if ( in_array( 'administrator', clickwhale()->user->get_current_user_roles() ) ) {
            return $options;
        }

        $current_user = wp_get_current_user();

        if ( ! $current_user->exists() ) {
            return $options;
        }

        $cached_role_caps = get_transient( 'clickwhale_user_' . $current_user->ID . '_role_caps' );

        if ( ! $cached_role_caps ) {
            return $options;
        }

        if ( ! isset( $cached_role_caps['manage_options'] ) ) {
            $current_user->remove_cap( 'manage_options' );
        }

        delete_transient( 'clickwhale_user_' . $current_user->ID . '_role_caps' );

        if ( 'sanitize_option_clickwhale_general_options' !== current_filter() ) {
            return $options;
        }

        // `access_level` at General tab is hidden from non-admin roles.
        // To avoid saving the default option value we restore `access_level` that was set for current user
        if ( ! isset( $options['access_level'] ) ) {
            $general_options = get_option( 'clickwhale_general_options' );

            if ( isset( $general_options['access_level'] ) ) {
                $options['access_level'] = $general_options['access_level'];
            }
        }

        return $options;
    }

    /** Setting sanitize callbacks */

    /**
     * Fallback sanitize callback for Pro version compatibility.
     *
     * This fallback allows option groups defined by the Pro version to be
     * correctly stored by WordPress when registered via `register_setting()`,
     * even though the Free version does not define a dedicated
     * `sanitize_*_options()` method for them.
     *
     * It intentionally performs no sanitization and simply returns
     * the provided options array unchanged.
     *
     * Its primary purpose is to ensure that Pro options are persisted
     * together with Free options when saving settings.
     *
     * Sanitization for Pro-defined option groups is applied via the
     * `sanitize_option_clickwhale_*_options()` filter.
     *
     * @param mixed $options May be either raw options array or null in some cases
     *
     * @return array Unmodified options array
     */
    public function sanitize_passthrough_options( $options ): array {
        if ( ! is_array( $options ) ) {
            return array();
        }

        return map_deep( $options, 'sanitize_text_field' );
    }

    public function sanitize_general_options( $options ): array {
        if ( ! is_array( $options ) ) {
            $options = array();
        }

        // Access Level
        if ( isset( $options['access_level'] ) && is_array( $options['access_level'] ) ) {
            $options['access_level'] = array_map( 'sanitize_key', $options['access_level'] );
        }

        // Hide Admin Bar Menu
        $options['hide_admin_bar_menu'] = ! empty( $options['hide_admin_bar_menu'] ) ? 1 : 0;

        return $options;
    }

    public function sanitize_tracking_options( $options ): array {
        if ( ! is_array( $options ) ) {
            $options = array();
        }

        $defaults = self::default_options();

        // Tracking Duration
        if ( isset( $options['tracking_duration'] ) ) {
            $value                      = intval( $options['tracking_duration'] );
            $allowed_tracking_durations = array_keys( Helper::get_tracking_durations() );

            if ( in_array( $value, $allowed_tracking_durations, true ) ) {
                $options['tracking_duration'] = $value;
            } else {
                $options['tracking_duration'] = $defaults['tracking']['options']['tracking_duration'];
            }
        }

        // Disable Tracking
        $options['disable_tracking'] = ! empty( $options['disable_tracking'] ) ? 1 : 0;

        // Exclude User Roles
        if ( isset( $options['exclude_user_by_role'] ) && is_array( $options['exclude_user_by_role'] ) ) {
            $options['exclude_user_by_role'] = array_map( 'sanitize_key', $options['exclude_user_by_role'] );
        }

        return $options;
    }

    public function sanitize_link_manager_options( $options ): array {
        if ( ! is_array( $options ) ) {
            $options = array();
        }

        $defaults = self::default_options();

        // Redirection Type
        if ( isset( $options['redirect_type'] ) ) {
            $value                  = intval( $options['redirect_type'] );
            $allowed_redirect_types = array_keys( Links_Helper::get_redirections() );

            if ( in_array( $value, $allowed_redirect_types, true ) ) {
                $options['redirect_type'] = $value;
            } else {
                $options['redirect_type'] = $defaults['link_manager']['options']['redirect_type'];
            }
        }

        // Link Target
        if ( isset( $options['link_target'] ) ) {
            $value   = sanitize_key( $options['link_target'] );
            $allowed = array_keys( Links_Helper::get_link_targets() );

            if ( in_array( $value, $allowed, true ) ) {
                $options['link_target'] = $value;
            } else {
                $options['link_target'] = $defaults['link_manager']['options']['link_target'];
            }
        }

        // Nofollow Links
        $options['nofollow'] = ! empty( $options['nofollow'] ) ? 1 : 0;

        // Sponsored Links
        $options['sponsored'] = ! empty( $options['sponsored'] ) ? 1 : 0;

        // Link Prefix
        if ( ! empty( $options['slug'] ) ) {
            $options['slug'] = Links_Helper::sanitize_slug( $options['slug'] );
        }

        // Random Slug
        $options['random_slug'] = ! empty( $options['random_slug'] ) ? 1 : 0;

        return $options;
    }

    public function sanitize_linkpages_options( $options ): array {
        if ( ! is_array( $options ) ) {
            $options = array();
        }

        // Show Credits
        $options['show_linkpage_credits'] = ! empty( $options['show_linkpage_credits'] ) ? 1 : 0;

        return $options;
    }

    /**
     * @param array $options
     *
     * @return array
     * @since 2.7.0
     */
    public function sanitize_smart_displays_options( $options ): array {
        if ( ! is_array( $options ) ) {
            $options = array();
        }

        $border_options          = $options['primary']['border'] ?? [];
        $defaults                = self::default_options();
        $primary_border_defaults = $defaults['smart_displays']['options']['primary']['border'] ?? [];

        // Disclosure text
        $options['disclosure'] = sanitize_textarea_field( wp_unslash( $options['disclosure'] ?? '' ) );

        // Primary button text
        if ( '' === sanitize_text_field( wp_unslash( $options['primary']['text'] ?? '' ) ) ) {
            $options['primary']['text'] = $defaults['smart_displays']['options']['primary']['text'];
        }

        // Primary button text color
        if ( ! Helper::validate_hex_color( $options['primary']['color'] ) ) {
            $options['primary']['color'] = $defaults['smart_displays']['options']['primary']['color'];
        }

        if ( ! Helper::validate_hex_color( $options['primary']['color_hover'] ) ) {
            $options['primary']['color_hover'] = $defaults['smart_displays']['options']['primary']['color_hover'];
        }

        // Primary button background color
        if ( ! Helper::validate_hex_color( $options['primary']['bg_color'] ) ) {
            $options['primary']['bg_color'] = $defaults['smart_displays']['options']['primary']['bg_color'];
        }

        if ( ! Helper::validate_hex_color( $options['primary']['bg_color_hover'] ) ) {
            $options['primary']['bg_color_hover'] = $defaults['smart_displays']['options']['primary']['bg_color_hover'];
        }

        // Primary button border style
        $allowed_border_styles = Smart_Displays_Helper::get_border_styles();
        if ( isset( $border_options['style'] ) && in_array( $border_options['style'], $allowed_border_styles, true ) ) {
            $options['primary']['border']['style'] = $border_options['style'];
        } else {
            $options['primary']['border']['style'] = $primary_border_defaults['style'];
        }

        // Primary button border width
        $min_width   = intval( $primary_border_defaults['width']['min'] );
        $max_width   = intval( $primary_border_defaults['width']['max'] );
        $width_value = intval( $border_options['width']['value'] );

        $options['primary']['border']['width']['value'] = max( $min_width, min( $max_width, $width_value ) );

        // Primary button border color
        if ( ! Helper::validate_hex_color( $border_options['color'] ) ) {
            $options['primary']['border']['color'] = $primary_border_defaults['color'];
        }

        if ( ! Helper::validate_hex_color( $border_options['color_hover'] ) ) {
            $options['primary']['border']['color_hover'] = $primary_border_defaults['color_hover'];
        }

        // Primary button border radius
        $min_radius   = intval( $primary_border_defaults['radius']['min'] );
        $max_radius   = intval( $primary_border_defaults['radius']['max'] );
        $radius_value = intval( $border_options['radius']['value'] );

        $options['primary']['border']['radius']['value'] = max( $min_radius, min( $max_radius, $radius_value ) );

        return $options;
    }

    /**
     * @param array $options
     *
     * @return array
     * @since 2.8.0
     */
    public function sanitize_integrations_options( $options ): array {
        if ( ! is_array( $options ) ) {
            $options = array();
        }

        $submitted_key = $options['amz_connect']['connection']['api_key'] ?? null;

        if ( $submitted_key === '__SAVED__' ) {
            // Key unchanged — restore existing connection data as-is
            $existing                             = get_option( 'clickwhale_integrations_options', array() );
            $options['amz_connect']['connection'] = $existing['amz_connect']['connection'] ?? array();
        } elseif ( empty( $submitted_key ) ) {
            // Key explicitly removed
            $options['amz_connect']['connection'] = array(
                    'api_key'      => '',
                    'status'       => 'disconnected',
                    'validated_at' => null,
            );
            delete_transient( 'clickwhale_amz_connect_quota' );
        } else {
            // New key submitted — validate immediately and store result
            $is_valid                             = Integrations_Helper::amz_connect_validate_key( $submitted_key );
            $options['amz_connect']['connection'] = array(
                    'api_key'      => $submitted_key,
                    'status'       => $is_valid ? 'connected' : 'disconnected',
                    'validated_at' => current_time( 'mysql' ),
            );
            delete_transient( 'clickwhale_amz_connect_quota' );
        }

        $options['amz_connect']['fields']['max_list_items'] = max( 0, intval( $options['amz_connect']['fields']['max_list_items'] ?? 3 ) );

        $overflow                                              = $options['amz_connect']['fields']['overflow_behavior'] ?? 'truncate';
        $options['amz_connect']['fields']['overflow_behavior'] = in_array( $overflow, array(
                'truncate',
                'show_more'
        ), true ) ? $overflow : 'truncate';

        return $options;
    }
}
