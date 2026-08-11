<?php

namespace Clickwhale\Admin\SmartDisplays;

use Clickwhale\Helpers\{Helper, Integrations_Helper, Smart_Displays_Helper};

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles daily auto-refresh of AMZ Connect Smart Displays via Action Scheduler.
 *
 * @since 2.8.0
 */
class Clickwhale_Smart_Display_Scheduler {

	const DAILY_ACTION   = 'clickwhale_amz_daily_refresh';
	const SINGLE_ACTION  = 'clickwhale_amz_refresh_display';
	const NOTICES_OPTION = 'clickwhale_amz_inactive_notices';
	const AS_GROUP       = 'clickwhale';

	public function register_hooks(): void {
		add_action( 'init',                    [ $this, 'maybe_schedule_daily' ] );
		add_action( self::DAILY_ACTION,        [ $this, 'dispatch_individual_refreshes' ] );
		add_action( self::SINGLE_ACTION,       [ $this, 'refresh_display' ] );
		add_action( 'admin_notices',           [ $this, 'show_inactive_notices' ] );
		add_action( 'admin_init',              [ $this, 'handle_dismiss_notices' ] );
	}

	/**
	 * Schedule the daily recurring action if not already scheduled.
	 */
	public function maybe_schedule_daily(): void {
		if ( ! as_has_scheduled_action( self::DAILY_ACTION, [], self::AS_GROUP ) ) {
			as_schedule_recurring_action( time(), DAY_IN_SECONDS, self::DAILY_ACTION, [], self::AS_GROUP, true );
		}
	}

	/**
	 * Unschedule all actions on plugin deactivation.
	 */
	public static function unschedule(): void {
		if ( ! function_exists( 'as_unschedule_all_actions' ) ) {
			return;
		}

		as_unschedule_all_actions( self::DAILY_ACTION, [], self::AS_GROUP );
	}

	/**
	 * Queue an individual refresh action for each active AMZ Connect display.
	 */
	public function dispatch_individual_refreshes(): void {
		global $wpdb;

		$data_table = Helper::get_db_table_name( 'smart_display_data' );
		$rows       = $wpdb->get_results(
			"SELECT id FROM {$data_table} WHERE is_active = 1 AND integration = 'amz_connect'",
			ARRAY_A
		);

		foreach ( $rows as $row ) {
			as_enqueue_async_action(
				self::SINGLE_ACTION,
				[ 'data_id' => intval( $row['id'] ) ],
				self::AS_GROUP
			);
		}
	}

