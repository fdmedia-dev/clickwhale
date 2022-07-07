<?php
$migration = new Clickwhale_Migration();
do_action( 'clickwhale_admin_banner' );
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php _e( 'ClickWhale Tools', 'clickwhale' ); ?></h1>
	<?php settings_errors(); ?>

	<?php $active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'migration_options'; ?>

    <h2 class="nav-tab-wrapper">
        <a href="?page=clickwhale-tools&tab=migration_options"
           class="nav-tab <?php echo $active_tab == 'migration_options' ? 'nav-tab-active' : ''; ?>"><?php _e( 'Migration', 'clickwhale' ); ?></a>
    </h2>

    <form method="post" action="options.php">
		<?php

		if ( $active_tab == 'migration_options' ) {
			$show_settings = false;

			foreach ( $migration->available_migrations() as $item ) {
				if ( $migration->check_active( $item['path'] ) ) {
					$show_settings = true;
					?>

                    <div class="clickwhale-migration-section clickwhale-migration-section-<?php echo $item['slug'] ?>">
						<?php
						settings_fields( 'clickwhale_tools_' . $item['slug'] . '_migration_options' );
						do_settings_sections( 'clickwhale_tools_' . $item['slug'] . '_migration_options' );
						?>
                        <div id="clickwhale-tools-migration-submit">
                            <button type="button"
                                    class="button button_start_migrate"
                                    data-migration="<?php echo esc_attr( $item['slug'] ) ?>"><?php _e( 'Start migration', 'clickwhale' ) ?></button>
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
                            type="button"><?php _e( 'Set default options and clear cache', 'clickwhale' ) ?></button>
                    <span class="results"></span>
                    <span class="spinner"></span>
                </div>
			<?php } else { ?>
                <h3 class=""><?php _e( 'No data to migrate! ', 'clickwhale' ); ?></h3>
                <p><?php _e( 'You do not have active plugins from which we can transfer data.', 'clickwhale' ); ?></p>
			<?php }
		}
		?>
    </form>
    <div id="clickwhale_migration_results"></div>
</div>