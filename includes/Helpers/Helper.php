<?php

namespace Clickwhale\Helpers;

use Exception;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Helper {

	/**
	 * Return BD table name with prefix
	 *
	 * @param string $name
	 *
	 * @return string
	 * @since 1.5.0
	 */
	public static function get_db_table_name( string $name ): string {
		global $wpdb;

		return "{$wpdb->prefix}clickwhale_$name";
	}

	/**
	 * Return BD table names with prefixes
	 *
	 * @param array $names
	 *
	 * @return array
	 */
	public static function get_db_table_names( array $names ): array {
		return array_map( array( self::class, 'get_db_table_name' ), $names );
	}

	/**
	 * Get the option value from clickwhale_{$group}_options
	 *
	 * @param string $group
	 * @param string $option
	 *
	 * @return mixed
	 * @Since 1.6.0
	 */
	public static function get_clickwhale_option( string $group, string $option ) {
		$options = get_option( 'clickwhale_' . $group . '_options' );

		if ( empty( $options[ $option ] ) ) {
			return false;
		}

		return $options[ $option ];
	}

	/**
	 * Return HTML markup for add_settings_field function
	 *
	 * @param array $args
	 * @param bool $row
	 * @param string $row_classes
	 *
	 * @return string
	 */
	public static function render_control( array $args, bool $row = false, string $row_classes = '' ): string {

		$item       = '';
		$id         = isset( $args['id'] ) && $args['id'] ? ' id="' . esc_attr( $args['id'] ) . '"' : '';
		$class      = isset( $args['class'] ) && $args['class'] ? ' class="' . esc_attr( $args['class'] ) . '"' : '';
		$name       = isset( $args['name'] ) && $args['name'] ? ' name="' . esc_attr( $args['name'] ) . '"' : '';
		$value      = $args['value'];
		$required   = isset( $args['required'] ) && $args['required'] ? ' required' : '';
		$disabled   = isset( $args['disabled'] ) && $args['disabled'] ? ' disabled="disabled"' : '';
		$extra_desc = esc_attr( $args['extra_desc'] ?? '' );

		if ( isset( $args['default'] ) && $args['default'] ) {
			$value = $value ?: $args['default'];
		}

		if ( $row ) {
			$item .= '<tr class="form-field ' . $row_classes . '">';
			$item .= '<th scope="row"><label for="' . $args['id'] . '">' . esc_html( $args['row_label'] ?? '' ) . '</label></th>';
			$item .= '<td>';
		}

		switch ( $args['control'] ) {
			case 'input':
				$class       = $class ? $class . ' regular-text' : $class;
				$type        = esc_attr( $args['type'] );
				$width       = ( 'number' !== $type ) ? '300' : '60';
				$placeholder = isset( $args['placeholder'] ) ? ' placeholder="' . esc_attr( $args['placeholder'] ) . '"' : '';
				$item        .= '<input ' . $id . $class . $name . ' type="' . $type . '" value="' . esc_attr( $value ) . '"' . $placeholder . $disabled . $required . ' style="width: ' . $width . 'px;"';
				if ( 'number' === $type ) {
					if ( isset( $args['min'] ) ) {
						$item .= ' min="' . esc_attr( $args['min'] ) . '"';
					}
					if ( isset( $args['max'] ) ) {
						$item .= ' max="' . esc_attr( $args['max'] ) . '"';
					}
				}
				$item .= ' />';

				if ( ! empty( $extra_desc ) ) {
					$item .= '<p class="description">' . $extra_desc . '</p>';
				}
				break;

			case 'checkbox':
				$item .= '<fieldset>';
				if ( isset( $args['screenreader'] ) ) {
					$item .= '<legend class="screen-reader-text"><span>' . esc_html( $args['screenreader'] ) . '</span></legend>';
				}
				$item .= '<label>';
				$item .= '<input type="checkbox" ' . $id . $class . $name . ' value="1" ' . checked( 1, esc_attr( $value ), false ) . $disabled . ' />';
				$item .= $args['label'];
				$item .= '</label>';
				$item .= '</fieldset>';
				break;

			case 'checkboxes':
				$item .= '<fieldset>';
				if ( isset( $args['screenreader'] ) ) {
					$item .= '<legend class="screen-reader-text"><span>' . esc_html( $args['screenreader'] ) . '</span></legend>';
				}

				$always_checked = ( ! empty( $args['always_checked'] ) && is_array( $args['always_checked'] ) ) ? $args['always_checked'] : array();

				foreach ( $args['options'] as $k => $v ) {
					$k = esc_attr( $k );

					if ( $always_checked && in_array( $k, $always_checked ) ) {
						$item .= '<label class="disabled">';
					} else {
						$item .= '<label>';
					}

					$item .= '<input type="checkbox" id="' . esc_attr( $args['id'] ) . '_' . $k . '" ' . $name . ' value="' . $k . '"';

					if ( $always_checked && in_array( $k, $always_checked ) ) {
						$item .= ' checked="checked" class="disabled" />';
					} else {
						$checked = ( is_array( $value ) ) ? checked( in_array( $k, $value ), 1, false ) : '';
						$item    .= $checked . $disabled . ' />';
					}

					$item .= esc_html( $v );
					$item .= '</label><br>';
				}
				$item .= '</fieldset>';
				break;

			case 'radio':
				$item .= '<fieldset>';
				if ( isset( $args['screenreader'] ) ) {
					$item .= '<legend class="screen-reader-text"><span>' . esc_html( $args['screenreader'] ) . '</span></legend>';
				}
				foreach ( $args['options'] as $k => $v ) {
					$k = esc_attr( $k );

					$item .= '<label>';
					$item .= '<input type="radio" ' . $name . ' value="' . $k . '" ' . checked( esc_attr( $value ), $k, false ) . $disabled . ' />';
					$item .= '<span>' . esc_html( $v ) . '</span>';
					$item .= '</label><br>';
				}
				$item .= '</fieldset>';
				break;

			case 'select':
				$class    = $class ? $class . ' regular-text' : $class;
				$multiple = isset( $args['multiple'] ) && $args['multiple'] ? ' multiple' : '';
				$item     .= '<select ' . $id . $class . $name . $multiple . $disabled . '>';
				foreach ( $args['options'] as $k => $v ) {
					$k = esc_attr( $k );

					if ( $multiple && is_array( $value ) ) {
						$selected = in_array( $k, $value ) ? ' selected' : '';
					} else {
						$selected = selected( $k, esc_attr( $value ), false );
					}

					$item .= '<option value="' . $k . '" ' . $selected . '>' . esc_html( $v ) . '</option>';
				}
				$item .= '</select>';
				break;

			case 'textarea':
				$class       = $class ? $class . ' regular-text' : $class;
				$placeholder = isset( $args['placeholder'] ) ? ' placeholder="' . esc_attr( $args['placeholder'] ) . '"' : '';
				$item        .= '<textarea ' . $id . $class . $name . $placeholder . ' rows="5" ' . $required . $disabled . '>' . esc_attr( $value ) . '</textarea>';
				break;

			default:
				$item .= 'Undefined control type';
		}

		if ( $disabled ) {
			if ( isset( $args['disabled_message'] ) ) {
				$item .= '<div class="cw-links-info">' . $args['disabled_message'] . '</div>';
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
	 * @return string
	 */
	public static function render_heading( array $data ): string {
		if ( ! $data ) {
			return '';
		}

		$wpHeading = $linkToList = $linkToAdd = $linkToView = $linkCustom = '';

		if ( isset( $data['is_edit'] ) ) {
			if ( $data['is_edit'] ) {
				$wpHeading = sprintf(
				/* translators: %s: item type/name (e.g., Link, Category, Link Page) */
					esc_html__( 'Edit %s', 'clickwhale' ),
					esc_html( $data['name'] )
				);
			} else {
				$wpHeading = sprintf(
				/* translators: %s: item type/name (e.g., Link, Category, Link Page) */
					esc_html__( 'Add %s', 'clickwhale' ),
					esc_html( $data['name'] )
				);
			}
		} elseif ( ! empty( $data['is_list'] ) ) {
			$wpHeading = esc_html( $data['name'] );
		}

		if ( ! empty( $data['link_to_list'] ) ) {
			$linkToListArgs = array(
				'url'   => esc_url( get_admin_url( get_current_blog_id(), 'admin.php?page=' . $data['link_to_list'] ) ),
				'title' => __( 'Back to list', 'clickwhale' )
			);
			$linkToList     = '<a class="page-title-action" href="' . $linkToListArgs['url'] . '">' . $linkToListArgs['title'] . '</a>';
		}

		$limit = ! empty( $data['is_limit'] );
		if ( ( ! empty( $data['link_to_add'] ) ) && ! $limit ) {
			$linkToAddArgs = array(
				'url'   => esc_url(
					get_admin_url(
						get_current_blog_id(),
						'admin.php?page=' . $data['link_to_add'] . '&id=0'
					)
				),
				'title' => __( 'Add new', 'clickwhale' )
			);
			$linkToAdd     = '<a class="page-title-action" href="' . $linkToAddArgs['url'] . '">' . $linkToAddArgs['title'] . '</a>';
		}

		if ( ! empty( $data['link_to_view'] ) ) {
			$linkToViewArgs = array(
				'url'   => esc_url( $data['link_to_view'] ),
				'title' => __( 'View page', 'clickwhale' )
			);
			$linkToView     = '<a class="page-title-action" target="_blank" rel="noopener" href="' . $linkToViewArgs['url'] . '">' . $linkToViewArgs['title'] . '</a>';
		}

		if ( ! empty( $data['link_custom'] ) ) {
			$linkCustomArgs = array(
				'url'   => esc_url( $data['link_custom']['url'] ),
				'title' => esc_html( $data['link_custom']['title'] )
			);
			$linkCustom     = '<a class="page-title-action" target="_blank" rel="noopener" href="' . $linkCustomArgs['url'] . '">' . $linkCustomArgs['title'] . '</a>';
		}

		return '<h1 class="wp-heading-inline">' . $wpHeading . ' ' . $linkToList . $linkToAdd . $linkToView . $linkCustom . '</h1>';
	}

	/**
	 * @return array
	 */
	public static function get_allowed_tags(): array {
		// Common ARIA attributes
		$common_aria = [
			'aria-atomic'      => true,
			'aria-busy'        => true,
			'aria-controls'    => true,
			'aria-current'     => true,
			'aria-describedby' => true,
			'aria-details'     => true,
			'aria-disabled'    => true,
			'aria-expanded'    => true,
			'aria-hidden'      => true,
			'aria-invalid'     => true,
			'aria-label'       => true,
			'aria-labelledby'  => true,
			'aria-live'        => true,
			'aria-pressed'     => true,
			'aria-readonly'    => true,
			'aria-relevant'    => true,
			'aria-required'    => true,
			'aria-selected'    => true,
			'aria-valuemax'    => true,
			'aria-valuemin'    => true,
			'aria-valuenow'    => true,
			'aria-valuetext'   => true,
		];

		// Common global attributes
		$common_global = [
			'class'  => true,
			'data-*' => true,
			'id'     => true,
			'style'  => true,
		];

		// Common SVG stroke attributes
		$common_stroke = [
			'stroke'            => true,
			'stroke-dasharray'  => true,
			'stroke-dashoffset' => true,
			'stroke-linecap'    => true,
			'stroke-linejoin'   => true,
			'stroke-miterlimit' => true,
			'stroke-opacity'    => true,
			'stroke-width'      => true,
		];

		return [
			'a'          => array_merge( $common_global, $common_aria, [
				'href'   => true,
				'rel'    => true,
				'role'   => true,
				'target' => true,
				'title'  => true,
			] ),
			'article'    => $common_global,
			'aside'      => $common_global,
			'b'          => array_merge( $common_global, $common_aria ),
			'blockquote' => array_merge( $common_global, [
				'cite' => true,
			] ),
			'br'         => $common_global,
			'button'     => array_merge( $common_global, $common_aria, [
				'type' => true,
			] ),
			'circle'     => array_merge( $common_global, $common_stroke, [
				'cx'        => true,
				'cy'        => true,
				'fill'      => true,
				'opacity'   => true,
				'r'         => true,
				'transform' => true,
			] ),
			'code'       => array_merge( $common_global, $common_aria ),
			'div'        => array_merge( $common_global, $common_aria, [
				'title' => true,
			] ),
			'em'         => array_merge( $common_global, $common_aria ),
			'fieldset'   => $common_global,
			'footer'     => $common_global,
			'form'       => array_merge( $common_global, $common_aria, [
				'action'     => true,
				'enctype'    => true,
				'method'     => true,
				'novalidate' => true,
			] ),
			'g'          => [
				'fill' => true,
			],
			'h1'         => array_merge( $common_global, $common_aria, [
				'title' => true,
			] ),
			'h2'         => array_merge( $common_global, $common_aria, [
				'title' => true,
			] ),
			'h3'         => array_merge( $common_global, $common_aria, [
				'title' => true,
			] ),
			'h4'         => array_merge( $common_global, $common_aria, [
				'title' => true,
			] ),
			'h5'         => array_merge( $common_global, $common_aria, [
				'title' => true,
			] ),
			'h6'         => array_merge( $common_global, $common_aria, [
				'title' => true,
			] ),
			'header'     => $common_global,
			'hr'         => $common_global,
			'i'          => array_merge( $common_global, $common_aria ),
			'img'        => array_merge( $common_global, $common_aria, [
				'alt'    => true,
				'height' => true,
				'src'    => true,
				'srcset' => true,
				'width'  => true,
			] ),
			'input'      => array_merge( $common_global, $common_aria, [
				'accept'       => true,
				'autocomplete' => true,
				'checked'      => true,
				'disabled'     => true,
				'max'          => true,
				'maxlength'    => true,
				'min'          => true,
				'minlength'    => true,
				'name'         => true,
				'placeholder'  => true,
				'required'     => true,
				'size'         => true,
				'type'         => true,
				'value'        => true,
			] ),
			'label'      => array_merge( $common_global, $common_aria, [
				'for' => true,
			] ),
			'legend'     => $common_global,
			'li'         => array_merge( $common_global, $common_aria, [
				'role' => true,
			] ),
			'line'       => array_merge( $common_global, $common_stroke, [
				'fill'      => true,
				'transform' => true,
				'x1'        => true,
				'x2'        => true,
				'y1'        => true,
				'y2'        => true,
			] ),
			'link'       => [
				'as'              => true,
				'crossorigin'     => true,
				'href'            => true,
				'importance'      => true,
				'integrity'       => true,
				'prefetch'        => true,
				'referrerpolicy'  => true,
				'rel'             => true,
				'sizes'           => true,
				'target'          => true,
				'title'           => true,
				'type'            => true,
				'use-credentials' => true,
			],
			'main'       => $common_global,
			'meta'       => [
				'content' => true,
				'data-*'  => true,
				'name'    => true,
			],
			'nav'        => array_merge( $common_global, $common_aria ),
			'noscript'   => [
				'data-*' => true,
			],
			'ol'         => array_merge( $common_global, $common_aria, [
				'role' => true,
			] ),
			'option'     => [
				'data-*'   => true,
				'disabled' => true,
				'selected' => true,
				'value'    => true,
			],
			'p'          => array_merge( $common_global, $common_aria, [
				'title' => true,
			] ),
			'path'       => array_merge( $common_global, $common_stroke, [
				'd'         => true,
				'fill'      => true,
				'fill-rule' => true,
				'transform' => true
			] ),
			'pre'        => array_merge( $common_global, $common_aria ),
			'rect'       => array_merge( $common_global, $common_stroke, [
				'fill'      => true,
				'height'    => true,
				'opacity'   => true,
				'rx'        => true,
				'transform' => true,
				'width'     => true,
				'x'         => true,
				'y'         => true,
			] ),
			'script'     => [
				'async'  => true,
				'data-*' => true,
				'defer'  => true,
				'src'    => true,
				'type'   => true,
			],
			'section'    => $common_global,
			'select'     => array_merge( $common_global, $common_aria, [
				'disabled' => true,
				'multiple' => true,
				'name'     => true,
			] ),
			'span'       => array_merge( $common_global, $common_aria, [
				'title' => true,
			] ),
			'strong'     => array_merge( $common_global, $common_aria ),
			'svg'        => array_merge( $common_global, $common_aria, [
				'fill'    => true,
				'height'  => true,
				'role'    => true,
				'viewbox' => true,
				'width'   => true,
				'xmlns'   => true,
			] ),
			'table'      => array_merge( $common_global, $common_aria, [
				'role' => true,
			] ),
			'tbody'      => $common_global,
			'td'         => array_merge( $common_global, $common_aria, [
				'colspan' => true,
				'rowspan' => true,
			] ),
			'textarea'   => array_merge( $common_global, $common_aria, [
				'cols'     => true,
				'disabled' => true,
				'name'     => true,
				'required' => true,
				'rows'     => true,
			] ),
			'tfoot'      => $common_global,
			'th'         => array_merge( $common_global, $common_aria, [
				'scope' => true,
			] ),
			'thead'      => $common_global,
			'tr'         => array_merge( $common_global, $common_aria, [
				'role' => true,
			] ),
			'u'          => array_merge( $common_global, $common_aria ),
			'ul'         => array_merge( $common_global, $common_aria, [
				'role' => true,
			] ),
			'use'        => [
				'data-*' => true,
				'href'   => true,
			],
		];
	}

	/**
	 * @param array $columns
	 * @param string $order
	 * @param string $orderby
	 *
	 * @return array
	 */
	public static function get_sort_params( array $columns, string $order = 'desc', string $orderby = 'id' ): array {
		$order   = strtolower( $order );
		$orderby = strtolower( $orderby );

		return array(
			'order'   => in_array( $order, array( 'asc', 'desc' ) ) ? $order : 'desc',
			'orderby' => in_array( $orderby, array_keys( $columns ) ) ? $orderby : 'id'
		);
	}

	/**
	 * @return string
	 * @since 1.4.0
	 */
	private static function pro_link(): string {
		return 'https://clickwhale.pro/upgrade/?utm_source=users&utm_medium=button&utm_campaign=plugin_admin&utm_content=header_upgrade_to_pro_button';
	}

	/**
	 * @return string
	 * @since 1.4.0
	 */
	public static function get_pro_link(): string {
		return self::pro_link();
	}

	public static function get_pro_message( $prompt = '' ) {
		if ( '' === $prompt ) {
			$prompt = esc_html__( 'Unlimited with', 'clickwhale' );
		} else {
			$prompt = esc_html( $prompt );
		}

		$pro_link = ' <strong>' . $prompt . ' ' . sprintf(
			/* translators: %1$s: ClickWhale PRO link URL, %2$s: "ClickWhale PRO" name */
				'<a href="%1$s" rel="noopener" target="_blank">%2$s</a>',
				esc_url( self::get_pro_link() ),
				esc_html__( 'ClickWhale PRO', 'clickwhale' )
			) . '</strong>';

		return apply_filters( 'clickwhale_get_pro_message', $pro_link );
	}

	/**
	 * @return string
	 */
	public static function get_affiliates_link(): string {
		return 'https://clickwhale.pro/affiliates/?utm_source=users&utm_medium=link&utm_campaign=plugin_admin&utm_content=settings_affiliate_program';
	}

	public static function get_public_path(): string {
		if ( isset( $_SERVER['HTTP_HOST'] ) ) {
			$http_host = strtolower( sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) );
		} else {
			$http_host = 'localhost';
		}

		$request_uri = wp_unslash( $_SERVER['REQUEST_URI'] ) ?? '/'; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$request_uri = esc_url_raw( wp_unslash( $request_uri ) );
		$scheme      = ( ! empty( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] !== 'off' ) ? 'https' : 'http';
		$actual_link = $scheme . '://' . $http_host . $request_uri;
		$actual_link = str_replace( home_url(), '', $actual_link );
		$path        = wp_parse_url( $actual_link, PHP_URL_PATH );

		if ( ! is_string( $path ) ) {
			return '';
		}

		return ltrim( str_replace( $http_host, '', untrailingslashit( $path ) ), '/' );
	}

	public static function get_import_default_columns(): array {
		return array( 'title', 'slug', 'url', 'redirection', 'link_target', 'nofollow', 'sponsored' );
	}

	/**
	 * @param string $label
	 *
	 * @return array
	 * @since 1.6.0
	 */
	public static function get_post_types( string $label = 'singular_name' ): array {
		$posts      = array();
		$args       = array(
			'public' => true
		);
		$post_types = get_post_types( $args, 'objects' );
		unset( $post_types['attachment'] );

		foreach ( $post_types as $post_type ) {
			$posts[ $post_type->name ] = $post_type->labels->{$label};
		}

		return $posts;
	}

	/**
	 * Check if slug exists in WP posts table
	 *
	 * @param string $slug
	 *
	 * @return array
	 */
	public static function get_post_by_slug( string $slug ): array {
		$post_id = url_to_postid( trailingslashit( home_url( $slug ) ) );

		if ( empty( $post_id ) ) {
			return array();
		}

		$post = get_post( $post_id );

		return array(
			'id'    => $post_id,
			'title' => esc_html( wp_unslash( $post->post_title ) ),
			'type'  => esc_html( $post->post_type )
		);
	}

	/**
	 * Check if slug exists in WP taxonomies
	 *
	 * @param string $slug
	 *
	 * @return array
	 */
	public static function get_taxonomy_by_slug( string $slug ): array {
		if ( false === strpos( $slug, '/' ) ) {
			return array();
		}

		$parts      = explode( '/', $slug );
		$slug_part  = array_pop( $parts );
		$base_part  = implode( '/', $parts );
		$taxonomies = get_taxonomies( [ 'public' => true ], 'objects' );

		foreach ( $taxonomies as $taxonomy ) {
			// May be either `array` or `false`
			$rewrite = ( property_exists( $taxonomy, 'rewrite' ) ) ? $taxonomy->rewrite : true;

			if ( $rewrite === false ) {
				continue;
			}

			if ( is_array( $rewrite ) && isset( $rewrite['slug'] ) ) {
				if ( '' === $rewrite['slug'] ) {
					continue;
				}

				$term_base = trim( $rewrite['slug'], '/' );
			} else {
				$term_base = $taxonomy->name;
			}

			if ( $term_base !== $base_part ) {
				continue;
			}

			$term = get_term_by( 'slug', $slug_part, $taxonomy->name );

			if ( $term && ! is_wp_error( $term ) ) {
				return array(
					'id'    => $term->term_id,
					'title' => esc_html( wp_unslash( $term->name ) ),
					'type'  => esc_html( $term->taxonomy )
				);
			}
		}

		return array();
	}

	/**
	 * Check if slug matches a WordPress virtual URL that has no real post/taxonomy entry:
	 * the Posts page (blog archive) or a public CPT archive.
	 *
	 * @param string $slug
	 *
	 * @return array  Empty if not found; otherwise ['id', 'title', 'type'].
	 */
	public static function get_virtual_url_by_slug( string $slug ): array {
		$slug     = ltrim( $slug, '/' );
		$home     = trailingslashit( home_url() );
		$home_len = strlen( $home );

		// Posts page (Settings → Reading → "Posts page")
		if ( 'page' === get_option( 'show_on_front' ) ) {
			$page_for_posts_id = (int) get_option( 'page_for_posts' );

			if ( $page_for_posts_id > 0 ) {
				$posts_page = get_post( $page_for_posts_id );

				if ( $posts_page ) {
					$posts_page_url = get_permalink( $posts_page->ID );

					if ( is_string( $posts_page_url ) && strpos( $posts_page_url, $home ) === 0 ) {
						$path = trim( substr( $posts_page_url, $home_len ), '/' );

						if ( $path === $slug ) {
							return array(
								'id'    => $posts_page->ID,
								'title' => $posts_page->post_title,
								'type'  => 'posts page',
							);
						}
					}
				}
			}
		}

		// Public CPT archives
		$post_types = get_post_types( array( 'public' => true ), 'objects' );

		foreach ( $post_types as $post_type ) {
			if ( empty( $post_type->has_archive ) ) {
				continue;
			}

			$archive_url = get_post_type_archive_link( $post_type->name );

			if ( ! $archive_url || strpos( $archive_url, $home ) !== 0 ) {
				continue;
			}

			$path = trim( substr( $archive_url, $home_len ), '/' );

			if ( $path === $slug ) {
				$label = isset( $post_type->labels->name ) ? $post_type->labels->name : $post_type->name;

				return array(
					'id'    => 0,
					'title' => $label,
					'type'  => $post_type->name . ' archive',
				);
			}
		}

		return array();
	}

	/**
	 * @return array
	 */
	public static function get_tracking_durations(): array {
		return apply_filters(
			'clickwhale_tracking_duration',
			array( 30 => __( '30 days', 'clickwhale' ) )
		);
	}

	/**
	 * Compare two URLs for effective equality within the same site context.
	 * Normalizes host (strip www), path (trim trailing slash) and ignores common tracking query params.
	 * Treats relative Location headers as pointing to the current site host.
	 *
	 * @param string $a
	 * @param string $b
	 *
	 * @return bool
	 */
	public static function urls_effectively_equal( string $a, string $b ): bool {
		$base      = wp_parse_url( home_url( '/' ) );
		$base_host = isset( $base['host'] ) ? preg_replace( '/^www\./i', '', strtolower( $base['host'] ) ) : '';

		$normalize = static function ( $url ) use ( $base_host ) {
			$parts = wp_parse_url( $url );
			if ( empty( $parts ) ) {
				return array( 'host' => '', 'path' => '', 'query' => array() );
			}

			$host = isset( $parts['host'] ) && $parts['host'] !== ''
				? preg_replace( '/^www\./i', '', strtolower( $parts['host'] ) )
				: $base_host; // relative Location → assume current host

			$path = isset( $parts['path'] ) ? rtrim( $parts['path'], '/' ) : '';

			$ignore = array(
				'utm_source',
				'utm_medium',
				'utm_campaign',
				'utm_term',
				'utm_content',
				'gclid',
				'fbclid',
				'_ga'
			);
			$query  = array();
			if ( ! empty( $parts['query'] ) ) {
				parse_str( $parts['query'], $query );
				foreach ( $ignore as $k ) {
					unset( $query[ $k ] );
				}
				ksort( $query );
			}

			return array(
				'host'  => $host,
				'path'  => $path,
				'query' => $query,
			);
		};

		$A = $normalize( $a );
		$B = $normalize( $b );

		return $A['host'] === $B['host'] && $A['path'] === $B['path'] && $A['query'] === $B['query'];
	}

	/**
	 * Check if media file exists in Wordpress Media library
	 *
	 * @param string $image_url
	 *
	 * @return bool
	 */
	public static function get_media_file_path( string $image_url ): bool {
		return file_exists( str_replace( home_url( '/' ), ABSPATH, $image_url ) );
	}

	/**
	 * @param string $page_slug
	 *
	 * @throws Exception
	 */
	public static function csrf_exception( string $page_slug = '' ) {
		/* translators: %d: current user ID */
		$log_msg = sprintf( esc_html__( 'Security check failed (possible CSRF) for user ID %d', 'clickwhale' ), get_current_user_id() );

		if ( $page_slug ) {
			/* translators: %s: admin page slug */
			$log_msg .= ' ' . sprintf( esc_html__( 'on page: %s', 'clickwhale' ), esc_html( $page_slug ) );
		}

		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log( $log_msg );
		throw new Exception( esc_html__( 'Security check failed. Please contact the ClickWhale customer support.', 'clickwhale' ) );
	}

	/**
	 * @param string $color
	 *
	 * @return bool
	 */
	public static function validate_hex_color( string $color ): bool {
		return preg_match( '/^#([0-9A-F]{3}){1,2}$/i', $color );
	}
}
