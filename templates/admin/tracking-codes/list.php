<?php
use clickwhale\includes\admin\tracking_codes\Clickwhale_Tracking_Codes_List_Table;
use clickwhale\includes\helpers\{Helper, Tracking_Codes_Helper};

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$table = new Clickwhale_Tracking_Codes_List_Table();
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
    echo wp_kses(
        Helper::render_heading(
            array(
                'name'        => esc_html( get_admin_page_title() ),
                'is_list'     => true,
                'link_to_add' => esc_attr( CLICKWHALE_SLUG ) . '-edit-tracking-code'
            )
        ),
        Helper::get_allowed_tags()
    );

    if ( ! empty( $message ) ) { ?>
        <div class="<?php echo esc_attr( $message['class'] ); ?> below-h2" id="message"><p><?php echo esc_html( $message['text'] ); ?></p></div>
    <?php } ?>

    <?php if ( Tracking_Codes_Helper::is_limit() ) { ?>
        <div id="clickwhale_tracking_codes_list_limit_notice"
             class="notice notice-info"
             style="display: block"
        >
            <p>
                <?php echo esc_html( Tracking_Codes_Helper::get_limitation_notice() ); ?>
                <?php echo wp_kses( Helper::get_pro_message(), Helper::get_allowed_tags() ); ?>
            </p>
        </div>
        <hr class="wp-header-end">
    <?php } ?>

    <?php do_action( 'clickwhale_admin_sidebar_begin' ); ?>

    <form method="GET">
        <input type="hidden" name="page" value="<?php echo esc_attr( sanitize_key( $_GET['page'] ) ); ?>" />
        <?php $table->display(); ?>
    </form>

    <?php do_action( 'clickwhale_admin_sidebar_end' ); ?>

</div>