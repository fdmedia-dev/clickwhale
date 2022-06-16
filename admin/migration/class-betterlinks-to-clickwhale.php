<?php

class BetterLinks_To_Clickwhale extends ClickWhale_Migration_Interface {
    
    public function process_links_data() {
        global $wpdb;

        $message                       = [];
        $table_betterlinks_links        = $wpdb->prefix . 'betterlinks';
        $table_betterlinks_categories   = $wpdb->prefix . 'betterlinks_terms';
        $table_betterlinks_relatioship  = $wpdb->prefix . 'betterlinks_terms_relationships';
        $table_clickwhale_categories    = $wpdb->prefix . 'clickwhale_categories';
        
        $data = $wpdb->get_results( "SELECT * FROM $table_betterlinks_links");

        foreach ($data as $item){

            if(count($this->if_link_exists($item->link_slug)) === 0){

                $category_id = $wpdb->get_var("SELECT $table_clickwhale_categories.id 
                    FROM $table_clickwhale_categories, $table_betterlinks_categories, $table_betterlinks_relatioship 
                    WHERE $table_betterlinks_categories.ID=$table_betterlinks_relatioship.term_id 
                    AND $table_betterlinks_relatioship.link_id=$item->ID 
                    AND $table_betterlinks_categories.term_slug=$table_clickwhale_categories.slug");

                $array = array(
                    'title'       => $item->link_title,
                    'url'         => $item->target_url,
                    'slug'        => $item->link_slug,
                    'redirection' => $item->redirect_type,
                    'nofollow'    => isset($item->nofollow) && $item->nofollow == 1 ? $item->nofollow : '',
                    'sponsored'   => isset($item->sponsored) && $item->sponsored == 1 ? $item->sponsored : '',
                    'categories'  => !is_null($category_id) ? $category_id : '',
                    'created_at'  => $item->link_date,
                    'updated_at'  => $item->link_modified,
                );
            
                $this->run_links_migration($array);

                $message[] = $this->link_item_import_success( $item->link_title );
            } else {
                $message[] = $this->link_item_import_error( $item->link_title );
            }
        }

        return [
            'links' => $message,
        ];

    }

    public function process_categories_data() {
        
        $message = [];

        global $wpdb;
        $table_clickwhale_categories   = $wpdb->prefix . 'clickwhale_categories';
        $table_betterlinks_categories   = $wpdb->prefix . 'betterlinks_terms';

        $data_betterlink  = $wpdb->get_results( "SELECT * FROM $table_betterlinks_categories");

        foreach ($data_betterlink as $item){
            
            if(count($this->if_category_exists($item->term_slug)) === 0){
            
                $array = array(
                    'title' => $item->term_name,
                    'slug'  => $item->term_slug,
                );
                
                $this->run_categories_migration($array);
    
                $message[] = $this->category_item_import_success( $item->term_name );
            } else {
                $message[] = $this->category_item_import_error( $item->term_name );
            }
        }

        return [
            'categories' => $message,
        ];

    }
}