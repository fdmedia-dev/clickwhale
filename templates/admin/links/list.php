<?php

use Clickwhale\Helpers\Helper;
use Clickwhale\Admin\Links\Clickwhale_Links_List_Table;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( function_exists( 'clickwhale_fs' ) && clickwhale_fs()->is__premium_only() ) {
    if ( class_exists( 'ClickwhalePro\Admin\Links\Clickwhale_Pro_Links_List_Table' ) ) {
        $clickwhale_table = new ClickwhalePro\Admin\Links\Clickwhale_Pro_Links_List_Table();
    }
}

if ( ! isset( $clickwhale_table ) || ! $clickwhale_table instanceof WP_List_Table ) {
    $clickwhale_table = new Clickwhale_Links_List_Table();
}

$clickwhale_message = array();

try {
    $clickwhale_table->prepare_items();

    if ( 'delete' === $clickwhale_table->current_action() ) {
        $clickwhale_message = array(
                'class' => 'updated',
                'text'  => __( 'Items deleted', 'clickwhale' )
        );
    }

} catch ( Exception $e ) {
    $clickwhale_message = array(
            'class' => 'error',
            'text'  => __( 'An error occurred', 'clickwhale' ) . ': ' . $e->getMessage()
    );
}

do_action( 'clickwhale_admin_banner' );
?>
<div class="wrap">
    <?php
    echo wp_kses(
            Helper::render_heading(
                    array(
                            'name'        => esc_html( get_admin_page_title() ),
                            'is_list'     => true,
                            'link_to_add' => esc_attr( CLICKWHALE_SLUG ) . '-edit-link',
                            'link_custom' => array(
                                    'url'   => esc_url( admin_url( 'admin.php?page=' . CLICKWHALE_SLUG . '-tools&tab=import' ) ),
                                    'title' => esc_html__( 'Import', 'clickwhale' )
                            )
                    )
            ),
            Helper::get_allowed_tags()
    );

    if ( ! empty( $clickwhale_message ) ) { ?>
        <div class="<?php echo esc_attr( $clickwhale_message['class'] ); ?> below-h2" id="message">
            <p><?php echo esc_html( $clickwhale_message['text'] ); ?></p></div>
    <?php } ?>

    <hr class="wp-header-end">

    <?php do_action( 'clickwhale_admin_sidebar_begin' ); ?>

    <form method="GET">
        <input type="hidden" name="page"
               value="<?php echo esc_attr( sanitize_key( (string) filter_input( INPUT_GET, 'page' ) ) ); ?>"/>
        <?php
        $clickwhale_table->search_box( __( 'Search', 'clickwhale' ), 'search_id' );
        $clickwhale_table->display();
        ?>
    </form>

    <?php do_action( 'clickwhale_admin_sidebar_end' ); ?>

</div>