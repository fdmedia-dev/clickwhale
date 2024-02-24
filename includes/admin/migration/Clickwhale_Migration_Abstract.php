<?php
namespace clickwhale\includes\admin\migration;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

abstract class Clickwhale_Migration_Abstract {

	public function run_migration( $categories, $links ) {
		$resutls = [];

		if ( $categories ) {
			$resutls[] = $this->process_categories_data();
		}
		if ( $links ) {
			$resutls[] = $this->process_links_data();
		}

		$this->process_migration_time();

		return $resutls;
	}

	public function process_links_data() {
		return [
			'links' => [],
		];
	}

	public function process_categories_data() {
		return [
			'categories' => [],
		];
	}

	public function process_author() {
		return get_current_user_id();
	}

	public function process_migration_time() {
		return false;
	}

	public function set_migration_time( $option, $time ) {
		$options            = get_option( 'clickwhale_tools_last_migration_options' );
		$options[ $option ] = $time;
		update_option( 'clickwhale_tools_last_migration_options', $options );
	}


	public function if_link_exists( $slug ) {
		global $wpdb;

		return $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}clickwhale_links WHERE slug='{$slug}'" );
	}

	public function link_url_parse( $url ) {
		$result        = [];
		$result['url'] = $url;
		$utms_params   = [ 'utm_campaign', 'utm_medium', 'utm_source', 'utm_term', 'utm_content' ];

		$url_array = parse_url( $url );
		if ( isset( $url_array['query'] ) && $url_array['query'] !== '' ) {
			parse_str( $url_array['query'], $params );

			if ( $params ) {
				$result['url'] = str_replace( '?' . $url_array['query'], '', $url );

				foreach ( $utms_params as $utm ) {
					if ( isset( $params[ $utm ] ) && $params[ $utm ] !== '' ) {
						$utms[ $utm ] = $params[ $utm ];
						unset( $params[ $utm ] );
					}
				}
				$result['params'] = $params;
				$result['utms']   = $utms;
			}

			if ( $result['params'] ) {
				$result['url'] = $result['url'] . '?' . http_build_query( $result['params'] );
			}
		}

		return $result;
	}

	public function link_item_import_success( $item ) {
		return sprintf( __( 'Link "%1$s" imported successfully.', CLICKWHALE_NAME ), $item );
	}

	public function link_item_import_error( $item ) {
		return sprintf( __( '<strong>Import failed!</strong> Link %1$s already exists', CLICKWHALE_NAME ), $item );
	}


	public function if_category_exists( $slug ) {
		global $wpdb;

		return $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}clickwhale_categories WHERE slug='{$slug}'" );
	}

	public function get_custom_post_type_categories( $id ) {
		global $wpdb;

		return $wpdb->get_results( $wpdb->prepare( "SELECT {$wpdb->prefix}clickwhale_categories.id 
                    FROM {$wpdb->prefix}clickwhale_categories, {$wpdb->prefix}terms, {$wpdb->prefix}term_relationships 
                    WHERE {$wpdb->prefix}terms.term_id={$wpdb->prefix}term_relationships.term_taxonomy_id 
                    AND {$wpdb->prefix}term_relationships.object_id=%d 
                    AND {$wpdb->prefix}terms.slug={$wpdb->prefix}clickwhale_categories.slug", $id ), ARRAY_A );

	}

	public function get_custom_term_taxonomy_ids( $taxonomy ) {
		global $wpdb;

		return $wpdb->get_results( $wpdb->prepare( "SELECT {$wpdb->prefix}terms.term_id, {$wpdb->prefix}terms.name, {$wpdb->prefix}terms.slug
            FROM {$wpdb->prefix}term_taxonomy, {$wpdb->prefix}terms
            WHERE {$wpdb->prefix}terms.term_id={$wpdb->prefix}term_taxonomy.term_taxonomy_id  
            AND {$wpdb->prefix}term_taxonomy.taxonomy=%s", $taxonomy ) );
	}

	public function get_categories_string( $categories ) {
		$category_id = [];

		foreach ( $categories as $id ) {
			$category_id[] = $id['id'];
		}

		return implode( ',', $category_id );
	}

	public function prepare_categories_data( $data ) {
		$message = [];
		foreach ( $data as $item ) {
			if ( count( $this->if_category_exists( $item->slug ) ) === 0 ) {
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

		return $message;
	}

	public function category_item_import_success( $item ) {
		return sprintf( __( 'Category "%1$s" imported successfully.', CLICKWHALE_NAME ), $item );
	}

	public function category_item_import_error( $item ) {
		return sprintf( __( '<strong>Import failed!</strong> Category "%1$s" already exists', CLICKWHALE_NAME ), $item );
	}

	public function run_links_migration( $data ) {
		global $wpdb;

		$table = $wpdb->prefix . 'clickwhale_links';
		$wpdb->insert( $table, $data );

		return $wpdb->insert_id;
	}

	public function run_categories_migration( $data ) {
		global $wpdb;

		$table = $wpdb->prefix . 'clickwhale_categories';
		$wpdb->insert( $table, $data );

		return $wpdb->insert_id;
	}
}