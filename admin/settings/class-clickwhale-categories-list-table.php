<?php

/**
 * Custom_Table_Example_List_Table class that will display our custom table
 * records in nice table
 */
class Clickwhale_Categories_List_Table extends WP_List_Table {

	private $users_data;

	function __construct() {
		global $status, $page;
		parent::__construct(
			array(
				'singular' => 'category',
				'plural'   => 'categories',
			)
		);
	}

	private function get_users_data( $search = "" ) {
		global $wpdb;

		if ( ! empty( $search ) ) {
			return $wpdb->get_results(
				"SELECT id,title,description from {$wpdb->prefix}clickwhale_categories
                     WHERE title Like '%{$search}%'  
                     OR description Like '%{$search}%'",
				ARRAY_A
			);
		} else {
			return $wpdb->get_results(
				"SELECT id,title,description from {$wpdb->prefix}clickwhale_categories",
				ARRAY_A
			);
		}
	}

	/**
	 * [REQUIRED] this is a default column renderer
	 *
	 * @param $item - row (key, value array)
	 * @param $column_name - string (key)
	 *
	 * @return HTML
	 */
	function column_default( $item, $column_name ) {
		return $item[ $column_name ];
	}

	/**
	 * Render columns
	 * method name must be like this: "column_[column_name]"
	 *
	 * @param $item - row (key, value array)
	 *
	 * @return HTML
	 */

	/**
	 * This is example, how to render column with actions,
	 * when you hover row "Edit | Delete" links showed
	 *
	 * @param $item - row (key, value array)
	 *
	 * @return string
	 */
	function column_title( $item ) {
		// links going to /admin.php?page=[your_plugin_page][&other_params]
		// notice how we used $_REQUEST['page'], so action will be done on curren page
		// also notice how we use $this->_args['singular'] so in this example it will
		// be something like &link=2
		$title   = sprintf( '<a href="?page=clickwhale-edit-category&id=%d">%s</a>', intval( $item['id'] ), $item['title'] );
		$actions = array(
			'edit'   => sprintf( '<a href="?page=clickwhale-edit-category&id=%d">%s</a>', intval( $item['id'] ), __( 'Edit', 'clickwhale' ) ),
			'delete' => sprintf( '<a href="?page=%s&action=delete&id=%d">%s</a>', sanitize_text_field( $_REQUEST['page'] ), intval( $item['id'] ), __( 'Delete', 'clickwhale' ) ),
		);

		return sprintf( '%s %s',
			$title,
			$this->row_actions( $actions )
		);
	}

	function column_description( $item ) {
		return $item['description'];
	}

