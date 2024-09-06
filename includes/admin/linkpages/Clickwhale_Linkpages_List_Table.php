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
	function column_default( $item, $column_name ) {
		return $item[ $column_name ];
	}

	/**
	 * @param $item - row (key, value array)
	 * @return string
	 */
	public function column_title( $item ) {
		$title = sprintf(
            '<a href="?page=' . CLICKWHALE_SLUG . '-edit-linkpage&id=%s">%s</a>',
            $item['id'],
			wp_unslash( $item['title'] )
        );
		$actions = array(
			'edit'   => sprintf(
                '<a href="?page=' . CLICKWHALE_SLUG . '-edit-linkpage&id=%s">%s</a>',
                $item['id'],
				__( 'Edit', CLICKWHALE_NAME )
            ),
			'view'   => '<a href="' . get_bloginfo( 'url' ) . '/' . $item['slug'] . '/" target="_blank">View</a>',
			'delete' => sprintf(
                '<a href="?page=%s&action=delete&id=%s">%s</a>',
				sanitize_text_field( $_REQUEST['page'] ),
                $item['id'],
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
	public function column_slug( $item ) {
		return '<div class="slug-input--wrap"><input class="slug-input" type="text" value="' . $item['slug'] . '" readonly><a href="#" class="slug-input--btn" data-id="' . $item['id'] . '" title="' . __( 'Copy Link', CLICKWHALE_NAME ) . '"><span class="dashicons dashicons-clipboard"></span></a></div>';
	}

	/**
	 * @param $item - row (key, value array)
	 * @return string
	 * @since 1.1.0
	 *
	 */
	public function column_views_count( $item ) {
		return $item['views_count'];
	}

	/**
	 * @param $item - row (key, value array)
	 * @return string
	 * @since 1.1.0
	 *
	 */
	public function column_clicks_count( $item ) {
		return $item['clicks_count'];
	}

	public function column_author( $item ) {
		$user_info = get_userdata( $item['author'] );

		return '<a href="' . get_admin_url( get_current_blog_id(), 'admin.php?page=' . CLICKWHALE_SLUG . '-linkpages' ) . '&author=' . $user_info->ID . '">' . $user_info->display_name . '</a>';
	}

	/**
	 * @param $item - row (key, value array)
	 * @return string
	 */
	public function column_links( $item ) {
		$links = maybe_unserialize( $item['links'] );
		$count = $links ? count( $links ) : 0;

		return $count . ' / ' . Linkpages_Helper::get_linkpage_links_limit();
	}

	/**
	 * @param $item - row (key, value array)
	 * @return string
	 */
	public function column_cb( $item ): string {
		return sprintf(
			'<input type="checkbox" name="id[]" value="%s" />',
			$item['id']
		);
	}

	/**
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
			'author'       => __( 'Author', CLICKWHALE_NAME ),
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
			'title'        => array( 'title', true ),
			'views_count'  => array( 'views_count', true ),
			'clicks_count' => array( 'clicks_count', true ),
		);
	}

	/**
	 * @return array
	 */
	public function get_bulk_actions(): array {
		return array(
			'delete' => 'Delete'
		);
	}

	/**
	 * @return void
	 */
	public function process_bulk_action() {
		global $wpdb;

		$linkpages_table = Helper::get_db_table_name( 'linkpages' );
		$meta_table      = Helper::get_db_table_name( 'meta' );

		if ( 'delete' !== $this->current_action() && ! isset( $_REQUEST['id'] ) ) {
			return;
		}

		if ( is_array( $_REQUEST['id'] ) ) {
			foreach ( $_REQUEST['id'] as $id ) {
				$wpdb->query(
					$wpdb->prepare(
						"DELETE FROM $linkpages_table WHERE id = %d",
						intval( $id )
					)
				);

				$wpdb->query(
					$wpdb->prepare(
						"DELETE FROM $meta_table WHERE linkpage_id = %d",
						intval( $id )
					)
				);
			}
		} else {
			$wpdb->query(
				$wpdb->prepare(
					"DELETE FROM $linkpages_table WHERE id = %d",
					intval( $_REQUEST['id'] )
				)
			);
			$wpdb->query(
				$wpdb->prepare(
					"DELETE FROM $meta_table WHERE linkpage_id = %d",
					intval( $_REQUEST['id'] )
				)
			);
		}
	}

	public function prepare_items() {
		global $wpdb;

		$table_linkpages = Helper::get_db_table_name( 'linkpages' );
		$table_track     = Helper::get_db_table_name( 'track' );
		$per_page        = 20; // constant, how much records will be shown per page
		$columns         = $this->get_columns();
		$hidden          = array();
		$sortable        = $this->get_sortable_columns();
		$total_items     = $wpdb->get_var( "SELECT COUNT(id) FROM $table_linkpages" );

		// here we configure table headers, defined in our methods
		$this->_column_headers = array( $columns, $hidden, $sortable );

		//  process bulk action if any
		$this->process_bulk_action();

		// prepare query params, as usual current page, order by and order direction
		$sort    = Helper::get_sort_params( $sortable, $_REQUEST['order'] ?? '', $_REQUEST['orderby'] ?? '' );
		$order   = $sort['order'];
		$orderby = $sort['orderby'];
		$paged   = isset( $_REQUEST['paged'] ) ? ( $per_page * max( 0, intval( $_REQUEST['paged'] ) - 1 ) ) : 0;

		$this->items = $wpdb->get_results( $wpdb->prepare(
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
                    ORDER BY $orderby $order LIMIT %d OFFSET %d",
			$per_page, $paged ),
			ARRAY_A
		);

		if ( isset( $_GET['author'] ) && $_GET['author'] > 0 ) {
			$author = sanitize_text_field( intval( $_GET['author'] ) );

			$this->items = $wpdb->get_results( $wpdb->prepare(
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
                    WHERE linkpages.author = %d
                    ORDER BY $orderby $order LIMIT %d OFFSET %d",
				$author, $per_page, $paged ),
				ARRAY_A
			);
		}

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