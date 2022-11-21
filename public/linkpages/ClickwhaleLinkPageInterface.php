<?php

interface ClickwhaleLinkPageInterface {

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