<?php

// HEADING
if ( isset( $item['id'] ) && $item['id'] !== 0 ) {
	$pageHeading = __( 'Edit Tracking Code', $this->plugin_name );
} else {
	$pageHeading = __( 'Add Tracking Code', $this->plugin_name );
}
$parentPage = 'clickwhale-tracking-codes';
$editPage   = 'clickwhale-edit-tracking-code';

do_action( 'clickwhale_admin_banner' );
?>

<div class="wrap">
    <h1 class="wp-heading-inline">
		<?php echo $pageHeading ?>
        <a class="page-title-action"
           href="<?php echo get_admin_url( get_current_blog_id(),
			   'admin.php?page=clickwhale-tracking-codes' ); ?>"><?php _e( 'Back to List', $this->plugin_name ) ?></a>
        <a href="<?php echo get_admin_url( get_current_blog_id(), 'admin.php?page=clickwhale-edit-link' ); ?>"
           class="page-title-action"><?php _e( 'Add New', $this->plugin_name ) ?></a>
    </h1>

    <form id="form_edit_linkpage" method="POST" action="<?php echo esc_attr( admin_url( 'admin-post.php' ) ); ?>">
        <input type="hidden" name="action" value="save_update_linkpage">
        <input type="hidden" name="nonce" value="<?php echo wp_create_nonce( basename( __FILE__ ) ) ?>"/>
        <input type="hidden" name="id" value="<?php echo esc_attr( $item['id'] ) ?>"/>

        <div id="post-body-content">


        </div>
    </form>

</div>
