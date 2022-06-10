<?php

class ClickWhale_Migration_Interface {

    public function run_migration() {
        $resutls = [];

        $resutls[] = $this->process_categories_data();
        $resutls[] = $this->process_links_data();

        return $resutls;
    }

    public function process_links_data() {

    }

    public function process_categories_data() {

    }

    public function if_link_exists($slug) {
        global $wpdb;
        $table = $wpdb->prefix . 'clickwhale_links';
        
        return $wpdb->get_results( "SELECT * FROM $table WHERE slug='$slug'");
    }

    public function if_category_exists($slug) {
        global $wpdb;
        $table = $wpdb->prefix . 'clickwhale_categories';
        
        return $wpdb->get_results( "SELECT * FROM $table WHERE slug='$slug'");
    }

    public function run_links_migration($data) {
        global $wpdb;

        $table  = $wpdb->prefix . 'clickwhale_links';
        $result = $wpdb->insert($table, $data);

        return $wpdb->insert_id;
    }

    public function run_categories_migration($data) {
        global $wpdb;

        $table  = $wpdb->prefix . 'clickwhale_categories';
        $result = $wpdb->insert($table, $data);

        return $wpdb->insert_id;
    }

    public function admin_scripts() {

    }

}