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
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;
		$this->migration   = new Clickwhale_Migration();

	}

	/**
	 * @return Clickwhale_Ajax
	 */
	public static function getInstance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
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
		$result    = [];

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

		}

		wp_send_json_success( $result );

		wp_die();
	}

	public function migration_reset() {
		check_ajax_referer( 'migration_reset', 'security' );

		foreach ( $this->migration->available_migrations() as $item ) {

			$migration_options[ $item['slug'] . '_categories' ] = $item['data']['categories'] ? true : false;
			$migration_options[ $item['slug'] . '_links' ]      = $item['data']['links'] ? true : false;

			$last_migration_options[ $item['slug'] . '_last_migration' ] = '';
		}

		update_option( 'clickwhale_tools_migration_options', $migration_options );
		update_option( 'clickwhale_tools_last_migration_options', $last_migration_options );
		update_option( 'clickwhale_hide_notice_migrate', [] );
		update_option( 'clickwhale_hide_notice_deactive', [] );

		$result = __( 'Successfully deleted! Page will reload...', $this->plugin_name );

		wp_send_json_success( $result );

		wp_die();
	}

	public function clickwhale_reset() {
		check_ajax_referer( 'clickwhale_reset', 'security' );

		global $wpdb;
		$result = [];
		$text   = __( 'All plugin tables has been reset', $this->plugin_name );

		$result['status'] = $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}clickwhale_categories, {$wpdb->prefix}clickwhale_clicks, {$wpdb->prefix}clickwhale_links, {$wpdb->prefix}clickwhale_links_meta, {$wpdb->prefix}clickwhale_linkpages, {$wpdb->prefix}clickwhale_linkpages_meta" );
		$result['text']   = $text;

		activate_clickwhale();

		wp_send_json_success( $result );

		wp_die();
	}

	public function check_linkpage_slug() {
		check_ajax_referer( 'linkpage_slug', 'security' );

		if ( isset( $_POST['slug'] ) && $_POST['slug'] !== '' ) {
			$result = ClickwhaleLinkpagesHelper::slug_exists( sanitize_text_field( $_POST['slug'] ) );
		} else {
			$result = 'error';
		}

		wp_send_json_success( $result );

		wp_die();
	}

}