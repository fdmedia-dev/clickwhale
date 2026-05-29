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
		$id = intval( $item['id'] );
		$title = sprintf(
			'<a href="?page=' . CLICKWHALE_SLUG . '-edit-smart-display&id=%d">%s</a>',
			$id,
			esc_html( wp_unslash( $item['title'] ) )
		);
		$actions = array(
			'edit'   => sprintf(
				'<a href="?page=' . CLICKWHALE_SLUG . '-edit-smart-display&id=%d">%s</a>',
				$id,
				__( 'Edit', 'clickwhale' )
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
			'title'     => __( 'Title', 'clickwhale' ),
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
		if ( 'delete' !== $this->current_action() ) {
			return;
		}

		if ( empty( $_GET['id'] ) ) {
			return;
		}

		$page_slug = sanitize_key( $_GET['page'] );

		if ( ! isset( $_GET['_wpnonce'] ) ) {
			Helper::csrf_exception( $page_slug );
		}

		$nonce = is_array( $_GET['id'] ) ? 'bulk-' . $this->_args['plural'] : 'delete-' . $this->_args['singular'];

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
		$table = Helper::get_db_table_name( 'smart_displays' );
		$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );

		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM $table WHERE id IN ($placeholders)",
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