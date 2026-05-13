<?php

namespace Clickwhale\Front;

use Clickwhale\Helpers\Helper;
use Clickwhale\ContentTemplates\Clickwhale_Linkpage_Content_Templates;
use WP_Post;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Clickwhale_Public_Linkpage {

    /**
     * @var WP_Post
     */
    private WP_Post $post;

    /**
     * @var array
     */
    private array $data;

    /**
     * @var array
     */
    private array $social;

    /**
     * @var array
     */
    private array $links;

    /**
     * @var array
     */
    private array $styles;

    /**
     * @var string
     */
    private string $logo;

    public function __construct( $post ) {
        $this->post   = $post;
        $this->data   = maybe_unserialize( $this->post->linkpage );
        $this->links  = (array) maybe_unserialize( $this->data['links'] );
        $this->styles = (array) maybe_unserialize( $this->data['styles'] );
        $this->social = isset( $this->data['social'] ) ? maybe_unserialize( $this->data['social'] ) : false;
        $this->logo   = trailingslashit( CLICKWHALE_PUBLIC_ASSETS_DIR ) . 'images/whale.svg';

        add_action( 'wp_before_admin_bar_render', array( $this, 'admin_bar_render' ), 25 );
        add_action( 'print_footer_scripts', array( $this, 'admin_scripts' ) );

        // Change Robots Tag
        if ( ( get_option( 'blog_public' ) || get_option( 'blog_public' ) === '1' ) ) {
            add_filter( 'wp_robots', array( $this, 'robots_tag' ), PHP_INT_MAX );
        }

        // Meta tag manipulation
        add_filter( 'pre_get_document_title', array( $this, 'get_page_title' ), PHP_INT_MAX );
        add_action( 'wp_head', array( $this, 'render_meta_tags' ), 1 );

        // Remove Yoast SEO Data
        add_filter( 'wpseo_json_ld_output', '__return_false' );
        add_filter( 'body_class', array( $this, 'linkpage_classes' ) );

        // Replace site icon for link page
        add_action( 'wp_head', array( $this, 'replace_wp_site_icon' ), 1 );
    }

    /**
     * @param array $robots
     *
     * @return array
     * @since 1.1.0
     *
     */
    public function robots_tag( array $robots ): array {
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
     * @return string
     */
    public function get_page_title(): string {
        return (string) $this->get_og_defaults()['title'];
    }

    /**
     * @return void
     */
    public function render_meta_tags() {
        if ( ! empty( $this->social['seo']['ogimage'] ) ) {
            $src = wp_get_attachment_image_src( $this->social['seo']['ogimage'], 'full' );
        } else {
            $src = false;
        }

        $tags = array(
                'description'    => array(
                        'name'    => 'description',
                        'content' => esc_attr( $this->get_og_defaults()['description'] )
                ),
                'og:title'       => array(
                        'content' =>
                                isset( $this->social['seo']['ogtitle'] ) && $this->social['seo']['ogtitle']
                                        ? esc_attr( $this->social['seo']['ogtitle'] )
                                        : esc_attr( $this->get_og_defaults()['title'] )
                ),
                'og:url'         => array(
                        'content' => $this->get_url()
                ),
                'og:image'       => array(
                        'content' =>
                                is_array( $src ) ? esc_url( $src[0] ) : $this->get_og_defaults()['image']
                ),
                'og:type'        => array(
                        'content' => 'website'
                ),
                'og:description' => array(
                        'content' =>
                                isset( $this->social['seo']['ogdescription'] ) && $this->social['seo']['ogdescription']
                                        ? esc_attr( $this->social['seo']['ogdescription'] )
                                        : esc_attr( $this->get_og_defaults()['description'] )
                ),
                'og:locale'      => array(
                        'content' => get_bloginfo( 'language' )
                ),
        );

        foreach ( $tags as $name => $v ) {
            if ( $v['content'] ) {
                echo '<meta name="' . esc_attr( $name ) . '" content="' . esc_attr( $v['content'] ) . '" />' . "\n";
            }
        }
    }

    /**
     * Get Open Graph Data
     * @return array
     * @since 1.1.1
     *
     */
    private function get_og_defaults(): array {

        // Title
        if ( ! empty( $this->social['seo']['title'] ) ) {
            $title = $this->social['seo']['title'];
        } else {
            $title = wp_kses( wp_unslash( $this->post->post_title ), wp_kses_allowed_html( 'post' ) );
        }

        // Description
        if ( ! empty( $this->social['seo']['description'] ) ) {
            $description = $this->social['seo']['description'];
        } else {
            $description = get_bloginfo( 'description' );
        }

        // Image
        $image = esc_url( $this->logo );

        if ( ! empty( $this->data['logo'] ) ) {
            $img_data = wp_get_attachment_image_src( $this->data['logo'], 'full' );

            if ( is_array( $img_data ) ) {
                $img_url = esc_url( $img_data[0] );

                if ( Helper::get_media_file_path( $img_url ) ) {
                    $image = $img_url;
                }
            }
        }

        return array(
                'title'       => $title,
                'description' => $description,
                'image'       => $image
        );
    }

    /**
     * Get link page URL
     * @return string
     */
    public function get_url(): string {
        return esc_url( trailingslashit( home_url( $this->data['slug'] ) ) );
    }

    /**
     * Get link page Title
     * @return string
     */
    public function get_title(): string {
        return wp_unslash( $this->post->post_title );
    }

    /**
     * Get link page Description
     * @return string
     */
    public function get_desc(): string {
        return isset( $this->data['description'] ) ? wp_unslash( $this->data['description'] ) : '';
    }

    /**
     * Get link page Logo
     * @return string
     */
    public function get_logo(): string {
        if ( empty( $this->data['logo'] ) ) {
            return '';
        }

        $image_url = wp_get_attachment_image_url( $this->data['logo'] );

        if ( ! $image_url ) {
            return '';
        }

        $image_url = esc_url( $image_url );

        if ( ! Helper::get_media_file_path( $image_url ) ) {
            return '';
        }

        $srcset = wp_get_attachment_image_srcset( $this->data['logo'] );

        if ( empty( $srcset ) ) {
            $srcset = '';
        }

        $classes = array();

        if ( isset( $this->styles['logo_style'] ) ) {
            $classes[] = $this->styles['logo_style'];
        }

        if ( isset( $this->styles['logo_shadow'] ) ) {
            $classes[] = 'with-shadow';
        }

        return '<div class="cw-linkpage-public--logo"><img class="' . implode( ' ', $classes ) . '" src="' . $image_url . '" srcset="' . $srcset . '" alt="' . esc_attr( $this->get_title() ) . '" /></div>';
    }

    /**
     * @return string
     */
    public function get_links(): string {
        $html     = '';
        $template = new Clickwhale_Linkpage_Content_Templates();

        if ( $this->links ) {
            foreach ( $this->links as $item ) {

                if ( ! isset( $item['is_active'] ) ) {
                    continue;
                }

                $type = $item['type'];
                $args = array(
                        'data' => $item,
                        'post' => $this->data
                );

                if ( in_array( $type, array( 'cw_custom_link', 'post' ), true ) ) {
                    $args['target'] = '_blank';
                }

                $html .= $template->get_template(
                        $type,
                        false,
                        true,
                        $args
                );
            }
        }

        return $html;
    }

    public function get_styles(): string {
        $style = '';

        if ( $this->styles ) {
            $default_styles = clickwhale()->linkpage->get_defaults()['styles'];

            // Validate colors
            // `$text_color`
            // `$link_color`
            // `$link_color_hover`
            foreach ( array( 'text_color', 'link_color', 'link_color_hover' ) as $color ) {
                if ( Helper::validate_hex_color( $this->styles[ $color ] ) ) {
                    $$color = $this->styles[ $color ];
                } else {
                    $$color = $default_styles[ $color ];
                }
            }

            $style .= ' :root{';
            $style .= '--clickwhale-text-color: ' . $text_color . ';';
            $style .= '--clickwhale-page-bg-color: ' . $this->styles['bg_color'] . ';';
            $style .= '--clickwhale-link-bg-color: ' . $this->styles['link_bg_color'] . ';';
            $style .= '--clickwhale-link-bg-hover: ' . $this->styles['link_bg_color_hover'] . ';';
            $style .= '--clickwhale-link-color: ' . $link_color . ';';
            $style .= '--clickwhale-link-hover: ' . $link_color_hover . ';';
            $style .= '}';
        }

        return apply_filters( 'clickwhale_linkpage_styles', $style, $this->styles );
    }

    public function get_credits_link() {
        $linkpages_options = get_option( 'clickwhale_linkpages_options' );
        if ( empty( $linkpages_options['show_linkpage_credits'] ) ) {
            return '';
        }

        $img = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 239.4 54.9" xml:space="preserve" fill="currentColor"><path d="M11.2 54.9c2.9.1 5.7-1 7.6-3.1.5-.5.8-1.2.8-2 0-1.6-1.3-2.9-2.9-2.9-.8 0-1.6.4-2.2 1-.8 1-2 1.5-3.3 1.5-2.7 0-4.9-2.2-4.9-4.9v-.2c-.1-2.7 1.9-5 4.6-5.2h.2c1.3 0 2.4.6 3.3 1.5.6.6 1.4 1 2.2 1 1.6 0 3-1.3 3-2.9 0-.7-.3-1.5-.8-2-2-2.1-4.8-3.2-7.6-3.1C4.9 33.5 0 37.9 0 44.2c0 6.4 4.9 10.7 11.2 10.7zm19.2-.3h8.4c1.5 0 2.7-1.2 2.7-2.7 0-1.5-1.2-2.7-2.7-2.7h-5.4V36.7c0-1.7-1.4-3.1-3.1-3.1-1.7 0-3.1 1.4-3.1 3.1v14.5c-.2 1.6 1 3.1 2.6 3.3.1.1.3.1.6.1zm21.1.3c1.7 0 3.1-1.4 3.1-3.1v-15c0-1.7-1.4-3.1-3.1-3.1-1.7 0-3.1 1.4-3.1 3.1v15c-.1 1.7 1.3 3.1 3.1 3.1zm21.2 0c2.9.1 5.7-1 7.6-3.1.5-.5.8-1.2.8-2 0-1.6-1.3-2.9-2.9-2.9-.8 0-1.6.4-2.2 1-.8 1-2 1.5-3.3 1.5-2.7 0-4.9-2.2-4.9-4.9v-.2c-.1-2.7 1.9-5 4.6-5.2h.2c1.3 0 2.4.6 3.3 1.5.6.6 1.4 1 2.2 1 1.6 0 3-1.3 3-2.9 0-.7-.3-1.5-.8-2-2-2.1-4.8-3.2-7.6-3.1-6.3 0-11.2 4.3-11.2 10.7 0 6.3 4.9 10.6 11.2 10.6zm33.3-5-5.3-6.4 4.7-5.1c.5-.5.7-1.2.7-1.9 0-1.6-1.3-2.9-2.9-2.9-.8 0-1.6.3-2.2 1l-6.2 7.1v-4.9c0-1.7-1.4-3.1-3.1-3.1-1.7 0-3.1 1.4-3.1 3.1v15c0 1.7 1.4 3.1 3.1 3.1 1.7 0 3.1-1.4 3.1-3.1v-2.9l1.3-1.5 5.2 6.5c.6.7 1.4 1 2.3 1 1.7 0 3-1.4 3-3.1.1-.7-.2-1.4-.6-1.9zm27.1 5c1.8 0 3.5-1.2 4-3l4-14.1c.1-.3.1-.6.1-.9 0-1.8-1.5-3.3-3.3-3.3-1.6 0-2.9 1.1-3.3 2.6l-2.1 10.3-2.7-10.8c-.3-1.3-1.5-2.2-2.8-2.2-1.3 0-2.5.9-2.8 2.2l-2.7 10.8-2.2-10.4c-.3-1.5-1.7-2.6-3.2-2.6-1.8 0-3.3 1.4-3.3 3.2 0 .3 0 .6.1.9l4 14.1c.5 1.8 2.1 3 4 3s3.6-1.3 4-3.2l2-8.8 2 8.8c.6 2 2.3 3.4 4.2 3.4zm31.9 0c1.7 0 3.1-1.4 3.1-3.1v-15c0-1.7-1.4-3.1-3.1-3.1-1.7 0-3.1 1.4-3.1 3.1v4.5h-7.4v-4.5c0-1.7-1.4-3.1-3.1-3.1-1.7 0-3.1 1.4-3.1 3.1v15c0 1.7 1.4 3.1 3.1 3.1 1.7 0 3.1-1.4 3.1-3.1v-5.1h7.4v5.1c0 1.7 1.4 3.1 3.1 3.1zm30.3-4.2-5-13.5c-.8-2.2-2.9-3.6-5.1-3.6-2.3 0-4.4 1.4-5.1 3.6l-5 13.5c-.1.3-.2.7-.2 1.1 0 1.7 1.4 3.1 3.1 3.1 1.4 0 2.6-.9 3-2.2l.3-1.1h7.7l.3 1.1c.4 1.3 1.6 2.2 3 2.2 1.7 0 3.1-1.4 3.1-3.1.1-.4 0-.8-.1-1.1zM183 46.3l2.2-6.9 2.2 6.9H183zm22.5 8.3h8.4c1.5 0 2.7-1.2 2.7-2.7 0-1.5-1.2-2.7-2.7-2.7h-5.4V36.7c0-1.7-1.4-3.1-3.1-3.1-1.7 0-3.1 1.4-3.1 3.1v14.5c-.2 1.6 1 3.1 2.6 3.3.1.1.4.1.6.1zm21.3 0h10.1c1.4 0 2.6-1.2 2.6-2.6 0-1.4-1.2-2.6-2.6-2.6h-7.1v-2.6h6.9c1.4 0 2.6-1.2 2.6-2.6 0-1.4-1.2-2.6-2.6-2.6h-6.9v-2.4h7.1c1.4 0 2.6-1.2 2.6-2.6 0-1.4-1.2-2.6-2.6-2.6h-10.1c-1.6-.2-3.1 1-3.3 2.6v14.7c-.2 1.6 1 3.1 2.6 3.3h.7zM52.3 28.3l3.3-19.1c.1-.7-.3-1.3-1-1.4H54l-5 1.6c-.7.2-1.4-.2-1.6-.8l-2.1-6.9c-.2-.7-.9-1-1.6-.8l-1.7.5c-.7.2-1 .9-.8 1.6l2.1 6.9c.2.7-.2 1.4-.8 1.6l-4.8 1.4c-.7.2-1 .9-.8 1.6.1.2.2.4.3.5l13.1 14.1c.5.5 1.3.5 1.8.1.1-.4.2-.7.2-.9M56.8 30h-.2c-1.1-.3-1.7-1.3-1.5-2.4l1.8-7.7c.3-1.1 1.3-1.7 2.4-1.5 1.1.3 1.7 1.3 1.5 2.4L59 28.5c-.3 1-1.2 1.6-2.2 1.5zM45.9 33.7c-.3 0-.5-.1-.7-.2l-6.3-3.6c-1-.5-1.3-1.8-.8-2.7.5-1 1.8-1.3 2.7-.8l6.3 3.6c1 .5 1.3 1.8.7 2.7-.3.8-1.1 1.1-1.9 1z"/></svg>';

        $link = 'https://clickwhale.pro/linkpages/';
        $utm  = '?utm_source=users&utm_medium=link_page&utm_campaign=ClickWhale+Users+Link+Pages&utm_content=copyright_link';

        return sprintf(
                '<a class="cw-linkpage-public--copyright" target="_blank" href="%1$s">%2$s %3$s</a>',
                esc_url( apply_filters( 'clickwhale_linkpage_credits_link', $link, $utm ) ),
                esc_html__( 'Powered by', 'clickwhale' ),
                $img
        );
    }

    /**
     * @return void
     * @since 1.3.0
     */
    public function admin_bar_render() {
        if ( ! clickwhale()->user->is_current_user_role_access_granted() ) {
            return;
        }

        global $wp_admin_bar;
        $wp_admin_bar->add_node( array(
                        'id'    => 'edit',
                        'title' => __( 'Edit Link Page', 'clickwhale' ),
                        'href'  => esc_url( admin_url( 'admin.php?page=' . CLICKWHALE_SLUG . '-edit-linkpage&id=' . $this->data['id'] ) ),
                )
        );
    }

    /**
     * Add body classes
     *
     * @param array $classes
     *
     * @return array
     * @since 1.3.2
     */
    public static function linkpage_classes( array $classes ): array {
        $classes[] = 'clickwhale-linkpage';

        return $classes;
    }

    public function get_legals_menu() {
        global $wpdb;
        $table_links_meta = $wpdb->prefix . 'clickwhale_meta';
        // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
        $legals_menu_id   = $wpdb->get_row(
                $wpdb->prepare(
                        "SELECT * FROM $table_links_meta WHERE linkpage_id=%d AND meta_key='legals_menu_id'",
                        intval( $this->data['id'] )
                ),
                ARRAY_A
        );
        // phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

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

    /**
     * Get link page Site Icon
     *
     * @param int $size
     *
     * @return string
     */
    private function get_favicon( int $size = 512 ): string {
        if ( empty( $this->data['favicon'] ) ) {
            return '';
        }

        $url = wp_get_attachment_image_url( $this->data['favicon'], array( $size, $size ) );

        if ( ! $url ) {
            return '';
        }

        $url = esc_url( $url );

        if ( '' === $url ) {
            return '';
        }

        if ( ! Helper::get_media_file_path( $url ) ) {
            return '';
        }

        return $url;
    }

    /**
     * Replaces wp site icon with the custom icon at Link page
     *
     * @return void
     */
    public function replace_wp_site_icon(): void {
        $default_icon = $this->get_favicon();

        if ( ! $default_icon ) {
            return;
        }

        /** @see \WP_Site_Icon()::site_icon_sizes in 'wp-admin/includes/class-wp-site-icon.php' */
        $sizes     = array(
                32  => '<link rel="icon" href="%s" sizes="32x32" />',
                192 => '<link rel="icon" href="%s" sizes="192x192" />',
                180 => '<link rel="apple-touch-icon" href="%s" />',
                270 => '<meta name="msapplication-TileImage" content="%s" />'
        );
        $meta_tags = array();

        foreach ( $sizes as $size => $template ) {
            $meta_tags[] = sprintf(
                    $template,
                    $this->get_favicon( $size ) ?: $default_icon
            );
        }

        remove_action( 'wp_head', 'wp_site_icon', 99 );

        foreach ( $meta_tags as $meta_tag ) {
            echo wp_kses( $meta_tag, Helper::get_allowed_tags() ) . "\n";
        }
    }

    public function admin_scripts() {
        ?>
        <script type='text/javascript'>
            jQuery(document).ready(function () {
                jQuery('.cw-linkpage-public--links').on('click', '.cw-track', function (e) {
                    let link = jQuery(this);

                    jQuery.post(clickwhale_public.ajaxurl, {
                        'security': <?php echo wp_json_encode( wp_create_nonce( 'track_custom_link' ) ); ?>,
                        'action': 'clickwhale/public/track_custom_link',
                        'id': link.data('id'),
                    }, function (response) {
                    });
                });
            });
        </script>
        <?php
    }
}
