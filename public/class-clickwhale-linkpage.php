<?php

class Clickwhale_Public_Linkpage {
	private $post;
	private $linkpages_options;
	private $other_options;

	public function __construct( $post ) {
		$this->post              = $post;
		$this->linkpages_options = get_option( 'clickwhale_linkpages_options' );
		$this->other_options     = get_option( 'clickwhale_other_options' );
	}

	public function get_title() {
		return wp_kses( wp_unslash( $this->post->post_title ), wp_kses_allowed_html( 'post' ) );
	}

	public function get_description() {
		return isset( $this->post->post_content['description'] ) ? wp_kses( wp_unslash( $this->post->post_content['description'] ), wp_kses_allowed_html( 'post' ) ) : '';
	}

	public function get_logo() {
		if ( isset( $this->post->post_content['logo'] ) && $this->post->post_content['logo'] ) {
			$img = wp_get_attachment_image_url( $this->post->post_content['logo'], 'medium' );
		} else {
			$img = plugin_dir_url( __FILE__ ) . 'images/click-whale.svg';
		}

		return '<img src="' . esc_url( $img ) . '" alt="' . esc_attr( $this->get_title() ) . '">';
	}

	public function get_links() {
		global $wpdb;

		$html  = '';
		$links = maybe_unserialize( $this->post->post_content['links'] );
		if ( $links ) {
			foreach ( $links as $link ) {
				$link_data = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}clickwhale_links WHERE id=%d", $link['id'] ), ARRAY_A );
				$url       = get_bloginfo( 'url' ) . '/' .  $link_data['slug'];
				if ( $link_data ) {
					$link_title = $link['title'] ? $link['title'] : $link_data['title'];
					$target     = isset( $this->linkpages_options['linkpage_links_target'] ) ? '_blank' : '_self';
					$html       .= '<a href="' . esc_url( $url ) . '" target="' . esc_attr( $target ) . '">' . esc_html( wp_unslash( $link_title ) ) . '</a>';
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
				if ( $v ) {
					$social_html .= ' <li><a href = "' . $v . '" target = "_blank" > ' . $social_svg[ $k ] . '</a ></li > ';
				}
			}
		}

		return $social_html;
	}

