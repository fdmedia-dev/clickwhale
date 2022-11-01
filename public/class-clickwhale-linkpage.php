<?php

class Clickwhale_Public_Linkpage {
	private $post;

	public function __construct( $post ) {
		$this->post            = $post;
		$this->general_options = get_option( 'clickwhale_general_options' );
		$this->other_options   = get_option( 'clickwhale_other_options' );
	}

	public function get_title() {
		return wp_kses( $this->post->post_title, wp_kses_allowed_html( 'post' ) );
	}

	public function get_description() {
		return isset( $this->post->post_content['description'] ) ? wp_kses( $this->post->post_content['description'], wp_kses_allowed_html( 'post' ) ) : '';
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
				$url       = get_bloginfo( 'url' ) . '/' . $this->general_options['slug'] . '/' . $link_data['slug'];
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
				if ( $v ) {
					$social_html .= ' <li><a href = "' . $v . '" target = "_blank" > ' . $social_svg[ $k ] . '</a ></li > ';
				}
			}
		}

		return $social_html;
	}

	public function get_copyright() {
		$ref            = isset( $this->other_options['affiliate_id'] ) && $this->other_options['affiliate_id'] ? '&ref=' . $this->other_options['affiliate_id'] : '';
		$copyright_link = 'https://clickwhale.pro?utm_source=user+site&utm_medium=linkpage&utm_campaign=ClickWhale+-+Free+Version';
		$img            = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 278.064 63.929" width="278px" height="64">
							  <g id="wordmark" transform="translate(-163.324 -658.964)">
							    <path d="M-126.144.432A11.537,11.537,0,0,0-117.288-3.2a3.447,3.447,0,0,0,.9-2.268,3.442,3.442,0,0,0-3.42-3.42,3.351,3.351,0,0,0-2.556,1.152,4.974,4.974,0,0,1-3.78,1.764,5.7,5.7,0,0,1-5.652-6.012A5.7,5.7,0,0,1-126.144-18a4.974,4.974,0,0,1,3.78,1.764,3.351,3.351,0,0,0,2.556,1.152,3.449,3.449,0,0,0,3.456-3.42,3.593,3.593,0,0,0-.936-2.34,11.393,11.393,0,0,0-8.856-3.564c-7.344,0-13.032,5-13.032,12.42C-139.176-4.608-133.488.432-126.144.432ZM-103.9,0h9.72a3.173,3.173,0,0,0,3.168-3.168,3.173,3.173,0,0,0-3.168-3.168h-6.3v-14.4a3.649,3.649,0,0,0-3.636-3.636,3.649,3.649,0,0,0-3.636,3.636V-3.852A3.475,3.475,0,0,0-103.9,0Zm24.48.36A3.649,3.649,0,0,0-75.78-3.276v-17.46a3.649,3.649,0,0,0-3.636-3.636,3.649,3.649,0,0,0-3.636,3.636v17.46A3.649,3.649,0,0,0-79.416.36Zm24.66.072A11.537,11.537,0,0,0-45.9-3.2,3.447,3.447,0,0,0-45-5.472a3.442,3.442,0,0,0-3.42-3.42A3.351,3.351,0,0,0-50.976-7.74a4.974,4.974,0,0,1-3.78,1.764,5.7,5.7,0,0,1-5.652-6.012A5.7,5.7,0,0,1-54.756-18a4.974,4.974,0,0,1,3.78,1.764,3.351,3.351,0,0,0,2.556,1.152,3.449,3.449,0,0,0,3.456-3.42,3.593,3.593,0,0,0-.936-2.34,11.393,11.393,0,0,0-8.856-3.564c-7.344,0-13.032,5-13.032,12.42C-67.788-4.608-62.1.432-54.756.432Zm38.664-5.868-6.192-7.452,5.472-5.868a3.255,3.255,0,0,0,.792-2.2,3.429,3.429,0,0,0-3.384-3.42,3.355,3.355,0,0,0-2.52,1.116l-7.164,8.208v-5.688a3.649,3.649,0,0,0-3.636-3.636,3.649,3.649,0,0,0-3.636,3.636v17.46A3.649,3.649,0,0,0-32.724.36a3.649,3.649,0,0,0,3.636-3.636V-6.624l1.512-1.8,6.084,7.6A3.455,3.455,0,0,0-18.828.36,3.562,3.562,0,0,0-15.3-3.2,3.377,3.377,0,0,0-16.092-5.436ZM15.444.36A4.805,4.805,0,0,0,20.052-3.1l4.68-16.38a3.716,3.716,0,0,0,.144-1.08,3.818,3.818,0,0,0-3.816-3.816,3.83,3.83,0,0,0-3.78,3.06L14.8-9.324,11.7-21.816a3.362,3.362,0,0,0-3.276-2.556,3.362,3.362,0,0,0-3.276,2.556L2.052-9.324-.468-21.348a3.824,3.824,0,0,0-3.744-3.024,3.8,3.8,0,0,0-3.816,3.816,3.48,3.48,0,0,0,.144,1.044L-3.2-3.1A4.805,4.805,0,0,0,1.4.36a4.793,4.793,0,0,0,4.68-3.744l2.34-10.224,2.34,10.224A4.793,4.793,0,0,0,15.444.36Zm37.008,0a3.649,3.649,0,0,0,3.636-3.636v-17.46a3.649,3.649,0,0,0-3.636-3.636,3.649,3.649,0,0,0-3.636,3.636v5.22h-8.6v-5.22a3.649,3.649,0,0,0-3.636-3.636,3.649,3.649,0,0,0-3.636,3.636v17.46A3.649,3.649,0,0,0,36.576.36a3.649,3.649,0,0,0,3.636-3.636v-5.9h8.6v5.9A3.649,3.649,0,0,0,52.452.36Zm35.172-4.9-5.8-15.7a6.334,6.334,0,0,0-5.976-4.14,6.334,6.334,0,0,0-5.976,4.14l-5.8,15.7a3.708,3.708,0,0,0-.216,1.26A3.626,3.626,0,0,0,67.5.36a3.651,3.651,0,0,0,3.492-2.592l.4-1.3h8.928l.4,1.3A3.651,3.651,0,0,0,84.2.36,3.626,3.626,0,0,0,87.84-3.276,3.708,3.708,0,0,0,87.624-4.536ZM73.3-9.576,75.852-17.6l2.556,8.028ZM99.468,0h9.72a3.173,3.173,0,0,0,3.168-3.168,3.173,3.173,0,0,0-3.168-3.168h-6.3v-14.4a3.649,3.649,0,0,0-3.636-3.636,3.649,3.649,0,0,0-3.636,3.636V-3.852A3.475,3.475,0,0,0,99.468,0Zm24.7,0h11.7a3.042,3.042,0,0,0,3.024-3.024,3.042,3.042,0,0,0-3.024-3.024h-8.28v-3.06h8.028a3.042,3.042,0,0,0,3.024-3.024,3.042,3.042,0,0,0-3.024-3.024h-8.028v-2.808h8.28a3.042,3.042,0,0,0,3.024-3.024,3.042,3.042,0,0,0-3.024-3.024h-11.7a3.475,3.475,0,0,0-3.852,3.852V-3.852A3.475,3.475,0,0,0,124.164,0Z" transform="translate(302.5 722.46)" fill="#curentColor"/>
							    <path d="M.675,1.382,0,23.446a1.423,1.423,0,0,0,2.033,1.329L7.458,22.2a1.423,1.423,0,0,1,1.9.68l3.5,7.433a1.423,1.423,0,0,0,1.889.684l1.9-.886a1.424,1.424,0,0,0,.686-1.9L13.841,20.8a1.424,1.424,0,0,1,.7-1.9l5.219-2.386a1.423,1.423,0,0,0,.3-2.4L2.992.319A1.423,1.423,0,0,0,.675,1.382" transform="matrix(-0.99, -0.139, 0.139, -0.99, 222.846, 693.296)" fill="#curentColor"/>
							    <path d="M1.023,0,0,8.944" transform="translate(228.793 682.672) rotate(7)" stroke="curentColor" stroke-linecap="round" stroke-width="4"/>
							    <path d="M0,0,7.666,3.194" transform="translate(208.116 691.719) rotate(7)" stroke="curentColor" stroke-linecap="round" stroke-width="4"/>
							  </g>
						   </svg>';

		return '<a class="linkpage-public--copyright" target="_blank" href="' . $copyright_link . $ref . '">Powered by ' . $img . '</a>';
	}
}