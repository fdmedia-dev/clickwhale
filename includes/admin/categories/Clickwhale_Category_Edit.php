<?php

namespace clickwhale\includes\admin\categories;

class Clickwhale_Category_Edit {
	private static $instance;

	public static function getInstance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function clickwhale_validate_category( $item ) {
		$messages = array();

		if ( empty( $item['title'] ) ) {
			$messages[] = __( 'Title is required', 'clickwhale' );
		}

		if ( empty( $messages ) ) {
			return true;
		}

		return implode( '<br />', $messages );
	}

	public function clear_category_slug( $item ) {
		$slug = $item['slug'] ? sanitize_title( $item['slug'] ) : sanitize_title( $item['title'] );

		$item['slug'] = $slug;

		return $item;
	}

	public function set_edit_category_page_title( $admin_title, $title ) {
		return 'Edit Category' . $admin_title;
	}

	public function set_add_category_page_title( $admin_title, $title ) {
		return 'Add Category' . $admin_title;
	}

}