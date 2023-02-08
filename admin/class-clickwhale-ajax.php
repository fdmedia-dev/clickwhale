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
		} else if ( $type === 'deactive' ) {
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

		if ( ( isset( $_POST['slug'] ) && $_POST['slug'] !== '' ) ) {
			if ( $_POST['type'] === 'linkpage' ) {
				$result = Clickwhale_Linkpage_Edit::check_slug( sanitize_title( $_POST['slug'] ) );
			} else {
				$result = Clickwhale_Link_Edit::check_slug( sanitize_title( $_POST['slug'] ), $_POST['id'] );
			}
		} else {
			$result = 'error';
		}

		wp_send_json_success( $result );

		wp_die();
	}

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

}