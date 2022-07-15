<?php

class PrettyLinks_To_Clickwhale extends ClickWhale_Migration_Interface {

	public function process_links_data() {
		global $wpdb;

		$message = [];
		$data    = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}prli_links" );

		foreach ( $data as $item ) {

			$categories_for_id = $wpdb->get_results( $wpdb->prepare( "SELECT {$wpdb->prefix}clickwhale_categories.id 
                    FROM {$wpdb->prefix}clickwhale_categories, {$wpdb->prefix}terms, {$wpdb->prefix}term_relationships 
                    WHERE {$wpdb->prefix}terms.term_id={$wpdb->prefix}term_relationships.term_taxonomy_id 
                    AND {$wpdb->prefix}term_relationships.object_id=%d 
                    AND {$wpdb->prefix}terms.slug={$wpdb->prefix}clickwhale_categories.slug", $item->link_cpt_id ), ARRAY_A );

			$category_id = [];
			foreach ( $categories_for_id as $id ) {
				$category_id[] = $id['id'];
			}
			$category_id = implode( ',', $category_id );


			if ( count( $this->if_link_exists( $item->slug ) ) === 0 ) {

				$link_data = $this->link_url_parse( $item->url );

				$array = array(
					'title'       => $item->name,
					'url'         => $link_data['url'],
					'slug'        => $item->slug,
					'redirection' => $item->redirect_type,
					'description' => isset( $item->description ) ? $item->description : '',
					'nofollow'    => $item->nofollow,
					'sponsored'   => $item->sponsored,
					'categories'  => ! is_null( $category_id ) ? $category_id : '',
					'created_at'  => $item->created_at,
					'updated_at'  => $item->updated_at,
				);

				$insert_id = $this->run_links_migration( $array );
				do_action( 'clickwhale_update_link_meta', $insert_id, $link_data['utms'] );

				$message[] = $this->link_item_import_success( $item->name );
			} else {
				$message[] = $this->link_item_import_error( $item->name );
			}
		}

		return [
			'links' => $message,
		];

	}

	public function process_categories_data() {

		$message = [];

		global $wpdb;

		$data_prettylinks = $wpdb->get_results( "SELECT {$wpdb->prefix}terms.term_id, {$wpdb->prefix}terms.name, {$wpdb->prefix}terms.slug
            FROM {$wpdb->prefix}term_taxonomy, {$wpdb->prefix}terms
            WHERE {$wpdb->prefix}terms.term_id={$wpdb->prefix}term_taxonomy.term_taxonomy_id  
            AND {$wpdb->prefix}term_taxonomy.taxonomy='pretty-link-category'" );

		foreach ( $data_prettylinks as $item ) {

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

		return [
			'categories' => $message,
		];

	}

	public function process_migration_time() {
		$migration_options = get_option( 'clickwhale_tools_migration_options' );

		if ( isset( $migration_options['prettylinks_categories'] ) || isset( $migration_options['prettylinks_links'] ) ) {
			$this->set_migration_time( 'prettylinks_last_migration', wp_date( 'Y-m-d H:i:s' ) );
		}
	}


}