<?php
namespace clickwhale\includes\admin;

use WP_User;
use clickwhale\includes\helpers\Helper;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * class Clickwhale_WP_User
 *
 * WP User information and is user able to be tracked
 *
 * @since 1.0.0
 *
 * @package    Clickwhale
 * @subpackage Clickwhale/public
 */
class Clickwhale_WP_User {

    /**
     * @var WP_User|null
     */
    private ?WP_User $user;

    public function __construct() {
        $this->user = wp_get_current_user();
    }

    public function get_user(): ?WP_User {
        return $this->user;
    }

    public function get_all_roles(): array {
        global $wp_roles;
        $roles = array();

        if ( ! empty( $wp_roles->roles ) ) {
            foreach ( $wp_roles->roles as $k => $v ) {
                $roles[$k] = $v['name'];
            }
        }

        return $roles;
    }

    /**
     * Get current user roles array
     *
     * @return array
     * @since  1.0.0
     */
    public function get_current_user_roles(): array {
        if ( ! $this->user->exists() ) {
            return array();
        }

        return $this->user->roles;
    }

    /**
     * Check if access for current user role is granted
     *
     * @return bool
     */
    public function is_current_user_role_access_granted(): bool {
        if ( ! $this->user->exists() ) {
            return false;
        }

        if ( $this->user->has_cap( 'manage_options' ) ) {
            return true;
        }

        $current_user_roles = $this->get_current_user_roles();

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
     * Check track ability
     *
     * @return bool
     */
    public function is_tracking_disabled(): bool {
        $tracking_options = get_option( 'clickwhale_tracking_options' );

        if ( ! empty( $tracking_options['disable_tracking'] ) ) {
            return true;
        }

        if ( empty( $tracking_options['exclude_user_by_role'] ) ) {
            return false;
        }

        $current_user_roles = $this->get_current_user_roles();

        if ( empty( $current_user_roles ) ) {
            return false;
        }

        return count( array_intersect( $current_user_roles, $tracking_options['exclude_user_by_role'] ) ) > 0;
    }
}
