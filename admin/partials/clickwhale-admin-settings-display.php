<?php
do_action( 'clickwhale_admin_banner' );
?>

<div class="wrap clickwhale-settings-wrap">
    <h1 class="wp-heading-inline"><?php _e( 'Settings', 'clickwhale' ); ?></h1>
	<?php settings_errors(); ?>

	<?php $active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'general_options'; ?>

    <h2 class="nav-tab-wrapper">
        <a href="?page=clickwhale-settings&tab=general_options"
           class="nav-tab <?php echo $active_tab == 'general_options' ? 'nav-tab-active' : ''; ?>"><?php _e( 'General Options', 'clickwhale' ); ?></a>
        <a href="?page=clickwhale-settings&tab=tracking_options"
           class="nav-tab <?php echo $active_tab == 'tracking_options' ? 'nav-tab-active' : ''; ?>"><?php _e( 'Tracking Options', 'clickwhale' ); ?></a>
        <a href="?page=clickwhale-settings&tab=other_options"
           class="nav-tab <?php echo $active_tab == 'other_options' ? 'nav-tab-active' : ''; ?>"><?php _e( 'Other Options', 'clickwhale' ); ?></a>
    </h2>

    <form method="post" action="options.php">
		<?php

		switch ( $active_tab ) {
			case 'tracking_options':
				settings_fields( 'clickwhale_tracking_options' );
				do_settings_sections( 'clickwhale_tracking_options' );
				break;
			case 'other_options':
				settings_fields( 'clickwhale_other_options' );
				do_settings_sections( 'clickwhale_other_options' );
				break;
			default:
				settings_fields( 'clickwhale_general_options' );
				do_settings_sections( 'clickwhale_general_options' );
		}

		submit_button();

		?>
    </form>
</div>