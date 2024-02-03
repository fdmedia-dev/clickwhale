<?php
namespace clickwhale\includes\front\linkpages;

use SplObjectStorage;
use WP_Post;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Linkpage_Controller implements Linkpage_Controller_Interface {

	private $pages;
	private $loader;
	private $matched;

	function __construct( $loader ) {
		$this->pages  = new SplObjectStorage;
		$this->loader = $loader;
	}

	function init() {
		do_action( 'clickwhale_virtual_pages', $this );
	}

	function addPage( $page ) {
		$this->pages->attach( $page );

		return $page;
	}

	function dispatch( $bool, $wp ) {
		if ( $this->checkRequest() && $this->matched instanceof Linkpage ) {
			$this->loader->init( $this->matched );
			$wp->virtual_page = $this->matched;
			do_action( 'parse_request', $wp );
			$this->setupQuery();
			do_action( 'wp', $wp );
			$this->loader->load();
			$this->handleExit();
		}

		return $bool;
	}

	private function checkRequest() {
		$this->pages->rewind();
		$path = trim( parse_url( $this->getPathInfo(), PHP_URL_PATH ), '/' );

		while ( $this->pages->valid() ) {
			// get current object
			// @link https://www.php.net/manual/en/splobjectstorage.current.php
			$current = trim( $this->pages->current()->getUrl(), '/' );
			// check url
			// 1. if virtual page url is matches to $path
			// 2. if virtual page url has GET params and only contains part of the $path
			if ( $current === $path ) {
				$this->matched = $this->pages->current();

				return true;
			}
			$this->pages->next();
		}
	}

	private function getPathInfo() {
		$home_path = parse_url( home_url(), PHP_URL_PATH );

		return preg_replace( "#^/?{$home_path}/#", '/', add_query_arg( array() ) );
	}

	private function setupQuery() {
		global $wp_query;
		$wp_query->init();
		$wp_query->is_page        = true;
		$wp_query->is_singular    = true;
		$wp_query->is_home        = false;
		$wp_query->found_posts    = 1;
		$wp_query->post_count     = 1;
		$wp_query->max_num_pages  = 1;
		$posts                    = (array) apply_filters( 'the_posts', array( $this->matched->asWpPost() ),
			$wp_query );
		$post                     = $posts[0];
		$wp_query->posts          = $posts;
		$wp_query->post           = $post;
		$wp_query->queried_object = $post;
		$GLOBALS['post']          = $post;
		$wp_query->virtual_page   = $post instanceof WP_Post && isset( $post->is_virtual )
			? $this->matched
			: null;
	}

	public function handleExit() {
		exit();
	}
}