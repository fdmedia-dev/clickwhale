<?php

class Clickwhale_Tools_Migration {

    public function __construct() {

		// Vars
		$this->options = 'clickwhale_tools_migration_options';
		$this->plugins = new ClickWhale_Migration();
        
		// Actions
        add_action('admin_init', [$this, 'initialize_tools_migration_options']);

		// for each plugin we should check is it active and then add options section
		if ($this->plugins->check_betterlinks_active()) {
			add_action('admin_init', [$this, 'initialize_tools_migration_betterlinks_options']);
		}
		if ($this->plugins->check_thirstyaffiliates_active()) {
			add_action('admin_init', [$this, 'initialize_tools_migration_thirstyaffiliates_options']);
		}
		if ($this->plugins->check_prettylinks_active()) {
			add_action('admin_init', [$this, 'initialize_tools_migration_prettylinks_options']);
		}

		// add js
		add_action('admin_print_footer_scripts', [$this, 'admin_scripts']);
    }

	public function default_options() {

		$defaults = array(
			'betterlinks_categories'    		=>	true,
			'betterlinks_links'    				=>	true,
			'thirstyaffiliates_categories'    	=>	true,
			'thirstyaffiliates_links'    		=>	true,
			'prettylinks_categories'   			=>	true,
			'prettylinks_links'    				=>	true,
		);

		return $defaults;

	}

    public function initialize_tools_migration_options() {

		if( false == get_option( $this->options ) ) {
			$defaults = $this->default_options();
			add_option( $this->options, $defaults );
		}

        register_setting(
			'clickwhale_tools_migration_options',
			'clickwhale_tools_migration_options'
		);

	}

	public function initialize_tools_migration_betterlinks_options(){

        add_settings_section(
			'clickwhale_tools_migration_betterlinks_section',			// ID used to identify this section and with which to register options
			__( 'Bettrelinks', 'clickwhale' ),		            		// Title to be displayed on the administration page
			array( $this, 'tools_migration_betterlinks_callback'),		// Callback used to render the description of the section
			'clickwhale_tools_migration_options'						// Page on which to add this section of options
		);

        add_settings_field(
			'betterlinks_categories',
			__( 'Categories', 'clickwhale' ),
			array( $this, 'tools_migration_betterlinks_categories_callback'),
			'clickwhale_tools_migration_options',
			'clickwhale_tools_migration_betterlinks_section',
			array(
				__( 'Migrate categories (add normal label)', 'clickwhale' ),
			)
		);

		add_settings_field(
			'betterlinks_links',
			__( 'Links', 'clickwhale' ),
			array( $this, 'tools_migration_betterlinks_links_callback'),
			'clickwhale_tools_migration_options',
			'clickwhale_tools_migration_betterlinks_section',
			array(
				__( 'Migrate links (add normal label)', 'clickwhale' ),
			)
		);

    }

	public function initialize_tools_migration_thirstyaffiliates_options(){

        add_settings_section(
			'clickwhale_tools_migration_thirstyaffiliates_section',			// ID used to identify this section and with which to register options
			__( 'ThirstyAffiliates', 'clickwhale' ),		            		// Title to be displayed on the administration page
			array( $this, 'tools_migration_thirstyaffiliates_callback'),		// Callback used to render the description of the section
			'clickwhale_tools_migration_options'						// Page on which to add this section of options
		);

        add_settings_field(
			'thirstyaffiliates_categories',
			__( 'Categories', 'clickwhale' ),
			array( $this, 'tools_migration_thirstyaffiliates_categories_callback'),
			'clickwhale_tools_migration_options',
			'clickwhale_tools_migration_thirstyaffiliates_section',
			array(
				__( 'Migrate categories (add normal label)', 'clickwhale' ),
			)
		);

		add_settings_field(
			'thirstyaffiliates_links',
			__( 'Links', 'clickwhale' ),
			array( $this, 'tools_migration_thirstyaffiliates_links_callback'),
			'clickwhale_tools_migration_options',
			'clickwhale_tools_migration_thirstyaffiliates_section',
			array(
				__( 'Migrate links (add normal label)', 'clickwhale' ),
			)
		);

    }

	public function initialize_tools_migration_prettylinks_options(){

        add_settings_section(
			'clickwhale_tools_migration_prettylinks_section',			// ID used to identify this section and with which to register options
			__( 'PrettyLinks', 'clickwhale' ),		            		// Title to be displayed on the administration page
			array( $this, 'tools_migration_prettylinks_callback'),		// Callback used to render the description of the section
			'clickwhale_tools_migration_options'						// Page on which to add this section of options
		);

        add_settings_field(
			'prettylinks_categories',
			__( 'Categories', 'clickwhale' ),
			array( $this, 'tools_migration_prettylinks_categories_callback'),
			'clickwhale_tools_migration_options',
			'clickwhale_tools_migration_prettylinks_section',
			array(
				__( 'Migrate categories (add normal label)', 'clickwhale' ),
			)
		);

		add_settings_field(
			'prettylinks_links',
			__( 'Links', 'clickwhale' ),
			array( $this, 'tools_migration_prettylinks_links_callback'),
			'clickwhale_tools_migration_options',
			'clickwhale_tools_migration_prettylinks_section',
			array(
				__( 'Migrate links (add normal label)', 'clickwhale' ),
			)
		);

    }

