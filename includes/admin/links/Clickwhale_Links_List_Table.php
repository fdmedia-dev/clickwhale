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

	private $users_data;

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

	private function get_users_data( $order, $orderby, $params ) {
		global $wpdb;

		$table_links = Helper::get_db_table_name( 'links' );
		$table_track = Helper::get_db_table_name( 'track' );
		if ( empty( $params ) ) {
			return $wpdb->get_results(
				"SELECT links.id, links.title, links.url, links.slug, links.description, links.categories, links.author, COALESCE(track.clicks,0) AS clicks_count 
                    FROM $table_links links
                    LEFT JOIN (SELECT link_id, COUNT(*) clicks FROM $table_track WHERE event_type='click' GROUP BY link_id ) track ON links.id = track.link_id
				ORDER BY $orderby $order",
				ARRAY_A
			);
		}

		$search   = $params['search'] ?? '';
		$category = $params['category'] ?? '';
		$author   = $params['author'] ?? '';

		$query = "";
		$query .= "SELECT links.id, links.title, links.url, links.slug, links.description, links.categories, links.author, COALESCE(track.clicks,0) AS clicks_count ";
		$query .= "FROM $table_links links ";
		$query .= "LEFT JOIN (SELECT link_id, COUNT(*) clicks FROM $table_track WHERE event_type='click' GROUP BY link_id ) track ON links.id = track.link_id ";
		if ( $search || $category || $author ) {
			$query .= "WHERE ";
		}
		if ( $search ) {
			$query .= "(links.title LIKE '%" . $search . "%' OR links.url LIKE '%" . $search . "%' OR links.slug LIKE '%" . $search . "%' OR links.description LIKE '%" . $search . "%') ";
			if ( $category || $author ) {
				$query .= " AND ";
			}
		}
		if ( $category ) {
			$query .= "(links.categories = '" . $category . "' OR links.categories LIKE '" . $category . ",%' OR links.categories LIKE '%," . $category . ",%' OR links.categories LIKE '%," . $category . "') ";
			if ( $author ) {
				$query .= " AND ";
			}
		}
		if ( $author ) {
			$query .= "links.author = " . intval( $author ) . " ";
		}
		$query .= "ORDER BY $orderby $order";

		return $wpdb->get_results(
			$query,
			ARRAY_A
		);
	}

    public function extra_tablenav( $which ) {

        $categories = Categories_Helper::get_all();
        $categories_count = $categories ? count( $categories ) : 0;

        if ( $categories_count > 0 && $which == "top" ) {
            ?>
            <div class="alignleft actions bulkactions">
                <?php if ( $categories ) { ?>
                    <select name="category" class="clickwhale-filter-categories">
                        <option value=""><?php _e( 'All Categories', CLICKWHALE_NAME ) ?></option>
                        <?php
                        foreach ( $categories as $category ) {
                            $selected = isset( $_GET['category'] ) && $_GET['category'] == $category->id ? ' selected = "selected"' : '';
                            ?>
                            <option value="<?php echo $category->id; ?>" <?php echo $selected; ?>><?php echo $category->title; ?></option>
                            <?php
                        }
                        ?>
                    </select>
                    <input type="submit" class="button" value="<?php _e( 'Filter', CLICKWHALE_NAME ); ?>">
                    <?php
                }
                ?>
            </div>
            <?php
        }
    }

	/**
	 * [REQUIRED] this is a default column renderer
	 *
	 * @param $item - row (key, value array)
	 * @param $column_name - string (key)
	 * @return string
	 */
	public function column_default( $item, $column_name ) {
		return $item[ $column_name ];
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
	public function column_title( $item ) {
		// links going to /admin.php?page=[your_plugin_page][&other_params]
		// notice how we used $_REQUEST['page'], so action will be done on curren page
		// also notice how we use $this->_args['singular'] so in this example it will
		// be something like &link=2
		$title = sprintf(
            '<a href="?page=' . CLICKWHALE_SLUG . '-edit-link&id=%s">%s</a>',
            $item['id'],
			wp_unslash( $item['title'] )
        );
		$actions = array(
			'edit'   => sprintf(
                '<a href="?page=' . CLICKWHALE_SLUG . '-edit-link&id=%s">%s</a>',
                $item['id'],
				__( 'Edit', CLICKWHALE_NAME )
            ),
			'reset'  => sprintf(
                '<a href="?page=%s&action=reset&id=%s">%s</a>',
				sanitize_text_field( $_REQUEST['page'] ),
                $item['id'],
                __( 'Reset Clicks', CLICKWHALE_NAME )
            ),
			'delete' => sprintf(
                '<a href="?page=%s&action=delete&id=%s">%s</a>',
				sanitize_text_field( $_REQUEST['page'] ),
                $item['id'],
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
	public function column_slug( $item ) {
		return '<div class="slug-input--wrap"><input class="slug-input" type="text" value="' . $item['slug'] . '" readonly><a href="#" class="slug-input--btn" data-id="' . $item['id'] . '" title="' . __( 'Copy Link', CLICKWHALE_NAME ) . '"><span class="dashicons dashicons-clipboard"></span></a></div>';
	}

	/**
	 * Target URL
	 *
	 * @param $item - row (key, value array)
	 * @return string
	 */
	public function column_url( $item ) {
		return $item['url'];
	}

	/**
	 * List of categories
	 *
	 * @param $item - row (key, value array)
	 * @return string
	 */
    public function column_categories( $item ) {
        $dash = '&mdash;';

		if ( empty( $item['categories'] ) ) {
			return $dash;
		}

		$current_categories = '';
		$categories = Categories_Helper::get_all( 'title', 'asc', 'ARRAY_A' );

		if ( ! $categories ) {
			return $dash;
		}

		$link_categories = explode( ',', $item['categories'] );
        $last = end( $link_categories );

		foreach ( $link_categories as $v ) {
			$v      = intval( $v );
			$result = array_column( $categories, null, 'id' )[ $v ] ?? false;

			if ( empty( $result ) ) {
				continue;
			}

			$current_categories .= '<a href="' . get_admin_url( get_current_blog_id(), 'admin.php?page=' . CLICKWHALE_SLUG ) . '&category=' . $result['id'] . '">' . wp_unslash( $result['title'] ) . '</a>';
			if ( $v != $last ) {
				//$current_categories .= ', ';
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
	public function column_clicks_count( $item ) {
		return $item['clicks_count'];
	}

	public function column_author( $item ) {
		$user_info = get_userdata( $item['author'] );

		return '<a href="' . get_admin_url( get_current_blog_id(), 'admin.php?page=' . CLICKWHALE_SLUG ) . '&author=' . $user_info->ID . '">' . $user_info->display_name . '</a>';
	}

	/**
	 * [REQUIRED] this is how checkbox column renders
	 *
	 * @param $item - row (key, value array)
	 * @return string
	 */
	public function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="id[]" value="%s" />',
			$item['id']
		);
	}

	/**
	 * [REQUIRED] This method return columns to display in table
	 * you can skip columns that you do not want to show
	 * like content, or description
	 *
	 * @return array
	 */
	public function get_columns() {
		$tracking_options = get_option( 'clickwhale_tracking_options' );

		$columns = array(
			'cb'           => '<input type="checkbox" />',             //Render checkbox instead of text
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
	public function get_sortable_columns() {
		return array(
			'title'        => array( 'title', true ),
			'clicks_count' => array( 'clicks_count', true ),
		);
	}

	/**
	 * Return array of bulk actions if has any
	 *
	 * @return array
	 */
	public function get_bulk_actions(): array {
		return array(
			'edit'   => 'Edit',
			'reset'  => 'Reset Clicks',
			'delete' => 'Delete',
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

		if ( ! $action || ! isset( $_REQUEST['id'] ) ) {
			return;
		}

		if ( is_array( $_REQUEST['id'] ) ) {
			$ids = $_REQUEST['id'];
		} else {
			$ids[] = $_REQUEST['id'];
		}

		switch ( $action ) {
			case 'edit':
				$data = array();

				if ( isset( $_GET['link_category'] ) ) {
					$data['categories'] = implode( ',', $_GET['link_category'] );
				}
				if ( isset( $_GET['link_author'] ) && $_GET['link_author'] !== '-1' ) {
					$data['author'] = esc_attr( $_GET['link_author'] );
				}
				if ( isset( $_GET['redirection_status'] ) && $_GET['redirection_status'] !== '-1' ) {
					$data['redirection'] = esc_attr( intval( $_GET['redirection_status'] ) );
				}
				if ( isset( $_GET['nofollow_status'] ) && $_GET['nofollow_status'] !== '-1' ) {
					$data['nofollow'] = esc_attr( intval( $_GET['nofollow_status'] ) );
				}
				if ( isset( $_GET['sponsored_status'] ) && $_GET['sponsored_status'] !== '-1' ) {
					$data['sponsored'] = esc_attr( intval( $_GET['sponsored_status'] ) );
				}

				if ( $data ) {
					foreach ( $ids as $id ) {
						$wpdb->update(
							Helper::get_db_table_name( 'links' ),
							$data,
							array( 'id' => $id )
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
					echo( "<script>location.href = '" . $url . "'</script>" );
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
		global $wpdb;

		$per_page     = 20; // constant, how much records will be shown per page
		$current_page = $this->get_pagenum();
		$columns      = $this->get_columns();
		$hidden       = array();
		$sortable     = $this->get_sortable_columns();

		// Configure table headers, defined in our methods
		$this->_column_headers = array( $columns, $hidden, $sortable );

		// Process bulk action if any
		$this->process_bulk_action();

		// Prepare query params, as usual current page, order by and order direction
		$sort    = Helper::get_sort_params(
			$sortable,
			$_REQUEST['order'] ?? '',
			$_REQUEST['orderby'] ?? 'id'
		);
		$order   = $sort['order'];
		$orderby = $sort['orderby'];
		$paged   = isset( $_REQUEST['paged'] ) ? ( $per_page * max( 0, intval( $_REQUEST['paged'] ) - 1 ) ) : 0;

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

		$this->users_data = $this->get_users_data(
			$order,
			$orderby,
			$params
		);
		$total_items      = count( $this->users_data );
		$this->users_data = array_slice( $this->users_data, ( ( $current_page - 1 ) * $per_page ), $per_page );
		$this->items      = $this->users_data;

		// [REQUIRED] Configure pagination
		$this->set_pagination_args( array(
			'total_items' => $total_items,                  // total items defined above
			'per_page'    => $per_page,                        // per page constant defined at top of method
			'total_pages' => ceil( $total_items / $per_page ) // calculate pages count
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
                if ( ( isset( $_REQUEST['action'] ) && $_REQUEST['action'] === 'edit' ) && ! empty( $_REQUEST['id'] ) ) {
                    $quick_edit = new Clickwhale_Links_Bulk_Edit( $_REQUEST['id'], $this->get_column_count() );
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
        <div class="tablenav <?php echo esc_attr( $which ); ?>">
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