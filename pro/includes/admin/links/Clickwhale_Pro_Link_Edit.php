<?php

namespace clickwhale_pro\includes\admin\links;

use clickwhale\includes\helpers\Helper;
use clickwhale\includes\helpers\Links_Helper;

class Clickwhale_Pro_Link_Edit {

	private function utms_default(): array {
		return array(
			array(
				'name'  => 'utm_campaign',
				'label' => 'UTM Campaign'
			),
			array(
				'name'  => 'utm_medium',
				'label' => 'UTM Medium'
			),
			array(
				'name'  => 'utm_source',
				'label' => 'UTM Source'
			),
			array(
				'name'  => 'utm_term',
				'label' => 'UTM Term'
			),
			array(
				'name'  => 'utm_content',
				'label' => 'UTM Content'
			),
		);
	}

	public function get_utms_default_array() {
		$result = [];
		foreach ( $this->utms_default() as $utm ) {
			$result[ $utm['name'] ] = '';
		}

		return $result;
	}

	/**
	 * Filter function for "link_defaults" filter
	 * Add utm params
	 *
	 * @param $fields
	 *
	 * @return mixed
	 */
	public function link_defaults_utm( $fields ) {
		return array_merge( $fields, $this->get_utms_default_array() );
	}

	public function get_utm_field_html( $name, $label, $value ) {
		$field = '';

		$field .= '<tr class="form-field">';
		$field .= '<th valign="top" scope="row"><label for="' . $name . '">' . sprintf( __( '%1$s',
				CLICKWHALE_PRO_NAME ), $label ) . '</label></th>';
		$field .= '<td><input id="' . $name . '" type="text" name="' . $name . '" value="' . $value . '" placeholder="" style="width: 95%" size="50"></td>';
		$field .= '</tr>';

		return $field;
	}

	public function utm_fields() {
		$request = $_REQUEST;
		$utms    = $this->utms_default();
		$fields  = '';

		$fields .= '<div class="clear"></div>';
		$fields .= '<h3>' . __( 'UTM Parameters', CLICKWHALE_PRO_NAME ) . '</h3>';
		$fields .= '<table cellspacing="2" cellpadding="5" style="width: 100%;" class="form-table"><tbody>';
		foreach ( $utms as $utm ) {
			$row    = isset( $request['id'] ) ? Links_Helper::get_meta( $request['id'], $utm['name'] ) : '';
			$value  = $row ? $row['meta_value'] : '';
			$fields .= $this->get_utm_field_html( $utm['name'], $utm['label'], $value );
		}
		$fields .= '</tbody></table>';

		return $fields;
	}

	public function link_edit_fields() {
		echo $this->utm_fields();
	}

	public function prepare_link_meta( $id, $item ) {
		$utms = $this->utms_default();

		$meta            = [];
		$meta['link_id'] = $id;
		$meta['item']    = [];
		$utm_string      = '';

		foreach ( $utms as $utm ) {
			$meta['item'][ $utm['name'] ] = $item[ $utm['name'] ];
			if ( $item[ $utm['name'] ] ) {
				$utm_string .= $utm['name'] . '=' . $item[ $utm['name'] ] . '&';
			}
		}
		$meta['item']['utm_string'] = substr( $utm_string, 0, - 1 );

		return $meta;
	}

	private function check_meta( $id, $key ) {
		global $wpdb;
		$links_meta_table = $wpdb->prefix . 'clickwhale_meta';

		return $wpdb->get_var( "SELECT count(*) FROM $links_meta_table WHERE link_id=$id AND meta_key='$key'" );
	}

	private function save_link_meta_to_db( $id, $key, $value, $action ) {
		global $wpdb;
		$links_meta_table = $wpdb->prefix . 'clickwhale_meta';

		switch ( $action ) {
			case 'insert':
				$result = $wpdb->insert(
					$links_meta_table,
					array(
						'meta_key'   => $key,
						'meta_value' => $value,
						'link_id'    => $id
					)
				);
				break;
			case 'update':
				$result = $wpdb->update(
					$links_meta_table,
					array(
						'meta_value' => $value
					),
					array(
						'link_id'  => $id,
						'meta_key' => $key,
					)
				);
				break;
		}

		return $result;
	}

	public function update_link_meta( $id, $post ) {
		$utms = array_intersect_key( $post, $this->get_utms_default_array() );
		$data = $this->prepare_link_meta( $id, $utms );

		foreach ( $data['item'] as $k => $v ) {
			if ( $this->check_meta( $data['link_id'], $k ) === '0' ) {
				$this->save_link_meta_to_db( $data['link_id'], $k, $v, 'insert' );
			} else {
				$this->save_link_meta_to_db( $data['link_id'], $k, $v, 'update' );
			}
		}
	}

	public function insert_link_meta( $id, $post ) {
		$utms = array_intersect_key( $post, $this->get_utms_default_array() );
		$data = $this->prepare_link_meta( $id, $utms );

		foreach ( $data['item'] as $k => $v ) {
			$this->save_link_meta_to_db( $data['link_id'], $k, $v, 'insert' );
		}
	}
}