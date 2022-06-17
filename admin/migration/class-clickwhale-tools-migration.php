<?php

class Clickwhale_Tools_Migration {

    public function __construct() {

		// Vars
		$this->options 		  = 'clickwhale_tools_migration_options';
		$this->last_migration = 'clickwhale_tools_last_migration_options';
		$this->migration = new ClickWhale_Migration();
        
		// Actions
        add_action('admin_init', [$this, 'add_tools_migration_options']);
        add_action('admin_init', [$this, 'register_tools_migration_setting']);
		add_action('admin_init', [$this, 'add_tools_migration_settings']);
		add_action('admin_init', [$this, 'add_notice_migrate_options']);
		add_action('admin_init', [$this, 'add_notice_deactive_options']);

		// add js
		add_action('admin_print_footer_scripts', [$this, 'admin_scripts']);
    }

	/**
	 * Default tools page options for each plugin
	 */
	public function default_options() {

		$defaults = [];

		foreach($this->migration->available_migrations() as $item){
			$defaults[$item['slug'] . '_categories'] 	 = $item['data']['categories'] ? true : false;
			$defaults[$item['slug'] . '_links'] 	 	 = $item['data']['links'] ? true : false;
		}

		return $defaults;

	}

	/**
	 * Default tools last migration options
	 */
	public function default_last_migration_options() {

		$defaults = [];

		foreach($this->migration->available_migrations() as $item){
			$defaults[$item['slug'] . '_last_migration'] = '';
		}

		return $defaults;

	}

	/**
	 * Add default options if not exisit
	 */
    public function add_tools_migration_options() {
		if( false == get_option( $this->options ) ) {
			$defaults = $this->default_options();
			add_option( $this->options, $defaults );
		}
	}

	/**
	 * Add default last migration options if not exisit
	 */
	public function add_tools_last_migration_options() {
		if( false == get_option( $this->last_migration ) ) {
			$defaults = $this->default_last_migration_options();
			add_option( $this->last_migration, $defaults );
		}
	}

	public function add_notice_migrate_options() {
		if( false == get_option( 'clickwhale_hide_notice_migrate' ) ) {
			add_option( 'clickwhale_hide_notice_migrate', [] );
		}
	}

