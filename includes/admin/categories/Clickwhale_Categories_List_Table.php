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

	public function __construct() {
		parent::__construct(
			array(
				'singular' => 'category',
				'plural'   => 'categories',
			)
		);
	}

    private function get_current_data( string $search = '' ): array {
        global $wpdb;
        $table = Helper::get_db_table_name( 'categories' );
        $query = "SELECT id,title,description FROM $table";
        $params = array();

        if ( ! empty( $search ) ) {
            $search = '%' . $wpdb->esc_like( $search ) . '%';
            $query .= " WHERE title LIKE %s OR description LIKE %s";
            $params = [ $search, $search ];
        }

        return (array) $wpdb->get_results(
            empty( $params ) ? $query : $wpdb->prepare( $query, ...$params ),
            ARRAY_A
        );
    }

    /**
     * [REQUIRED] this is a default column renderer
     *
     * @param $item - row (key, value array)
     * @param $column_name - string (key)
     *
     * @return string
     */
    public function column_default( $item, $column_name ): string {
        return esc_html( $item[ $column_name ] );
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
     * @return string
     */
    public function column_title( $item ): string {
        $id = intval( $item['id'] );
        // Links going to `/admin.php?page=[your_plugin_page][&other_params]` notice how we used `$_GET['page']`,
        // so action will be done on curren page.
        // Also notice how we use `$this->_args['singular']` so in this example it will be something `like &link=2`
        $title = sprintf(
            '<a href="?page=' . CLICKWHALE_SLUG . '-edit-category&id=%d">%s</a>',
            $id,
            esc_html( wp_unslash( $item['title'] ) )
        );
        $actions = array(
            'edit'   => sprintf(
                '<a href="?page=' . CLICKWHALE_SLUG . '-edit-category&id=%d">%s</a>',
                $id,
                __( 'Edit', CLICKWHALE_NAME )
            ),
            'delete' => sprintf(
                '<a href="?page=%s&action=delete&id=%d">%s</a>',
                sanitize_text_field( $_GET['page'] ),
                $id,
                __( 'Delete', CLICKWHALE_NAME )
            )
        );

        return sprintf( '%s %s',
            $title,
            $this->row_actions( $actions )
        );
    }

    /**
     * @param $item - row (key, value array)
     * @return string
     */
    public function column_description( $item ): string {
        return esc_html( $item['description'] );
    }

    public function column_count( $item ): string {
        global $wpdb;
        $table = Helper::get_db_table_name( 'links' );

        $cat_id = intval( $item['id'] );
        $total = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM $table
                WHERE categories = '{$cat_id}'
                OR categories LIKE '{$cat_id},%'
                OR categories LIKE '%,{$cat_id},%'
                OR categories LIKE '%,{$cat_id}'"
            )
        );

        if ( $total ) {
            return sprintf(
                '<a href="%s&category=%d">%d</a>',
                get_admin_url( get_current_blog_id(), 'admin.php?page=' . CLICKWHALE_SLUG ),
                $cat_id,
                intval( $total )
            );
        } else {
            return '';
        }
    }

    /**
     * [REQUIRED] this is how checkbox column renders
     *
     * @param $item - row (key, value array)
     */
    public function column_cb( $item ) {
        $item_id = intval( $item['id'] );
        ?>
        <input type="checkbox"
               name="id[]"
               id="cb-select-<?php echo $item_id; ?>"
               value="<?php echo $item_id; ?>"
        />
        <?php
    }

    /**
     * [REQUIRED] This method return columns to display in table
     * you can skip columns that you do not want to show
     *
     * @return array
     */
    public function get_columns(): array {
        return array(
            'cb'          => '<input type="checkbox" />',
            'title'       => __( 'Title', CLICKWHALE_NAME ),
            'description' => __( 'Description', CLICKWHALE_NAME ),
            'count'       => __( 'Links count', CLICKWHALE_NAME )
        );
    }

    /**
     * [OPTIONAL] This method return columns that may be used to sort table
     * all strings in array - is column names
     * notice that true on name column means that its default sort
     *
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
            'delete' => __( 'Delete', CLICKWHALE_NAME )
        );
    }

    /**
     * This method processes bulk actions
     * it can be outside of class
     * it can not use wp_redirect coz there is output already
     * in this example we are processing delete action
     * message about successful deletion will be shown on page in next part
     */
    public function process_bulk_action() {
        global $wpdb;

        $categories_table = Helper::get_db_table_name( 'categories' );

        if ( 'delete' !== $this->current_action() ) {
            return;
        }

        if ( empty( $_GET['id'] ) ) {
            return;
        }

        if ( is_array( $_GET['id'] ) ) {
            foreach ( $_GET['id'] as $id ) {
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
            $id = intval( $_GET['id'] );
            $wpdb->query(
                $wpdb->prepare(
                    "DELETE FROM $categories_table WHERE id = %d",
                    $id
                )
            );

            $this->update_link_categories( $id );
        }
    }

    private function update_link_categories( int $id ) {
        global $wpdb;

        $links_table = Helper::get_db_table_name( 'links' );

        $like_start = $wpdb->esc_like( "{$id}," );
        $like_middle = $wpdb->esc_like( ",{$id}," );
        $like_end = $wpdb->esc_like( ",{$id}" );

        $links = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $links_table
                WHERE categories = %d
                OR categories LIKE %s
                OR categories LIKE %s
                OR categories LIKE %s",
                $id,
                "{$like_start}%",
                "%{$like_middle}%",
                "%{$like_end}"
            ),
            ARRAY_A
        );

        if ( ! $links ) {
            return;
        }

        foreach ( $links as $link ) {
            $categories = explode( ',', $link['categories'] );

            while ( ( $i = array_search( $id, $categories ) ) !== false ) {
                unset( $categories[ $i ] );
            }

            $categories = implode( ',', $categories );
            $link['categories'] = $categories;

            $wpdb->update(
                $links_table,
                $link,
                array( 'id' => intval( $link['id'] ) )
            );
        }
    }

    /**
     * This is the most important method.
     * It will get rows from database and prepare them to be showed in table
     */
    function prepare_items() {
        global $wpdb;

        $table        = Helper::get_db_table_name( 'categories' );
        $per_page     = 20;
        $current_page = $this->get_pagenum();
        $columns      = $this->get_columns();
        $hidden       = array();
        $sortable     = $this->get_sortable_columns();

        $this->_column_headers = array( $columns, $hidden, $sortable );
        $this->process_bulk_action();

        $order_arg = isset( $_GET['order'] ) ? sanitize_text_field( $_GET['order'] ) : 'asc';
        $orderby_arg = isset( $_GET['orderby'] ) ? sanitize_text_field( $_GET['orderby'] ) : 'title';
        $sort = Helper::get_sort_params( $sortable, $order_arg, $orderby_arg );
        $order = $sort['order'];
        $orderby = $sort['orderby'];
        $paged = isset( $_GET['paged'] ) ? ( $per_page * max( 0, intval( $_GET['paged'] ) - 1 ) ) : 0;

        // Will be used in pagination settings
        if ( isset( $_GET['page'] ) && ! empty( $_GET['s'] ) ) {
            $current_data = $this->get_current_data( sanitize_text_field( $_GET['s'] ) );
            if ( ! $current_data ) {
                $current_data = array();
            }
            $total_items = count( $current_data );
            $current_data = array_slice( $current_data, ( ( $current_page - 1 ) * $per_page ), $per_page );
            $this->items = $current_data;
        } else {
            $total_items = intval( $wpdb->get_var( "SELECT COUNT(id) FROM $table" ) );
            $current_data = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM $table ORDER BY $orderby $order LIMIT $per_page OFFSET %d",
                    $paged
                ),
                ARRAY_A
            );
            if ( ! $current_data ) {
                $current_data = array();
            }
            $this->items = $current_data;
        }

        // [REQUIRED] configure pagination
        $this->set_pagination_args( array(
            'total_items' => $total_items,                    // total items defined above
            'per_page'    => $per_page,                       // per page constant defined at top of method
            'total_pages' => ceil( $total_items / $per_page ) // calculate pages count
        ) );
    }
}