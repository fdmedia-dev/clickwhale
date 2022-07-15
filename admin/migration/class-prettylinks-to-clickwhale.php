<?php

class PrettyLinks_To_Clickwhale extends ClickWhale_Migration_Interface {

	public function process_links_data() {
		global $wpdb;

		$message = [];
		$data    = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}prli_links" );

		foreach ( $data as $item ) {

			if ( count( $this->if_link_exists( $item->slug ) ) === 0 ) {

				$category_id = $this->get_categories_string( $this->get_custom_post_type_categories( $item->link_cpt_id ) );
				$link_data   = $this->link_url_parse( $item->url );
				$array       = array(
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
		return [
			'categories' => $this->prepare_categories_data( $this->get_custom_term_taxonomy_ids( 'pretty-link-category' ) ),
		];
	}

	public function process_migration_time() {
		$migration_options = get_option( 'clickwhale_tools_migration_options' );

		if ( isset( $migration_options['prettylinks_categories'] ) || isset( $migration_options['prettylinks_links'] ) ) {
			$this->set_migration_time( 'prettylinks_last_migration', wp_date( 'Y-m-d H:i:s' ) );
		}
	}


}