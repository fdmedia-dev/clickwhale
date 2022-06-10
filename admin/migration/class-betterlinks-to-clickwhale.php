<?php

class BetterLinks_To_Clickwhale extends ClickWhale_Migration_Interface {
    
    public function process_links_data() {

        $message = [];

        global $wpdb;
        $table_betterlink_links        = $wpdb->prefix . 'betterlinks';
        $table_betterlink_categories   = $wpdb->prefix . 'betterlinks_terms';
        $table_betterlink_relatioship  = $wpdb->prefix . 'betterlinks_terms_relationships';
        $table_clickwhale_categories   = $wpdb->prefix . 'clickwhale_categories';
        
        $data  = $wpdb->get_results( "SELECT * FROM $table_betterlink_links");

        foreach ($data as $item){

            if(count($this->if_link_exists($item->link_slug)) === 0){

                $category_id = $wpdb->get_var("SELECT $table_clickwhale_categories.id 
                    FROM $table_clickwhale_categories, $table_betterlink_categories, $table_betterlink_relatioship 
                    WHERE $table_betterlink_categories.ID=$table_betterlink_relatioship.term_id 
                    AND $table_betterlink_relatioship.link_id=$item->ID 
                    AND $table_betterlink_categories.term_slug=$table_clickwhale_categories.slug");

                $array = array(
                    'title'       => $item->link_title,
                    'url'         => $item->target_url,
                    'slug'        => $item->link_slug,
                    'redirection' => $item->redirect_type,
                    'nofollow'    => isset($item->nofollow) && $item->nofollow == 1 ? $item->nofollow : '',
                    'sponsored'   => isset($item->sponsored) && $item->sponsored == 1 ? $item->sponsored : '',
                    'categories'  => $category_id,
                    'created_at'  => $item->link_date,
                    'updated_at'  => $item->link_modified,
                );
            
                $this->run_links_migration($array);

                $message[] = 'Link Imported Successfully "' . $item->link_title . '"';
            } else {
                $message[] = 'Link import failed "' . $item->link_title . '" already exists';
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
        $table_betterlink_categories   = $wpdb->prefix . 'betterlinks_terms';

        $data_clickwhale  = $wpdb->get_results( "SELECT * FROM $table_clickwhale_categories");
        $data_betterlink  = $wpdb->get_results( "SELECT * FROM $table_betterlink_categories");

        foreach ($data_betterlink as $item){
            
            if(count($this->if_category_exists($item->term_slug)) === 0){
            
                $array = array(
                    'title' => $item->term_name,
                    'slug'  => $item->term_slug,
                );
                
                $this->run_categories_migration($array);
    
                $message[] = 'Category "' . $item->term_name . '" Imported Successfully';
            } else {
                $message[] = 'Import failed! Category "' . $item->term_name . '" already exists';
            }
        }

        return [
            'categories' => $message,
        ];

    }
}