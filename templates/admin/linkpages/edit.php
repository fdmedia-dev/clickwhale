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

// LP Image
$logo_id = esc_attr( $item['logo'] ?? '' );

// LP Favicon
$favicon_id = esc_attr( $item['favicon'] ?? '' );

// Contents Tab
$item['links'] = maybe_unserialize( $item['links'] );
$links = $item['links'];
$links_limit = $links && count( $links ) >= Linkpages_Helper::get_linkpage_links_limit();
$select = $linkpage->get_select_values();

// Styles Tab
$item['styles'] = isset( $item['styles'] ) && $item['styles'] !== '' ? maybe_unserialize( $item['styles'] ) : $defaults['styles'];
$styles = $item['styles'];

// SEO and Social Tabs
$item['social'] = isset( $item['social'] ) && $item['social'] !== '' ? maybe_unserialize( $item['social'] ) : $defaults['social'];
$social = $item['social'];
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

// Banner
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
          action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
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
                    <h2><?php esc_html_e( 'General', 'clickwhale' ); ?></h2>
                    <table style="width: 100%;" class="form-table">
                        <caption hidden><?php esc_html_e( 'Link Page Main Settings', 'clickwhale' ); ?></caption>
                        <tbody>
                        <tr class="form-field">
                            <th scope="row">
                                <label for="title"><?php esc_html_e( 'Title', 'clickwhale' ); ?></label>
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
                                <label for="slug"><?php esc_html_e( 'Slug', 'clickwhale' ); ?></label>
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
                                <label for="logo"><?php esc_html_e( 'Page Logo', 'clickwhale' ); ?></label>
                            </th>
                            <td>
                                <div class="logo-field image-field">
                                    <?php
                                    $logo_src = wp_get_attachment_image_url( $logo_id );
                                    if ( $logo_id && $logo_src ) { ?>
                                        <a href="#"
                                           class="cw-linkpage-image-upload"
                                        ><img alt="linkpage-logo" src="<?php echo esc_url( $logo_src ); ?>" /></a>
                                        <a href="#"
                                           class="button cw-linkpage-image-remove"
                                        ><?php esc_html_e( 'Remove image', 'clickwhale' ); ?></a>
                                        <input type="hidden" name="logo" value="<?php echo $logo_id; ?>" />
                                    <?php } else { ?>
                                        <a href="#"
                                           class="button cw-linkpage-image-upload"
                                        ><?php esc_html_e( 'Upload image', 'clickwhale' ); ?></a>
                                        <a href="#"
                                           class="button cw-linkpage-image-remove"
                                           style="display: none;"
                                        ><?php esc_html_e( 'Remove image', 'clickwhale' ); ?></a>
                                        <input type="hidden" name="logo" value="" />
                                    <?php } ?>
                                </div>
                                <p><?php esc_html_e( 'Max logo size 275 × 275 pixels is recommended. Please use a ratio of 1:1', 'clickwhale' ); ?></p>
                            </td>
                        </tr>

                        <tr class="form-field">
                            <th scope="row">
                                <label for="logo"><?php esc_html_e( 'Site Icon', 'clickwhale' ); ?></label>
                            </th>
                            <td>
                                <div class="favicon-field image-field">
                                    <?php
                                    $favicon_src = $favicon_id ? wp_get_attachment_image_url( $favicon_id ) : '';
                                    if ( $favicon_id && $favicon_src ) { ?>
                                        <a href="#"
                                           class="cw-linkpage-image-upload"
                                        ><img alt="linkpage-favicon" src="<?php echo esc_url( $favicon_src ); ?>" /></a>
                                        <a href="#"
                                           class="button cw-linkpage-image-remove"
                                        ><?php esc_html_e( 'Remove Site Icon', 'clickwhale' ); ?></a>
                                        <input type="hidden" name="favicon" value="<?php echo $favicon_id; ?>" />
                                    <?php } else { ?>
                                        <a href="#"
                                           class="button cw-linkpage-image-upload"
                                        ><?php esc_html_e( 'Upload Site Icon', 'clickwhale' ); ?></a>
                                        <a href="#"
                                           class="button cw-linkpage-image-remove"
                                           style="display: none;"
                                        ><?php esc_html_e( 'Remove Site Icon', 'clickwhale' ); ?></a>
                                        <input type="hidden" name="favicon" value="" />
                                    <?php } ?>
                                </div>
                                <p><?php esc_html_e( 'Add custom Site Icon for the current Link Page. It replaces default one only on this page.', 'clickwhale' ); ?></p>
                                <p><?php esc_html_e( 'Site Icon should be square and at least 512 by 512 pixels.', 'clickwhale' ); ?></p>
                            </td>
                        </tr>

                        <tr class="form-field">
                            <th scope="row">
                                <label for="meta__legals_menu_id"><?php esc_html_e( 'Legals', 'clickwhale' ); ?></label>
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
                                           rel="noopener"><?php esc_html_e( 'Create a Legals Menu', 'clickwhale' ); ?></a>
                                    </p>
                                    <?php
                                } else {
                                    esc_html_e( 'Your theme does not support navigation menus or widgets.' );
                                }
                                ?>
                            </td>
                        </tr>

                        <?php do_action( 'clickwhale_linkpage_after_settings_fields', $item ); ?>
                        </tbody>
                    </table>
                </div>

                <div id="lp-tab-contents">
                    <h2><?php esc_html_e( 'Contents', 'clickwhale' ); ?></h2>
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
                            <div class="cw-links-list-wrap connectedSortable">
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
                                <div class="cw-links-info">
                                    <?php echo Linkpages_Helper::get_links_limitation_notice(); ?>
                                    <?php echo Helper::get_pro_message(); ?>
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                </div>

                <div id="lp-tab-styles">
                    <h2><?php esc_html_e( 'Styles', 'clickwhale' ); ?></h2>
                    <table cellspacing="2" cellpadding="5" style="width: 100%;" class="form-table">
                        <caption hidden><?php esc_html_e( 'Link Page Customization Options', 'clickwhale' ); ?></caption>
                        <tbody>

                        <?php do_action( 'clickwhale_linkpage_before_general_styles', $item ); ?>

                        <?php // PAGE TEXT COLOR ?>
                        <tr class="form-field">
                            <th scope="row">
                                <label for="styles[text_color]"><?php esc_html_e( 'Page Text Color', 'clickwhale' ); ?></label>
                            </th>
                            <td>
                                <input name="styles[text_color]"
                                       class="cw-color-control"
                                       type="text"
                                       value="<?php echo esc_attr( $styles['text_color'] ); ?>" />
                                <p class="description"><?php esc_html_e( 'Set page text color', 'clickwhale' ); ?></p>
                            </td>
                        </tr>

                        <?php
                        // PAGE BACKGROUND
                        $background = apply_filters( 'clickwhale_linkpage_advanced_background', false );
                        if ( ! $background ) {
                            ?>
                            <tr class="form-field">
                                <th scope="row">
                                    <label for="styles[bg_color]"><?php esc_html_e( 'Page Background', 'clickwhale' ); ?></label>
                                </th>
                                <td>
                                    <input name="styles[bg_color]"
                                           class="cw-color-control"
                                           type="text"
                                           value="<?php echo esc_attr( $styles['bg_color'] ); ?>" />
                                    <p class="description"><?php esc_html_e( 'Set page background color', 'clickwhale' ); ?></p>
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

                    <h2><?php esc_html_e( 'Links', 'clickwhale' ); ?></h2>
                    <table cellspacing="2" cellpadding="5" style="width: 100%;" class="form-table">
                        <caption hidden><?php esc_html_e( 'Link Page Links Customization Options', 'clickwhale' ); ?></caption>
                        <tbody>

                        <?php // LINK BACKGROUND ?>
                        <tr class="form-field">
                            <th scope="row">
                                <label for="styles[link_bg_color]"><?php esc_html_e( 'Background Color', 'clickwhale' ); ?></label>
                            </th>
                            <td>
                                <input name="styles[link_bg_color]"
                                       class="cw-color-control"
                                       type="text"
                                       value="<?php echo esc_attr( $styles['link_bg_color'] ); ?>" />
                                <p class="description"><?php esc_html_e( 'Set link background color (normal state)', 'clickwhale' ); ?></p>
                            </td>
                        </tr>

                        <?php // LINK BACKGROUND:HOVER ?>
                        <tr class="form-field">
                            <th scope="row">
                                <label for="styles[link_bg_color_hover]"><?php esc_html_e( 'Background Color (hover/active)', 'clickwhale' ); ?></label>
                            </th>
                            <td>
                                <input name="styles[link_bg_color_hover]"
                                       class="cw-color-control"
                                       type="text"
                                       value="<?php echo esc_attr( $styles['link_bg_color_hover'] ); ?>" />
                                <p class="description"><?php esc_html_e( 'Set link background color (hover/active)', 'clickwhale' ); ?></p>
                            </td>
                        </tr>

                        <?php // LINK TEXT COLOR ?>
                        <tr class="form-field">
                            <th scope="row">
                                <label for="styles[link_color]"><?php esc_html_e( 'Text Color', 'clickwhale' ); ?></label>
                            </th>
                            <td>
                                <input name="styles[link_color]"
                                       class="cw-color-control"
                                       type="text"
                                       value="<?php echo esc_attr( $styles['link_color'] ); ?>" />
                                <p class="description"><?php esc_html_e( 'Set link text color (normal state)', 'clickwhale' ); ?></p>
                            </td>
                        </tr>

                        <?php // LINK TEXT COLOR:HOVER ?>
                        <tr class="form-field">
                            <th scope="row">
                                <label for="styles[link_color_hover]"><?php esc_html_e( 'Text Color (hover/active)', 'clickwhale' ); ?></label>
                            </th>
                            <td>
                                <input name="styles[link_color_hover]"
                                       class="cw-color-control"
                                       type="text"
                                       value="<?php echo esc_attr( $styles['link_color_hover'] ); ?>" />
                                <p class="description"><?php esc_html_e( 'Set link text color (hover/active)', 'clickwhale' ); ?></p>
                            </td>
                        </tr>
                        </tbody>
                    </table>

                    <?php do_action( 'clickwhale_linkpage_after_styles_tables', $item ); ?>
                </div>

                <div id="lp-tab-seo">
                    <h2><?php esc_html_e( 'SEO Options', 'clickwhale' ); ?></h2>
                    <table cellspacing="2" cellpadding="5" style="width: 100%;" class="form-table">
                        <caption hidden><?php esc_html_e( 'Link Page SEO Options', 'clickwhale' ); ?></caption>
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
                                'description' => esc_html__( 'Set page SEO title', 'clickwhale' )
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
                                'description' => esc_html__( 'Set page SEO description', 'clickwhale' )
                            ),
                            true
                        );
                        ?>

                        <tr class="form-field">
                            <th scope="row">
                                <label><?php esc_html_e( 'Robots Meta', 'clickwhale' ); ?></label>
                            </th>
                            <td>
                                <?php
                                if ( ! get_option( 'blog_public' ) || get_option( 'blog_public' ) === '0' ) {
                                    ?>
                                    <div class="cw-links-info">
                                        <?php
                                        printf(
                                            wp_kses(
                                                __( 'Search engines are not allowed to index this site. See the option "Search engine visibility" in <a href="%1$s" target="_blank">reading settings!</a>', 'clickwhale' ),
                                                array(
                                                    'a' => array(
                                                        'href'   => array(),
                                                        'target' => array( '_blank' )
                                                    )
                                                )
                                            ),
                                            esc_url( admin_url( 'options-reading.php' ) )
                                        );
                                        ?>
                                    </div>
                                    <?php
                                }

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

                    <h2><?php esc_html_e( 'Open Graph Options', 'clickwhale' ); ?></h2>
                    <table cellspacing="2" cellpadding="5" style="width: 100%;" class="form-table">
                        <caption hidden><?php esc_html_e( 'Link Page Open Graph Options', 'clickwhale' ); ?></caption>
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
                                'description' => esc_html__( 'The title of your page for social network. By default this is Link Page title.', 'clickwhale' )
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
                                'description' => esc_html__( 'The description of your page for social network. By default this is SEO description.', 'clickwhale' )
                            ),
                            true
                        );
                        ?>

                        <tr class="form-field">
                            <th scope="row">
                                <label for="ogimage"><?php esc_html_e( 'Open Graph Image', 'clickwhale' ); ?></label>
                            </th>
                            <td>
                                <div class="og-image-field image-field">
                                    <?php
                                    $ogImage = wp_get_attachment_image_url( $seoOGImageId );

                                    if ( $seoOGImageId && $ogImage ) { ?>
                                        <a href="#"
                                           class="cw-linkpage-image-upload"
                                        ><img alt="linkpage-og-image" src="<?php echo esc_url( $ogImage ); ?>" /></a>
                                        <a href="#"
                                           class="button cw-linkpage-image-remove"
                                        ><?php esc_html_e( 'Remove image', 'clickwhale' ); ?></a>
                                        <input type="hidden" name="social[seo][ogimage]" value="<?php echo $seoOGImageId; ?>" />
                                    <?php } else { ?>
                                        <a href="#"
                                           class="button cw-linkpage-image-upload"
                                        ><?php esc_html_e( 'Upload image', 'clickwhale' ); ?></a>
                                        <a href="#"
                                           class="button cw-linkpage-image-remove"
                                           style="display:none"
                                        ><?php esc_html_e( 'Remove image', 'clickwhale' ); ?></a>
                                        <input type="hidden" name="social[seo][ogimage]" value="" />
                                    <?php } ?>
                                </div>
                                <p><?php esc_html_e( 'Recommended image size 1200 × 630 pixels.', 'clickwhale' ); ?></p>
                            </td>
                        </tr>
                        <tr class="form-field">
                            <th scope="row">
                                <label for="ogimage"><?php esc_html_e( 'Open Graph Preview', 'clickwhale' ); ?></label>
                            </th>
                            <td>
                                <a class="button"
                                   id="opengraph-live-preview"
                                   href="<?php echo esc_url( $seoOGPreviewVendorURL . rawurlencode( $seoOGLPURL ) ); ?>"
                                   target="_blank"
                                   rel="noopener"
                                ><?php esc_html_e( 'View live preview', 'clickwhale' ); ?></a>
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

            <input type="hidden"
                   id="created_at"
                   name="created_at"
                   value="<?php echo esc_attr( $item['created_at'] ); ?>"
            />

            <input type="submit"
                   value="<?php esc_attr_e( 'Save', 'clickwhale' ); ?>"
                   id="submit"
                   class="button-primary"
                   name="submit"
            />

            <input type="button"
                   value="<?php esc_attr_e( 'Reset styles', 'clickwhale' ); ?>"
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