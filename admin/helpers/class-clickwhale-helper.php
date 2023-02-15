<?php

class ClickwhaleHepler {
	/**
	 * Return HTML marckup for add_settings_field function
	 *
	 * @param array $args
	 *
	 * @return string
	 */
	public static function render_contol( $args ) {

		$item  = '';
		$id    = 'id="' . $args['id'] . '"';
		$name  = 'name="' . esc_attr( $args['name'] ) . '"';
		$value = $args['value'];

		switch ( $args['control'] ) {
			case 'input':
				$item .= '<input ' . $id . ' ' . $name . ' type="' . esc_attr( $args['type'] ) . '" value="' . esc_attr( $value ) . '" placeholder="' . esc_attr( $args['placeholder'] ) . '" class="regular-text">';
				break;

			case 'checkbox':
				$item .= '<fieldset>';
				if ( isset( $args['screenreader'] ) ) {
					$item .= '<legend class="screen-reader-text"><span>' . $args['screenreader'] . '</span></legend>';
				}
				$item .= '<label>';
				$item .= '<input type="checkbox" ' . $id . ' ' . $name . ' value="1" ' . checked( 1, $value,
						false ) . ' />';
				$item .= $args['label'];
				$item .= '</label>';
				$item .= '</fieldset>';
				break;

			case 'checkboxes':
				$item .= '<fieldset>';
				if ( isset( $args['screenreader'] ) ) {
					$item .= '<legend class="screen-reader-text"><span>' . $args['screenreader'] . '</span></legend>';
				}
				foreach ( $args['options'] as $k => $v ) {
					$item .= '<label>';
					if ( is_array( $value ) ) {
						$item .= '<input type="checkbox" id="' . esc_attr( $args['id'] . '_' . $k ) . '" ' . $name . ' value="' . esc_attr( $k ) . '" ' . checked( in_array( $k,
								$value ), 1, false ) . ' />';
					} else {
						$item .= '<input type="checkbox" id="' . esc_attr( $args['id'] . '_' . $k ) . '" ' . $name . ' value="' . esc_attr( $k ) . '" />';
					}
					$item .= $v;
					$item .= '</label><br>';
				}
				$item .= '</fieldset>';
				break;
			case 'radio':
				$item .= '<fieldset>';
				if ( isset( $args['screenreader'] ) ) {
					$item .= '<legend class="screen-reader-text"><span>' . $args['screenreader'] . '</span></legend>';
				}
				foreach ( $args['options'] as $k => $v ) {
					$item .= '<label>';
					$item .= '<input type="radio" ' . $name . ' value="' . esc_attr( $k ) . '" ' . checked( $k, $value,
							false ) . ' />';
					$item .= '<span>' . $v . '</span>';
					$item .= '</label><br>';
				}
				$item .= '</fieldset>';
				break;

			case 'select':
				$item .= '<select ' . $id . ' ' . $name . ' class="regular-text">';
				foreach ( $args['options'] as $k => $v ) {
					$item .= '<option value="' . esc_attr( $k ) . '" ' . selected( $k, $value,
							false ) . '>' . $v . '</option>';
				}
				$item .= '</select>';
				break;

			default:
				$item .= 'Undefined control type';
		}

		if ( isset( $args['description'] ) ) {
			$item .= '<p class="description ">' . $args['description'] . '</p>';
		}

		return $item;
	}

	/**
	 * @param array $data
	 * $data possible keys:
	 * string 'name' - current page name
	 * bool 'is_edit' - is add/edit page
	 * bool 'is_list' - is list page
	 * string 'link_to_edit' - get param "page" for add/edit page
	 * string 'link_to_list' - get param "page" for list page
	 * string 'link_to_view' - LP public link
	 * bool 'is_limit' - if limit exists
	 *
	 * @return false|string
	 */
	public static function render_heading( array $data ) {
		if ( ! $data ) {
			return false;
		}

		$wpHeading = $linkToList = $linkToEdit = $linkToView = '';

		if ( isset( $data['is_edit'] ) ) {
			if ( $data['is_edit'] ) {
				$wpHeading = sprintf( __( 'Edit %s', 'clickwhale' ), $data['name'] );
			} else {
				$wpHeading = sprintf( __( 'Add %s', 'clickwhale' ), $data['name'] );
			}
		} elseif ( isset( $data['is_list'] ) && $data['is_list'] ) {
			$wpHeading = $data['name'];
		}

		if ( isset( $data['link_to_list'] ) && $data['link_to_list'] ) {
			$linkToListArgs = array(
				'url'   => esc_url( get_admin_url( get_current_blog_id(), 'admin.php?page=' . $data['link_to_list'] ) ),
				'title' => __( 'Back to List', 'clickwhale' )
			);
			$linkToList     = '<a class="page-title-action" href="' . $linkToListArgs['url'] . '">' . $linkToListArgs['title'] . '</a>';
		}

		$limit = isset( $data['is_limit'] ) && $data['is_limit'];
		if ( ( isset( $data['link_to_edit'] ) && $data['link_to_edit'] ) && !$limit ) {
			$linkToEditArgs = array(
				'url'   => esc_url( get_admin_url( get_current_blog_id(), 'admin.php?page=' . $data['link_to_edit'] ) ),
				'title' => __( 'Add New', 'clickwhale' )
			);
			$linkToEdit     = '<a class="page-title-action" href="' . $linkToEditArgs['url'] . '">' . $linkToEditArgs['title'] . '</a>';
		}

		if ( isset( $data['link_to_view'] ) && $data['link_to_view'] ) {
			$linkToViewArgs = array(
				'url'   => $data['link_to_view'],
				'title' => __( 'View Page', 'clickwhale' )
			);
			$linkToView     = '<a class="page-title-action" target="_blank" rel="noopener" href="' . $linkToViewArgs['url'] . '">' . $linkToViewArgs['title'] . '</a>';
		}

		return '<h1 class="wp-heading-inline">' . $wpHeading . ' ' . $linkToList . $linkToEdit . $linkToView . '</h1>';

	}
}
