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

    public function migration_notice_hide() {
        $type   = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : '';
        $plugin = isset($_POST['plugin']) ? sanitize_text_field($_POST['plugin']) : '';

        check_ajax_referer('clickwhale_' . $plugin . '_admin_nonce', 'security');

        if (!current_user_can('manage_options')) {
            wp_die();
        }

        if($type === 'migrate') {
            $options_migrate = get_option('clickwhale_hide_notice_migrate');
            $options_migrate[$plugin] = true;
            update_option('clickwhale_hide_notice_migrate', $options_migrate);
        } else if($type === 'deactive') {
            $options_deactive = get_option('clickwhale_hide_notice_deactive');
            $options_deactive[$plugin] = true;
            update_option('clickwhale_hide_notice_deactive', $options_deactive);
        }
        wp_die();
    }

    public function migration_deactive() {
        $plugin = isset($_POST['plugin']) ? sanitize_text_field($_POST['plugin']) : '';
        $target = isset($_POST['target']) ? sanitize_text_field($_POST['target']) : '';

        check_ajax_referer('clickwhale_' . $plugin . '_admin_nonce', 'security');

        if (!current_user_can('manage_options')) {
            wp_die();
        }
        
        $deactivate = deactivate_plugins($target);

        wp_send_json_success($deactivate);

        wp_die();
    }


    public function migration_to_clickwhale() {
        $result  = [];
        $i       = 0;
        $plugins = new ClickWhale_Migration();
        $options = get_option('clickwhale_tools_migration_options');

        foreach ($plugins->available_migrations() as $item) {
            if ($plugins->check_active($item['path'])) {
                $result[$i]           = [];
                $result[$i]['title']  = $item['name'];
        
                if(isset($options[$item['slug'].'_categories']) 
                  || isset($options[$item['slug'].'_links'])
                ) {
                    $migrator            = new $item['class']();
                    $result[$i]['data']  = $migrator->run_migration(
                        isset($options[$item['slug'].'_categories']), 
                        isset($options[$item['slug'] . '_links'])
                    );
                } else {
                    $result[$i]['data']  = __('Nothing to migrate', 'clickwhale');
                }
                $i++;
            }
        }

        wp_send_json_success($result);

        wp_die();
    }

}