<?php
namespace clickwhale\includes\admin\tracking_codes;

use clickwhale\includes\helpers\{Helper, Tracking_Codes_Helper};
use WP_List_Table;

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
				'plural'   => 'tracking-codes',
			)
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
	public function column_default( $item, $column_name ) {
		return $item[ $column_name ];
	}

	/**
	 * @param $item - row (key, value array)
	 *
	 * @return string
	 */
	public function column_title( $item ): string {
		$title = sprintf(
            '<a href="?page=' . CLICKWHALE_SLUG . '-edit-tracking-code&id=%d">%s</a>',
            $item['id'],
			wp_unslash( $item['title'] )
        );
		$actions = array(
			'edit'   => sprintf(
                '<a href="?page=' . CLICKWHALE_SLUG . '-edit-tracking-code&id=%d">%s</a>',
                $item['id'],
				__( 'Edit', CLICKWHALE_NAME )
            ),
			'delete' => sprintf(
                '<a href="?page=%s&action=delete&id=%d">%s</a>',
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

	public function column_is_active( $item ): string {
		$checked  = checked( intval( $item['is_active'] ), 1, false );
		$disabled = ! $checked && Tracking_Codes_Helper::is_active_limit() ? ' disabled="disabled"' : '';
		$output   = '';

		$output .= '<label class="clickwhale-checkbox--toggle">';
		$output .= sprintf(
			'<input type="checkbox" name="is_active" class="clickwhale_tc_active_toggle" value="1" data-id="%d" ' . $checked . $disabled . ' />',
			$item['id']
		);
		$output .= '<span class="clickwhale-checkbox--toggle-slider"></span>';
		$output .= '</label>';

		return $output;
	}

	/**
	 * @param $item - row (key, value array)
	 *
	 * @return string
	 */
	public function column_description( $item ): string {
		return wp_unslash( $item['description'] );
	}

	/**
	 * @param $item - row (key, value array)
	 *
	 * @return string
	 */
	public function column_position( $item ): string {
		$position     = '';
		$positionCode = maybe_unserialize( $item['position'] );

		if ( ! isset( $positionCode['code'] ) ) {
			return $position;
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
		}

		return $position;
	}

	/**
	 * @param $item
	 *
	 * @return string
	 * @since 1.2.0
	 *
	 */
	public function column_author( $item ): string {
		$user_info = get_userdata( $item['author'] );

		return '<a href="' . get_admin_url( get_current_blog_id(),
				'admin.php?page=' . CLICKWHALE_SLUG ) . '&author=' . $user_info->ID . '">' . $user_info->display_name . '</a>';
	}

	/**
	 * @param $item - row (key, value array)
	 *
	 * @return string
	 */
	public function column_created_at( $item ): string {
		return $item['created_at'];
	}

	/**
	 * @param $item - row (key, value array)
	 *
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
	public function get_columns() {
		return array(
			'cb'          => '<input type="checkbox" />',
			'is_active'   => __( 'Active',      CLICKWHALE_NAME ),
			'title'       => __( 'Tilte',       CLICKWHALE_NAME ),
			'description' => __( 'Description', CLICKWHALE_NAME ),
			'position'    => __( 'Position',    CLICKWHALE_NAME ),
			'author'      => __( 'Author',      CLICKWHALE_NAME ),
			'created_at'  => __( 'Created',     CLICKWHALE_NAME ),
		);
	}

	/**
	 * @return array
	 */
	public function get_sortable_columns() {
		return array(
			'title' => array( 'title', true ),
		);
	}

	/**
	 * @return array
	 */
	public function get_bulk_actions() {
		return array(
			'delete' => 'Delete'
		);
	}

	public function process_bulk_action() {
		global $wpdb;

		$table = Helper::get_db_table_name( 'tracking_codes' );

		if ( 'delete' === $this->current_action() && isset( $_REQUEST['id'] ) ) {
			if ( is_array( $_REQUEST['id'] ) ) {
				foreach ( $_REQUEST['id'] as $id ) {
					$wpdb->query( $wpdb->prepare( "DELETE FROM $table WHERE id IN(%d)",
						intval( $id ) ) );
				}
			} else {
				$wpdb->query( $wpdb->prepare( "DELETE FROM $table WHERE id IN(%d)",
					intval( $_REQUEST['id'] ) ) );
			}
		}
	}

	public function prepare_items() {
		global $wpdb;

		$table                 = Helper::get_db_table_name( 'tracking_codes' );
		$per_page              = 20;
		$orderby               = 'id';
		$order                 = 'desc';
		$columns               = $this->get_columns();
		$hidden                = [];
		$sortable              = $this->get_sortable_columns();
		$total_items           = $wpdb->get_var( "SELECT COUNT(id) FROM $table" );
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
			"SELECT * FROM $table ORDER BY $orderby $order LIMIT %d OFFSET %d",
			$per_page, $paged ),
			ARRAY_A
		);

		if ( isset( $_GET['author'] ) && $_GET['author'] > 0 ) {
			$author = sanitize_text_field( intval( $_GET['author'] ) );

			$this->items = $wpdb->get_results( $wpdb->prepare(
				"SELECT * FROM $table ORDER BY $orderby $order LIMIT %d OFFSET %d",
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
		_e( 'No Tracking Codes Found.', CLICKWHALE_NAME );
	}
}