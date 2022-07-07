<?php

class ThirstyAffiliates_To_Clickwhale extends ClickWhale_Migration_Interface {

	public function process_links_data() {
		global $wpdb;

		$message = [];
		$data    = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}posts WHERE post_type='thirstylink' AND post_status='publish'" );

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

			if ( count( $this->if_link_exists( $item->post_name ) ) === 0 ) {

				$redirection = get_post_meta( $item->ID, '_ta_redirect_type', true ) !== 'global'
					? get_post_meta( $item->ID, '_ta_redirect_type', true )
					: $global_redirect_type;

				if ( get_post_meta( $item->ID, '_ta_no_follow', true ) === 'global' ) {
					if ( $global_nofollow === 'category' ) {
						$categories_for_nofollow = $wpdb->get_results( "SELECT term_taxonomy_id FROM {$wpdb->prefix}term_relationships WHERE object_id=$item->ID", ARRAY_A );
						$category_nofollow       = false;

						foreach ( $categories_for_nofollow as $k => $v ) {
							if ( array_intersect( $global_nofollow_categories, $v ) ) {
								$category_nofollow = true;
								break;
							}
						}
						$nofollow = $category_nofollow;
					}
				} else {
					$nofollow = get_post_meta( $item->ID, '_ta_no_follow', true ) === 'yes' ? true : false;
				}

				$categories_for_id = $wpdb->get_results( $wpdb->prepare( "SELECT {$wpdb->prefix}clickwhale_categories.id 
                    FROM {$wpdb->prefix}clickwhale_categories, {$wpdb->prefix}terms, {$wpdb->prefix}term_relationships 
                    WHERE {$wpdb->prefix}terms.term_id={$wpdb->prefix}term_relationships.term_taxonomy_id 
                    AND {$wpdb->prefix}term_relationships.object_id=%d 
                    AND {$wpdb->prefix}terms.slug={$wpdb->prefix}clickwhale_categories.slug", $item->ID ), ARRAY_A );

				$category_id = [];
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
					'categories'  => ! is_null( $category_id ) ? $category_id : '',
					'created_at'  => $item->post_date,
					'updated_at'  => $item->post_modified,
				);

				$this->run_links_migration( $array );

				$message[] = $this->link_item_import_success( $item->post_title );
			} else {
				$message[] = $this->link_item_import_error( $item->post_title );
			}
		}

		return [
			'links' => $message,
		];

	}

	public function process_categories_data() {

		$message = [];

		global $wpdb;

		$data_thirstyaffiliates = $wpdb->get_results( "SELECT {$wpdb->prefix}terms.term_id, {$wpdb->prefix}terms.name, {$wpdb->prefix}terms.slug
            FROM {$wpdb->prefix}term_taxonomy, {$wpdb->prefix}terms
            WHERE {$wpdb->prefix}terms.term_id={$wpdb->prefix}term_taxonomy.term_taxonomy_id  
            AND {$wpdb->prefix}term_taxonomy.taxonomy='thirstylink-category'" );

		foreach ( $data_thirstyaffiliates as $item ) {

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

		if ( isset( $migration_options['thirstyaffiliates_categories'] ) || isset( $migration_options['thirstyaffiliates_links'] ) ) {
			$this->set_migration_time( 'thirstyaffiliates_last_migration', wp_date( 'Y-m-d H:i:s' ) );
		}
	}
}