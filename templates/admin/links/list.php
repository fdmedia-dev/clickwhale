<?php

use clickwhale\includes\helpers\Helper;
use clickwhale\includes\admin\links\Clickwhale_Links_List_Table;
if ( !defined( 'ABSPATH' ) ) {
    exit;
}
if ( !isset( $table ) || !$table instanceof WP_List_Table ) {
    $table = new Clickwhale_Links_List_Table();
}
$message = array();
try {
    $table->prepare_items();
    if ( 'delete' === $table->current_action() ) {
        $message = array(
            'class' => 'updated',
            'text'  => __( 'Items deleted', 'clickwhale' ),
        );
    }
} catch ( Exception $e ) {
    $message = array(
        'class' => 'error',
        'text'  => __( 'An error occurred', 'clickwhale' ) . ': ' . $e->getMessage(),
    );
}
do_action( 'clickwhale_admin_banner' );
?>
<div class="wrap">
    <?php 
echo wp_kses( Helper::render_heading( array(
    'name'        => esc_html( get_admin_page_title() ),
    'is_list'     => true,
    'link_to_add' => esc_attr( CLICKWHALE_SLUG ) . '-edit-link',
    'link_custom' => array(
        'url'   => esc_url( admin_url( 'admin.php?page=' . CLICKWHALE_SLUG . '-tools&tab=import' ) ),
        'title' => esc_html__( 'Import', 'clickwhale' ),
    ),
) ), Helper::get_allowed_tags() );
if ( !empty( $message ) ) {
    ?>
        <div class="<?php 
    echo esc_attr( $message['class'] );
    ?> below-h2" id="message"><p><?php 
    echo esc_html( $message['text'] );
    ?></p></div>
    <?php 
}
?>

    <hr class="wp-header-end">

    <?php 
do_action( 'clickwhale_admin_sidebar_begin' );
?>

    <form method="GET">
        <input type="hidden" name="page" value="<?php 
echo esc_attr( sanitize_key( $_GET['page'] ) );
?>" />
        <?php 
$table->search_box( __( 'Search', 'clickwhale' ), 'search_id' );
$table->display();
?>
    </form>

    <?php 
do_action( 'clickwhale_admin_sidebar_end' );
?>

</div>