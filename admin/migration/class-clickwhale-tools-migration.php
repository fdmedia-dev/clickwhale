<?php

class Clickwhale_Tools_Migration {

	public function __construct() {

		// Vars
		$this->options        = 'clickwhale_tools_migration_options';
		$this->last_migration = 'clickwhale_tools_last_migration_options';
		$this->migration      = new Clickwhale_Migration();

		// Actions
		add_action( 'admin_init', [ $this, 'add_tools_migration_options' ] );
		//add_action('admin_init', [$this, 'register_tools_migration_setting']);
		add_action( 'admin_init', [ $this, 'add_tools_migration_settings' ] );
		add_action( 'admin_init', [ $this, 'add_notice_migrate_options' ] );
		add_action( 'admin_init', [ $this, 'add_notice_deactive_options' ] );

		// add js
		add_action( 'admin_print_footer_scripts', [ $this, 'admin_scripts' ] );
	}

	/**
	 * Default tools page options for each plugin
	 */
	public function default_options() {

		$defaults = [];

		foreach ( $this->migration->available_migrations() as $item ) {
			$defaults[ $item['slug'] . '_categories' ] = true;
			$defaults[ $item['slug'] . '_links' ]      = true;
		}

		return $defaults;

	}

	/**
	 * Default tools last migration options
	 */
	public function default_last_migration_options() {

		$defaults = [];

		foreach ( $this->migration->available_migrations() as $item ) {
			$defaults[ $item['slug'] . '_last_migration' ] = '';
		}

		return $defaults;

	}

	/**
	 * Add default options if not exisit
	 */
	public function add_tools_migration_options() {
		if ( false == get_option( $this->options ) ) {
			$defaults = $this->default_options();
			add_option( $this->options, $defaults );
		}
	}

	/**
	 * Add default last migration options if not exisit
	 */
	public function add_tools_last_migration_options() {
		if ( ! get_option( $this->last_migration ) ) {
			$defaults = $this->default_last_migration_options();
			add_option( $this->last_migration, $defaults );
		}
	}

	public function add_notice_migrate_options() {
		if ( ! get_option( 'clickwhale_hide_notice_migrate' ) ) {
			add_option( 'clickwhale_hide_notice_migrate', [] );
		}
	}

	public function add_notice_deactive_options() {
		if ( ! get_option( 'clickwhale_hide_notice_deactive' ) ) {
			add_option( 'clickwhale_hide_notice_deactive', [] );
		}
	}

	/**
	 * Register tools migration settings
	 */
	public function register_tools_migration_setting() {

		register_setting(
			'clickwhale_tools_migration_options',
			'clickwhale_tools_migration_options'
		);

	}

	/**
	 * Add tools migration settings for each plugin if it is active
	 */
	public function add_tools_migration_settings() {
		foreach ( $this->migration->available_migrations() as $item ) {
			if ( $this->migration->check_active( $item['path'] ) ) {
				add_settings_section(
					'clickwhale_tools_migration_' . $item['slug'] . '_section',            // ID used to identify this section and with which to register options
					__( $item['name'], 'clickwhale' ),                                    // Title to be displayed on the administration page
					function () use ( $item ) {
						$this->tools_migration_callback( $item );
					},        // Callback used to render the description of the section
					'clickwhale_tools_' . $item['slug'] . '_migration_options'                                // Page on which to add this section of options
				);

				add_settings_field(
					$item['slug'] . '_categories',
					__( 'Categories', 'clickwhale' ),
					function () use ( $item ) {
						$this->tools_migration_categories_callback( $item );
					},
					'clickwhale_tools_' . $item['slug'] . '_migration_options',
					'clickwhale_tools_migration_' . $item['slug'] . '_section',
					array()
				);

				add_settings_field(
					$item['slug'] . '_links',
					__( 'Links', 'clickwhale' ),
					function () use ( $item ) {
						$this->tools_migration_links_callback( $item );
					},
					'clickwhale_tools_' . $item['slug'] . '_migration_options',
					'clickwhale_tools_migration_' . $item['slug'] . '_section',
					array()
				);

				register_setting(
					'clickwhale_tools_' . $item['slug'] . '_migration_options',
					'clickwhale_tools_' . $item['slug'] . '_migration_options'
				);
			}
		}
	}

	public function tools_migration_callback_count( $data ) {
		$links           = $data['links'] ? intval( $data['links'] ) : 0;
		$links_text      = $data['links'] > 1 ? __( 'links', 'clickwhale' ) : __( 'link', 'clickwhale' );
		$categories      = $data['categories'] ? intval( $data['categories'] ) : 0;
		$categories_text = $data['categories'] > 1 ? __( 'categories', 'clickwhale' ) : __( 'category', 'clickwhale' );

		$result = '';
		if ( $data['links'] || $data['categories'] ) {
			$result .= sprintf( __( 'Found %1$s %2$s and %3$s %4$s.', 'clickwhale' ), $categories, $categories_text, $links, $links_text );
			$result .= '<br>';
		}

		return $result;
	}

