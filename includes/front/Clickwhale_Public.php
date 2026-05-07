<?php

namespace Clickwhale\Front;

use Clickwhale\Front\Tracking\Clickwhale_Click_Track;
use Clickwhale\Helpers\{
	Helper,
	Linkpages_Helper,
	Links_Helper
};

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Clickwhale
 * @subpackage Clickwhale/public
 * @author     fdmedia <https://fdmedia.io>
 */
final class Clickwhale_Public {

	/**
	 * @since    1.0.0
	 * @var Clickwhale_Public
	 */
	private static Clickwhale_Public $instance;

	/**
	 * @var string
	 */
	private string $path;

	/**
	 * @var Clickwhale_Public_Linkpages
	 * @since    1.0.0
	 */
	public Clickwhale_Public_Linkpages $linkpages;

	/**
	 * @var Clickwhale_Public_Tracking_Codes
	 * @since    1.0.0
	 */
	public Clickwhale_Public_Tracking_Codes $tracking_codes;

	/**
	 * @return Clickwhale_Public
	 * @since    1.0.0
	 */
	public static function get_instance(): Clickwhale_Public {
		if ( empty( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Initialize the class and set its properties.
	 * @since    1.0.0
	 */
	public function __construct() {

		$this->path           = Helper::get_public_path();
		$this->linkpages      = new Clickwhale_Public_Linkpages();
		$this->tracking_codes = new Clickwhale_Public_Tracking_Codes( $this->path );
	}

	/**
	 * Load the required dependencies for the Admin facing functionality.
	 * Include the following files that make up the plugin:
	 *   Clickwhale_Admin_Settings - Registers the admin settings and page.
	 *
	 * @since    1.0.0
	 * @access   private
	 */

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		if ( ! is_admin() && $this->path && Linkpages_Helper::is_linkpage( $this->path ) ) {
			wp_enqueue_style(
				'clickwhale',
				CLICKWHALE_PUBLIC_ASSETS_DIR . '/css/clickwhale-public.css',
				array(),
				CLICKWHALE_VERSION
			);
		}
	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		if ( ! is_admin() && $this->path && Linkpages_Helper::is_linkpage( $this->path ) ) {
			wp_enqueue_script(
				'clickwhale',
				CLICKWHALE_PUBLIC_ASSETS_DIR . '/js/clickwhale-public.js',
				array( 'jquery' ),
				CLICKWHALE_VERSION,
				true
			);

			wp_localize_script(
				'clickwhale',
				'clickwhale_public',
				array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) )
			);
		}
	}

