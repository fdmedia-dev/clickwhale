<?php

class Clickwhale_Public_Linkpage {
	private $post;

	private $options;

	public function __construct( $post ) {
		$this->post    = $post;
		$this->options = get_option( 'clickwhale_general_options' );
	}

	public function get_title() {
		return wp_kses( $this->post->post_title, wp_kses_allowed_html( 'post' ) );
	}

	public function get_description() {
		return isset( $this->post->post_content['description'] ) ? wp_kses( $this->post->post_content['description'], wp_kses_allowed_html( 'post' ) ) : '';
	}

	public function get_logo() {
		$default = get_bloginfo( 'url' ) . '/wp-content/plugins/clickwhale/admin/images/click-whale.svg';
		$custom  = wp_get_attachment_image_src( $this->post->post_content['logo'], 'medium' );

		return isset( $this->post->post_content['logo'] ) ? $custom[0] : $default;
	}

	public function get_links() {
		global $wpdb;
		$html  = '';
		$links = maybe_unserialize( $this->post->post_content['links'] );
		if ( $links ) {
			foreach ( $links as $link ) {
				$link_data = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}clickwhale_links WHERE id=%d", $link['id'] ), ARRAY_A );
				$url       = get_bloginfo( 'url' ) . '/' . $this->options['slug'] . '/' . $link_data['slug'];
				if ( $link_data ) {
					$link_title = $link['title'] ? $link['title'] : $link_data['title'];
					$html       .= '<a href="' . esc_url( $url ) . '">' . esc_html( $link_title ) . '</a>';
				}
			}
		}

		return $html;
	}

	public function get_styles() {
		$style = '';

		$styles = maybe_unserialize( $this->post->post_content['styles'] );
		if ( $styles ) {
			$style .= ':root{ --page-bg-color: ' . $styles['bg_color'] . '; --text-color: ' . $styles['text_color'] . '; --link-bg-color: ' . $styles['link_bg_color'] . '; --link-color: ' . $styles['link_color'] . '; --link-bg-hover: ' . $styles['link_bg_color_hover'] . '; --link-hover: ' . $styles['link_color_hover'] . ';  }';
		}

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
		$social_html = '';
		$social_svg  = $this->socials_svg();
		$socials     = maybe_unserialize( $this->post->post_content['social'] );
		if ( $socials ) {
			foreach ( $socials as $k => $v ) {
				$social_html .= ' <li><a href = "' . $v . '" target = "_blank" > ' . $social_svg[ $k ] . '</a ></li > ';
			}
		}

		return $social_html;
	}

	public function get_copyright() {
		$ref = isset( $this->options['affiliate_id'] ) ? '?ref=' . $this->options['affiliate_id'] : '';

		return '<a class="linkpage-public--copyright" href="https://clickwhale.pro/' . $ref . '">Clickwhale Copyright</a>';
	}
}