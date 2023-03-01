<?php

class ClickwhaleHepler {
	/**
	 * Return HTML markup for add_settings_field function
	 *
	 * @param array $args
	 *
	 * @return string
	 */
	public static function render_control( array $args, bool $row = false ): string {

		$item     = '';
		$id       = isset( $args['id'] ) && $args['id'] ? ' id="' . esc_attr( $args['id'] ) . '"' : '';
		$class    = isset( $args['class'] ) && $args['class'] ? ' class="' . esc_attr( $args['class'] ) . '"' : '';
		$name     = isset( $args['name'] ) && $args['name'] ? ' name="' . esc_attr( $args['name'] ) . '"' : '';
		$value    = $args['value'];
		$required = isset( $args['required'] ) && $args['required'] ? ' required' : '';
		$disabled = isset( $args['disabled'] ) && $args['disabled'] ? ' disabled="disabled"' : '';

		if ( isset( $args['default'] ) && $args['default'] ) {
			$value = $value ?: $args['default'];
		}

		if ( $row ) {
			$rowLabel = $args['row_label'] ?? '';
			$item     .= '<tr class="form-field">';
			$item     .= '<th scope="row"><label for="' . $args['id'] . '">' . $rowLabel . '</label></th>';
			$item     .= '<td>';
		}

		switch ( $args['control'] ) {
			case 'input':
				$class = $class ? $class . ' regular-text' : $class;
				$item  .= '<input ' . $id . $class . $name . ' type="' . esc_attr( $args['type'] ) . '" value="' . esc_attr( $value ) . '" placeholder="' . esc_attr( $args['placeholder'] ) . '" ' . $disabled . $required . '>';
				break;

			case 'checkbox':
				$item .= '<fieldset>';
				if ( isset( $args['screenreader'] ) ) {
					$item .= '<legend class="screen-reader-text"><span>' . $args['screenreader'] . '</span></legend>';
				}
				$item .= '<label>';
				$item .= '<input type="checkbox" ' . $id . $class . $name . ' value="1" ' . checked( 1, $value,
						false ) . $disabled . ' />';
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
								$value ), 1, false ) . $disabled . ' />';
					} else {
						$item .= '<input type="checkbox" id="' . esc_attr( $args['id'] . '_' . $k ) . '" ' . $name . ' value="' . esc_attr( $k ) . '" ' . $disabled . ' />';
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
							false ) . $disabled . ' />';
					$item .= '<span>' . $v . '</span>';
					$item .= '</label><br>';
				}
				$item .= '</fieldset>';
				break;

			case 'select':
				$class    = $class ? $class . ' regular-text' : $class;
				$multiple = isset( $args['multiple'] ) && $args['multiple'] ? ' multiple' : '';
				$item     .= '<select ' . $id . $class . $name . $multiple . $disabled . '>';
				foreach ( $args['options'] as $k => $v ) {
					if ( $multiple && is_array( $value ) ) {
						$selected = in_array( $k, $value ) ? ' selected' : '';
					} else {
						$selected = selected( $k, $value, false );
					}

					$item .= '<option value="' . esc_attr( $k ) . '" ' . $selected . '>' . $v . '</option>';
				}
				$item .= '</select>';
				break;

			case 'textarea':
				$class       = $class ? $class . ' regular-text' : $class;
				$placeholder = $args['placeholder'] ?? '';
				$item        .= '<textarea ' . $id . $class . $name . ' placeholder="' . esc_attr( $placeholder ) . '" rows="5" ' . $required . $disabled . '>' . esc_attr( $value ) . '</textarea>';
				break;

			default:
				$item .= 'Undefined control type';
		}

		if ( $disabled ) {
			if ( isset( $args['disabled_message'] ) ) {
				$item .= '<div class="links-info">' . $args['disabled_message'] . '</div>';
			}
		}

		if ( isset( $args['description'] ) ) {
			$item .= '<p class="description ">' . $args['description'] . '</p>';
		}

		if ( $row ) {
			$item .= '</td>';
			$item .= '</tr>';
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
		if ( ( isset( $data['link_to_edit'] ) && $data['link_to_edit'] ) && ! $limit ) {
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
