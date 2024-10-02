<?php
namespace clickwhale\includes\helpers;

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
	public static function render_control( array $args, bool $row = false, $row_classes = '' ): string {

		$item     = '';
		$id       = isset( $args['id'] ) && $args['id'] ? ' id="' . esc_attr( $args['id'] ) . '"' : '';
		$class    = isset( $args['class'] ) && $args['class'] ? ' class="' . esc_attr( $args['class'] ) . '"' : '';
		$name     = isset( $args['name'] ) && $args['name'] ? ' name="' . esc_attr( $args['name'] ) . '"' : '';
		$value    = $args['value'];
		$required = isset( $args['required'] ) && $args['required'] ? ' required' : '';
		$disabled = isset( $args['disabled'] ) && $args['disabled'] ? ' disabled="disabled"' : '';
		$extra_desc = esc_attr( $args['extra_desc'] ?? '' );

		if ( isset( $args['default'] ) && $args['default'] ) {
			$value = $value ?: $args['default'];
		}

		if ( $row ) {
			$rowLabel = $args['row_label'] ?? '';
			$item     .= '<tr class="form-field ' . $row_classes . '">';
			$item     .= '<th scope="row"><label for="' . $args['id'] . '">' . $rowLabel . '</label></th>';
			$item     .= '<td>';
		}

		switch ( $args['control'] ) {
			case 'input':
				$class = $class ? $class . ' regular-text' : $class;
                $type  = esc_attr( $args['type'] );
                $width = ( 'number' !== $type ) ? '300' : '60';
                $placeholder  = isset( $args['placeholder'] ) ? ' placeholder="' . esc_attr( $args['placeholder'] ) . '"' : '';
				$item  .= '<input ' . $id . $class . $name . ' type="' . $type . '" value="' . esc_attr( $value ) . '"' . $placeholder . $disabled . $required . ' style="width: ' . $width . 'px;"';
                if ( 'number' === $type ) {
                    $min = isset( $args['min'] ) ? esc_attr( $args['min'] ) : '1';
                    $max = isset( $args['max'] ) ? esc_attr( $args['max'] ) : '1';
                    $item .= ' min="' . $min . '"' . ' max="' . $max . '"';
                }
                $item .= ' />';

                if ( ! empty( $extra_desc ) ) {
                    $item .= '<code style="display: inline-block; margin-left: 16px;">' . $extra_desc . '</code>';
                }
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

				$always_checked = ( ! empty( $args['always_checked'] ) && is_array( $args['always_checked'] ) ) ? $args['always_checked'] : array();

				foreach ( $args['options'] as $k => $v ) {
					if ( $always_checked && in_array( $k, $always_checked ) ) {
						$item .= '<label class="disabled">';
					} else {
						$item .= '<label>';
					}

                    $item .= '<input type="checkbox" id="' . esc_attr( $args['id'] . '_' . $k ) . '" ' . $name . ' value="' . esc_attr( $k ) . '"';

                    if ( $always_checked && in_array( $k, $always_checked ) ) {
                        $item .= ' checked="checked" class="disabled" />';
                    } else {
                        $checked = ( is_array( $value ) ) ? checked( in_array( $k, $value ), 1, false ) : '';
                        $item .= $checked . $disabled . ' />';
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
					$item .= '<input type="radio" ' . $name . ' value="' . esc_attr( $k ) . '" ' . checked( $value, $k,
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

		$wpHeading = $linkToList = $linkToAdd = $linkToView = $linkCustom = '';

		if ( isset( $data['is_edit'] ) ) {
			if ( $data['is_edit'] ) {
				$wpHeading = sprintf( __( 'Edit %s', CLICKWHALE_NAME ), $data['name'] );
			} else {
				$wpHeading = sprintf( __( 'Add %s', CLICKWHALE_NAME ), $data['name'] );
			}
		} elseif ( ! empty( $data['is_list'] ) ) {
			$wpHeading = $data['name'];
		}

		if ( ! empty( $data['link_to_list'] ) ) {
			$linkToListArgs = array(
				'url'   => esc_url( get_admin_url( get_current_blog_id(), 'admin.php?page=' . $data['link_to_list'] ) ),
				'title' => __( 'Back to list', CLICKWHALE_NAME )
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
				'title' => __( 'Add new', CLICKWHALE_NAME )
			);
			$linkToAdd     = '<a class="page-title-action" href="' . $linkToAddArgs['url'] . '">' . $linkToAddArgs['title'] . '</a>';
		}

		if ( ! empty( $data['link_to_view'] ) ) {
			$linkToViewArgs = array(
				'url'   => $data['link_to_view'],
				'title' => __( 'View page', CLICKWHALE_NAME )
			);
			$linkToView     = '<a class="page-title-action" target="_blank" rel="noopener" href="' . $linkToViewArgs['url'] . '">' . $linkToViewArgs['title'] . '</a>';
		}

		if ( ! empty( $data['link_custom'] ) ) {
			$linkCustomArgs = array(
				'url'   => $data['link_custom']['url'],
				'title' => $data['link_custom']['title']
			);
			$linkCustom     = '<a class="page-title-action" target="_blank" rel="noopener" href="' . $linkCustomArgs['url'] . '">' . $linkCustomArgs['title'] . '</a>';
		}

		return '<h1 class="wp-heading-inline">' . $wpHeading . ' ' . $linkToList . $linkToAdd . $linkToView . $linkCustom . '</h1>';

	}

	public static function get_sort_params( $columns, $order, $orderby ): array {
		$result = array(
			'order'   => 'desc',
			'orderby' => 'id',
		);

		if ( $order ) {
			$orderArg        = htmlspecialchars( $order, ENT_QUOTES );
			$result['order'] = in_array( $orderArg, array( 'asc', 'desc' ) ) ? $orderArg : $order;
		}

		if ( $orderby ) {
			$orderByArg        = htmlspecialchars( $orderby, ENT_QUOTES );
			$result['orderby'] = in_array( $orderByArg, array_keys( $columns ) ) ? $orderByArg : $orderby;
		}

		return $result;
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

	/**
	 * @return string
	 * @since 1.4.0
	 */
	public static function admin_pro_label(): string {
		return apply_filters( 'clickwhale_admin_pro_label', '<em class="clickwhale-pro-label">PRO</em>' );
	}

	public static function get_pro_message( $prompt = '' ) {
        if ( '' == $prompt ) {
            $prompt = __( 'Unlimited with', CLICKWHALE_NAME );
        }

		return apply_filters(
			'clickwhale_get_pro_message',
            ' <strong>' . $prompt . ' ' . sprintf(
				__( '<a href="%s" rel="noopener" target="_blank">ClickWhale PRO</a>', CLICKWHALE_NAME ),
				self::get_pro_link()
			) . '</strong>'
		);
	}

    /**
     * @return string
     */
    public static function get_affiliates_link(): string {
        return 'https://clickwhale.pro/affiliates/?utm_source=users&utm_medium=link&utm_campaign=plugin_admin&utm_content=settings_affiliate_program';
    }

	private static function public_path( bool $trimmed = false ): string {
		if ( ! isset( $_SERVER['HTTP_HOST'] ) ) {
			$_SERVER['HTTP_HOST'] = 'localhost';
		}

		$actual_link = ( empty( $_SERVER['HTTPS'] ) ? 'http' : 'https' ) . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

		$actual_link = str_replace( get_bloginfo( 'url' ), '', $actual_link );
		$result      = untrailingslashit( parse_url( $actual_link, PHP_URL_PATH ) );

		if ( $trimmed ) {
			return ltrim( str_replace( $_SERVER['HTTP_HOST'], '', $result ), '/' );
		} else {
			return $result;
		}
	}

	public static function get_public_path( bool $is_trimmed = false ): string {
		return self::public_path( $is_trimmed );
	}

	public static function get_import_default_columns(): array {
		return array( 'title', 'slug', 'url', 'redirection', 'nofollow', 'sponsored' );
	}

	/**
	 * @return array
	 * @since 1.6.0
	 */
	public static function get_post_types( $label = 'singular_name' ): array {
		$posts      = [];
		$args       = array(
			'public' => true,
		);
		$post_types = get_post_types( $args, 'objects' );
		unset( $post_types['attachment'] );

		foreach ( $post_types as $post_type ) {
			$posts[ $post_type->name ] = $post_type->labels->{$label};
		}

		return $posts;
	}
}
