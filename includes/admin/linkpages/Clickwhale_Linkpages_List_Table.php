<?php
namespace clickwhale\includes\admin\linkpages;

use Exception;
use WP_List_Table;
use clickwhale\includes\helpers\{Helper, Linkpages_Helper};

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
            'view'   => sprintf(
                '<a href="%s" target="_blank">%s</a>',
                esc_url( trailingslashit( home_url( $item['slug'] ) ) ),
                __( 'View', 'clickwhale' )
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
     * Link url with copy button
     *
     * @param $item - row (key, value array)
     * @return string
     */
    public function column_slug( $item ): string {
        return '<div class="slug-input--wrap"><input class="slug-input" type="text" value="' . esc_attr( $item['slug'] ) . '" readonly><a href="#" class="slug-input--btn" data-id="' . intval( $item['id'] ) . '" title="' . __( 'Copy Link', 'clickwhale' ) . '"><span class="dashicons dashicons-clipboard"></span></a></div>';
    }

    /**
     * @param $item - row (key, value array)
     * @return string
     *
     * @since 1.1.0
     */
    public function column_views_count( $item ): string {
        return esc_html( $item['views_count'] );
    }

    /**
     * @param $item - row (key, value array)
     * @return string
     *
     * @since 1.1.0
     */
    public function column_clicks_count( $item ): string {
        return esc_html( $item['clicks_count'] );
    }

    public function column_author( $item ): string {
        $user_info = get_userdata( $item['author'] );

        return sprintf(
            '<a href="%s&author=%d">%s</a>',
            esc_url( get_admin_url( get_current_blog_id(), 'admin.php?page=' . CLICKWHALE_SLUG . '-linkpages' ) ),
            $user_info->ID,
            $user_info->display_name
        );
    }

    /**
     * @param $item - row (key, value array)
     * @return string
     */
    public function column_links( $item ): string {
        $links = maybe_unserialize( $item['links'] );
        $count = $links && is_array( $links ) ? count( $links ) : 0;

        return $count . ' / ' . Linkpages_Helper::get_linkpage_links_limit();
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

        if ( isset( $tracking_options['disable_tracking'] ) ) {
            unset( $columns['views_count'], $columns['clicks_count'] );
        }

        return $columns;
    }

    /**
     * @return array
     */
    public function get_sortable_columns(): array {
        return array(
            'title' => array( 'title', true ),
            'views_count' => array( 'views_count', true ),
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

        $table = Helper::get_db_table_name( 'linkpages' );
        $meta_table = Helper::get_db_table_name( 'meta' );
        $placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );

        $result = $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM $table WHERE id IN ($placeholders)",
                ...$ids
            )
        );

        if ( false !== $result ) {
            $wpdb->query(
                $wpdb->prepare(
                    "DELETE FROM $meta_table WHERE linkpage_id IN ($placeholders)",
                    ...$ids
                )
            );
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
        $total_items     = intval( $wpdb->get_var( "SELECT COUNT(id) FROM $table_linkpages" ) );

        $this->_column_headers = array( $columns, $hidden, $sortable );
        $this->process_bulk_action();

        $order_arg = isset( $_GET['order'] ) ? sanitize_key( $_GET['order'] ) : 'desc';
        $orderby_arg = isset( $_GET['orderby'] ) ? sanitize_key( $_GET['orderby'] ) : 'id';
        $sort = Helper::get_sort_params( $sortable, $order_arg, $orderby_arg );
        $order = $sort['order'];
        $orderby = $sort['orderby'];
        $paged = isset( $_GET['paged'] ) ? ( $per_page * max( 0, intval( $_GET['paged'] ) - 1 ) ) : 0;

        $where_clause = '';
        $prepare_args = array();

        if ( isset( $_GET['author'] ) ) {
            $author = intval( $_GET['author'] );

            if ( $author > 0 ) {
                $where_clause = "WHERE linkpages.author = %d";
                $prepare_args[] = $author;
            }
        }

        $prepare_args[] = $per_page;
        $prepare_args[] = $paged;

        $current_data = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT *, COALESCE(v_track.views,0) AS views_count, COALESCE(c_track.clicks,0) AS clicks_count
                FROM $table_linkpages linkpages
                LEFT JOIN (
                    SELECT linkpage_id, COUNT(*) views 
                    FROM $table_track 
                    WHERE event_type='view' 
                    GROUP BY linkpage_id
                    ) v_track ON linkpages.id = v_track.linkpage_id
                LEFT JOIN (
                    SELECT linkpage_id, COUNT(*) clicks 
                    FROM $table_track 
                    WHERE event_type='click' AND linkpage_id > 0
                    GROUP BY linkpage_id
                    ) c_track ON linkpages.id = c_track.linkpage_id
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
        _e( 'No Link Pages Found', 'clickwhale' );
    }
}
