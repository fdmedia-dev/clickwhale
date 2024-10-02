<?php

use clickwhale\includes\helpers\{Helper, Linkpages_Helper};
use clickwhale\includes\content_templates\Clickwhale_Linkpage_Content_Templates;

Linkpages_Helper::get_limitation_error( $_GET['id'] );

$linkpage = clickwhale()->linkpage;
$item     = $linkpage->get_item( $_REQUEST );

// ITEM
$defaults        = $linkpage->get_defaults();
$post_type_links = Helper::get_post_types();
$tabs            = $linkpage->render_tabs();

// LINKS
$item['links'] = maybe_unserialize( $item['links'] );
$links         = $item['links'];
$links_limit  = $links && count( $links ) >= Linkpages_Helper::get_linkpage_links_limit();
$select        = $linkpage->get_select_values();

// STYLES
$item['styles'] = isset( $item['styles'] ) && $item['styles'] !== '' ? maybe_unserialize( $item['styles'] ) : $defaults['styles'];
$item['social'] = isset( $item['social'] ) && $item['social'] !== '' ? maybe_unserialize( $item['social'] ) : $defaults['social'];
$styles         = $item['styles'];
$social         = $item['social'];

// LP IMAGE
$logo_id = $item['logo'] ?? '';

// LP SEO ROBOTS
$seoTitle         = $social['seo']['title'] ?? $item['title'];
$seoDescription   = $social['seo']['description'] ?? get_bloginfo( 'description' );
$robots           = array(
	'noindex'      => array(
		'title'       => __( "No Index", CLICKWHALE_NAME ),
		'description' => __( "Do not show this page in search results. If you don't specify this rule, the page may be indexed and shown in search results.", CLICKWHALE_NAME )
	),
	'nofollow'     => array(
		'title'       => __( "No Follow", CLICKWHALE_NAME ),
		'description' => __( "Do not follow the links on this page. If you don't specify this rule, search engine may use the links on the page to discover those linked pages", CLICKWHALE_NAME )
	),
	'noarchive'    => array(
		'title'       => __( "No Archive", CLICKWHALE_NAME ),
		'description' => __( "Do not show a cached link in search results.", CLICKWHALE_NAME ),
	),
	'nosnippet'    => array(
		'title'       => __( "No Snippet", CLICKWHALE_NAME ),
		'description' => __( "Do not show a text snippet or video preview in the search results for this page.", CLICKWHALE_NAME ),
	),
	'noimageindex' => array(
		'title'       => __( "No Image Index", CLICKWHALE_NAME ),
		'description' => __( "Do not index images on this page.", CLICKWHALE_NAME ),
	)
);
$seoOGTitle       = $social['seo']['ogtitle'] ?? '';
$seoOGDescription = $social['seo']['ogdescription'] ?? '';
$seoOGImageId     = $social['seo']['ogimage'] ?? '';

$seoOGPreviewVendorURL = 'https://www.opengraph.xyz/url/';
$seoOGLPURL            = get_bloginfo( 'url' ) . '/' . esc_attr( $item['slug'] ) . '/';

