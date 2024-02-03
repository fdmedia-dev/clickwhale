<?php
namespace clickwhale\includes\front\linkpages;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

interface Linkpage_Interface {

	function getUrl();

	function getTemplate();

	function getTitle();

	function setTitle( $title );

	function setContent( $content );

	function setTemplate( $template );

	/**
	 * Get a WP_Post build using virtual Page object
	 *
	 * @return WP_Post
	 */
	function asWpPost();
}