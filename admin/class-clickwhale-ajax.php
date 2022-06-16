<?php

/**
 * The settings of the plugin.
 *
 * @link       #
 * @since      1.0.0
 *
 * @package    Clickwhale
 * @subpackage Clickwhale/admin
 */

class Clickwhale_Ajax{

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

    public function migration_betterlinks_notice_hide() {

        check_ajax_referer('clickwhale_betterlinks_admin_nonce', 'security');
        if (!current_user_can('manage_options')) {
            wp_die();
        }

        $type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : '';

        if ($type == 'deactive') {
            $options_deactive = get_option('clickwhale_hide_betterlinks_notice_deactive');
            $options_deactive['betterlinks'] = true;
            update_option('clickwhale_hide_betterlinks_notice_deactive', $options_deactive);
        } elseif ($type == 'migrate') {
            $options_migrate = get_option('clickwhale_hide_betterlinks_notice_migrate');
            $options_migrate['betterlinks'] = true;
            update_option('clickwhale_hide_betterlinks_notice_migrate', $options_migrate);
        }

        wp_die();

    }

    public function deactive_betterlinks() {

        check_ajax_referer('clickwhale_betterlinks_admin_nonce', 'security');

        if (!current_user_can('manage_options')) {
            wp_die();
        }
        $target     = isset($_POST['plugin']) ? sanitize_text_field($_POST['plugin']) : '';
        $deactivate = deactivate_plugins($target);

        wp_send_json_success($deactivate);
        wp_die();

    }

    public function migration_thirstyaffiliates_notice_hide() {

        check_ajax_referer('clickwhale_thirstyaffiliates_admin_nonce', 'security');

        if (!current_user_can('manage_options')) {
            wp_die();
        }

        $type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : '';

        if ($type == 'deactive') {
            $options_deactive = get_option('clickwhale_hide_thirstyaffiliates_notice_deactive');
            $options_deactive['thirstyaffiliates'] = true;
            update_option('clickwhale_hide_thirstyaffiliates_notice_deactive', $options_deactive);
        } elseif ($type == 'migrate') {
            $options_migrate = get_option('clickwhale_hide_thirstyaffiliates_notice_migrate');
            $options_migrate['thirstyaffiliates'] = true;
            update_option('clickwhale_hide_thirstyaffiliates_notice_migrate', $options_migrate);
        }

        wp_die();

    }

    public function deactive_thirstyaffiliates() {

        check_ajax_referer('clickwhale_thirstyaffiliates_admin_nonce', 'security');

        if (!current_user_can('manage_options')) {
            wp_die();
        }

        $target     = isset($_POST['plugin']) ? sanitize_text_field($_POST['plugin']) : '';
        $deactivate = deactivate_plugins($target);

        wp_send_json_success($deactivate);
        wp_die();

    }

    public function migration_to_clickwhale() {
        $result  = [];
        $i       = 0;
        $plugins = new ClickWhale_Migration();
        $options = get_option('clickwhale_tools_migration_options');

        if ($plugins->check_betterlinks_active()) {
            $result[$i]           = [];
            $result[$i]['title']  = 'BetterLinks';

            if(isset($options['betterlinks_categories']) || isset($options['betterlinks_links'])) {
                $migrator            = new BetterLinks_To_Clickwhale();
                $result[$i]['data']  = $migrator->run_migration(isset($options['betterlinks_categories']), isset($options['betterlinks_links']));
            } else {
                $result[$i]['data']  = __('Nothing to migrate', 'clickwhale');
            }
            $i++;
        }

        if ($plugins->check_thirstyaffiliates_active()) {
            $result[$i]           = [];
            $result[$i]['title']  = 'ThirstyAffiliates';

            if(isset($options['thirstyaffiliates_categories']) || isset($options['thirstyaffiliates_links'])) {
                $migrator            = new ThirstyAffiliates_To_Clickwhale();
                $result[$i]['data']  = $migrator->run_migration(isset($options['thirstyaffiliates_categories']), isset($options['thirstyaffiliates_links']));
            } else {
                $result[$i]['data']  = __('Nothing to migrate', 'clickwhale');
            }
            $i++;
        }

        wp_send_json_success($result);

        wp_die();
    }

}