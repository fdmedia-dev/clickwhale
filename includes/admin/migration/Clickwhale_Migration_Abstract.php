<?php
namespace clickwhale\includes\admin\migration;

use clickwhale\includes\helpers\Helper;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

abstract class Clickwhale_Migration_Abstract {

    public function run_migration( $categories, $links ): array {
        $results = array();

        if ( $categories ) {
            $results[] = $this->process_categories_data();
        }
        if ( $links ) {
            $results[] = $this->process_links_data();
        }

        $this->process_migration_time();

        return $results;
    }

    public function process_links_data(): array {
        return array(
            'links' => array()
        );
    }

    public function process_categories_data(): array {
        return array(
            'categories' => array()
        );
    }

    public function process_author(): int {
        return get_current_user_id();
    }

    abstract public function process_migration_time();

    public function set_migration_time( $option, $time ) {
        $options            = get_option( 'clickwhale_tools_last_migration_options' );
        $options[$option] = $time;
        update_option( 'clickwhale_tools_last_migration_options', $options );
    }

    /**
     * @param string $slug
     * @return bool
     */
    public function if_link_exists( string $slug ): bool {
        $slug = Helper::sanitize_slug( $slug );

        if ( empty( $slug ) ) {
            return false;
        }

        global $wpdb;

        return (bool) $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}clickwhale_links WHERE slug=%s",
                $slug
            )
        );
    }

    public function link_url_parse( $url ): array {
        $result = array(
            'url' => $url,
            'utms' => array()
        );
        $utm_params = [ 'utm_campaign', 'utm_medium', 'utm_source', 'utm_term', 'utm_content' ];

        $url_array = parse_url( $url );
        if ( isset( $url_array['query'] ) && $url_array['query'] !== '' ) {
            parse_str( $url_array['query'], $params );
            $result['url'] = str_replace( '?' . $url_array['query'], '', $url );

            foreach ( $utm_params as $utm ) {
                if ( isset( $params[$utm] ) && $params[$utm] !== '' ) {
                    $result['utms'][$utm] = $params[$utm];
                    unset( $params[$utm] );
                }
            }
            $result['params'] = $params;

            if ( ! empty( $params ) ) {
                $result['url'] .= '?' . http_build_query( $params );
            }
        }

        return $result;
    }

    public function link_item_import_success( $item ): string {
        return sprintf( __( 'Link "%1$s" imported successfully.', 'clickwhale' ), $item );
    }

    public function link_item_import_error( $item ): string {
        return sprintf( __( '<strong>Import failed!</strong> Link %1$s already exists', 'clickwhale' ), $item );
    }

    /**
     * @param string $slug
     * @return bool
     */
    public function if_category_exists( string $slug ): bool {
        $slug = Helper::sanitize_slug( $slug );

        if ( empty( $slug ) ) {
            return false;
        }

        global $wpdb;

        return (bool) $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}clickwhale_categories WHERE slug=%s",
                $slug
            )
        );
    }

    public function get_custom_post_type_categories( int $id ): array {
        global $wpdb;

        return (array) $wpdb->get_results(
            $wpdb->prepare(
                "SELECT {$wpdb->prefix}clickwhale_categories.id
                FROM {$wpdb->prefix}clickwhale_categories, {$wpdb->prefix}terms, {$wpdb->prefix}term_relationships 
                WHERE {$wpdb->prefix}terms.term_id={$wpdb->prefix}term_relationships.term_taxonomy_id 
                AND {$wpdb->prefix}term_relationships.object_id=%d 
                AND {$wpdb->prefix}terms.slug={$wpdb->prefix}clickwhale_categories.slug",
                $id
            ),
            ARRAY_A
        );
    }

    public function get_custom_term_taxonomy_ids( string $taxonomy ): array {
        global $wpdb;

        return (array) $wpdb->get_results(
            $wpdb->prepare(
                "SELECT {$wpdb->prefix}terms.term_id, {$wpdb->prefix}terms.name, {$wpdb->prefix}terms.slug
                FROM {$wpdb->prefix}term_taxonomy, {$wpdb->prefix}terms
                WHERE {$wpdb->prefix}terms.term_id={$wpdb->prefix}term_taxonomy.term_taxonomy_id
                AND {$wpdb->prefix}term_taxonomy.taxonomy=%s",
                sanitize_text_field( $taxonomy )
            )
        );
    }

    public function get_categories_string( array $categories ): string {
        $category_id = array();

        foreach ( $categories as $id ) {
            $category_id[] = $id['id'];
        }

        return implode( ',', $category_id );
    }

    public function prepare_categories_data( array $data ): array {
        $message = array();
        foreach ( $data as $item ) {
            if ( ! $this->if_category_exists( $item->slug ) ) {
                $array = array(
                    'title' => $item->name,
                    'slug'  => $item->slug
                );

                $this->run_categories_migration( $array );

                $message[] = $this->category_item_import_success( $item->name );
            } else {
                $message[] = $this->category_item_import_error( $item->name );
            }
        }

        return $message;
    }

    public function category_item_import_success( $item ): string {
        return sprintf( __( 'Category "%1$s" imported successfully.', 'clickwhale' ), $item );
    }

    public function category_item_import_error( $item ): string {
        return sprintf( __( '<strong>Import failed!</strong> Category "%1$s" already exists', 'clickwhale' ), $item );
    }

    public function run_links_migration( $data ): int {
        global $wpdb;

        $table = $wpdb->prefix . 'clickwhale_links';
        $wpdb->insert( $table, $data );

        return $wpdb->insert_id;
    }

    public function run_categories_migration( $data ): int {
        global $wpdb;

        $table = $wpdb->prefix . 'clickwhale_categories';
        $wpdb->insert( $table, $data );

        return $wpdb->insert_id;
    }
}
