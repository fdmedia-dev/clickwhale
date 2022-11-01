<?php

/**
 * Custom_Table_Example_List_Table class that will display our custom table
 * records in nice table
 */
class ClickwhaleLinkpagesListTable extends WP_List_Table {

	function __construct() {
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
	 * @return HTML
	 */
	function column_default( $item, $column_name ) {
		return $item[ $column_name ];
	}

	/**
	 * @param $item - row (key, value array)
	 *
	 * @return HTML
	 */

	/**
	 * @param $item - row (key, value array)
	 *
	 * @return string
	 */
	function column_title( $item ) {
		$title   = sprintf( '<a href="?page=clickwhale-edit-linkpage&id=%s">%s</a>', $item['id'], $item['title'] );
		$actions = array(
			'edit'   => sprintf( '<a href="?page=clickwhale-edit-linkpage&id=%s">%s</a>', $item['id'], __( 'Edit', 'clickwhale' ) ),
			'view'   => '<a href="' . get_bloginfo( 'url' ) . '/' . $item['slug'] . '" target="_blank">View</a>',
			'delete' => sprintf( '<a href="?page=%s&action=delete&id=%s">%s</a>', sanitize_text_field( $_REQUEST['page'] ), $item['id'], __( 'Delete', 'clickwhale' ) ),
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
	function column_slug( $item ) {
		return '<div class="slug-input--wrap"><input class="slug-input" type="text" value="' . $item['slug'] . '" readonly><a href="#" class="slug-input--btn" data-id="' . $item['id'] . '" title="' . __( 'Copy Link', 'clickwhale' ) . '"><span class="dashicons dashicons-clipboard"></span></a></div>';
	}

	/**
	 * @param $item - row (key, value array)
	 *
	 * @return string
	 */
	function column_views( $item ) {
		global $wpdb;

		return $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}clickwhale_track WHERE linkpage_id=%d AND event_type='view'", intval( $item['id'] ) ) );
	}

	/**
	 * @param $item - row (key, value array)
	 *
	 * @return string
	 */
	function column_links( $item ) {
		$links = maybe_unserialize( $item['links'] );
		$count = $links ? count( $links ) : 0;

		return $count . ' / ' . ClickwhaleLinkpagesHelper::get_links_limit();
	}

	/**
	 * @param $item - row (key, value array)
	 *
	 * @return string
	 */
	function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="id[]" value="%s" />',
			$item['id']
		);
	}

	/**
	 * @return array
	 */
	function get_columns() {
		return array(
			'cb'    => '<input type="checkbox" />',
			'title' => __( 'Title', 'clickwhale' ),
			'slug'  => __( 'Link', 'clickwhale' ),
			'links' => __( 'Links', 'clickwhale' ),
			'views' => __( 'Views', 'clickwhale' ),
		);
	}

	/**
	 * @return array
	 */
	function get_sortable_columns() {
		return array(
			'title' => array( 'title', true ),
		);
	}

	/**
	 * @return array
	 */
	function get_bulk_actions() {
		return array(
			'delete' => 'Delete'
		);
	}

	function process_bulk_action() {
		global $wpdb;

		if ( 'delete' === $this->current_action() && isset( $_REQUEST['id'] ) ) {
			if ( is_array( $_REQUEST['id'] ) ) {
				foreach ( $_REQUEST['id'] as $id ) {
					$wpdb->query(
						$wpdb->prepare(
							"DELETE FROM {$wpdb->prefix}clickwhale_linkpages WHERE id IN(%d)",
							intval( $id )
						)
					);
				}
			} else {
				$wpdb->query(
					$wpdb->prepare(
						"DELETE FROM {$wpdb->prefix}clickwhale_linkpages WHERE id IN(%d)",
						intval( $_REQUEST['id'] )
					)
				);
			}
		}
	}

	function prepare_items() {
		global $wpdb;

		$per_page    = 20;
		$columns     = $this->get_columns();
		$hidden      = [];
		$sortable    = $this->get_sortable_columns();
		$total_items = $wpdb->get_var( "SELECT COUNT(id) FROM {$wpdb->prefix}clickwhale_linkpages" );

		$this->_column_headers = array( $columns, $hidden, $sortable );
		$this->process_bulk_action();

		$paged   = isset( $_REQUEST['paged'] ) ? ( $per_page * max( 0, intval( $_REQUEST['paged'] ) - 1 ) ) : 0;
		$orderby = ( isset( $_REQUEST['orderby'] ) && in_array( $_REQUEST['orderby'], array_keys( $this->get_sortable_columns() ) ) ) ? sanitize_text_field( 'title' ) : 'id';
		$order   = '';
		if ( isset( $_REQUEST['order'] ) ) {
			$order = $_REQUEST['order'] === 'asc' ? 'asc' : 'desc';
		}
		$this->items = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}clickwhale_linkpages ORDER BY $orderby $order LIMIT %d OFFSET %d", $per_page, $paged ), ARRAY_A );

		$this->set_pagination_args( array(
			'total_items' => $total_items,
			'per_page'    => $per_page,
			'total_pages' => ceil( $total_items / $per_page )
		) );
	}

	public function no_items() {
		_e( 'No linkpages found.', 'clickwhale' );
	}

	public function display_tablenav( $which ) {
		?>
        <div class="tablenav <?php echo esc_attr( $which ); ?>">
            <div class="alignleft actions">
				<?php $this->bulk_actions( $which ); ?>
            </div>
			<?php
			$this->pagination( $which );
			?>
            <br class="clear"/>
        </div>
		<?php
	}
}