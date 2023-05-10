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
$tabs            = $linkpage_edit->render_tabs();

// LINKS
$item['links'] = maybe_unserialize( $item['links'] );
$links         = $item['links'];
$select        = $linkpage_edit->get_select_values();

// STYLES
$item['styles'] = isset( $item['styles'] ) && $item['styles'] !== '' ? maybe_unserialize( $item['styles'] ) : $defaults['styles'];
$item['social'] = isset( $item['social'] ) && $item['social'] !== '' ? maybe_unserialize( $item['social'] ) : $defaults['styles'];
$styles         = $item['styles'];
$social         = $item['social'];

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
			<div id="message" class="updated"><p><?php _e( 'Link Page was successfully saved',
						$this->plugin_name ) ?></p>
			</div>
		<?php } ?>
		<?php if ( $message === 'linkpage_updated' ) { ?>
			<div id="message" class="updated"><p><?php _e( 'Link Page was successfully updated',
						$this->plugin_name ) ?></p>
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
				<?php if ( $tabs ) { ?>
					<ul>
						<?php foreach ( $tabs as $tab ) { ?>
							<li>
								<a href="#lp-tab-<?php echo $tab['url'] ?>"><?php echo $tab['name'] ?></a>
							</li>
						<?php } ?>
					</ul>
				<?php } ?>

				<div id="lp-tab-settings">
					<table style="width: 100%;" class="form-table">
						<caption hidden>Linkpage Main Settings</caption>
						<tbody>

						<tr class="form-field">
							<th scope="row">
								<label for="title"><?php _e( 'Title', $this->plugin_name ) ?></label>
							</th>
							<td>
								<?php
								echo ClickwhaleHepler::render_control(
									array(
										'control'     => 'input',
										'id'          => 'title',
										'name'        => 'title',
										'type'        => 'text',
										'value'       => esc_attr( wp_unslash( $item['title'] ) ),
										'placeholder' => __( 'Link Page Title', $this->plugin_name ),
										'required'    => true,
									)
								);
								?>
								<p id="cw-title--description"></p>
							</td>
						</tr>

						<?php
						echo ClickwhaleHepler::render_control(
							array(
								'row_label'   => __( 'Description', $this->plugin_name ),
								'control'     => 'textarea',
								'id'          => 'description',
								'name'        => 'description',
								'value'       => esc_html( wp_unslash( $item['description'] ) ),
								'placeholder' => __( 'Description', $this->plugin_name ),
							),
							true
						);
						?>

						<tr class="form-field">
							<th scope="row">
								<label for="slug"><?php _e( 'Slug', $this->plugin_name ) ?></label>
							</th>
							<td>
								<?php
								echo ClickwhaleHepler::render_control(
									array(
										'control'     => 'input',
										'id'          => 'cw-slug',
										'name'        => 'slug',
										'type'        => 'text',
										'value'       => esc_attr( $item['slug'] ),
										'placeholder' => __( 'Link Page Slug', $this->plugin_name ),
										'required'    => true,
									)
								);
								?>
								<p id="cw-slug--description"></p>
								<p id="cw-slug--text"
								   class="code"
								   title="<?php _e( 'Copy url', $this->plugin_name ) ?>">
									<?php $url = __( 'URL Preview',
											$this->plugin_name ) . ': ' . get_bloginfo( 'url' ) . '/'; ?>
									<?php echo esc_html( $url ) ?><span><?php echo esc_html( $item['slug'] ) ?></span>/
									<em class="dashicons dashicons-clipboard"></em>

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
										<a href="#" class="button linkpage-logo-remove">Remove image</a>
										<input type="hidden" name="logo" value="<?php echo esc_attr( $logo_id ); ?>">
									<?php } else { ?>
										<a href="#" class="button linkpage-logo-upload">
											<?php _e( 'Upload image', 'clickwhale' ) ?>
										</a>
										<a href="#" class="button linkpage-logo-remove" style="display: none;">
											<?php _e( 'Remove image', 'clickwhale' ) ?>
										</a>
										<input type="hidden" name="logo" value="">
									<?php } ?>
								</div>
								<p><?php _e( 'Max logo size 275px * 275px', 'clickwhale' ); ?></p>
							</td>
						</tr>

						<tr class="form-field">
							<th scope="row">
								<label for="meta__legals_menu_id"><?php _e( 'Legals', $this->plugin_name ) ?></label>
							</th>
							<td>
								<?php
								$legals = $linkpage_edit->get_link_meta( $item['id'], 'legals_menu_id' );
								echo ClickwhaleHepler::render_control(
									array(
										'row_label' => __( 'Legals', $this->plugin_name ),
										'control'   => 'select',
										'id'        => 'cw-legals',
										'name'      => 'meta__legals_menu_id',
										'value'     => $legals['meta_value'] ?? 0,
										'options'   => $linkpage_edit->get_nav_menus()
									)
								);
								?>
								<p class="description">
									<a href="<?php echo esc_url( admin_url( 'nav-menus.php' ) ) ?>"
									   target="_blank"
									   rel="noopener"><?php _e( 'Create a Legals Menu', $this->plugin_name ) ?></a>
								</p>
							</td>
						</tr>

						<?php do_action( 'clickwhale_linkpage_after_settings_fields', $item ) ?>
						</tbody>
					</table>
				</div>

				<div id="lp-tab-contents">
					<div class="contents-wrap">
						<div class="contents-aside">
							<div class="contents-aside--inner">
								<div class="add-content-wrap">
									<?php
									$disabled = $links && count( $links ) >= ClickwhaleLinkpagesHelper::get_links_limit() ? 'disabled' : '';
									foreach ( $select as $g => $group ) {
										?>
										<div class="cw-content--group">
											<h3><?php echo $group['label'] ?>
												(<?php echo count( $group['options'] ) ?>)</h3>
											<div class="cw-content--items">
												<?php foreach ( $group['options'] as $value => $options ) { ?>
													<div id="cw-content--<?php echo $value ?>"
													     class="cw-content--item <?php echo $disabled ?>"
													     data-content="<?php echo $value ?>">
														<?php if ( isset( $options['icon'] ) && $options['icon'] ) { ?>
															<svg class="feather">
																<use
																	href="<?php echo ADMIN_IMAGES_DIR ?>/feather-sprite.svg#<?php echo $options['icon'] ?>"></use>
															</svg>
														<?php } ?>
														<?php echo $options['name'] ?>
													</div>
												<?php } ?>
											</div>
										</div>
									<?php } ?>
								</div>
							</div>
						</div>
						<div class="contents-main">
							<div class="links-list-wrap connectedSortable">
								<?php
								if ( $links ) {
									$template = new LinkpageContentTemplates();
									foreach ( $links as $link ) {
										$template->get_template(
											$link['type'],
											true,
											false,
											array( 'data' => $link, 'linkpage_id' => $item['id'] )
										);
									}
								}
								?>
							</div>
							<?php if ( $links && count( $links ) >= ClickwhaleLinkpagesHelper::get_links_limit() ) { ?>
								<div class="links-info">
									<?php printf(
										__( 'Currently, a maximum of %d links can be added', $this->plugin_name ),
										ClickwhaleLinkpagesHelper::get_links_limit()
									); ?>
								</div>
							<?php } ?>
						</div>
					</div>
				</div>

				<div id="lp-tab-styles">

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

					<?php do_action( 'clickwhale_linkpage_after_styles_tables', $item ) ?>
				</div>

				<div id="lp-tab-seo">
					<table cellspacing="2" cellpadding="5" style="width: 100%;" class="form-table">
						<caption hidden>Link Page SEO Options</caption>
						<tbody>

						<h2><?php _e( 'SEO Options', $this->plugin_name ); ?></h2>
						<?php
						echo ClickwhaleHepler::render_control(
							array(
								'row_label'   => __( 'SEO Title', $this->plugin_name ),
								'control'     => 'input',
								'id'          => 'socialSeoTitle',
								'name'        => 'social[seo][title]',
								'type'        => 'text',
								'value'       => esc_attr( wp_unslash( $seoTitle ) ),
								'placeholder' => '',
								'description' => __( 'Set page SEO title', $this->plugin_name ),
							),
							true
						);
						echo ClickwhaleHepler::render_control(
							array(
								'row_label'   => __( 'SEO Description', $this->plugin_name ),
								'control'     => 'input',
								'id'          => 'socialSeoDescription',
								'name'        => 'social[seo][description]',
								'type'        => 'text',
								'value'       => esc_attr( wp_unslash( $seoDescription ) ),
								'placeholder' => '',
								'description' => __( 'Set page SEO description', $this->plugin_name ),
							),
							true
						);
						?>

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
						<?php
						echo ClickwhaleHepler::render_control(
							array(
								'row_label'   => __( 'Open Graph Title (Optional)', $this->plugin_name ),
								'control'     => 'input',
								'id'          => 'socialOGTitle',
								'name'        => 'social[seo][ogtitle]',
								'type'        => 'text',
								'value'       => esc_attr( wp_unslash( $seoOGTitle ) ),
								'placeholder' => esc_attr( wp_unslash( $seoTitle ) ),
								'description' => __( 'The title of your page for social network. By default this is Link Page title.',
									$this->plugin_name ),
							),
							true
						);
						echo ClickwhaleHepler::render_control(
							array(
								'row_label'   => __( 'Open Graph Description (Optional)', $this->plugin_name ),
								'control'     => 'input',
								'id'          => 'socialOGDescription',
								'name'        => 'social[seo][ogdescription]',
								'type'        => 'text',
								'value'       => esc_attr( wp_unslash( $seoOGDescription ) ),
								'placeholder' => esc_attr( wp_unslash( $seoDescription ) ),
								'description' => __( 'The description of your page for social network. By default this is SEO description.',
									$this->plugin_name ),
							),
							true
						);
						?>

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

						<?php do_action( 'clickwhale_linkpage_after_og_fields', $item ) ?>
						</tbody>
					</table>

					<?php do_action( 'clickwhale_linkpage_after_seo_tables', $item ) ?>

				</div>

				<?php do_action( 'clickwhale_linkpage_after_tabs_content', $item ); ?>

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

			<?php
			// icons picker
			$images = LinkpageContentTemplates::get_images();
			if ( $images ) {
				?>
				<div id="icon-picker--wrap" class="icon-picker--wrap">
					<div>
						<div class="icon-picker--search-wrap">
							<input type="search" name="icon-picker--search">
							<span>
                            <svg class="feather">
                                <use href="<?php echo ADMIN_IMAGES_DIR ?>/feather-sprite.svg#search"></use>
                            </svg>
                        </span>
						</div>
						<div class="icon-picker--icons-wrap">
							<?php foreach ( $images as $image ) { ?>
								<button type="button" data-icon="<?php echo $image ?>">
									<ion-icon name="<?php echo $image ?>"></ion-icon>
								</button>
							<?php } ?>
						</div>
					</div>
				</div>
			<?php } ?>
			<!-- icons picker -->
		</div>
	</form>

</div>
