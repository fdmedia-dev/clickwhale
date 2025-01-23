<?php
namespace clickwhale\includes\admin\links;

use clickwhale\includes\helpers\{Helper, Categories_Helper};
use WP_List_Table;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Custom_Table_Example_List_Table class that will display our custom table
 * records in nice table
 */
class Clickwhale_Links_List_Table extends WP_List_Table {

	public function __construct() {
		global $status, $page;
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
        $table_links = Helper::get_db_table_name( 'links' );
        $table_track = Helper::get_db_table_name( 'track' );
        $orderby = sanitize_text_field( $orderby );
        $order = strtolower( $order ) === 'desc' ? 'DESC' : 'ASC';

        if ( empty( $params ) ) {
            return (array) $wpdb->get_results(
                "SELECT links.id, links.title, links.url, links.slug, links.description, links.categories, links.author, COALESCE(track.clicks,0) AS clicks_count 
                FROM $table_links links
                LEFT JOIN (SELECT link_id, COUNT(*) clicks FROM $table_track WHERE event_type='click' GROUP BY link_id ) track ON links.id = track.link_id
                ORDER BY $orderby $order",
                ARRAY_A
            );
        }

        $search   = sanitize_text_field( $params['search'] ?? '' );
        $category = sanitize_text_field( $params['category'] ?? '' );
        $author   = sanitize_text_field( $params['author'] ?? '' );
        $prepared_args = array();

        $sql = "SELECT links.id, links.title, links.url, links.slug, links.description, links.categories, links.author, COALESCE(track.clicks,0) AS clicks_count ";
        $sql .= "FROM $table_links links ";
        $sql .= "LEFT JOIN (SELECT link_id, COUNT(*) clicks FROM $table_track WHERE event_type='click' GROUP BY link_id ) track ON links.id = track.link_id ";

        if ( $search || $category || $author ) {
            $sql .= "WHERE ";
            if ( $search ) {
                $search = '%' . $wpdb->esc_like( $search ) . '%';
                $sql .= "(links.title LIKE %s OR links.url LIKE %s OR links.slug LIKE %s OR links.description LIKE %s) ";
                $prepared_args = array_fill( 0, 4, $search );

                if ( $category || $author ) {
                    $sql .= " AND ";
                }
            }
            if ( $category ) {
                $like_start = $wpdb->esc_like( "{$category}," );
                $like_middle = $wpdb->esc_like( ",{$category}," );
                $like_end = $wpdb->esc_like( ",{$category}" );

                $sql .= "(links.categories = %s OR links.categories LIKE %s OR links.categories LIKE %s OR links.categories LIKE %s) ";
                $prepared_args = array_merge( $prepared_args, array( intval( $category ), $like_start, $like_middle, $like_end ) );

                if ( $author ) {
                    $sql .= " AND ";
                }
            }
            if ( $author ) {
                $sql .= "links.author = " . intval( $author ) . " ";
            }
        }

        $sql .= "ORDER BY $orderby $order";

        if ( ! empty( $prepared_args ) ) {
            $query = $wpdb->prepare( $sql, ...$prepared_args );
        } else {
            $query = $sql;
        }

        return (array) $wpdb->get_results( $query, ARRAY_A );
    }

    public function extra_tablenav( $which ) {

        $categories = Categories_Helper::get_all();

        if ( ! $categories ) {
            return;
        }

        if ( $which != "top" ) {
            return;
        } ?>

        <div class="alignleft actions bulkactions">
            <select name="category" class="clickwhale-filter-categories">
                <option value=""><?php _e( 'All Categories', CLICKWHALE_NAME ); ?></option>
                <?php foreach ( $categories as $category ) {
                    $category_id = intval( $category->id );
                    $selected = isset( $_GET['category'] ) && $_GET['category'] == $category_id ? ' selected = "selected"' : '';
                    ?>
                    <option value="<?php echo $category_id; ?>" <?php echo $selected; ?>><?php esc_html_e( $category->title ); ?></option>
                <?php } ?>
            </select>
            <input type="submit" class="button" value="<?php _e( 'Filter', CLICKWHALE_NAME ); ?>" />
        </div>
        <?php
    }

    /**
     * [REQUIRED] this is a default column renderer
     *
     * @param $item - row (key, value array)
     * @param $column_name - string (key)
     * @return string
     */
    public function column_default( $item, $column_name ): string {
        return esc_html( $item[ $column_name ] );
    }

    /**
     * Render columns
     * method name must be like this: "column_[ column_name ]"
     *
     * @param $item - row (key, value array)
     * @return string
     */

