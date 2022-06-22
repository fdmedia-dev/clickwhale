<?php

class Clickwhale_Link_Edit {
	function __construct() {

	}

	public function get_base_defaults() {
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

	public function get_pro_defaults() {
		return array(
			'utm_campaign' => '',
			'utm_medium'   => '',
			'utm_source'   => '',
			'utm_term'     => '',
			'utm_content'  => '',
		);
	}

	public function get_defaults() {
		if ( class_exists( 'Clickwhale_Pro' ) ) {
			return array_merge( $this->get_base_defaults(), $this->get_pro_defaults() );
		} else {
			return $this->get_base_defaults();
		}
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
		$categories_table = $wpdb->prefix . 'clickwhale_categories';
		$results          = $wpdb->get_results( "SELECT * FROM $categories_table" );
		if ( ! empty( $results ) ) {
			return $results;
		}
	}

	public function clickwhale_link_edit_fields() {

	}

}