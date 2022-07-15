<?php

class Clickwhale_Migration_Interface {

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

	public function process_migration_time() {
		return false;
	}

	public function set_migration_time( $option, $time ) {
		$options            = get_option( 'clickwhale_tools_last_migration_options' );
		$options[ $option ] = $time;
		update_option( 'clickwhale_tools_last_migration_options', $options );
	}

	public function link_url_parse( $url ) {
		$result        = [];
		$result['url'] = $url;
		$utms_params   = [ 'utm_campaign', 'utm_medium', 'utm_source', 'utm_term', 'utm_content' ];

		$url_array = parse_url( $url );
		parse_str( $url_array['query'], $params );

		if ( $params ) {
			$result['url'] = str_replace( '?' . $url_array['query'], '', $url );

			foreach ( $utms_params as $utm ) {
				if ( isset( $params[ $utm ] ) ) {
					$utms[ $utm ] = $params[ $utm ] ? $params[ $utm ] : '';
					unset( $params[ $utm ] );
				}
			}
			$result['params'] = $params;
			$result['utms']   = $utms;
		}

		if ( $result['params'] ) {
			$result['url'] = $result['url'] . '?' . http_build_query( $result['params'] );
		}

		return $result;
	}

	public function if_link_exists( $slug ) {
		global $wpdb;

		return $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}clickwhale_links WHERE slug='{$slug}'" );
	}

	public function if_category_exists( $slug ) {
		global $wpdb;

		return $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}clickwhale_categories WHERE slug='{$slug}'" );
	}

	public function link_item_import_success( $item ) {
		return sprintf( __( 'Link "%1$s" imported successfully.', 'clickwhale' ), $item );
	}

	public function link_item_import_error( $item ) {
		return sprintf( __( '<strong>Import failed!</strong> Link %1$s already exists', 'clickwhale' ), $item );
	}

	public function category_item_import_success( $item ) {
		return sprintf( __( 'Category "%1$s" imported successfully.', 'clickwhale' ), $item );
	}

	public function category_item_import_error( $item ) {
		return sprintf( __( '<strong>Import failed!</strong> Category "%1$s" already exists', 'clickwhale' ), $item );
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