	public function add_notice_deactive_options() {
		if( false == get_option( 'clickwhale_hide_notice_deactive' ) ) {
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
	public function add_tools_migration_settings(){
		foreach($this->migration->available_migrations() as $item){
			if ($this->migration->check_active($item['path'])) {
				add_settings_section(
					'clickwhale_tools_migration_' . $item['slug'] . '_section',			// ID used to identify this section and with which to register options
					__( $item['name'], 'clickwhale' ),		            				// Title to be displayed on the administration page
					function() use($item){$this->tools_migration_callback($item);},		// Callback used to render the description of the section
					'clickwhale_tools_migration_options'								// Page on which to add this section of options
				);

				add_settings_field(
					$item['slug'] . '_categories',
					__( 'Categories', 'clickwhale' ),
					function() use($item){$this->tools_migration_categories_callback($item);},
					'clickwhale_tools_migration_options',
					'clickwhale_tools_migration_' . $item['slug'] . '_section',
					array()
				);

				add_settings_field(
					$item['slug'] . '_links',
					__( 'Links', 'clickwhale' ),
					function() use($item){$this->tools_migration_links_callback($item);},
					'clickwhale_tools_migration_options',
					'clickwhale_tools_migration_' . $item['slug'] . '_section',
					array()
				);
			}
		}
	}

	public function tools_migration_callback_count($data){
		$links 			 = $data['links'] ? intval($data['links']) : 0;
		$links_text 	 = $data['links'] > 1 ? __('links', 'clickwhale') : __('link', 'clickwhale');
		$categories 	 = $data['categories'] ? intval($data['categories']) : 0;
		$categories_text = $data['categories'] > 1 ? __('categories', 'clickwhale') : __('category', 'clickwhale');

		$result = '';
		if($data['links'] || $data['categories']){
			$result .= sprintf(__( 'Found %1$s %2$s and %3$s %4$s.', 'clickwhale' ), $categories, $categories_text, $links, $links_text );
			$result .= '<br>';
		}

		return $result;
	}

	public function tools_migration_callback_last_migration($data){
		$options = get_option( $this->last_migration );
		$result  = '';

		if(isset($options[$data])){
			$result .= sprintf(__('Last migration at %1$s', 'clickwhale'), $options[$data]);
			$result .= '<br>';
		}
		return $result;
	}

	/**
	 * This function provides a simple description for the Options section.
	 *
	 */
	public function tools_migration_callback($item) {
		$result = $this->tools_migration_callback_count($item['data']);
		$result .= $this->tools_migration_callback_last_migration($item['slug'] . '_last_migration');
		$result .= __( 'Set what you want to migrate from ' . $item['name'] . ' to CLickwhale', 'clickwhale' );
		
		echo '<p>' . $result . '</p>';
	}

	/**
	 * Fields
	 */
    public function tools_migration_categories_callback($item) {
		$options = get_option($this->options);

		$html = '<input type="checkbox" id="' . $item['slug'] . '_categories" name="' . $this->options . '[' . $item['slug'] . '_categories]" value="1" ' . checked( 1, isset( $options['' . $item['slug'] . '_categories'] ) ? $options['' . $item['slug'] . '_categories'] : 0, false ) . '/>';
		$html .= '<label for="' . $item['slug'] . '_categories">&nbsp;'  . __( 'Migrate categories (add normal label)', 'clickwhale' ) . '</label>';

		echo $html;
	}

	public function tools_migration_links_callback($item) {
		$options = get_option($this->options);

		$html = '<input type="checkbox" id="' . $item['slug'] . '_links" name="' . $this->options . '[' . $item['slug'] . '_links]" value="1" ' . checked( 1, isset( $options['' . $item['slug'] . '_links'] ) ? $options['' . $item['slug'] . '_links'] : 0, false ) . '/>';
		$html .= '<label for="' . $item['slug'] . '_links">&nbsp;'  . __( 'Migrate links (add normal label)', 'clickwhale' ) . '</label>';

		echo $html;
	}


	public function admin_scripts() {
		$nonce = wp_create_nonce('clickwhale_migration_admin_nonce');
		?>
		<script type='text/javascript'>	
		
		jQuery( document ).ready(function() {
			var migrationButton = jQuery('<button>', {
				id: 'button_migrate',
				class: 'button button_start_migrate',
				type: 'button',
				text: 'Start Migration'
			}).appendTo('#clickwhale-tools-migration-submit .submit');

			var migrationSpinner = jQuery('<span>', {
				id: 'spinner_migrate',
				class: 'spinner',
			}).appendTo('#clickwhale-tools-migration-submit .submit');
			
			jQuery(migrationButton).click(function(e){
                e.preventDefault();
				
				jQuery(migrationButton).prop('disabled', true);
				jQuery(migrationSpinner).addClass("is-active");

				jQuery.post(ajaxurl, {
					'action': 'clickwhale/admin/migration_to_clickwhale',
				}, function(response) {
					if(response.success){
						var data = response.data;

						//console.log(data);

						for (var item in data) {

							var migrationResult = jQuery('<div>', {
								id: 'clickwhale-result-'+item,
								class: 'clickwhale_migration_results',
							}).appendTo('#clickwhale_migration_results');

							jQuery(migrationResult).append('<h3>'+data[item].title+'</h3>');
							
							if('string' === typeof data[item].data){
								jQuery(migrationResult).append('<p>'+data[item].data+'</p>');
							} else if('object' === typeof data[item].data) {
								
								for (var type in data[item].data) {
									var categories = data[item].data[type].categories;
									var links = data[item].data[type].links;

									if(categories !== null){
										for (var category in categories) {
											jQuery(migrationResult).append('<p>'+categories[category]+'</p>');
										}
									}

									if(links !== null){
										for (var link in links) {
											jQuery(migrationResult).append('<p>'+links[link]+'</p>');
										}
									}
								}

							}
						}

						jQuery(migrationButton).prop('disabled', false);
						jQuery(migrationSpinner).removeClass("is-active");
						
						

					}
				});
			})
		});
		</script>
		<?php
	}

}