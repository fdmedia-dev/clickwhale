<?php

class ClickWhale_Migration {

    public function __construct() {
    }

    public function init() {
        $this->load_dependencies();
        $this->dispath_actions();
    }

    private function load_dependencies() {

		require_once plugin_dir_path( dirname( __FILE__ ) ) .  'admin/migration/class-clickwhale-migration-interface.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) .  'admin/migration/class-clickwhale-migration-notice.php';

        // load classes if plugin active
        foreach ($this->available_migrations() as $item) {
            if ($this->check_active($item['path'])) {
                require_once plugin_dir_path( dirname( __FILE__ ) ) .  'admin/migration/class-' . $item['slug'] . '-to-clickwhale.php';
            }
        }

    }

    /**
     * Set target plugin data for migration
     */
    public function available_migrations() {

		return array(
			'betterlinks' => array(
                'slug'  => 'betterlinks',
                'name'  => 'Betterlinks',
                'path'  => 'betterlinks/betterlinks.php',
                'class' => 'BetterLinks_To_Clickwhale',
                'data'  => $this->count_betterlinks_data(),
            ),
            'thirstyaffiliates' => array(
                'slug'  => 'thirstyaffiliates',
                'name'  => 'ThirstyAffiliates',
                'path'  => 'thirstyaffiliates/thirstyaffiliates.php',
                'class' => 'ThirstyAffiliates_To_Clickwhale',
                'data'  => $this->count_thirstyaffiliates_data(),
            ),
            'prettylinks' => array(
                'slug'  => 'prettylinks',
                'name'  => 'PrettyLinks',
                'path'  => 'pretty-link/pretty-link.php',
                'class' => 'PrettyLinks_To_Clickwhale',
                'data'  => $this->count_prettylinks_data(),
            )
		);
	}

    public function check_active($path) {
        return in_array( $path, apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) );
    }

    /**
     * Count links and categories for plugins
     */
    public function count_betterlinks_data(){
        global $wpdb;

        $result             = [];
        $table_links        = $wpdb->prefix . 'betterlinks';
        $table_categories   = $wpdb->prefix . 'betterlinks_terms';

        $result['links']      = $wpdb->get_var("SELECT COUNT(*) FROM $table_links");
        $result['categories'] = $wpdb->get_var("SELECT COUNT(*) FROM $table_categories");

        return $result;
    }

    public function count_thirstyaffiliates_data(){
        global $wpdb;
        
        $result             = [];
        $table_links        = $wpdb->prefix . 'posts';
        $table_categories   = $wpdb->prefix . 'term_taxonomy';

        $result['links']      = $wpdb->get_var("SELECT COUNT(*) FROM $table_links WHERE post_type='thirstylink' AND post_status='publish'");
        $result['categories'] = $wpdb->get_var("SELECT COUNT(*) FROM $table_categories WHERE taxonomy='thirstylink-category'");

        return $result;
    }

    public function count_prettylinks_data(){
        global $wpdb;
        
        $result      = [];
        $table_links = $wpdb->prefix . 'prli_links';

        $result['links']      = $wpdb->get_var("SELECT COUNT(*) FROM $table_links");
        $result['categories'] = '';

        return $result;
    }

    public function dispath_actions(){
        $available_migrations = $this->available_migrations();

        foreach($available_migrations as $item){
            if($this->check_active($item['path'])){
              $migration = new ClickWhale_Migration_Notice($item['slug'], $item['name'], $item['path']);
              $migration->init(); 
            }
        }
	}

}