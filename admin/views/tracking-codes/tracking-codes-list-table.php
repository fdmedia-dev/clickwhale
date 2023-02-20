<?php

global $wpdb;

$table = new ClickwhaleTrackingCodesListTable();
$table->prepare_items();

do_action( 'clickwhale_admin_banner' );
?>

<div class="wrap">
	<?php
	echo ClickwhaleHepler::render_heading(
		array(
			'name'         => esc_html( get_admin_page_title() ),
			'is_list'      => true,
			'link_to_edit' => 'clickwhale-edit-tracking-code',
			'is_limit'     => ClickwhaleTrackingCodesHelper::get_count() >= ClickwhaleTrackingCodesHelper::get_limit(),
		)
	);
	?>

	<?php if ( ! empty( $message ) ) { ?>
        <div class="updated below-h2" id="message"><p><?php echo esc_html( $message ) ?></p></div>
	<?php } ?>

	<?php if ( ClickwhaleTrackingCodesHelper::get_count() >= ClickwhaleTrackingCodesHelper::get_limit() ) { ?>
        <div class="notice notice-info">
            <p><?php printf( 'Currently, a maximum of %d %s can be created.',
					ClickwhaleTrackingCodesHelper::get_limit(),
					ClickwhaleTrackingCodesHelper::get_limit() === 1 ? 'tracking code' : 'tracking codes'
				); ?>
            </p>
        </div>
        <hr class="wp-header-end">
	<?php } ?>

    <form method="GET">
        <input type="hidden" name="page" value="<?php echo esc_attr( $_REQUEST['page'] ) ?>"/>
		<?php $table->display(); ?>
    </form>

</div>