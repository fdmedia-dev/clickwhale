<?php
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php _e( 'ClickWhale Tools', 'clickwhale' ); ?></h1>
    <?php settings_errors(); ?>

    <?php $active_tab = isset( $_GET[ 'tab' ] ) ? $_GET[ 'tab' ] :  'migration_options'; ?>

    <h2 class="nav-tab-wrapper">
        <a href="?page=clickwhale-settings&tab=general_options" class="nav-tab <?php echo $active_tab == 'migration_options' ? 'nav-tab-active' : ''; ?>"><?php _e( 'Migration Options', 'clickwhale' ); ?></a>
    </h2>

    <form method="post" action="options.php">
        <?php

        //if( $active_tab == 'tracking_options' ) {
            //settings_fields( 'clickwhale_tracking_options' );
            //do_settings_sections( 'clickwhale_tracking_options' );
        //} else {
            //settings_fields( 'clickwhale_general_options' );
            //do_settings_sections( 'clickwhale_general_options' );
        //}

        submit_button();

        ?>
    </form>
</div>