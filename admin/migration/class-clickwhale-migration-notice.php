<?php

/**
 * Show migration admin notice
 * 
 * @link       #
 * @since      1.0.0
 */
class ClickWhale_Migration_Notice {

    /**
	 * Plugin name
     * Used for actions and classes
	 *
	 * @since    1.0.0
	 * @access   public
	 * @var      string
	 */
    public $migrant;

    /**
	 * Plugin full name for messages.
	 *
	 * @since    1.0.0
	 * @access   public
	 * @var      string
	 */
    public $migrant_full;

    /**
	 * Plugin directory/name for deactivation.
	 *
	 * @since    1.0.0
	 * @access   public
	 * @var      string
	 */
    public $migrant_file;
    
    /**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $migrant
	 * @param      string    $migrant_full
	 * @param      string    $migrant_file
	 */
    public function __construct($migrant, $migrant_full, $migrant_file) {

        $this->migrant = $migrant;
        $this->migrant_full = $migrant_full;
        $this->migrant_file = $migrant_file;

    }

    /**
	 * Add admin notice
	 *
	 * @since    1.0.0
	 */
    public function init(){
        
        if (in_array( $this->migrant_file, apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) )) {

            $options_migrate = get_option('clickwhale_hide_' . $this->migrant . '_notice_migrate');
            $options_deactive = get_option('clickwhale_hide_' . $this->migrant . '_notice_deactive');
            
            if( !$options_migrate ) {
                add_action('admin_notices', [$this, 'migration_notice']);
                add_action('admin_print_footer_scripts', [$this, 'admin_scripts']);
            } elseif ($options_migrate && !$options_deactive) {
                add_action('admin_notices', [$this, 'deactive_notice']);
                add_action('admin_print_footer_scripts', [$this, 'admin_scripts']);
            }

        }

    }

    /**
	 * Notice before migration.
	 *
	 * @since    1.0.0
	 */
    public function migration_notice(){
        ?>
        <div class="notice notice-info clickwhale-notice clickwhale-notice-<?php echo $this->migrant ?>-migrate">
            <p>
                <span> <?php printf( __('You are already using %1$s on your website. To migrate your %1$s data to Clickwhale, click here.', 'clickwhale'), $this->migrant_full ); ?></span>
                <a href="<?php echo esc_url(admin_url('admin.php?page=clickwhale-tools&migration=' . $this->migrant)); ?>" class="button button-primary"><?php _e('Start Migration', 'clickwhale'); ?></a>
                <a href="#" class="button button-dismiss"><?php _e('Not now', 'clickwhale'); ?></a>
            </p>
        </div>
        <?php
    }

    /**
	 * Notice after migration for plugin deactivation.
	 *
	 * @since    1.0.0
	 */
    public function deactive_notice(){
        ?>
        <div class="notice notice-error clickwhale-notice clickwhale-notice-<?php echo $this->migrant ?>-deactive">
            <p>
            <span> <?php printf( __('All %1$s data have been successfully migrated to Clickwhale. You can now safely deactivate %1$s on your website.', 'clickwhale'), $this->migrant_full ); ?></span>
                <a href="#" class="button button-primary deactive"><?php printf( __('Deactivate %1$s', 'clickwhale'), $this->migrant_full ); ?></a>
                <a href="#" class="button button-dismiss"><?php _e('Leave it active', 'clickwhale'); ?></a>
            </p>
        </div>
        <?php
    }

    /**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
    public function admin_scripts(){
        $nonce = wp_create_nonce('clickwhale_' . $this->migrant . '_admin_nonce'); 
        ?>
        <script type='text/javascript'>
		jQuery( document ).ready(function() {
			jQuery('.clickwhale-notice-<?php echo $this->migrant ?>-deactive').on('click', '.deactive', function(e){
				e.preventDefault();
				jQuery.post(ajaxurl, {
					'action': 'clickwhale/admin/deactive_<?php echo $this->migrant ?>',
					'security': '<?php echo $nonce; ?>',
                    'plugin' : '<?php echo $this->migrant_file ?>',
				}, function(response) {
					if(response.success){
						location.reload(true); 
					}
				});
			})
			
			jQuery('.clickwhale-notice-<?php echo $this->migrant ?>-deactive').on('click', '.button-dismiss', function(e){
                e.preventDefault();
                jQuery(this).closest('.clickwhale-notice').remove();

				jQuery.post(ajaxurl, {
					'action': 'clickwhale/admin/migration_<?php echo $this->migrant ?>_notice_hide',
					'security': '<?php echo $nonce; ?>',
					'type': 'deactive'
				}, function(response) {});
			})

            jQuery('.clickwhale-notice-<?php echo $this->migrant ?>-migrate').on('click', '.button-dismiss', function(e){
                e.preventDefault();
                jQuery(this).closest('.clickwhale-notice').remove();
                
				jQuery.post(ajaxurl, {
					'action': 'clickwhale/admin/migration_<?php echo $this->migrant ?>_notice_hide',
					'security': '<?php echo $nonce; ?>',
					'type': 'migrate',
				}, function(response) {});
			})
		});
		</script>
        <?php
    }
}