<?php
namespace clickwhale\includes\front;

use clickwhale\includes\front\tracking\Clickwhale_Click_Track;
use clickwhale\includes\helpers\{
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
    private static $instance;

    /**
     * @var string
     */
	private $path;

	/**
	 * @var Clickwhale_Public_Linkpages
	 * @since    1.0.0
	 */
	public $linkpages;

	/**
	 * @var Clickwhale_Public_Tracking_Codes
	 * @since    1.0.0
	 */
	public $tracking_codes;

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
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		$this->load_dependencies();

		$this->path           = Helper::get_public_path( true );
		$this->linkpages      = new Clickwhale_Public_Linkpages();
		$this->tracking_codes = new Clickwhale_Public_Tracking_Codes( $this->path );
	}

	/**
	 * Load the required dependencies for the Admin facing functionality.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Clickwhale_Admin_Settings. Registers the admin settings and page.
	 *
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'front/Clickwhale_Public_Ajax.php';

		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'front/tracking/Clickwhale_View_Track.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'front/tracking/Clickwhale_Parser.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'front/tracking/Clickwhale_Visitor_Track.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'front/tracking/Clickwhale_Click_Track.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'front/Clickwhale_Public_Linkpages.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'front/Clickwhale_Public_Linkpage.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'front/Clickwhale_Public_Tracking_Codes.php';
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		if ( ! is_admin() && $this->path && Linkpages_Helper::is_linkpage( $this->path ) ) {
			wp_enqueue_style(
				CLICKWHALE_NAME,
				CLICKWHALE_PUBLIC_ASSETS_DIR . '/css/clickwhale-public.css',
				array(),
				CLICKWHALE_VERSION
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
				CLICKWHALE_NAME . '_ionicons',
				CLICKWHALE_PUBLIC_ASSETS_DIR . '/js/ionicons/ionicons.js',
				array( 'jquery' ),
				'7.1.0',
				true
			);

			wp_enqueue_script(
				CLICKWHALE_NAME,
				CLICKWHALE_PUBLIC_ASSETS_DIR . '/js/clickwhale-public.js',
				array( 'jquery' ),
				CLICKWHALE_VERSION,
				true
			);

			wp_localize_script(
				CLICKWHALE_NAME,
				'clickwhale_public',
				array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) )
			);
		}
	}

	/**
	 * Clickwhale Link redirect to its url
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function do_redirect_handler() {
        if ( ! is_admin() && $this->path ) {
            $results = Links_Helper::get_by_slug( $this->path, 'OBJECT' );
        }

        if ( empty( $results ) ) {
            return;
        }

        $id = intval( $results->id );

        // Track click on link
        new Clickwhale_Click_Track( $id );

        // Set headers
        $nofollow  = '';
        $sponsored = '';
        $sep       = '';

        if ( $results->nofollow ) {
            $nofollow = 'noindex, nofollow';
        }

        if ( $results->sponsored ) {
            $sponsored = 'sponsored';
        }

        if ( $results->nofollow && $results->sponsored ) {
            $sep = ', ';
        }

        if ( $results->nofollow || $results->sponsored ) {
            header( 'X-Robots-Tag: ' . $nofollow . $sep . $sponsored );
        }

		$link_url = apply_filters( 'clickwhale_url_params', $results->url, $id );
		wp_redirect( $link_url, $results->redirection );
        exit;
	}

	public function add_target_to_clickwhale_link( $content ) {

		return preg_replace_callback( '/<a(.*?)?href=[\'"]?[\'"]?(.*?)?>/i', function ( $m ) {
			$link = null;
			$tpl  = array_shift( $m );
			$hrf  = $m[1] ?? null;

			// if link has target attr
			if ( preg_match( '/target=[\'"]?(.*?)[\'"]?/i', $tpl ) ) {
				return $tpl;
			}

			// if link is an anchor
			if ( trim( $hrf ) && 0 === strpos( $hrf, '#' ) ) {
				return $tpl;
			}

			// if link with params
			if ( strpos( $hrf, '?' ) !== false ) {
				return $tpl;
			}

			// get href attr
			if ( preg_match( '/href=["\']?([^"\'>]+)["\']?/', $tpl, $matches ) ) {
				// remove waste from href
				$href_search = array(
					'href="' . get_bloginfo( 'url' )       => '',
					'href="' . get_bloginfo( 'url' ) . '/' => '',
					'href="/'                              => '',
					'/"'                                   => '',
					'"'                                    => '',
					"href='" . get_bloginfo( 'url' )       => '',
					"href='" . get_bloginfo( 'url' ) . "/" => '',
					"href='/"                              => '',
					"/'"                                   => '',
					"'"                                    => '',
				);

				// if is not Clickwhale Link
				$link = Links_Helper::get_by_slug( strtr( $matches[0], $href_search ) );
				if ( ! $link ) {
					return $tpl;
				}
			}

			$tpl = preg_replace_callback( '/href=/i', function ( $m3 ) {
				return sprintf( 'target="_blank" %s', array_shift( $m3 ) );
			}, $tpl );

			if ( $link['sponsored'] ) {
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
