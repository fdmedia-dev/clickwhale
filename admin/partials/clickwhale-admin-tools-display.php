<?php
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php _e( 'ClickWhale Tools', 'clickwhale' ); ?></h1>
    <?php settings_errors(); ?>

    <?php $active_tab = isset( $_GET[ 'tab' ] ) ? $_GET[ 'tab' ] :  'migration_options'; ?>

    <h2 class="nav-tab-wrapper">
        <a href="?page=clickwhale-tools&tab=migration_options" class="nav-tab <?php echo $active_tab == 'migration_options' ? 'nav-tab-active' : ''; ?>"><?php _e( 'Migration', 'clickwhale' ); ?></a>
    </h2>

    <form method="post" action="options.php">
        <?php



        
        if( $active_tab == 'migration_options' ) {
            settings_fields( 'clickwhale_tools_migration_options' );
            do_settings_sections( 'clickwhale_tools_migration_options' );
            do_settings_sections( 'clickwhale_tools_migration_thirstyaffiliates_section' );
            do_settings_sections( 'clickwhale_tools_migration_prettylinks_section' );
        }
        
        submit_button();

        ?>
    </form>
    <div id="clickwhale_migration_results"></div>
</div>