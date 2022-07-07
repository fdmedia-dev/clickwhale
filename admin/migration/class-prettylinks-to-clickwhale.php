<?php

class PrettyLinks_To_Clickwhale extends ClickWhale_Migration_Interface {

	public function process_links_data() {
		global $wpdb;

		$message = [];
		$data    = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}prli_links" );

		// in future for pro version
		//$data = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}prli_links WHERE post_type='pretty-link' AND post_status='publish'");


		foreach ( $data as $item ) {

			if ( count( $this->if_link_exists( $item->slug ) ) === 0 ) {

				$array = array(
					'title'       => $item->name,
					'url'         => $item->url,
					'slug'        => $item->slug,
					'redirection' => $item->redirect_type,
					'description' => isset( $item->description ) ? $item->description : '',
					'nofollow'    => $item->nofollow,
					'sponsored'   => $item->sponsored,
					'categories'  => '',
					'created_at'  => $item->created_at,
					'updated_at'  => $item->updated_at,
				);

				$this->run_links_migration( $array );

				$message[] = $this->link_item_import_success( $item->name );
			} else {
				$message[] = $this->link_item_import_error( $item->name );
			}
		}

		return [
			'links' => $message,
		];

	}

	public function process_migration_time() {
		$migration_options = get_option( 'clickwhale_tools_migration_options' );

		if ( isset( $migration_options['prettylinks_categories'] ) || isset( $migration_options['prettylinks_links'] ) ) {
			$this->set_migration_time( 'prettylinks_last_migration', wp_date( 'Y-m-d H:i:s' ) );
		}
	}


}