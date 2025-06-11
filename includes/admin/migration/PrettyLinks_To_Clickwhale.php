<?php
namespace clickwhale\includes\admin\migration;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class PrettyLinks_To_Clickwhale extends Clickwhale_Migration_Abstract {

    public function process_links_data(): array {
        global $wpdb;
        $data = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}prli_links" );

        if ( ! $data ) {
            return array(
                'links' => array()
            );
        }

        $message = array();

        foreach ( $data as $item ) {
            if ( ! $this->link_exists( $item->slug ) ) {
                $cpt_cats = $this->get_custom_post_type_categories( $item->link_cpt_id );
                if ( ! $cpt_cats ) {
                    $cpt_cats = array();
                }

                $category_id = $this->get_categories_string( $cpt_cats );
                $link_data = $this->parse_link_url( $item->url );
                $array = array(
                    'title'       => $item->name,
                    'url'         => $link_data['url'],
                    'slug'        => $item->slug,
                    'redirection' => $item->redirect_type,
                    'description' => $item->description ?? '',
                    'nofollow'    => $item->nofollow,
                    'sponsored'   => $item->sponsored,
                    'categories'  => sanitize_text_field( $category_id ),
                    'author'      => $this->process_author(),
                    'created_at'  => $item->created_at,
                    'updated_at'  => $item->updated_at
                );

                $insert_id = $this->run_links_migration( $array );

                if ( ! empty( $link_data['utms'] ) ) {
                    do_action( 'clickwhale_link_updated', $insert_id, $link_data['utms'] );
                }

                $message[] = $this->link_item_import_success( $item->name );
            } else {
                $message[] = $this->link_item_import_error( $item->name );
            }
        }

        return array(
            'links' => $message
        );
    }

    public function process_categories_data(): array {
        $taxonomy_ids = $this->get_custom_term_taxonomy_ids( 'pretty-link-category' );

        return array(
            'categories' => $this->prepare_categories_data( $taxonomy_ids )
        );
    }

    public function process_migration_time() {
        $migration_options = get_option( 'clickwhale_tools_migration_options' );

        if ( isset( $migration_options['prettylinks_categories'] ) || isset( $migration_options['prettylinks_links'] ) ) {
            $this->set_migration_time( 'prettylinks_last_migration', wp_date( 'Y-m-d H:i:s' ) );
        }
    }
}
