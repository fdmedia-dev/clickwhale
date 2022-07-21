<?php
if ( $_GET['id'] === '0' && $this->get_linkpages_count() >= $this->get_linkpages_limit() ) {
	wp_die( __( 'You have reached the page limit', $this->plugin_name ) );
}


$linkpage_edit = new Clickwhale_Linkpage_Edit();
$linkpage_edit->init();

$item  = $linkpage_edit->get_item( $_REQUEST );
$links = $linkpage_edit->get_links();

do_action( 'clickwhale_admin_banner' );
?>

<div class="wrap">
    <h1 class="wp-heading-inline">
		<?php _e( 'Edit Link Page', $this->plugin_name ); ?>
        <a class="page-title-action"
           href="<?php echo get_admin_url( get_current_blog_id(), 'admin.php?page=clickwhale-linkpages' ); ?>"><?php _e( 'Back to list', $this->plugin_name ) ?></a>

		<?php if ( $this->get_linkpages_count() < $this->get_linkpages_limit() ) { ?>
            <a href="<?php echo get_admin_url( get_current_blog_id(), 'admin.php?page=clickwhale-edit-linkpage' ); ?>"
               class="page-title-action"><?php _e( 'Add new', $this->plugin_name ) ?></a>
		<?php } ?>

    </h1>

    <form id="form_edit_link" method="POST" action="<?php echo esc_attr( admin_url( 'admin-post.php' ) ); ?>">
        <input type="hidden" name="action" value="save_update_linkpage">
        <input type="hidden" name="nonce" value="<?php echo wp_create_nonce( basename( __FILE__ ) ) ?>"/>
        <input type="hidden" name="id" value="<?php echo esc_attr( $item['id'] ) ?>"/>

        <div class="metabox-holder" id="poststuff">
            <div id="post-body">
                <div id="post-body-content">
                    <table cellspacing="2" cellpadding="5" style="width: 100%;" class="form-table">
                        <tbody>
                        <tr class="form-field">
                            <th valign="top" scope="row">
                                <label for="title"><?php _e( 'Title', $this->plugin_name ) ?></label>
                            </th>
                            <td>
                                <input id="title"
                                       name="title"
                                       type="text"
                                       value="<?php echo esc_attr( $item['title'] ) ?>"
                                       size="40"
                                       class="regular-text"
                                       placeholder="<?php _e( 'Link Page Title', $this->plugin_name ) ?>"
                                       required>
                            </td>
                        </tr>
                        <tr class="form-field">
                            <th valign="top" scope="row">
                                <label for="description"><?php _e( 'Description', $this->plugin_name ) ?></label>
                            </th>
                            <td>
                                    <textarea id="description"
                                              name="description"
                                              rows="5"
                                              class="regular-text"
                                              placeholder="<?php _e( 'Description', $this->plugin_name ) ?>"
                                    ><?php echo wp_kses( $item['description'], wp_kses_allowed_html( 'post' ) ) ?></textarea>
                            </td>
                        </tr>
                        <tr class="form-field">
                            <th valign="top" scope="row">
                                <label for="slug"><?php _e( 'Slug', $this->plugin_name ) ?></label>
                            </th>
                            <td>
                                <input id="slug"
                                       name="slug"
                                       type="text"
                                       value="<?php echo esc_attr( $item['slug'] ) ?>"
                                       size="50"
                                       class="regular-text"
                                       placeholder="<?php esc_attr( __( 'Linkpage Slug', $this->plugin_name ) ) ?>"
                                       required>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2">
                                <hr>
                            </td>
                        </tr>
                        <tr class="form-field">
                            <th valign="top" scope="row">
                                <label for="links"><?php _e( 'Links', $this->plugin_name ) ?></label>
                            </th>
                            <td>
                                <select id="add-pagelink-select" class="regular-text">
									<?php foreach ( $links as $link ) { ?>
                                        <option value="<?php echo esc_attr( $link['id'] ) ?>">
											<?php echo esc_html( $link['title'] ) ?>
                                        </option>
									<?php } ?>
                                </select>
                                <button type="button" class="button" id="add-pagelink-link">
									<?php _e( 'Add link to page', $this->plugin_name ) ?>
                                </button>

                                <div class="linkpage-wrap">

									<?php
									$links = maybe_unserialize( $item['links'] );
									if ( $links ) {
										foreach ( $links as $link ) {
											$link_data = $linkpage_edit->get_link( $link['id'] );
											?>
                                            <div class="linkpage-row">
                                                <input type="hidden"
                                                       name="links[<?php echo esc_attr( $link['id'] ) ?>][id]"
                                                       value="<?php echo esc_attr( $link['id'] ) ?>">
                                                <div class="linkpage-row--drag"></div>
                                                <div class="linkpage-link"><?php echo esc_html( $link_data['title'] ) ?></div>
                                                <div class="linkpage-link--title">
                                                    <input type="text"
                                                           name="links[<?php echo esc_attr( $link['id'] ) ?>][title]"
                                                           value="<?php echo esc_html( $link['title'] ) ?>"
                                                           placeholder="<?php _e( 'Link Title', 'clickwhale' ); ?>">
                                                </div>
                                                <div class="linkpage-row--remove"></div>
                                            </div>
											<?php
										}
									}
									?>

                                </div>
                            </td>
                        </tr>

                        </tbody>
                    </table>

					<?php do_action( 'linkpage_edit_fields' ) ?>

                    <input type="hidden" id="created_at" name="created_at"
                           value="<?php echo esc_attr( $item['created_at'] ) ?>">

                    <input type="submit" value="<?php _e( 'Save', $this->plugin_name ) ?>" id="submit"
                           class="button-primary"
                           name="submit">

                </div>
            </div>
        </div>
    </form>

</div>
