<?php

class ClickwhaleLinkpagesHelper {

	/**
	 * Count linkpages in DB
	 *
	 * @return string|null
	 */
	public static function get_linkpages_count() {
		global $wpdb;

		return intval( $wpdb->get_var( "SELECT count(*) FROM {$wpdb->prefix}clickwhale_linkpages" ) );
	}

	/**
	 * Filter function
	 * return number of available links on linkpage
	 * @return mixed|void
	 */
	public static function get_links_limit() {
		return apply_filters( 'clickwhale_linkpage_links_limit', 10 );
	}

	/**
	 * Filter function
	 * return number of available linkpages
	 * @return mixed|void
	 */
	public static function get_limit() {
		return apply_filters( 'clickwhale_linkpages_limit', 1 );
	}

	/**
	 * Return link pages limitation notice string
	 * @return string
	 * @since 1.4.0
	 */
	public static function get_limitation_notice(): string {
		return sprintf(
			__( 'Currently, a maximum of %d link page can be added.', CLICKWHALE_NAME ),
			self::get_limit(),
		);
	}

	/**
	 * Return link page links limitation notice string
	 * @return string
	 * @since 1.4.0
	 */
	public static function get_links_limitation_notice(): string {
		return sprintf(
			__( 'Currently, a maximum of %d links can be added.', CLICKWHALE_NAME ),
			self::get_links_limit(),
		);
	}

	/**
	 * Check if linkpage with slug exists
	 *
	 * @param string $slug
	 *
	 * @return string|null
	 * @since 1.2.0
	 */
	public static function is_linkpage( string $slug ) {
		global $wpdb;

		return $wpdb->get_var(
			$wpdb->prepare( "SELECT count(*) FROM {$wpdb->prefix}clickwhale_linkpages WHERE slug=%s", $slug )
		);

	}

	/**
	 * Return Linkpage ID by its slug
	 *
	 * @param string $slug
	 *
	 * @return string|null
	 * @since 1.2.0
	 */
	public static function get_linkpage_id_by_slug( string $slug ) {
		global $wpdb;

		if ( ! $slug ) {
			return 0;
		}

		$result = $wpdb->get_var(
			$wpdb->prepare( "SELECT id FROM {$wpdb->prefix}clickwhale_linkpages WHERE slug=%s", $slug )
		);

		return $result ?? 0;
	}

	/**
	 * @return array
	 * @since 1.3.0
	 */
	public static function get_post_types(): array {
		$posts      = [];
		$args       = array(
			'public' => true,
		);
		$post_types = get_post_types( $args, 'objects' );
		unset( $post_types['attachment'] );

		foreach ( $post_types as $post_type ) {
			$posts[ $post_type->name ] = $post_type->labels->singular_name;
		}

		return $posts;
	}

	/**
	 * @since 1.3.0
	 */
	public static function get_linkpage_link_clicks( string $linkpage_id, string $link_id, bool $is_link = true ) {
		global $wpdb;


		if ( $is_link ) {
			$result = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$wpdb->prefix}clickwhale_track WHERE linkpage_id = %s AND link_id=%s AND event_type = 'click'",
					$linkpage_id, $link_id )
			);
		} else {
			$result = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$wpdb->prefix}clickwhale_track WHERE linkpage_id = %s AND custom_link_id=%s AND event_type = 'click'",
					$linkpage_id, $link_id )
			);
		}

		return $result ?? 0;
	}

}