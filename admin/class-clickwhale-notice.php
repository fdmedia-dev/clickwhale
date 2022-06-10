<?php 

class ClickWhale_Notice {

	public function __construct() {

        $this->load_dependencies();
        $this->betterlinks();
        $this->thirstyaffiliates();

	}

    private function load_dependencies() {

		require_once plugin_dir_path( dirname( __FILE__ ) ) .  'admin/migration/class-clickwhale-migration-notice.php';
    
    }


    public function betterlinks(){

        $migration = new ClickWhale_Migration_Notice('betterlinks', 'BetterLinks', 'betterlinks/betterlinks.php');
        $migration->init();

    }

    public function thirstyaffiliates(){

        $migration = new ClickWhale_Migration_Notice('thirstyaffiliates', 'ThirstyAffiliates', 'thirstyaffiliates/thirstyaffiliates.php');
        $migration->init();

    }


}