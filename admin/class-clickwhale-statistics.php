<?php

/**
 * @since 1.3.0
 */
class ClickwhaleStatistics {
	private static $instance;

	/**
	 * @return ClickwhaleStatistics
	 */
	public static function getInstance(): ClickwhaleStatistics {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function init() {

	}

	public function show_promo() {
		return true;
	}
}