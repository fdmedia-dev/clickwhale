<?php
$migration = new ClickWhale_Migration();
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
            $show_settings = false;

            foreach($migration->available_migrations() as $item){
                if ($migration->check_active($item['path'])) {
                    $show_settings = true;

                    settings_fields( 'clickwhale_tools_migration_options' );
                    do_settings_sections( 'clickwhale_tools_migration_options' );
                    echo '<div id="clickwhale-tools-migration-submit">';
                    submit_button();
                    echo '<span class="spinner"></span>';
                    echo '</div>';

                    break;
                }
            }

            if(!$show_settings){ 
                ?>
                <h3 class=""><?php _e('No data to migrate! ', 'clickwhale'); ?></h3>
                <p><?php _e('You do not have active plugins from which we can transfer data.', 'clickwhale'); ?></p>
            <?php }
        }
        ?>
    </form>
    <div id="clickwhale_migration_results"></div>
</div>