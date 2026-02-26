<?php

namespace clickwhale\includes\admin;

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
		$current_user_roles   = clickwhale()->user->get_current_user_roles();
		$always_checked_roles = array( 'administrator' );

		$slug = ( ! empty( $link_manager_options['slug'] ) ) ? esc_attr( wp_unslash( $link_manager_options['slug'] ) ) : $defaults['link_manager']['options']['slug'];

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

		do_action( 'clickwhale_settings_fields' );
	}

	/**
	 * This functions provides a simple description for the Options page.
	 * @since 1.0.0
	 */
	public static function settings_section_callback( $args ) {
		echo '<p>' . esc_html( $args['text'] ) . '</p>';
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
				'url'  => 'general_options'
			),
			'tracking'     => array(
				'name' => __( 'Tracking', 'clickwhale' ),
				'url'  => 'tracking_options'
			),
			'linkpages'    => array(
				'name' => __( 'Link Pages', 'clickwhale' ),
				'url'  => 'linkpages_options'
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
}
