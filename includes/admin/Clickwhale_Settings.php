<?php
namespace clickwhale\includes\admin;

use clickwhale\includes\Clickwhale;
use clickwhale\includes\helpers\Helper;
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
	private static $instance;

	/**
	 * @return Clickwhale_Settings|null
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
		$defaults               = self::default_options();
		$general_options        = get_option( 'clickwhale_general_options' );
		$tracking_options       = get_option( 'clickwhale_tracking_options' );
		$linkpages_options      = get_option( 'clickwhale_linkpages_options' );

		$duration = apply_filters( 'clickwhale_tracking_duration', array(
			30 => __( '30 days', CLICKWHALE_NAME ),
		) );

        $current_user = Clickwhale::get_instance()->user;
        $current_user_roles = $current_user::get_current_user_roles();

		if ( $defaults ) {

			// Add settings sections

			// Register settings
			foreach ( $defaults as $k => $v ) {

				if ( ! $v['options'] ) {
					continue;
				}

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

        // `always_checked` current user roles
        $always_checked_roles = ['administrator'];

        if ( ! in_array( 'administrator', $current_user_roles ) ) {
            foreach ( $current_user_roles as $user_role ) {
                $always_checked_roles[] = $user_role;
            }
        }

		// Add fields

        // General options
		add_settings_field(
			'access_level',
			__( 'Access Level', CLICKWHALE_NAME ),
			array( $this, 'render_controls' ),
			'clickwhale_general_options',
			'general_settings_section',
			array(
				'control'        => 'checkboxes',
				'id'             => 'access_level',
				'name'           => 'clickwhale_general_options[access_level][]',
				'value'          => $general_options['access_level'] ?? [ 'administrator' ],
				'options'        => Clickwhale_WP_User::get_all_roles(),
				'always_checked' => $always_checked_roles,
				'description'    => __( 'Decide who can access critical admin pages and the plugin settings.',
					CLICKWHALE_NAME ),
			)
		);
		add_settings_field(
			'redirection',
			__( 'Redirection Type', CLICKWHALE_NAME ),
			array( $this, 'render_controls' ),
			'clickwhale_general_options',
			'general_settings_section',
			array(
				'control'     => 'select',
				'id'          => 'redirect_type',
				'name'        => 'clickwhale_general_options[redirect_type]',
				'value'       => ! empty( $general_options['redirect_type'] ) ? $general_options['redirect_type'] : $defaults['general']['options']['redirect_type'],
				'options'     => array(
					301 => __( '301 redirect: Moved permanently', CLICKWHALE_NAME ),
					302 => __( '302 redirect: Found / Moved temporarily', CLICKWHALE_NAME ),
					303 => __( '303 redirect: See Other', CLICKWHALE_NAME ),
					307 => __( '307 redirect: Temporarily Redirect', CLICKWHALE_NAME ),
					308 => __( '308 redirect: Permanent Redirect', CLICKWHALE_NAME )
				),
				'description' => __( 'Set default redirection type which will be used for new links.', CLICKWHALE_NAME ),
			)
		);
		add_settings_field(
			'nofollow',
			__( 'Nofollow Links', CLICKWHALE_NAME ),
			array( $this, 'render_controls' ),
			'clickwhale_general_options',
			'general_settings_section',
			array(
				'control' => 'checkbox',
				'id'      => 'nofollow',
				'name'    => 'clickwhale_general_options[nofollow]',
				'value'   => isset( $general_options['nofollow'] ) ? 1 : 0,
				'label'   => __( 'Check to mark links as nofollow & noindex by default', CLICKWHALE_NAME ),
			)
		);
		add_settings_field(
			'sponsored',
			__( 'Sponsored Links', CLICKWHALE_NAME ),
			array( $this, 'render_controls' ),
			'clickwhale_general_options',
			'general_settings_section',
			array(
				'control'     => 'checkbox',
				'id'          => 'sponsored',
				'name'        => 'clickwhale_general_options[sponsored]',
				'value'       => isset( $general_options['sponsored'] ) ? 1 : 0,
				'label'       => __( 'Check to mark links as sponsored by default.', CLICKWHALE_NAME ),
				'description' => __( 'Recommended for affiliate links.', CLICKWHALE_NAME ),
			)
		);
		add_settings_field(
			'slug',
			__( 'Link Prefix', CLICKWHALE_NAME ),
			array( $this, 'render_controls' ),
			'clickwhale_general_options',
			'general_settings_section',
			array(
				'control'     => 'input',
				'id'          => 'slug',
				'name'        => 'clickwhale_general_options[slug]',
				'type'        => 'text',
				'value'       => $general_options['slug'],
				'placeholder' => '',
				'description' => __( 'Here, you can enter a prefix that will be prepended when creating a new link. For example: <em>link</em>.<br><strong>Important:</strong> If you change the prefix, it will <u>not</u> affect already existing links.', CLICKWHALE_NAME ),
			)
		);
		add_settings_field(
			'random_slug',
			__( 'Random Slug', CLICKWHALE_NAME ),
			array( $this, 'render_controls' ),
			'clickwhale_general_options',
			'general_settings_section',
			array(
				'control' => 'checkbox',
				'id'      => 'random_slug',
				'name'    => 'clickwhale_general_options[random_slug]',
				'value'   => isset( $general_options['random_slug'] ) ? 1 : 0,
				'label'   => __( 'Check to <u>not</u> suggest a random link slug when creating a new link', CLICKWHALE_NAME ),
			)
		);
		add_settings_field(
			'hide_admin_bar_menu',
			__( 'Hide Admin Bar Menu', CLICKWHALE_NAME ),
			array( $this, 'render_controls' ),
			'clickwhale_general_options',
			'general_settings_section',
			array(
				'control' => 'checkbox',
				'id'      => 'hide_admin_bar_menu',
				'name'    => 'clickwhale_general_options[hide_admin_bar_menu]',
				'value'   => isset( $general_options['hide_admin_bar_menu'] ) ? 1 : 0,
				'label'   => __( 'Check to hide Clickwhale quick menu from the admin bar', CLICKWHALE_NAME ),
			)
		);

        // Tracking options
		add_settings_field(
			'tracking_duration',
			__( 'Tracking Duration', CLICKWHALE_NAME ),
			array( $this, 'render_controls' ),
			'clickwhale_tracking_options',
			'tracking_settings_section',
			array(
				'control' => 'select',
				'id'      => 'tracking_duration',
				'name'    => 'clickwhale_tracking_options[tracking_duration]',
				'value'   => $tracking_options['tracking_duration'] ?? $defaults['tracking']['options']['tracking_duration'],
				'options' => $duration
			)
		);
		add_settings_field(
			'disable_tracking',
			__( 'Disable Tracking', CLICKWHALE_NAME ),
			array( $this, 'render_controls' ),
			'clickwhale_tracking_options',
			'tracking_settings_section',
			array(
				'control' => 'checkbox',
				'id'      => 'disable_tracking',
				'name'    => 'clickwhale_tracking_options[disable_tracking]',
				'value'   => isset( $tracking_options['disable_tracking'] ) ? 1 : 0,
				'label'   => __( 'Check to disable tracking of views and clicks', CLICKWHALE_NAME ),
			)
		);
		add_settings_field(
			'exclude_user_by_role',
			__( 'Exclude User Roles', CLICKWHALE_NAME ),
			array( $this, 'render_controls' ),
			'clickwhale_tracking_options',
			'tracking_settings_section',
			array(
				'control'     => 'checkboxes',
				'id'          => 'exclude_user_by_role',
				'name'        => 'clickwhale_tracking_options[exclude_user_by_role][]',
				'value'       => $tracking_options['exclude_user_by_role'] ?? 0,
				'options'     => Clickwhale_WP_User::get_all_roles(),
				'description' => __( 'Check the user roles that should be excluded from tracking.', CLICKWHALE_NAME ),
			)
		);

        // Linkpages options
		add_settings_field(
			'linkpage_links_target',
			__( 'Links: Target', CLICKWHALE_NAME ),
			array( $this, 'render_controls' ),
			'clickwhale_linkpages_options',
			'linkpages_settings_section',
			array(
				'control' => 'checkbox',
				'id'      => 'linkpage_links_target',
				'name'    => 'clickwhale_linkpages_options[linkpage_links_target]',
				'value'   => isset( $linkpages_options['linkpage_links_target'] ) ? 1 : 0,
				'label'   => __( 'Check to open links in a new tab/window.', CLICKWHALE_NAME ),
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
		$defaults = self::default_options();
		$tabs     = array(
			'general'        => array(
				'name' => __( 'General Options', CLICKWHALE_NAME ),
				'url'  => 'general_options',
			),
			'tracking'       => array(
				'name' => __( 'Tracking Options', CLICKWHALE_NAME ),
				'url'  => 'tracking_options'
			),
			'linkpages'      => array(
				'name' => __( 'Link Pages', CLICKWHALE_NAME ),
				'url'  => 'linkpages_options'
			),
			'tracking_codes' => array(
				'name' => __( 'Tracking Codes', CLICKWHALE_NAME ),
				'url'  => 'tracking_codes_options'
			),
			'other'          => array(
				'name' => __( 'Other Options', CLICKWHALE_NAME ),
				'url'  => 'other_options'
			)
		);

		$tabs = apply_filters( 'clickwhale_settings_tabs', $tabs );

		foreach ( $tabs as $k => $v ) {
			if ( ! $defaults[ $k ]['options'] ) {
				unset ( $tabs[ $k ] );
			}
		}

		return $tabs;
	}

	/**
	 * This function renders the interface elements.
	 */
	public static function render_controls( $args ) {
		echo Helper::render_control( $args );
	}

    public function add_capability( $capability ) {

        $general_options = get_option( 'clickwhale_general_options' );
        $access_roles = $general_options['access_level'] ?? ['administrator'];

        $current_user = Clickwhale::get_instance()->user;
        $current_user_roles = $current_user::get_current_user_roles();

        if ( in_array( 'administrator', $current_user_roles ) ) {
            return $capability;
        }

        if ( $current_user->get_user()->has_cap( 'manage_options' ) ) {
            return $capability;
        }

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