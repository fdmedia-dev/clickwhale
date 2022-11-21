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
				$item .= '<input type="checkbox" ' . $id . ' ' . $name . ' value="1" ' . checked( 1, $value, false ) . ' />';
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
						$item .= '<input type="checkbox" id="' . esc_attr( $args['id'] . '_' . $k ) . '" ' . $name . ' value="' . esc_attr( $k ) . '" ' . checked( in_array( $k, $value ), 1, false ) . ' />';
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
					$item .= '<input type="radio" ' . $name . ' value="' . esc_attr( $k ) . '" ' . checked( $k, $value, false ) . ' />';
					$item .= '<span>' . $v . '</span>';
					$item .= '</label><br>';
				}
				$item .= '</fieldset>';
				break;

			case 'select':
				$item .= '<select ' . $id . ' ' . $name . ' class="regular-text">';
				foreach ( $args['options'] as $k => $v ) {
					$item .= '<option value="' . esc_attr( $k ) . '" ' . selected( $k, $value, false ) . '>' . $v . '</option>';
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
}
