<?php
global $wpdb;
$table_name  = $wpdb->prefix . 'clickwhale_categories';
$total_items = $wpdb->get_var( "SELECT COUNT(id) FROM $table_name" );
$limit       = ClickwhaleCategoriesHelper::get_limit();

$message = '';
$notice  = '';

// default $item which will be used for new records
$default = array(
	'id'          => 0,
	'title'       => '',
	'slug'        => '',
	'description' => '',
);

// here we are verifying does this request is post back and have correct nonce
if ( isset( $_REQUEST['nonce'] ) && wp_verify_nonce( $_REQUEST['nonce'], basename( __FILE__ ) ) ) {
	// combine our default item with request params
	$item = shortcode_atts( $default, $_REQUEST );
	// validate data, and if all ok save item to database
	// if id is zero insert otherwise update

	//$item_valid = clickwhale_validate_link($item);
	$item_validation = new Clickwhale_Category_Edit();
	$item_valid      = $item_validation->clickwhale_validate_category( $item );

	if ( $item_valid === true ) {

		$item = $item_validation->clear_category_slug( $item );

		if ( $item['id'] == 0 ) {
			$result     = $wpdb->insert( $table_name, $item );
			$item['id'] = $wpdb->insert_id;
			if ( $result ) {
				$message = __( 'Item was successfully saved', $this->plugin_name );
			} else {
				$notice = __( 'There was an error while saving item', $this->plugin_name );
			}
		} else {
			$result = $wpdb->update( $table_name, $item, array( 'id' => $item['id'] ) );
			if ( $result ) {
				$message = __( 'Item was successfully updated', $this->plugin_name );
			} else {
				$notice = __( 'There was an error while updating item', $this->plugin_name );
			}
		}
	} else {
		// if $item_valid not true it contains error message(s)
		$notice = $item_valid;
	}
} else {
	// if this is not post back we load item to edit or give new one to create
	$item = $default;
	if ( isset( $_REQUEST['id'] ) ) {
		$id   = sanitize_text_field( intval( $_REQUEST['id'] ) );
		$item = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE id = %d", $id ), ARRAY_A );
		if ( ! $item ) {
			$item   = $default;
			$notice = __( 'Item not found', $this->plugin_name );
		}
	}
}

do_action( 'clickwhale_admin_banner' );
?>

<div class="wrap">

	<?php
	echo ClickwhaleHepler::render_heading(
		array(
			'name'         => __( 'Category', $this->plugin_name ),
			'is_edit'      => isset( $item['id'] ) && $item['id'] !== 0,
			'link_to_list' => 'clickwhale-categories',
			'link_to_edit' => 'clickwhale-edit-category',
			'is_limit'     => ClickwhaleCategoriesHelper::get_categories_count() >= ClickwhaleCategoriesHelper::get_limit()
		)
	);
	?>

	<?php if ( ! empty( $notice ) ) { ?>
        <div id="notice" class="error"><p><?php echo esc_html( $notice ) ?></p></div>
	<?php } ?>
	<?php if ( ! empty( $message ) ) { ?>
        <div id="message" class="updated"><p><?php echo esc_html( $message ) ?></p></div>
	<?php } ?>

    <form id="form_edit_category" method="POST">
        <input type="hidden" name="nonce" value="<?php echo wp_create_nonce( basename( __FILE__ ) ) ?>"/>
		<?php /* NOTICE: here we storing id to determine will be item added or updated */ ?>
        <input type="hidden" name="id" value="<?php echo esc_attr( $item['id'] ) ?>"/>

        <div class="metabox-holder" id="poststuff">
            <div id="post-body">
                <div id="post-body-content">
                    <table style="width: 100%;" class="form-table">
                        <tbody>
                        <tr class="form-field">
                            <th scope="row">
                                <label for="link_title"><?php _e( 'Title', $this->plugin_name ) ?></label>
                            </th>
                            <td>
                                <input id="title"
                                       name="title"
                                       type="text"
                                       style="width: 95%"
                                       value="<?php echo esc_attr( wp_unslash( $item['title'] ) ) ?>"
                                       size="50"
                                       class="code"
                                       placeholder="<?php _e( 'Category Title', $this->plugin_name ) ?>"
                                       required>
                            </td>
                        </tr>

                        <tr class="form-field">
                            <th scope="row">
                                <label for="link_slug"><?php _e( 'Slug', $this->plugin_name ) ?></label>
                            </th>
                            <td>
                                <input id="slug"
                                       name="slug"
                                       type="text"
                                       style="width: 95%"
                                       value="<?php echo esc_attr( $item['slug'] ) ?>"
                                       size="50"
                                       class="code"
                                       placeholder="<?php _e( 'Category Slug', $this->plugin_name ) ?>">
                            </td>
                        </tr>
                        <tr class="form-field">
                            <th scope="row">
                                <label for="link_description"><?php _e( 'Description', $this->plugin_name ) ?></label>
                            </th>
                            <td>
                                    <textarea id="description"
                                              name="description"
                                              style="width: 95%"
                                              rows="5"
                                              class="code"
                                              placeholder="<?php _e( 'Description', $this->plugin_name ) ?>"
                                    ><?php echo esc_html( wp_unslash( $item['description'] ) ) ?></textarea>
                            </td>
                        </tr>
                        </tbody>
                    </table>

					<?php if ( $total_items < $limit ) { ?>
                        <input type="submit" value="<?php _e( 'Save category', $this->plugin_name ) ?>" id="submit"
                               class="button-primary" name="submit">
					<?php } ?>
                </div>
            </div>
        </div>
    </form>
</div>