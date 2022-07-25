<?php

class Clickwhale_Public_Linkpage {
	private $post;

	public function __construct( $post ) {
		$this->post = $post;
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
		$html    = '';
		$options = get_option( 'clickwhale_general_options' );
		$links   = maybe_unserialize( $this->post->post_content['links'] );
		if ( $links ) {
			foreach ( $links as $link ) {
				$link_data = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}clickwhale_links WHERE id=%d", $link['id'] ), ARRAY_A );
				$url       = get_bloginfo( 'url' ) . '/' . $options['slug'] . '/' . $link_data['slug'];
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
			$style .= '.virtual-page-template-default { background-color: ' . $styles['bg_color'] . '; }';
			$style .= '.linkpage-public--links > a { background-color: ' . $styles['link_bg_color'] . '; color: ' . $styles['link_color'] . ' ; }';
			$style .= '.linkpage-public--links > a:hover { background-color: ' . $styles['link_bg_color_hover'] . '; color: ' . $styles['link_color_hover'] . ' ; }';
		}

		return '<style>' . $style . '</style>';
	}
}