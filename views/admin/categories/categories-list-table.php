<?php

global $wpdb;

use clickwhale\includes\admin\categories\Clickwhale_Categories_List_Table;

$categories_table = $wpdb->prefix . 'clickwhale_categories';
$total_items      = $wpdb->get_var( "SELECT COUNT(id) FROM $categories_table" );
$limit            = ClickwhaleCategoriesHelper::get_limit();

$table = new Clickwhale_Categories_List_Table();
$table->prepare_items();

$message = '';
if ( 'delete' === $table->current_action() ) {
	$message = __( 'Items deleted', 'clickwhale' );
}

do_action( 'clickwhale_admin_banner' );
?>

<div class="wrap">

	<?php
	echo ClickwhaleHelper::render_heading(
		array(
			'name'         => esc_html( get_admin_page_title() ),
			'is_list'      => true,
			'link_to_edit' => 'clickwhale-edit-category',
			'is_limit'     => ClickwhaleCategoriesHelper::get_categories_count() >= ClickwhaleCategoriesHelper::get_limit()
		)
	);
	?>

	<?php if ( ! empty( $message ) ) { ?>
        <div class="updated below-h2" id="message"><p><?php echo esc_html( $message ) ?></p></div>
	<?php } ?>

	<?php if ( ClickwhaleCategoriesHelper::get_categories_count() >= ClickwhaleCategoriesHelper::get_limit() ) { ?>
        <div class="notice notice-info">
            <p>
				<?php echo ClickwhaleCategoriesHelper::get_limitation_notice(); ?>
				<?php echo ClickwhaleHelper::get_pro_message(); ?>
            </p>
        </div>
        <hr class="wp-header-end">
	<?php } ?>

    <form method="GET">
        <input type="hidden" name="page" value="<?php echo esc_attr( $_REQUEST['page'] ) ?>"/>
		<?php
		$table->search_box( __( 'Search', 'clickwhale' ), 'search_id' );
		$table->display();
		?>
    </form>

</div>