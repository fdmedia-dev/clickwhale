<?php
global $wpdb;
$table_name = $wpdb->prefix . 'clickwhale_links';

$message = '';
$notice = '';

// default $item which will be used for new records
$default = array(
    'id' => 0,
    'created_at'=> '',
    'updated_at'=> '',
    'title' => '',
    'url' => '',
    'slug' => '',
    'redirection' => '301',
    'description' => '',
    'categories' => '',
);

$link_edit = new Clickwhale_Link_Edit();
$link_categories = $link_edit->get_link_categories();

// here we are verifying does this request is post back and have correct nonce
if (isset($_REQUEST['nonce']) && wp_verify_nonce($_REQUEST['nonce'], basename(__FILE__))) {
    // combine our default item with request params
    $item = shortcode_atts($default, $_REQUEST);
    // validate data, and if all ok save item to database
    // if id is zero insert otherwise update

    $item_valid = $link_edit->clickwhale_validate_link($item);

    if ($item_valid === true) {
        $item = $link_edit->clear_link_slug($item);
        //$item['categories'] = serialize($item['categories']);
        if($item['categories']){
            $item['categories'] = implode(',', $item['categories']);
        } else {
            $item['categories'] = '';
        }
        
        
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
} else {
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
        <?php _e('Edit link', 'clickwhale') ?>
        <a class="page-title-action" href="<?php echo get_admin_url(get_current_blog_id(), 'admin.php?page=clickwhale');?>"><?php _e('Back to list', 'clickwhale')?></a>
    </h1>

    <?php if (!empty($notice)) { ?>
        <div id="notice" class="error"><p><?php echo $notice ?></p></div>
    <?php } ?>
    <?php if (!empty($message)) { ?>
        <div id="message" class="updated"><p><?php echo $message ?></p></div>
    <?php } ?>

    <form id="form_edit_link" method="POST">
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
                                            placeholder="<?php _e('Link Title', 'clickwhale')?>" 
                                            required>
                                </td>
                            </tr>
                            <tr class="form-field">
                                <th valign="top" scope="row">
                                    <label for="link_url"><?php _e('Target URL', 'clickwhale')?></label>
                                </th>
                                <td>
                                    <input 	id="url" 
                                            name="url" 
                                            type="text" 
                                            style="width: 95%" 
                                            value="<?php echo esc_attr($item['url'])?>"
                                            size="50" 
                                            class="code" 
                                            placeholder="<?php _e('Link Target URL', 'clickwhale')?>" 
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
                                            placeholder="<?php _e('Link Slug without /link/', 'clickwhale')?>" 
                                            required>
                                    <p id="slug__text">URL Preview: <?php echo get_bloginfo('url') ?>/link/<span><?php echo esc_attr($item['slug'])?></span></p>
                                </td>
                            </tr>
                            <tr class="form-field">
                                <th valign="top" scope="row">
                                    <label for="link_redirection"><?php _e('Redirection Type', 'clickwhale')?></label>
                                </th>
                                <td>
                                <select name="redirection" id="redirection">
                                    <option value="301" <?php selected( $item['redirection'], 301 ); ?>>301 redirect: Moved permanently</option>
                                    <option value="302" <?php selected( $item['redirection'], 302 ); ?>>302 redirect: Found / Moved temporarily</option>
                                    <option value="303" <?php selected( $item['redirection'], 303 ); ?>>303 redirect: See Other</option>
                                    <option value="307" <?php selected( $item['redirection'], 307 ); ?>>307 redirect: Temporarily Redirect</option>
                                    <option value="308" <?php selected( $item['redirection'], 308 ); ?>>308 redirect: Permanent Redirect</option>
                                </select>
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
                            <tr class="form-field">
                                <th valign="top" scope="row">
                                    <label for="link_categories"><?php _e('Category', 'clickwhale')?></label>
                                </th>
                                <td>
                                    <?php 
                                    if($link_categories) { 
                                        $current_categories = isset($item['categories']) ? explode(',',$item['categories']) : [];
                                        foreach($link_categories as $category) {
                                            ?>
                                            <p>
                                                <input  type="checkbox" 
                                                        id="category-<?php echo $category->id ?>" 
                                                        name="categories[]"
                                                        value="<?php echo $category->id ?>"
                                                        <?php
                                                        if( $current_categories) {
                                                            checked( in_array($category->id, $current_categories), 1 ); 
                                                        }
                                                        ?> 
                                                        />
                                                <label for="category-<?php echo $category->id ?>"><?php echo $category->title ?></label>
                                            </p>
                                        <?php 
                                        }
                                    }
                                    ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <input type="hidden" id="created_at" name="created_at" value="<?php echo $item['created_at'] ?>">
                    <input type="hidden" id="updated_at" name="updated_at" value="">

                    <input type="submit" value="<?php _e('Save', 'clickwhale')?>" id="submit" class="button-primary" name="submit">
                </div>
            </div>
        </div>
    </form>
</div>