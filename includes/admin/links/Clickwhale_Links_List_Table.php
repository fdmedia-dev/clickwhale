<?php

namespace Clickwhale\Admin\Links;

use Exception;
use WP_List_Table;
use Clickwhale\Helpers\{Helper, Categories_Helper};

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Custom_Table_Example_List_Table class that will display our custom table
 * records in nice table
 */
class Clickwhale_Links_List_Table extends WP_List_Table {

    public function __construct() {
        parent::__construct(
                array(
                        'singular' => 'link',
                        'plural'   => 'links',
                        'ajax'     => true
                )
        );
    }

    private function get_current_data( $order, $orderby, $params ): array {
        global $wpdb;
        $table_links       = Helper::get_db_table_name( 'links' );
        $table_track       = Helper::get_db_table_name( 'track' );
        $non_api_condition = '(links.created_by_api IS NULL OR links.created_by_api = 0)';

        $allowed_columns   = array_keys( $this->get_columns() );
        $allowed_columns[] = 'id';
        $orderby           = in_array( $orderby, $allowed_columns, true ) ? $orderby : 'id';

        if ( 'clicks_count' !== $orderby ) {
            $orderby = 'links.' . $orderby;
        }

        $order = strtolower( $order ) === 'desc' ? 'DESC' : 'ASC';

        if ( empty( $params ) ) {
            // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
            return (array) $wpdb->get_results(
                    $wpdb->prepare(
                            "SELECT links.id, links.title, links.url, links.slug, links.description, links.categories, links.created_by_api, COALESCE(track.clicks,0) AS clicks_count
                    FROM {$table_links} links
                    LEFT JOIN (SELECT link_id, COUNT(*) clicks FROM {$table_track} WHERE event_type=%s GROUP BY link_id) track ON links.id = track.link_id
                    WHERE links.created_by_api IS NULL OR links.created_by_api = 0
                    ORDER BY $orderby $order",
                            'click'
                    ),
                    ARRAY_A
            );
            // phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        }

        $search        = isset( $params['search'] ) ? sanitize_text_field( wp_unslash( $params['search'] ) ) : '';
        $category      = isset( $params['category'] ) ? sanitize_text_field( wp_unslash( $params['category'] ) ) : '';
        $created_by    = isset( $params['created_by'] ) ? sanitize_text_field( wp_unslash( $params['created_by'] ) ) : 'admin';
        $prepared_args = array( 'click' );

        $sql = "SELECT links.id, links.title, links.url, links.slug, links.description, links.categories, links.created_by_api, COALESCE(track.clicks,0) AS clicks_count
        FROM {$table_links} links
        LEFT JOIN (SELECT link_id, COUNT(*) clicks FROM {$table_track} WHERE event_type=%s GROUP BY link_id) track ON links.id = track.link_id ";

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        $sql = apply_filters( 'clickwhale_links_list_table_sql', $sql, $table_links, $table_track );

        $is_filtered_created_by = ( 'all' !== $created_by );

        if ( $search || $category || $is_filtered_created_by ) {
            $sql .= "WHERE ";

            // Search
            if ( $search ) {
                $search        = '%' . $wpdb->esc_like( $search ) . '%';
                $sql           .= "(links.title LIKE %s OR links.url LIKE %s OR links.slug LIKE %s OR links.description LIKE %s) ";
                $prepared_args = array_merge( $prepared_args, array_fill( 0, 4, $search ) );

                if ( $category || $is_filtered_created_by ) {
                    $sql .= "AND ";
                }
            }

            // Category
            if ( $category ) {
                $like_start  = $wpdb->esc_like( "{$category}," );
                $like_middle = $wpdb->esc_like( ",{$category}," );
                $like_end    = $wpdb->esc_like( ",{$category}" );

                $sql           .= "(links.categories = %d OR links.categories LIKE %s OR links.categories LIKE %s OR links.categories LIKE %s) ";
                $prepared_args = array_merge( $prepared_args, array(
                        intval( $category ),
                        "{$like_start}%",
                        "%{$like_middle}%",
                        "%{$like_end}"
                ) );

                if ( $is_filtered_created_by ) {
                    $sql .= "AND ";
                }
            }

            // Created by API
            if ( $is_filtered_created_by ) {
                if ( 'api' === $created_by ) {
                    $sql .= "links.created_by_api = 1 ";
                } else {
                    $sql .= "(links.created_by_api IS NULL OR links.created_by_api = 0) ";
                }
            }
        }

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $sql .= "ORDER BY $orderby $order";

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
        return (array) $wpdb->get_results( $wpdb->prepare( $sql, ...$prepared_args ), ARRAY_A );
    }

    public function extra_tablenav( $which ) {
        if ( $which !== "top" ) {
            return;
        }

        $categories = Categories_Helper::get_all();
        ?>
        <div class="alignleft actions bulkactions">
            <?php
            if ( ! empty( $categories ) ) {
                ?>
                <select name="category" class="clickwhale-filter-categories">
                    <option value=""><?php esc_html_e( 'All Categories', 'clickwhale' ); ?></option>
                    <?php foreach ( $categories as $category ) {
                        $category_id  = intval( $category->id );
                        $get_category = (int) filter_input( INPUT_GET, 'category', FILTER_SANITIZE_NUMBER_INT );
                        $selected     = $get_category === $category_id ? ' selected="selected"' : '';
                        ?>
                        <option value="<?php echo esc_attr( $category_id ); ?>" <?php echo esc_attr( $selected ); ?>><?php echo esc_html( $category->title ); ?></option>
                    <?php } ?>
                </select>
                <?php
            }
            ?>
            <select name="created_by" class="clickwhale-filter-created_by">
                <?php
                $options    = array(
                        'all'   => __( 'All Links', 'clickwhale' ),
                        'admin' => __( 'Non-API Links', 'clickwhale' ),
                        'api'   => __( 'Created by API Links', 'clickwhale' )
                );
                $created_by = sanitize_key( (string) filter_input( INPUT_GET, 'created_by' ) );
                $selected   = in_array( $created_by, array_keys( $options ), true ) ? $created_by : 'admin';
                ?>
                <?php foreach ( $options as $value => $label ) {
                    ?>
                    <option value="<?php echo esc_attr( $value ); ?>"
                            <?php selected( $selected, $value ); ?>
                    ><?php echo esc_html( $label ); ?></option>
                    <?php
                }
                ?>
            </select>
            <input type="submit" class="button" value="<?php esc_attr_e( 'Filter', 'clickwhale' ); ?>"/>
        </div>
        <?php
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
     *
     * @return string
     */
    public function column_title( $item ): string {
        $id           = intval( $item['id'] );
        $current_page = sanitize_key( (string) filter_input( INPUT_GET, 'page' ) );
        $title        = sprintf(
                '<a href="?page=' . CLICKWHALE_SLUG . '-edit-link&id=%d">%s</a>',
                $id,
                esc_html( wp_unslash( $item['title'] ) )
        );
        $actions      = array(
                'edit'   => sprintf(
                        '<a href="?page=' . CLICKWHALE_SLUG . '-edit-link&id=%d">%s</a>',
                        $id,
                        __( 'Edit', 'clickwhale' )
                ),
                'scan'   => sprintf(
                        '<a href="?page=' . CLICKWHALE_SLUG . '-edit-link&id=%d&tab=link_scanner">%s</a>',
                        $id,
                        __( 'Scan', 'clickwhale' )
                ),
                'reset'  => sprintf(
                        '<a href="%s">%s</a>',
                        esc_url(
                                wp_nonce_url(
                                        admin_url( 'admin.php?page=' . $current_page . '&action=reset&id=' . $id ),
                                        'reset-' . $this->_args['singular']
                                )
                        ),
                        __( 'Reset Clicks', 'clickwhale' )
                ),
                'delete' => sprintf(
                        '<a href="%s">%s</a>',
                        esc_url(
                                wp_nonce_url(
                                        admin_url( 'admin.php?page=' . $current_page . '&action=delete&id=' . $id ),
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
     *
     * @return string
     */
    public function column_slug( $item ): string {
        return '<div class="slug-input--wrap"><input class="slug-input" type="text" value="' . esc_attr( $item['slug'] ) . '" readonly><a href="#" class="slug-input--btn" data-id="' . intval( $item['id'] ) . '" title="' . __( 'Copy Link', 'clickwhale' ) . '"><span class="dashicons dashicons-clipboard"></span></a></div>';
    }

    /**
     * Target URL
     *
     * @param $item - row (key, value array)
     *
     * @return string
     */
    public function column_url( $item ): string {
        return esc_html( $item['url'] );
    }

    /**
     * List of categories
     *
     * @param $item - row (key, value array)
     *
     * @return string
     */
    public function column_categories( $item ): string {
        $dash = '&mdash;';

        if ( empty( $item['categories'] ) ) {
            return $dash;
        }

        $categories = Categories_Helper::get_all( 'title', 'asc', 'ARRAY_A' );

        if ( ! $categories ) {
            return $dash;
        }

        $link_categories    = explode( ',', esc_html( $item['categories'] ) );
        $last               = end( $link_categories );
        $current_categories = '';

        foreach ( $link_categories as $v ) {
            $v      = intval( $v );
            $result = array_column( $categories, null, 'id' )[ $v ] ?? false;

            if ( empty( $result ) ) {
                continue;
            }

            $current_categories .= sprintf(
                    '<a href="%s&category=%d">%s</a>',
                    esc_url( get_admin_url( get_current_blog_id(), 'admin.php?page=' . CLICKWHALE_SLUG ) ),
                    intval( $result['id'] ),
                    esc_html( wp_unslash( $result['title'] ) )
            );

            if ( $v != $last ) {
                $current_categories .= ',<br>';
            }
        }

        return $current_categories ?: $dash;
    }

    /**
     * Total clicks per link
     *
     * @param $item - row (key, value array)
     *
     * @return string
     * @since 1.1.0
     *
     */
    public function column_clicks_count( $item ): string {
        return esc_html( $item['clicks_count'] );
    }

    /**
     * @param $item - row (key, value array)
     *
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

        $columns = array(
                'cb'           => '<input type="checkbox" />',
                'title'        => __( 'Title', 'clickwhale' ),
                'slug'         => __( 'Slug', 'clickwhale' ),
                'url'          => __( 'Target URL', 'clickwhale' ),
                'categories'   => __( 'Categories', 'clickwhale' ),
                'clicks_count' => __( 'Clicks', 'clickwhale' )
        );

        if ( ! empty( $tracking_options['disable_tracking'] ) ) {
            unset( $columns['clicks_count'] );
        }

        return apply_filters( 'clickwhale_links_list_table', $columns );
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
                'title'        => array( 'title', true ),
                'clicks_count' => array( 'clicks_count', true )
        );
    }

    /**
     * Return array of bulk actions if has any
     *
     * @return array
     */
    public function get_bulk_actions(): array {
        return array(
                'edit'   => __( 'Edit', 'clickwhale' ),
                'reset'  => __( 'Reset Clicks', 'clickwhale' ),
                'delete' => __( 'Delete', 'clickwhale' )
        );
    }

    /**
     * This method processes bulk actions
     * it can be outside of class
     * it cannot use wp_redirect coz there is output already
     * in this example we are processing delete action
     * a message about successful deletion will be shown on the page in the next part
     *
     * @throws Exception
     */
    public function process_bulk_action() {
        global $wpdb;

        $action = $this->current_action();

        if ( ! $action ) {
            return;
        }

        $get_page = sanitize_key( (string) filter_input( INPUT_GET, 'page' ) );
        $get_id   = filter_input( INPUT_GET, 'id', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );

        if ( ! is_array( $get_id ) ) {
            $get_id = (string) filter_input( INPUT_GET, 'id' );
        }

        if ( empty( $get_id ) ) {
            return;
        }

        $wpnonce = (string) filter_input( INPUT_GET, '_wpnonce' );

        if ( empty( $wpnonce ) ) {
            Helper::csrf_exception( $get_page );
        }

        switch ( $action ) {
            case 'edit':
                if ( ! wp_verify_nonce( $wpnonce, 'bulk-' . $this->_args['plural'] ) ) {
                    Helper::csrf_exception( $get_page );
                }

                $data = array();

                $categories = filter_input( INPUT_GET, 'link_category', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
                if ( is_array( $categories ) ) {
                    $data['categories'] = implode( ',', array_map( 'sanitize_text_field', $categories ) );
                } else {
                    $category_single = (string) filter_input( INPUT_GET, 'link_category' );
                    if ( $category_single !== '' && $category_single !== null ) {
                        $data['categories'] = sanitize_text_field( $category_single );
                    }
                }

                $redirection = sanitize_text_field( (string) filter_input( INPUT_GET, 'redirection_status' ) );
                if ( $redirection !== '' && $redirection !== '-1' ) {
                    $data['redirection'] = $redirection;
                }

                $link_target = sanitize_text_field( (string) filter_input( INPUT_GET, 'link_target_status' ) );
                if ( $link_target !== '' && $link_target !== '-1' ) {
                    $data['link_target'] = $link_target;
                }

                $nofollow = sanitize_text_field( (string) filter_input( INPUT_GET, 'nofollow_status' ) );
                if ( $nofollow !== '' && $nofollow !== '-1' ) {
                    $data['nofollow'] = $nofollow;
                }

                $sponsored = sanitize_text_field( (string) filter_input( INPUT_GET, 'sponsored_status' ) );
                if ( $sponsored !== '' && $sponsored !== '-1' ) {
                    $data['sponsored'] = $sponsored;
                }

                if ( $data ) {
                    $ids = is_array( $get_id ) ? $get_id : array( $get_id );

                    // Convert to integers, then remove zero values
                    $ids = array_filter( array_map( 'intval', $ids ) );

                    if ( ! empty( $ids ) ) {
                        foreach ( $ids as $id ) {
                            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                            $wpdb->update(
                                    Helper::get_db_table_name( 'links' ),
                                    $data,
                                    array( 'id' => $id )
                            );
                        }
                    }

                    $url = remove_query_arg( '_wp_http_referer' );
                    $url = remove_query_arg(
                            array(
                                    'link_category',
                                    'redirection_status',
                                    'link_target_status',
                                    'nofollow_status',
                                    'sponsored_status'
                            ),
                            $url
                    );
                    $url = add_query_arg(
                            array(
                                    'action'  => '-1',
                                    'action2' => '-1'
                            ),
                            $url
                    );
                    ?>
                    <script>window.location.href =<?php echo wp_json_encode( $url ); ?></script>
                    <?php
                }
                break;

            case 'delete':

                $nonce = is_array( $get_id ) ? 'bulk-' . $this->_args['plural'] : 'delete-' . $this->_args['singular'];

                if ( ! wp_verify_nonce( $wpnonce, $nonce ) ) {
                    Helper::csrf_exception( $get_page );
                }

                $ids = is_array( $get_id ) ? $get_id : array( $get_id );

                // Convert to integers, then remove zero values
                $ids = array_filter( array_map( 'intval', $ids ) );

                if ( empty( $ids ) ) {
                    break;
                }

                $links_table  = Helper::get_db_table_name( 'links' );
                $meta_table   = Helper::get_db_table_name( 'meta' );
                $placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );

                // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
                $result = $wpdb->query(
                        $wpdb->prepare(
                                "DELETE FROM {$links_table} WHERE id IN ($placeholders)",
                                ...$ids
                        )
                );
                // phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare

                if ( false !== $result ) {
                    // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
                    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
                    $wpdb->query(
                            $wpdb->prepare(
                                    "DELETE FROM {$meta_table} WHERE link_id IN ($placeholders)",
                                    ...$ids
                            )
                    );
                    // phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare

                    do_action( 'clickwhale_link_deleted', $ids );
                }
                break;

            case 'reset':
                $get_page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';

                if ( ! isset( $_GET['_wpnonce'] ) ) {
                    Helper::csrf_exception( $get_page );
                }

                $get_id = filter_input( INPUT_GET, 'id', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
                if ( null === $get_id ) {
                    $get_id = (string) filter_input( INPUT_GET, 'id' );
                }
                $nonce = is_array( $get_id ) ? 'bulk-' . $this->_args['plural'] : 'reset-' . $this->_args['singular'];

                $wpnonce = (string) filter_input( INPUT_GET, '_wpnonce' );
                if ( empty( $wpnonce ) || ! wp_verify_nonce( $wpnonce, $nonce ) ) {
                    Helper::csrf_exception( $get_page );
                }

                $ids = is_array( $get_id ) ? $get_id : array( $get_id );

                // Convert to integers, then remove zero values
                $ids = array_filter( array_map( 'intval', $ids ) );

                if ( empty( $ids ) ) {
                    break;
                }

                $table        = Helper::get_db_table_name( 'track' );
                $placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );

                // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
                $wpdb->query(
                        $wpdb->prepare(
                                "DELETE FROM {$table} WHERE link_id IN ($placeholders)",
                                ...$ids
                        )
                );
                // phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
                break;
        }
    }

    /**
     * @throws Exception
     */
    public function prepare_items() {
        $per_page     = 20;
        $current_page = $this->get_pagenum();
        $columns      = $this->get_columns();
        $hidden       = array();
        $sortable     = $this->get_sortable_columns();

        $this->_column_headers = array( $columns, $hidden, $sortable );
        $this->process_bulk_action();

        $order_arg = sanitize_text_field( (string) filter_input( INPUT_GET, 'order' ) );
        if ( empty( $order_arg ) ) {
            $order_arg = 'desc';
        }
        $orderby_arg = sanitize_text_field( (string) filter_input( INPUT_GET, 'orderby' ) );
        if ( empty( $orderby_arg ) ) {
            $orderby_arg = 'id';
        }
        $sort    = Helper::get_sort_params( $sortable, $order_arg, $orderby_arg );
        $order   = $sort['order'];
        $orderby = $sort['orderby'];

        // Will be used in pagination settings
        $params = array();

        $search_q = (string) filter_input( INPUT_GET, 's' );
        if ( '' !== $search_q && null !== $search_q ) {
            $params['search'] = sanitize_text_field( $search_q );
        }

        $cat_q = (int) filter_input( INPUT_GET, 'category', FILTER_SANITIZE_NUMBER_INT );
        if ( $cat_q ) {
            $params['category'] = $cat_q;
        }

        $created_by_q = (string) filter_input( INPUT_GET, 'created_by' );
        if ( '' !== $created_by_q && null !== $created_by_q ) {
            $params['created_by'] = sanitize_text_field( $created_by_q );
        }

        $current_data = $this->get_current_data( $order, $orderby, $params );
        $total_items  = count( $current_data );
        $current_data = array_slice( $current_data, ( ( $current_page - 1 ) * $per_page ), $per_page );
        $this->items  = $current_data;

        // [REQUIRED] Configure pagination
        $this->set_pagination_args( array(
                'per_page'    => $per_page,
                'total_items' => $total_items,
                'total_pages' => ceil( $total_items / $per_page )
        ) );
    }

    public function no_items() {
        esc_html_e( 'No Links Found', 'clickwhale' );
    }

    public function display() {
        $singular = $this->_args['singular'];
        $this->display_tablenav( 'top' );
        $this->screen->render_screen_reader_content( 'heading_list' );
        ?>
        <table class="wp-list-table <?php echo esc_attr( implode( ' ', $this->get_table_classes() ) ); ?>">
            <?php $this->print_table_description(); ?>
            <thead>
            <tr>
                <?php $this->print_column_headers(); ?>
            </tr>
            </thead>
            <tbody id="the-list"
                    <?php
                    if ( $singular ) {
                        echo " data-wp-lists='list:" . esc_attr( $singular ) . "'";
                    }
                    ?>
            >
            <?php
            $action_q = sanitize_key( (string) filter_input( INPUT_GET, 'action' ) );
            $id_q     = array_map( 'intval', (array) filter_input( INPUT_GET, 'id', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY ) );
            if ( $action_q === 'edit' && $id_q ) {
                $quick_edit = new Clickwhale_Links_Bulk_Edit( $id_q, $this->get_column_count() );
                echo wp_kses( $quick_edit->render_quick_edit(), Helper::get_allowed_tags() );
            }
            $this->display_rows_or_placeholder();
            ?>
            </tbody>
            <tfoot>
            <tr>
                <?php $this->print_column_headers( false ); ?>
            </tr>
            </tfoot>
        </table>
        <?php
        $this->display_tablenav( 'bottom' );
    }
}
