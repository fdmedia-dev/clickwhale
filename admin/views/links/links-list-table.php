<?php

global $wpdb;

$table = new Clickwhale_links_List_Table();
$table->prepare_items();

$message = '';
if ( 'delete' === $table->current_action() ) {
	$message = __( 'Items deleted', $this->plugin_name );
}

do_action( 'clickwhale_admin_banner' );
?>

<div class="wrap">
	<?php
	echo ClickwhaleHelper::render_heading(
		array(
			'name'         => esc_html( get_admin_page_title() ),
			'is_list'      => true,
			'link_to_edit' => 'clickwhale-edit-link',
		)
	);
	?>

	<?php if ( ! empty( $message ) ) { ?>
        <div class="updated below-h2" id="message"><p><?php echo esc_html( $message ) ?></p></div>
	<?php } ?>

    <hr class="wp-header-end">

    <form method="GET">
        <input type="hidden" name="page" value="<?php echo esc_attr( $_REQUEST['page'] ) ?>"/>
		<?php
		$table->search_box( __( 'Search', 'clickwhale' ), 'search_id' );
		$table->display();
		?>
    </form>

</div>