// BANNER
do_action( 'clickwhale_admin_banner' );
?>
<div class="wrap">
	<?php
	echo Helper::render_heading(
		array(
			'name'         => __( 'Link Page', CLICKWHALE_NAME ),
			'is_edit'      => isset( $item['id'] ) && $item['id'] !== 0,
			'link_to_list' => CLICKWHALE_SLUG . '-linkpages',
			'link_to_add' => CLICKWHALE_SLUG . '-edit-linkpage',
			'link_to_view' => esc_url( trailingslashit( get_bloginfo( 'url' ) ) . $item['slug'] ) . '/',
			'is_limit'     => Linkpages_Helper::get_count() >= Linkpages_Helper::get_limit()
		)
	);

	$linkpage->show_message( $item['id'] );
	?>

    <?php do_action( 'clickwhale_admin_sidebar_begin' ); ?>

    <form id="form_edit_<?php echo $linkpage->instance_single ?>"
          class="clickwhale_form_edit"
          method="POST"
          action="<?php echo esc_attr( admin_url( 'admin-post.php' ) ); ?>">

        <input type="hidden" name="action" value="save_update_clickwhale_<?php echo $linkpage->instance_single ?>">
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
                    <h2><?php _e( 'General', CLICKWHALE_NAME ); ?></h2>
                    <table style="width: 100%;" class="form-table">
                        <caption hidden><?php _e( 'Link Page Main Settings', CLICKWHALE_NAME ); ?></caption>
                        <tbody>
                        <tr class="form-field">
                            <th scope="row">
                                <label for="title"><?php _e( 'Title', CLICKWHALE_NAME ) ?></label>
                            </th>
                            <td>
								<?php
								echo Helper::render_control(
									array(
										'control'     => 'input',
										'id'          => 'title',
										'name'        => 'title',
										'type'        => 'text',
										'value'       => esc_attr( wp_unslash( $item['title'] ) ),
										'placeholder' => __( 'Link Page Title', CLICKWHALE_NAME ),
										'required'    => true,
									)
								);
								?>
                                <p id="cw-title--description"></p>
                            </td>
                        </tr>

						<?php
						echo Helper::render_control(
							array(
								'row_label'   => __( 'Description', CLICKWHALE_NAME ),
								'control'     => 'textarea',
								'id'          => 'description',
								'name'        => 'description',
								'value'       => esc_html( wp_unslash( $item['description'] ) ),
								'placeholder' => __( 'Description', CLICKWHALE_NAME ),
							),
							true
						);
						?>

                        <tr class="form-field">
                            <th scope="row">
                                <label for="slug"><?php _e( 'Slug', CLICKWHALE_NAME ) ?></label>
                            </th>
                            <td>
								<?php
								echo Helper::render_control(
									array(
										'control'     => 'input',
										'id'          => 'cw-slug',
										'name'        => 'slug',
										'type'        => 'text',
										'value'       => esc_attr( $item['slug'] ),
										'placeholder' => __( 'Link Page Slug', CLICKWHALE_NAME ),
										'required'    => true,
									)
								);
								?>
                                <p id="cw-slug--description"></p>
                                <p id="cw-slug--text"
                                   class="code"
                                   title="<?php _e( 'Copy url', CLICKWHALE_NAME ) ?>"
                                >
									<?php $url = __( 'URL Preview', CLICKWHALE_NAME ) . ': ' . get_bloginfo( 'url' ) . '/'; ?>
									<?php echo esc_html( $url ) ?><span><?php echo esc_html( $item['slug'] ) ?></span>/
                                    <em class="dashicons dashicons-clipboard"></em>

                                </p>
                            </td>
                        </tr>
                        <tr class="form-field">
                            <th scope="row">
                                <label for="logo"><?php _e( 'Page Logo', CLICKWHALE_NAME ) ?></label>
                            </th>
                            <td>
                                <div class="logo-field image-field">
									<?php
									if ( $logo_id ) {
										$image = wp_get_attachment_image_src( $logo_id );
										?>
                                        <a href="#" class="linkpage-image-upload">
                                            <img alt="linkpage-logo" src="<?php echo esc_url( $image[0] ) ?>"/>
                                        </a>
                                        <a href="#" class="button linkpage-image-remove">Remove image</a>
                                        <input type="hidden" name="logo" value="<?php echo esc_attr( $logo_id ); ?>">
									<?php } else { ?>
                                        <a href="#" class="button linkpage-image-upload">
											<?php _e( 'Upload image', CLICKWHALE_NAME ) ?>
                                        </a>
                                        <a href="#" class="button linkpage-image-remove" style="display: none;">
											<?php _e( 'Remove image', CLICKWHALE_NAME ) ?>
                                        </a>
                                        <input type="hidden" name="logo" value="">
									<?php } ?>
                                </div>
                                <p><?php _e( 'Max logo size 275px * 275px', CLICKWHALE_NAME ); ?></p>
                            </td>
                        </tr>

                        <tr class="form-field">
                            <th scope="row">
                                <label for="meta__legals_menu_id"><?php _e( 'Legals', CLICKWHALE_NAME ) ?></label>
                            </th>
                            <td>
								<?php
								$legals = $linkpage->get_link_meta( $item['id'], 'legals_menu_id' );
								echo Helper::render_control(
									array(
										'row_label' => __( 'Legals', CLICKWHALE_NAME ),
										'control'   => 'select',
										'id'        => 'cw-legals',
										'name'      => 'meta__legals_menu_id',
										'value'     => $legals['meta_value'] ?? 0,
										'options'   => $linkpage->get_nav_menus()
									)
								);
								?>
                                <p class="description">
                                    <a href="<?php echo esc_url( admin_url( 'nav-menus.php' ) ) ?>"
                                       target="_blank"
                                       rel="noopener"><?php _e( 'Create a Legals Menu', CLICKWHALE_NAME ) ?></a>
                                </p>
                            </td>
                        </tr>

						<?php do_action( 'clickwhale_linkpage_after_settings_fields', $item ) ?>
                        </tbody>
                    </table>
                </div>

                <div id="lp-tab-contents">
                    <h2><?php _e( 'Contents', CLICKWHALE_NAME ); ?></h2>
                    <div class="contents-wrap">
                        <div class="contents-aside">
                            <div class="contents-aside--inner">
                                <div class="add-content-wrap">
									<?php
									$disabled = $links_limit ? 'disabled' : '';
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
                                                                        href="<?php echo CLICKWHALE_ADMIN_ASSETS_DIR ?>/images/feather-sprite.svg#<?php echo $options['icon'] ?>"></use>
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
									$template = new Clickwhale_Linkpage_Content_Templates();
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
							<?php if ( $links_limit ) { ?>
                                <div class="links-info">
									<?php echo Linkpages_Helper::get_links_limitation_notice(); ?>
									<?php echo Helper::get_pro_message(); ?>
                                </div>
							<?php } ?>
                        </div>
                    </div>
                </div>

                <div id="lp-tab-styles">
                    <h2><?php _e( 'Styles', CLICKWHALE_NAME ); ?></h2>
                    <table cellspacing="2" cellpadding="5" style="width: 100%;" class="form-table">
                        <caption hidden><?php _e( 'Link Page Customization Options', CLICKWHALE_NAME ); ?></caption>
                        <tbody>

						<?php do_action( 'clickwhale_linkpage_before_general_styles', $item ); ?>

						<?php // PAGE TEXT COLOR ?>
                        <tr class="form-field">
                            <th scope="row">
                                <label for="styles[text_color]"><?php _e( 'Page Text Color', CLICKWHALE_NAME ); ?></label>
                            </th>
                            <td>
                                <input name="styles[text_color]"
                                       class="cw-color-control"
                                       type="text"
                                       value="<?php echo esc_attr( $styles['text_color'] ) ?>"/>
                                <p class="description"><?php _e( 'Set page text color', CLICKWHALE_NAME ) ?></p>
                            </td>
                        </tr>

						<?php
						// PAGE BACKGROUND
						$background = apply_filters( 'clickwhale_linkpage_advanced_background', false );
						if ( ! $background ) {
							?>
                            <tr class="form-field">
                                <th scope="row">
                                    <label for="styles[bg_color]"><?php _e( 'Page Background', CLICKWHALE_NAME ); ?></label>
                                </th>
                                <td>
                                    <input name="styles[bg_color]"
                                           class="cw-color-control"
                                           type="text"
                                           value="<?php echo esc_attr( $styles['bg_color'] ) ?>"/>
                                    <p class="description"><?php _e( 'Set page background color', CLICKWHALE_NAME ) ?></p>
                                </td>
                            </tr>

							<?php
						}
						do_action( 'clickwhale_linkpage_after_general_styles', $item );
						?>

                        </tbody>
                    </table>

                    <hr>

                    <h2><?php _e( 'Links', CLICKWHALE_NAME ); ?></h2>
                    <table cellspacing="2" cellpadding="5" style="width: 100%;" class="form-table">
                        <caption hidden><?php _e( 'Link Page Links Customization Options', CLICKWHALE_NAME ); ?></caption>
                        <tbody>

						<?php // LINK BACKGROUND ?>
                        <tr class="form-field">
                            <th scope="row">
                                <label for="styles[link_bg_color]"><?php _e( 'Background Color',  CLICKWHALE_NAME ); ?></label>
                            </th>
                            <td>
                                <input name="styles[link_bg_color]"
                                       class="cw-color-control"
                                       type="text"
                                       value="<?php echo esc_attr( $styles['link_bg_color'] ) ?>"/>
                                <p class="description"><?php _e( 'Set link background color (normal state)',  CLICKWHALE_NAME ) ?></p>
                            </td>
                        </tr>

						<?php // LINK BACKGROUND:HOVER ?>
                        <tr class="form-field">
                            <th scope="row">
                                <label for="styles[link_bg_color_hover]"><?php _e( 'Background Color (hover/active)', CLICKWHALE_NAME ); ?></label>
                            </th>
                            <td>
                                <input name="styles[link_bg_color_hover]"
                                       class="cw-color-control"
                                       type="text"
                                       value="<?php echo esc_attr( $styles['link_bg_color_hover'] ) ?>"/>
                                <p class="description"><?php _e( 'Set link background color (hover/active)',
										CLICKWHALE_NAME ) ?></p>
                            </td>
                        </tr>

						<?php // LINK TEXT COLOR ?>
                        <tr class="form-field">
                            <th scope="row">
                                <label for="styles[link_color]"><?php _e( 'Text Color', CLICKWHALE_NAME ); ?></label>
                            </th>
                            <td>
                                <input name="styles[link_color]"
                                       class="cw-color-control"
                                       type="text"
                                       value="<?php echo esc_attr( $styles['link_color'] ) ?>"/>
                                <p class="description"><?php _e( 'Set link text color (normal state)',
										CLICKWHALE_NAME ) ?></p>
                            </td>
                        </tr>

						<?php // LINK TEXT COLOR:HOVER ?>
                        <tr class="form-field">
                            <th scope="row">
                                <label for="styles[link_color_hover]"><?php _e( 'Text Color (hover/active)',
										CLICKWHALE_NAME ); ?></label>
                            </th>
                            <td>
                                <input name="styles[link_color_hover]"
                                       class="cw-color-control"
                                       type="text"
                                       value="<?php echo esc_attr( $styles['link_color_hover'] ) ?>"/>
                                <p class="description"><?php _e( 'Set link text color (hover/active)',
										CLICKWHALE_NAME ) ?></p>
                            </td>
                        </tr>
                        </tbody>
                    </table>

					<?php do_action( 'clickwhale_linkpage_after_styles_tables', $item ) ?>
                </div>

                <div id="lp-tab-seo">
                    <h2><?php _e( 'SEO Options', CLICKWHALE_NAME ); ?></h2>
                    <table cellspacing="2" cellpadding="5" style="width: 100%;" class="form-table">
                        <caption hidden><?php _e( 'Link Page SEO Options', CLICKWHALE_NAME ); ?></caption>
                        <tbody>
						<?php
						echo Helper::render_control(
							array(
								'row_label'   => __( 'SEO Title', CLICKWHALE_NAME ),
								'control'     => 'input',
								'id'          => 'socialSeoTitle',
								'name'        => 'social[seo][title]',
								'type'        => 'text',
								'value'       => esc_attr( wp_unslash( $seoTitle ) ),
								'placeholder' => '',
								'description' => __( 'Set page SEO title', CLICKWHALE_NAME ),
							),
							true
						);
						echo Helper::render_control(
							array(
								'row_label'   => __( 'SEO Description', CLICKWHALE_NAME ),
								'control'     => 'input',
								'id'          => 'socialSeoDescription',
								'name'        => 'social[seo][description]',
								'type'        => 'text',
								'value'       => esc_attr( wp_unslash( $seoDescription ) ),
								'placeholder' => '',
								'description' => __( 'Set page SEO description', CLICKWHALE_NAME ),
							),
							true
						);
						?>

                        <tr class="form-field">
                            <th scope="row">
                                <label>
									<?php _e( 'Robots Meta', CLICKWHALE_NAME ) ?>
                                </label>
                            </th>
                            <td>
								<?php if ( ! get_option( 'blog_public' ) || get_option( 'blog_public' ) === '0' ) { ?>
                                    <div class="links-info">
										<?php printf(
											__( 'Search engines are not allowed to index this site. See the option "Search engine visibility" in <a href="%1$s" target="_blank">reading settings!</a>',
												CLICKWHALE_NAME ),
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

                    <h2><?php _e( 'Open Graph Options', CLICKWHALE_NAME ); ?></h2>
                    <table cellspacing="2" cellpadding="5" style="width: 100%;" class="form-table">
                        <caption hidden><?php _e( 'Link Page Open Graph Options', CLICKWHALE_NAME ); ?></caption>
                        <tbody>
						<?php
						echo Helper::render_control(
							array(
								'row_label'   => __( 'Open Graph Title (Optional)', CLICKWHALE_NAME ),
								'control'     => 'input',
								'id'          => 'socialOGTitle',
								'name'        => 'social[seo][ogtitle]',
								'type'        => 'text',
								'value'       => esc_attr( wp_unslash( $seoOGTitle ) ),
								'placeholder' => esc_attr( wp_unslash( $seoTitle ) ),
								'description' => __( 'The title of your page for social network. By default this is Link Page title.',
									CLICKWHALE_NAME ),
							),
							true
						);
						echo Helper::render_control(
							array(
								'row_label'   => __( 'Open Graph Description (Optional)', CLICKWHALE_NAME ),
								'control'     => 'input',
								'id'          => 'socialOGDescription',
								'name'        => 'social[seo][ogdescription]',
								'type'        => 'text',
								'value'       => esc_attr( wp_unslash( $seoOGDescription ) ),
								'placeholder' => esc_attr( wp_unslash( $seoDescription ) ),
								'description' => __( 'The description of your page for social network. By default this is SEO description.',
									CLICKWHALE_NAME ),
							),
							true
						);
						?>

                        <tr class="form-field">
                            <th scope="row">
                                <label for="ogimage"><?php _e( 'Open Graph Image', CLICKWHALE_NAME ) ?></label>
                            </th>
                            <td>
                                <div class="og-image-field image-field">
									<?php
									if ( $seoOGImageId ) {
										$ogImage = wp_get_attachment_image_src( $seoOGImageId );
										?>
                                        <a href="#" class="linkpage-image-upload">
                                            <img alt="linkpage-logo" src="<?php echo esc_url( $ogImage[0] ) ?>"/>
                                        </a>
                                        <a href="#" class="linkpage-image-remove">Remove image</a>
                                        <input type="hidden" name="social[seo][ogimage]"
                                               value="<?php echo esc_attr( $seoOGImageId ); ?>">
									<?php } else { ?>
                                        <a href="#" class="linkpage-image-upload">
											<?php _e( 'Upload image' ) ?>
                                        </a>
                                        <a href="#" class="linkpage-image-remove" style="display:none">
											<?php _e( 'Remove image' ) ?>
                                        </a>
                                        <input type="hidden" name="social[seo][ogimage]" value="">
									<?php } ?>
                                </div>
                                <p><?php _e( 'Recommended image size 1200px * 630px', CLICKWHALE_NAME ); ?></p>
                            </td>
                        </tr>
                        <tr class="form-field">
                            <th scope="row">
                                <label for="ogimage"><?php _e( 'Open Graph Preview', CLICKWHALE_NAME ) ?></label>
                            </th>
                            <td>
                                <a class="button"
                                   id="opengraph-live-preview"
                                   href="<?php echo $seoOGPreviewVendorURL . $seoOGLPURL ?>"
                                   target="_blank"
                                   rel="noopener">
									<?php _e( 'View live preview', CLICKWHALE_NAME ) ?>
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

            <input type="submit" value="<?php _e( 'Save', CLICKWHALE_NAME ) ?>" id="submit"
                   class="button-primary"
                   name="submit">

            <input type="button" value="<?php _e( 'Reset styles', CLICKWHALE_NAME ) ?>"
                   id="reset-styles"
                   class="button"
                   name="reset-styles"
                   style="display: none">

			<?php
			// icons picker
			$images = Clickwhale_Linkpage_Content_Templates::get_images();
			if ( $images ) {
				?>
                <div id="icon-picker--wrap" class="icon-picker--wrap">
                    <div>
                        <div class="icon-picker--search-wrap">
                            <input type="search" name="icon-picker--search">
                            <span>
                            <svg class="feather">
                                <use href="<?php echo CLICKWHALE_ADMIN_ASSETS_DIR ?>/images/feather-sprite.svg#search"></use>
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

    <?php do_action( 'clickwhale_admin_sidebar_end' ); ?>

</div>
