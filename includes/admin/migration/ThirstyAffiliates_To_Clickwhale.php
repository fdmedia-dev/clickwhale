<?php
namespace clickwhale\includes\admin\migration;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ThirstyAffiliates_To_Clickwhale extends Clickwhale_Migration_Abstract {

    public function process_links_data(): array {
        global $wpdb;

        $table_ta_posts              = $wpdb->prefix . 'posts';
        //$table_ta_postmeta           = $wpdb->prefix . 'postmeta';
        $table_ta_terms              = $wpdb->prefix . 'terms';
        $table_ta_relationships      = $wpdb->prefix . 'term_relationships';
        $table_clickwhale_categories = $wpdb->prefix . 'clickwhale_categories';

        $data = $wpdb->get_results( "SELECT * FROM $table_ta_posts WHERE post_type='thirstylink' AND post_status='publish'" );
        if ( ! $data ) {
            return array(
                'links' => array()
            );
        }

        $message = array();
        $global_redirect_type       = get_option( 'ta_link_redirect_type' );
        $global_nofollow_categories = get_option( 'ta_no_follow_category' );

        switch ( get_option( 'ta_no_follow' ) ) {
            case 'yes':
                $global_nofollow = true;
                break;
            case 'no':
                $global_nofollow = false;
                break;
            case 'category':
                $global_nofollow = 'category';
                break;
            default:
                $global_nofollow = false;
        }

        foreach ( $data as $item ) {
            if ( ! $this->if_link_exists( $item->post_name ) ) {
                $nofollow = true;
                $redirection = get_post_meta( $item->ID, '_ta_redirect_type', true ) !== 'global'
                    ? get_post_meta( $item->ID, '_ta_redirect_type', true )
                    : $global_redirect_type;

                if ( get_post_meta( $item->ID, '_ta_no_follow', true ) === 'global' ) {
                    if ( $global_nofollow === 'category' ) {
                        $categories_for_nofollow = $wpdb->get_results(
                            $wpdb->prepare(
                                "SELECT term_taxonomy_id FROM $table_ta_relationships WHERE object_id=%d",
                                intval( $item->ID )
                            ),
                            ARRAY_A
                        );
                        if ( ! $categories_for_nofollow ) {
                            $categories_for_nofollow = array();
                        }

                        $category_nofollow = false;
                        foreach ( $categories_for_nofollow as $v ) {
                            if ( array_intersect( $global_nofollow_categories, $v ) ) {
                                $category_nofollow = true;
                                break;
                            }
                        }
                        $nofollow = $category_nofollow;
                    }
                } else {
                    $nofollow = ( 'yes' === get_post_meta( $item->ID, '_ta_no_follow', true ) ); // bool
                }

                $categories_for_id = $wpdb->get_results(
                    $wpdb->prepare(
                        "SELECT $table_clickwhale_categories.id
                        FROM $table_clickwhale_categories, $table_ta_terms, $table_ta_relationships 
                        WHERE $table_ta_terms.term_id=$table_ta_relationships.term_taxonomy_id 
                        AND $table_ta_relationships.object_id=%d 
                        AND $table_ta_terms.slug=$table_clickwhale_categories.slug",
                        intval( $item->ID )
                    ),
                    ARRAY_A
                );
                if ( ! $categories_for_id ) {
                    $categories_for_id = array();
                }

                $category_id = array();
                foreach ( $categories_for_id as $id ) {
                    $category_id[] = $id['id'];
                }
                $category_id = implode( ',', $category_id );

                $array = array(
                    'title'       => $item->post_title,
                    'url'         => get_post_meta( $item->ID, '_ta_destination_url', true ),
                    'slug'        => $item->post_name,
                    'redirection' => $redirection,
                    'nofollow'    => $nofollow,
                    'sponsored'   => '',
                    'categories'  => sanitize_text_field( $category_id ),
                    'author'      => $this->process_author(),
                    'created_at'  => $item->post_date,
                    'updated_at'  => $item->post_modified,
                );

                $this->run_links_migration( $array );

                $message[] = $this->link_item_import_success( $item->post_title );
            } else {
                $message[] = $this->link_item_import_error( $item->post_title );
            }
        }

        return array(
            'links' => $message
        );
    }

    public function process_categories_data(): array {
        global $wpdb;
        $table_ta_term_taxnomy = $wpdb->prefix . 'term_taxonomy';
        $table_ta_terms        = $wpdb->prefix . 'terms';

        $data = $wpdb->get_results( "SELECT $table_ta_terms.term_id, $table_ta_terms.name, $table_ta_terms.slug
            FROM $table_ta_term_taxnomy, $table_ta_terms
            WHERE $table_ta_terms.term_id=$table_ta_term_taxnomy.term_taxonomy_id  
            AND $table_ta_term_taxnomy.taxonomy='thirstylink-category'" );

        if ( ! $data ) {
            return array(
                'categories' => array()
            );
        }

        $message = array();
        foreach ( $data as $item ) {
            if ( ! $this->if_category_exists( $item->slug ) ) {
                $array = array(
                    'title' => $item->name,
                    'slug'  => $item->slug,
                );

                $this->run_categories_migration( $array );

                $message[] = $this->category_item_import_success( $item->name );
            } else {
                $message[] = $this->category_item_import_error( $item->name );
            }
        }

        return array(
            'categories' => $message
        );
    }

    public function process_migration_time() {
        $migration_options = get_option( 'clickwhale_tools_migration_options' );

        if ( isset( $migration_options['thirstyaffiliates_categories'] ) || isset( $migration_options['thirstyaffiliates_links'] ) ) {
            $this->set_migration_time( 'thirstyaffiliates_last_migration', wp_date( 'Y-m-d H:i:s' ) );
        }
    }
}
