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
 * Class Clickwhale_WP_User
 *
 */
class Clickwhale_WP_User {
	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

    /**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

    /**
     * Get ID of about logged-in user
     * 
     * @since       1.0.0 
     * @return      string
     */
    public function get_loggedin_user_id(){
        return is_user_logged_in() ? get_current_user_id() : '';
    }

    /**
     * Get current user roles array (yes, array!)
     * 
     * @since       1.0.0 
     * @return      array
     */
    public function get_current_user_roles(){
        $id = $this->get_loggedin_user_id();

        return $user_meta = $id ? get_userdata($id)->roles : false;
    }

    /**
     * Get track options from options table
     * 
     * @since       1.0.0 
     * @return      array
     */
    public function get_track_options(){
        return get_option('clickwhale_tracking_options');
    }

    /**
     * Get disalowed user roles from get_track_options() function
     * 
     * @since       1.0.0 
     * @return      array
     */
    public function get_disallowed_user_roles(){
        $tracking_options = $this->get_track_options();

        return isset($tracking_options['exclude_users_by_role']) ? $tracking_options['exclude_users_by_role'] : '';
    }

    /**
     * Check current user for tracking by roles
     * 
     * @since       1.0.0 
     * @return      bool
     */
    public function is_user_untracked(){
        $current_user_roles = $this->get_current_user_roles();
        $disallowed_roles = $this->get_disallowed_user_roles();

        if(is_array($current_user_roles) && is_array($disallowed_roles)){
            // if current user role in array of disalowed roles
            return count(array_intersect($current_user_roles, $disallowed_roles)) > 0 ? true : false;
        } else {
            return false; // user can be tracked
        } 
    }

    /**
     * Check track ability by plugin settings page
     * 
     * @since       1.0.0 
     * @return      bool
     */
    public function is_track_disabled(){
        $tracking_options = $this->get_track_options();

        return isset($tracking_options['disable_click_tracking']) ? $tracking_options['disable_click_tracking'] : false;
    }

    /**
     * Check track ability
     * 
     * @since       1.0.0 
     * @return      bool
     */
    public function disallow_track(){
        return $this->is_track_disabled() || $this->is_user_untracked();
    }

}