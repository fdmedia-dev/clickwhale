<?php
namespace clickwhale\includes\front\linkpages;

use WP_Post;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Linkpage implements Linkpage_Interface {

    /**
     * @var string
     */
    private string $url;

    /**
     * @var string
     */
    private string $title;

    /**
     * @var string
     */
    private string $template;

    /**
     * @var string
     */
    private string $content;

    /**
     * @var array
     */
    private array $linkpage;

    /**
     * @var WP_Post|null
     */
    private ?WP_Post $wp_post = null;

	public function __construct( string $url, string $title = 'Untitled', string $template = 'page.php', $linkpage = array() ) {
		$this->setUrl( $url );
		$this->setTitle( $title );
		$this->setTemplate( $template );
		$this->setContent();
		$this->setLinkpage( $linkpage );
	}

	public function getUrl(): string {
		return $this->url;
	}

    public function setUrl( string $url ): Linkpage {
        $this->url = filter_var( $url, FILTER_SANITIZE_URL );
        return $this;
    }

    public function getTitle(): string {
        return $this->title;
    }

    public function setTitle( string $title ): Linkpage {
        $this->title = sanitize_text_field( $title );
        return $this;
    }

	public function getTemplate(): string {
		return $this->template;
	}

    public function setTemplate( string $template ): Linkpage {
        $this->template = $template;
        return $this;
    }

	public function setContent( string $content = '' ): Linkpage {
		$this->content = $content;
		return $this;
	}

    /**
     * @param array $linkpage
     * @return Linkpage
     *
     * @since 1.1.0
     */
    public function setLinkpage( array $linkpage ): Linkpage {
        $this->linkpage = $linkpage;
        return $this;
    }

	public function asWpPost(): WP_Post {
		if ( is_null( $this->wp_post ) ) {
			$post          = array(
				'ID'             => 0,
				'post_title'     => $this->title,
				'post_name'      => $this->title,
				'post_content'   => $this->content,
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
				'guid'           => home_url( $this->url ),
				'post_date'      => current_time( 'mysql' ),
				'post_date_gmt'  => current_time( 'mysql', 1 ),
				'post_author'    => is_user_logged_in() ? get_current_user_id() : 0,
				'is_virtual'     => true,
				'filter'         => 'raw',
				'linkpage'       => $this->linkpage
			);
			$this->wp_post = new WP_Post( (object) $post );
		}

		return $this->wp_post;
	}
}