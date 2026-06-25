<?php

namespace Clickwhale\Helpers;

use Clickwhale\Clickwhale;
use DateTime;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @since 2.8.0
 */
class Integrations_Helper {

	const AMZ_CONNECT_API_BASE = 'https://api.amzconnect.io/v1';

	public static function get_integration_default_options( $name ): array {
		$options = Clickwhale::get_instance()->default_options( $name );

		return $options['integrations']['options'][ $name ];
	}

	/**
	 * Common for all integrations
	 * @since 2.8.0
	 */
	public static function get_about_settings_filed( array $about = [], string $url = '' ): string {
		$content     = '';
		$text        = ! empty( $about['text'] ) ? $about['text'] : '';
		$description = ! empty( $about['description'] ) ? $about['description'] : '';
		$link_html   = '';

		if ( $url ) {
			$link_text = __( 'Learn more', 'clickwhale' );
			$link_html = '<a href="' . esc_url( $url ) . '" target="_blank" rel="noopener noreferrer">' . $link_text . '</a>';
		}

		if ( $text ) {
			$content .= '<p style="margin-top: 0;">';
			$content .= esc_html( $about['text'] );
			$content .= ! $description ? ' ' . $link_html : '';
			$content .= '</p>';
		}

		if ( $description ) {
			$content .= '<p class="description">' . esc_html( $about['description'] ) . ' ' . $link_html . '</p>';
		}


		return $content;
	}

	/**
	 * AMZ Connect
	 */
	/**
	 * @return array
	 * @since 2.8.0
	 */
	public static function get_amz_connect_stores(): array {
		return array(
			'com'    => array( 'domain' => 'amazon.com', 'name' => 'USA', 'flag' => '🇺🇸' ),
			'co.uk'  => array( 'domain' => 'amazon.co.uk', 'name' => 'UK', 'flag' => '🇬🇧' ),
			'de'     => array( 'domain' => 'amazon.de', 'name' => 'Germany', 'flag' => '🇩🇪' ),
			'fr'     => array( 'domain' => 'amazon.fr', 'name' => 'France', 'flag' => '🇫🇷' ),
			'it'     => array( 'domain' => 'amazon.it', 'name' => 'Italy', 'flag' => '🇮🇹' ),
			'es'     => array( 'domain' => 'amazon.es', 'name' => 'Spain', 'flag' => '🇪🇸' ),
			'ca'     => array( 'domain' => 'amazon.ca', 'name' => 'Canada', 'flag' => '🇨🇦' ),
			'com.au' => array( 'domain' => 'amazon.com.au', 'name' => 'Australia', 'flag' => '🇦🇺' ),
			'co.jp'  => array( 'domain' => 'amazon.co.jp', 'name' => 'Japan', 'flag' => '🇯🇵' ),
			'in'     => array( 'domain' => 'amazon.in', 'name' => 'India', 'flag' => '🇮🇳' ),
			'com.br' => array( 'domain' => 'amazon.com.br', 'name' => 'Brazil', 'flag' => '🇧🇷' ),
			'com.mx' => array( 'domain' => 'amazon.com.mx', 'name' => 'Mexico', 'flag' => '🇲🇽' ),
			'nl'     => array( 'domain' => 'amazon.nl', 'name' => 'Netherlands', 'flag' => '🇳🇱' ),
			'se'     => array( 'domain' => 'amazon.se', 'name' => 'Sweden', 'flag' => '🇸🇪' ),
			'pl'     => array( 'domain' => 'amazon.pl', 'name' => 'Poland', 'flag' => '🇵🇱' ),
			'com.tr' => array( 'domain' => 'amazon.com.tr', 'name' => 'Turkey', 'flag' => '🇹🇷' ),
			'ae'     => array( 'domain' => 'amazon.ae', 'name' => 'UAE', 'flag' => '🇦🇪' ),
			'sg'     => array( 'domain' => 'amazon.sg', 'name' => 'Singapore', 'flag' => '🇸🇬' ),
		);
	}

	public static function get_integration_status( string $key ): string {
		$options = get_option( 'clickwhale_integrations_options', array() );

		return $options[ $key ]['connection']['status'] ?? 'disconnected';
	}

	public static function is_integration_enabled( string $key ): bool {
		return self::get_integration_status( $key ) === 'connected';
	}

