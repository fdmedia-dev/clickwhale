<?php
use clickwhale\includes\admin\categories\Clickwhale_Categories_List_Table;
use clickwhale\includes\helpers\{Helper, Categories_Helper};

$limit = Categories_Helper::get_limit();
$table = new Clickwhale_Categories_List_Table();
$message = array();

try {
    $table->prepare_items();

    if ( 'delete' === $table->current_action() ) {
        $message = array(
            'class' => 'updated',
            'text' => __( 'Items deleted', 'clickwhale' )
        );
    }

} catch ( Exception $e ) {
    $message = array(
        'class' => 'error',
        'text' => __( 'An error occurred', 'clickwhale' ) . ': ' . $e->getMessage()
    );
}

do_action( 'clickwhale_admin_banner' );
?>
<div class="wrap">
    <?php
    echo Helper::render_heading(
        array(
            'name'        => esc_html( get_admin_page_title() ),
            'is_list'     => true,
            'link_to_add' => CLICKWHALE_SLUG . '-edit-category',
            'is_limit'    => Categories_Helper::get_count() >= $limit
        )
    );

    if ( ! empty( $message ) ) { ?>
        <div class="<?php echo esc_attr( $message['class'] ); ?> below-h2" id="message"><p><?php echo esc_html( $message['text'] ); ?></p></div>
    <?php } ?>

    <?php if ( Categories_Helper::get_count() >= $limit ) { ?>
        <div class="notice notice-info">
            <p>
                <?php echo Categories_Helper::get_limitation_notice(); ?>
                <?php echo Helper::get_pro_message(); ?>
            </p>
        </div>
        <hr class="wp-header-end">
    <?php } ?>

    <?php do_action( 'clickwhale_admin_sidebar_begin' ); ?>

    <form method="GET">
        <input type="hidden" name="page" value="<?php echo esc_attr( $_GET['page'] ); ?>" />
        <?php
            $table->search_box( __( 'Search', 'clickwhale' ), 'search_id' );
            $table->display();
        ?>
    </form>

    <?php do_action( 'clickwhale_admin_sidebar_end' ); ?>

</div>