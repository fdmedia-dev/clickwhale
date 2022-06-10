<?php

class ClickWhale_Migration {

    public function __construct() {

    }

    public function init() {
        $this->load_dependencies();
        $this->tools_migration();
    }

    private function load_dependencies() {

		require_once plugin_dir_path( dirname( __FILE__ ) ) .  'admin/migration/class-clickwhale-tools-migration.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) .  'admin/migration/class-clickwhale-migration-interface.php';
		
        // load classes if plugin active
        if ($this->check_betterlinks_active()) {
            require_once plugin_dir_path( dirname( __FILE__ ) ) .  'admin/migration/class-betterlinks-to-clickwhale.php';
        }

    }

    public function check_betterlinks_active() {
        return in_array( 'betterlinks/betterlinks.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) );
    }

    public function check_thirstyaffiliates_active() {
        return in_array( 'thirstyaffiliates/thirstyaffiliates.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) );
    }

    public function check_prettylinks_active() {
        return in_array( 'prettylinks/prettylinks.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) );
    }

    public function tools_migration() {
        new Clickwhale_Tools_Migration();
    }

}