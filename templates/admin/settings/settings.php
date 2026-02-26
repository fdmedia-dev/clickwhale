<?php

use clickwhale\includes\admin\Clickwhale_Settings;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$tabs = Clickwhale_Settings::render_tabs();
do_action( 'clickwhale_admin_banner' );
?>
<div class="wrap clickwhale-settings-wrap">
    <h1 class="wp-heading-inline"><?php esc_html_e( 'Settings', 'clickwhale' ); ?></h1>
    <?php settings_errors(); ?>

    <?php do_action( 'clickwhale_admin_sidebar_begin' ); ?>

    <?php
    if ( $tabs ) {
        $clickwhale_get_tab_raw = (string) filter_input( INPUT_GET, 'tab' );
        $clickwhale_get_tab = $clickwhale_get_tab_raw !== '' && $clickwhale_get_tab_raw !== null ? sanitize_text_field( $clickwhale_get_tab_raw ) : 'general_options';
        ?>
        <h2 class="nav-tab-wrapper">
            <?php foreach ( $tabs as $tab ) {
                $clickwhale_url    = '?page=' . CLICKWHALE_SLUG . '-settings&tab=' . $tab['url'];
                $clickwhale_active = $clickwhale_get_tab === $tab['url'] ? 'nav-tab-active' : '';
                ?>
                <a href="<?php echo esc_url( $clickwhale_url ); ?>"
                   class="nav-tab <?php echo esc_attr( $clickwhale_active ); ?>"
                ><?php echo esc_html( $tab['name'] ); ?></a>
            <?php } ?>
        </h2>

        <form method="post" action="options.php">
            <?php
            settings_fields( 'clickwhale_' . $clickwhale_get_tab );
            do_settings_sections( 'clickwhale_' . $clickwhale_get_tab );
            submit_button( __( 'Save changes', 'clickwhale' ) );
            ?>
        </form>
    <?php } ?>

    <?php do_action( 'clickwhale_admin_sidebar_end' ); ?>
</div>