<?php

interface ClickwhaleLinkPageTemplateLoaderInterface {
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