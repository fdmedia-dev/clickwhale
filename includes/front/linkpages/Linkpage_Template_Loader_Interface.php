<?php
namespace clickwhale\includes\front\linkpages;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

interface Linkpage_Template_Loader_Interface {
	/**
	 * Setup loader for a page objects
	 *
	 * @param object $page matched virtual page
	 */
	public function init( $page );

	/**
	 * Trigger core and custom hooks to filter templates,
	 * then load the found template.
	 */
	public function load();
}