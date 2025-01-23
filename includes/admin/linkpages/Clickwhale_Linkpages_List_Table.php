<?php
namespace clickwhale\includes\admin\linkpages;

use clickwhale\includes\helpers\{Helper, Linkpages_Helper};
use WP_List_Table;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Custom_Table_Example_List_Table class that will display our custom table
 * records in nice table
 */
class Clickwhale_Linkpages_List_Table extends WP_List_Table {

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
     * @return string
     */
    function column_default( $item, $column_name ): string {
        return esc_html( $item[ $column_name ] );
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
                __( 'Edit', CLICKWHALE_NAME )
            ),
            'view'   => sprintf(
                '<a href="%s/%s/" target="_blank">%s</a>',
                get_bloginfo( 'url' ),
                esc_attr( $item['slug'] ),
                __( 'View', CLICKWHALE_NAME )
            ),
            'delete' => sprintf(
                '<a href="?page=%s&action=delete&id=%d">%s</a>',
                sanitize_text_field( $_GET['page'] ),
                $id,
                __( 'Delete', CLICKWHALE_NAME )
            ),
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
            get_admin_url( get_current_blog_id(), 'admin.php?page=' . CLICKWHALE_SLUG . '-linkpages' ),
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
     * [REQUIRED] This method return columns to display in table
     *  you can skip columns that you do not want to show
     *
     * @return array
     */
    public function get_columns(): array {
        $tracking_options = get_option( 'clickwhale_tracking_options' );
        $columns          = array(
            'cb'           => '<input type="checkbox" />',
            'title'        => __( 'Title', CLICKWHALE_NAME ),
            'slug'         => __( 'Link', CLICKWHALE_NAME ),
            'links'        => __( 'Links', CLICKWHALE_NAME ),
            'views_count'  => __( 'Views', CLICKWHALE_NAME ),
            'clicks_count' => __( 'Clicks', CLICKWHALE_NAME ),
            'author'       => __( 'Author', CLICKWHALE_NAME )
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
            'clicks_count' => array( 'clicks_count', true ),
        );
    }

    /**
     * @return array
     */
    public function get_bulk_actions(): array {
        return array(
            'delete' => __( 'Delete', CLICKWHALE_NAME )
        );
    }

    /**
     * @return void
     */
    public function process_bulk_action() {
        global $wpdb;

        $linkpages_table = Helper::get_db_table_name( 'linkpages' );
        $meta_table = Helper::get_db_table_name( 'meta' );

        if ( 'delete' !== $this->current_action() ) {
            return;
        }

        if ( ! isset( $_GET['id'] ) ) {
            return;
        }

        if ( is_array( $_GET['id'] ) ) {
            foreach ( $_GET['id'] as $id ) {
                $id = intval( $id );
                $wpdb->query(
                    $wpdb->prepare(
                        "DELETE FROM $linkpages_table WHERE id = %d",
                        $id
                    )
                );

                $wpdb->query(
                    $wpdb->prepare(
                        "DELETE FROM $meta_table WHERE linkpage_id = %d",
                        $id
                    )
                );
            }
        } else {
            $id = intval( $_GET['id'] );
            $wpdb->query(
                $wpdb->prepare(
                    "DELETE FROM $linkpages_table WHERE id = %d",
                    $id
                )
            );

            $wpdb->query(
                $wpdb->prepare(
                    "DELETE FROM $meta_table WHERE linkpage_id = %d",
                    $id
                )
            );
        }
    }

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

        $order_arg = isset( $_GET['order'] ) ? sanitize_text_field( $_GET['order'] ) : '';
        $orderby_arg = isset( $_GET['orderby'] ) ? sanitize_text_field( $_GET['orderby'] ) : '';
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

	public function no_items() {
		_e( 'No Link Pages Found.', CLICKWHALE_NAME );
	}
}