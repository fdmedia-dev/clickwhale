<?php
namespace clickwhale_pro\includes\admin;

use clickwhale_pro\includes\helpers\traits\{Singleton_Clone, Singleton_Wakeup};

/**
 * The settings of the plugin.
 *
 * @link       #
 * @since      1.0.0
 *
 * @package    Clickwhale
 * @subpackage Clickwhale/admin
 */
class Clickwhale_Pro_Ajax {

	/**
	 * @since    1.5.0
	 * @var Clickwhale_Pro_Ajax
	 */
	private static $instance;

	/**
	 * @return Clickwhale_Pro_Ajax
	 * @since    1.5.0
	 */
	public static function get_instance(): Clickwhale_Pro_Ajax {
		if ( empty( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	use Singleton_Clone;
	use Singleton_Wakeup;

	public function get_total_clicks_for_period() {
		global $wpdb;
		check_ajax_referer( 'clickwhale_pro_total_clicks_for_period', 'security' );

		$result           = [];
		$result['action'] = 'get_total_clicks_for_period';

		if ( ! empty( $_POST['period'] ) ) {
			$period = $_POST['period'];

			$result['items'] = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT track.link_id as id, COUNT(*) count, links.id AS item_id, links.title
							FROM {$wpdb->prefix}clickwhale_track track
							LEFT JOIN {$wpdb->prefix}clickwhale_links links 
							ON track.link_id = links.id
							WHERE track.event_type='click'
							  AND track.link_id != '0'
							  AND DATE_FORMAT(track.created_at, '%Y-%m-%%d') >= %s 
							  AND DATE_FORMAT(track.created_at, '%Y-%m-%%d') <= %s
							GROUP BY track.link_id
							ORDER BY count desc",
					$period[0], $period[1] ),
				ARRAY_A
			);
		} else {
			$result['items'] = $wpdb->get_results(
				"SELECT track.link_id as id, COUNT(*) count, links.id AS item_id, links.title
						FROM {$wpdb->prefix}clickwhale_track track
						LEFT JOIN {$wpdb->prefix}clickwhale_links links 
						ON track.link_id = links.id
						WHERE track.event_type='click'
						GROUP BY track.link_id
						ORDER BY count desc",
				ARRAY_A
			);
		}


		if ( ! $result['items'] ) {
			wp_send_json_error( 'No items!' );
			//wp_die();
		}

		wp_send_json_success( $result );
		//wp_die();
	}

	public static function get_clicks_count_for_day_and_id() {
		global $wpdb;

		check_ajax_referer( 'clickwhale_pro_clicks_count_for_day_and_id', 'security' );

		$result           = [];
		$result['action'] = 'get_linkpages_by_clicks_count_for_day';

		$result['items'] = $wpdb->get_results(
			"SELECT track.link_id as id, COUNT(*) count, links.id AS item_id, DATE_FORMAT(track.created_at, '%Y-%m-%d') AS created
					FROM {$wpdb->prefix}clickwhale_track track
					LEFT JOIN {$wpdb->prefix}clickwhale_links links 
					ON track.link_id = links.id
					WHERE track.event_type='click' 
					  AND track.link_id != '0'
					GROUP BY id, created
					ORDER BY created asc",
			ARRAY_A
		);

		if ( ! $result['items'] ) {
			wp_send_json_error( 'No items!' );
			//wp_die();
		}

		wp_send_json_success( $result );
		//wp_die();
	}

	public function get_total_views_for_period() {
		global $wpdb;
		check_ajax_referer( 'clickwhale_pro_total_views_for_period', 'security' );

		$result           = [];
		$result['action'] = 'get_total_views_for_period';

		if ( ! empty( $_POST['period'] ) ) {
			$period = $_POST['period'];

			$result['items'] = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT track.linkpage_id as id, COUNT(*) count, linkpages.id AS item_id, linkpages.title
							FROM {$wpdb->prefix}clickwhale_track track
							LEFT JOIN {$wpdb->prefix}clickwhale_linkpages linkpages 
							ON track.linkpage_id = linkpages.id
							WHERE track.event_type='view' 
							  AND DATE_FORMAT(track.created_at, '%Y-%m-%%d') >= %s 
							  AND DATE_FORMAT(track.created_at, '%Y-%m-%%d') <= %s
							GROUP BY track.linkpage_id
							ORDER BY count desc",
					$period[0], $period[1] ),
				ARRAY_A
			);
		} else {
			$result['items'] = $wpdb->get_results(
				"SELECT track.linkpage_id as id, COUNT(*) count, linkpages.id AS item_id, linkpages.title
					FROM {$wpdb->prefix}clickwhale_track track
					LEFT JOIN {$wpdb->prefix}clickwhale_linkpages linkpages 
					ON track.linkpage_id = linkpages.id
					WHERE track.event_type='view'
					GROUP BY track.linkpage_id
					ORDER BY count desc",
				ARRAY_A
			);
		}


		if ( ! $result['items'] ) {
			wp_send_json_error( 'No items!' );
			//wp_die();
		}

		wp_send_json_success( $result );
		//wp_die();
	}

	public static function get_views_count_for_day_and_id() {
		global $wpdb;

		check_ajax_referer( 'clickwhale_pro_views_count_for_day_and_id', 'security' );

		$result           = [];
		$result['action'] = 'get_linkpages_by_views_count_for_day';

		$result['items'] = $wpdb->get_results(
			"SELECT track.linkpage_id as id, COUNT(*) count, linkpages.id AS item_id, DATE_FORMAT(track.created_at, '%Y-%m-%d') AS created
					FROM {$wpdb->prefix}clickwhale_track track
					LEFT JOIN {$wpdb->prefix}clickwhale_linkpages linkpages 
					ON track.linkpage_id = linkpages.id
					WHERE track.event_type='view'
					GROUP BY id, created
					ORDER BY created asc",
			ARRAY_A
		);

		if ( ! $result['items'] ) {
			wp_send_json_error( 'No items!' );
			//wp_die();
		}

		wp_send_json_success( $result );
		//wp_die();
	}
}