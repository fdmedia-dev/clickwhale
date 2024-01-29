<?php

global $wpdb;

use clickwhale\includes\admin\links\Clickwhale_Links_List_Table;
use clickwhale\includes\helpers\Helper;

$table = new Clickwhale_links_List_Table();
$table->prepare_items();

$message = '';
if ( 'delete' === $table->current_action() ) {
	$message = __( 'Items deleted', CLICKWHALE_SLUG );
}

do_action( 'clickwhale_admin_banner' );
?>

<div class="wrap">
	<?php
	echo Helper::render_heading(
		array(
			'name'         => esc_html( get_admin_page_title() ),
			'is_list'      => true,
			'link_to_add' => 'clickwhale-edit-link',
			'link_custom'  => array(
				'url'   => esc_url( admin_url( 'admin.php?page=clickwhale-tools&tab=import' ) ),
				'title' => __( 'Import', CLICKWHALE_SLUG )
			)
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