	/**
	 * This function provides a simple description for the Options section.
	 *
	 */
	public function tools_migration_betterlinks_callback() {
		echo '<p>' . __( 'Set what you want to migrate from BetterLinks to CLickwhale', 'clickwhale' ) . '</p>';
	}

	public function tools_migration_thirstyaffiliates_callback() {
		echo '<p>' . __( 'Set what you want to migrate from ThirstyAffiliates to CLickwhale', 'clickwhale' ) . '</p>';
	}

	public function tools_migration_prettylinks_callback() {
		echo '<p>' . __( 'Set what you want to migrate from PrettyLinks to CLickwhale', 'clickwhale' ) . '</p>';
	}

	/**
	 * Fields
	 */
    public function tools_migration_betterlinks_categories_callback($args) {
		$options = get_option($this->options);

		$html = '<input type="checkbox" id="betterlinks_categories" name="' . $this->options . '[betterlinks_categories]" value="1" ' . checked( 1, isset( $options['betterlinks_categories'] ) ? $options['betterlinks_categories'] : 0, false ) . '/>';
		$html .= '<label for="betterlinks_categories">&nbsp;'  . $args[0] . '</label>';

		echo $html;
	}

	public function tools_migration_betterlinks_links_callback($args) {
		$options = get_option($this->options);

		$html = '<input type="checkbox" id="betterlinks_links" name="' . $this->options . '[betterlinks_links]" value="1" ' . checked( 1, isset( $options['betterlinks_links'] ) ? $options['betterlinks_links'] : 0, false ) . '/>';
		$html .= '<label for="betterlinks_links">&nbsp;'  . $args[0] . '</label>';

		echo $html;
	}

	public function tools_migration_thirstyaffiliates_categories_callback($args) {
		$options = get_option($this->options);

		$html = '<input type="checkbox" id="thirstyaffiliates_categories" name="' . $this->options . '[thirstyaffiliates_categories]" value="1" ' . checked( 1, isset( $options['thirstyaffiliates_categories'] ) ? $options['thirstyaffiliates_categories'] : 0, false ) . '/>';
		$html .= '<label for="thirstyaffiliates_categories">&nbsp;'  . $args[0] . '</label>';

		echo $html;
	}

	public function tools_migration_thirstyaffiliates_links_callback($args) {
		$options = get_option($this->options);

		$html = '<input type="checkbox" id="thirstyaffiliates_links" name="' . $this->options . '[thirstyaffiliates_links]" value="1" ' . checked( 1, isset( $options['thirstyaffiliates_links'] ) ? $options['thirstyaffiliates_links'] : 0, false ) . '/>';
		$html .= '<label for="thirstyaffiliates_links">&nbsp;'  . $args[0] . '</label>';

		echo $html;
	}

	public function tools_migration_prettylinks_categories_callback($args) {
		$options = get_option($this->options);

		$html = '<input type="checkbox" id="prettylinks_categories" name="' . $this->options . '[prettylinks_categories]" value="1" ' . checked( 1, isset( $options['prettylinks_categories'] ) ? $options['prettylinks_categories'] : 0, false ) . '/>';
		$html .= '<label for="prettylinks_categories">&nbsp;'  . $args[0] . '</label>';

		echo $html;
	}

	public function tools_migration_prettylinks_links_callback($args) {
		$options = get_option($this->options);

		$html = '<input type="checkbox" id="prettylinks_links" name="' . $this->options . '[prettylinks_links]" value="1" ' . checked( 1, isset( $options['prettylinks_links'] ) ? $options['prettylinks_links'] : 0, false ) . '/>';
		$html .= '<label for="prettylinks_links">&nbsp;'  . $args[0] . '</label>';

		echo $html;
	}



	public function admin_scripts() {
		$nonce = wp_create_nonce('clickwhale_migration_admin_nonce');
		?>
		<script type='text/javascript'>	
		jQuery( document ).ready(function() {
			jQuery('.submit').append('<button id="button_migrate" type="button" class="button button_start_migrate">Start Migration</button>')
		
			jQuery('.submit').on('click', '#button_migrate', function(e){
                e.preventDefault();

				jQuery.post(ajaxurl, {
					'action': 'clickwhale/admin/migration_to_clickwhale',
				}, function(response) {
					if(response.success){
						var data = response.data;
						
						for (var type in data) {
							var categories = data[type].categories;
							var links = data[type].links;
							
							//jQuery('.results').append('<h3>Categories</h3>');
							for (var category in categories) {
								jQuery('.results').append('<p>'+categories[category]+'</p>');
							}

							//jQuery('.results').append('<h3>Links</h3>');
							for (var link in links) {
								jQuery('.results').append('<p>'+links[link]+'</p>');
							}

						}

						//jQuery.post(ajaxurl, {
						//	'action': 'clickwhale/admin/deactive_<?php //echo $this->migrant ?>',
						//	'security': '<?php //echo $nonce; ?>',
						//	'plugin' : 'betterlinks/betterlinks.php<?php //echo $this->migrant_file ?>',
						//}, function(response) {
						//	if(response.success){
						//		location.reload(true); 
						//	}
						//});
					}
				});
			})
		});
		</script>
		<?php
	}

}