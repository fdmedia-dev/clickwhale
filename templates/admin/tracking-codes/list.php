<?php

global $wpdb;

use clickwhale\includes\admin\tracking_codes\Clickwhale_Tracking_Codes_List_Table;
use clickwhale\includes\helpers\{Helper, Tracking_Codes_Helper};

$table = new Clickwhale_Tracking_Codes_List_Table();
$table->prepare_items();
$limit = Tracking_Codes_Helper::is_limit() ? 'block' : 'none';

$message = '';
if ( 'delete' === $table->current_action() ) {
	$message = __( 'Tracking code deleted', CLICKWHALE_NAME );
}

do_action( 'clickwhale_admin_banner' );
?>
<div class="wrap">
	<?php
	echo Helper::render_heading(
		array(
			'name'         => esc_html( get_admin_page_title() ),
			'is_list'      => true,
			'link_to_add' => CLICKWHALE_SLUG . '-edit-tracking-code',
		)
	);

    if ( ! empty( $message ) ) { ?>
        <div class="updated below-h2" id="message"><p><?php echo esc_html( $message ) ?></p></div>
	<?php } ?>

    <div id="clickwhale_tracking_codes_list_limit_notice"
         class="notice notice-info"
         style="display: <?php echo $limit ?>">
        <p>
			<?php echo Tracking_Codes_Helper::get_limitation_notice(); ?>
			<?php echo Helper::get_pro_message(); ?>
        </p>
    </div>
    <hr class="wp-header-end">

    <?php do_action( 'clickwhale_admin_sidebar_begin' ); ?>

    <form method="GET">
        <input type="hidden" name="page" value="<?php echo esc_attr( $_REQUEST['page'] ) ?>"/>
		<?php $table->display(); ?>
    </form>

    <?php do_action( 'clickwhale_admin_sidebar_end' ); ?>

</div>