<?php

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
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
	}

	/**
	 * Get ID of about logged-in user
	 *
	 * @return      string
	 * @since       1.0.0
	 */
	public function get_loggedin_user_id() {
		return is_user_logged_in() ? get_current_user_id() : '';
	}

	/**
	 * Get current user roles array (yes, array!)
	 *
	 * @return      array
	 * @since       1.0.0
	 */
	public function get_current_user_roles() {
		$id = $this->get_loggedin_user_id();

		return $user_meta = $id ? get_userdata( $id )->roles : false;
	}

	/**
	 * Get track options from options table
	 *
	 * @return      array
	 * @since       1.0.0
	 */
	public function get_track_options() {
		return get_option( 'clickwhale_tracking_options' );
	}

	/**
	 * Get disalowed user roles from get_track_options() function
	 *
	 * @return      array
	 * @since       1.0.0
	 */
	public function get_disallowed_user_roles() {
		$tracking_options = $this->get_track_options();

		return isset( $tracking_options['exclude_users_by_role'] ) ? $tracking_options['exclude_users_by_role'] : '';
	}

	/**
	 * Check current user for tracking by roles
	 *
	 * @return      bool
	 * @since       1.0.0
	 */
	public function is_user_untracked() {
		$current_user_roles = $this->get_current_user_roles();
		$disallowed_roles   = $this->get_disallowed_user_roles();

		if ( is_array( $current_user_roles ) && is_array( $disallowed_roles ) ) {
			// if current user role in array of disalowed roles
			return count( array_intersect( $current_user_roles, $disallowed_roles ) ) > 0;
		} else {
			return false; // user can be tracked
		}
	}

	/**
	 * Check track ability by plugin settings page
	 *
	 * @return      bool
	 * @since       1.0.0
	 */
	public function is_track_disabled() {
		$tracking_options = $this->get_track_options();

		return isset( $tracking_options['disable_click_tracking'] ) ? $tracking_options['disable_click_tracking'] : false;
	}

	/**
	 * Check track ability
	 *
	 * @return      bool
	 * @since       1.0.0
	 */
	public function disallow_track() {
		return $this->is_track_disabled() || $this->is_user_untracked();
	}

}