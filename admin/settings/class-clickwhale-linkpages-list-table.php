<?php

/**
 * Custom_Table_Example_List_Table class that will display our custom table
 * records in nice table
 */
class Clickwhale_Linkpages_List_Table extends WP_List_Table {

	private $users_data;

	function __construct() {
		global $status, $page;
		parent::__construct(
			array(
				'singular' => 'link',
				'plural'   => 'links',
			)
		);
	}

	private function get_users_data( $search = "" ) {
		global $wpdb;

		if ( ! empty( $search ) ) {
			return $wpdb->get_results(
				"SELECT id,title,slug,description,views from {$wpdb->prefix}clickwhale_linkpages
                     WHERE title Like '%{$search}%' 
                     OR slug Like '%{$search}%' 
                     OR description Like '%{$search}%'",
				ARRAY_A
			);
		} else {
			return $wpdb->get_results(
				"SELECT id,title,slug,description,views from {$wpdb->prefix}clickwhale_linkpages",
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
	 * Render column with actions,
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
		$options = get_option( 'clickwhale_general_options' );

		return '<div class="slug-input--wrap"><input class="slug-input" type="text" value="' . $options['slug'] . '/' . $item['slug'] . '" readonly><a href="#" class="slug-input--btn" data-id="' . $item['id'] . '" title="' . __( 'Copy Link', 'clickwhale' ) . '"><span class="dashicons dashicons-clipboard"></span></a></div>';
	}

	/**
	 * Total views per linkpage
	 *
	 * @param $item - row (key, value array)
	 *
	 * @return string
	 */
	function column_views( $item ) {
		global $wpdb;

		return 'in progress...';
	}


	/**
	 * [REQUIRED] this is how checkbox column renders
	 *
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
	 * [REQUIRED] This method return columns to display in table
	 * you can skip columns that you do not want to show
	 * like content, or description
	 *
	 * @return array
	 */
	function get_columns() {
		return array(
			'cb'    => '<input type="checkbox" />',             //Render a checkbox instead of text
			'title' => __( 'Title', 'clickwhale' ),
			//'slug'  => __( 'Link', 'clickwhale' ),
			'views' => __( 'Views', 'clickwhale' ),
		);
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
		return array(
			'delete' => 'Delete'
		);
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
			$total_items      = $wpdb->get_var( "SELECT COUNT(id) FROM {$wpdb->prefix}clickwhale_linkpages" );
		}

		// prepare query params, as usual current page, order by and order direction
		$paged   = isset( $_REQUEST['paged'] ) ? ( $per_page * max( 0, intval( $_REQUEST['paged'] ) - 1 ) ) : 0;
		$orderby = ( isset( $_REQUEST['orderby'] ) && in_array( $_REQUEST['orderby'], array_keys( $this->get_sortable_columns() ) ) ) ? sanitize_text_field( $_REQUEST['orderby'] ) : 'id';
		$order   = ( isset( $_REQUEST['order'] ) && in_array( $_REQUEST['order'], array(
				'asc',
				'desc'
			) ) ) ? sanitize_text_field( $_REQUEST['order'] ) : 'desc';

		// [REQUIRED] define $items array
		// notice that last argument is ARRAY_A, so we will retrieve array
		if ( isset( $_GET['page'] ) && isset( $_GET['s'] ) ) {
			$this->items = $this->users_data;
		} else {
			$this->items = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}clickwhale_linkpages ORDER BY $orderby $order LIMIT %d OFFSET %d", $per_page, $paged ), ARRAY_A );
		}

		// [REQUIRED] configure pagination
		$this->set_pagination_args( array(
			'total_items' => $total_items,                  // total items defined above
			'per_page'    => $per_page,                        // per page constant defined at top of method
			'total_pages' => ceil( $total_items / $per_page ) // calculate pages count
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