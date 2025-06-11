<?php

use clickwhale\includes\helpers\{Helper, Linkpages_Helper};
use clickwhale\includes\content_templates\Clickwhale_Linkpage_Content_Templates;

Linkpages_Helper::get_limitation_error( $_GET['id'] );

$linkpage = clickwhale()->linkpage;
$item = $linkpage->get_item( $_GET );
$item_id = intval( $item['id'] );
$slug = $item['slug'];
$linkpage_url = esc_url( trailingslashit( home_url( $slug ) ) );

// ITEM
$defaults = $linkpage->get_defaults();
$post_type_links = Helper::get_post_types();
$tabs = $linkpage->render_tabs();

// LINKS
$item['links'] = maybe_unserialize( $item['links'] );
$links = $item['links'];
$links_limit = $links && count( $links ) >= Linkpages_Helper::get_linkpage_links_limit();
$select = $linkpage->get_select_values();

// STYLES
$item['styles'] = isset( $item['styles'] ) && $item['styles'] !== '' ? maybe_unserialize( $item['styles'] ) : $defaults['styles'];
$item['social'] = isset( $item['social'] ) && $item['social'] !== '' ? maybe_unserialize( $item['social'] ) : $defaults['social'];
$styles = $item['styles'];
$social = $item['social'];

// LP IMAGE
$logo_id = esc_attr( $item['logo'] ?? '' );

// LP SEO ROBOTS
$seoTitle = esc_attr( $social['seo']['title'] ?? $item['title'] );
$seoDescription = esc_attr( $social['seo']['description'] ?? get_bloginfo( 'description' ) );
$robots = array(
    'noindex'      => array(
        'title'       => __( "No Index", 'clickwhale' ),
        'description' => __( "Do not show this page in search results. If you don't specify this rule, the page may be indexed and shown in search results.", 'clickwhale' )
    ),
    'nofollow'     => array(
        'title'       => __( "No Follow", 'clickwhale' ),
        'description' => __( "Do not follow the links on this page. If you don't specify this rule, search engine may use the links on the page to discover those linked pages", 'clickwhale' )
    ),
    'noarchive'    => array(
        'title'       => __( "No Archive", 'clickwhale' ),
        'description' => __( "Do not show a cached link in search results.", 'clickwhale' )
    ),
    'nosnippet'    => array(
        'title'       => __( "No Snippet", 'clickwhale' ),
        'description' => __( "Do not show a text snippet or video preview in the search results for this page.", 'clickwhale' )
    ),
    'noimageindex' => array(
        'title'       => __( "No Image Index", 'clickwhale' ),
        'description' => __( "Do not index images on this page.", 'clickwhale' )
    )
);
$seoOGTitle = esc_attr( $social['seo']['ogtitle'] ?? '' );
$seoOGDescription = esc_attr( $social['seo']['ogdescription'] ?? '' );
$seoOGImageId = esc_attr( $social['seo']['ogimage'] ?? '' );

$seoOGPreviewVendorURL = 'https://www.opengraph.xyz/url/';
$seoOGLPURL = $linkpage_url;

