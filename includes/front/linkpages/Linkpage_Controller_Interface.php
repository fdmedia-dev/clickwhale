<?php
namespace clickwhale\includes\front\linkpages;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

interface Linkpage_Controller_Interface {

	/**
	 * Init the controller, fires the hook that allows consumer to add pages
	 */
	function init();

	/**
	 * Register a page object in the controller
	 *
	 * @param $page
	 */
	function addPage( $page );

	/**
	 * Run on 'do_parse_request' and if the request is for one of the registered pages
	 * setup global variables, fire core hooks, requires page template and exit.
	 *
	 * @param boolean $bool The boolean flag value passed by 'do_parse_request'
	 * @param object $wp The global wp object passed by 'do_parse_request'
	 */
	function dispatch( $bool, $wp );
}