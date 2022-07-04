<?php

global $wpdb;
$categories_table = $wpdb->prefix . 'clickwhale_categories';
$total_items = $wpdb->get_var("SELECT COUNT(id) FROM $categories_table");
$limit = apply_filters( 'clickwhale_categories_limit', 10 );

$table = new Clickwhale_Categories_List_Table();
$table->prepare_items();

$message = '';
if ('delete' === $table->current_action()) {
    $message = '<div class="updated below-h2" id="message"><p>' . sprintf(__('Items deleted: %d', 'clickwhale'), count($_REQUEST['id'])) . '</p></div>';
}

do_action( 'clickwhale_admin_banner' );
?>

<div class="wrap">

    <h1 class="wp-heading-inline">
        <?php echo esc_html( get_admin_page_title() ); ?>
        <?php if($total_items < $limit){ ?>
            <a href="<?php echo get_admin_url(get_current_blog_id(), 'admin.php?page=clickwhale-edit-category');?>" class="page-title-action"><?php _e('Add new', 'clickwhale') ?></a>
        <?php } ?>
    </h1>
    <?php echo $message; ?>

    <form method="GET">
        <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>"/>
        <?php 
        $table->search_box(__('Search', 'clickwhale'), 'search_id');
        $table->display();
        ?>
    </form>

</div>