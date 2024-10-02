<?php
namespace clickwhale\includes\admin\migration;

//use clickwhale\includes\admin\migration\Clickwhale_Migration_Abstract;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class BetterLinks_To_Clickwhale extends Clickwhale_Migration_Abstract {

	public function process_links_data() {
		global $wpdb;

		$message = [];
		$data    = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}betterlinks" );

		foreach ( $data as $item ) {

			if ( count( $this->if_link_exists( $item->link_slug ) ) === 0 ) {

				$category_id = $wpdb->get_var( $wpdb->prepare( "SELECT {$wpdb->prefix}clickwhale_categories.id 
                    FROM {$wpdb->prefix}clickwhale_categories, {$wpdb->prefix}betterlinks_terms, {$wpdb->prefix}betterlinks_terms_relationships 
                    WHERE {$wpdb->prefix}betterlinks_terms.ID={$wpdb->prefix}betterlinks_terms_relationships.term_id 
                    AND {$wpdb->prefix}betterlinks_terms_relationships.link_id=%d 
                    AND {$wpdb->prefix}betterlinks_terms.term_slug={$wpdb->prefix}clickwhale_categories.slug",
					$item->ID ) );

				$link_data = $this->link_url_parse( $item->target_url );
				$array     = array(
					'title'       => $item->link_title,
					'url'         => $link_data['url'],
					'slug'        => $item->link_slug,
					'redirection' => $item->redirect_type,
					'description' => isset( $item->link_note ) ? $item->link_note : '',
					'nofollow'    => isset( $item->nofollow ) && $item->nofollow == 1 ? $item->nofollow : '',
					'sponsored'   => isset( $item->sponsored ) && $item->sponsored == 1 ? $item->sponsored : '',
					'categories'  => ! is_null( $category_id ) ? $category_id : '',
					'author'      => $this->process_author(),
					'created_at'  => $item->link_date,
					'updated_at'  => $item->link_modified,
				);

				$insert_id = $this->run_links_migration( $array );
				if ( isset( $link_data['utms'] ) ) {
					do_action( 'clickwhale_link_updated', $insert_id, $link_data['utms'] );
				}

				$message[] = $this->link_item_import_success( $item->link_title );
			} else {
				$message[] = $this->link_item_import_error( $item->link_title );
			}
		}

		return [
			'links' => $message,
		];

	}

	public function process_categories_data() {
		global $wpdb;

		$message         = [];
		$data_betterlink = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}betterlinks_terms" );

		foreach ( $data_betterlink as $item ) {

			if ( count( $this->if_category_exists( $item->term_slug ) ) === 0 ) {

				$array = array(
					'title' => $item->term_name,
					'slug'  => $item->term_slug,
				);

				$this->run_categories_migration( $array );

				$message[] = $this->category_item_import_success( $item->term_name );
			} else {
				$message[] = $this->category_item_import_error( $item->term_name );
			}
		}

		return [
			'categories' => $message,
		];

	}

	public function process_migration_time() {
		$migration_options = get_option( 'clickwhale_tools_migration_options' );

		if ( isset( $migration_options['betterlinks_categories'] ) || isset( $migration_options['betterlinks_links'] ) ) {
			$this->set_migration_time( 'betterlinks_last_migration', wp_date( 'Y-m-d H:i:s' ) );
		}
	}
}