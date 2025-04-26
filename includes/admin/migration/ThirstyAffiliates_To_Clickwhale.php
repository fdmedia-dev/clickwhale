<?php
namespace clickwhale\includes\admin\migration;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ThirstyAffiliates_To_Clickwhale extends Clickwhale_Migration_Abstract {

    public function process_links_data(): array {
        global $wpdb;
        $table_ta_posts         = $wpdb->prefix . 'posts';
        $table_ta_terms         = $wpdb->prefix . 'terms';
        $table_ta_relationships = $wpdb->prefix . 'term_relationships';
        $table_cw_categories    = $wpdb->prefix . 'clickwhale_categories';

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
            $item_id = $item->ID;
            $item_title = $item->post_title;
            $permalink = get_permalink( $item_id );

            if ( false === $permalink ) {
                $message[] = $this->link_item_import_error( $item_title );
                continue;
            }

            $slug = trim( substr( $permalink, strlen( home_url() ) ), '/' );

            if ( $this->link_exists( $slug ) ) {
                $message[] = $this->link_item_import_error( $item_title );
                continue;
            }

            $redirect_post_meta = get_post_meta( $item_id, '_ta_redirect_type', true );
            $redirection = ( $redirect_post_meta !== 'global' ) ? $redirect_post_meta : $global_redirect_type;

            $nofollow = true;
            $nofollow_post_meta = get_post_meta( $item_id, '_ta_no_follow', true );

            if ( $nofollow_post_meta === 'global' ) {
                if ( $global_nofollow === 'category' ) {
                    $categories_for_nofollow = $wpdb->get_results(
                        $wpdb->prepare(
                            "SELECT term_taxonomy_id FROM $table_ta_relationships WHERE object_id=%d",
                            intval( $item_id )
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
                $nofollow = ( 'yes' === $nofollow_post_meta ); // bool
            }

            $categories_for_id = (array) $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT $table_cw_categories.id
                    FROM $table_cw_categories, $table_ta_terms, $table_ta_relationships 
                    WHERE $table_ta_terms.term_id=$table_ta_relationships.term_taxonomy_id 
                    AND $table_ta_relationships.object_id=%d 
                    AND $table_ta_terms.slug=$table_cw_categories.slug",
                    intval( $item_id )
                ),
                ARRAY_A
            );

            $category_id = array();

            foreach ( $categories_for_id as $id ) {
                $category_id[] = $id['id'];
            }

            $link = array(
                'title'       => $item_title,
                'url'         => get_post_meta( $item_id, '_ta_destination_url', true ),
                'slug'        => $slug,
                'redirection' => $redirection,
                'nofollow'    => $nofollow,
                'sponsored'   => '',
                'categories'  => sanitize_text_field( implode( ',', $category_id ) ),
                'author'      => $this->process_author(),
                'created_at'  => $item->post_date,
                'updated_at'  => $item->post_modified,
            );

            $this->run_links_migration( $link );
            $message[] = $this->link_item_import_success( $item_title );
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
            if ( $this->category_exists( $item->slug ) ) {
                $message[] = $this->category_item_import_error( $item->name );
                continue;
            }

            $category = array(
                'title' => $item->name,
                'slug'  => $item->slug,
            );

            $this->run_categories_migration( $category );
            $message[] = $this->category_item_import_success( $item->name );
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
