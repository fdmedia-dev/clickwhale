<?php

// HEADING
if ( isset( $item['id'] ) && $item['id'] !== 0 ) {
	$pageHeading = __( 'Edit Link Page', $this->plugin_name );
} else {
	$pageHeading = __( 'Add Link Page', $this->plugin_name );
}

do_action( 'clickwhale_admin_banner' );
?>

<div class="wrap">
	<h1 class="wp-heading-inline">
		<?php echo $pageHeading ?>

	</h1>

	<form id="form_edit_linkpage" method="POST" action="<?php echo esc_attr( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="save_update_linkpage">
		<input type="hidden" name="nonce" value="<?php echo wp_create_nonce( basename( __FILE__ ) ) ?>"/>
		<input type="hidden" name="id" value="<?php echo esc_attr( $item['id'] ) ?>"/>

		<div id="post-body-content">


		</div>
	</form>

</div>
