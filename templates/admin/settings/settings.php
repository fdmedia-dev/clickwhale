<?php

use Clickwhale\Admin\Clickwhale_Settings;
use Clickwhale\Helpers\Helper;

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
        $clickwhale_active_tab = null;
        foreach ( $tabs as $clickwhale_tab_data ) {
            if ( $clickwhale_tab_data['url'] === $clickwhale_get_tab ) {
                $clickwhale_active_tab = $clickwhale_tab_data;
                break;
            }
        }
        ?>
        <div class="nav-tab-wrapper">
            <?php foreach ( $tabs as $tab ) {
                $clickwhale_url    = '?page=' . CLICKWHALE_SLUG . '-settings&tab=' . $tab['url'];
                $clickwhale_active = $clickwhale_get_tab === $tab['url'] ? 'nav-tab-active' : '';
                ?>
                <a href="<?php echo esc_url( $clickwhale_url ); ?>"
                   class="nav-tab <?php echo esc_attr( $clickwhale_active ); ?>"
                ><?php
                    echo esc_html( $tab['name'] );
                    if ( ! empty( $tab['locked'] ) ) {
                        echo wp_kses( Helper::pro_badge( __( 'Available in ClickWhale PRO', 'clickwhale' ) ), Helper::get_allowed_tags() );
                    }
                ?></a>
            <?php } ?>
        </div>

        <?php if ( $clickwhale_active_tab && ! empty( $clickwhale_active_tab['locked'] ) ) : ?>
            <?php include CLICKWHALE_TEMPLATES_DIR . '/admin/pro-tab-teaser.php'; ?>
        <?php else : ?>
            <form method="post" action="options.php">
                <?php
                settings_fields( 'clickwhale_' . $clickwhale_get_tab );
                do_settings_sections( 'clickwhale_' . $clickwhale_get_tab );
                submit_button( __( 'Save changes', 'clickwhale' ) );
                ?>
            </form>
        <?php endif; ?>
    <?php } ?>

    <?php do_action( 'clickwhale_admin_sidebar_end' ); ?>
</div>