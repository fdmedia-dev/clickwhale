<?php

use clickwhale\includes\helpers\Helper;
use clickwhale\includes\admin\links\Clickwhale_Links_List_Table;
if ( !isset( $table ) || !$table instanceof WP_List_Table ) {
    $table = new Clickwhale_Links_List_Table();
}
$message = array();
try {
    $table->prepare_items();
    if ( 'delete' === $table->current_action() ) {
        $message = array(
            'class' => 'updated',
            'text'  => __( 'Items deleted', CLICKWHALE_NAME ),
        );
    }
} catch ( Exception $e ) {
    $message = array(
        'class' => 'error',
        'text'  => __( 'An error occurred', CLICKWHALE_NAME ) . ': ' . $e->getMessage(),
    );
}
do_action( 'clickwhale_admin_banner' );
?>
<div class="wrap">
    <?php 
echo Helper::render_heading( array(
    'name'        => esc_html( get_admin_page_title() ),
    'is_list'     => true,
    'link_to_add' => CLICKWHALE_SLUG . '-edit-link',
    'link_custom' => array(
        'url'   => esc_url( admin_url( 'admin.php?page=' . CLICKWHALE_SLUG . '-tools&tab=import' ) ),
        'title' => __( 'Import', CLICKWHALE_NAME ),
    ),
) );
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
echo esc_attr( $_GET['page'] );
?>" />
        <?php 
$table->search_box( __( 'Search', CLICKWHALE_NAME ), 'search_id' );
$table->display();
?>
    </form>

    <?php 
do_action( 'clickwhale_admin_sidebar_end' );
?>

</div>