<?php

namespace Clickwhale\Admin\Linkpages;

use Exception;
use WP_List_Table;
use Clickwhale\Helpers\{Helper, Linkpages_Helper};

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Clickwhale_Linkpages_List_Table extends WP_List_Table {

	public function __construct() {
		parent::__construct(
			array(
				'singular' => 'linkpage',
				'plural'   => 'linkpages',
			)
		);
	}

	/**
	 * @param $item - row (key, value array)
	 * @param $column_name - string (key)
	 *
	 * @return string
	 */
	public function column_default( $item, $column_name ): string {
		return esc_html( $item[ $column_name ] );
	}

	/**
	 * @param $item - row (key, value array)
	 *
	 * @return string
	 */
	public function column_title( $item ): string {
		$id      = intval( $item['id'] );
		$title   = sprintf(
			'<a href="?page=' . CLICKWHALE_SLUG . '-edit-linkpage&id=%d">%s</a>',
			$id,
			esc_html( wp_unslash( $item['title'] ) )
		);
		$actions = array(
			'edit'   => sprintf(
				'<a href="?page=' . CLICKWHALE_SLUG . '-edit-linkpage&id=%d">%s</a>',
				$id,
				__( 'Edit', 'clickwhale' )
			),
			'view'      => sprintf(
				'<a href="%s" target="_blank">%s</a>',
				esc_url( trailingslashit( home_url( $item['slug'] ) ) ),
				__( 'View', 'clickwhale' )
			),
			'duplicate' => sprintf(
				'<a href="%s">%s</a>',
				esc_url(
					wp_nonce_url(
						admin_url( 'admin.php?page=' . sanitize_key( (string) filter_input( INPUT_GET, 'page' ) ) . '&action=duplicate&id=' . $id ),
						'duplicate-' . $this->_args['singular']
					)
				),
				__( 'Duplicate', 'clickwhale' )
			),
			'delete'    => sprintf(
				'<a href="%s">%s</a>',
				esc_url(
					wp_nonce_url(
						admin_url( 'admin.php?page=' . sanitize_key( (string) filter_input( INPUT_GET, 'page' ) ) . '&action=delete&id=' . $id ),
						'delete-' . $this->_args['singular']
					)
				),
				__( 'Delete', 'clickwhale' )
			)
		);

		return sprintf( '%s %s',
			$title,
			$this->row_actions( $actions )
		);
	}

	/**
	 * Link url with copy button
	 *
	 * @param $item - row (key, value array)
	 *
	 * @return string
	 */
	public function column_slug( $item ): string {
		return sprintf(
			'<div class="slug-input--wrap"><input class="slug-input" type="text" value="%1$s" readonly /><a href="#" class="slug-input--btn" data-id="%2$d" title="%3$s"><span class="dashicons dashicons-clipboard"></span></a></div>',
			esc_attr( $item['slug'] ),
			intval( $item['id'] ),
			esc_attr__( 'Copy Link', 'clickwhale' )
		);
	}

	/**
	 * @param $item - row (key, value array)
	 *
	 * @return string
	 *
	 * @since 1.1.0
	 */
	public function column_views_count( $item ): string {
		return esc_html( $item['views_count'] );
	}

	/**
	 * @param $item - row (key, value array)
	 *
	 * @return string
	 *
	 * @since 1.1.0
	 */
	public function column_clicks_count( $item ): string {
		return esc_html( $item['clicks_count'] );
	}

	public function column_author( $item ): string {
		$user_info = get_userdata( $item['author'] );

		if ( ! $user_info ) {
			return '&mdash;';
		}

		return sprintf(
			'<a href="%s&author=%d">%s</a>',
			esc_url( get_admin_url( get_current_blog_id(), 'admin.php?page=' . CLICKWHALE_SLUG . '-linkpages' ) ),
			$user_info->ID,
			$user_info->display_name
		);
	}

	/**
	 * @param $item - row (key, value array)
	 *
	 * @return string
	 */
	public function column_links( $item ): string {
		$links = maybe_unserialize( $item['links'] );
		$count = $links && is_array( $links ) ? count( $links ) : 0;

		return $count . ' / ' . Linkpages_Helper::get_linkpage_links_limit();
	}

	/**
	 * @param $item - row (key, value array)
	 *
	 * @return string
	 */
	public function column_cb( $item ): string {
		return sprintf(
			'<input type="checkbox" name="id[]" value="%d" />',
			intval( $item['id'] )
		);
	}

	/**
	 * @return array
	 */
	public function get_columns(): array {
		$tracking_options = get_option( 'clickwhale_tracking_options' );
		$columns          = array(
			'cb'           => '<input type="checkbox" />',
			'title'        => __( 'Title', 'clickwhale' ),
			'slug'         => __( 'Link', 'clickwhale' ),
			'links'        => __( 'Links', 'clickwhale' ),
			'views_count'  => __( 'Views', 'clickwhale' ),
			'clicks_count' => __( 'Clicks', 'clickwhale' ),
			'author'       => __( 'Author', 'clickwhale' )
		);

		if ( ! empty( $tracking_options['disable_tracking'] ) ) {
			unset( $columns['views_count'], $columns['clicks_count'] );
		}

		return $columns;
	}

	/**
	 * @return array
	 */
	public function get_sortable_columns(): array {
		return array(
			'title'        => array( 'title', true ),
			'views_count'  => array( 'views_count', true ),
			'clicks_count' => array( 'clicks_count', true )
		);
	}

	/**
	 * @return array
	 */
	public function get_bulk_actions(): array {
		return array(
			'delete' => __( 'Delete', 'clickwhale' )
		);
	}

	/**
	 * This method processes bulk actions
	 * it can be outside of class
	 * it can not use wp_redirect coz there is output already
	 * in this example we are processing delete action
	 * message about successful deletion will be shown on page in next part
	 *
	 * @return void
	 * @throws Exception
	 */
	public function process_bulk_action() {
		global $wpdb;

		$action = $this->current_action();

		if ( ! in_array( $action, array( 'delete', 'duplicate' ), true ) ) {
			return;
		}

		$get_id = filter_input( INPUT_GET, 'id', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
		if ( ! is_array( $get_id ) ) {
			$get_id = (string) filter_input( INPUT_GET, 'id' );
		}

		if ( empty( $get_id ) ) {
			return;
		}

		$page_slug = sanitize_key( (string) filter_input( INPUT_GET, 'page' ) );

		$wpnonce = (string) filter_input( INPUT_GET, '_wpnonce' );
		if ( empty( $wpnonce ) ) {
			Helper::csrf_exception( $page_slug );
		}

		$post_id = $get_id;
		$nonce   = is_array( $post_id ) ? 'bulk-' . $this->_args['plural'] : $action . '-' . $this->_args['singular'];

		if ( empty( $wpnonce ) || ! wp_verify_nonce( $wpnonce, $nonce ) ) {
			Helper::csrf_exception( $page_slug );
		}

		$ids = is_array( $post_id ) ? $post_id : array( $post_id );

		// Convert to integers, then remove zero values
		$ids = array_filter( array_map( 'intval', $ids ) );

		if ( empty( $ids ) ) {
			return;
		}

		$table      = Helper::get_db_table_name( 'linkpages' );
		$meta_table = Helper::get_db_table_name( 'meta' );

		if ( 'duplicate' === $action ) {
			foreach ( $ids as $id ) {
				$original = Linkpages_Helper::get_by_id( $id );

				if ( empty( $original ) ) {
					continue;
				}

				$base_slug = $original['slug'] . '-copy';
				$new_slug  = $base_slug;
				$counter   = 1;

				while ( ! empty( Linkpages_Helper::get_by_slug( $new_slug ) ) ) {
					$new_slug = $base_slug . '-' . ( ++$counter );
				}

				unset( $original['id'], $original['created_at'] );
				$original['slug']   = $new_slug;
				$original['author'] = get_current_user_id();

				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				$wpdb->insert( $table, $original );
				$new_id = $wpdb->insert_id;

				if ( ! $new_id ) {
					continue;
				}

				// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
				$meta_rows = $wpdb->get_results(
					$wpdb->prepare( "SELECT meta_key, meta_value FROM {$meta_table} WHERE linkpage_id=%d", $id ),
					ARRAY_A
				);
				// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

				foreach ( (array) $meta_rows as $meta_row ) {
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
					$wpdb->insert(
						$meta_table,
						array(
							'meta_key'    => $meta_row['meta_key'],
							'meta_value'  => $meta_row['meta_value'],
							'link_id'     => 0,
							'linkpage_id' => $new_id,
						)
					);
				}
			}

			return;
		}

		$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$result = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$table} WHERE id IN ($placeholders)",
				...$ids
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare

		if ( false !== $result ) {
			// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
			$wpdb->query(
				$wpdb->prepare(
					"DELETE FROM {$meta_table} WHERE linkpage_id IN ($placeholders)",
					...$ids
				)
			);
			// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
		}
	}

	/**
	 * @throws Exception
	 */
	public function prepare_items() {
		global $wpdb;
		$table_linkpages = Helper::get_db_table_name( 'linkpages' );
		$table_track     = Helper::get_db_table_name( 'track' );
		$per_page        = 20;
		$columns         = $this->get_columns();
		$hidden          = array();
		$sortable        = $this->get_sortable_columns();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$total_items = intval( $wpdb->get_var( "SELECT COUNT(id) FROM {$table_linkpages}" ) );

		$this->_column_headers = array( $columns, $hidden, $sortable );
		$this->process_bulk_action();

		$order_arg = sanitize_text_field( (string) filter_input( INPUT_GET, 'order' ) );
		if ( empty( $order_arg ) ) {
			$order_arg = 'desc';
		}
		$orderby_arg = sanitize_text_field( (string) filter_input( INPUT_GET, 'orderby' ) );
		if ( empty( $orderby_arg ) ) {
			$orderby_arg = 'id';
		}
		$sort    = Helper::get_sort_params( $sortable, $order_arg, $orderby_arg );
		$order   = $sort['order'];
		$orderby = $sort['orderby'];
		$paged_q = (int) filter_input( INPUT_GET, 'paged', FILTER_SANITIZE_NUMBER_INT );
		$paged   = $paged_q ? ( $per_page * max( 0, $paged_q - 1 ) ) : 0;

		$where_clause = '';
		$prepare_args = array();

		$author = (int) filter_input( INPUT_GET, 'author', FILTER_SANITIZE_NUMBER_INT );
		if ( $author ) {
			if ( $author > 0 ) {
				$where_clause   = "WHERE linkpages.author = %d";
				$prepare_args[] = $author;
			}
		}

		$prepare_args[] = $per_page;
		$prepare_args[] = $paged;

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$current_data = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT *, COALESCE(v_track.views,0) AS views_count, COALESCE(c_track.clicks,0) AS clicks_count
                FROM {$table_linkpages} linkpages
                LEFT JOIN (
                    SELECT linkpage_id, COUNT(*) views 
                    FROM {$table_track} 
                    WHERE event_type='view' 
                    GROUP BY linkpage_id
                    ) v_track ON linkpages.id = v_track.linkpage_id
                LEFT JOIN (
                    SELECT linkpage_id, COUNT(*) clicks 
                    FROM {$table_track} 
                    WHERE event_type='click' AND linkpage_id > 0
                    GROUP BY linkpage_id
                    ) c_track ON linkpages.id = c_track.linkpage_id
                $where_clause
                ORDER BY $orderby $order LIMIT %d OFFSET %d",
				...$prepare_args
			),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber
		if ( ! $current_data ) {
			$current_data = array();
		}

		$this->items = $current_data;

		$this->set_pagination_args( array(
			'per_page'    => $per_page,
			'total_items' => $total_items,
			'total_pages' => ceil( $total_items / $per_page )
		) );
	}

	public function no_items() {
		esc_html_e( 'No Link Pages Found', 'clickwhale' );
	}
}
