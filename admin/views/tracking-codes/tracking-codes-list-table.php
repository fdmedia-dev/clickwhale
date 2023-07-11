<?php

global $wpdb;

$table = new ClickwhaleTrackingCodesListTable();
$table->prepare_items();
$limit = ClickwhaleTrackingCodesHelper::is_limit() ? 'block' : 'none';

$message = '';
if ( 'delete' === $table->current_action() ) {
	$message = __( 'Tracking code deleted', 'clickwhale' );
}

do_action( 'clickwhale_admin_banner' );
?>

<div class="wrap">
	<?php
	echo ClickwhaleHelper::render_heading(
		array(
			'name'         => esc_html( get_admin_page_title() ),
			'is_list'      => true,
			'link_to_edit' => 'clickwhale-edit-tracking-code',
		)
	);
	?>

	<?php if ( ! empty( $message ) ) { ?>
        <div class="updated below-h2" id="message"><p><?php echo esc_html( $message ) ?></p></div>
	<?php } ?>

    <div id="clickwhale_tracking_codes_list_limit_notice"
         class="notice notice-info"
         style="display: <?php echo $limit ?>">
        <p>
			<?php echo ClickwhaleTrackingCodesHelper::get_limitation_notice(); ?>
			<?php echo ClickwhaleHelper::get_pro_message(); ?>
        </p>
    </div>
    <hr class="wp-header-end">

    <form method="GET">
        <input type="hidden" name="page" value="<?php echo esc_attr( $_REQUEST['page'] ) ?>"/>
		<?php $table->display(); ?>
    </form>

</div>