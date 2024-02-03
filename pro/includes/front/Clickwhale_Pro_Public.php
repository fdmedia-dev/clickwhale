<?php
/**
 * The public-facing functionality of the plugin.
 *
 *  Defines the plugin name, version, and two examples hooks for how to
 *  enqueue the public-facing stylesheet and JavaScript.
 *
 * @link       https://fdmedia.io/
 * @since      1.0.0
 *
 * @package    Clickwhale_Pro
 * @subpackage Clickwhale_Pro/public
 * @author     fdmedia <dev@krapan.net>
 */

namespace clickwhale_pro\includes\front;

use clickwhale\includes\helpers\Helper;
use clickwhale\includes\helpers\Linkpages_Helper;
use clickwhale\includes\helpers\Tracking_Codes_Helper;
use clickwhale_pro\includes\helpers\Linkpage_Styles_Helper;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Clickwhale_Pro_Public {
	/**
	 * The unique instance of the plugin.
	 *
	 * @var Clickwhale_Pro_Public
	 * @since 1.0.3
	 */
	private static $instance;

    /**
     * @var string
     */
	private $path;

	public static function get_instance(): Clickwhale_Pro_Public {
		if ( empty( self::$instance ) ) {
			self::$instance       = new self();
			self::$instance->path = Helper::get_public_path( true );

			self::$instance->load_dependencies();
			self::$instance->do_clickwhale_tracking_code_conversion();
		}

		return self::$instance;
	}

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	private function __construct() {}

	public function load_dependencies() {
		require_once CLICKWHALE_PRO_DIR . 'includes/front/Clickwhale_Pro_Tracking_Code_Conversion.php';
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		if ( ! is_admin() && $this->path && Linkpages_Helper::is_linkpage( $this->path ) ) {
			wp_enqueue_style(
                CLICKWHALE_PRO_NAME,
				CLICKWHALE_PRO_PUBLIC_ASSETS_DIR . '/css/clickwhale-pro-public.css',
				array(),
				CLICKWHALE_PRO_VERSION
			);
		}
	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		if ( ! is_admin() && $this->path && Linkpages_Helper::is_linkpage( $this->path ) ) {
			wp_enqueue_script(
                CLICKWHALE_PRO_NAME,
				CLICKWHALE_PRO_PUBLIC_ASSETS_DIR . '/js/clickwhale-pro-public.js',
				array( 'jquery' ),
				CLICKWHALE_PRO_VERSION,
				true
			);
		}
	}

	public function clickwhale_pro_url_params( $url, $id ): string {
		global $wpdb;

		$utm_string = $wpdb->get_var( $wpdb->prepare( "SELECT meta_value FROM {$wpdb->prefix}clickwhale_meta WHERE link_id=%d AND meta_key='utm_string'",
			$id ) );
		$url_array  = parse_url( $url );
		$utm        = $url_array['query'] ? '&' . $utm_string : '?' . $utm_string;

		return $url . $utm;
	}

	/**
	 * Filter function
	 * Add new PRO styles to the linkpage
	 *
	 * @param $style
	 * @param $styles
	 *
	 * @return string
	 */
	public function linkpage_pro_styles( $style, $styles ) {

		// Add predefined gradient as css variables
		$gradients_vars = '';
		$gradients      = Linkpage_Styles_Helper::get_gradients();

		if ( $gradients ) {
			foreach ( $gradients as $k => $gradient ) {
				$css_var_name    = '--clickwhale-page-bg-gradient-' . $k;
				$gradient_string = '';

				foreach ( $gradient['colors'] as $stop => $color ) {
					$gradient_string .= ', ' . $color . ' ' . $stop . '%';
				}
				$css_var_value = $gradient['style'] . '-gradient(' . $gradient['direction'] . $gradient_string . ')';

				$gradients_vars .= $css_var_name . ': ' . $css_var_value . ';';
			}
		}

		// Setup background
		$bg_string = '';
		$bg_style  = $styles['bg_style'] ?? 'color';
		switch ( $bg_style ) {
			case 'gradient':

				if ( $styles['bg_gradient'] !== 'custom' ) {
					$bg_string = 'background: var(--clickwhale-page-bg-gradient-' . $styles['bg_gradient'] . ')';
				} else {
					$custom = $styles['bg_gradient_custom'];

					$direction = '';
					$direction = $custom['style'] === 'linear' ? 'to ' . $custom['direction'] . ',' : $direction;
					$direction = $custom['style'] === 'radial' ? 'circle at ' . $custom['direction'] . ',' : $direction;

					$bg_string = 'background: ' . $custom['style'] . '-gradient(' . $direction . $custom['end'] . ', ' . $custom['start'] . ');';
				}

				break;
			case 'pattern':
				$pattern_url = CLICKWHALE_PRO_PUBLIC_ASSETS_DIR . '/patterns/';
				$bg_string   = 'background: url(' . $pattern_url . $styles['bg_pattern'] . '.svg) repeat center center;';

				break;
			case 'image':
				$image       = $styles['bg_image'];
				$bg_image    = 'background-image: url(' . wp_get_attachment_image_url( $image['image'], 'full' ) . ');';
				$bg_position = 'background-position: ' . $image['x'] . ' ' . $image['y'] . ';';
				$bg_repeat   = 'background-repeat: ' . $image['repeat'] . ';';
				$bg_size     = 'background-size: ' . $image['size'] . ';';

				$bg_string = $bg_image . $bg_position . $bg_repeat . $bg_size;

				break;
		}
		$bg = '.linkpage-public--wrap{' . $bg_string . '}';

		if ( $gradients_vars ) {
			$style .= ':root{' . $gradients_vars . '}';
		}

		$style .= $bg;

		return $style;
	}

	/**
	 * Runs WOO or EDD conversion for the clickwhale tracking code
	 *
	 * @return void
	 */
	private function do_clickwhale_tracking_code_conversion(): void {
		$tracking_codes = maybe_unserialize( Tracking_Codes_Helper::get_active() );

		if ( ! $tracking_codes ) {
			return;
		}

		foreach ( $tracking_codes as $tracking_code ) {
			$position = maybe_unserialize( $tracking_code['position'] );

			if ( empty( $position['conversion'] ) || $position['conversion'] === 'standard' ) {
				continue;
			}

			new Clickwhale_Pro_Tracking_Code_Conversion( $tracking_code );
		}
	}
}