	/**
	 * Fetch fresh product data and update a single Smart Display.
	 *
	 * @param int $data_id Row ID in smart_display_data.
	 */
	public function refresh_display( int $data_id ): void {
		global $wpdb;

		$data_table = Helper::get_db_table_name( 'smart_display_data' );

		$row = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$data_table} WHERE id = %d", $data_id ),
			ARRAY_A
		);

		if ( ! $row ) {
			return;
		}

		$params = json_decode( $row['params'], true );
		$asin   = $params['asin'] ?? '';
		$store  = $params['store'] ?? 'com';

		if ( empty( $asin ) ) {
			return;
		}

		$api_key = Integrations_Helper::get_integration_api_key( 'amz_connect' );
		if ( empty( $api_key ) ) {
			return;
		}

		$result = Integrations_Helper::amz_connect_get_product( $api_key, $asin, $store );

		if ( is_wp_error( $result ) ) {
			$wpdb->update(
				$data_table,
				[
					'is_active'  => 0,
					'updated_at' => current_time( 'mysql' ),
				],
				[ 'id' => $data_id ]
			);

			$this->add_inactive_notice( intval( $row['smart_display_id'] ) );
			return;
		}

		$wpdb->update(
			$data_table,
			[
				'is_active'  => 1,
				'raw_data'   => wp_json_encode( $result ),
				'updated_at' => current_time( 'mysql' ),
			],
			[ 'id' => $data_id ]
		);

		$this->update_display_fields( intval( $row['smart_display_id'] ), $result, $asin, $store );
	}

	/**
	 * Map API product data to Smart Display fields, updating only changed values.
	 */
	private function update_display_fields( int $sd_id, array $product, string $asin, string $store ): void {
		global $wpdb;

		$sd_table = Helper::get_db_table_name( 'smart_displays' );
		$current  = Smart_Displays_Helper::get_by_id( $sd_id );

		if ( ! $current ) {
			return;
		}

		$options = maybe_unserialize( $current['options'] ) ?: [];

		// Build Amazon URL
		$stores      = Integrations_Helper::get_amz_connect_stores();
		$domain      = $stores[ $store ]['domain'] ?? "amazon.{$store}";
		$tracking_id = Integrations_Helper::get_integration_field( 'amz_connect', 'tracking_id', '' );
		$tag         = $tracking_id ? "?tag={$tracking_id}" : '';
		$link_url    = "https://{$domain}/dp/{$asin}{$tag}";

		// Map product → fields
		$new_title = sanitize_text_field( $product['title'] ?? '' );

		$desc_html = '';
		if ( ! empty( $product['feature_bullets'] ) ) {
			$lis       = array_map(
				fn( $b ) => '<li>' . wp_kses_post( $b ) . '</li>',
				$product['feature_bullets']
			);
			$desc_html = '<ul>' . implode( '', $lis ) . '</ul>';
		}

		$price     = sanitize_text_field( $product['buybox_winner']['price']['raw'] ?? $product['buybox_winner']['rrp']['raw'] ?? '' );
		$image_url = esc_url_raw( $product['main_image']['link'] ?? '' );

		// Collect only changed fields
		$updates        = [];
		$options_dirty  = false;

		if ( $new_title && $new_title !== $current['title'] && empty( $options['override']['title'] ) ) {
			$updates['title'] = $new_title;
		}

		if ( $desc_html !== ( $current['description'] ?? '' ) && empty( $options['override']['description'] ) ) {
			$updates['description'] = wp_kses_post( $desc_html );
		}

		if ( $price !== ( $options['price'] ?? '' ) ) {
			$options['price'] = $price;
			$options_dirty    = true;
		}

		if ( $image_url !== ( $options['image_url'] ?? '' ) ) {
			$options['image_url'] = $image_url;
			$options_dirty        = true;
		}

		if ( $link_url !== ( $options['link_url'] ?? '' ) ) {
			$options['link_url'] = $link_url;
			$options_dirty       = true;
		}

		if ( $options_dirty ) {
			$updates['options'] = maybe_serialize( $options );
		}

		if ( ! empty( $updates ) ) {
			$wpdb->update( $sd_table, $updates, [ 'id' => $sd_id ] );
		}
	}

	/**
	 * Store the ID of a display that became inactive so an admin notice can be shown.
	 */
	private function add_inactive_notice( int $sd_id ): void {
		$notices = get_option( self::NOTICES_OPTION, [] );

		if ( ! in_array( $sd_id, $notices, true ) ) {
			$notices[] = $sd_id;
			update_option( self::NOTICES_OPTION, $notices );
		}
	}

	/**
	 * Display an admin notice listing Smart Displays that became inactive.
	 */
	public function show_inactive_notices(): void {
		$notices = get_option( self::NOTICES_OPTION, [] );

		if ( empty( $notices ) || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$links = [];
		foreach ( $notices as $sd_id ) {
			$data = Smart_Displays_Helper::get_by_id( intval( $sd_id ) );
			if ( ! $data ) {
				continue;
			}
			$name    = ! empty( $data['name'] ) ? $data['name'] : ( $data['title'] ?: '#' . $sd_id );
			$url     = admin_url( 'admin.php?page=' . CLICKWHALE_SLUG . '-edit-smart-display&id=' . intval( $sd_id ) );
			$links[] = '<a href="' . esc_url( $url ) . '">' . esc_html( $name ) . '</a>';
		}

		if ( empty( $links ) ) {
			delete_option( self::NOTICES_OPTION );
			return;
		}

		$dismiss_url = wp_nonce_url(
			add_query_arg( 'clickwhale_dismiss_amz_notices', '1' ),
			'clickwhale_dismiss_amz_notices'
		);
		?>
		<div class="notice notice-warning">
			<p>
				<strong><?php esc_html_e( 'ClickWhale — AMZ Connect:', 'clickwhale' ); ?></strong>
				<?php esc_html_e( 'The following Smart Displays became inactive because their Amazon product data could not be fetched. Please review them:', 'clickwhale' ); ?>
				<?php echo implode( ', ', $links ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				&mdash; <a href="<?php echo esc_url( $dismiss_url ); ?>"><?php esc_html_e( 'Dismiss', 'clickwhale' ); ?></a>
			</p>
		</div>
		<?php
	}

	/**
	 * Handle the dismiss action for inactive display notices.
	 */
	public function handle_dismiss_notices(): void {
		if ( empty( $_GET['clickwhale_dismiss_amz_notices'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( sanitize_key( $_GET['_wpnonce'] ?? '' ), 'clickwhale_dismiss_amz_notices' ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		delete_option( self::NOTICES_OPTION );

		wp_safe_redirect( remove_query_arg( [ 'clickwhale_dismiss_amz_notices', '_wpnonce' ] ) );
		exit;
	}
}