	public static function get_integration_field( string $key, string $field, $default = null ) {
		$options = get_option( 'clickwhale_integrations_options', array() );

		return $options[ $key ]['fields'][ $field ] ?? $default;
	}

	public static function get_integration_api_key( string $key ): string {
		$options = get_option( 'clickwhale_integrations_options', array() );

		return $options[ $key ]['connection']['api_key'] ?? '';
	}

	public static function get_integration_connection( string $key ): array {
		$options = get_option( 'clickwhale_integrations_options', array() );

		return $options[ $key ]['connection'] ?? array();
	}

	public static function update_integration_connection( string $key, array $connection ): void {
		$options                       = get_option( 'clickwhale_integrations_options', array() );
		$options[ $key ]['connection'] = $connection;
		update_option( 'clickwhale_integrations_options', $options );
	}

	public static function get_amz_connect_api_key_field( string $stored_key ): string {
		$has_key      = ! empty( $stored_key );
		$is_connected = self::is_integration_enabled( 'amz_connect' );
		$output       = '<div class="cw-api-key-field">';

		if ( $has_key ) {
			// Key is saved — show readonly masked field; sentinel value prevents overwriting on save
			$output .= '<input'
			           . ' type="password"'
			           . ' id="amz_connect_fields_api_key"'
			           . ' name="clickwhale_integrations_options[amz_connect][connection][api_key]"'
			           . ' value="__SAVED__"'
			           . ' readonly'
			           . ' autocomplete="off"'
			           . ' style="width:300px;"'
			           . '>';
			$output .= '<button type="button" class="button cw-amz-key-remove">'
			           . esc_html__( 'Remove key', 'clickwhale' )
			           . '</button>';
		} else {
			$output .= '<input'
			           . ' type="text"'
			           . ' id="amz_connect_fields_api_key"'
			           . ' name="clickwhale_integrations_options[amz_connect][connection][api_key]"'
			           . ' value=""'
			           . ' autocomplete="off"'
			           . ' placeholder="' . esc_attr__( 'Paste API key here', 'clickwhale' ) . '"'
			           . ' style="width:300px;"'
			           . '>';
		}

		$label = $is_connected ? __( 'Connected', 'clickwhale' ) : __( 'Invalid API Key', 'clickwhale' );
		$cls   = $is_connected ? 'connected' : 'disconnected';

		if ( $has_key ) {
			$output .= '<span class="clickwhale-api-status ' . esc_attr( $cls ) . '" id="cw-amz-api-status">'
			           . esc_html( $label )
			           . '</span>';
		} else {
			$output .= '<span class="clickwhale-api-status" id="cw-amz-api-status" style="display:none;"></span>';
		}

		$output .= '</div>';
		$output .= '<p class="description">' . esc_html__( 'Your AMZ Connect API key. Get one at amzconnect.io.', 'clickwhale' ) . '</p>';

		return $output;
	}

	public static function get_amz_connect_quota_field(): string {
		$enabled = self::is_integration_enabled( 'amz_connect' );

		if ( ! $enabled ) {
			return __( 'AMZ Connect API key is not configured', 'clickwhale' );
		}

		$reset_date = new DateTime( 'first day of next month' );
		$this_month = date( 'F' );

		$quota_text = sprintf(
		/* translators: 1: number of used requests, 2: total limit, 3: month name, 4: number remaining */
			__( '%1$s of %2$s monthly fetches used <span class="muted">(%3$s remaining)</span>', 'clickwhale' ),
			'<span id="cw-amz-quota-used">0</span>',
			'<span id="cw-amz-quota-total">0</span>',
			'<span id="cw-amz-quota-remaining">0</span>'
		);

		$content = '<div class="cw-amz-connect-quota-field cw-is-loading">';
		$content .= '<div class="cw-amz-connect-quota--header">';
		$content .= '<p class="cw-api-quota-label">' . $quota_text . '</p>';
		$content .= '<button type="button" id="cw-amz-connect-quota-reset" class="button cw-amz-connect-quota-reset">' . esc_html__( 'Refresh', 'clickwhale' ) . '</button>';
		$content .= '</div><!-- ./cw-amz-connect-quota--header -->';
		$content .= '<div class="cw-amz-connect-quota--progress-bar">';
		$content .= '<progress id="cw-amz-connect-quota-progress" max="100" value="0"></progress>';
		$content .= '</div><!-- ./cw-amz-connect-quota--progress-bar -->';
		$content .= '<div class="cw-amz-connect-quota--footer">';
		$content .= '<p class="cw-api-quota-label">Resets: ' . esc_html( $reset_date->format( 'F j, Y' ) ) . '</p>';
		$content .= '<p class="cw-api-quota-label cw-api-quota-cached-at" id="cw-amz-quota-cached-at-wrap">'
		            . esc_html__( 'Updated:', 'clickwhale' )
		            . ' <span id="cw-amz-quota-cached-at"></span></p>';
		$content .= '</div><!-- ./cw-amz-connect-quota--footer -->';
		$content .= '</div><!-- ./cw-amz-connect-quota-field -->';

		return $content;
	}

