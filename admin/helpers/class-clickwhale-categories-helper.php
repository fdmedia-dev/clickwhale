<?php

class ClickwhaleCategoriesHelper {

	/**
	 * Filter function
	 * return number of available links
	 * @return mixed|void
	 */
	public static function get_limit() {
		return apply_filters( 'clickwhale_categories_limit', 10 );
	}

}