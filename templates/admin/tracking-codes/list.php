<?php
use Clickwhale\Admin\TrackingCodes\Clickwhale_Tracking_Codes_List_Table;
use Clickwhale\Helpers\{Helper, Tracking_Codes_Helper};

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! isset( $clickwhale_table ) || ! $clickwhale_table instanceof WP_List_Table ) {
    $clickwhale_table = new Clickwhale_Tracking_Codes_List_Table();
}
$clickwhale_message = array();

try {
    $clickwhale_table->prepare_items();

    if ( 'delete' === $clickwhale_table->current_action() ) {
        $clickwhale_message = array(
            'class' => 'updated',
            'text' => __( 'Items deleted', 'clickwhale' )
        );
    }

} catch ( Exception $e ) {
    $clickwhale_message = array(
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

    if ( ! empty( $clickwhale_message ) ) { ?>
        <div class="<?php echo esc_attr( $clickwhale_message['class'] ); ?> below-h2" id="message"><p><?php echo esc_html( $clickwhale_message['text'] ); ?></p></div>
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
        <input type="hidden" name="page" value="<?php echo esc_attr( sanitize_key( (string) filter_input( INPUT_GET, 'page' ) ) ); ?>" />
        <?php $clickwhale_table->display(); ?>
    </form>

    <?php do_action( 'clickwhale_admin_sidebar_end' ); ?>

</div>