	function column_count( $item ) {
		global $wpdb;

		$category = intval( $item['id'] );
		$total    = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}clickwhale_links WHERE categories = '{$category}' OR categories LIKE '{$category},%' OR categories LIKE '%,{$category},%' OR categories LIKE '%,{$category}'" ) );
		if ( $total ) {
			return '<a href="' . get_admin_url( get_current_blog_id(), 'admin.php?page=clickwhale' ) . '&category=' . $category . '">' . $total . '</a>';
		} else {
			return false;
		}

	}


	/**
	 * [REQUIRED] this is how checkbox column renders
	 *
	 * @param $item - row (key, value array)
	 *
	 * @return string
	 */
	function column_cb( $item ) {
		?>
        <input type="checkbox" name="id[]" id="cb-select-<?php echo esc_attr( $item['id'] ); ?>"
               value="<?php echo esc_attr( $item['id'] ); ?>"/>
		<?php
	}

	/**
	 * [REQUIRED] This method return columns to display in table
	 * you can skip columns that you do not want to show
	 * like content, or description
	 *
	 * @return array
	 */
	function get_columns() {
		$columns = array(
			'cb'          => '<input type="checkbox" />', //Render a checkbox instead of text
			'title'       => __( 'Title', 'clickwhale' ),
			'description' => __( 'Description', 'clickwhale' ),
			'count'       => __( 'Count', 'clickwhale' ),
		);

		return $columns;
	}

	/**
	 * [OPTIONAL] This method return columns that may be used to sort table
	 * all strings in array - is column names
	 * notice that true on name column means that its default sort
	 *
	 * @return array
	 */
	function get_sortable_columns() {
		return array(
			'title' => array( 'title', true ),
		);
	}

	/**
	 * Return array of bult actions if has any
	 *
	 * @return array
	 */
	function get_bulk_actions() {
		$actions = array(
			'delete' => 'Delete'
		);

		return $actions;
	}

	/**
	 * This method processes bulk actions
	 * it can be outside of class
	 * it can not use wp_redirect coz there is output already
	 * in this example we are processing delete action
	 * message about successful deletion will be shown on page in next part
	 */
	function process_bulk_action() {
		global $wpdb;

		if ( 'delete' === $this->current_action() ) {
			if ( isset( $_REQUEST['id'] ) ) {
				if ( is_array( $_REQUEST['id'] ) ) {
					foreach ( $_REQUEST['id'] as $id ) {
						$id = intval( $id );
						$wpdb->query(
							$wpdb->prepare(
								"DELETE FROM {$wpdb->prefix}clickwhale_categories WHERE id IN(%d)",
								$id
							)
						);
					}
				} else {
					$id = intval( $_REQUEST['id'] );
					$wpdb->query(
						$wpdb->prepare(
							"DELETE FROM {$wpdb->prefix}clickwhale_categories WHERE id IN(%d)",
							$id
						)
					);
				}
			}
		}
	}

	/**
	 * This is the most important method
	 *
	 * It will get rows from database and prepare them to be showed in table
	 */
	function prepare_items() {
		global $wpdb;

		$per_page     = 20; // constant, how much records will be shown per page
		$current_page = $this->get_pagenum();
		$columns      = $this->get_columns();
		$hidden       = array();
		$sortable     = $this->get_sortable_columns();

		// here we configure table headers, defined in our methods
		$this->_column_headers = array( $columns, $hidden, $sortable );

		//  process bulk action if any
		$this->process_bulk_action();

		// will be used in pagination settings
		if ( isset( $_GET['page'] ) && isset( $_GET['s'] ) ) {
			$this->users_data = $this->get_users_data( sanitize_text_field( $_GET['s'] ) );
			$total_items      = count( $this->users_data );
			$this->users_data = array_slice( $this->users_data, ( ( $current_page - 1 ) * $per_page ), $per_page );
			usort( $this->users_data, array( &$this, 'usort_reorder' ) );
		} else {
			$this->users_data = $this->get_users_data();
			$total_items      = $wpdb->get_var( "SELECT COUNT(id) FROM {$wpdb->prefix}clickwhale_categories" );
		}

		// prepare query params, as usual current page, order by and order direction
		$paged   = isset( $_REQUEST['paged'] ) ? ( $per_page * max( 0, intval( $_REQUEST['paged'] ) - 1 ) ) : 0;
		$orderby = ( isset( $_REQUEST['orderby'] ) && in_array( $_REQUEST['orderby'], array_keys( $this->get_sortable_columns() ) ) ) ? sanitize_text_field( $_REQUEST['orderby'] ) : 'id';
		$order   = ( isset( $_REQUEST['order'] ) && in_array( $_REQUEST['order'], array(
				'asc',
				'desc'
			) ) ) ? sanitize_text_field( $_REQUEST['order'] ) : 'asc';

		// [REQUIRED] define $items array
		// notice that last argument is ARRAY_A, so we will retrieve array
		if ( isset( $_GET['page'] ) && isset( $_GET['s'] ) ) {
			$this->items = $this->users_data;
		} else {
			$this->items = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}clickwhale_categories ORDER BY $orderby $order LIMIT %d OFFSET %d", $per_page, $paged ), ARRAY_A );
		}

		// [REQUIRED] configure pagination
		$this->set_pagination_args( array(
			'total_items' => $total_items, // total items defined above
			'per_page'    => $per_page, // per page constant defined at top of method
			'total_pages' => ceil( $total_items / $per_page ) // calculate pages count
		) );
	}
}