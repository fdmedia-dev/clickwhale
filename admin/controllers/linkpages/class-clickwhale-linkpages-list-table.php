<?php

/**
 * Custom_Table_Example_List_Table class that will display our custom table
 * records in nice table
 */
class ClickwhaleLinkpagesListTable extends WP_List_Table {

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
	 *
	 * @return HTML
	 */
	function column_default( $item, $column_name ) {
		return $item[ $column_name ];
	}

	/**
	 * @param $item - row (key, value array)
	 *
	 * @return HTML
	 */

	/**
	 * @param $item - row (key, value array)
	 *
	 * @return string
	 */
	public function column_title( $item ) {
		$title   = sprintf( '<a href="?page=clickwhale-edit-linkpage&id=%s">%s</a>', $item['id'],
			wp_unslash( $item['title'] ) );
		$actions = array(
			'edit'   => sprintf( '<a href="?page=clickwhale-edit-linkpage&id=%s">%s</a>', $item['id'],
				__( 'Edit', 'clickwhale' ) ),
			'view'   => '<a href="' . get_bloginfo( 'url' ) . '/' . $item['slug'] . '/" target="_blank">View</a>',
			'delete' => sprintf( '<a href="?page=%s&action=delete&id=%s">%s</a>',
				sanitize_text_field( $_REQUEST['page'] ), $item['id'], __( 'Delete', 'clickwhale' ) ),
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
	public function column_slug( $item ) {
		return '<div class="slug-input--wrap"><input class="slug-input" type="text" value="' . $item['slug'] . '" readonly><a href="#" class="slug-input--btn" data-id="' . $item['id'] . '" title="' . __( 'Copy Link',
				'clickwhale' ) . '"><span class="dashicons dashicons-clipboard"></span></a></div>';
	}

	/**
	 * @param $item - row (key, value array)
	 * @since 1.1.0
     *
	 * @return string
	 */
	public function column_views_count( $item ) {
		return $item['views_count'];
	}

	/**
	 * @param $item - row (key, value array)
	 * @since 1.1.0
     *
	 * @return string
	 */
	public function column_clicks_count( $item ) {
		return $item['clicks_count'];
	}

	public function column_author( $item ) {
		$user_info = get_userdata( $item['author'] );

		return '<a href="' . get_admin_url( get_current_blog_id(),
				'admin.php?page=clickwhale-linkpages' ) . '&author=' . $user_info->ID . '">' . $user_info->display_name . '</a>';
	}

	/**
	 * @param $item - row (key, value array)
	 *
	 * @return string
	 */
	public function column_links( $item ) {
		$links = maybe_unserialize( $item['links'] );
		$count = $links ? count( $links ) : 0;

		return $count . ' / ' . ClickwhaleLinkpagesHelper::get_links_limit();
	}

	/**
	 * @param $item - row (key, value array)
	 *
	 * @return string
	 */
	public function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="id[]" value="%s" />',
			$item['id']
		);
	}

	/**
	 * @return array
	 */
	public function get_columns() {
		return array(
			'cb'           => '<input type="checkbox" />',
			'title'        => __( 'Title', 'clickwhale' ),
			'slug'         => __( 'Link', 'clickwhale' ),
			'links'        => __( 'Links', 'clickwhale' ),
			'views_count'  => __( 'Views', 'clickwhale' ),
			'clicks_count' => __( 'Clicks', 'clickwhale' ),
			'author'       => __( 'Author', 'clickwhale' ),
		);
	}

	/**
	 * @return array
	 */
	function get_sortable_columns() {
		return array(
			'title'        => array( 'title', true ),
			'views_count'  => array( 'views_count', true ),
			'clicks_count' => array( 'clicks_count', true ),
		);
	}

	/**
	 * @return array
	 */
	function get_bulk_actions() {
		return array(
			'delete' => 'Delete'
		);
	}

	function process_bulk_action() {
		global $wpdb;

		if ( 'delete' === $this->current_action() && isset( $_REQUEST['id'] ) ) {
			if ( is_array( $_REQUEST['id'] ) ) {
				foreach ( $_REQUEST['id'] as $id ) {
					$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}clickwhale_linkpages WHERE id IN(%d)",
						intval( $id ) ) );
				}
			} else {
				$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}clickwhale_linkpages WHERE id IN(%d)",
					intval( $_REQUEST['id'] ) ) );
			}
		}
	}

	function prepare_items() {
		global $wpdb;

		$per_page              = 20;
		$orderby               = 'id';
		$order                 = 'desc';
		$columns               = $this->get_columns();
		$hidden                = [];
		$sortable              = $this->get_sortable_columns();
		$total_items           = $wpdb->get_var( "SELECT COUNT(id) FROM {$wpdb->prefix}clickwhale_linkpages" );
		$this->_column_headers = array( $columns, $hidden, $sortable );

		$this->process_bulk_action();

		if ( isset( $_REQUEST['orderby'] ) ) {
			$orderByArg = htmlspecialchars( $_REQUEST['orderby'], ENT_QUOTES );
			$orderby    = in_array( $orderByArg, array_keys( $this->get_sortable_columns() ) ) ? $orderByArg : $orderby;
		}
		if ( isset( $_REQUEST['order'] ) ) {
			$orderArg = htmlspecialchars( $_REQUEST['order'], ENT_QUOTES );
			$order    = in_array( $orderArg, array( 'asc', 'desc' ) ) ? $orderArg : $order;
		}

		$paged = isset( $_REQUEST['paged'] ) ? ( $per_page * max( 0, intval( $_REQUEST['paged'] ) - 1 ) ) : 0;

		// [REQUIRED] define $items array
		// notice that last argument is ARRAY_A, so we will retrieve array

		$this->items = $wpdb->get_results( $wpdb->prepare(
			"SELECT *, COALESCE(v_track.views,0) AS views_count, COALESCE(c_track.clicks,0) AS clicks_count
                    FROM {$wpdb->prefix}clickwhale_linkpages linkpages
                    LEFT JOIN (
                        SELECT linkpage_id, COUNT(*) views 
                        FROM {$wpdb->prefix}clickwhale_track 
                        WHERE event_type='view' 
                        GROUP BY linkpage_id
                        ) v_track ON linkpages.id = v_track.linkpage_id
                    LEFT JOIN (
                        SELECT linkpage_id, COUNT(*) clicks 
                        FROM {$wpdb->prefix}clickwhale_track 
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
                    FROM {$wpdb->prefix}clickwhale_linkpages linkpages
                    LEFT JOIN (
                        SELECT linkpage_id, COUNT(*) views 
                        FROM {$wpdb->prefix}clickwhale_track 
                        WHERE event_type='view' 
                        GROUP BY linkpage_id
                        ) v_track ON linkpages.id = v_track.linkpage_id
                    LEFT JOIN (
                        SELECT linkpage_id, COUNT(*) clicks 
                        FROM {$wpdb->prefix}clickwhale_track 
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
		_e( 'No Link Pages Found.', 'clickwhale' );
	}
}