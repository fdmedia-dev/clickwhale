<?php
// if limit reached
if ( ! isset( $_GET['id'] ) && ClickwhaleLinkpagesHelper::get_linkpages_count() >= ClickwhaleLinkpagesHelper::get_limit() ) {
	wp_die( __( 'You have reached the page limit', $this->plugin_name ) );
}

// init class
$linkpage_edit = new Clickwhale_Linkpage_Edit();
$linkpage_edit->init();

// ITEM
$defaults        = $linkpage_edit->get_defaults();
$item            = $linkpage_edit->get_item( $_REQUEST );
$post_type_links = $linkpage_edit->get_post_types();

// STYLES
$styles = isset( $item['styles'] ) && $item['styles'] !== '' ? maybe_unserialize( $item['styles'] ) : $defaults['styles'];
$social = isset( $item['social'] ) && $item['social'] !== '' ? maybe_unserialize( $item['social'] ) : $defaults['styles'];

// LP IMAGE
$logo_id = $item['logo'] ?? '';

// LP SEO ROBOTS
$seoTitle         = $social['seo']['title'] ?? $item['title'];
$seoDescription   = $social['seo']['description'] ?? get_bloginfo( 'description' );
$robots           = array(
	'noindex'      => array(
		'title'       => __( "No Index", $this->plugin_name ),
		'description' => __( "Do not show this page in search results. If you don't specify this rule, the page may be indexed and shown in search results.",
			$this->plugin_name )
	),
	'nofollow'     => array(
		'title'       => __( "No Follow", $this->plugin_name ),
		'description' => __( "Do not follow the links on this page. If you don't specify this rule, search engine may use the links on the page to discover those linked pages",
			$this->plugin_name )
	),
	'noarchive'    => array(
		'title'       => __( "No Archive", $this->plugin_name ),
		'description' => __( "Do not show a cached link in search results.", $this->plugin_name ),
	),
	'nosnippet'    => array(
		'title'       => __( "No Snippet", $this->plugin_name ),
		'description' => __( "Do not show a text snippet or video preview in the search results for this page.",
			$this->plugin_name ),
	),
	'noimageindex' => array(
		'title'       => __( "No Image Index", $this->plugin_name ),
		'description' => __( "Do not index images on this page.", $this->plugin_name ),
	)
);
$seoOGTitle       = $social['seo']['ogtitle'] ?? '';
$seoOGDescription = $social['seo']['ogdescription'] ?? '';
$seoOGImageId     = $social['seo']['ogimage'] ?? '';

$seoOGPreviewVendorURL = 'https://www.opengraph.xyz/url/';
$seoOGLPURL            = get_bloginfo( 'url' ) . '/' . esc_attr( $item['slug'] ) . '/';

// transient
$message = get_transient( 'linkpage-' . $item['id'] );

// BANNER
do_action( 'clickwhale_admin_banner' );
?>