	public function tools_migration_callback_last_migration( $data ) {
		$options = get_option( $this->last_migration );
		$result  = '';

		if ( isset( $options[ $data ] ) && $options[ $data ] !== '' ) {
			$result .= sprintf( __( 'Last migration at %1$s', 'clickwhale' ), $options[ $data ] );
			$result .= '<br>';
		}

		return $result;
	}

	/**
	 * This function provides a simple description for the Options section.
	 *
	 */
	public function tools_migration_callback( $item ) {
		$allowed_html = wp_kses_allowed_html( 'post' );

		$result = $this->tools_migration_callback_count( $this->migration->get_plugin_data( $item['slug'] ) );
		$result .= $this->tools_migration_callback_last_migration( $item['slug'] . '_last_migration' );
		$result .= __( 'Set what you want to migrate from ' . $item['name'] . ' to CLickwhale', 'clickwhale' );
		?>
        <p><?php echo wp_kses( $result, $allowed_html ); ?></p>
		<?php
	}

	/**
	 * Fields
	 */
	public function tools_migration_categories_callback( $item ) {
		$options = get_option( $this->options );
		?>
        <input type="checkbox"
               id="<?php echo esc_attr( $item['slug'] . '_categories' ) ?>"
               name="<?php echo esc_attr( $this->options . '[' . $item['slug'] . '_categories]' ) ?>"
               value="1"
			<?php checked( 1, isset( $options[ '' . $item['slug'] . '_categories' ] ) ? $options[ '' . $item['slug'] . '_categories' ] : 0, true ) ?>/>
        <label for="<?php echo esc_attr( $item['slug'] . '_categories' ) ?>">&nbsp;<?php _e( 'Migrate categories', 'clickwhale' ) ?></label>
		<?php
	}

	public function tools_migration_links_callback( $item ) {
		$options = get_option( $this->options );
		?>
        <input type="checkbox"
               id="<?php echo esc_attr( $item['slug'] . '_links' ) ?>"
               name="<?php echo esc_attr( $this->options . '[' . $item['slug'] . '_links]' ) ?>"
               value="1"
			<?php checked( 1, isset( $options[ '' . $item['slug'] . '_links' ] ) ? $options[ '' . $item['slug'] . '_links' ] : 0, true ) ?>/>
        <label for="<?php echo esc_attr( $item['slug'] . '_links' ) ?>">&nbsp;<?php _e( 'Migrate links', 'clickwhale' ) ?></label>
		<?php
	}


	public function admin_scripts() {
		if ( isset( $_GET['page'] ) && $_GET['page'] === 'clickwhale-tools' ) {
			$nonce       = wp_create_nonce( 'migration_to_clickwhale' );
			$nonce_reset = wp_create_nonce( 'migration_reset' );
			?>
            <script type='text/javascript'>

                jQuery(document).ready(function () {

                    jQuery('.clickwhale-migration-section [type="checkbox"]').change(function () {
                        var migrationContainer = jQuery(this).closest('.clickwhale-migration-section'),
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
                        }, function (response) {
                            jQuery(migrationButton).prop('disabled', false);
                        })
                    })

                    jQuery('.button_start_migrate').click(function (e) {
                        e.preventDefault();

                        var migrationContainer = jQuery(this).closest('.clickwhale-migration-section'),
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
                        }, function (response) {
                            if (response.success) {
                                var result = response.data;

                                if ('string' === typeof result.data) {
                                    jQuery(migrationResult).append('<p>' + result.data + '</p>');
                                } else if ('object' === typeof result.data) {

                                    for (var type in result.data) {
                                        var categories = result.data[type].categories;
                                        var links = result.data[type].links;

                                        if (categories !== null) {
                                            for (var category in categories) {
                                                jQuery(migrationResult).append('<p>' + categories[category] + '</p>');
                                            }
                                        }

                                        if (links !== null) {
                                            for (var link in links) {
                                                jQuery(migrationResult).append('<p>' + links[link] + '</p>');
                                            }
                                        }
                                    }

                                }

                                jQuery(migrationResult).addClass("is-active");
                                jQuery(migrationButton).prop('disabled', false);
                                jQuery(migrationSpinner).removeClass("is-active");

                            }
                        });
                    })

                    jQuery('.button_reset_migrate').click(function (e) {
                        e.preventDefault();

                        var resetContainer = jQuery('#clickwhale-tools-migration-reset'),
                            resetButton = jQuery(this),
                            resetSpinner = jQuery(resetContainer).find('.spinner'),
                            resetResult = jQuery(resetContainer).find('.results');

                        jQuery(resetButton).prop('disabled', true);
                        jQuery(resetSpinner).addClass("is-active");

                        jQuery.post(ajaxurl, {
                            'security': '<?php echo $nonce_reset ?>',
                            'action': 'clickwhale/admin/migration_reset'
                        }, function (response) {
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

}