<?php

namespace Clickwhale\Front\SmartDisplays;

use Clickwhale\Helpers\Links_Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Clickwhale_Smart_Display_Template {

	/**
	 * @var array $data
	 */
	private array $data;

	/**
	 * @var array $link
	 */
	private array $link = array();

	/**
	 * @var string
	 */
	private string $link_url;

	/**
	 * @var string
	 */
	private string $link_target;

	/**
	 * @var array
	 */
	private array $smart_displays_options;

	/**
	 * @var array
	 */
	private array $plugin_defaults;

	public function __construct( array $data ) {
		$this->data                   = $data;
		$this->data['options']        = maybe_unserialize( $data['options'] ?? [] );
		$this->smart_displays_options = get_option( 'clickwhale_smart_displays_options' );
		$this->plugin_defaults        = clickwhale()->settings->default_options();
		$this->set_link();
		$this->set_link_url();
		$this->set_link_target();
	}

	private function set_link(): void {
		if ( empty( $this->data['link_id'] ) ) {
			$this->link = array();

			return;
		}

		$this->link = Links_Helper::get_by_id( $this->data['link_id'] );
	}

	private function set_link_url(): void {
		if ( ! empty( $this->link['slug'] ) ) {
			$this->link_url = esc_url( trailingslashit( home_url( $this->link['slug'] ) ) );

			return;
		}
		$link_url       = $this->data['options']['link_url'] ?? '';
		$this->link_url = $link_url ? esc_url( $link_url ) : '';
	}

	private function set_link_target(): void {
		$this->link_target = '';

		if ( empty( $this->link ) && ! empty( $this->data['options']['link_url'] ) ) {
			$this->link_target = ' target="_blank"';

			return;
		}

		if ( ! isset( $this->link['link_target'] ) ) {
			return;
		}

		$target_arg = $this->link['link_target'];

		if ( '' === $target_arg ) {
			$link_manager_options = get_option( 'clickwhale_link_manager_options' );
			$target_arg           = $link_manager_options['link_target'] ?? $this->plugin_defaults['link_manager']['options']['link_target'];
		}

		$target_arg = esc_attr( $target_arg );

		if ( 'blank' === $target_arg ) {
			$this->link_target = ' target="_blank"';
		}
	}

	public function get_id(): int {
		return intval( $this->data['id'] );
	}

	public function get_title(): string {
		$title = esc_html( wp_unslash( $this->data['title'] ) );

		if ( ! $this->link_url ) {
			return $title;
		}

		return sprintf(
			'<a href="%1$s"%2$s>%3$s</a>',
			$this->link_url,
			$this->link_target,
			$title
		);
	}

	public function get_image(): string {
		$img_id  = intval( $this->data['image'] ?? 0 );
		$img_url = '';

		if ( $img_id ) {
			$img_url = wp_get_attachment_image_url( $img_id, 'full' ) ?: '';
		}

		if ( empty( $img_url ) ) {
			$img_url = $this->data['options']['image_url'] ?? '';
		}

		if ( empty( $img_url ) ) {
			return '';
		}

		$img_html = '<img src="' . esc_url( $img_url ) . '" alt="" />';

		if ( $this->link_url ) {
			return sprintf(
				'<a href="%1$s"%2$s>%3$s</a>',
				$this->link_url,
				$this->link_target,
				$img_html
			);
		}

		return $img_html;
	}

	public function get_description(): string {
		if ( empty( $this->data['description'] ) ) {
			return '';
		}

		if ( ! empty( $this->data['options']['override']['description'] ) ) {
			return wpautop( wp_kses_post( $this->data['description'] ) );
		}

		$integrations_options = get_option( 'clickwhale_integrations_options', array() );
		$max_items            = intval( $integrations_options['amz_connect']['fields']['max_list_items'] ?? 3 );
		$overflow             = $integrations_options['amz_connect']['fields']['overflow_behavior'] ?? 'truncate';

		$description = $this->data['description'];

		if ( $max_items > 0 && preg_match( '/<ul[^>]*>(.*?)<\/ul>/si', $description, $ul_match ) ) {
			preg_match_all( '/<li[^>]*>.*?<\/li>/si', $ul_match[1], $li_matches );
			$items = $li_matches[0];

			if ( count( $items ) > $max_items ) {
				$visible      = array_slice( $items, 0, $max_items );
				$excess       = array_slice( $items, $max_items );
				$visible_html = '<ul>' . implode( '', $visible ) . '</ul>';

				if ( 'show_more' === $overflow ) {
					$summary_html = '<span class="cw-show-more-label">' . esc_html__( 'Show more', 'clickwhale' ) . '</span>'
					                . '<span class="cw-show-less-label">' . esc_html__( 'Show less', 'clickwhale' ) . '</span>';
					$description  = $visible_html . '<details class="cw-sd-show-more"><summary>' . $summary_html . '</summary><ul>' . implode( '', $excess ) . '</ul></details>';

					return wp_kses( $description, array_merge(
						wp_kses_allowed_html( 'post' ),
						array(
							'details' => array( 'class' => true, 'open' => true ),
							'summary' => array(),
							'span'    => array( 'class' => true ),
						)
					) );
				}

				$description = $visible_html;

				return wp_kses_post( $description );
			}
		}

		return wpautop( wp_kses_post( $description ) );
	}

	public function get_disclosure(): string {
		return esc_html( $this->smart_displays_options['disclosure'] ?? '' );
	}

	public function get_price(): string {
		return esc_html( $this->data['options']['price'] ?? '' );
	}

	public function get_primary_button(): string {
		if ( '' === $this->link_url ) {
			return '';
		}

		if ( ! empty( $this->data['options']['primary']['text'] ) ) {
			$text = $this->data['options']['primary']['text'];
		} elseif ( ! empty( $this->data['options']['link_url'] ) ) {
			$integrations_options = get_option( 'clickwhale_integrations_options', array() );
			$text = ! empty( $integrations_options['amz_connect']['fields']['button_text'] )
				? $integrations_options['amz_connect']['fields']['button_text']
				: 'Buy on Amazon';
		} else {
			$text = $this->smart_displays_options['primary']['text'] ?? '';
		}

		if ( '' === $text ) {
			return '';
		}

		return sprintf(
			'<a href="%1$s" class="cw-smart-display-public--primary-button"%2$s>%3$s</a>',
			$this->link_url,
			$this->link_target,
			esc_html( $text )
		);
	}

	/**
	 * "Powered by ClickWhale" credit shown at the bottom of every Smart
	 * Display in the free version (mirrors the Link Pages credit,
	 * Clickwhale\Front\Clickwhale_Public_Linkpage::get_credits_link()).
	 * Unlike Link Pages this isn't a real on/off setting in free; it's
	 * always shown, teased in Settings as something Pro can remove.
	 *
	 * @return string
	 * @since 2.8.3
	 */
	public function get_credits(): string {
		if ( ! function_exists( 'clickwhale_fs' ) || clickwhale_fs()->can_use_premium_code() ) {
			return '';
		}

		$img = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 239.4 54.9" xml:space="preserve" fill="currentColor"><path d="M11.2 54.9c2.9.1 5.7-1 7.6-3.1.5-.5.8-1.2.8-2 0-1.6-1.3-2.9-2.9-2.9-.8 0-1.6.4-2.2 1-.8 1-2 1.5-3.3 1.5-2.7 0-4.9-2.2-4.9-4.9v-.2c-.1-2.7 1.9-5 4.6-5.2h.2c1.3 0 2.4.6 3.3 1.5.6.6 1.4 1 2.2 1 1.6 0 3-1.3 3-2.9 0-.7-.3-1.5-.8-2-2-2.1-4.8-3.2-7.6-3.1C4.9 33.5 0 37.9 0 44.2c0 6.4 4.9 10.7 11.2 10.7zm19.2-.3h8.4c1.5 0 2.7-1.2 2.7-2.7 0-1.5-1.2-2.7-2.7-2.7h-5.4V36.7c0-1.7-1.4-3.1-3.1-3.1-1.7 0-3.1 1.4-3.1 3.1v14.5c-.2 1.6 1 3.1 2.6 3.3.1.1.3.1.6.1zm21.1.3c1.7 0 3.1-1.4 3.1-3.1v-15c0-1.7-1.4-3.1-3.1-3.1-1.7 0-3.1 1.4-3.1 3.1v15c-.1 1.7 1.3 3.1 3.1 3.1zm21.2 0c2.9.1 5.7-1 7.6-3.1.5-.5.8-1.2.8-2 0-1.6-1.3-2.9-2.9-2.9-.8 0-1.6.4-2.2 1-.8 1-2 1.5-3.3 1.5-2.7 0-4.9-2.2-4.9-4.9v-.2c-.1-2.7 1.9-5 4.6-5.2h.2c1.3 0 2.4.6 3.3 1.5.6.6 1.4 1 2.2 1 1.6 0 3-1.3 3-2.9 0-.7-.3-1.5-.8-2-2-2.1-4.8-3.2-7.6-3.1-6.3 0-11.2 4.3-11.2 10.7 0 6.3 4.9 10.6 11.2 10.6zm33.3-5-5.3-6.4 4.7-5.1c.5-.5.7-1.2.7-1.9 0-1.6-1.3-2.9-2.9-2.9-.8 0-1.6.3-2.2 1l-6.2 7.1v-4.9c0-1.7-1.4-3.1-3.1-3.1-1.7 0-3.1 1.4-3.1 3.1v15c0 1.7 1.4 3.1 3.1 3.1 1.7 0 3.1-1.4 3.1-3.1v-2.9l1.3-1.5 5.2 6.5c.6.7 1.4 1 2.3 1 1.7 0 3-1.4 3-3.1.1-.7-.2-1.4-.6-1.9zm27.1 5c1.8 0 3.5-1.2 4-3l4-14.1c.1-.3.1-.6.1-.9 0-1.8-1.5-3.3-3.3-3.3-1.6 0-2.9 1.1-3.3 2.6l-2.1 10.3-2.7-10.8c-.3-1.3-1.5-2.2-2.8-2.2-1.3 0-2.5.9-2.8 2.2l-2.7 10.8-2.2-10.4c-.3-1.5-1.7-2.6-3.2-2.6-1.8 0-3.3 1.4-3.3 3.2 0 .3 0 .6.1.9l4 14.1c.5 1.8 2.1 3 4 3s3.6-1.3 4-3.2l2-8.8 2 8.8c.6 2 2.3 3.4 4.2 3.4zm31.9 0c1.7 0 3.1-1.4 3.1-3.1v-15c0-1.7-1.4-3.1-3.1-3.1-1.7 0-3.1 1.4-3.1 3.1v4.5h-7.4v-4.5c0-1.7-1.4-3.1-3.1-3.1-1.7 0-3.1 1.4-3.1 3.1v15c0 1.7 1.4 3.1 3.1 3.1 1.7 0 3.1-1.4 3.1-3.1v-5.1h7.4v5.1c0 1.7 1.4 3.1 3.1 3.1zm30.3-4.2-5-13.5c-.8-2.2-2.9-3.6-5.1-3.6-2.3 0-4.4 1.4-5.1 3.6l-5 13.5c-.1.3-.2.7-.2 1.1 0 1.7 1.4 3.1 3.1 3.1 1.4 0 2.6-.9 3-2.2l.3-1.1h7.7l.3 1.1c.4 1.3 1.6 2.2 3 2.2 1.7 0 3.1-1.4 3.1-3.1.1-.4 0-.8-.1-1.1zM183 46.3l2.2-6.9 2.2 6.9H183zm22.5 8.3h8.4c1.5 0 2.7-1.2 2.7-2.7 0-1.5-1.2-2.7-2.7-2.7h-5.4V36.7c0-1.7-1.4-3.1-3.1-3.1-1.7 0-3.1 1.4-3.1 3.1v14.5c-.2 1.6 1 3.1 2.6 3.3.1.1.4.1.6.1zm21.3 0h10.1c1.4 0 2.6-1.2 2.6-2.6 0-1.4-1.2-2.6-2.6-2.6h-7.1v-2.6h6.9c1.4 0 2.6-1.2 2.6-2.6 0-1.4-1.2-2.6-2.6-2.6h-6.9v-2.4h7.1c1.4 0 2.6-1.2 2.6-2.6 0-1.4-1.2-2.6-2.6-2.6h-10.1c-1.6-.2-3.1 1-3.3 2.6v14.7c-.2 1.6 1 3.1 2.6 3.3h.7zM52.3 28.3l3.3-19.1c.1-.7-.3-1.3-1-1.4H54l-5 1.6c-.7.2-1.4-.2-1.6-.8l-2.1-6.9c-.2-.7-.9-1-1.6-.8l-1.7.5c-.7.2-1 .9-.8 1.6l2.1 6.9c.2.7-.2 1.4-.8 1.6l-4.8 1.4c-.7.2-1 .9-.8 1.6.1.2.2.4.3.5l13.1 14.1c.5.5 1.3.5 1.8.1.1-.4.2-.7.2-.9M56.8 30h-.2c-1.1-.3-1.7-1.3-1.5-2.4l1.8-7.7c.3-1.1 1.3-1.7 2.4-1.5 1.1.3 1.7 1.3 1.5 2.4L59 28.5c-.3 1-1.2 1.6-2.2 1.5zM45.9 33.7c-.3 0-.5-.1-.7-.2l-6.3-3.6c-1-.5-1.3-1.8-.8-2.7.5-1 1.8-1.3 2.7-.8l6.3 3.6c1 .5 1.3 1.8.7 2.7-.3.8-1.1 1.1-1.9 1z"/></svg>';

		$link = 'https://clickwhale.pro/smart-displays/';
		$utm  = '?utm_source=users&utm_medium=smart_display&utm_campaign=ClickWhale+Users+Smart+Displays&utm_content=copyright_link';

		return sprintf(
				'<a class="cw-smart-display-public--credits" target="_blank" href="%1$s">%2$s %3$s</a>',
				esc_url( apply_filters( 'clickwhale_smart_display_credits_link', $link, $utm ) ),
				esc_html__( 'Powered by', 'clickwhale' ),
				$img
		);
	}

	/**
	 * Global styles for all SD instances on the page.
	 * Static so it can be called from wp_enqueue_scripts before any instance exists.
	 *
	 * @return string
	 */
	public static function get_smart_display_styles(): string {
		$plugin_defaults        = clickwhale()->settings->default_options();
		$smart_displays_options = get_option( 'clickwhale_smart_displays_options' );
		$defaults               = $plugin_defaults['smart_displays']['options'];

		$container = $defaults['container'];
		$primary   = $smart_displays_options['primary'] ?? $defaults['primary'];

		// Container
		$border_width       = intval( $container['border']['width']['value'] );
		$border_style       = esc_attr( $container['border']['style'] );
		$border_color       = esc_attr( $container['border']['color'] ?: 'transparent' );
		$border_color_hover = esc_attr( $container['border']['color_hover'] ?: 'transparent' );
		$border_radius      = intval( $container['border']['radius']['value'] );
		$padding            = intval( $container['padding']['value'] );
		$box_shadow         = esc_attr( $container['box_shadow'] );

		// Primary button
		$primary_color              = esc_attr( $primary['color'] );
		$primary_color_hover        = esc_attr( $primary['color_hover'] );
		$primary_bg_color           = esc_attr( $primary['bg_color'] );
		$primary_bg_color_hover     = esc_attr( $primary['bg_color_hover'] );
		$primary_border_width       = intval( $primary['border']['width']['value'] );
		$primary_border_style       = esc_attr( $primary['border']['style'] );
		$primary_border_color       = esc_attr( $primary['border']['color'] );
		$primary_border_color_hover = esc_attr( $primary['border']['color_hover'] );
		$primary_border_radius      = intval( $primary['border']['radius']['value'] );

		$css = '';

		// CSS custom properties
		$css .= '.cw-smart-display-preview, .cw-smart-display-public--wrap{';
		$css .= "--clickwhale-sd--border-width:{$border_width}px;";
		$css .= "--clickwhale-sd--border-style:{$border_style};";
		$css .= "--clickwhale-sd--border-color:{$border_color};";
		$css .= "--clickwhale-sd--border-color-hover:{$border_color_hover};";
		$css .= "--clickwhale-sd--border-radius:{$border_radius}px;";
		$css .= "--clickwhale-sd--padding:{$padding}px;";
		$css .= "--clickwhale-sd--box-shadow:{$box_shadow};";
		$css .= "--clickwhale-sd--primary-color:var(--clickwhale-sd-preview-primary-color, {$primary_color});";
		$css .= "--clickwhale-sd--primary-color-hover:{$primary_color_hover};";
		$css .= "--clickwhale-sd--primary-bg-color:{$primary_bg_color};";
		$css .= "--clickwhale-sd--primary-bg-color-hover:{$primary_bg_color_hover};";
		$css .= "--clickwhale-sd--primary-border-width:{$primary_border_width}px;";
		$css .= "--clickwhale-sd--primary-border-style:{$primary_border_style};";
		$css .= "--clickwhale-sd--primary-border-color:{$primary_border_color};";
		$css .= "--clickwhale-sd--primary-border-color-hover:{$primary_border_color_hover};";
		$css .= "--clickwhale-sd--primary-border-radius:{$primary_border_radius}px;";

		$css .= 'display:flex;gap:calc(var(--clickwhale-sd--padding) * 1.25);background-color:#fff;font-family:inherit;box-sizing:border-box;';
		$css .= 'border-width:var(--clickwhale-sd--border-width);';
		$css .= 'border-style:var(--clickwhale-sd--border-style);';
		$css .= 'border-color:var(--clickwhale-sd--border-color);';
		$css .= 'border-radius:var(--clickwhale-sd--border-radius);';
		$css .= 'padding:var(--clickwhale-sd--padding);';
		$css .= 'box-shadow:var(--clickwhale-sd--box-shadow);';
		$css .= 'max-width:clamp(300px, 50rem, var(--wp--style--global--content-size));';
		$css .= 'margin:1.5rem auto;';

		$css .= '&:hover, &:focus{';
		$css .= 'border-color:var(--clickwhale-sd--border-color-hover);';
		$css .= '}';

		$css .= '.cw-smart-display-preview--image, .cw-smart-display-public--image{';
		$css .= 'display:flex;flex:1;justify-content:center;align-items:flex-start;width:100%;height:100%;';

		$css .= 'img{';
		$css .= 'display:block;width:100%;max-width:100%;height:auto;box-shadow:none;';
		$css .= 'border-radius:var(--clickwhale-sd--image-border-radius, .5rem);';
		$css .= '}';

		$css .= 'a:hover, a:focus{';
		$css .= 'opacity:.9;';
		$css .= '}';

		$css .= '}';//.cw-smart-display-preview--image, .cw-smart-display-public--image

		$css .= '.cw-smart-display-preview--content, .cw-smart-display-public--content{';
		$css .= 'flex:2;display:flex;flex-direction:column;justify-content:flex-start;gap:.5rem;';

		$css .= '.cw-smart-display-public--credits{';
		$css .= 'display:inline-flex;align-items:center;gap:4px;align-self:flex-start;margin-top:.25rem;';
		$css .= 'font-size:.7rem;line-height:1em;color:inherit;opacity:.55;text-decoration:none;';
		$css .= '}';

		$css .= '.cw-smart-display-public--credits svg{';
		$css .= 'display:block;width:56px;height:auto;';
		$css .= '}';

		$css .= '.cw-smart-display-public--credits:hover,';
		$css .= '.cw-smart-display-public--credits:focus{';
		$css .= 'opacity:1;color:inherit;';
		$css .= '}';

		$css .= '}';

		$css .= '.cw-smart-display-preview--header,.cw-smart-display-public--header{';
		$css .= 'display:flex;flex-direction:column;gap:(--clickwhale-sd--header-gap, .5rem);';
		$css .= '}';

		$css .= '.cw-smart-display-preview--title, .cw-smart-display-public--title{';
		$css .= 'color:var(--clickwhale-sd--title-color, #1a1c1d);';
		$css .= 'font-size:var(--clickwhale-sd--title-font-size, 1rem);line-height:1.25;font-weight:700;';

		$css .= 'a{';
		$css .= 'display:block;color:var(--clickwhale-sd--title-color, #1a1c1d);';
		$css .= 'text-decoration:none;';

		$css .= '&:hover, &:focus{';
		$css .= 'color:var(--clickwhale-sd--title-color-hover, #397eff);';
		$css .= 'text-decoration:underline;';
		$css .= '}';

		$css .= '}'; //a

		$css .= '}'; //.cw-smart-display-preview--title, .cw-smart-display-public--title

		$css .= '.cw-smart-display-preview--description, .cw-smart-display-public--description{';
		$css .= 'font-family:inherit;font-size:var(--clickwhale-sd--text-font-size, .875rem);line-height:var(--clickwhale-sd--text-line-height, 1.5em);font-weight:400;';

		$css .= '*{';
		$css .= 'font-family:inherit;font-size:var(--clickwhale-sd--text-font-size, .875rem);line-height:var(--clickwhale-sd--text-line-height, 1.5em);';
		$css .= '}';

		$css .= '& > * {';
		$css .= 'padding:0;margin:0;';

		$css .= '& + * {';
		$css .= 'margin-top:var(--clickwhale-sd--description-spacing, .5rem);';
		$css .= '}';

		$css .= '}'; // & > *

		$css .= 'ul, ol{';
		$css .=     'padding-left: 1rem;';
		$css .=     'li{';
		$css .=         'margin-top: .25rem;';
		$css .=     '}';
		$css .= '}';
		$css .= 'ul li{list-style:disc;}';

		$css .= '.cw-sd-show-more{';
		$css .=     'display:flex; flex-direction:column-reverse; gap:0; margin: 0;';
		$css .=     'summary{';
		$css .=         'padding-left: 1rem; list-style:none; outline:none; color:var(--clickwhale-sd--primary-bg-color); cursor:pointer;';
		$css .=         'span{';
		$css .=             'font-size: .75rem; border-bottom:1px dotted var(--clickwhale-sd--primary-bg-color);';
		$css .=             '&.cw-show-less-label{display:none;}';
		$css .=         '}';
		$css .=         '&::-webkit-details-marker{display:none;}';
		$css .=         '&:hover, &:focus{';
		$css .=             'color: var(--clickwhale-sd--primary-bg-color-hover);';
		$css .=             'span{';
		$css .=                 'border-bottom-color: var(--clickwhale-sd--primary-bg-color-hover);';
		$css .=             '}';
		$css .=         '}';
		$css .=     '}'; // summary
		$css .=     'ul{margin: 0;}';
		$css .=     '&[open]{';
		$css .=         'summary{';
		$css .=             'span{';
		$css .=                 '&.cw-show-more-label{display:none;}';
		$css .=                 '&.cw-show-less-label{display:inline-block;}';
		$css .=             '}';
		$css .=         '}';
		$css .=     '}';
		$css .= '}'; // .cw-sd-show-more / details

		$css .= '}'; //.cw-smart-display-preview--description, .cw-smart-display-public--description


		$css .= '.cw-smart-display-preview--footer, .cw-smart-display-public--footer{';
		$css .= 'display:flex;flex-direction:column;justify-content:flex-end;gap:var(--clickwhale-sd--footer-gap, 1rem);height:100%;margin-top:var(--clickwhale-sd--footer-margin, 1.5rem);';
		$css .= '}';

		$css .= '.cw-smart-display-preview--price, .cw-smart-display-public--price{';
		$css .= 'font-size:1rem;line-height:1;font-weight:700;';
		$css .= '}';

		$css .= '.cw-smart-display-preview--buttons, .cw-smart-display-public--buttons{';
		$css .= 'display:flex;flex-direction:row;justify-content:flex-start;align-items:center;gap:1rem;';

		$css .= 'a{';
		$css .= 'position:relative;display:inline-block;padding:.5rem 1.5rem;';
		$css .= 'font-size:var(--clickwhale-sd--button-font-size, 1rem);font-weight:700;line-height:1; font-family:inherit;';
		$css .= 'text-decoration:none !important;text-transform:none;';
		$css .= 'box-shadow:none;white-space:nowrap;transition:all .2s ease-in-out;';
		$css .= 'color:var(--clickwhale-sd--primary-color);';
		$css .= 'background-color:var(--clickwhale-sd--primary-bg-color);';
		$css .= 'border-width:var(--clickwhale-sd--primary-border-width);';
		$css .= 'border-style:var(--clickwhale-sd--primary-border-style);';
		$css .= 'border-color:var(--clickwhale-sd--primary-border-color);';
		$css .= 'border-radius:var(--clickwhale-sd--primary-border-radius);';

		$css .= '&:hover, &:focus{';
		$css .= 'text-decoration:none !important;';
		$css .= 'color:var(--clickwhale-sd--primary-color-hover);';
		$css .= 'background-color:var(--clickwhale-sd--primary-bg-color-hover);';
		$css .= 'border-color:var(--clickwhale-sd--primary-border-color-hover);';
		$css .= '}';

		$css .= '}'; //a

		$css .= '}'; //.cw-smart-display-preview--buttons, .cw-smart-display-public--buttons

		$css .= '.cw-smart-display-public--disclosure{';
		$css .= 'padding:0;font-size:.75rem;line-height:1.25em;opacity:.75;';
		$css .= '}';

		$css .= '.cw-smart-display-public--title,';
		$css .= '.cw-smart-display-public--description,';
		$css .= '.cw-smart-display-public--disclosure{';
		$css .= 'word-wrap:break-word;overflow-wrap:anywhere;white-space:normal;';
		$css .= '}';

		$css .= '}';//.cw-smart-display-preview--wrap, .cw-smart-display-public--wrap

		$css .= '.wp-block-clickwhale-smart-display{';
		$css .= '&.align{max-width:var(--wp--style--global--content-size);}';
		$css .= '&.alignwide{max-width:var(--wp--style--global--wide-size);}';

		$css .= '.cw-smart-display-public--wrap{max-width:100%;}';
		$css .= '}'; //.wp-block-clickwhale-smart-display

		$css .= '@media (max-width: 768px){';
		$css .= '.cw-smart-display-public--wrap{flex-direction:column;align-items:center;padding:1.25rem;gap:1rem;}';
		$css .= '.cw-smart-display-public--content{width:100%;flex:1;}';
		$css .= '.cw-smart-display-public--title, .cw-smart-display-public--title a{font-size:1.25rem;}';
		$css .= '.cw-smart-display-public--buttons{justify-content:center;}';
		$css .= '.cw-smart-display-public--buttons a{width:100%;text-align:center;}';
		$css .= '}';

		return $css;
	}
}