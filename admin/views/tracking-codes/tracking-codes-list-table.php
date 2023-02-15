<?php

global $wpdb;


do_action( 'clickwhale_admin_banner' );
?>

<div class="wrap">
	<?php
	echo ClickwhaleHepler::render_heading(
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

    <form method="GET">
        <input type="hidden" name="page" value="<?php echo esc_attr( $_REQUEST['page'] ) ?>"/>
		<?php //$table->display(); ?>
    </form>

</div>