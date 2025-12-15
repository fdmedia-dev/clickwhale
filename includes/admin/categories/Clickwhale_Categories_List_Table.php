<?php
namespace clickwhale\includes\admin\categories;

use Exception;
use WP_List_Table;
use clickwhale\includes\helpers\Helper;

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
                'plural'   => 'categories'
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
            $params = array( $search, $search );
        }

        return (array) $wpdb->get_results(
            empty( $params ) ? $query : $wpdb->prepare( $query, ...$params ),
            ARRAY_A
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
            '<a href="?page=' . CLICKWHALE_SLUG . '-edit-category&id=%d">%s</a>',
            $id,
            esc_html( wp_unslash( $item['title'] ) )
        );
        $actions = array(
            'edit'   => sprintf(
                '<a href="?page=' . CLICKWHALE_SLUG . '-edit-category&id=%d">%s</a>',
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
     * @param $item - row (key, value array)
     * @return string
     */
    public function column_description( $item ): string {
        return esc_html( wp_unslash( $item['description'] ) );
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
                esc_url( get_admin_url( get_current_blog_id(), 'admin.php?page=' . CLICKWHALE_SLUG ) ),
                $cat_id,
                intval( $total )
            );
        } else {
            return '';
        }
    }

    /**
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
     * @return array
     */
    public function get_columns(): array {
        return array(
            'cb'          => '<input type="checkbox" />',
            'title'       => __( 'Title', 'clickwhale' ),
            'description' => __( 'Description', 'clickwhale' ),
            'count'       => __( 'Links count', 'clickwhale' )
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
        $table = Helper::get_db_table_name( 'categories' );
        $placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );

        $result = $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM $table WHERE id IN ($placeholders)",
                ...$ids
            )
        );

        if ( false !== $result ) {
            foreach ( $ids as $id ) {
                $this->update_link_categories( $id );
            }
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
                unset( $categories[$i] );
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
     * @throws Exception
     */
    public function prepare_items() {
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

        $this->set_pagination_args( array(
            'per_page'    => $per_page,
            'total_items' => $total_items,
            'total_pages' => ceil( $total_items / $per_page )
        ) );
    }

    public function no_items() {
        esc_html_e( 'No Categories Found', 'clickwhale' );
    }
}
