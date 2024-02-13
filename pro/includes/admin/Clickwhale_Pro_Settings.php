<?php
namespace clickwhale_pro\includes\admin;

use clickwhale\includes\admin\Clickwhale_Settings;
use clickwhale\includes\helpers\Helper;
use clickwhale\includes\helpers\traits\{Singleton_Clone, Singleton_Wakeup};

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Clickwhale_Pro_Settings {
	/**
	 * @since    1.0.0
	 * @var Clickwhale_Pro_Settings
	 */
	private static $instance;

	/**
	 * @return Clickwhale_Pro_Settings
	 */
	public static function get_instance(): Clickwhale_Pro_Settings {
		if ( empty( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	use Singleton_Clone;
	use Singleton_Wakeup;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	private function __construct() {
		if ( ! get_option( 'clickwhale_pro_version' ) ) {
			add_option( 'clickwhale_pro_version', CLICKWHALE_PRO_VERSION );
		}
	}

	public function default_options(): array {
		return array();
	}

	public function settings_defaults( $fields ): array {
		$tracking_code_options = array(
			'tracking_codes_credits' => 0,
		);
		$other_options         = array(
			'affiliate_id' => '',
		);

		$fields['tracking_codes']['options'][] = $tracking_code_options;
		$fields['other']['options'][]          = $other_options;

		return $fields;
	}

	public function settings_fields() {
		$defaults               = $this->default_options();
		$general_options        = get_option( 'clickwhale_general_options' );
		$tracking_options       = get_option( 'clickwhale_tracking_options' );
		$linkpages_options      = get_option( 'clickwhale_linkpages_options' );
		$tracking_codes_options = get_option( 'clickwhale_tracking_codes_options' );
		$other_options          = get_option( 'clickwhale_other_options' );

		if ( $defaults ) {
			foreach ( $defaults as $k => $v ) {

				add_settings_section(
					$k . '_settings_section',
					$v['name'],
					array( get_class( Clickwhale_Settings::get_instance() ), 'settings_section_callback' ),
					'clickwhale_' . $k . '_options',
					array( 'text' => $v['text'] )
				);

				register_setting(
					'clickwhale_' . $k . '_options',
					'clickwhale_' . $k . '_options'
				);
			}
		}
		add_settings_field(
			'linkpage_credits',
			__( 'Credits', CLICKWHALE_PRO_NAME ),
			array( $this, 'render_control' ),
			'clickwhale_linkpages_options',
			'linkpages_settings_section',
			array(
				'control' => 'checkbox',
				'id'      => 'linkpage_credits',
				'name'    => 'clickwhale_linkpages_options[linkpage_credits]',
				'value'   => isset( $linkpages_options['linkpage_credits'] ) ? 1 : 0,
				'label'   => __( 'Check to hide Link Page credits.', CLICKWHALE_PRO_NAME )
			)
		);

		add_settings_field(
			'tracking_codes_credits',
			__( 'Tracking Codes Credits', CLICKWHALE_PRO_NAME ),
			array( $this, 'render_control' ),
			'clickwhale_tracking_codes_options',
			'tracking_codes_settings_section',
			array(
				'control' => 'checkbox',
				'id'      => 'tracking_codes_credits',
				'name'    => 'clickwhale_tracking_codes_options[tracking_codes_credits]',
				'value'   => isset( $tracking_codes_options['tracking_codes_credits'] ) ? 1 : 0,
				'label'   => __( 'Hide plugin HTML credits for the active tracking codes.', CLICKWHALE_PRO_NAME )
			)
		);

		add_settings_field(
			'affiliate_id',
			__( 'Affiliate ID', CLICKWHALE_PRO_NAME ),
			array( $this, 'render_control' ),
			'clickwhale_other_options',
			'other_settings_section',
			array(
				'control'     => 'input',
				'id'          => 'affiliate_id',
				'name'        => 'clickwhale_other_options[affiliate_id]',
				'type'        => 'text',
				'value'       => $other_options['affiliate_id'] ?? '',
				'placeholder' => '123456',
				'description' => __( 'Enter your Affiliate ID.', CLICKWHALE_PRO_NAME )
			)
		);
	}

	public function settings_tabs( $fields ): array {
		$tabs = array();

		return array_merge( $fields, $tabs );
	}

	public function render_control( $args ) {
		echo Helper::render_control( $args );
	}

    public static function render_tabs(): array {
        return array(
            'tracking_codes' => array(
                'name' => __( 'Tracking Codes', CLICKWHALE_NAME ),
                'url'  => 'tracking_codes_options'
            ),
            'other'          => array(
                'name' => __( 'Other Options', CLICKWHALE_NAME ),
                'url'  => 'other_options'
            ),
        );
    }

}