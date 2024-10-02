<?php
namespace clickwhale\includes\admin\categories;

use clickwhale\includes\helpers\Helper;
use WP_List_Table;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Custom_Table_Example_List_Table class that will display our custom table
 * records in nice table
 */
class Clickwhale_Categories_List_Table extends WP_List_Table {

	private $users_data;

	function __construct() {
		parent::__construct(
			array(
				'singular' => 'category',
				'plural'   => 'categories',
			)
		);
	}

	private function get_users_data( $search = "" ) {
		global $wpdb;
		$table = Helper::get_db_table_name( 'categories' );

		if ( ! empty( $search ) ) {
			return $wpdb->get_results(
				"SELECT id,title,description from $table
                     WHERE title Like '%{$search}%'  
                     OR description Like '%{$search}%'",
				ARRAY_A
			);
		} else {
			return $wpdb->get_results(
				"SELECT id,title,description from $table",
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
	 * @return string
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
	 * @return string
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
		$title = sprintf(
            '<a href="?page=' . CLICKWHALE_SLUG . '-edit-category&id=%d">%s</a>',
            intval( $item['id'] ),
            wp_unslash( $item['title'] )
        );
		$actions = array(
			'edit'   => sprintf(
                '<a href="?page=' . CLICKWHALE_SLUG . '-edit-category&id=%d">%s</a>',
                intval( $item['id'] ),
				__( 'Edit', CLICKWHALE_NAME )
            ),
			'delete' => sprintf(
                '<a href="?page=%s&action=delete&id=%d">%s</a>',
				sanitize_text_field( $_REQUEST['page'] ),
                intval( $item['id'] ),
                __( 'Delete', CLICKWHALE_NAME )
            )
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
		$table = Helper::get_db_table_name( 'links' );

		$category = intval( $item['id'] );
		$total    = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $table WHERE categories = '{$category}' OR categories LIKE '{$category},%' OR categories LIKE '%,{$category},%' OR categories LIKE '%,{$category}'" ) );
		if ( $total ) {
			return '<a href="' . get_admin_url( get_current_blog_id(),
					'admin.php?page=' . CLICKWHALE_SLUG ) . '&category=' . $category . '">' . $total . '</a>';
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
			'title'       => __( 'Title', CLICKWHALE_NAME ),
			'description' => __( 'Description', CLICKWHALE_NAME ),
			'count'       => __( 'Links count', CLICKWHALE_NAME ),
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

		$categories_table = Helper::get_db_table_name( 'categories' );

		if ( 'delete' === $this->current_action() ) {

			if ( empty( $_REQUEST['id'] ) ) {
				return false;
			}

			if ( is_array( $_REQUEST['id'] ) ) {
				foreach ( $_REQUEST['id'] as $id ) {
					$id = intval( $id );
					$wpdb->query(
						$wpdb->prepare(
							"DELETE FROM $categories_table WHERE id = %d",
							$id
						)
					);

					$this->update_link_categories( $id );
				}
			} else {
				$id = intval( $_REQUEST['id'] );
				$wpdb->query(
					$wpdb->prepare(
						"DELETE FROM $categories_table WHERE id = %d",
						$id
					)
				);
				// update link categories
				$this->update_link_categories( $id );
			}
		}
	}

	private function update_link_categories( int $id ) {
		global $wpdb;

		$links_table = Helper::get_db_table_name( 'links' );
		$links       = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $links_table WHERE categories = $id OR categories LIKE '{$id},%' OR categories LIKE '%,{$id},%' OR categories LIKE '%,{$id}'"
			), ARRAY_A
		);

		if ( ! $links ) {
			return;
		}

		foreach ( $links as $link ) {
			$categories = explode( ',', $link['categories'] );

			while ( ( $i = array_search( $id, $categories ) ) !== false ) {
				unset( $categories[ $i ] );
			}

			$categories         = implode( ',', $categories );
			$link['categories'] = $categories;

			$wpdb->update(
				$links_table,
				$link,
				array( 'id' => $link['id'] )
			);
		}
	}

	/**
	 * This is the most important method
	 *
	 * It will get rows from database and prepare them to be showed in table
	 */
	function prepare_items() {
		global $wpdb;

		$table        = Helper::get_db_table_name( 'categories' );
		$per_page     = 20; // constant, how much records will be shown per page
		$current_page = $this->get_pagenum();
		$columns      = $this->get_columns();
		$hidden       = array();
		$sortable     = $this->get_sortable_columns();

		// here we configure table headers, defined in our methods
		$this->_column_headers = array( $columns, $hidden, $sortable );

		//  process bulk action if any
		$this->process_bulk_action();

		// prepare query params, as usual current page, order by and order direction
		$sort    = Helper::get_sort_params(
			$sortable,
			$_REQUEST['order'] ?? 'asc',
			$_REQUEST['orderby'] ?? 'title'
		);
		$order   = $sort['order'];
		$orderby = $sort['orderby'];
		$paged   = isset( $_REQUEST['paged'] ) ? ( $per_page * max( 0, intval( $_REQUEST['paged'] ) - 1 ) ) : 0;

		// will be used in pagination settings
		if ( isset( $_GET['page'] ) && ! empty( $_GET['s'] ) ) {
			$this->users_data = $this->get_users_data( sanitize_text_field( $_GET['s'] ) );
			$total_items      = count( $this->users_data );
			$this->users_data = array_slice( $this->users_data, ( ( $current_page - 1 ) * $per_page ), $per_page );
			$this->items      = $this->users_data;
		} else {
			$this->users_data = $this->get_users_data();
			$total_items      = $wpdb->get_var( "SELECT COUNT(id) FROM $table" );
			$this->items      = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM $table ORDER BY $orderby $order LIMIT %d OFFSET %d", $per_page, $paged ),
				ARRAY_A
			);
		}

		// [REQUIRED] configure pagination
		$this->set_pagination_args( array(
			'total_items' => $total_items, // total items defined above
			'per_page'    => $per_page, // per page constant defined at top of method
			'total_pages' => ceil( $total_items / $per_page ) // calculate pages count
		) );
	}
}