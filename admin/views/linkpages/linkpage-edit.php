<?php
// if limit reached
if ( ! isset( $_GET['id'] ) && ClickwhaleLinkpagesHelper::get_linkpages_count() >= ClickwhaleLinkpagesHelper::get_limit() ) {
	wp_die( __( 'You have reached the page limit', $this->plugin_name ) );
}

// init class
$linkpage_edit = new Clickwhale_Linkpage_Edit();
$linkpage_edit->init();

// ITEM
$defaults       = $linkpage_edit->get_defaults();
$item           = $linkpage_edit->get_item( $_REQUEST );
$linkpage_links = $linkpage_edit->get_links();

// STYLES
$styles = isset( $item['styles'] ) && $item['styles'] !== '' ? maybe_unserialize( $item['styles'] ) : $defaults['styles'];

do_action( 'clickwhale_admin_banner' );
?>

<div class="wrap">
    <h1 class="wp-heading-inline">

		<?php
		if ( isset( $item['id'] ) && $item['id'] !== 0 ) {
			_e( 'Edit Link Page', $this->plugin_name );
		} else {
			_e( 'Add Link Page', $this->plugin_name );
		}
		?>

        <a class="page-title-action"
           href="<?php echo get_admin_url( get_current_blog_id(), 'admin.php?page=clickwhale-linkpages' ); ?>"><?php _e( 'Back to List', $this->plugin_name ) ?></a>

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

        <div id="post-body-content">
            <div id="clickwhale-tabs" class="clickwhale-tabs">
                <ul>
                    <li><a href="#lp-tab-settings"
                           class=""><?php _e( 'Settings', 'clickwhale' ); ?></a></li>
                    <li><a href="#lp-tab-colors"
                           class=""><?php _e( 'Colors', 'clickwhale' ); ?></a></li>
                    <!--li><a href="#lp-tab-social"
                                   class=""><?php _e( 'Social', 'clickwhale' ); ?></a></li-->
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
                                       value="<?php echo esc_attr( wp_unslash( $item['title'] ) ) ?>"
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
                                    ><?php echo wp_kses( wp_unslash( $item['description'] ), wp_kses_allowed_html( 'post' ) ) ?></textarea>
                            </td>
                        </tr>
                        <tr class="form-field">
                            <th scope="row">
                                <label for="slug"><?php _e( 'Slug', $this->plugin_name ) ?></label>
                            </th>
                            <td>
                                <input id="cw-slug"
                                       name="slug"
                                       type="text"
                                       value="<?php echo esc_attr( $item['slug'] ) ?>"
                                       size="50"
                                       class="regular-text"
                                       placeholder="<?php esc_attr( __( 'Linkpage Slug', $this->plugin_name ) ) ?>"
                                       required>
                                <p id="cw-slug--description"></p>
                                <p id="cw-slug--text">
									<?php $url = __( 'URL Preview', $this->plugin_name ) . ': ' . get_bloginfo( 'url' ) . '/'; ?>
									<?php echo esc_html( $url ) ?><span><?php echo esc_html( $item['slug'] ) ?></span>
                                </p>
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
                                        <input type="hidden" name="logo"
                                               value="<?php echo esc_attr( $logo_id ); ?>">
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
								<?php if ( $linkpage_links ) { ?>
                                    <select id="add-pagelink-select" class="regular-text">
										<?php foreach ( $linkpage_links as $linkpage_link ) { ?>
                                            <option value="<?php echo esc_attr( $linkpage_link['id'] ) ?>"
                                                    data-url="<?php echo esc_url( $linkpage_link['url'] ) ?>">
												<?php echo esc_html( wp_unslash( $linkpage_link['title'] ) ) ?>
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
														<?php echo esc_html( wp_unslash( $link_data['title'] ) ) ?>
                                                        <span><?php echo esc_url( $link_data['url'] ) ?></span>
                                                    </div>
                                                    <div class="linkpage-link--title">
                                                        <input type="text"
                                                               name="links[<?php echo esc_attr( $link['id'] ) ?>][title]"
                                                               value="<?php echo esc_html( wp_unslash( $link['title'] ) ) ?>"
                                                               placeholder="<?php _e( 'Link Title', 'clickwhale' ); ?>">
                                                    </div>
                                                    <div class="linkpage-row--remove"></div>
                                                </div>
												<?php
											}
										}
										?>

                                    </div>
									<?php if ( $links && count( $links ) >= ClickwhaleLinkpagesHelper::get_links_limit() ) { ?>
                                        <div class="links-info"><?php printf( 'Currently, a maximum of %d links can be added', ClickwhaleLinkpagesHelper::get_links_limit() ); ?></div>
									<?php } ?>
								<?php } else { ?>
                                    <div><?php _e( 'No links have been added yet' ); ?></div>
								<?php } ?>
                            </td>
                        </tr>
						<?php do_action( 'clickwhale_linkpage_edit_fields', $item ) ?>
                        </tbody>
                    </table>
                </div>
                <div id="lp-tab-colors">

                    <h2><?php _e( 'General', $this->plugin_name ); ?></h2>
                    <table cellspacing="2" cellpadding="5" style="width: 100%;" class="form-table">
                        <tbody>
                        <caption hidden>Link Page customization options</caption>

						<?php // PAGE BACKGROUND ?>
                        <tr class="form-field">
                            <th scope="row">
                                <label for="styles[bg_color]"><?php _e( 'Site background', $this->plugin_name ); ?></label>
                            </th>
                            <td>
                                <input name="styles[bg_color]"
                                       class="cw-color-control"
                                       type="text"
                                       value="<?php echo esc_attr( $styles['bg_color'] ) ?>"/>
                                <p class="description"><?php _e( 'Set page background color', $this->plugin_name ) ?></p>
                            </td>
                        </tr>

						<?php // PAGE TEXT COLOR ?>
                        <tr class="form-field">
                            <th scope="row">
                                <label for="styles[text_color]"><?php _e( 'Page text color', $this->plugin_name ); ?></label>
                            </th>
                            <td>
                                <input name="styles[text_color]"
                                       class="cw-color-control"
                                       type="text"
                                       value="<?php echo esc_attr( $styles['text_color'] ) ?>"/>
                                <p class="description"><?php _e( 'Set page text color', $this->plugin_name ) ?></p>
                            </td>
                        </tr>
                        </tbody>
                    </table>

                    <hr>

                    <h2><?php _e( 'Links', $this->plugin_name ); ?></h2>
                    <table cellspacing="2" cellpadding="5" style="width: 100%;" class="form-table">
                        <tbody>
                        <caption hidden>Link Page links customization options</caption>

						<?php // LINK BACKGROUND ?>
                        <tr class="form-field">
                            <th scope="row">
                                <label for="styles[link_bg_color]"><?php _e( 'Background color', $this->plugin_name ); ?></label>
                            </th>
                            <td>
                                <input name="styles[link_bg_color]"
                                       class="cw-color-control"
                                       type="text"
                                       value="<?php echo esc_attr( $styles['link_bg_color'] ) ?>"/>
                                <p class="description"><?php _e( 'Set link background color (normal state)', $this->plugin_name ) ?></p>
                            </td>
                        </tr>

						<?php // LINK BACKGROUND:HOVER ?>
                        <tr class="form-field">
                            <th scope="row">
                                <label for="styles[link_bg_color_hover]"><?php _e( 'Background color (hover/active)', $this->plugin_name ); ?></label>
                            </th>
                            <td>
                                <input name="styles[link_bg_color_hover]"
                                       class="cw-color-control"
                                       type="text"
                                       value="<?php echo esc_attr( $styles['link_bg_color_hover'] ) ?>"/>
                                <p class="description"><?php _e( 'Set link background color (hover/active)', $this->plugin_name ) ?></p>
                            </td>
                        </tr>

						<?php // LINK TEXT COLOR ?>
                        <tr class="form-field">
                            <th scope="row">
                                <label for="styles[link_color]"><?php _e( 'Text color', $this->plugin_name ); ?></label>
                            </th>
                            <td>
                                <input name="styles[link_color]"
                                       class="cw-color-control"
                                       type="text"
                                       value="<?php echo esc_attr( $styles['link_color'] ) ?>"/>
                                <p class="description"><?php _e( 'Set link text color (normal state)', $this->plugin_name ) ?></p>
                            </td>
                        </tr>
                        </tbody>

						<?php // LINK TEXT COLOR:HOVER ?>
                        <tr class="form-field">
                            <th scope="row">
                                <label for="styles[link_color_hover]"><?php _e( 'Text color (hover/active)', $this->plugin_name ); ?></label>
                            </th>
                            <td>
                                <input name="styles[link_color_hover]"
                                       class="cw-color-control"
                                       type="text"
                                       value="<?php echo esc_attr( $styles['link_color_hover'] ) ?>"/>
                                <p class="description"><?php _e( 'Set link text color (hover/active)', $this->plugin_name ) ?></p>
                            </td>
                        </tr>
                        </tbody>
                    </table>

					<?php do_action( 'clickwhale_linkpage_style_fields', $item ); ?>
                </div>
                <!--div id="lp-tab-social">
							<?php do_action( 'clickwhale_admin_pro_message' ); ?>
							<?php do_action( 'clickwhale_linkpage_social_fields', $item ); ?>
                        </div -->
            </div>

            <input type="hidden" id="created_at" name="created_at"
                   value="<?php echo esc_attr( $item['created_at'] ) ?>">

            <input type="submit" value="<?php _e( 'Save', $this->plugin_name ) ?>" id="submit"
                   class="button-primary"
                   name="submit">

            <input type="button" value="<?php _e( 'Reset colors', $this->plugin_name ) ?>"
                   id="reset-colors"
                   class="button"
                   name="reset-colors"
                   style="display: none">

        </div>
    </form>

</div>