    /**
     * Render column with actions,
     * when you hover row "Edit | Delete" links showed
     *
     * @param $item - row (key, value array)
     * @return string
     */
    public function column_title( $item ): string {
        // Links going to `/admin.php?page=[your_plugin_page][&other_params]` notice how we used `$_GET['page']`,
        // so action will be done on current page.
        // Also notice how we use `$this->_args['singular']` so in this example it will be something like `&link=2`
        $id = intval( $item['id'] );
        $title = sprintf(
            '<a href="?page=' . CLICKWHALE_SLUG . '-edit-link&id=%d">%s</a>',
            $id,
            esc_html( wp_unslash( $item['title'] ) )
        );
        $actions = array(
            'edit'   => sprintf(
                '<a href="?page=' . CLICKWHALE_SLUG . '-edit-link&id=%d">%s</a>',
                $id,
                __( 'Edit', CLICKWHALE_NAME )
            ),
            'reset'  => sprintf(
                '<a href="?page=%s&action=reset&id=%d">%s</a>',
                sanitize_text_field( $_GET['page'] ),
                $id,
                __( 'Reset Clicks', CLICKWHALE_NAME )
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
     * Link url with copy button
     *
     * @param $item - row (key, value array)
     * @return string
     */
    public function column_slug( $item ): string {
        return '<div class="slug-input--wrap"><input class="slug-input" type="text" value="' . esc_attr( $item['slug'] ) . '" readonly><a href="#" class="slug-input--btn" data-id="' . intval( $item['id'] ) . '" title="' . __( 'Copy Link', CLICKWHALE_NAME ) . '"><span class="dashicons dashicons-clipboard"></span></a></div>';
    }

    /**
     * Target URL
     *
     * @param $item - row (key, value array)
     * @return string
     */
    public function column_url( $item ): string {
        return esc_html( $item['url'] );
    }

    /**
     * List of categories
     *
     * @param $item - row (key, value array)
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

        $link_categories = explode( ',', esc_html( $item['categories'] ) );
        $last = end( $link_categories );
        $current_categories = '';

        foreach ( $link_categories as $v ) {
            $v = intval( $v );
            $result = array_column( $categories, null, 'id' )[ $v ] ?? false;

            if ( empty( $result ) ) {
                continue;
            }

            $current_categories .= sprintf(
                '<a href="%s&category=%d">%s</a>',
                get_admin_url( get_current_blog_id(), 'admin.php?page=' . CLICKWHALE_SLUG ),
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
     * @return string
     * @since 1.1.0
     *
     */
    public function column_clicks_count( $item ): string {
        return esc_html( $item['clicks_count'] );
    }

    /**
     * @param $item
     * @return string
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
     * [REQUIRED] this is how checkbox column renders
     *
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
     * [REQUIRED] This method return columns to display in table
     * you can skip columns that you do not want to show
     *
     * @return array
     */
    public function get_columns(): array {
        $tracking_options = get_option( 'clickwhale_tracking_options' );

        $columns = array(
            'cb'           => '<input type="checkbox" />',
            'title'        => __( 'Title', CLICKWHALE_NAME ),
            'slug'         => __( 'Slug', CLICKWHALE_NAME ),
            'url'          => __( 'Target URL', CLICKWHALE_NAME ),
            'categories'   => __( 'Categories', CLICKWHALE_NAME ),
            'clicks_count' => __( 'Clicks', CLICKWHALE_NAME ),
            'author'       => __( 'Author', CLICKWHALE_NAME )
        );

        if ( isset( $tracking_options['disable_tracking'] ) ) {
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
            'title' => array( 'title', true ),
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
            'edit' => __( 'Edit', CLICKWHALE_NAME ),
            'reset' => __( 'Reset Clicks', CLICKWHALE_NAME ),
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

        $action = $this->current_action();

        if ( ! $action || ! isset( $_GET['id'] ) ) {
            return;
        }

        if ( is_array( $_GET['id'] ) ) {
            $ids = $_GET['id'];
        } else {
            $ids[] = $_GET['id'];
        }

        switch ( $action ) {
            case 'edit':
                $data = array();

                if ( isset( $_GET['link_category'] ) ) {
                    $categories = $_GET['link_category'];

                    if ( is_array( $categories ) ) {
                        $data['categories'] = implode( ',', array_map( 'sanitize_text_field', $categories ) );
                    } else {
                        $data['categories'] = sanitize_text_field( $categories );
                    }
                }

                if ( isset( $_GET['link_author'] ) ) {
                    $link_author = sanitize_text_field( $_GET['link_author'] );

                    if ( $link_author !== '-1' ) {
                        $data['author'] = $link_author;
                    }
                }

                if ( isset( $_GET['redirection_status'] ) ) {
                    $redirection = sanitize_text_field( $_GET['redirection_status'] );

                    if ( $redirection !== '-1' ) {
                        $data['redirection'] = $redirection;
                    }
                }

                if ( isset( $_GET['nofollow_status'] ) ) {
                    $nofollow = sanitize_text_field( $_GET['nofollow_status'] );

                    if ( $nofollow !== '-1' ) {
                        $data['nofollow'] = $nofollow;
                    }
                }

                if ( isset( $_GET['sponsored_status'] ) ) {
                    $sponsored = sanitize_text_field( $_GET['sponsored_status'] );

                    if ( $sponsored !== '-1' ) {
                        $data['sponsored'] = $sponsored;
                    }
                }

                if ( $data ) {
                    foreach ( $ids as $id ) {
                        $wpdb->update(
                            Helper::get_db_table_name( 'links' ),
                            $data,
                            array( 'id' => intval( $id ) )
                        );
                    }
                    $url = remove_query_arg( '_wp_http_referer' );
                    $url = remove_query_arg(
                        array(
                            'link_category',
                            'link_author',
                            'redirection_status',
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
                    echo( "<script>window.location.href = '" . esc_js( $url ) . "'</script>" );
                }
                break;

            case 'delete':
                $links_table = Helper::get_db_table_name( 'links' );
                $meta_table  = Helper::get_db_table_name( 'meta' );
                foreach ( $ids as $id ) {
                    $wpdb->query(
                        $wpdb->prepare(
                            "DELETE FROM $links_table WHERE id=%d",
                            intval( $id )
                        )
                    );
                    $wpdb->query(
                        $wpdb->prepare(
                            "DELETE FROM $meta_table WHERE link_id=%d",
                            intval( $id )
                        )
                    );
                }
                do_action( 'clickwhale_link_deleted', $ids );
                break;

            case 'reset':
                $table = Helper::get_db_table_name( 'track' );
                foreach ( $ids as $id ) {
                    $wpdb->query(
                        $wpdb->prepare(
                            "DELETE FROM $table WHERE link_id=%d",
                            intval( $id )
                        )
                    );
                }
                break;
        }
    }

    /**
     * This is the most important method.
     * It will get rows from database and prepare them to be showed in table
     */
    public function prepare_items() {
        $per_page     = 20;
        $current_page = $this->get_pagenum();
        $columns      = $this->get_columns();
        $hidden       = array();
        $sortable     = $this->get_sortable_columns();

        $this->_column_headers = array( $columns, $hidden, $sortable );
        $this->process_bulk_action();

        $order_arg = isset( $_GET['order'] ) ? sanitize_text_field( $_GET['order'] ) : '';
        $orderby_arg = isset( $_GET['orderby'] ) ? sanitize_text_field( $_GET['orderby'] ) : 'id';
        $sort = Helper::get_sort_params( $sortable, $order_arg, $orderby_arg );
        $order = $sort['order'];
        $orderby = $sort['orderby'];

        // Will be used in pagination settings
        $params = array();

        if ( ! empty( $_GET['s'] ) ) {
            $params['search'] = sanitize_text_field( $_GET['s'] );
        }
        if ( ! empty( $_GET['category'] ) ) {
            $params['category'] = intval( $_GET['category'] );
        }
        if ( ! empty( $_GET['author'] ) ) {
            $params['author'] = intval( $_GET['author'] );
        }

        $current_data = $this->get_current_data( $order, $orderby, $params );
        $total_items = count( $current_data );
        $current_data = array_slice( $current_data, ( ( $current_page - 1 ) * $per_page ), $per_page );
        $this->items = $current_data;

        // [REQUIRED] Configure pagination
        $this->set_pagination_args( array(
            'total_items' => $total_items,                     // total items defined above
            'per_page'    => $per_page,                        // per page constant defined at top of method
            'total_pages' => ceil( $total_items / $per_page )  // calculate pages count
        ) );
    }

	public function no_items() {
		_e( 'No Links Found.', CLICKWHALE_NAME );
	}

	public function display() {
		$singular = $this->_args['singular'];
		$this->display_tablenav( 'top' );
		$this->screen->render_screen_reader_content( 'heading_list' );
		?>
        <table class="wp-list-table <?php echo implode( ' ', $this->get_table_classes() ); ?>">
			<?php $this->print_table_description(); ?>
            <thead>
            <tr>
				<?php $this->print_column_headers(); ?>
            </tr>
            </thead>
            <tbody id="the-list"<?php echo $singular ? " data-wp-lists='list:$singular'" : ''; ?>>
			<?php
                if ( ( isset( $_GET['action'] ) && $_GET['action'] === 'edit' ) && ! empty( $_GET['id'] ) ) {
                    $quick_edit = new Clickwhale_Links_Bulk_Edit( $_GET['id'], $this->get_column_count() );
                    echo $quick_edit->render_quick_edit();
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

	public function display_tablenav( $which ) {
		?>
        <div class="tablenav <?php esc_attr_e( $which ); ?>">
            <div class="alignleft actions">
				<?php $this->bulk_actions( $which ); ?>
            </div>
			<?php
			$this->extra_tablenav( $which );
			$this->pagination( $which );
			?>
            <br class="clear"/>
        </div>
		<?php
	}
}