<?php

class BetterLinks_To_ClickWhale extends ClickWhale_Migration_Notice{

    public static $pagenow;

    // Change this for new migration
    public static $migrant        = 'betterlinks';
    public static $migrant_full   = 'BetterLinks';
    public static $migrant_file   = 'betterlinks/betterlinks.php';

    public static function init(){

        $self = new self();
        
        if (in_array( self::$migrant_file, apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) )) {
            global $pagenow;
            $self::$pagenow = $pagenow;

            $options_migrate = get_option('clickwhale_hide_' . self::$migrant . '_notice_migrate');
            
            if( isset( $options_migrate ) && false == $options_migrate ) {
                add_action('admin_notices', [$self, 'migration_notice']);
                add_action('admin_print_footer_scripts', [$self, 'admin_scripts']);
            }
        }

    }

    public function migration_notice(){
        ?>
        <div class="notice notice-info clickwhale-notice clickwhale-notice-<?php echo self::$migrant ?>-migrate">
            <p>
                <span> <?php printf( __('You are already using %1$s on your website. To migrate your %1$s data to Clickwhale, click here.', 'clickwhale'), self::$migrant_full ); ?></span>
                <a href="<?php echo esc_url(admin_url('admin.php?page=clickwhale-tools&migration=' . self::$migrant)); ?>" class="button button-primary"><?php _e('Start Migration', 'clickwhale'); ?></a>
                <a href="#" class="button button-dismiss"><?php _e('Not now', 'clickwhale'); ?></a>
            </p>
        </div>
        <?php
    }
    public function deactive_notice(){
        ?>
        <div class="notice notice-error clickwhale-notice clickwhale-notice-<?php echo self::$migrant ?>-deactive">
            <p>
            <span> <?php printf( __('All %1$s data have been successfully migrated to Clickwhale. You can now safely deactivate %1$s on your website.', 'clickwhale'), self::$migrant_full ); ?></span>
                <a href="#" class="button button-primary deactive"><?php printf( __('Deactivate %1$s', 'clickwhale'), self::$migrant_full ); ?></a>
                <a href="#" class="button button-dismiss"><?php _e('Leave it active', 'clickwhale'); ?></a>
            </p>
        </div>
        <?php
    }

    public function admin_scripts(){
        $nonce = wp_create_nonce('clickwhale_' . self::$migrant . '_admin_nonce'); 
        ?>
        <script type='text/javascript'>
		jQuery( document ).ready(function() {
			jQuery('.clickwhale-notice-<?php echo self::$migrant ?>-deactive').on('click', '.deactive', function(e){
				e.preventDefault();
				jQuery.post(ajaxurl, {
					'action': 'clickwhale/admin/deactive_<?php echo self::$migrant ?>',
					'security': '<?php echo $nonce; ?>',
                    'plugin' : '<?php echo self::$migrant_file ?>',
				}, function(response) {
					if(response.success){
						location.reload(true); 
					}
				});
			})
			
			jQuery('.clickwhale-notice-<?php echo self::$migrant ?>-deactive').on('click', '.notice-dismiss', function(){
				jQuery.post(ajaxurl, {
					'action': 'clickwhale/admin/migration_<?php echo self::$migrant ?>_notice_hide',
					'security': '<?php echo $nonce; ?>',
					'type': 'deactive'
				}, function(response) {});
			})

            jQuery('.clickwhale-notice-<?php echo self::$migrant ?>-migrate').on('click', '.button-dismiss', function(e){
                e.preventDefault();
                jQuery(this).closest('.clickwhale-notice').remove();
                
				jQuery.post(ajaxurl, {
					'action': 'clickwhale/admin/migration_<?php echo self::$migrant ?>_notice_hide',
					'security': '<?php echo $nonce; ?>',
					'type': 'migrate',
				}, function(response) {});
			})
		});
		</script>
        <?php
    }
}