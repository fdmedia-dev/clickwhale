<?php

class Clickwhale_Link_Edit {
	function __construct() {

	}

	/**
	 * Default values for new link
	 * Could be hooked by filter "clickwhale_link_defaults"
	 * @return array
	 */
	public function get_defaults() {
		$global_options = get_option( 'clickwhale_general_options' );

		return array(
			'id'          => 0,
			'created_at'  => '',
			'updated_at'  => '',
			'title'       => '',
			'url'         => '',
			'slug'        => '',
			'redirection' => $global_options['redirect_type'],
			'nofollow'    => '',
			'sponsored'   => '',
			'description' => '',
			'categories'  => '',
		);
	}

	public function get_item( $request ) {
		global $wpdb;

		$notice   = '';
		$defaults = apply_filters( 'clickwhale_link_defaults', $this->get_defaults() );

		if ( isset( $request['id'] ) ) {
			$item = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}clickwhale_links WHERE id = %d", intval( $request['id'] ) ), ARRAY_A );
			if ( ! $item ) {
				$item   = $defaults;
				$notice = __( 'Item not found', 'clickwhale' );
			}
		} else {
			$item = $defaults;
		}

		return $item;
	}

	public function clickwhale_validate_link( $item ) {
		$messages = array();

		if ( empty( $item['title'] ) ) {
			$messages[] = __( 'Title is required', 'clickwhale' );
		}
		if ( empty( $item['url'] ) ) {
			$messages[] = __( 'Target URL is required', 'clickwhale' );
		}
		if ( empty( $item['slug'] ) ) {
			$messages[] = __( 'Slug is required', 'clickwhale' );
		}
		if ( ! ctype_digit( $item['redirection'] ) ) {
			$messages[] = __( 'Wrong redirection code', 'clickwhale' );
		}
		if ( ! empty( $item['redirection'] ) && ! absint( intval( $item['redirection'] ) ) ) {
			$messages[] = __( 'Redirection code can not be less than zero' );
		}
		if ( ! empty( $item['redirection'] ) && ! preg_match( '/[0-9]+/', $item['redirection'] ) ) {
			$messages[] = __( 'Redirection code must be number' );
		}
		if ( empty( $item['slug'] ) ) {
			$messages[] = __( 'Slug is required', 'clickwhale' );
		}
		//if (!empty($item['email']) && !is_email($item['email'])) $messages[] = __('E-Mail is in wrong format', 'clickwhale');

		if ( empty( $messages ) ) {
			return true;
		}

		return implode( '<br />', $messages );
	}

	public function clear_link_slug( $item ) {

		$slug = $item['slug'];
		$slug = strtolower( $slug );                  // to lowercase
		$slug = str_replace( ' ', '-', $slug );       // space
		$slug = str_replace( '\\\\\\', '/', $slug );  // triple backslash
		$slug = str_replace( '\\\\', '/', $slug );    // double backslash
		$slug = str_replace( '\\', '/', $slug );      // single backslash
		$slug = str_replace( '///', '/', $slug );     // triple slash
		$slug = str_replace( '//', '/', $slug );      // double slash
		$slug = untrailingslashit( $slug );           // https://developer.wordpress.org/reference/functions/untrailingslashit/

		if ( $slug[0] === '/' ) {
			$slug = ltrim( $slug, $slug[0] );
		}

		$item['slug'] = $slug;

		return $item;
	}

	public function get_link_categories() {
		global $wpdb;

		$results = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}clickwhale_categories" );
		if ( ! empty( $results ) ) {
			return $results;
		}
	}

	public function link_categories_to_string( array $categories ) {
		return implode( ',', $categories );
	}

	function save_update_link() {
		global $wpdb;
		$links_table        = $wpdb->prefix . 'clickwhale_links';
		$item               = array_intersect_key( $_POST, $this->get_defaults() );
		$item               = $this->clear_link_slug( $item );
		$item['categories'] = isset( $item['categories'] ) ? $this->link_categories_to_string( $item['categories'] ) : '';

		$result = $wpdb->update(
			$links_table,
			$item,
			array( 'id' => $item['id'] )
		);

		if ( false === $result || $result < 1 ) {
			$wpdb->insert(
				$links_table,
				$item,
			);
			$item['id'] = $wpdb->insert_id;
			do_action( 'clickwhale_insert_link_meta', $item['id'], $_POST );
			set_transient( 'link-' . $item['id'], 'link_added', 45 );
		} else {
			do_action( 'clickwhale_update_link_meta', $item['id'], $_POST );
			set_transient( 'link-' . $item['id'], 'link_updated', 45 );
		}


		$url = 'admin.php?page=clickwhale-edit-link&id=' . $item['id'];
		wp_redirect( admin_url( $url ) );
		die;
	}
}