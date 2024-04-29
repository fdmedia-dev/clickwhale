<?php

use clickwhale\includes\admin\Clickwhale_Settings;

$tabs = Clickwhale_Settings::render_tabs();
do_action( 'clickwhale_admin_banner' );
?>
<div class="wrap clickwhale-settings-wrap">
    <h1 class="wp-heading-inline"><?php _e( 'Settings', CLICKWHALE_NAME ); ?></h1>
	<?php settings_errors(); ?>

    <?php do_action( 'clickwhale_admin_sidebar_begin' ); ?>

	<?php
	if ( $tabs ) {
		$active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'general_options';
		?>
        <h2 class="nav-tab-wrapper">
			<?php foreach ( $tabs as $tab ) {
				$url    = '?page=' . CLICKWHALE_SLUG . '-settings&tab=' . $tab['url'];
				$active = $active_tab === $tab['url'] ? 'nav-tab-active' : '';
				?>
                <a href="<?php echo $url ?>" class="nav-tab <?php echo $active ?>">
					<?php echo $tab['name'] ?>
                </a>
			<?php } ?>
        </h2>

        <form method="post" action="options.php">
			<?php

			settings_fields( 'clickwhale_' . $active_tab );
			do_settings_sections( 'clickwhale_' . $active_tab );

			submit_button( __( 'Save changes', 'clikwhale' ) );

			?>
        </form>

	<?php } ?>

    <?php do_action( 'clickwhale_admin_sidebar_end' ); ?>
</div>