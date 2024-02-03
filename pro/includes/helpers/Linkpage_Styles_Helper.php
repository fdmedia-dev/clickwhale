<?php
namespace clickwhale_pro\includes\helpers;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Linkpage_Styles_Helper {

	public static function get_gradients(): array {
		return array(
			array(
				'style'     => 'linear',
				'direction' => 'to right',
				'colors'    => array(
					'0'   => '#00b09b',
					'100' => '#96c93d'
				),
			),
			array(
				'style'     => 'linear',
				'direction' => 'to right',
				'colors'    => array(
					'0'   => '#F37335',
					'100' => '#FDC830'
				),
			),
			array(
				'style'     => 'linear',
				'direction' => 'to right',
				'colors'    => array(
					'0'   => '#cc5333',
					'100' => '#23074d'
				),
			),
			array(
				'style'     => 'linear',
				'direction' => 'to right',
				'colors'    => array(
					'0'   => '#FFAFBD',
					'100' => '#C9FFBF'
				),
			),
			array(
				'style'     => 'linear',
				'direction' => 'to right',
				'colors'    => array(
					'0'   => '#CFDEF3',
					'100' => '#E0EAFC'
				),
			),
			array(
				'name'      => 'Purple Bliss',
				'style'     => 'linear',
				'direction' => 'to right',
				'colors'    => array(
					'0'   => '#360033',
					'100' => '#0b8793'
				),
			),
			array(
				'name'      => 'MegaTron',
				'style'     => 'linear',
				'direction' => 'to right',
				'colors'    => array(
					'0'   => '#C6FFDD',
					'50'  => '#FBD786',
					'100' => '#f7797d'
				),
			),
			array(
				'name'      => 'Monte Carlo',
				'style'     => 'linear',
				'direction' => 'to right',
				'colors'    => array(
					'0'   => '#CC95C0',
					'50'  => '#DBD4B4',
					'100' => '#7AA1D2'
				),
			),
			array(
				'name'      => 'Instagram',
				'style'     => 'linear',
				'direction' => 'to right',
				'colors'    => array(
					'0'   => '#833ab4',
					'50'  => '#fd1d1d',
					'100' => '#7AA1D2'
				),
			),
			array(
				'name'      => 'JShine',
				'style'     => 'linear',
				'direction' => 'to right',
				'colors'    => array(
					'0'   => '#12c2e9',
					'50'  => '#c471ed',
					'100' => '#f7797d'
				),
			),
			array(
				'name'      => 'Argon',
				'style'     => 'linear',
				'direction' => 'to right',
				'colors'    => array(
					'0'   => '#03001e',
					'33'  => '#7303c0',
					'66'  => '#f7797d',
					'100' => '#fdeff9'
				),
			),
			array(
				'name'      => 'Midnight City',
				'style'     => 'linear',
				'direction' => 'to right',
				'colors'    => array(
					'0'   => '#232526',
					'100' => '#414345'
				),
			),
			array(
				'name'      => 'Firewatch',
				'style'     => 'linear',
				'direction' => 'to right',
				'colors'    => array(
					'0'   => '#cb2d3e',
					'100' => '#ef473a'
				),
			),
			array(
				'name'      => 'Ver',
				'style'     => 'linear',
				'direction' => 'to right',
				'colors'    => array(
					'0'   => '#FFE000',
					'100' => '#799F0C'
				),
			),
			array(
				'name'      => 'Rainbow',
				'style'     => 'linear',
				'direction' => 'to right',
				'colors'    => array(
					'0'   => 'red',
					'16'  => 'orange',
					'33'  => 'yellow',
					'50'  => 'green',
					'66'  => 'blue',
					'84'  => 'Indigo',
					'100' => 'violet'
				),
			),
		);
	}

	public static function get_patterns(): array {
		$result   = [];
		$patterns = scandir( WP_PLUGIN_DIR . '/' . CLICKWHALE_PRO_SLUG . '/assets/front/patterns/' );

		foreach ( $patterns as $k => $pattern ) {
			if ( $pattern === '.' || $pattern === '..' ) {
				continue;
			}

			$pattern_info = pathinfo( $pattern );
			if ( $pattern_info['extension'] !== 'svg' ) {
				continue;
			}

			$result[ $k ]['name'] = preg_replace( '/\.\w+$/', '', $pattern );
			$result[ $k ]['url']  = CLICKWHALE_PRO_PUBLIC_ASSETS_DIR . '/patterns/' . $pattern;
		}

		return $result;
	}
}