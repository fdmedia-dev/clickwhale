<?php
namespace clickwhale\includes\admin;

use clickwhale\includes\helpers\Helper;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * class Clickwhale_WP_User
 *
 * WP User information and is user able to be tracked.
 *
 * In some cases trying to access WP_User was too early and did not return a valid user instance,
 * so it is no longer called in the constructor
 *
 * @since 1.0.0
 *
 * @package    Clickwhale
 * @subpackage Clickwhale/public
 */
class Clickwhale_WP_User {

    public function get_all_roles(): array {
        global $wp_roles;
        $roles = array();

        if ( ! empty( $wp_roles->roles ) ) {
            foreach ( $wp_roles->roles as $key => $data ) {
                $roles[$key] = $data['name'];
            }
        }

        return $roles;
    }

    public function get_roles_with_upload_cap(): array {
        $roles = array();
        $all_roles = $this->get_all_roles();

        if ( ! empty( $all_roles ) ) {
            foreach ( $all_roles as $key => $name ) {
                $role = get_role( $key );

                if ( $role && $role->has_cap( 'upload_files' ) ) {
                    $roles[$key] = $name;
                }
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
        $user = wp_get_current_user();
        if ( ! $user->exists() ) {
            return array();
        }

        return $user->roles;
    }

    /**
     * Check if access for current user role is granted
     *
     * @return bool
     */
    public function is_current_user_role_access_granted(): bool {
        $user = wp_get_current_user();
        if ( ! $user->exists() ) {
            return false;
        }

        if ( $user->has_cap( 'manage_options' ) ) {
            return true;
        }

        $current_user_roles = $user->roles;

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

        $user = wp_get_current_user();
        if ( ! $user->exists() ) {
            return false;
        }
        $current_user_roles = $user->roles;

        if ( empty( $current_user_roles ) ) {
            return false;
        }

        return count( array_intersect( $current_user_roles, $tracking_options['exclude_user_by_role'] ) ) > 0;
    }
}
