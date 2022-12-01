<?php
$link_edit = new Clickwhale_Link_Edit();
$link_edit->init();

$item            = $link_edit->get_item( $_REQUEST );
$item_categories = $link_edit->get_link_categories();
$options_general = get_option( 'clickwhale_general_options' );
if ( $item['slug'] ) {
	$slug = $item['slug'];
} else {
	$slug = isset( $options_general['slug'] ) && $options_general['slug'] !== '' ? $options_general['slug'] . '/' : '';
}
$message = get_transient( 'link-' . $item['id'] );

do_action( 'clickwhale_admin_banner' );
?>

<div class="wrap">
    <h1 class="wp-heading-inline">

		<?php
		if ( isset( $item['id'] ) && $item['id'] !== 0 ) {
			_e( 'Edit link', $this->plugin_name );
		} else {
			_e( 'Add Link', $this->plugin_name );
		}
		?>

        <a class="page-title-action"
           href="<?php echo get_admin_url( get_current_blog_id(), 'admin.php?page=clickwhale' ); ?>"><?php _e( 'Back to list', $this->plugin_name ) ?></a>
        <a href="<?php echo get_admin_url( get_current_blog_id(), 'admin.php?page=clickwhale-edit-link' ); ?>"
           class="page-title-action"><?php _e( 'Add new', $this->plugin_name ) ?></a>
    </h1>

	<?php if ( ! empty( $message ) ) { ?>
		<?php if ( $message === 'link_added' ) { ?>
            <div id="message" class="updated"><p><?php _e( 'Item was successfully saved', $this->plugin_name ) ?></p>
            </div>
		<?php } ?>
		<?php if ( $message === 'link_updated' ) { ?>
            <div id="message" class="updated"><p><?php _e( 'Item was successfully updated', $this->plugin_name ) ?></p>
            </div>
		<?php } ?>
		<?php delete_transient( 'link-' . $item['id'] ); ?>
	<?php } ?>

    <form id="form_edit_link" method="POST" action="<?php echo esc_attr( admin_url( 'admin-post.php' ) ); ?>">
        <input type="hidden" name="action" value="save_update_link">
        <input type="hidden" name="nonce" value="<?php echo wp_create_nonce( basename( __FILE__ ) ) ?>"/>
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
                                       value="<?php echo esc_attr( wp_unslash( $item['title'] ) ) ?>"
                                       size="40"
                                       class="regular-text"
                                       placeholder="<?php _e( 'Link Title', $this->plugin_name ) ?>"
                                       required>
                            </td>
                        </tr>
                        <tr class="form-field">
                            <th scope="row">
                                <label for="link_slug"><?php _e( 'Slug', $this->plugin_name ) ?></label>
                            </th>
                            <td>
                                <input id="cw-slug"
                                       name="slug"
                                       type="text"
                                       value="<?php echo esc_attr( $slug ) ?>"
                                       size="50"
                                       class="regular-text"
                                       placeholder="<?php esc_attr( __( 'Link Slug', $this->plugin_name ) ) ?>"
                                       required>
                                <p id="cw-slug--description"></p>
                                <p id="cw-slug--text">
									<?php $url = __( 'URL Preview', $this->plugin_name ) . ': ' . get_bloginfo( 'url' ) . '/'; ?>
									<?php echo esc_html( $url ) ?><span><?php echo esc_html( $item['slug'] ) ?></span>
                                </p>
                            </td>
                        </tr>
                        <tr class="form-field">
                            <th scope="row">
                                <label for="link_url"><?php _e( 'Target URL', $this->plugin_name ) ?></label>
                            </th>
                            <td>
                                <input id="url"
                                       name="url"
                                       type="text"
                                       value="<?php echo esc_attr( $item['url'] ) ?>"
                                       size="50"
                                       class="regular-text"
                                       placeholder="<?php _e( 'Link Target URL', $this->plugin_name ) ?>"
                                       required>
                            </td>
                        </tr>
                        <tr class="form-field">
                            <th scope="row">
                                <label for="link_redirection"><?php _e( 'Redirection Type', $this->plugin_name ) ?></label>
                            </th>
                            <td>
                                <select name="redirection" id="redirection" class="regular-text">
                                    <option value="301" <?php selected( $item['redirection'], 301 ); ?>>
                                        301 redirect: Moved permanently
                                    </option>
                                    <option value="302" <?php selected( $item['redirection'], 302 ); ?>>
                                        302 redirect: Found / Moved temporarily
                                    </option>
                                    <option value="303" <?php selected( $item['redirection'], 303 ); ?>>
                                        303 redirect: See Other
                                    </option>
                                    <option value="307" <?php selected( $item['redirection'], 307 ); ?>>
                                        307 redirect: Temporarily Redirect
                                    </option>
                                    <option value="308" <?php selected( $item['redirection'], 308 ); ?>>
                                        308 redirect: Permanent Redirect
                                    </option>
                                </select>
                            </td>
                        </tr>
                        <tr class="form-field">
                            <th scope="row">
                                <label for="nofollow"><?php _e( 'Nofollow', $this->plugin_name ) ?></label>
                            </th>
                            <td>
                                <fieldset>
                                    <input type="checkbox"
                                           id="nofollow"
                                           name="nofollow"
                                           value="1"
										<?php
										if ( $item['id'] === 0 && isset( $options_general['nofollow'] ) ) {
											echo 'checked';
										} else {
											checked( 1, $item['nofollow'] );
										}
										?>
                                    />
                                    <label for="nofollow"><?php _e( 'Check to mark link as nofollow & noindex', $this->plugin_name ) ?></label>
                                </fieldset>
                            </td>
                        </tr>
                        <tr class="form-field">
                            <th scope="row">
                                <label for="sponsored"><?php _e( 'Sponsored', $this->plugin_name ) ?></label>
                            </th>
                            <td>
                                <fieldset>
                                    <input type="checkbox"
                                           id="sponsored"
                                           name="sponsored"
                                           value="1"
										<?php
										if ( $item['id'] === 0 && isset( $options_general['sponsored'] ) ) {
											echo 'checked';
										} else {
											checked( 1, $item['sponsored'] );
										}
										?>
                                    />
                                    <label for="sponsored"><?php _e( 'Check to mark link as sponsored', $this->plugin_name ) ?></label>
                                </fieldset>
                            </td>
                        </tr>
                        <tr class="form-field">
                            <th scope="row">
                                <label for="link_description"><?php _e( 'Description', $this->plugin_name ) ?></label>
                            </th>
                            <td>
                                    <textarea id="description"
                                              name="description"
                                              rows="5"
                                              class="regular-text"
                                              placeholder="<?php _e( 'Description', $this->plugin_name ) ?>"
                                    ><?php echo esc_html( wp_unslash( $item['description'] ) ) ?></textarea>
                            </td>
                        </tr>
                        <tr class="form-field">
                            <th scope="row">
                                <label for="link_categories"><?php _e( 'Category', $this->plugin_name ) ?></label>
                            </th>
                            <td>
								<?php
								if ( $item_categories ) {
									$current_categories = isset( $item['categories'] ) ? explode( ',', $item['categories'] ) : [];
									foreach ( $item_categories as $category ) {
										?>
                                        <p>
                                            <input type="checkbox"
                                                   id="category-<?php echo esc_attr( $category->id ) ?>"
                                                   name="categories[]"
                                                   value="<?php echo esc_attr( $category->id ) ?>"
												<?php
												if ( $current_categories ) {
													checked( 1, in_array( $category->id, $current_categories ) );
												}
												?>
                                            />
                                            <label for="category-<?php echo esc_attr( $category->id ) ?>"><?php echo esc_attr( wp_unslash( $category->title ) ) ?></label>
                                        </p>
										<?php
									}
								} else {
									?>
                                    <label><?php _e( 'No categories have been created yet', $this->plugin_name ) ?></label>
								<?php } ?>
                            </td>
                        </tr>
                        </tbody>
                    </table>

					<?php do_action( 'clickwhale_link_edit_fields' ); ?>

                    <input type="hidden" id="created_at" name="created_at"
                           value="<?php echo esc_attr( $item['created_at'] ) ?>">
                    <input type="hidden" id="updated_at" name="updated_at" value="">

                    <input type="submit" value="<?php _e( 'Save', $this->plugin_name ) ?>" id="submit"
                           class="button-primary"
                           name="submit">
                </div>
            </div>
        </div>
    </form>
</div>