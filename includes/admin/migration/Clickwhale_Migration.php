<?php
namespace clickwhale\includes\admin\migration;

use clickwhale\includes\helpers\Helper;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Clickwhale_Migration {

    /**
     * @var string
     */
	private $options;

    /**
     * @var string
     */
	private $last_migration;

	public function __construct() {
		$this->options        = 'clickwhale_tools_migration_options';
		$this->last_migration = 'clickwhale_tools_last_migration_options';

		$this->load_dependencies();
		$this->dispath_actions();

		// Actions
		add_action( 'admin_init', [ $this, 'add_migration_options' ] );
		add_action( 'admin_init', [ $this, 'add_migration_settings' ] );
		add_action( 'admin_init', [ $this, 'add_notice_migrate_options' ] );
		add_action( 'admin_init', [ $this, 'add_notice_deactive_options' ] );

		// add js
		add_action( 'admin_print_footer_scripts', [ $this, 'admin_scripts' ] );
	}

	private function load_dependencies() {
		// load classes if available plugin is active
		foreach ( $this->available_migrations() as $item ) {
			if ( $this->check_active( $item['path'] ) ) {
				require_once plugin_dir_path( dirname( __FILE__ ) ) . 'migration/' . $item['class'] . '.php';
			}
		}
	}

	/**
	 * Set target plugins data for migration
	 *
	 * @return array[]
	 * @since 1.0.0
	 */
	public static function available_migrations(): array {
		return array(
			'betterlinks'       => array(
				'slug'  => 'betterlinks',
				'name'  => 'Betterlinks',
				'path'  => 'betterlinks/betterlinks.php',
				'class' => 'BetterLinks_To_Clickwhale',
			),
			'thirstyaffiliates' => array(
				'slug'  => 'thirstyaffiliates',
				'name'  => 'ThirstyAffiliates',
				'path'  => 'thirstyaffiliates/thirstyaffiliates.php',
				'class' => 'ThirstyAffiliates_To_Clickwhale',
			),
			'prettylinks'       => array(
				'slug'  => 'prettylinks',
				'name'  => 'PrettyLinks',
				'path'  => 'pretty-link/pretty-link.php',
				'class' => 'PrettyLinks_To_Clickwhale',
			)
		);
	}

	/**
	 * Add default options if not exists
	 * @return void
	 * @since 1.6.0
	 */
	public function add_migration_options() {
		if ( false === get_option( $this->options ) ) {
			$defaults = [];

			foreach ( $this->available_migrations() as $item ) {
				$defaults[ $item['slug'] . '_categories' ] = true;
				$defaults[ $item['slug'] . '_links' ]      = true;
			}

			add_option( $this->options, $defaults );
		}
	}

	/**
	 * Add option to hide notice that some migration is available
	 * @return void
	 * @since 1.6.0
	 */
	public function add_notice_migrate_options() {
		if ( ! get_option( 'clickwhale_hide_notice_migrate' ) ) {

			foreach ( $this->available_migrations() as $item ) {
				$notice_migrate_options[ $item['slug'] ] = false;
			}

			add_option( 'clickwhale_hide_notice_migrate', $notice_migrate_options );
		}
	}

	/**
	 * Add option to hide notice that some plugin can be deactivated
	 * @return void
	 * @since 1.6.0
	 */
	public function add_notice_deactive_options() {
		if ( ! get_option( 'clickwhale_hide_notice_deactive' ) ) {

			foreach ( $this->available_migrations() as $item ) {
				$notice_deactive_options[ $item['slug'] ] = true;
			}

			add_option( 'clickwhale_hide_notice_deactive', $notice_deactive_options );
		}
	}

	/**
	 * Check if plugin is active
	 *
	 * @param string $path
	 *
	 * @return bool
	 * @since 1.0.0
	 */
	public function check_active( string $path ): bool {
		return in_array( $path, apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) );
	}

	/**
	 * Count links and categories for plugins
	 *
	 * @param string $plugin
	 *
	 * @return array
	 * @since 1.0.0
	 */
	public function get_plugin_data( string $plugin ): array {
		global $wpdb;

		$data = [];

		switch ( $plugin ) {
			case 'betterlinks':
				$data['links']      = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}betterlinks" );
				$data['categories'] = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}betterlinks_terms" );

				break;
			case 'thirstyaffiliates':
				$data['links']      = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}posts WHERE post_type='thirstylink' AND post_status='publish'" );
				$data['categories'] = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}term_taxonomy WHERE taxonomy='thirstylink-category'" );

				break;
			case 'prettylinks':
				$data['links']      = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}prli_links" );
				$data['categories'] = '';

				break;
		}

		return $data;
	}


	public function dispath_actions() {
		$available_migrations = $this->available_migrations();

		foreach ( $available_migrations as $item ) {
			if ( $this->check_active( $item['path'] ) ) {
				$migration = new Clickwhale_Migration_Notice( $item['slug'], $item['name'], $item['path'] );
				$migration->init();
			}
		}
	}

	/**
	 * Add settings for each plugin if it is active
	 * @since 1.0.0
	 */
	public function add_migration_settings() {
		foreach ( $this->available_migrations() as $item ) {
			if ( ! $this->check_active( $item['path'] ) ) {
				continue;
			}

			$options = get_option( $this->options );

			add_settings_section(
				'clickwhale_tools_migration_' . $item['slug'] . '_section',
				__( $item['name'], CLICKWHALE_NAME ),
				function () use ( $item ) {
					$this->migration_settings_section_callback( $item );
				},
				'clickwhale_tools_' . $item['slug'] . '_migration_options'
			);

			add_settings_field(
				"{$item['slug']}_categories",
				__( 'Categories', CLICKWHALE_NAME ),
				array( $this, 'render_controls' ),
				"clickwhale_tools_{$item['slug']}_migration_options",
				"clickwhale_tools_migration_{$item['slug']}_section",
				array(
					'control' => 'checkbox',
					'id'      => "{$item['slug']}_categories",
					'name'    => "$this->options[{$item['slug']}_categories]",
					'value'   => ! empty( $options[ $item['slug'] . '_categories' ] ) ? 1 : 0,
					'label'   => __( 'Migrate categories', CLICKWHALE_NAME ),
				)
			);

			add_settings_field(
				"{$item['slug']}_links",
				__( 'Links', CLICKWHALE_NAME ),
				array( $this, 'render_controls' ),
				"clickwhale_tools_{$item['slug']}_migration_options",
				"clickwhale_tools_migration_{$item['slug']}_section",
				array(
					'control' => 'checkbox',
					'id'      => "{$item['slug']}_links",
					'name'    => "$this->options[{$item['slug']}_links]",
					'value'   => ! empty( $options[ $item['slug'] . '_links' ] ) ? 1 : 0,
					'label'   => __( 'Migrate links', CLICKWHALE_NAME ),
				)
			);

			register_setting(
				'clickwhale_tools_' . $item['slug'] . '_migration_options',
				'clickwhale_tools_' . $item['slug'] . '_migration_options'
			);
		}
	}

	/**
	 * This function provides a simple description for the Options section.
	 *
	 */
	public function migration_settings_section_callback( $item ) {
		$data         = $this->get_plugin_data( $item['slug'] );
		$options      = get_option( $this->last_migration );
		$allowed_html = wp_kses_allowed_html( 'post' );

		$links           = $data['links'] ? intval( $data['links'] ) : 0;
		$links_text      = $data['links'] > 1 ? __( 'links', CLICKWHALE_NAME ) : __( 'link', CLICKWHALE_NAME );
		$categories      = $data['categories'] ? intval( $data['categories'] ) : 0;
		$categories_text = $data['categories'] > 1 ? __( 'categories', CLICKWHALE_NAME ) : __( 'category',
			CLICKWHALE_NAME );

		$result = $data['links'] || $data['categories']
			? sprintf( __( 'Found %1$s %2$s and %3$s %4$s.', CLICKWHALE_NAME ),
				$categories, $categories_text, $links, $links_text ) . '<br>'
			: '';
		$result .= ! empty( $options[ $item['slug'] . '_last_migration' ] )
			? sprintf( __( 'Last migration at %1$s', CLICKWHALE_NAME ),
				$options[ $item['slug'] . '_last_migration' ] ) . '<br>'
			: '';
		$result .= __( 'Set what you want to migrate from ' . $item['name'] . ' to CLickWhale', CLICKWHALE_NAME );
		?>
        <p><?php echo wp_kses( $result, $allowed_html ); ?></p>
		<?php
	}

	/**
	 * @param $item
	 *
	 * @return void
	 */


	public static function render_controls( $args ) {
		echo Helper::render_control( $args );
	}

	public function admin_scripts() {
        if ( empty( $_GET['page'] ) ) {
            return;
        }

        if ( $_GET['page'] !== CLICKWHALE_SLUG . '-tools' ) {
            return;
        }

        $nonce       = wp_create_nonce( 'migration_to_clickwhale' );
        $nonce_reset = wp_create_nonce( 'migration_reset' );
        $linksURL    = esc_url( admin_url( 'admin.php?page=' . CLICKWHALE_SLUG ) )
        ?>
        <script type='text/javascript'>

            jQuery(document).ready(function() {

                jQuery('.clickwhale-migration-section [type="checkbox"]').on('change', function() {
                    let migrationContainer = jQuery(this).closest('.clickwhale-migration-section'),
                        migrationButton = jQuery(migrationContainer).find('.button_start_migrate'),
                        checkbox = jQuery(this),
                        name = jQuery(checkbox).attr('name'),
                        matches = name.match(/\[(.*?)\]/),
                        value = jQuery(checkbox).prop('checked') ? 1 : 0;

                    jQuery(migrationButton).prop('disabled', true);

                    jQuery.post(ajaxurl, {
                        'security': '<?php echo $nonce ?>',
                        'action': 'clickwhale/admin/save_migration_option',
                        'name': matches[1],
                        'value': value
                    }, function(response) {
                        jQuery(migrationButton).prop('disabled', false);
                    })
                })

                jQuery('.button_start_migrate').on('click', function(e) {
                    e.preventDefault();

                    let migrationContainer = jQuery(this).closest('.clickwhale-migration-section'),
                        migrationButton = jQuery(this),
                        migrationSpinner = jQuery(migrationContainer).find('.spinner'),
                        migrationResult = jQuery(migrationContainer).find('.results');

                    jQuery(migrationButton).prop('disabled', true);
                    jQuery(migrationSpinner).addClass("is-active");
                    jQuery(migrationResult).removeClass("is-active").html('');

                    jQuery.post(ajaxurl, {
                        'security': '<?php echo esc_attr( $nonce ) ?>',
                        'action': 'clickwhale/admin/migration_to_clickwhale',
                        'migrant': migrationButton.data('migration')
                    }, function(response) {
                        if (response.success) {
                            let result = response.data;

                            if ('string' === typeof result.data) {
                                jQuery(migrationResult).append('<p>' + result.data + '</p>');
                            } else if ('object' === typeof result.data) {

                                for (let type in result.data) {
                                    let categories = result.data[type].categories,
                                        links = result.data[type].links;

                                    if (categories !== null) {
                                        for (let category in categories) {
                                            jQuery(migrationResult).append('<p>' + categories[category] + '</p>');
                                        }
                                    }

                                    if (links !== null) {
                                        for (let link in links) {
                                            jQuery(migrationResult).append('<p>' + links[link] + '</p>');
                                        }
                                    }
                                }
                            }

                            jQuery(migrationResult).addClass("is-active");
                            jQuery(migrationButton).prop('disabled', false);
                            jQuery(migrationSpinner).removeClass("is-active");

                            if ('object' === typeof result.data) {
                                jQuery(migrationResult).append('<br>' +
                                    '<a href="<?php echo $linksURL ?>" class="button-primary"> ' +
                                    '<?php _e( 'Get started with ClickWhale now', CLICKWHALE_NAME ) ?>' +
                                    '</a>');
                            }
                        }
                    });
                })

                jQuery('.button_reset_migrate').on('click', function(e) {
                    e.preventDefault();

                    let resetContainer = jQuery('#clickwhale-tools-migration-reset'),
                        resetButton = jQuery(this),
                        resetSpinner = jQuery(resetContainer).find('.spinner'),
                        resetResult = jQuery(resetContainer).find('.results');

                    jQuery(resetButton).prop('disabled', true);
                    jQuery(resetSpinner).addClass("is-active");

                    jQuery.post(ajaxurl, {
                        'security': '<?php echo $nonce_reset ?>',
                        'action': 'clickwhale/admin/migration_reset'
                    }, function(response) {
                        if (response.success) {
                            jQuery(resetButton).prop('disabled', false);
                            jQuery(resetSpinner).removeClass("is-active");
                            jQuery(resetResult).html(response.data);

                            location.reload(true);
                        }
                    });
                })
            });
        </script>
        <?php
	}
}