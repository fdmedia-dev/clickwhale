<?php

class ClickwhaleLinksHelper {

	/**
	 * Filter function
	 * return number of available links
	 * @return mixed|void
	 */
	public static function get_limit() {
		return apply_filters( 'clickwhale_links_limit', 1 );
	}

}