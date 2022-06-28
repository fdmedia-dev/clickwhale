<?php
$link_edit       = new Clickwhale_Link_Edit();
$item            = $link_edit->get_item( $_REQUEST );
$item_categories = $link_edit->get_link_categories();
$message         = get_transient( 'link-' . $item['id'] );
?>

<div class="wrap">
    <h1 class="wp-heading-inline">
		<?php _e( 'Edit link', 'clickwhale' ) ?>
        <a class="page-title-action"
           href="<?php echo get_admin_url( get_current_blog_id(), 'admin.php?page=clickwhale' ); ?>"><?php _e( 'Back to list', 'clickwhale' ) ?></a>
        <a href="<?php echo get_admin_url( get_current_blog_id(), 'admin.php?page=clickwhale-edit-link' ); ?>"
           class="page-title-action"><?php _e( 'Add new', 'clickwhale' ) ?></a>
    </h1>

	<?php if ( ! empty( $message ) ) { ?>
		<?php if ( $message === 'link_added' ) { ?>
            <div id="message" class="updated"><p><?php _e( 'Item was successfully saved', 'clickwhale' ) ?></p></div>
		<?php } ?>
		<?php if ( $message === 'link_updated' ) { ?>
            <div id="message" class="updated"><p><?php _e( 'Item was successfully updated', 'clickwhale' ) ?></p></div>
		<?php } ?>
		<?php delete_transient( 'link-' . $item['id'] ); ?>
	<?php } ?>

    <form id="form_edit_link" method="POST" action="<?php echo esc_attr( admin_url( 'admin-post.php' ) ); ?>">
        <input type="hidden" name="action" value="save_update_link">
        <input type="hidden" name="nonce" value="<?php echo wp_create_nonce( basename( __FILE__ ) ) ?>"/>
        <input type="hidden" name="id" value="<?php echo $item['id'] ?>"/>

        <div class="metabox-holder" id="poststuff">
            <div id="post-body">
                <div id="post-body-content">
                    <table cellspacing="2" cellpadding="5" style="width: 100%;" class="form-table">
                        <tbody>
                        <tr class="form-field">
                            <th valign="top" scope="row">
                                <label for="link_title"><?php _e( 'Title', 'clickwhale' ) ?></label>
                            </th>
                            <td>
                                <input id="title"
                                       name="title"
                                       type="text"
                                       style="width: 95%"
                                       value="<?php echo esc_attr( $item['title'] ) ?>"
                                       size="50"
                                       class="code"
                                       placeholder="<?php _e( 'Link Title', 'clickwhale' ) ?>"
                                       required>
                            </td>
                        </tr>
                        <tr class="form-field">
                            <th valign="top" scope="row">
                                <label for="link_slug"><?php _e( 'Slug', 'clickwhale' ) ?></label>
                            </th>
                            <td>
                                <input id="slug"
                                       name="slug"
                                       type="text"
                                       style="width: 95%"
                                       value="<?php echo esc_attr( $item['slug'] ) ?>"
                                       size="50"
                                       class="code"
                                       placeholder="<?php _e( 'Link Slug without /link/', 'clickwhale' ) ?>"
                                       required>
                                <p id="slug__text">
									<?php echo 'URL Preview: ' . get_bloginfo( 'url' ) . '/link/<span>' . esc_attr( $item['slug'] ) . '</span>' ?>
                                </p>
                            </td>
                        </tr>
                        <tr class="form-field">
                            <th valign="top" scope="row">
                                <label for="link_url"><?php _e( 'Target URL', 'clickwhale' ) ?></label>
                            </th>
                            <td>
                                <input id="url"
                                       name="url"
                                       type="text"
                                       style="width: 95%"
                                       value="<?php echo esc_attr( $item['url'] ) ?>"
                                       size="50"
                                       class="code"
                                       placeholder="<?php _e( 'Link Target URL', 'clickwhale' ) ?>"
                                       required>
                            </td>
                        </tr>
                        <tr class="form-field">
                            <th valign="top" scope="row">
                                <label for="link_redirection"><?php _e( 'Redirection Type', 'clickwhale' ) ?></label>
                            </th>
                            <td>
                                <select name="redirection" id="redirection">
                                    <option value="301" <?php selected( $item['redirection'], 301 ); ?>>301 redirect:
                                        Moved permanently
                                    </option>
                                    <option value="302" <?php selected( $item['redirection'], 302 ); ?>>302 redirect:
                                        Found / Moved temporarily
                                    </option>
                                    <option value="303" <?php selected( $item['redirection'], 303 ); ?>>303 redirect:
                                        See Other
                                    </option>
                                    <option value="307" <?php selected( $item['redirection'], 307 ); ?>>307 redirect:
                                        Temporarily Redirect
                                    </option>
                                    <option value="308" <?php selected( $item['redirection'], 308 ); ?>>308 redirect:
                                        Permanent Redirect
                                    </option>
                                </select>
                            </td>
                        </tr>
                        <tr class="form-field">
                            <th valign="top" scope="row">
                                <label for="nofollow"><?php _e( 'Nofollow', 'clickwhale' ) ?></label>
                            </th>
                            <td>
                                <input type="checkbox"
                                       id="nofollow"
                                       name="nofollow"
                                       value="1"
									<?php
									if ( $item['id'] === 0 && isset( $global_options['nofollow'] ) ) {
										echo 'checked';
									} else {
										checked( $item['nofollow'], 1 );
									}
									?>
                                />
                                <label for="nofollow"><?php _e( 'Check to mark link as nofollow & noindex', 'clickwhale' ) ?></label>
                            </td>
                        </tr>
                        <tr class="form-field">
                            <th valign="top" scope="row">
                                <label for="sponsored"><?php _e( 'Sponsored', 'clickwhale' ) ?></label>
                            </th>
                            <td>
                                <input type="checkbox"
                                       id="sponsored"
                                       name="sponsored"
                                       value="1"
									<?php
									if ( $item['id'] === 0 && isset( $global_options['sponsored'] ) ) {
										echo 'checked';
									} else {
										checked( $item['sponsored'], 1 );
									}
									?>
                                />
                                <label for="sponsored"><?php _e( 'Check to mark link as sponsored', 'clickwhale' ) ?></label>
                            </td>
                        </tr>
                        <tr class="form-field">
                            <th valign="top" scope="row">
                                <label for="link_description"><?php _e( 'Description', 'clickwhale' ) ?></label>
                            </th>
                            <td>
                                    <textarea id="description"
                                              name="description"
                                              style="width: 95%"
                                              rows="5"
                                              class="code"
                                              placeholder="<?php _e( 'Description', 'clickwhale' ) ?>"
                                    ><?php echo esc_attr( $item['description'] ) ?></textarea>
                            </td>
                        </tr>
                        <tr class="form-field">
                            <th valign="top" scope="row">
                                <label for="link_categories"><?php _e( 'Category', 'clickwhale' ) ?></label>
                            </th>
                            <td>
								<?php
								if ( $item_categories ) {
									$current_categories = isset( $item['categories'] ) ? explode( ',', $item['categories'] ) : [];
									foreach ( $item_categories as $category ) {
										?>
                                        <p>
                                            <input type="checkbox"
                                                   id="category-<?php echo $category->id ?>"
                                                   name="categories[]"
                                                   value="<?php echo $category->id ?>"
												<?php
												if ( $current_categories ) {
													checked( in_array( $category->id, $current_categories ), 1 );
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

	                <?php do_action( 'link_edit_fields' ); ?>

                    <input type="hidden" id="created_at" name="created_at" value="<?php echo $item['created_at'] ?>">
                    <input type="hidden" id="updated_at" name="updated_at" value="">

                    <input type="submit" value="<?php _e( 'Save', 'clickwhale' ) ?>" id="submit" class="button-primary"
                           name="submit">
                </div>
            </div>
        </div>
    </form>
</div>