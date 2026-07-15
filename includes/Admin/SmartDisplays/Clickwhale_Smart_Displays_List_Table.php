<?php

namespace Clickwhale\Admin\SmartDisplays;

use Exception;
use WP_List_Table;
use Clickwhale\Helpers\{Helper, Smart_Displays_Helper};

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @since 2.6.0
 */
class Clickwhale_Smart_Displays_List_Table extends WP_List_Table {

	public function __construct() {
		parent::__construct(
			array(
				'singular' => 'smart-display',
				'plural'   => 'smart-displays'
			)
		);
	}

	/**
	 * @param $item - row (key, value array)
	 * @param $column_name - string (key)
	 * @return string
	 */
	public function column_default( $item, $column_name ): string {
		return esc_html( $item[$column_name] );
	}

	/**
	 * @param $item - row (key, value array)
	 * @return string
	 */
	public function column_title( $item ): string {
		$id           = intval( $item['id'] );
		$display_name = ! empty( $item['name'] ) ? $item['name'] : $item['title'];
		$is_inactive  = empty( $item['title'] );
		$label_html   = $is_inactive
			? ' <span class="post-state">' . esc_html__( 'Inactive', 'clickwhale' ) . '</span>'
			: '';
		$title = sprintf(
			'<a href="?page=' . CLICKWHALE_SLUG . '-edit-smart-display&id=%d">%s</a>%s',
			$id,
			esc_html( wp_unslash( $display_name ) ),
			$label_html
		);
		$actions = array(
			'edit'   => sprintf(
				'<a href="?page=' . CLICKWHALE_SLUG . '-edit-smart-display&id=%d">%s</a>',
				$id,
				__( 'Edit', 'clickwhale' )
			),
			'duplicate' => sprintf(
				'<a href="%s">%s</a>',
				esc_url(
					wp_nonce_url(
						admin_url( 'admin.php?page=' . sanitize_key( $_GET['page'] ) . '&action=duplicate&id=' . $id ),
						'duplicate-' . $this->_args['singular']
					)
				),
				__( 'Duplicate', 'clickwhale' )
			),
			'delete' => sprintf(
				'<a href="%s">%s</a>',
				esc_url(
					wp_nonce_url(
						admin_url( 'admin.php?page=' . sanitize_key( $_GET['page'] ) . '&action=delete&id=' . $id ),
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
	 * Shortcode with copy button
	 * *
	 * @param $item - row (key, value array)
	 * @return string
	 */
	public function column_shortcode( $item ): string {
		return sprintf(
			'<div class="shortcode-input--wrap"><input class="shortcode-input" type="text" value="%1$s" readonly /><a href="#" class="shortcode-input--btn" data-id="%2$d" title="%3$s"><span class="dashicons dashicons-clipboard"></span></a></div>',
			esc_attr( Smart_Displays_Helper::get_shortcode( intval( $item['id'] ) ) ),
			intval( $item['id'] ),
			esc_attr__( 'Copy Shortcode', 'clickwhale' )
		);
	}

	/**
	 * @param $item - row (key, value array)
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
		return array(
			'cb'        => '<input type="checkbox" />',
			'title'     => __( 'Name', 'clickwhale' ),
			'shortcode' => __( 'Shortcode', 'clickwhale' )
		);
	}

	/**
	 * @return array
	 */
	public function get_sortable_columns(): array {
		return array(
			'title' => array( 'title', true )
		);
	}

	/**
	 * Return array of built-in actions if has any
	 *
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
		$action = $this->current_action();

		if ( ! in_array( $action, array( 'delete', 'duplicate' ), true ) ) {
			return;
		}

		if ( empty( $_GET['id'] ) ) {
			return;
		}

		$page_slug = sanitize_key( $_GET['page'] );

		if ( ! isset( $_GET['_wpnonce'] ) ) {
			Helper::csrf_exception( $page_slug );
		}

		$nonce = is_array( $_GET['id'] ) ? 'bulk-' . $this->_args['plural'] : $action . '-' . $this->_args['singular'];

		if ( ! wp_verify_nonce( $_GET['_wpnonce'], $nonce ) ) {
			Helper::csrf_exception( $page_slug );
		}

		$ids = is_array( $_GET['id'] ) ? $_GET['id'] : array( $_GET['id'] );

		// Convert to integers, then remove zero values
		$ids = array_filter( array_map( 'intval', $ids ) );

		if ( empty( $ids ) ) {
			return;
		}

		global $wpdb;
		$table      = Helper::get_db_table_name( 'smart_displays' );
		$data_table = Helper::get_db_table_name( 'smart_display_data' );

		if ( 'duplicate' === $action ) {
			foreach ( $ids as $id ) {
				$original = Smart_Displays_Helper::get_by_id( $id );

				if ( empty( $original ) ) {
					continue;
				}

				unset( $original['id'], $original['created_at'] );

				if ( ! empty( $original['name'] ) ) {
					/* translators: %s: original smart display name */
					$original['name'] = sprintf( __( '%s (copy)', 'clickwhale' ), $original['name'] );
				} elseif ( ! empty( $original['title'] ) ) {
					/* translators: %s: original smart display title */
					$original['title'] = sprintf( __( '%s (copy)', 'clickwhale' ), $original['title'] );
				}

				$wpdb->insert( $table, $original );
				$new_id = $wpdb->insert_id;

				if ( ! $new_id ) {
					continue;
				}

				$data_rows = $wpdb->get_results(
					$wpdb->prepare( "SELECT integration, is_active, params, raw_data FROM $data_table WHERE smart_display_id=%d", $id ),
					ARRAY_A
				);

				foreach ( (array) $data_rows as $data_row ) {
					$wpdb->insert(
						$data_table,
						array(
							'smart_display_id' => $new_id,
							'integration'       => $data_row['integration'],
							'is_active'         => $data_row['is_active'],
							'params'            => $data_row['params'],
							'raw_data'          => $data_row['raw_data'],
						)
					);
				}
			}

			return;
		}

		$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );

		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM $table WHERE id IN ($placeholders)",
				...$ids
			)
		);

		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM $data_table WHERE smart_display_id IN ($placeholders)",
				...$ids
			)
		);
	}

	/**
	 * @throws Exception
	 */
	public function prepare_items() {
		global $wpdb;
		$table       = Helper::get_db_table_name( 'smart_displays' );
		$per_page    = 20;
		$columns     = $this->get_columns();
		$hidden      = array();
		$sortable    = $this->get_sortable_columns();

		$this->_column_headers = array( $columns, $hidden, $sortable );
		$this->process_bulk_action();

		$order_arg = isset( $_GET['order'] ) ? sanitize_text_field( $_GET['order'] ) : 'desc';
		$orderby_arg = isset( $_GET['orderby'] ) ? sanitize_text_field( $_GET['orderby'] ) : 'id';
		$sort = Helper::get_sort_params( $sortable, $order_arg, $orderby_arg );
		$order = $sort['order'];
		$orderby = $sort['orderby'];
		$paged = isset( $_GET['paged'] ) ? ( $per_page * max( 0, intval( $_GET['paged'] ) - 1 ) ) : 0;

		if ( ! empty( $_GET['s'] ) ) {
			$search       = '%' . $wpdb->esc_like( sanitize_text_field( $_GET['s'] ) ) . '%';
			$total_items  = intval( $wpdb->get_var(
				$wpdb->prepare( "SELECT COUNT(id) FROM $table WHERE title LIKE %s", $search )
			));
			$current_data = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM $table WHERE title LIKE %s ORDER BY $orderby $order LIMIT %d OFFSET %d",
					$search,
					$per_page,
					$paged
				),
				ARRAY_A
			);
		} else {
			$total_items  = intval( $wpdb->get_var( "SELECT COUNT(id) FROM $table" ) );
			$current_data = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM $table ORDER BY $orderby $order LIMIT %d OFFSET %d",
					$per_page,
					$paged
				),
				ARRAY_A
			);
		}

		$this->items = $current_data ?: array();

		$this->set_pagination_args( array(
			'per_page'    => $per_page,
			'total_items' => $total_items,
			'total_pages' => ceil( $total_items / $per_page )
		) );
	}

	public function no_items() {
		_e( 'No Smart Displays Found', 'clickwhale' );
	}
}