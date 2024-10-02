<?php
namespace clickwhale\includes\front;

use clickwhale\includes\content_templates\Clickwhale_Linkpage_Content_Templates;
use DOMDocument;
use DOMException;
use DOMXPath;
use WP_Post;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Clickwhale_Public_Linkpage {

    /**
     * @var WP_Post $post;
     */
	private $post;

    /**
     * @var false|mixed|null
     */
	private $linkpages_options;

    /**
     * @var false|mixed|null
     */
	private $other_options;

    /**
     * @var mixed|string
     */
	private $data;

    /**
     * @var false|mixed|string
     */
	private $social;

    /**
     * @var mixed|string
     */
	private $links;

    /**
     * @var mixed|string
     */
	private $styles;

    /**
     * @var string
     */
	private $logo;

	public function __construct( $post ) {
		$this->post              = $post;
		$this->linkpages_options = get_option( 'clickwhale_linkpages_options' );
		$this->other_options     = get_option( 'clickwhale_other_options' );
		$this->data              = maybe_unserialize( $this->post->linkpage );
		$this->links             = maybe_unserialize( $this->post->linkpage['links'] );
		$this->styles            = maybe_unserialize( $this->post->linkpage['styles'] );
		$this->social            = isset( $this->data['social'] ) ? maybe_unserialize( $this->data['social'] ) : false;
		$this->logo              = trailingslashit( CLICKWHALE_PUBLIC_ASSETS_DIR ) . 'images/whale.svg';

		add_action( 'wp_before_admin_bar_render', [ $this, 'admin_bar_render' ], 25 );
		add_action( 'print_footer_scripts', [ $this, 'admin_scripts' ] );

		// Change Robots Tag
		if ( ( get_option( 'blog_public' ) || get_option( 'blog_public' ) === '1' ) ) {
			add_filter( 'wp_robots', [ $this, 'robots_tag' ], PHP_INT_MAX );
		}

		// Meta tag manipulation
		add_action( 'wp_head', [ $this, 'start_wp_head_buffer' ], 0 );
		add_action( 'wp_head', [ $this, 'end_wp_head_buffer' ], PHP_INT_MAX );

		// Remove Yoast SEO Data
		add_filter( 'wpseo_json_ld_output', '__return_false' );
		add_filter( 'body_class', [ $this, 'linkpage_classes' ] );
	}

	/**
	 * @param $robots
	 *
	 * @return mixed
	 * @since 1.1.0
	 *
	 */
	public function robots_tag( $robots ) {
		$robotsData = isset( $this->social['seo']['robots'] ) ? maybe_unserialize( $this->social['seo']['robots'] ) : false;

		// by default we need set this values because without SEO Plugin it can be empty
		$robots['index']  = ! isset( $this->social['seo']['robots']['noindex'] );
		$robots['follow'] = ! isset( $this->social['seo']['robots']['nofollow'] );

		if ( $robotsData ) {

			// replace if needed
			$robots['index']  = ! in_array( 'noindex', $robotsData );
			$robots['follow'] = ! in_array( 'nofollow', $robotsData );

			foreach ( $robotsData as $robot ) {
				if ( 'nosnippet' === $robot ) {
					unset( $robots['max-snippet'] );
				}
				$robots[ $robot ] = true;
			}

			if ( in_array( 'noindex', $robotsData ) && in_array( 'nofollow', $robotsData ) ) {
				unset( $robots['max-snippet'] );
			}
		}

		return $robots;
	}

	/**
	 * @return void
	 * @since 1.1.0
	 *
	 */
	public function start_wp_head_buffer() {
		ob_start();
	}

	/**
	 * @return void
	 * @throws DOMException
	 * @since 1.1.0
	 *
	 */
	public function end_wp_head_buffer() {
		$in = ob_get_clean();

		// replace <title>
		$in = preg_replace( '/<title>(.*)<\/title>/i', '<title>' . $this->get_og_defaults()['title'] . '</title>',
			$in );

		$dom = new DOMDocument;
		@$dom->loadHTML( $in, LIBXML_HTML_NODEFDTD );
		$xpath = new DOMXPath( $dom );

		$tags = array(
			'description'   => array(
				'name'    => 'description',
				'content' => esc_attr( $this->get_og_defaults()['description'] )
			),
			'ogtitle'       => array(
				'name'    => 'og:title',
				'content' =>
					isset( $this->social['seo']['ogtitle'] ) && $this->social['seo']['ogtitle']
						? esc_attr( $this->social['seo']['ogtitle'] )
						: esc_attr( $this->get_og_defaults()['title'] )
			),
			'ogurl'         => array(
				'name'    => 'og:url',
				'content' => esc_url( home_url( '/' . $this->data['slug'] . '/' ) )
			),
			'ogimage'       => array(
				'name'    => 'og:image',
				'content' =>
					isset( $this->social['seo']['ogimage'] ) && $this->social['seo']['ogimage']
						? esc_url( wp_get_attachment_image_src( $this->social['seo']['ogimage'], 'full' )[0] )
						: $this->get_og_defaults()['image'],
			),
			'ogtype'        => array(
				'name'    => 'og:type',
				'content' => 'website'
			),
			'ogdescription' => array(
				'name'    => 'og:description',
				'content' =>
					isset( $this->social['seo']['ogdescription'] ) && $this->social['seo']['ogdescription']
						? esc_attr( $this->social['seo']['ogdescription'] )
						: esc_attr( $this->get_og_defaults()['description'] )
			),
			'locale'        => array(
				'name'    => 'og:locale',
				'content' => get_bloginfo( 'language' )
			),
		);

		foreach ( $tags as $k => $v ) {
			if ( $v['content'] ) {

				$metaTag = $xpath->query( '//meta[@property="' . $v['name'] . '"]' )[0];
				if ( $metaTag ) {
					$metaTag->setAttribute( 'content', $v['content'] );
				} else {
					$newMetaTag = $dom->createElement( "meta" );
					$newMetaTag->setAttribute( "name", $v['name'] );
					$newMetaTag->setAttribute( "content", $v['content'] );
					$dom->appendChild( $newMetaTag );
				}

			}
		}

		$in = $dom->saveHTML();

		$in = str_replace( array( '<html>', '</html>', '<head>', '</head>' ), '', $in );

		echo $in;
	}

	/**
	 * Get Open Graph Data
	 * @return mixed|string
	 * @since 1.1.1
	 *
	 */
	private function get_og_defaults() {
		return array(
			'title'       =>
				isset( $this->social['seo']['title'] ) && $this->social['seo']['title']
					? $this->social['seo']['title']
					: wp_kses( wp_unslash( $this->post->post_title ), wp_kses_allowed_html( 'post' ) ),
			'description' =>
				isset( $this->social['seo']['description'] ) && $this->social['seo']['description']
					? $this->social['seo']['description']
					: get_bloginfo( 'description' ),
			'image'       =>
				isset( $this->post->linkpage['logo'] ) && $this->post->linkpage['logo']
					? esc_url( wp_get_attachment_image_src( $this->post->linkpage['logo'], 'full' )[0] )
					: esc_url( $this->logo )
		);
	}

	/**
	 * Get link Page Title from DB
	 * @return string
	 */
	public function get_title() {
		return wp_kses( wp_unslash( $this->post->post_title ), wp_kses_allowed_html( 'post' ) );
	}

	/**
	 * Get LInk Page Description from DB
	 * @return string
	 */
	public function get_desc() {
		return isset( $this->post->linkpage['description'] ) ? wp_kses( wp_unslash( $this->post->linkpage['description'] ),
			wp_kses_allowed_html( 'post' ) ) : '';
	}

	public function get_logo() {
		$img     = $this->logo;
		$srcset  = '';
		$classes = [];
		if ( isset( $this->styles['logo_style'] ) ) {
			$classes[] = $this->styles['logo_style'];
		}
		if ( isset( $this->styles['logo_shadow'] ) ) {
			$classes[] = 'with-shadow';
		}
		if ( isset( $this->post->linkpage['logo'] ) && $this->post->linkpage['logo'] ) {
			$srcset = wp_get_attachment_image_srcset( $this->post->linkpage['logo'] );
			$img    = wp_get_attachment_image_url( $this->post->linkpage['logo'] );
		}
		$class = implode( ' ', $classes );

		return '<img class="' . $class . '" src="' . esc_url( $img ) . '" srcset="' . $srcset . '" alt="' . esc_attr( $this->get_title() ) . '">';
	}

	public function get_links(): string {
		$html     = '';
		$template = new Clickwhale_Linkpage_Content_Templates();
		$target   = isset( $this->linkpages_options['linkpage_links_target'] ) ? '_blank' : '_self';

		if ( $this->links ) {
			foreach ( $this->links as $link ) {

				if ( ! isset( $link['is_active'] ) ) {
					continue;
				}

				$html .= $template->get_template(
					$link['type'],
					false,
					true,
					array( 'data' => $link, 'target' => $target, 'post' => $this->data )
				);
			}
		}

		return $html;
	}

	public function get_styles() {
		$style = '';

		if ( $this->styles ) {
			$style .= ':root{ --clickwhale-page-bg-color: ' . $this->styles['bg_color'] . '; --clickwhale-text-color: ' . $this->styles['text_color'] . '; --clickwhale-link-bg-color: ' . $this->styles['link_bg_color'] . '; --clickwhale-link-color: ' . $this->styles['link_color'] . '; --clickwhale-link-bg-hover: ' . $this->styles['link_bg_color_hover'] . '; --clickwhale-link-hover: ' . $this->styles['link_color_hover'] . ';  }';
		}

		$style = apply_filters( 'clickwhale_linkpage_styles', $style, $this->styles );

		return ' <style>' . $style . ' </style > ';
	}

	private function socials_svg() {
		return array(
			'facebook'  => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 49.652 49.652" fill="currentColor"><path data-name="Pfad 739" d="M24.826 0a24.826 24.826 0 1 0 24.826 24.826A24.854 24.854 0 0 0 24.826 0ZM31 25.7h-4.039v14.4h-5.985V25.7h-2.845v-5.088h2.845v-3.291c0-2.357 1.12-6.04 6.04-6.04l4.435.017v4.939h-3.219a1.219 1.219 0 0 0-1.269 1.386v2.99h4.56Z"/></svg>',
			'instagram' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 49.652 49.652" fill="currentColor"><path data-name="Pfad 740" d="M24.825 29.8a4.962 4.962 0 1 0-4.968-4.971 4.978 4.978 0 0 0 4.968 4.971Z"/><path data-name="Pfad 741" d="M35.678 18.746V13.96h-.623l-4.164.013.016 4.787Z"/><path data-name="Pfad 742" d="M24.826 0a24.826 24.826 0 1 0 24.826 24.826A24.854 24.854 0 0 0 24.826 0Zm14.119 21.929v11.56a5.463 5.463 0 0 1-5.457 5.458H16.164a5.462 5.462 0 0 1-5.457-5.458V16.165a5.462 5.462 0 0 1 5.457-5.457h17.323a5.463 5.463 0 0 1 5.458 5.457Z"/><path data-name="Pfad 743" d="M32.549 24.826a7.723 7.723 0 1 1-14.877-2.9h-4.215v11.56a2.706 2.706 0 0 0 2.706 2.7h17.323a2.707 2.707 0 0 0 2.706-2.7V21.929h-4.217a7.617 7.617 0 0 1 .574 2.9Z"/></svg>',
			'twitter'   => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 49.652 49.652" fill="currentColor"><path data-name="Pfad 738" d="M24.826 0a24.826 24.826 0 1 0 24.826 24.826A24.854 24.854 0 0 0 24.826 0ZM35.9 19.144c.011.246.017.494.017.742 0 7.551-5.746 16.255-16.259 16.255a16.163 16.163 0 0 1-8.758-2.565 11.538 11.538 0 0 0 8.46-2.366 5.72 5.72 0 0 1-5.338-3.969 5.736 5.736 0 0 0 2.58-.1 5.715 5.715 0 0 1-4.583-5.6v-.072a5.691 5.691 0 0 0 2.587.714 5.723 5.723 0 0 1-1.768-7.629 16.225 16.225 0 0 0 11.777 5.972 5.718 5.718 0 0 1 9.737-5.213 11.406 11.406 0 0 0 3.63-1.387 5.74 5.74 0 0 1-2.516 3.162 11.36 11.36 0 0 0 3.282-.9 11.494 11.494 0 0 1-2.848 2.956Z"/></svg>'
		);
	}

	public function get_socails() {
		if ( ! isset( $this->post->linkpage['social'] ) ) {
			return false;
		}

		$social_html = '';
		$social_svg  = $this->socials_svg();
		$socials     = maybe_unserialize( $this->post->linkpage['social'] );
		if ( isset( $socials['profiles'] ) ) {
			foreach ( $socials['profiles'] as $k => $v ) {
				if ( $v ) {
					$social_html .= ' <li><a href = "' . $v . '" target = "_blank" > ' . $social_svg[ $k ] . '</a ></li > ';
				}
			}
		}

		return $social_html;
	}

	public function get_credits_link() {
		$img = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 239.4 54.9" xml:space="preserve" fill="currentColor"><path d="M11.2 54.9c2.9.1 5.7-1 7.6-3.1.5-.5.8-1.2.8-2 0-1.6-1.3-2.9-2.9-2.9-.8 0-1.6.4-2.2 1-.8 1-2 1.5-3.3 1.5-2.7 0-4.9-2.2-4.9-4.9v-.2c-.1-2.7 1.9-5 4.6-5.2h.2c1.3 0 2.4.6 3.3 1.5.6.6 1.4 1 2.2 1 1.6 0 3-1.3 3-2.9 0-.7-.3-1.5-.8-2-2-2.1-4.8-3.2-7.6-3.1C4.9 33.5 0 37.9 0 44.2c0 6.4 4.9 10.7 11.2 10.7zm19.2-.3h8.4c1.5 0 2.7-1.2 2.7-2.7 0-1.5-1.2-2.7-2.7-2.7h-5.4V36.7c0-1.7-1.4-3.1-3.1-3.1-1.7 0-3.1 1.4-3.1 3.1v14.5c-.2 1.6 1 3.1 2.6 3.3.1.1.3.1.6.1zm21.1.3c1.7 0 3.1-1.4 3.1-3.1v-15c0-1.7-1.4-3.1-3.1-3.1-1.7 0-3.1 1.4-3.1 3.1v15c-.1 1.7 1.3 3.1 3.1 3.1zm21.2 0c2.9.1 5.7-1 7.6-3.1.5-.5.8-1.2.8-2 0-1.6-1.3-2.9-2.9-2.9-.8 0-1.6.4-2.2 1-.8 1-2 1.5-3.3 1.5-2.7 0-4.9-2.2-4.9-4.9v-.2c-.1-2.7 1.9-5 4.6-5.2h.2c1.3 0 2.4.6 3.3 1.5.6.6 1.4 1 2.2 1 1.6 0 3-1.3 3-2.9 0-.7-.3-1.5-.8-2-2-2.1-4.8-3.2-7.6-3.1-6.3 0-11.2 4.3-11.2 10.7 0 6.3 4.9 10.6 11.2 10.6zm33.3-5-5.3-6.4 4.7-5.1c.5-.5.7-1.2.7-1.9 0-1.6-1.3-2.9-2.9-2.9-.8 0-1.6.3-2.2 1l-6.2 7.1v-4.9c0-1.7-1.4-3.1-3.1-3.1-1.7 0-3.1 1.4-3.1 3.1v15c0 1.7 1.4 3.1 3.1 3.1 1.7 0 3.1-1.4 3.1-3.1v-2.9l1.3-1.5 5.2 6.5c.6.7 1.4 1 2.3 1 1.7 0 3-1.4 3-3.1.1-.7-.2-1.4-.6-1.9zm27.1 5c1.8 0 3.5-1.2 4-3l4-14.1c.1-.3.1-.6.1-.9 0-1.8-1.5-3.3-3.3-3.3-1.6 0-2.9 1.1-3.3 2.6l-2.1 10.3-2.7-10.8c-.3-1.3-1.5-2.2-2.8-2.2-1.3 0-2.5.9-2.8 2.2l-2.7 10.8-2.2-10.4c-.3-1.5-1.7-2.6-3.2-2.6-1.8 0-3.3 1.4-3.3 3.2 0 .3 0 .6.1.9l4 14.1c.5 1.8 2.1 3 4 3s3.6-1.3 4-3.2l2-8.8 2 8.8c.6 2 2.3 3.4 4.2 3.4zm31.9 0c1.7 0 3.1-1.4 3.1-3.1v-15c0-1.7-1.4-3.1-3.1-3.1-1.7 0-3.1 1.4-3.1 3.1v4.5h-7.4v-4.5c0-1.7-1.4-3.1-3.1-3.1-1.7 0-3.1 1.4-3.1 3.1v15c0 1.7 1.4 3.1 3.1 3.1 1.7 0 3.1-1.4 3.1-3.1v-5.1h7.4v5.1c0 1.7 1.4 3.1 3.1 3.1zm30.3-4.2-5-13.5c-.8-2.2-2.9-3.6-5.1-3.6-2.3 0-4.4 1.4-5.1 3.6l-5 13.5c-.1.3-.2.7-.2 1.1 0 1.7 1.4 3.1 3.1 3.1 1.4 0 2.6-.9 3-2.2l.3-1.1h7.7l.3 1.1c.4 1.3 1.6 2.2 3 2.2 1.7 0 3.1-1.4 3.1-3.1.1-.4 0-.8-.1-1.1zM183 46.3l2.2-6.9 2.2 6.9H183zm22.5 8.3h8.4c1.5 0 2.7-1.2 2.7-2.7 0-1.5-1.2-2.7-2.7-2.7h-5.4V36.7c0-1.7-1.4-3.1-3.1-3.1-1.7 0-3.1 1.4-3.1 3.1v14.5c-.2 1.6 1 3.1 2.6 3.3.1.1.4.1.6.1zm21.3 0h10.1c1.4 0 2.6-1.2 2.6-2.6 0-1.4-1.2-2.6-2.6-2.6h-7.1v-2.6h6.9c1.4 0 2.6-1.2 2.6-2.6 0-1.4-1.2-2.6-2.6-2.6h-6.9v-2.4h7.1c1.4 0 2.6-1.2 2.6-2.6 0-1.4-1.2-2.6-2.6-2.6h-10.1c-1.6-.2-3.1 1-3.3 2.6v14.7c-.2 1.6 1 3.1 2.6 3.3h.7zM52.3 28.3l3.3-19.1c.1-.7-.3-1.3-1-1.4H54l-5 1.6c-.7.2-1.4-.2-1.6-.8l-2.1-6.9c-.2-.7-.9-1-1.6-.8l-1.7.5c-.7.2-1 .9-.8 1.6l2.1 6.9c.2.7-.2 1.4-.8 1.6l-4.8 1.4c-.7.2-1 .9-.8 1.6.1.2.2.4.3.5l13.1 14.1c.5.5 1.3.5 1.8.1.1-.4.2-.7.2-.9M56.8 30h-.2c-1.1-.3-1.7-1.3-1.5-2.4l1.8-7.7c.3-1.1 1.3-1.7 2.4-1.5 1.1.3 1.7 1.3 1.5 2.4L59 28.5c-.3 1-1.2 1.6-2.2 1.5zM45.9 33.7c-.3 0-.5-.1-.7-.2l-6.3-3.6c-1-.5-1.3-1.8-.8-2.7.5-1 1.8-1.3 2.7-.8l6.3 3.6c1 .5 1.3 1.8.7 2.7-.3.8-1.1 1.1-1.9 1z"/></svg>';

		$credits_link_output = '<a class="linkpage-public--copyright" target="_blank" href="' . apply_filters( 'clickwhale_linkpage_credits_link', 'https://clickwhale.pro/linkpages/' ) . '">Powered by ' . $img . '</a>';

		return apply_filters( 'clickwhale_show_linkpage_credits', $credits_link_output );
	}

	/**
	 * @return void
	 * @since 1.3.0
	 */

	public function admin_bar_render() {
		global $wp_admin_bar;

		$data = $this->post;

		$wp_admin_bar->add_node( array(
				'id'    => 'edit',
				'title' => __( 'Edit Link Page', CLICKWHALE_NAME ),
				'href'  => admin_url( 'admin.php?page=' . CLICKWHALE_SLUG . '-edit-linkpage&id=' . $data->linkpage['id'] ),
			)
		);
	}

	/**
	 * Add body classes
	 *
	 * @param $classes
	 *
	 * @return mixed
	 * @since 1.3.2
	 */
	public static function linkpage_classes( $classes ) {
		$classes[] = 'clickwhale-linkpage';

		return $classes;
	}

	public function get_legals_menu() {
		global $wpdb;

		$table_links_meta = $wpdb->prefix . 'clickwhale_meta';
		$linkpage_id      = $this->data['id'];

		$legals_menu_id = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $table_links_meta WHERE linkpage_id=%s AND meta_key='legals_menu_id'",
				$linkpage_id ),
			ARRAY_A
		);

		if ( ! $legals_menu_id || ! $legals_menu_id['meta_value'] ) {
			return false;
		}

		return wp_nav_menu( array(
			'menu'            => $legals_menu_id['meta_value'],
			'menu_class'      => 'linkpage-menu',
			'container'       => 'div',
			'container_class' => 'linkpage-menu--wrap',
			'fallback_cb'     => false,
			'depth'           => 0
		) );
	}

	public function admin_scripts() {
		$nonce = wp_create_nonce( 'track_custom_link' );
		?>
        <script type='text/javascript'>
            jQuery(document).ready(function() {
                jQuery('.linkpage-public--links').on('click', '.cw-track', function(e) {
                    let link = jQuery(this);

                    jQuery.post(clickwhale_public.ajaxurl, {
                        'security': '<?php echo $nonce ?>',
                        'action': 'clickwhale/public/track_custom_link',
                        'id': link.data('id'),
                    }, function(response) {});
                });
            });
        </script>
		<?php
	}
}