	/**
	 * Fetch quota info from the AMZ Connect API.
	 *
	 * @return array|WP_Error
	 * @since 2.8.0
	 */
	public static function amz_connect_get_quota( string $api_key ) {
		$response = wp_remote_get(
			self::AMZ_CONNECT_API_BASE . '/check/quota',
			array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $api_key,
					'Content-Type'  => 'application/json',
					'Accept'        => 'application/json',
				),
				'timeout' => 15,
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$http_code = (int) wp_remote_retrieve_response_code( $response );
		$data      = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $http_code < 200 || $http_code >= 300 ) {
			$message = $data['message'] ?? __( 'Failed to fetch AMZ Connect quota', 'clickwhale' );

			return new WP_Error( 'amz_connect_quota_error', $message, array( 'status' => $http_code ) );
		}

		return is_array( $data ) ? $data : array();
	}

	/**
	 * Validate an AMZ Connect API key using the test endpoint.
	 * Returns true if the key is accepted, false otherwise.
	 *
	 * @since 2.8.0
	 */
	public static function amz_connect_validate_key( string $api_key ): bool {
		$response = wp_remote_post(
			self::AMZ_CONNECT_API_BASE . '/test/product',
			array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $api_key,
					'Content-Type'  => 'application/json',
					'Accept'        => 'application/json',
				),
				'body'    => wp_json_encode( array(
					'asin'  => 'B08N5WRWNW',
					'store' => 'com',
				) ),
				'timeout' => 15,
			)
		);

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );

		return $code >= 200 && $code < 300;
	}

	/**
	 * Fetch a product from the AMZ Connect API by ASIN and store.
	 * Returns the decoded response array or WP_Error on failure.
	 *
	 * @since 2.8.0
	 */
	/**
	 * Fetch a product from the AMZ Connect API by ASIN and store.
	 *
	 * Successful response shape:
	 *   asin            string
	 *   title           string
	 *   main_image      array { link: string }
	 *   images          array<array{ link: string, variant: string }>
	 *   feature_bullets string[]
	 *   rating          float
	 *   ratings_total   int
	 *   source          string  ("API"|"DB")
	 *   usage           int
	 *   buybox_winner   array {
	 *     availability  array { type: "in_stock"|"not_in_stock", raw: string, dispatch_days?: int }
	 *     price         array|null { symbol, value, currency, raw }
	 *     is_prime      bool
	 *     fulfillment   array { is_fulfilled_by_amazon: int }
	 *     shipping      array { value: string }
	 *     rrp           array
	 *   }
	 *
	 * Error HTTP codes: 403 invalid_key, 404 not_found.
	 *
	 * @return array|WP_Error
	 * @since 2.8.0
	 */
	public static function amz_connect_get_product( string $api_key, string $asin, string $store ) {
		$response = wp_remote_post(
			self::AMZ_CONNECT_API_BASE . '/get/product',
			array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $api_key,
					'Content-Type'  => 'application/json',
					'Accept'        => 'application/json',
				),
				'body'    => wp_json_encode( array(
					'asin'  => sanitize_text_field( $asin ),
					'store' => sanitize_text_field( $store ),
				) ),
				'timeout' => 15,
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$http_code = (int) wp_remote_retrieve_response_code( $response );
		$data      = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $http_code < 200 || $http_code >= 300 ) {
			$api_code = $data['code'] ?? 'amz_connect_error';
			$message  = $data['message'] ?? __( 'AMZ Connect API request failed', 'clickwhale' );

			return new WP_Error( $api_code, $message, array( 'status' => $http_code ) );
		}

		return is_array( $data ) ? $data : array();
	}

}