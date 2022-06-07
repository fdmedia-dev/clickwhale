<?php 

class ClickWhale_Notice {

	public function __construct() {

        $this->load_dependencies();
        $this->betterlinks();
        $this->thirstyaffiliates();

	}

    private function load_dependencies() {

		require_once plugin_dir_path( dirname( __FILE__ ) ) .  'admin/abstracts/class-clickwhale-migration-notice.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) .  'admin/migration/class-betterlinks-to-clickwhale.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) .  'admin/migration/class-thirstyaffiliates-to-clickwhale.php';
    
    }


    public function betterlinks(){

        $migration = new BetterLinks_To_ClickWhale();
        $migration::init();

    }

    public function thirstyaffiliates(){

        $migration = new ThirstyAffiliates_To_ClickWhale();
        $migration::init();

    }


}