<?php
namespace clickwhale\includes\front\linkpages;

use WP_Post;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Linkpage implements Linkpage_Interface {

	private $url;
	private $title;
	private $content;
	private $linkpage;
	private $template;
	private $wp_post;

	public function __construct( $url, $title = 'Untitled', $template = 'page.php', $linkpage = 0 ) {
		$this->url = filter_var( $url, FILTER_SANITIZE_URL );
		$this->setTitle( $title );
		$this->setTemplate( $template );
		$this->setLinkpage( $linkpage );
	}

	public function getUrl() {
		return $this->url;
	}

	public function getTemplate() {
		return $this->template;
	}

	public function getTitle() {
		return $this->title;
	}

	public function setTitle( $title ) {
		$this->title = filter_var( $title, FILTER_UNSAFE_RAW );

		return $this;
	}

	public function setContent( $content ) {
		$this->content = $content;

		return $this;
	}

	/**
	 * @param $linkpage
	 *
	 * @return $this
	 * @since 1.1.0
	 *
	 */
	public function setLinkpage( $linkpage ) {
		$this->linkpage = $linkpage;

		return $this;
	}

	public function setTemplate( $template ) {
		$this->template = $template;

		return $this;
	}

	public function asWpPost() {
		if ( is_null( $this->wp_post ) ) {
			$post          = array(
				'ID'             => 0,
				'post_title'     => $this->title,
				'post_name'      => sanitize_title( $this->title ),
				'post_content'   => $this->content ?: '',
				'post_excerpt'   => '',
				'post_parent'    => 0,
				'menu_order'     => 0,
				'post_type'      => 'page',
				'post_status'    => 'publish',
				'comment_status' => 'closed',
				'ping_status'    => 'closed',
				'comment_count'  => 0,
				'post_password'  => '',
				'to_ping'        => '',
				'pinged'         => '',
				'guid'           => home_url( $this->getUrl() ),
				'post_date'      => current_time( 'mysql' ),
				'post_date_gmt'  => current_time( 'mysql', 1 ),
				'post_author'    => is_user_logged_in() ? get_current_user_id() : 0,
				'is_virtual'     => true,
				'filter'         => 'raw',
				'linkpage'       => $this->linkpage ?: '',
			);
			$this->wp_post = new WP_Post( (object) $post );
		}

		return $this->wp_post;
	}
}