<div class="wrap">

	<?php
	echo ClickwhaleHepler::render_heading(
		array(
			'name'         => __( 'Link Page', $this->plugin_name ),
			'is_edit'      => isset( $item['id'] ) && $item['id'] !== 0,
			'link_to_list' => 'clickwhale-linkpages',
			'link_to_edit' => 'clickwhale-edit-linkpage',
			'link_to_view' => esc_url( trailingslashit( get_bloginfo( 'url' ) ) . $item['slug'] ) . '/',
			'is_limit'     => ClickwhaleLinkpagesHelper::get_linkpages_count() >= ClickwhaleLinkpagesHelper::get_limit()
		)
	);
	if ( ! empty( $message ) ) { ?>
		<?php if ( $message === 'linkpage_added' ) { ?>
            <div id="message" class="updated"><p><?php _e( 'Link Page was successfully saved', $this->plugin_name ) ?></p>
            </div>
		<?php } ?>
		<?php if ( $message === 'linkpage_updated' ) { ?>
            <div id="message" class="updated"><p><?php _e( 'Link Page was successfully updated', $this->plugin_name ) ?></p>
            </div>
		<?php } ?>
		<?php delete_transient( 'linkpage-' . $item['id'] ); ?>
	<?php } ?>

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
                    <li><a href="#lp-tab-seo"
                           class=""><?php _e( 'SEO', 'clickwhale' ); ?></a></li>
                    <!--li><a href="#lp-tab-social"
                                   class=""><?php _e( 'Social', 'clickwhale' ); ?></a></li-->
                </ul>

                <div id="lp-tab-settings">
                    <table style="width: 100%;" class="form-table">
                        <caption hidden>Linkpage Main Settings</caption>
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
                                    ><?php echo wp_kses( wp_unslash( $item['description'] ),
		                                    wp_kses_allowed_html( 'post' ) ) ?></textarea>
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
									<?php $url = __( 'URL Preview',
											$this->plugin_name ) . ': ' . get_bloginfo( 'url' ) . '/'; ?>
									<?php echo esc_html( $url ) ?><span><?php echo esc_html( $item['slug'] ) ?></span>/
                                </p>
                            </td>
                        </tr>
                        <tr class="form-field">
                            <th scope="row">
                                <label for="logo"><?php _e( 'Page Logo', $this->plugin_name ) ?></label>
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
                                <label for="links"><?php _e( 'Add Link to Page', $this->plugin_name ) ?></label>
                            </th>
                            <td>
                                <div class="add-links-wrap">
                                    <div class="add-links-type-wrap">
                                        <select id="add-links-type" class="add-links-type regular-text">
                                            <option></option>
                                            <option value="cw_link"><?php _e( 'ClickWhale Link',
													$this->plugin_name ) ?></option>
											<?php foreach ( $post_type_links as $name => $singular_name ) { ?>
                                                <option value="<?php echo esc_attr( $name ); ?>"><?php echo esc_attr( $singular_name ); ?></option>
											<?php } ?>
                                            <option value="cw_custom"><?php _e( 'Custom Link',
													$this->plugin_name ) ?></option>
                                        </select>
                                    </div>
                                    <div class="add-links-inputs-wrap">
                                        <div id="links-post-type" class="">
                                            <select name="add-links-select" id="add-links-select" class="regular-text"
                                                    disabled>
                                                <option></option>
                                            </select>
                                        </div>
                                        <div id="links-cw-custom" class="hidden">
                                            <div class="custom-links-action-wrap">
                                                <input type="text"
                                                       name="custom-link-title"
                                                       placeholder="Link Title"
                                                       value=""
                                                       class="regular-text">
                                                <input type="url"
                                                       name="custom-link-url"
                                                       placeholder="Link Url"
                                                       value=""
                                                       class="regular-text">
                                            </div>

                                        </div>
                                    </div>
                                    <div class="add-links-button-wrap">
                                        <button type="button" class="button" id="add-links-button" disabled>
											<?php _e( 'Add Link to Page', $this->plugin_name ) ?>
                                        </button>
                                    </div>
                                </div>
                                <div class="links-list-wrap">

									<?php
									$links = maybe_unserialize( $item['links'] );
									if ( $links ) {
										foreach ( $links as $link ) {
											echo $linkpage_edit->render_link( $link );
										}
									}
									?>

                                </div>
								<?php if ( $links && count( $links ) >= ClickwhaleLinkpagesHelper::get_links_limit() ) { ?>
                                    <div class="links-info"><?php printf( 'Currently, a maximum of %d links can be added',
											ClickwhaleLinkpagesHelper::get_links_limit() ); ?></div>
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
                        <caption hidden>Link Page Customization Options</caption>
                        <tbody>

						<?php // PAGE BACKGROUND ?>
                        <tr class="form-field">
                            <th scope="row">
                                <label for="styles[bg_color]"><?php _e( 'Site Background',
										$this->plugin_name ); ?></label>
                            </th>
                            <td>
                                <input name="styles[bg_color]"
                                       class="cw-color-control"
                                       type="text"
                                       value="<?php echo esc_attr( $styles['bg_color'] ) ?>"/>
                                <p class="description"><?php _e( 'Set page background color',
										$this->plugin_name ) ?></p>
                            </td>
                        </tr>

						<?php // PAGE TEXT COLOR ?>
                        <tr class="form-field">
                            <th scope="row">
                                <label for="styles[text_color]"><?php _e( 'Page Text Color',
										$this->plugin_name ); ?></label>
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
                        <caption hidden>Link Page Links Customization Options</caption>
                        <tbody>

						<?php // LINK BACKGROUND ?>
                        <tr class="form-field">
                            <th scope="row">
                                <label for="styles[link_bg_color]"><?php _e( 'Background Color',
										$this->plugin_name ); ?></label>
                            </th>
                            <td>
                                <input name="styles[link_bg_color]"
                                       class="cw-color-control"
                                       type="text"
                                       value="<?php echo esc_attr( $styles['link_bg_color'] ) ?>"/>
                                <p class="description"><?php _e( 'Set link background color (normal state)',
										$this->plugin_name ) ?></p>
                            </td>
                        </tr>

						<?php // LINK BACKGROUND:HOVER ?>
                        <tr class="form-field">
                            <th scope="row">
                                <label for="styles[link_bg_color_hover]"><?php _e( 'Background Color (hover/active)',
										$this->plugin_name ); ?></label>
                            </th>
                            <td>
                                <input name="styles[link_bg_color_hover]"
                                       class="cw-color-control"
                                       type="text"
                                       value="<?php echo esc_attr( $styles['link_bg_color_hover'] ) ?>"/>
                                <p class="description"><?php _e( 'Set link background color (hover/active)',
										$this->plugin_name ) ?></p>
                            </td>
                        </tr>

						<?php // LINK TEXT COLOR ?>
                        <tr class="form-field">
                            <th scope="row">
                                <label for="styles[link_color]"><?php _e( 'Text Color', $this->plugin_name ); ?></label>
                            </th>
                            <td>
                                <input name="styles[link_color]"
                                       class="cw-color-control"
                                       type="text"
                                       value="<?php echo esc_attr( $styles['link_color'] ) ?>"/>
                                <p class="description"><?php _e( 'Set link text color (normal state)',
										$this->plugin_name ) ?></p>
                            </td>
                        </tr>
                        </tbody>

						<?php // LINK TEXT COLOR:HOVER ?>
                        <tr class="form-field">
                            <th scope="row">
                                <label for="styles[link_color_hover]"><?php _e( 'Text Color (hover/active)',
										$this->plugin_name ); ?></label>
                            </th>
                            <td>
                                <input name="styles[link_color_hover]"
                                       class="cw-color-control"
                                       type="text"
                                       value="<?php echo esc_attr( $styles['link_color_hover'] ) ?>"/>
                                <p class="description"><?php _e( 'Set link text color (hover/active)',
										$this->plugin_name ) ?></p>
                            </td>
                        </tr>
                        </tbody>
                    </table>

					<?php do_action( 'clickwhale_linkpage_style_fields', $item ); ?>
                </div>

                <div id="lp-tab-seo">
                    <table cellspacing="2" cellpadding="5" style="width: 100%;" class="form-table">
                        <caption hidden>Link Page SEO Options</caption>
                        <tbody>

                        <h2><?php _e( 'SEO Options', $this->plugin_name ); ?></h2>
                        <tr class="form-field">
                            <th scope="row">
                                <label for="socialSeoTitle"><?php _e( 'SEO Title', $this->plugin_name ); ?></label>
                            </th>
                            <td>
                                <input id="socialSeoTitle"
                                       name="social[seo][title]"
                                       type="text"
                                       value="<?php echo esc_attr( wp_unslash( $seoTitle ) ) ?>"
                                       size="40"
                                       class="regular-text"
                                       placeholder="">
                                <p class="description"><?php _e( 'Set page SEO title', $this->plugin_name ) ?></p>
                            </td>
                        </tr>
                        <tr class="form-field">
                            <th scope="row">
                                <label for="socialSeoDescription"><?php _e( 'SEO Description',
										$this->plugin_name ); ?></label>
                            </th>
                            <td>
                                <input id="socialSeoDescription"
                                       name="social[seo][description]"
                                       type="text"
                                       value="<?php echo esc_attr( wp_unslash( $seoDescription ) ) ?>"
                                       size="40"
                                       class="regular-text"
                                       placeholder="">
                                <p class="description"><?php _e( 'Set page SEO description', $this->plugin_name ) ?></p>
                            </td>
                        </tr>
                        <tr class="form-field">
                            <th scope="row">
                                <label>
									<?php _e( 'Robots Meta', $this->plugin_name ) ?>
                                </label>
                            </th>
                            <td>
								<?php if ( ! get_option( 'blog_public' ) || get_option( 'blog_public' ) === '0' ) { ?>
                                    <div class="links-info">
										<?php printf(
											__( 'Search engines are not allowed to index this site. See the option "Search engine visibility" in <a href="%1$s" target="_blank">reading settings!</a>',
												$this->plugin_name ),
											esc_url( admin_url( 'options-reading.php' ) )
										); ?>
                                    </div>
								<?php } ?>

								<?php
								if ( $robots ) {
									$current_robots =
										isset( $social['seo']['robots'] )
											? maybe_unserialize( $social['seo']['robots'] )
											: [];
									foreach ( $robots as $robotKey => $robotVal ) {
										?>
                                        <p>
                                            <input type="checkbox"
                                                   id="robots-<?php echo esc_attr( $robotKey ) ?>"
                                                   name="social[seo][robots][]"
                                                   value="<?php echo esc_attr( $robotKey ) ?>"
												<?php
												if ( $current_robots ) {
													checked( 1, in_array( $robotKey, $current_robots ) );
												}
												if ( ! get_option( 'blog_public' ) || get_option( 'blog_public' ) === '0' ) { ?>
                                                    disabled
												<?php } ?>
                                            />
                                            <label for="robots-<?php echo esc_attr( $robotKey ) ?>">
												<?php echo esc_attr( wp_unslash( $robotVal['title'] ) ) ?>
                                                <small>(<?php echo esc_attr( wp_unslash( $robotVal['description'] ) ) ?>
                                                    )</small>
                                            </label>
                                        </p>
										<?php
									}
								}
								?>
                            </td>
                        </tr>
                        </tbody>
                    </table>

                    <hr>

                    <h2><?php _e( 'Open Graph Options', $this->plugin_name ); ?></h2>
                    <table cellspacing="2" cellpadding="5" style="width: 100%;" class="form-table">
                        <caption hidden>Link Page Open Graph Options</caption>
                        <tbody>
                        <tr class="form-field">
                            <th scope="row">
                                <label for="socialOGTitle"><?php _e( 'Open Graph Title (Optional)',
										$this->plugin_name ); ?></label>
                            </th>
                            <td>
                                <input id="socialOGTitle"
                                       name="social[seo][ogtitle]"
                                       type="text"
                                       value="<?php echo esc_attr( wp_unslash( $seoOGTitle ) ) ?>"
                                       size="40"
                                       class="regular-text"
                                       placeholder="<?php echo esc_attr( wp_unslash( $seoTitle ) ) ?>">
                                <p class="description"><?php _e( 'The title of your page for social network. By default this is Link Page title.',
										$this->plugin_name ) ?></p>
                            </td>
                        </tr>
                        <tr class="form-field">
                            <th scope="row">
                                <label for="socialOGDescription"><?php _e( 'Open Graph Description (Optional)',
										$this->plugin_name ); ?></label>
                            </th>
                            <td>
                                <input id="socialOGDescription"
                                       name="social[seo][ogdescription]"
                                       type="text"
                                       value="<?php echo esc_attr( wp_unslash( $seoOGDescription ) ) ?>"
                                       size="40"
                                       class="regular-text"
                                       placeholder="<?php echo esc_attr( wp_unslash( $seoDescription ) ) ?>">
                                <p class="description"><?php _e( 'The description of your page for social network. By default this is SEO description.',
										$this->plugin_name ) ?></p>
                            </td>
                        </tr>
                        <tr class="form-field">
                            <th scope="row">
                                <label for="ogimage"><?php _e( 'Open Graph Image', $this->plugin_name ) ?></label>
                            </th>
                            <td>
                                <div class="logo-field">
									<?php
									if ( $seoOGImageId ) {
										$ogImage = wp_get_attachment_image_src( $seoOGImageId );
										?>
                                        <a href="#" class="linkpage-logo-upload">
                                            <img alt="linkpage-logo" src="<?php echo esc_url( $ogImage[0] ) ?>"/>
                                        </a>
                                        <a href="#" class="linkpage-logo-remove">Remove image</a>
                                        <input type="hidden" name="social[seo][ogimage]"
                                               value="<?php echo esc_attr( $seoOGImageId ); ?>">
									<?php } else { ?>
                                        <a href="#" class="linkpage-logo-upload">
											<?php _e( 'Upload image' ) ?>
                                        </a>
                                        <a href="#" class="linkpage-logo-remove" style="display:none">
											<?php _e( 'Remove image' ) ?>
                                        </a>
                                        <input type="hidden" name="social[seo][ogimage]" value="">
									<?php } ?>
                                </div>
                                <p><?php _e( 'Recommended image size 1200px * 630px', $this->plugin_name ); ?></p>
                            </td>
                        </tr>
                        <tr class="form-field">
                            <th scope="row">
                                <label for="ogimage"><?php _e( 'Open Graph Preview', $this->plugin_name ) ?></label>
                            </th>
                            <td>
                                <a class="button"
                                   id="opengraph-live-preview"
                                   href="<?php echo $seoOGPreviewVendorURL . $seoOGLPURL ?>"
                                   target="_blank"
                                   rel="noopener">
									<?php _e( 'View live preview', $this->plugin_name ) ?>
                                </a>
                                <p class="description"></p>
                            </td>
                        </tr>
                        </tbody>
                    </table>
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
