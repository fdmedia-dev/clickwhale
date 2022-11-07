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
    <h1 class="wp-heading-inline">
		<?php echo esc_html( get_admin_page_title() ); ?>
		<?php if ( ClickwhaleLinkpagesHelper::get_linkpages_count() < ClickwhaleLinkpagesHelper::get_limit() ) { ?>
            <a href="<?php echo get_admin_url( get_current_blog_id(), 'admin.php?page=clickwhale-edit-linkpage' ); ?>"
               class="page-title-action"><?php _e( 'Add new', $this->plugin_name ) ?></a>
		<?php } ?>
    </h1>


	<?php if ( ! empty( $message ) ) { ?>
        <div class="updated below-h2" id="message"><p><?php echo esc_html( $message ) ?></p></div>
	<?php } ?>

    <hr class="wp-header-end">

	<?php if ( ClickwhaleLinkpagesHelper::get_linkpages_count() >= ClickwhaleLinkpagesHelper::get_limit() ) { ?>
        <div class="links-info"><?php printf( 'Currently, a maximum of %d %s can be created', array( ClickwhaleLinkpagesHelper::get_limit() ), ClickwhaleLinkpagesHelper::get_limit() === 1 ? 'link page' : 'link pages' ); ?></div>
        <hr class="wp-header-end">
	<?php } ?>

    <form method="GET">
        <input type="hidden" name="page" value="<?php echo esc_attr( $_REQUEST['page'] ) ?>"/>
		<?php $table->display(); ?>
    </form>

</div>