	public function get_copyright() {
		$ref            = isset( $this->other_options['affiliate_id'] ) && $this->other_options['affiliate_id'] ? '&ref=' . $this->other_options['affiliate_id'] : '';
		$copyright_link = 'https://clickwhale.pro?utm_source=user+site&utm_medium=linkpage&utm_campaign=ClickWhale+-+Free+Version&utm_content=' . get_bloginfo( 'url' );
		$img            = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 239.4 54.9" xml:space="preserve" fill="currentColor"><path d="M11.2 54.9c2.9.1 5.7-1 7.6-3.1.5-.5.8-1.2.8-2 0-1.6-1.3-2.9-2.9-2.9-.8 0-1.6.4-2.2 1-.8 1-2 1.5-3.3 1.5-2.7 0-4.9-2.2-4.9-4.9v-.2c-.1-2.7 1.9-5 4.6-5.2h.2c1.3 0 2.4.6 3.3 1.5.6.6 1.4 1 2.2 1 1.6 0 3-1.3 3-2.9 0-.7-.3-1.5-.8-2-2-2.1-4.8-3.2-7.6-3.1C4.9 33.5 0 37.9 0 44.2c0 6.4 4.9 10.7 11.2 10.7zm19.2-.3h8.4c1.5 0 2.7-1.2 2.7-2.7 0-1.5-1.2-2.7-2.7-2.7h-5.4V36.7c0-1.7-1.4-3.1-3.1-3.1-1.7 0-3.1 1.4-3.1 3.1v14.5c-.2 1.6 1 3.1 2.6 3.3.1.1.3.1.6.1zm21.1.3c1.7 0 3.1-1.4 3.1-3.1v-15c0-1.7-1.4-3.1-3.1-3.1-1.7 0-3.1 1.4-3.1 3.1v15c-.1 1.7 1.3 3.1 3.1 3.1zm21.2 0c2.9.1 5.7-1 7.6-3.1.5-.5.8-1.2.8-2 0-1.6-1.3-2.9-2.9-2.9-.8 0-1.6.4-2.2 1-.8 1-2 1.5-3.3 1.5-2.7 0-4.9-2.2-4.9-4.9v-.2c-.1-2.7 1.9-5 4.6-5.2h.2c1.3 0 2.4.6 3.3 1.5.6.6 1.4 1 2.2 1 1.6 0 3-1.3 3-2.9 0-.7-.3-1.5-.8-2-2-2.1-4.8-3.2-7.6-3.1-6.3 0-11.2 4.3-11.2 10.7 0 6.3 4.9 10.6 11.2 10.6zm33.3-5-5.3-6.4 4.7-5.1c.5-.5.7-1.2.7-1.9 0-1.6-1.3-2.9-2.9-2.9-.8 0-1.6.3-2.2 1l-6.2 7.1v-4.9c0-1.7-1.4-3.1-3.1-3.1-1.7 0-3.1 1.4-3.1 3.1v15c0 1.7 1.4 3.1 3.1 3.1 1.7 0 3.1-1.4 3.1-3.1v-2.9l1.3-1.5 5.2 6.5c.6.7 1.4 1 2.3 1 1.7 0 3-1.4 3-3.1.1-.7-.2-1.4-.6-1.9zm27.1 5c1.8 0 3.5-1.2 4-3l4-14.1c.1-.3.1-.6.1-.9 0-1.8-1.5-3.3-3.3-3.3-1.6 0-2.9 1.1-3.3 2.6l-2.1 10.3-2.7-10.8c-.3-1.3-1.5-2.2-2.8-2.2-1.3 0-2.5.9-2.8 2.2l-2.7 10.8-2.2-10.4c-.3-1.5-1.7-2.6-3.2-2.6-1.8 0-3.3 1.4-3.3 3.2 0 .3 0 .6.1.9l4 14.1c.5 1.8 2.1 3 4 3s3.6-1.3 4-3.2l2-8.8 2 8.8c.6 2 2.3 3.4 4.2 3.4zm31.9 0c1.7 0 3.1-1.4 3.1-3.1v-15c0-1.7-1.4-3.1-3.1-3.1-1.7 0-3.1 1.4-3.1 3.1v4.5h-7.4v-4.5c0-1.7-1.4-3.1-3.1-3.1-1.7 0-3.1 1.4-3.1 3.1v15c0 1.7 1.4 3.1 3.1 3.1 1.7 0 3.1-1.4 3.1-3.1v-5.1h7.4v5.1c0 1.7 1.4 3.1 3.1 3.1zm30.3-4.2-5-13.5c-.8-2.2-2.9-3.6-5.1-3.6-2.3 0-4.4 1.4-5.1 3.6l-5 13.5c-.1.3-.2.7-.2 1.1 0 1.7 1.4 3.1 3.1 3.1 1.4 0 2.6-.9 3-2.2l.3-1.1h7.7l.3 1.1c.4 1.3 1.6 2.2 3 2.2 1.7 0 3.1-1.4 3.1-3.1.1-.4 0-.8-.1-1.1zM183 46.3l2.2-6.9 2.2 6.9H183zm22.5 8.3h8.4c1.5 0 2.7-1.2 2.7-2.7 0-1.5-1.2-2.7-2.7-2.7h-5.4V36.7c0-1.7-1.4-3.1-3.1-3.1-1.7 0-3.1 1.4-3.1 3.1v14.5c-.2 1.6 1 3.1 2.6 3.3.1.1.4.1.6.1zm21.3 0h10.1c1.4 0 2.6-1.2 2.6-2.6 0-1.4-1.2-2.6-2.6-2.6h-7.1v-2.6h6.9c1.4 0 2.6-1.2 2.6-2.6 0-1.4-1.2-2.6-2.6-2.6h-6.9v-2.4h7.1c1.4 0 2.6-1.2 2.6-2.6 0-1.4-1.2-2.6-2.6-2.6h-10.1c-1.6-.2-3.1 1-3.3 2.6v14.7c-.2 1.6 1 3.1 2.6 3.3h.7zM52.3 28.3l3.3-19.1c.1-.7-.3-1.3-1-1.4H54l-5 1.6c-.7.2-1.4-.2-1.6-.8l-2.1-6.9c-.2-.7-.9-1-1.6-.8l-1.7.5c-.7.2-1 .9-.8 1.6l2.1 6.9c.2.7-.2 1.4-.8 1.6l-4.8 1.4c-.7.2-1 .9-.8 1.6.1.2.2.4.3.5l13.1 14.1c.5.5 1.3.5 1.8.1.1-.4.2-.7.2-.9M56.8 30h-.2c-1.1-.3-1.7-1.3-1.5-2.4l1.8-7.7c.3-1.1 1.3-1.7 2.4-1.5 1.1.3 1.7 1.3 1.5 2.4L59 28.5c-.3 1-1.2 1.6-2.2 1.5zM45.9 33.7c-.3 0-.5-.1-.7-.2l-6.3-3.6c-1-.5-1.3-1.8-.8-2.7.5-1 1.8-1.3 2.7-.8l6.3 3.6c1 .5 1.3 1.8.7 2.7-.3.8-1.1 1.1-1.9 1z"/></svg>';

		return '<a class="linkpage-public--copyright" target="_blank" href="' . $copyright_link . $ref . '">Powered by ' . $img . '</a>';
	}
}