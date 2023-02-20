<?php

global $wpdb;

$table = new ClickwhaleLinkpagesListTable();
$table->prepare_items();

$message = '';
if ( 'delete' === $table->current_action() ) {
	$message = __( 'Items deleted', $this->plugin_name );
}

do_action( 'clickwhale_admin_banner' );
?>

<div class="wrap">
	<?php
	echo ClickwhaleHepler::render_heading(
		array(
			'name'         => esc_html( get_admin_page_title() ),
			'is_list'      => true,
			'link_to_edit' => 'clickwhale-edit-linkpage',
			'is_limit'     => ClickwhaleLinkpagesHelper::get_linkpages_count() >= ClickwhaleLinkpagesHelper::get_limit()
		)
	);
	?>

	<?php if ( ! empty( $message ) ) { ?>
        <div class="updated below-h2" id="message"><p><?php echo esc_html( $message ) ?></p></div>
	<?php } ?>

	<?php if ( ClickwhaleLinkpagesHelper::get_linkpages_count() >= ClickwhaleLinkpagesHelper::get_limit() ) { ?>
        <div class="notice notice-info"><p><?php printf( 'Currently, a maximum of %d %s can be created.',
					ClickwhaleLinkpagesHelper::get_limit(),
					ClickwhaleLinkpagesHelper::get_limit() === 1 ? 'link page' : 'link pages' ); ?></p></div>
        <hr class="wp-header-end">
	<?php } ?>

    <form method="GET">
        <input type="hidden" name="page" value="<?php echo esc_attr( $_REQUEST['page'] ) ?>"/>
		<?php $table->display(); ?>
    </form>

</div>