<?php

$migration = clickwhale()->tools->migration;

do_action( 'clickwhale_admin_banner' );
?>
<div class="wrap">
    <h1 class="wp-heading-inline"><?php esc_html_e( 'Tools', 'clickwhale' ); ?></h1>
    <?php settings_errors(); ?>

    <?php do_action( 'clickwhale_admin_sidebar_begin' ); ?>

    <?php $active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'migration_options'; ?>

    <h2 class="nav-tab-wrapper">
        <a href="?page=<?php echo CLICKWHALE_SLUG; ?>-tools&tab=migration_options"
           class="nav-tab <?php echo $active_tab == 'migration_options' ? 'nav-tab-active' : ''; ?>"
        ><?php esc_html_e( 'Migration', 'clickwhale' ); ?></a>
        <a href="?page=<?php echo CLICKWHALE_SLUG; ?>-tools&tab=reset_options"
           class="nav-tab <?php echo $active_tab == 'reset_options' ? 'nav-tab-active' : ''; ?>"
        ><?php esc_html_e( 'Reset', 'clickwhale' ); ?></a>
        <a href="?page=<?php echo CLICKWHALE_SLUG; ?>-tools&tab=import"
           class="nav-tab <?php echo $active_tab == 'import' ? 'nav-tab-active' : ''; ?>"
        ><?php esc_html_e( 'Import', 'clickwhale' ); ?></a>
        <a href="?page=<?php echo CLICKWHALE_SLUG; ?>-tools&tab=export"
           class="nav-tab <?php echo $active_tab == 'export' ? 'nav-tab-active' : ''; ?>"
        ><?php esc_html_e( 'Export', 'clickwhale' ); ?></a>
    </h2>

    <?php
    if ( $active_tab == 'migration_options' ) {
        $show_settings = false;

        foreach ( $migration->available_migrations() as $item ) {
            if ( $migration->check_active( $item['path'] ) ) {
                $show_settings = true;
                $slug = esc_attr( $item['slug'] );
                ?>
                <div class="clickwhale-migration-section clickwhale-migration-section-<?php echo $slug; ?>">
                    <?php
                    settings_fields( 'clickwhale_tools_' . $slug . '_migration_options' );
                    do_settings_sections( 'clickwhale_tools_' . $slug . '_migration_options' );
                    ?>
                    <div id="clickwhale-tools-migration-submit">
                        <button type="button"
                                class="button button_start_migrate"
                                data-migration="<?php echo $slug; ?>"
                        ><?php esc_html_e( 'Start migration', 'clickwhale' ); ?></button>
                        <span class="spinner"></span>
                    </div>
                    <div class="results"></div>
                </div>
                <?php
            }
        }

        if ( $show_settings ) {
            ?>
            <div id="clickwhale-tools-migration-reset">
                <button class="button button-primary button_reset_migrate"
                        type="button"
                ><?php esc_html_e( 'Set default options and clear cache', 'clickwhale' ); ?></button>
                <span class="results"></span>
                <span class="spinner"></span>
            </div>
        <?php } else { ?>
            <h3 class=""><?php esc_html_e( 'No data to migrate! ', 'clickwhale' ); ?></h3>
            <p><?php esc_html_e( 'You do not have active plugins from which we can transfer data.', 'clickwhale' ); ?></p>
        <?php }
    }

    if ( $active_tab == 'reset_options' ) {
        ?>
        <div id="clickwhale-tools-reset">
            <?php
            settings_fields( 'clickwhale_tools_reset_settings' );
            do_settings_sections( 'clickwhale_tools_reset_settings' );
            ?>
            <p class="submit">
                <button id="button-reset-settings"
                        class="button button-primary"
                        type="button"
                ><?php esc_html_e( 'Restore default settings', 'clickwhale' ); ?></button>
                <span class="spinner"></span>
                <span class="results"></span>
            </p>
            <hr>
            <?php
            settings_fields( 'clickwhale_tools_reset_db_settings' );
            do_settings_sections( 'clickwhale_tools_reset_db_settings' );
            ?>
            <p class="submit">
                <button id="button-reset-db"
                        class="button button-primary"
                        type="button"
                ><?php esc_html_e( 'Delete all plugin data now', 'clickwhale' ); ?></button>
                <span class="spinner"></span>
                <span class="results"></span>
            </p>
            <hr>
            <?php
            settings_fields( 'clickwhale_tools_reset_stats_settings' );
            do_settings_sections( 'clickwhale_tools_reset_stats_settings' );
            ?>
            <p class="submit">
                <button id="button-reset-stats" class="button button-primary"
                        type="button"
                ><?php esc_html_e( 'Reset all tracking data now', 'clickwhale' ); ?></button>
                <span class="spinner"></span>
                <span class="results"></span>
            </p>
        </div>
        <?php
    }

    if ( $active_tab == 'import' ) {
        ?>
        <div id="clickwhale_tools_import">

            <div id="import_progress" class="import-progress">
                <div class="import-progress--bar"></div>
                <div class="import-progress--placeholder"></div>
                <div id="point-01" class="import-progress--point active">
                    01. <?php esc_html_e( 'Upload CSV file', 'clickwhale' ); ?>
                </div>
                <div id="point-02" class="import-progress--point">
                    02. <?php esc_html_e( 'Column mapping', 'clickwhale' ); ?>
                </div>
                <div id="point-03" class="import-progress--point">
                    03. <?php esc_html_e( 'Edit fields', 'clickwhale' ); ?>
                </div>
                <div id="point-04" class="import-progress--point">
                    04. <?php esc_html_e( 'Import', 'clickwhale' ); ?>
                </div>
            </div>

            <form method="post" id="upload_form" enctype="multipart/form-data">
                <?php
                settings_fields( 'clickwhale_tools_import_settings' );
                do_settings_sections( 'clickwhale_tools_import_settings' );
                submit_button( __( 'Upload import file', 'clickwhale' ) );
                ?>
            </form>

            <div id="mapping_table">
                <p class="submit">
                    <button id="mapping_button"
                            class="button button-primary"
                            type="button"
                    ><?php esc_html_e( 'Continue', 'clickwhale' ); ?></button>
                </p>
            </div>
            <div id="import_table">
                <p class="submit">
                    <button id="import_button"
                            class="button button-primary"
                            type="button"
                    ><?php esc_html_e( 'Run Importer', 'clickwhale' ); ?></button>
                    <span class="spinner" style="float: none;"></span>
                </p>
            </div>
            <div id="import_result">
                <a class="button button-primary"
                   style="display: none;"
                   rel="noopener"
                   target="_blank"
                   href="<?php echo esc_url( admin_url( 'admin.php?page=' . CLICKWHALE_SLUG ) ); ?>"
                ><?php esc_html_e( 'Go to links', 'clickwhale' ); ?></a>
            </div>
        </div>
        <?php
    }

    if ( $active_tab == 'export' ) {
        ?>
        <div id="clickwhale_tools_export">
            <form method="post" id="export_form" enctype="multipart/form-data">
                <?php
                settings_fields( 'clickwhale_tools_export_settings' );
                do_settings_sections( 'clickwhale_tools_export_settings' );
                submit_button( __( 'Generate CSV', 'clickwhale' ) );
                ?>
            </form>
        </div>
        <?php
    }

    do_action( 'clickwhale_admin_sidebar_end' ); ?>

</div><!-- ./wrap -->