// BANNER
do_action( 'clickwhale_admin_banner' );
?>
<div class="wrap">
    <?php
    echo Helper::render_heading(
        array(
            'name' => __( 'Link Page', 'clickwhale' ),
            'is_edit' => $item_id !== 0,
            'link_to_list' => CLICKWHALE_SLUG . '-linkpages',
            'link_to_add' => CLICKWHALE_SLUG . '-edit-linkpage',
            'link_to_view' => $linkpage_url,
            'is_limit' => Linkpages_Helper::get_count() >= Linkpages_Helper::get_limit()
        )
    );

    $linkpage->show_message( $item_id );
    ?>

    <?php do_action( 'clickwhale_admin_sidebar_begin' ); ?>

    <form id="form_edit_<?php echo $linkpage->instance_single; ?>"
          class="clickwhale_form_edit"
          method="POST"
          action="<?php echo esc_attr( admin_url( 'admin-post.php' ) ); ?>"
    >
        <input type="hidden" name="action" value="save_update_clickwhale_<?php echo $linkpage->instance_single ?>" />
        <input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce( basename( __FILE__ ) ) ); ?>" />
        <input type="hidden" name="id" value="<?php echo $item_id; ?>" />

        <div id="post-body-content">
            <div id="clickwhale-tabs" class="clickwhale-tabs">
                <?php if ( $tabs ) { ?>
                    <ul>
                        <?php foreach ( $tabs as $tab ) { ?>
                            <li>
                                <a href="#lp-tab-<?php echo esc_attr( $tab['url'] ); ?>"><?php echo esc_html( $tab['name'] ); ?></a>
                            </li>
                        <?php } ?>
                    </ul>
                <?php } ?>

                <div id="lp-tab-settings">
                    <h2><?php _e( 'General', 'clickwhale' ); ?></h2>
                    <table style="width: 100%;" class="form-table">
                        <caption hidden><?php _e( 'Link Page Main Settings', 'clickwhale' ); ?></caption>
                        <tbody>
                        <tr class="form-field">
                            <th scope="row">
                                <label for="title"><?php _e( 'Title', 'clickwhale' ); ?></label>
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
                                        'placeholder' => __( 'Link Page Title', 'clickwhale' ),
                                        'required'    => true
                                    )
                                );
                                ?>
                                <p id="cw-title--description"></p>
                            </td>
                        </tr>

                        <?php
                        echo Helper::render_control(
                            array(
                                'row_label'   => __( 'Description', 'clickwhale' ),
                                'control'     => 'textarea',
                                'id'          => 'description',
                                'name'        => 'description',
                                'value'       => esc_html( wp_unslash( $item['description'] ) ),
                                'placeholder' => __( 'Description', 'clickwhale' )
                            ),
                            true
                        );
                        ?>

                        <tr class="form-field">
                            <th scope="row">
                                <label for="slug"><?php _e( 'Slug', 'clickwhale' ); ?></label>
                            </th>
                            <td>
                                <?php
                                echo Helper::render_control(
                                    array(
                                        'control'     => 'input',
                                        'id'          => 'cw-slug',
                                        'name'        => 'slug',
                                        'type'        => 'text',
                                        'value'       => esc_attr( $slug ),
                                        'placeholder' => __( 'Link Page Slug', 'clickwhale' ),
                                        'required'    => true
                                    )
                                );
                                ?>
                                <p id="cw-slug--description"></p>
                                <p id="cw-slug--text"
                                   class="code"
                                   title="<?php esc_attr_e( 'Copy url', 'clickwhale' ); ?>"
                                ><?php
                                    echo esc_html__( 'URL Preview', 'clickwhale' ) . ': ' . esc_html( trailingslashit( home_url() ) );
                                    ?><span><?php echo ( $slug ) ? esc_html( trailingslashit( $slug ) ) : ''; ?></span><svg class="feather"><use href="<?php echo CLICKWHALE_ADMIN_ASSETS_DIR; ?>/images/feather-sprite.svg#copy"></use></svg>
                                </p>
                            </td>
                        </tr>
                        <tr class="form-field">
                            <th scope="row">
                                <label for="logo"><?php _e( 'Page Logo', 'clickwhale' ); ?></label>
                            </th>
                            <td>
                                <div class="logo-field image-field">
                                    <?php
                                    $src = wp_get_attachment_image_url( $logo_id );

                                    if ( $logo_id && $src ) { ?>
                                        <a href="#"
                                           class="linkpage-image-upload"
                                        ><img alt="linkpage-logo" src="<?php echo esc_url( $src ); ?>" /></a>
                                        <a href="#"
                                           class="button linkpage-image-remove"
                                        ><?php _e( 'Remove image', 'clickwhale' ); ?></a>
                                        <input type="hidden" name="logo" value="<?php echo $logo_id; ?>" />
                                    <?php } else { ?>
                                        <a href="#"
                                           class="button linkpage-image-upload"
                                        ><?php _e( 'Upload image', 'clickwhale' ); ?></a>
                                        <a href="#"
                                           class="button linkpage-image-remove"
                                           style="display: none;"
                                        ><?php _e( 'Remove image', 'clickwhale' ); ?></a>
                                        <input type="hidden" name="logo" value="" />
                                    <?php } ?>
                                </div>
                                <p><?php echo __( 'Max logo size', 'clickwhale' ) . ' 275px * 275px'; ?></p>
                            </td>
                        </tr>

                        <tr class="form-field">
                            <th scope="row">
                                <label for="meta__legals_menu_id"><?php _e( 'Legals', 'clickwhale' ); ?></label>
                            </th>
                            <td>
                                <?php if ( current_theme_supports( 'menus' ) ) {
                                    $legals = $linkpage->get_link_meta( $item_id, 'legals_menu_id' );
                                    echo Helper::render_control(
                                        array(
                                            'row_label' => __( 'Legals', 'clickwhale' ),
                                            'control'   => 'select',
                                            'id'        => 'cw-legals',
                                            'name'      => 'meta__legals_menu_id',
                                            'value'     => $legals['meta_value'] ?? 0,
                                            'options'   => $linkpage->get_nav_menus()
                                        )
                                    );
                                    ?>
                                    <p class="description">
                                        <a href="<?php echo esc_url( admin_url( 'nav-menus.php' ) ); ?>"
                                           target="_blank"
                                           rel="noopener"><?php _e( 'Create a Legals Menu', 'clickwhale' ); ?></a>
                                    </p>
                                    <?php
                                } else {
                                    _e( 'Your theme does not support navigation menus or widgets.' );
                                }
                                ?>
                            </td>
                        </tr>

                        <?php do_action( 'clickwhale_linkpage_after_settings_fields', $item ); ?>
                        </tbody>
                    </table>
                </div>

                <div id="lp-tab-contents">
                    <h2><?php _e( 'Contents', 'clickwhale' ); ?></h2>
                    <div class="contents-wrap">
                        <div class="contents-aside">
                            <div class="contents-aside--inner">
                                <div class="add-content-wrap">
                                    <?php
                                    $disabled = $links_limit ? 'disabled' : '';
                                    foreach ( $select as $g => $group ) {
                                        ?>
                                        <div class="cw-content--group">
                                            <h3><?php echo esc_html( $group['label'] ); ?>
                                                (<?php echo esc_html( count( $group['options'] ) ); ?>)</h3>
                                            <div class="cw-content--items">
                                                <?php foreach ( $group['options'] as $value => $options ) { ?>
                                                    <div id="cw-content--<?php echo esc_attr( $value ); ?>"
                                                         class="cw-content--item <?php echo esc_attr( $disabled ); ?>"
                                                         data-content="<?php echo esc_attr( $value ); ?>"
                                                    ><?php
                                                        if ( isset( $options['icon'] ) && $options['icon'] ) {
                                                            ?>
                                                            <svg class="feather"><use href="<?php echo esc_url( CLICKWHALE_ADMIN_ASSETS_DIR . '/images/feather-sprite.svg#' . $options['icon'] ); ?>"></use></svg>
                                                            <?php
                                                        }
                                                        echo esc_html( $options['name'] ); ?>
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
                                            sanitize_text_field( $link['type'] ),
                                            true,
                                            false,
                                            array( 'data' => $link, 'linkpage_id' => $item_id )
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
                    <h2><?php _e( 'Styles', 'clickwhale' ); ?></h2>
                    <table cellspacing="2" cellpadding="5" style="width: 100%;" class="form-table">
                        <caption hidden><?php _e( 'Link Page Customization Options', 'clickwhale' ); ?></caption>
                        <tbody>

                        <?php do_action( 'clickwhale_linkpage_before_general_styles', $item ); ?>

                        <?php // PAGE TEXT COLOR ?>
                        <tr class="form-field">
                            <th scope="row">
                                <label for="styles[text_color]"><?php _e( 'Page Text Color', 'clickwhale' ); ?></label>
                            </th>
                            <td>
                                <input name="styles[text_color]"
                                       class="cw-color-control"
                                       type="text"
                                       value="<?php echo esc_attr( $styles['text_color'] ); ?>" />
                                <p class="description"><?php _e( 'Set page text color', 'clickwhale' ); ?></p>
                            </td>
                        </tr>

                        <?php
                        // PAGE BACKGROUND
                        $background = apply_filters( 'clickwhale_linkpage_advanced_background', false );
                        if ( ! $background ) {
                            ?>
                            <tr class="form-field">
                                <th scope="row">
                                    <label for="styles[bg_color]"><?php _e( 'Page Background', 'clickwhale' ); ?></label>
                                </th>
                                <td>
                                    <input name="styles[bg_color]"
                                           class="cw-color-control"
                                           type="text"
                                           value="<?php echo esc_attr( $styles['bg_color'] ); ?>" />
                                    <p class="description"><?php _e( 'Set page background color', 'clickwhale' ); ?></p>
                                </td>
                            </tr>

                            <?php
                        }
                        do_action( 'clickwhale_linkpage_after_general_styles', $item );
                        ?>

                        </tbody>
                    </table>

                    <hr>
                    <?php do_action( 'clickwhale_linkpage_after_general_styles_table', $item ); ?>

                    <h2><?php _e( 'Links', 'clickwhale' ); ?></h2>
                    <table cellspacing="2" cellpadding="5" style="width: 100%;" class="form-table">
                        <caption hidden><?php _e( 'Link Page Links Customization Options', 'clickwhale' ); ?></caption>
                        <tbody>

                        <?php // LINK BACKGROUND ?>
                        <tr class="form-field">
                            <th scope="row">
                                <label for="styles[link_bg_color]"><?php _e( 'Background Color', 'clickwhale' ); ?></label>
                            </th>
                            <td>
                                <input name="styles[link_bg_color]"
                                       class="cw-color-control"
                                       type="text"
                                       value="<?php echo esc_attr( $styles['link_bg_color'] ); ?>" />
                                <p class="description"><?php _e( 'Set link background color (normal state)', 'clickwhale' ); ?></p>
                            </td>
                        </tr>

                        <?php // LINK BACKGROUND:HOVER ?>
                        <tr class="form-field">
                            <th scope="row">
                                <label for="styles[link_bg_color_hover]"><?php _e( 'Background Color (hover/active)', 'clickwhale' ); ?></label>
                            </th>
                            <td>
                                <input name="styles[link_bg_color_hover]"
                                       class="cw-color-control"
                                       type="text"
                                       value="<?php echo esc_attr( $styles['link_bg_color_hover'] ); ?>" />
                                <p class="description"><?php _e( 'Set link background color (hover/active)', 'clickwhale' ); ?></p>
                            </td>
                        </tr>

                        <?php // LINK TEXT COLOR ?>
                        <tr class="form-field">
                            <th scope="row">
                                <label for="styles[link_color]"><?php _e( 'Text Color', 'clickwhale' ); ?></label>
                            </th>
                            <td>
                                <input name="styles[link_color]"
                                       class="cw-color-control"
                                       type="text"
                                       value="<?php echo esc_attr( $styles['link_color'] ); ?>" />
                                <p class="description"><?php _e( 'Set link text color (normal state)', 'clickwhale' ); ?></p>
                            </td>
                        </tr>

                        <?php // LINK TEXT COLOR:HOVER ?>
                        <tr class="form-field">
                            <th scope="row">
                                <label for="styles[link_color_hover]"><?php _e( 'Text Color (hover/active)', 'clickwhale' ); ?></label>
                            </th>
                            <td>
                                <input name="styles[link_color_hover]"
                                       class="cw-color-control"
                                       type="text"
                                       value="<?php echo esc_attr( $styles['link_color_hover'] ); ?>" />
                                <p class="description"><?php _e( 'Set link text color (hover/active)', 'clickwhale' ); ?></p>
                            </td>
                        </tr>
                        </tbody>
                    </table>

                    <?php do_action( 'clickwhale_linkpage_after_styles_tables', $item ); ?>
                </div>

                <div id="lp-tab-seo">
                    <h2><?php _e( 'SEO Options', 'clickwhale' ); ?></h2>
                    <table cellspacing="2" cellpadding="5" style="width: 100%;" class="form-table">
                        <caption hidden><?php _e( 'Link Page SEO Options', 'clickwhale' ); ?></caption>
                        <tbody>
                        <?php
                        echo Helper::render_control(
                            array(
                                'row_label'   => __( 'SEO Title', 'clickwhale' ),
                                'control'     => 'input',
                                'id'          => 'socialSeoTitle',
                                'name'        => 'social[seo][title]',
                                'type'        => 'text',
                                'value'       => wp_unslash( $seoTitle ),
                                'placeholder' => '',
                                'description' => __( 'Set page SEO title', 'clickwhale' )
                            ),
                            true
                        );
                        echo Helper::render_control(
                            array(
                                'row_label'   => __( 'SEO Description', 'clickwhale' ),
                                'control'     => 'input',
                                'id'          => 'socialSeoDescription',
                                'name'        => 'social[seo][description]',
                                'type'        => 'text',
                                'value'       => wp_unslash( $seoDescription ),
                                'placeholder' => '',
                                'description' => __( 'Set page SEO description', 'clickwhale' )
                            ),
                            true
                        );
                        ?>

                        <tr class="form-field">
                            <th scope="row">
                                <label><?php _e( 'Robots Meta', 'clickwhale' ); ?></label>
                            </th>
                            <td>
                                <?php if ( ! get_option( 'blog_public' ) || get_option( 'blog_public' ) === '0' ) { ?>
                                    <div class="links-info">
                                        <?php printf(
                                            __( 'Search engines are not allowed to index this site. See the option "Search engine visibility" in <a href="%1$s" target="_blank">reading settings!</a>', 'clickwhale' ),
                                            esc_url( admin_url( 'options-reading.php' ) )
                                        ); ?>
                                    </div>
                                <?php } ?>

                                <?php
                                if ( $robots ) {
                                    $current_robots =
                                        isset( $social['seo']['robots'] )
                                            ? maybe_unserialize( $social['seo']['robots'] )
                                            : array();
                                    foreach ( $robots as $robotKey => $robotVal ) {
                                        ?>
                                        <p>
                                            <input type="checkbox"
                                                   id="robots-<?php echo esc_attr( $robotKey ); ?>"
                                                   name="social[seo][robots][]"
                                                   value="<?php echo esc_attr( $robotKey ); ?>"
                                                <?php
                                                if ( $current_robots ) {
                                                    checked( 1, in_array( $robotKey, $current_robots ) );
                                                }
                                                if ( ! get_option( 'blog_public' ) || get_option( 'blog_public' ) === '0' ) { ?>
                                                    disabled
                                                <?php } ?>
                                            />
                                            <label for="robots-<?php echo esc_attr( $robotKey ); ?>">
                                                <?php echo esc_attr( wp_unslash( $robotVal['title'] ) ); ?>
                                                <small>(<?php echo esc_attr( wp_unslash( $robotVal['description'] ) ); ?>
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

                    <h2><?php _e( 'Open Graph Options', 'clickwhale' ); ?></h2>
                    <table cellspacing="2" cellpadding="5" style="width: 100%;" class="form-table">
                        <caption hidden><?php _e( 'Link Page Open Graph Options', 'clickwhale' ); ?></caption>
                        <tbody>
                        <?php
                        echo Helper::render_control(
                            array(
                                'row_label'   => __( 'Open Graph Title (Optional)', 'clickwhale' ),
                                'control'     => 'input',
                                'id'          => 'socialOGTitle',
                                'name'        => 'social[seo][ogtitle]',
                                'type'        => 'text',
                                'value'       => wp_unslash( $seoOGTitle ),
                                'placeholder' => wp_unslash( $seoTitle ),
                                'description' => __( 'The title of your page for social network. By default this is Link Page title.', 'clickwhale' )
                            ),
                            true
                        );
                        echo Helper::render_control(
                            array(
                                'row_label'   => __( 'Open Graph Description (Optional)', 'clickwhale' ),
                                'control'     => 'input',
                                'id'          => 'socialOGDescription',
                                'name'        => 'social[seo][ogdescription]',
                                'type'        => 'text',
                                'value'       => wp_unslash( $seoOGDescription ),
                                'placeholder' => wp_unslash( $seoDescription ),
                                'description' => __( 'The description of your page for social network. By default this is SEO description.', 'clickwhale' )
                            ),
                            true
                        );
                        ?>

                        <tr class="form-field">
                            <th scope="row">
                                <label for="ogimage"><?php _e( 'Open Graph Image', 'clickwhale' ); ?></label>
                            </th>
                            <td>
                                <div class="og-image-field image-field">
                                    <?php
                                    $ogImage = wp_get_attachment_image_url( $seoOGImageId );

                                    if ( $seoOGImageId && $ogImage ) { ?>
                                        <a href="#"
                                           class="linkpage-image-upload"
                                        ><img alt="linkpage-logo" src="<?php echo esc_url( $ogImage ); ?>" /></a>
                                        <a href="#"
                                           class="button linkpage-image-remove"
                                        ><?php _e( 'Remove image', 'clickwhale' ); ?></a>
                                        <input type="hidden" name="social[seo][ogimage]" value="<?php echo $seoOGImageId; ?>" />
                                    <?php } else { ?>
                                        <a href="#"
                                           class="button linkpage-image-upload"
                                        ><?php _e( 'Upload image', 'clickwhale' ); ?></a>
                                        <a href="#"
                                           class="button linkpage-image-remove"
                                           style="display:none"
                                        ><?php _e( 'Remove image', 'clickwhale' ); ?></a>
                                        <input type="hidden" name="social[seo][ogimage]" value="" />
                                    <?php } ?>
                                </div>
                                <p><?php _e( 'Recommended image size 1200px * 630px', 'clickwhale' ); ?></p>
                            </td>
                        </tr>
                        <tr class="form-field">
                            <th scope="row">
                                <label for="ogimage"><?php _e( 'Open Graph Preview', 'clickwhale' ); ?></label>
                            </th>
                            <td>
                                <a class="button"
                                   id="opengraph-live-preview"
                                   href="<?php echo esc_url( $seoOGPreviewVendorURL . rawurlencode( $seoOGLPURL ) ); ?>"
                                   target="_blank"
                                   rel="noopener"
                                ><?php _e( 'View live preview', 'clickwhale' ); ?></a>
                                <p class="description"></p>
                            </td>
                        </tr>

                        <?php do_action( 'clickwhale_linkpage_after_og_fields', $item ); ?>
                        </tbody>
                    </table>

                    <?php do_action( 'clickwhale_linkpage_after_seo_tables', $item ); ?>

                </div>

                <?php do_action( 'clickwhale_linkpage_after_tabs_content', $item ); ?>

            </div>

            <input type="hidden" id="created_at" name="created_at"
                   value="<?php echo esc_attr( $item['created_at'] ); ?>">

            <input type="submit" value="<?php _e( 'Save', 'clickwhale' ); ?>"
                   id="submit"
                   class="button-primary"
                   name="submit"
            />

            <input type="button"
                   value="<?php _e( 'Reset styles', 'clickwhale' ); ?>"
                   id="reset-styles"
                   class="button"
                   name="reset-styles"
                   style="display: none"
            />

            <?php
            // icons picker
            $images = Clickwhale_Linkpage_Content_Templates::get_images();
            if ( $images ) {
                ?>
                <div id="icon-picker--wrap" class="icon-picker--wrap">
                    <div>
                        <div class="icon-picker--search-wrap">
                            <input type="search" name="icon-picker--search" />
                            <span>
                            <svg class="feather">
                                <use href="<?php echo CLICKWHALE_ADMIN_ASSETS_DIR; ?>/images/feather-sprite.svg#search"></use>
                            </svg>
                        </span>
                        </div>
                        <div class="icon-picker--icons-wrap">
                            <?php foreach ( $images as $image ) { ?>
                                <button type="button"
                                        data-icon="<?php echo $image; ?>"
                                ><ion-icon name="<?php echo $image; ?>"></ion-icon></button>
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