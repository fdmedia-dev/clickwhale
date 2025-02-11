<?php
namespace clickwhale\includes\admin\tracking_codes;

use Exception;
use WP_List_Table;
use clickwhale\includes\helpers\{Helper, Tracking_Codes_Helper};

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * @since 1.2.0
 */
class Clickwhale_Tracking_Codes_List_Table extends WP_List_Table {

    public function __construct() {
        parent::__construct(
            array(
                'singular' => 'tracking-code',
                'plural'   => 'tracking-codes'
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
     * @return string
     */
    public function column_title( $item ): string {
        $id = intval( $item['id'] );
        $title = sprintf(
            '<a href="?page=' . CLICKWHALE_SLUG . '-edit-tracking-code&id=%d">%s</a>',
            $id,
            esc_html( wp_unslash( $item['title'] ) )
        );
        $actions = array(
            'edit'   => sprintf(
                '<a href="?page=' . CLICKWHALE_SLUG . '-edit-tracking-code&id=%d">%s</a>',
                $id,
                __( 'Edit', CLICKWHALE_NAME )
            ),
            'delete' => sprintf(
                '<a href="%s">%s</a>',
                esc_url(
                    wp_nonce_url(
                        admin_url( 'admin.php?page=' . sanitize_text_field( $_GET['page'] ) . '&action=delete&id=' . $id ),
                        'delete-' . $this->_args['singular']
                    )
                ),
                __( 'Delete', CLICKWHALE_NAME )
            )
        );

        return sprintf( '%s %s',
            $title,
            $this->row_actions( $actions )
        );
    }

    public function column_is_active( $item ): string {
        $checked = checked( intval( $item['is_active'] ), 1, false );
        $disabled = ! $checked && Tracking_Codes_Helper::is_active_limit() ? 'disabled="disabled"' : '';

        $output = '<label class="clickwhale-checkbox--toggle">';
        $output .= sprintf(
            '<input type="checkbox" name="is_active" class="clickwhale_tc_active_toggle" value="1" data-id="%d" %s %s />',
            intval( $item['id'] ),
            $checked,
            $disabled
        );
        $output .= '<span class="clickwhale-checkbox--toggle-slider"></span>';
        $output .= '</label>';

        return $output;
    }

    /**
     * @param $item - row (key, value array)
     * @return string
     */
    public function column_description( $item ): string {
        return esc_html( wp_unslash( $item['description'] ) );
    }

    /**
     * @param $item - row (key, value array)
     * @return string
     */
    public function column_position( $item ): string {
        $positionCode = maybe_unserialize( $item['position'] );

        if ( ! isset( $positionCode['code'] ) ) {
            return '';
        }

        switch ( $positionCode['code'] ) {
            case 'wp_head':
                $position = 'before <code>&lt;/head&gt;</code>';
                break;
            case 'wp_body_open':
                $position = 'after <code>&lt;body&gt;</code>';
                break;
            case 'wp_footer':
                $position = 'before <code>&lt;/body&gt;</code>';
                break;
            default:
                $position = '';
        }

        return $position;
    }

    /**
     * @param $item
     * @return string
     * @since 1.2.0
     */
    public function column_author( $item ): string {
        $user_info = get_userdata( $item['author'] );

        return sprintf(
            '<a href="%s&author=%d">%s</a>',
            get_admin_url( get_current_blog_id(), 'admin.php?page=' . CLICKWHALE_SLUG ),
            $user_info->ID,
            $user_info->display_name
        );
    }

    /**
     * @param $item - row (key, value array)
     * @return string
     */
    public function column_created_at( $item ): string {
        return esc_html( $item['created_at'] );
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
            'cb'          => '<input type="checkbox" />',
            'is_active'   => __( 'Active',      CLICKWHALE_NAME ),
            'title'       => __( 'Title',       CLICKWHALE_NAME ),
            'description' => __( 'Description', CLICKWHALE_NAME ),
            'position'    => __( 'Position',    CLICKWHALE_NAME ),
            'author'      => __( 'Author',      CLICKWHALE_NAME ),
            'created_at'  => __( 'Created',     CLICKWHALE_NAME ),
        );
    }

    /**
     * @return array
     */
    public function get_sortable_columns(): array {
        return array(
            'title' => array( 'title', true ),
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
     *
     * @return void
     * @throws Exception
     */
    public function process_bulk_action() {
        global $wpdb;

        if ( 'delete' !== $this->current_action() ) {
            return;
        }

        if ( empty( $_GET['id'] ) ) {
            return;
        }

        $page_slug = sanitize_text_field( $_GET['page'] );

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

        $table = Helper::get_db_table_name( 'tracking_codes' );
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
        $table       = Helper::get_db_table_name( 'tracking_codes' );
        $per_page    = 20;
        $columns     = $this->get_columns();
        $hidden      = array();
        $sortable    = $this->get_sortable_columns();
        $total_items = intval( $wpdb->get_var( "SELECT COUNT(id) FROM $table" ) );

        $this->_column_headers = array( $columns, $hidden, $sortable );
        $this->process_bulk_action();

        $order_arg = isset( $_GET['order'] ) ? sanitize_text_field( $_GET['order'] ) : 'desc';
        $orderby_arg = isset( $_GET['orderby'] ) ? sanitize_text_field( $_GET['orderby'] ) : 'id';
        $sort = Helper::get_sort_params( $sortable, $order_arg, $orderby_arg );
        $order = $sort['order'];
        $orderby = $sort['orderby'];
        $paged = isset( $_GET['paged'] ) ? ( $per_page * max( 0, intval( $_GET['paged'] ) - 1 ) ) : 0;

        $where_clause = '';
        $prepare_args = array();

        if ( isset( $_GET['author'] ) ) {
            $author = intval( $_GET['author'] );

            if ( $author > 0 ) {
                $where_clause = "WHERE author = %d";
                $prepare_args[] = $author;
            }
        }

        $prepare_args[] = $per_page;
        $prepare_args[] = $paged;

        $current_data = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table
                $where_clause
                ORDER BY $orderby $order LIMIT %d OFFSET %d",
                ...$prepare_args
            ),
            ARRAY_A
        );
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
        _e( 'No Tracking Codes Found', CLICKWHALE_NAME );
    }
}
