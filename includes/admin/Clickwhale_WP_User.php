<?php
namespace clickwhale\includes\admin;

use WP_User;
use clickwhale\includes\helpers\Helper;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * WP User information and is user able to be tracked
 *
 * @link       #
 * @since      1.0.0
 *
 * @package    Clickwhale
 * @subpackage Clickwhale/public
 */

/**
 * class Clickwhale_WP_User
 *
 */
class Clickwhale_WP_User {

    /**
     * @var WP_User
     */
	protected $user;

	public function __construct() {
		$this->user = wp_get_current_user();
	}

    public function get_user(): WP_User {
        return $this->user;
    }

	public static function get_all_roles(): array {
		global $wp_roles;

		$roles = [];

		foreach ( $wp_roles->roles as $k => $v ) {
			$roles[ $k ] = $v['name'];
		}

		return $roles;
	}

	/**
	 * Get ID of about logged-in user
	 *
	 * @return      string
	 * @since       1.0.0
	 */
	public static function get_logged_in_user_id(): string {
		return is_user_logged_in() ? get_current_user_id() : '';
	}

	/**
	 * Get current user roles array
	 *
	 * @return      array
	 * @since       1.0.0
	 */
	public static function get_current_user_roles(): array {
		$id = self::get_logged_in_user_id();

		return $id ? get_userdata( $id )->roles : array();
	}

	/**
	 * Check if access for current user role is granted
	 *
	 * @return bool
	 */
    static public function is_current_user_role_access_granted(): bool {
        $current_user_roles = self::get_current_user_roles();

        if ( in_array( 'administrator', $current_user_roles ) ) {
            return true;
        }

        $access_roles = Helper::get_clickwhale_option( 'general', 'access_level' );

		return ( ! empty( $access_roles ) &&
                 ! empty( $current_user_roles ) &&
                 array_intersect( $access_roles, $current_user_roles )
        );
	}

	/**
	 * Get track options from options table
	 *
	 * @return      array
	 * @since       1.0.0
	 */
	public function get_track_options(): array {
		return get_option( 'clickwhale_tracking_options' );
	}

	/**
	 * Get disallowed user roles from get_track_options() function
	 *
	 * @return      array
	 * @since       1.0.0
	 */
	public function get_disallowed_user_roles(): array {
		$tracking_options = $this->get_track_options();

		if ( isset( $tracking_options['exclude_user_by_role'] ) ) {
			return $tracking_options['exclude_user_by_role'];
		}

		return array();
	}

	/**
	 * Check current user for tracking by roles
	 *
	 * @return      bool
	 * @since       1.0.0
	 */
	public function is_user_untracked(): bool {
		$current_user_roles = $this->get_current_user_roles();
		$disallowed_roles   = $this->get_disallowed_user_roles();

		if ( ! empty( $current_user_roles ) && ! empty( $disallowed_roles ) ) {
			// if current user role in array of disallowed roles
			return count( array_intersect( $current_user_roles, $disallowed_roles ) ) > 0;
		} else {
			// user can be tracked
			return false;
		}
	}

	/**
	 * Check track ability by plugin settings page
	 *
	 * @return      bool
	 * @since       1.0.0
	 */
	public function is_track_disabled(): bool {
		$tracking_options = $this->get_track_options();

		return $tracking_options['disable_tracking'] ?? false;
	}

	/**
	 * Check track ability
	 *
	 * @return      bool
	 * @since       1.0.0
	 */
	public function disallow_track(): bool {
		return $this->is_track_disabled() || $this->is_user_untracked();
	}
}