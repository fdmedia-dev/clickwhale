<?php

global $wpdb;

use clickwhale\includes\admin\linkpages\Clickwhale_Linkpages_List_Table;
use clickwhale\includes\helpers\{Helper, Linkpages_Helper};

$table = new Clickwhale_Linkpages_List_Table();
$table->prepare_items();

$message = '';
if ( 'delete' === $table->current_action() ) {
	$message = __( 'Items deleted', CLICKWHALE_NAME );
}

do_action( 'clickwhale_admin_banner' );
?>
<div class="wrap">
	<?php
	echo Helper::render_heading(
		array(
			'name'         => esc_html( get_admin_page_title() ),
			'is_list'      => true,
			'link_to_add' => CLICKWHALE_SLUG . '-edit-linkpage',
			'is_limit'     => Linkpages_Helper::get_count() >= Linkpages_Helper::get_limit()
		)
	);

    if ( ! empty( $message ) ) { ?>
        <div class="updated below-h2" id="message"><p><?php echo esc_html( $message ) ?></p></div>
	<?php } ?>

	<?php if ( Linkpages_Helper::get_count() >= Linkpages_Helper::get_limit() ) { ?>
        <div class="notice notice-info">
            <p>
                <?php echo Linkpages_Helper::get_limitation_notice(); ?>
                <?php echo Helper::get_pro_message(); ?>
            </p>
        </div>
        <hr class="wp-header-end">
	<?php } ?>

    <?php do_action( 'clickwhale_admin_sidebar_begin' ); ?>

    <form method="GET">
        <input type="hidden" name="page" value="<?php echo esc_attr( $_REQUEST['page'] ) ?>"/>
		<?php $table->display(); ?>
    </form>

    <?php do_action( 'clickwhale_admin_sidebar_end' ); ?>

</div>