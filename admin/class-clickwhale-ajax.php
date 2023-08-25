<?php

/**
 * The settings of the plugin.
 *
 * @link       #
 * @since      1.0.0
 *
 * @package    Clickwhale
 * @subpackage Clickwhale/admin
 */

class Clickwhale_Ajax {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $plugin_name The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $version The current version of this plugin.
	 */
	private $version;

	private static $instance;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param string $plugin_name The name of this plugin.
	 * @param string $version The version of this plugin.
	 *
	 * @since    1.0.0
	 */
	public function __construct( string $plugin_name, string $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;
		$this->migration   = new Clickwhale_Migration();

	}

	/**
	 * @param $plugin_name
	 * @param $version
	 *
	 * @return Clickwhale_Ajax
	 */
	public static function getInstance( $plugin_name, $version ): Clickwhale_Ajax {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self( $plugin_name, $version );
		}

		return self::$instance;
	}

	public function migration_notice_hide() {
		$type   = isset( $_POST['type'] ) ? sanitize_text_field( $_POST['type'] ) : '';
		$plugin = isset( $_POST['plugin'] ) ? sanitize_text_field( $_POST['plugin'] ) : '';

		check_ajax_referer( 'clickwhale_' . $plugin . '_admin_nonce', 'security' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die();
		}

		if ( $type === 'migrate' ) {
			$options_migrate            = get_option( 'clickwhale_hide_notice_migrate' );
			$options_migrate[ $plugin ] = true;
			update_option( 'clickwhale_hide_notice_migrate', $options_migrate );
		} elseif ( $type === 'deactive' ) {
			$options_deactive            = get_option( 'clickwhale_hide_notice_deactive' );
			$options_deactive[ $plugin ] = true;
			update_option( 'clickwhale_hide_notice_deactive', $options_deactive );
		}
		wp_die();
	}

	public function migration_deactive() {
		$plugin = isset( $_POST['plugin'] ) ? sanitize_text_field( $_POST['plugin'] ) : '';
		$target = isset( $_POST['target'] ) ? sanitize_text_field( $_POST['target'] ) : '';

		check_ajax_referer( 'clickwhale_' . $plugin . '_admin_nonce', 'security' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die();
		}

		$deactivate = deactivate_plugins( $target );

		wp_send_json_success( $deactivate );

		wp_die();
	}

	public function save_migration_option() {
		check_ajax_referer( 'migration_to_clickwhale', 'security' );

		if ( isset( $_POST['name'] ) && isset( $_POST['value'] ) ) {
			$options            = get_option( 'clickwhale_tools_migration_options' );
			$option             = sanitize_text_field( $_POST['name'] );
			$value              = boolval( sanitize_text_field( $_POST['value'] ) );
			$options[ $option ] = $value;

			update_option( 'clickwhale_tools_migration_options', $options );
			wp_send_json_success();
			wp_die();
		} else {
			return false;
		}
	}


	public function migration_to_clickwhale() {
		check_ajax_referer( 'migration_to_clickwhale', 'security' );

		$available = $this->migration->available_migrations();
		$options   = get_option( 'clickwhale_tools_migration_options' );
		$migrant   = isset( $_POST['migrant'] ) ? sanitize_text_field( $_POST['migrant'] ) : '';
		$item      = $available[ $migrant ];

		if ( $item ) {
			if ( $this->migration->check_active( $item['path'] ) ) {
				$result          = [];
				$result['title'] = $item['name'];

				if ( isset( $options[ $item['slug'] . '_categories' ] )
				     && $options[ $item['slug'] . '_categories' ] !== false
				     || isset( $options[ $item['slug'] . '_links' ] )
				        && $options[ $item['slug'] . '_links' ] !== false
				) {
					$migrator       = new $item['class']();
					$result['data'] = $migrator->run_migration(
						$options[ $item['slug'] . '_categories' ],
						$options[ $item['slug'] . '_links' ]
					);
				} else {
					$result['data'] = __( 'Nothing to migrate', $this->plugin_name );
				}
			}

			$options_migrate             = get_option( 'clickwhale_hide_notice_migrate' );
			$options_migrate[ $migrant ] = true;
			update_option( 'clickwhale_hide_notice_migrate', $options_migrate );

			$options_deactive             = get_option( 'clickwhale_hide_notice_deactive' );
			$options_deactive[ $migrant ] = false;
			update_option( 'clickwhale_hide_notice_deactive', $options_deactive );

			wp_send_json_success( $result );

		} else {
			wp_send_json_error();
		}
		wp_die();
	}

	public function migration_reset() {
		check_ajax_referer( 'migration_reset', 'security' );

		foreach ( $this->migration->available_migrations() as $item ) {

			$migration_options[ $item['slug'] . '_categories' ]          = (bool) $item['data']['categories'];
			$migration_options[ $item['slug'] . '_links' ]               = (bool) $item['data']['links'];
			$notice_migrate_options[ $item['slug'] ]                     = false;
			$notice_deactive_options[ $item['slug'] ]                    = true;
			$last_migration_options[ $item['slug'] . '_last_migration' ] = '';
		}

		update_option( 'clickwhale_tools_migration_options', $migration_options );
		update_option( 'clickwhale_tools_last_migration_options', $last_migration_options );
		update_option( 'clickwhale_hide_notice_migrate', $notice_migrate_options );
		update_option( 'clickwhale_hide_notice_deactive', $notice_deactive_options );

		$result = __( 'Successfully deleted! Page will reload...', $this->plugin_name );

		wp_send_json_success( $result );

		wp_die();
	}

	public function clickwhale_reset() {
		check_ajax_referer( 'clickwhale_reset', 'security' );

		global $wpdb;
		$result = [];

		if ( ! isset( $_POST['reset'] ) ) {
			wp_send_json_error();
			wp_die();
		}

		switch ( $_POST['reset'] ) {
			case 'stats':
				//result text
				$text = __( 'All statistic has been reset', $this->plugin_name );

				//drop tables
				$result['status'] = $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}clickwhale_track, {$wpdb->prefix}clickwhale_visitors" );

				break;
			case 'db':
				//result text
				$text = __( 'All plugin tables has been reset', $this->plugin_name );

				//drop tables
				$result['status'] = $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}clickwhale_links, {$wpdb->prefix}clickwhale_categories, {$wpdb->prefix}clickwhale_linkpages, {$wpdb->prefix}clickwhale_meta, {$wpdb->prefix}clickwhale_track, {$wpdb->prefix}clickwhale_visitors" );

				break;
			case 'settings':
				//result text
				$text = __( 'All plugin settings has been restored', $this->plugin_name );

				// delete all options
				delete_option( 'clickwhale_general_options' );
				delete_option( 'clickwhale_tracking_options' );
				delete_option( 'clickwhale_other_options' );

				// init settings class and set defaults
				$settings = Clickwhale_Admin_Settings::getInstance();
				$settings->add_default_options();

				$result['status'] = true;

				break;
		}

		$result['text'] = $text;

		activate_clickwhale();

		wp_send_json_success( $result );

		wp_die();
	}

	public function check_slug() {
		check_ajax_referer( 'check_slug', 'security' );

		if ( empty( $_POST['slug'] ) ) {
			wp_send_json_error();
			wp_die();
		}

		$result = null;

		if ( $_POST['type'] === 'linkpage' ) {
			$result = Clickwhale_Linkpage_Edit::check_slug( $_POST['slug'] );
		} else {
			$result = Clickwhale_Link_Edit::check_slug( $_POST['slug'], $_POST['id'] );
		}

		wp_send_json_success( $result );
		wp_die();
	}

	/**
	 * @return void
	 * @since 1.1.0
	 */
	public function get_posts_by_post_type() {
		check_ajax_referer( 'get_posts_by_post_type', 'security' );

		if ( ! isset( $_POST['post_type'] ) || ! $_POST['post_type'] ) {
			wp_send_json_error( 'Post Type Error!' );
			wp_die();
		}

		$result = [];
		$args   = array(
			'numberposts' => - 1,
			'post_type'   => $_POST['post_type'],
			'orderby'     => 'title',
			'order'       => 'ASC',
			'post_status' => 'publish'
		);
		$posts  = get_posts( $args );

		if ( $posts ) {
			foreach ( $posts as $post ) {
				$result['posts'][] = array(
					'id'    => $post->ID,
					'title' => $post->post_title,
					'url'   => get_permalink( $post->ID ),
				);
			}

		} else {
			$result['posts'] = false;
		}

		wp_send_json_success( $result );
		wp_die();
	}

	/**
	 * @return void
	 * @since 1.1.0
	 */
	public function get_cw_links() {
		check_ajax_referer( 'get_cw_links', 'security' );

		global $wpdb;

		$result          = [];
		$result['links'] = $wpdb->get_results(
			"SELECT id,title,url from {$wpdb->prefix}clickwhale_links",
			ARRAY_A
		);

		if ( ! $result['links'] ) {
			wp_send_json_error( 'ClickWhale Links Not Found!' );
			wp_die();
		}

		wp_send_json_success( $result );
		wp_die();
	}

	/**
	 * @return void
	 * @since 1.1.0
	 */
	public function track_custom_link() {
		check_ajax_referer( 'track_custom_link', 'security' );

		if ( ! isset( $_POST['id'] ) || ! $_POST['id'] ) {
			wp_send_json_error( 'Track Error!' );

			wp_die();
		}

		// Track click on link
		$track = new Clickwhale_Click_Track( $_POST['id'], true );

		wp_send_json_success();

		wp_die();
	}

	/**
	 * @return void
	 * @since 1.2.0
	 */
	public function tracking_code_toggle_active() {
		global $wpdb;

		check_ajax_referer( 'clickwhale_toggle_tracking_code', 'security' );

		$result               = [];
		$tracking_codes_table = $wpdb->prefix . 'clickwhale_tracking_codes';
		$data                 = array( 'is_active' => sanitize_text_field( $_POST['status'] ) );
		$where                = array( 'id' => intval( sanitize_text_field( $_POST['id'] ) ) );

		$wpdb->update( $tracking_codes_table, $data, $where );

		$result['action_disable_all'] = ClickwhaleTrackingCodesHelper::is_limit();

		wp_send_json_success( $result );

		wp_die();
	}

	/**
	 * @return void
	 * @since 1.3.0
	 */
	public function add_link_to_linkpage() {
		check_ajax_referer( 'clickwhale_add_link_to_linkpage', 'security' );
		// activate item
		$template           = new LinkpageContentTemplates();
		$result['template'] = $template->get_template( $_POST['type'], false, false );

		wp_send_json_success( $result );

		wp_die();
	}

	public function upload_csv() {
		check_ajax_referer( 'upload_csv', 'security' );

		if ( ! function_exists( 'wp_handle_upload' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/file.php' );
		}

		$file = $_FILES['file'];

		// Check file type
		if ( $file['type'] !== 'text/csv' ) {
			wp_send_json_error( 'Wrong file type!' );
			wp_die();
		}

		$col_delimiter   = ",";
		$delimiters      = [ ";", "\t", "|" ];
		$html            = '';
		$default_columns = ClickwhaleHelper::get_import_default_columns();

		$file_data = fopen( $file['tmp_name'], 'r' );

		if ( $file_data === false ) {
			wp_send_json_error( 'Error opening file!' );
			wp_die();
		}

		// find col head and delimiter
		// by default delimiter is comma ','
		$headings = fgetcsv( $file_data, 4096, $col_delimiter );

		// if comma is not a delimiter than $columns returns
		// array{ [0]=> 'col1?col2?col3...'}
		// '?' is unknown delimiter
		if ( count( $headings ) <= 1 ) {
			foreach ( $delimiters as $delimiter ) {
				// try to find delimiter
				$headings_tmp = explode( $delimiter, $headings[0] );
				// if delimiter is correct explode() will return array with more than 1 item
				if ( $headings_tmp > 1 ) {
					$col_delimiter = $delimiter;
					// stop search
					break;
				}
			}

			$headings = $headings_tmp;
		}

		$first_line = fgetcsv( $file_data, 4096, $col_delimiter );

		// clean headings
		foreach ( $headings as $k => $v ) {
			$v = preg_replace( '/[\x00-\x1F\x80-\xFF]/', '', $v );
			$v = strtolower( $v );

			$headings[ $k ] = $v;
		}

		$html .= '<table class="wp-list-table widefat striped table-view-list"><thead><tr>';
		$html .= '<th>' . __( 'Column name', CLICKWHALE_NAME ) . '</th>';
		$html .= '<th>' . __( 'Map to field', CLICKWHALE_NAME ) . '</th>';
		$html .= '</tr></thead><tbody>';

		$i = 0;
		foreach ( $headings as $heading ) {
			$select = '<select>';
			$select .= '<option value="0">' . __( 'Do not import', CLICKWHALE_NAME ) . '</option>';
			$select .= '<option value="" disabled>---------------------</option>';
			foreach ( $default_columns as $option ) {
				$selected = in_array( $option, $headings ) && $option === $headings[ $i ]
					? 'selected="selected"'
					: '';
				$select   .= '<option value="' . strtolower( $option ) . '" ' . $selected . '>' . ucfirst( $option ) . '</option>';
			}
			$select .= '</select>';

			$example = __( 'Example:', CLICKWHALE_NAME );
			$html    .= '<tr><td><strong>' . $heading . '</strong><br><small>' . $example . ' ' . $first_line[ $i ] . '</small></td>';
			$html    .= '<td>' . $select . '</td></tr>';

			$i ++;
		}
		$html .= '</tbody></table>';

		$result              = [];
		$result['delimiter'] = $col_delimiter;
		$result['table']     = $html;

		wp_send_json_success( $result );

		wp_die();
	}

	public function map_csv() {
		check_ajax_referer( 'map_csv', 'security' );

		if ( ! $_FILES['file'] ) {
			wp_send_json_error( 'Missing file!' );
			wp_die();
		}

		if ( $_FILES['file']['type'] !== 'text/csv' ) {
			wp_send_json_error( 'Wrong file type!' );
			wp_die();
		}

		$html             = '';
		$delimiter        = $_POST['delimiter'];
		$redirections     = array(
			301 => '301 redirect: Moved permanently',
			302 => '302 redirect: Found / Moved temporarily',
			303 => '303 redirect: See Other',
			307 => '307 redirect: Temporarily Redirect',
			308 => '308 redirect: Permanent Redirect',
		);
		$default_columns  = ClickwhaleHelper::get_import_default_columns();
		$mapped_columns   = $_POST['mapped'] ? explode( ',', $_POST['mapped'] ) : [];
		$excluded_columns = $_POST['excluded'] ? explode( ',', $_POST['excluded'] ) : [];
		$filtered         = [];

		$file_data = fopen( $_FILES['file']['tmp_name'], 'r' );

		if ( $file_data === false ) {
			wp_send_json_error( 'Error opening file!' );
			wp_die();
		}

		// get headings
		$file_headings = fgetcsv( $file_data, 4096, $delimiter );

		// get array of unique values from mapped and default columns
		foreach ( $default_columns as $default_column ) {
			if ( ! in_array( $default_column, $mapped_columns ) ) {
				$mapped_columns[] = $default_column;
			}
		}

		// filter csv row and exclude not mapped columns
		$headings_num = count( $file_headings );
		while ( ( $data = fgetcsv( $file_data, 4096, $delimiter ) ) !== false ) {

			$row = [];
			for ( $c = 0; $c < $headings_num; $c ++ ) {
				if ( in_array( $c, $excluded_columns ) ) {
					continue;
				} else {
					$row[] = $data[ $c ];
				}
			}
			$filtered[] = $row;
		}
		fclose( $file_data );

		// start html
		$html .= '<table class="wp-list-table widefat striped table-view-list"><thead><tr>';
		foreach ( $mapped_columns as $heading ) {
			$html .= '<th>' . $heading . '</th>';
		}
		$html .= '<th></th>';
		$html .= '</tr></thead><tbody>';

		// add rows
		$mapped_num = count( $mapped_columns );
		foreach ( $filtered as $data ) {
			$html .= '<tr>';
			for ( $c = 0; $c < $mapped_num; $c ++ ) {

				$value = $data[ $c ] ?? '';

				switch ( $mapped_columns[ $c ] ) {
					case 'title':
						$input = '<input name="' . $mapped_columns[ $c ] . '" type="text" value="' . $value . '" required>';
						break;
					case 'url':
						$input = '<input name="' . $mapped_columns[ $c ] . '" type="url" value="' . $value . '" required>';
						break;
					case 'slug':
						$value = trim( $value, '/' );
						$input = '<input name="' . $mapped_columns[ $c ] . '" type="text" value="' . $value . '" required>';
						break;
					case 'redirection':
						$options = '';
						$value   = $data[ $c ] ?? 301;
						foreach ( $redirections as $k => $v ) {
							$selected = selected( $k, $value, false );
							$options  .= '<option value="' . $k . '"' . $selected . '>' . $v . '</option>';
						}
						$input = '<select name="' . $mapped_columns[ $c ] . '">' . $options . '</select>';
						break;
					case 'nofollow':
					case 'sponsored':
						$value   = $data[ $c ] ?? 0;
						$checked = checked( '1', $value, false );
						$input   = '<label><input name="' . $mapped_columns[ $c ] . '" type="checkbox" value="1"' . $checked . '>' . $mapped_columns[ $c ] . '</label>';
						break;
					default:
						$input = '<input type="text" value="">';
				}

				$html .= '<td class="for_import ' . $mapped_columns[ $c ] . '">' . $input . '</td>';
			}
			$html .= '<td><button type="button"><svg class="feather"><use href="' . CLICKWHALE_ADMIN_IMAGES_DIR . '/feather-sprite.svg#trash-2"></use></svg></button></td>';
			$html .= '</tr>';
		}
		$html .= '</tbody></table>';

		wp_send_json_success( $html );

		wp_die();
	}

	public function import_csv() {
		check_ajax_referer( 'import_csv', 'security' );

		global $wpdb;

		$data            = $_POST['data'];
		$links_table     = $wpdb->prefix . 'clickwhale_links';
		$result          = [];
		$default_columns = array( 'title', 'slug', 'url', 'redirection', 'nofollow', 'sponsored' );

		foreach ( $data as $k => $v ) {
			$v['title']       = sanitize_text_field( $v['title'] );
			$v['slug']        = sanitize_title( $v['slug'] );
			$v['url']         = esc_url( $v['url'] );
			$v['description'] = '';
			$v['author']      = get_current_user_id();
			$v['created_at']  = date( 'Y-m-d H:i:s' );
			$v['updated_at']  = date( 'Y-m-d H:i:s' );

			if ( isset( $v['undefined'] ) ) {
				unset( $v['undefined'] );
			}

			$insert = $wpdb->insert(
				$links_table,
				$v
			);

			if ( $insert ) {
				$result[] = __(
					'Link <strong>&quot;' . $v['title'] . '&quot;</strong> successfully imported!',
					CLICKWHALE_NAME
				);
			} else {
				$result[] = __(
					'<strong>Error!</strong> Link <strong>&quot;' . $v['title'] . '&quot;</strong> not imported!',
					CLICKWHALE_NAME
				);
			}
		}

		wp_send_json_success( $result );

		wp_die();
	}

	public function export_csv() {
		check_ajax_referer( 'export_csv', 'security' );

		global $wpdb;

		// disable caching
		$now  = date( "D, d M Y H:i:s" );
		$date = date( "Y-m-d" );

		header( "Expires: Tue, 03 Jul 2001 06:00:00 GMT" );
		header( "Cache-Control: max-age=0, no-cache, must-revalidate, proxy-revalidate" );
		header( "Last-Modified: {$now} GMT" );

		// force download
		header( "Content-Type: application/force-download" );
		header( "Content-Type: application/octet-stream" );
		header( "Content-Type: application/download" );

		// disposition / encoding on response body
		header( "Content-Disposition: attachment;filename=clickwhale-links-export-{$date}.csv" );
		header( "Content-Transfer-Encoding: binary" );

		$headers    = $_POST['columns'];
		$categories = '';
		$merged     = [];

		if ( isset( $_POST['categories'] ) ) {
			$categories = " WHERE categories LIKE '%" . implode( "%' OR categories LIKE '%",
					$_POST['categories'] ) . "%'";
		}

		$query = "SELECT " . implode( ',', $headers ) . " FROM {$wpdb->prefix}clickwhale_links" . $categories;
		$rows  = $wpdb->get_results( $query, ARRAY_A );

		$merged[] = $headers;
		$merged   = array_merge( $merged, $rows );

		if ( count( $merged ) == 0 ) {
			wp_send_json_error( __( 'Nothing to export', CLICKWHALE_NAME ) );
			wp_die();
		}

		//ob_start();
		$df = fopen( "php://output", 'w' );

		foreach ( $merged as $row ) {
			fputcsv( $df, $row );
		}

		fclose( $df );
		$result['file']     = ob_get_clean();
		$result['filename'] = "clickwhale-links-export-{$date}.csv";

		wp_send_json_success( $result );
		wp_die();
	}

}