	/**
	 * Redirect Clickwhale Link to its url
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function do_redirect_handler() {
		if ( is_admin()
		     || '' === $this->path
		     || substr( $this->path, - 4 ) === '.php' // pages like `wp-cron.php`, `wp-login.php`
		) {
			return;
		}

		$slug = Links_Helper::sanitize_slug( $this->path );

		if ( '' === $slug ) {
			return;
		}

		$link = Links_Helper::get_by_slug( $slug );

		if ( empty( $link ) ) {
			return;
		}

		$link_id = intval( $link['id'] );

		// Track click on link
		$click_track = new Clickwhale_Click_Track( $link_id );
		$track_data  = $click_track->proceed_click_track();

		#--------------------------------------------------------------------------------
		# Hooks for third-party integration
		#--------------------------------------------------------------------------------

		$user_id = get_current_user_id();

		// Hook: ClickWhale link was clicked (no matter where it is placed)
		do_action( 'clickwhale/link_clicked', $link, $link_id, $user_id );

		// Hook: ClickWhale link placed at Link Page was clicked
		if ( ! empty( $track_data['linkpage_id'] ) ) {
			$linkpage_id = intval( $track_data['linkpage_id'] );

			if ( 0 !== $linkpage_id ) {
				$linkpage = Linkpages_Helper::get_by_id( $linkpage_id );

				if ( $linkpage ) {
					do_action( 'clickwhale/link_page_link_clicked', $link, $link_id, $linkpage, $linkpage_id, $user_id );
				}
			}
		}

		#--------------------------------------------------------------------------------
		# End of `Hooks for third-party integration`
		#--------------------------------------------------------------------------------

		// Set headers
		$nofollow  = '';
		$sponsored = '';
		$sep       = '';

		if ( $link['nofollow'] ) {
			$nofollow = 'noindex, nofollow';
		}

		if ( $link['sponsored'] ) {
			$sponsored = 'sponsored';
		}

		if ( $link['nofollow'] && $link['sponsored'] ) {
			$sep = ', ';
		}

		if ( $link['nofollow'] || $link['sponsored'] ) {
			header( 'X-Robots-Tag: ' . $nofollow . $sep . $sponsored );
		}

		$link_url = apply_filters( 'clickwhale_url_params', $link['url'], $link_id );
		wp_redirect( esc_url_raw( $link_url ), $link['redirection'] );
		exit;
	}

	public function add_target_to_clickwhale_link( string $content ): string {

		return preg_replace_callback( '/<a(.*?)?href=[\'"]?[\'"]?(.*?)?>/i', function ( $m ) {
			$tpl = array_shift( $m );
			$hrf = $m[1] ?? null;

			// If link has `target` attr
			if ( preg_match( '/target=[\'"]?(.*?)[\'"]?/i', $tpl ) ) {
				return $tpl;
			}

			// If link is anchor
			if ( trim( $hrf ) && 0 === strpos( $hrf, '#' ) ) {
				return $tpl;
			}

			// If link has params
			if ( strpos( $hrf, '?' ) !== false ) {
				return $tpl;
			}

			$link = array();

			// Get `href` attr
			if ( preg_match( '/href=["\']?([^"\'>]+)["\']?/', $tpl, $matches ) ) {
				$home_url = home_url();

				// Remove extra from `href`
				$href_search = array(
					'href="' . $home_url . '/' => '',
					'href="' . $home_url       => '',
					'href="/'                  => '',
					'/"'                       => '',
					'"'                        => '',
					"href='" . $home_url . "/" => '',
					"href='" . $home_url       => '',
					"href='/"                  => '',
					"/'"                       => '',
					"'"                        => ''
				);

				$slug = strtr( $matches[0], $href_search );
				$slug = Links_Helper::sanitize_slug( $slug );

				if ( '' === $slug ) {
					return $tpl;
				}

				$link = Links_Helper::get_by_slug( $slug );

				// If link is not Clickwhale Link
				if ( empty( $link ) ) {
					return $tpl;
				}
			}

			// Get `target` attr
			if ( isset( $link['link_target'] ) ) {
				$target_arg = $link['link_target'];
			} else {
				$link_manager_options = get_option( 'clickwhale_link_manager_options' );
				$defaults             = clickwhale()->settings->default_options();
				$target_arg           = $link_manager_options['link_target'] ?? $defaults['link_manager']['options']['link_target'];
			}

			$target_arg = esc_attr( $target_arg );

			$target = '';

			if ( in_array( $target_arg, array( 'blank', 'self' ), true ) ) {
				$target = 'target="_' . $target_arg . '"';
			}

			$tpl = preg_replace_callback( '/href=/i', function ( $m3 ) use ( $target ) {
				return sprintf(
					'%s %s',
					$target,
					array_shift( $m3 )
				);
			}, $tpl );

			if ( isset( $link['sponsored'] ) ) {
				if ( preg_match( '/rel=["\']?([^"\'>]+)["\']?/', $tpl, $matches ) ) {
					$tpl = str_replace( 'rel="', 'rel="sponsored ', $tpl );
				} else {
					$tpl = str_replace( '">', '" rel="sponsored">', $tpl );
				}
			}

			return $tpl;

		}, $content );
	}
}
