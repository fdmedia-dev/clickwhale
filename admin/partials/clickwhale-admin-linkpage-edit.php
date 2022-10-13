<?php
// if limit reached
if ( ! isset( $_GET['id'] ) && ClickwhaleLinkpagesHelper::get_linkpages_count() >= ClickwhaleLinkpagesHelper::get_limit() ) {
	wp_die( __( 'You have reached the page limit', $this->plugin_name ) );
}

// init class
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

		<?php if ( ClickwhaleLinkpagesHelper::get_linkpages_count() < ClickwhaleLinkpagesHelper::get_limit() ) { ?>
            <a href="<?php echo get_admin_url( get_current_blog_id(), 'admin.php?page=clickwhale-edit-linkpage' ); ?>"
               class="page-title-action"><?php _e( 'Add new', $this->plugin_name ) ?></a>
		<?php } ?>

		<?php if ( isset( $item['slug'] ) && $item['slug'] !== '' ) { ?>
            <a href="<?php echo trailingslashit( get_bloginfo( 'url' ) ) . $item['slug'] ?>"
               target="_blank" rel="noopener"
               class="page-title-action"><?php _e( 'View Page', $this->plugin_name ) ?></a>
		<?php } ?>
    </h1>

    <form id="form_edit_linkpage" method="POST" action="<?php echo esc_attr( admin_url( 'admin-post.php' ) ); ?>">
        <input type="hidden" name="action" value="save_update_linkpage">
        <input type="hidden" name="nonce" value="<?php echo wp_create_nonce( basename( __FILE__ ) ) ?>"/>
        <input type="hidden" name="id" value="<?php echo esc_attr( $item['id'] ) ?>"/>

        <div class="metabox-holder" id="poststuff">
            <div id="post-body">
                <div id="post-body-content">
                    <div id="clickwhale-tabs" class="clickwhale-tabs">
                        <ul>
                            <li><a href="#lp-tab-settings"
                                   class=""><?php _e( 'Settings', 'clickwhale' ); ?></a></li>
                            <li><a href="#lp-tab-customization"
                                   class=""><?php _e( 'Customization', 'clickwhale' ); ?></a></li>
                            <li><a href="#lp-tab-social"
                                   class=""><?php _e( 'Social', 'clickwhale' ); ?></a></li>
                        </ul>
                        <div id="lp-tab-settings">
                            <table style="width: 100%;" class="form-table">
                                <caption hidden>Linkpage main settings</caption>
                                <tbody>
                                <tr class="form-field">
                                    <th scope="row">
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
                                    <th scope="row">
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
                                    <th scope="row">
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
                                        <p id="slug-description"></p>
                                    </td>
                                </tr>
								<?php $logo_id = isset( $item['logo'] ) ? $item['logo'] : ''; ?>
                                <tr class="form-field">
                                    <th scope="row">
                                        <label for="logo"><?php _e( 'Page logo', $this->plugin_name ) ?></label>
                                    </th>
                                    <td>
                                        <div class="logo-field">
											<?php
											if ( $logo_id ) {
												$image = wp_get_attachment_image_src( $logo_id );
												?>
                                                <a href="#" class="linkpage-logo-upload">
                                                    <img alt="linkpage-logo" src="<?php echo esc_url( $image[0] ) ?>"/>
                                                </a>
                                                <a href="#" class="linkpage-logo-remove">Remove image</a>
                                                <input type="hidden" name="logo" value="<?php esc_attr( $logo_id ); ?>">
											<?php } else { ?>
                                                <a href="#" class="linkpage-logo-upload">
													<?php _e( 'Upload image' ) ?>
                                                </a>
                                                <a href="#" class="linkpage-logo-remove" style="display:none">
													<?php _e( 'Remove image' ) ?>
                                                </a>
                                                <input type="hidden" name="logo" value="">
											<?php } ?>
                                        </div>
                                        <p><?php _e( 'Max logo size 275px * 275px', 'clickwhale' ); ?></p>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="2">
                                        <hr>
                                    </td>
                                </tr>
                                <tr class="form-field">
                                    <th scope="row">
                                        <label for="links"><?php _e( 'Links', $this->plugin_name ) ?></label>
                                    </th>
                                    <td>
                                        <select id="add-pagelink-select" class="regular-text">
											<?php foreach ( $links as $link ) { ?>
                                                <option value="<?php echo esc_attr( $link['id'] ) ?>"
                                                        data-url="<?php echo esc_url( $link['url'] ) ?>">
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
                                                        <div class="linkpage-link">
															<?php echo esc_html( $link_data['title'] ) ?>
                                                            <span><?php echo esc_url( $link_data['url'] ) ?></span>
                                                        </div>
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
								<?php do_action( 'clickwhale_linkpage_edit_fields', $item ) ?>
                                </tbody>
                            </table>
                        </div>
                        <div id="lp-tab-customization">
							<?php do_action( 'clickwhale_admin_pro_message' ); ?>
							<?php do_action( 'clickwhale_linkpage_style_fields', $item ); ?>
                        </div>
                        <div id="lp-tab-social">
							<?php do_action( 'clickwhale_admin_pro_message' ); ?>
							<?php do_action( 'clickwhale_linkpage_social_fields', $item ); ?>
                        </div>
                    </div>

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
