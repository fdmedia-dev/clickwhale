<?php
global $wpdb;
$table_name = $wpdb->prefix . 'clickwhale_categories';
$total_items = $wpdb->get_var("SELECT COUNT(id) FROM $table_name");
$limit = apply_filters( 'clickwhale_categories_limit', 10 );

$message = '';
$notice = '';

// default $item which will be used for new records
$default = array(
    'id'            => 0,
    'title'         => '',
    'slug'          => '',
    'description'   => '',
);

// here we are verifying does this request is post back and have correct nonce
if (wp_verify_nonce($_REQUEST['nonce'], basename(__FILE__))) {
    // combine our default item with request params
    $item = shortcode_atts($default, $_REQUEST);
    // validate data, and if all ok save item to database
    // if id is zero insert otherwise update
    
    //$item_valid = clickwhale_validate_link($item);
    $item_validation = new Clickwhale_Category_Edit();
    $item_valid = $item_validation->clickwhale_validate_category($item);

    if ($item_valid === true) {

        $item = $item_validation->clear_category_slug($item);

        if ($item['id'] == 0) {
            $result = $wpdb->insert($table_name, $item);
            $item['id'] = $wpdb->insert_id;
            if ($result) {
                $message = __('Item was successfully saved', 'clickwhale');
            } else {
                $notice = __('There was an error while saving item', 'clickwhale');
            }
        } else {
            $result = $wpdb->update($table_name, $item, array('id' => $item['id']));
            if ($result) {
                $message = __('Item was successfully updated', 'clickwhale');
            } else {
                $notice = __('There was an error while updating item', 'clickwhale');
            }
        }
    } else {
        // if $item_valid not true it contains error message(s)
        $notice = $item_valid;
    }
}
else {
    // if this is not post back we load item to edit or give new one to create
    $item = $default;
    if (isset($_REQUEST['id'])) {
        $item = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $_REQUEST['id']), ARRAY_A);
        if (!$item) {
            $item = $default;
            $notice = __('Item not found', 'clickwhale');
        }
    }
}
?>

<div class="wrap">
    <h1 class="wp-heading-inline">
        <?php _e('Edit Category', 'clickwhale') ?>
        <a class="page-title-action" href="<?php echo get_admin_url(get_current_blog_id(), 'admin.php?page=clickwhale');?>"><?php _e('Back to list', 'clickwhale')?></a>
    </h1>

    <?php if (!empty($notice)) { ?>
        <div id="notice" class="error"><p><?php echo $notice ?></p></div>
    <?php } ?>
    <?php if (!empty($message)) { ?>
        <div id="message" class="updated"><p><?php echo $message ?></p></div>
    <?php } ?>

    <form id="form_edit_category" method="POST">
        <input type="hidden" name="nonce" value="<?php echo wp_create_nonce(basename(__FILE__))?>"/>
        <?php /* NOTICE: here we storing id to determine will be item added or updated */ ?>
        <input type="hidden" name="id" value="<?php echo $item['id'] ?>"/>

        <div class="metabox-holder" id="poststuff">
            <div id="post-body">
                <div id="post-body-content">
                    <table cellspacing="2" cellpadding="5" style="width: 100%;" class="form-table">
                        <tbody>
                            <tr class="form-field">
                                <th valign="top" scope="row">
                                    <label for="link_title"><?php _e('Title', 'clickwhale')?></label>
                                </th>
                                <td>
                                    <input 	id="title" 
                                            name="title" 
                                            type="text" 
                                            style="width: 95%" 
                                            value="<?php echo esc_attr($item['title'])?>"
                                            size="50" 
                                            class="code" 
                                            placeholder="<?php _e('Category Title', 'clickwhale')?>" 
                                            required>
                                </td>
                            </tr>
                            
                            <tr class="form-field">
                                <th valign="top" scope="row">
                                    <label for="link_slug"><?php _e('Slug', 'clickwhale')?></label>
                                </th>
                                <td>
                                    <input 	id="slug" 
                                            name="slug" 
                                            type="text" 
                                            style="width: 95%" 
                                            value="<?php echo esc_attr($item['slug'])?>"
                                            size="50" 
                                            class="code" 
                                            placeholder="<?php _e('Category Slug', 'clickwhale')?>" >
                                </td>
                            </tr>
                            <tr class="form-field">
                                <th valign="top" scope="row">
                                    <label for="link_description"><?php _e('Description', 'clickwhale')?></label>
                                </th>
                                <td>
                                    <textarea 	id="description" 
                                                name="description"
                                                style="width: 95%"
                                                rows="5"
                                                class="code" 
                                                placeholder="<?php _e('Description', 'clickwhale')?>"
                                                ><?php echo esc_attr($item['description'])?></textarea>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <?php if($total_items < $limit){ ?>
                        <input type="submit" value="<?php _e('Save category', 'clickwhale')?>" id="submit" class="button-primary" name="submit">
                    <?php } ?>
                </div>
            </div>
        </div>
    </form>
</div>