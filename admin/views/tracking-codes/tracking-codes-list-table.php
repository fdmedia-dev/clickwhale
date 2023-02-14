<?php

global $wpdb;


do_action( 'clickwhale_admin_banner' );
?>

<div class="wrap">
	<h1 class="wp-heading-inline">
		<?php echo esc_html( get_admin_page_title() ); ?>
	</h1>

	<?php if ( ! empty( $message ) ) { ?>
		<div class="updated below-h2" id="message"><p><?php echo esc_html( $message ) ?></p></div>
	<?php } ?>

	<form method="GET">
		<input type="hidden" name="page" value="<?php echo esc_attr( $_REQUEST['page'] ) ?>"/>
		<?php //$table->display(); ?>
	</form>

</div>