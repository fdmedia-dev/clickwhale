<?php

use Clickwhale\Helpers\{Helper, Linkpages_Helper};
use Clickwhale\ContentTemplates\Clickwhale_Linkpage_Content_Templates;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$clickwhale_get_id = (int) filter_input( INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT );
Linkpages_Helper::get_limitation_error( $clickwhale_get_id );

$clickwhale_linkpage = clickwhale()->linkpage;
$clickwhale_item = $clickwhale_linkpage->get_item( array( 'id' => $clickwhale_get_id ) );
$clickwhale_item_id = intval( $clickwhale_item['id'] );
$clickwhale_slug = $clickwhale_item['slug'];
$clickwhale_linkpage_url = esc_url( trailingslashit( home_url( $clickwhale_slug ) ) );

// ITEM
$clickwhale_defaults = $clickwhale_linkpage->get_defaults();
$clickwhale_post_type_links = Helper::get_post_types();
$tabs = $clickwhale_linkpage->render_tabs();

// LP Image
$clickwhale_logo_id = esc_attr( $clickwhale_item['logo'] ?? '' );

// LP Favicon
$clickwhale_favicon_id = esc_attr( $clickwhale_item['favicon'] ?? '' );

// Contents Tab
$clickwhale_item['links'] = maybe_unserialize( $clickwhale_item['links'] );
$clickwhale_links = $clickwhale_item['links'];
$clickwhale_links_limit = $clickwhale_links && count( $clickwhale_links ) >= Linkpages_Helper::get_linkpage_links_limit();
$clickwhale_select = $clickwhale_linkpage->get_select_values();

// Styles Tab
$clickwhale_item['styles'] = isset( $clickwhale_item['styles'] ) && $clickwhale_item['styles'] !== '' ? maybe_unserialize( $clickwhale_item['styles'] ) : $clickwhale_defaults['styles'];
$clickwhale_styles = $clickwhale_item['styles'];

// SEO and Social Tabs
$clickwhale_item['social'] = isset( $clickwhale_item['social'] ) && $clickwhale_item['social'] !== '' ? maybe_unserialize( $clickwhale_item['social'] ) : $clickwhale_defaults['social'];
$clickwhale_social = $clickwhale_item['social'];
$clickwhale_seoTitle = esc_attr( $clickwhale_social['seo']['title'] ?? $clickwhale_item['title'] );
$clickwhale_seoDescription = esc_attr( $clickwhale_social['seo']['description'] ?? get_bloginfo( 'description' ) );
$clickwhale_robots = array(
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
$clickwhale_seoOGTitle = esc_attr( $clickwhale_social['seo']['ogtitle'] ?? '' );
$clickwhale_seoOGDescription = esc_attr( $clickwhale_social['seo']['ogdescription'] ?? '' );
$clickwhale_seoOGImageId = esc_attr( $clickwhale_social['seo']['ogimage'] ?? '' );

$clickwhale_seoOGPreviewVendorURL = 'https://www.opengraph.xyz/url/';
$clickwhale_seoOGLPURL = $clickwhale_linkpage_url;

// Banner
do_action( 'clickwhale_admin_banner' );
?>
<div class="wrap">
    <?php
    echo wp_kses(
        Helper::render_heading(
            array(
                'name' => esc_html__( 'Link Page', 'clickwhale' ),
                'is_edit' => $clickwhale_item_id !== 0,
                'link_to_list' => esc_attr( CLICKWHALE_SLUG ) . '-linkpages',
                'link_to_add' => esc_attr( CLICKWHALE_SLUG ) . '-edit-linkpage',
                'link_to_view' => esc_url( $clickwhale_linkpage_url ),
                'is_limit' => Linkpages_Helper::get_count() >= Linkpages_Helper::get_limit()
            )
        ),
        Helper::get_allowed_tags()
    );

    $clickwhale_linkpage->show_message( $clickwhale_item_id );
    ?>

    <?php do_action( 'clickwhale_admin_sidebar_begin' ); ?>

    <form id="form_edit_<?php echo esc_attr( $clickwhale_linkpage->instance_single ); ?>"
          class="clickwhale_form_edit"
          method="POST"
          action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
    >
        <input type="hidden" name="action" value="save_update_clickwhale_<?php echo esc_attr( $clickwhale_linkpage->instance_single ); ?>" />
        <input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce( basename( __FILE__ ) ) ); ?>" />
        <input type="hidden" name="id" value="<?php echo intval( $clickwhale_item_id ); ?>" />

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
                                echo wp_kses(
                                    Helper::render_control(
                                        array(
                                            'control'     => 'input',
                                            'id'          => 'title',
                                            'name'        => 'title',
                                            'type'        => 'text',
                                            'value'       => esc_attr( wp_unslash( $clickwhale_item['title'] ) ),
                                            'placeholder' => esc_attr__( 'Link Page Title', 'clickwhale' ),
                                            'required'    => true
                                        )
                                    ),
                                    Helper::get_allowed_tags()
                                );
                                ?>
                                <p id="cw-title--description"></p>
                            </td>
                        </tr>

                        <?php
                        echo wp_kses(
                            Helper::render_control(
                                array(
                                    'row_label'   => esc_html__( 'Description', 'clickwhale' ),
                                    'control'     => 'textarea',
                                    'id'          => 'description',
                                    'name'        => 'description',
                                    'value'       => esc_attr( wp_unslash( $clickwhale_item['description'] ) ),
                                    'placeholder' => esc_attr__( 'Description', 'clickwhale' )
                                ),
                                true
                            ),
                            Helper::get_allowed_tags()
                        );
                        ?>

                        <tr class="form-field">
                            <th scope="row">
                                <label for="slug"><?php esc_html_e( 'Slug', 'clickwhale' ); ?></label>
                            </th>
                            <td>
                                <?php
                                echo wp_kses(
                                    Helper::render_control(
                                        array(
                                            'control'     => 'input',
                                            'id'          => 'cw-slug',
                                            'name'        => 'slug',
                                            'type'        => 'text',
                                            'value'       => esc_attr( $clickwhale_slug ),
                                            'placeholder' => esc_attr__( 'Link Page Slug', 'clickwhale' ),
                                            'required'    => true
                                        )
                                    ),
                                    Helper::get_allowed_tags()
                                );
                                ?>
                                <p id="cw-slug--description"></p>
                                <p id="cw-slug--text"
                                   class="code"
                                   title="<?php esc_attr_e( 'Copy url', 'clickwhale' ); ?>"
                                ><?php
                                    echo esc_html__( 'URL Preview', 'clickwhale' ) . ': ' . esc_html( trailingslashit( home_url() ) );
                                    ?><span><?php echo ( $clickwhale_slug ) ? esc_html( trailingslashit( $clickwhale_slug ) ) : ''; ?></span><svg class="feather"><use href="<?php echo esc_url( CLICKWHALE_ADMIN_ASSETS_DIR ); ?>/images/feather-sprite.svg#copy"></use></svg>
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
                                    $clickwhale_logo_src = wp_get_attachment_image_url( $clickwhale_logo_id );
                                    if ( $clickwhale_logo_id && $clickwhale_logo_src ) { ?>
                                        <a href="#"
                                           class="cw-linkpage-image-upload"
                                        ><img alt="linkpage-logo" src="<?php echo esc_url( $clickwhale_logo_src ); ?>" /></a>
                                        <a href="#"
                                           class="button cw-linkpage-image-remove"
                                        ><?php esc_html_e( 'Remove image', 'clickwhale' ); ?></a>
                                        <input type="hidden" name="logo" value="<?php echo esc_attr( $clickwhale_logo_id ); ?>" />
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
                                    $clickwhale_favicon_src = $clickwhale_favicon_id ? wp_get_attachment_image_url( $clickwhale_favicon_id ) : '';
                                    if ( $clickwhale_favicon_id && $clickwhale_favicon_src ) { ?>
                                        <a href="#"
                                           class="cw-linkpage-image-upload"
                                        ><img alt="linkpage-favicon" src="<?php echo esc_url( $clickwhale_favicon_src ); ?>" /></a>
                                        <a href="#"
                                           class="button cw-linkpage-image-remove"
                                        ><?php esc_html_e( 'Remove Site Icon', 'clickwhale' ); ?></a>
                                        <input type="hidden" name="favicon" value="<?php echo esc_attr( $clickwhale_favicon_id ); ?>" />
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
                                    $clickwhale_legals = $clickwhale_linkpage->get_link_meta( $clickwhale_item_id, 'legals_menu_id' );
                                    echo wp_kses(
                                        Helper::render_control(
                                            array(
                                                'row_label' => esc_html__( 'Legals', 'clickwhale' ),
                                                'control'   => 'select',
                                                'id'        => 'cw-legals',
                                                'name'      => 'meta__legals_menu_id',
                                                'value'     => esc_attr( $clickwhale_legals['meta_value'] ?? 0 ),
                                                'options'   => $clickwhale_linkpage->get_nav_menus()
                                            )
                                        ),
                                        Helper::get_allowed_tags()
                                    );
                                    ?>
                                    <p class="description">
                                        <a href="<?php echo esc_url( admin_url( 'nav-menus.php' ) ); ?>"
                                           target="_blank"
                                           rel="noopener"><?php esc_html_e( 'Create a Legals Menu', 'clickwhale' ); ?></a>
                                    </p>
                                    <?php
                                } else {
                                    esc_html_e( 'Your theme does not support navigation menus or widgets.', 'clickwhale' );
                                }
                                ?>
                            </td>
                        </tr>

                        <?php do_action( 'clickwhale_linkpage_after_settings_fields', $clickwhale_item ); ?>
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
                                    $clickwhale_disabled = $clickwhale_links_limit ? 'disabled' : '';
                                    foreach ( $clickwhale_select as $clickwhale_g => $clickwhale_group ) {
                                        ?>
                                        <div class="cw-content--group">
                                            <h3><?php echo esc_html( $clickwhale_group['label'] ); ?>
                                                (<?php echo esc_html( count( $clickwhale_group['options'] ) ); ?>)</h3>
                                            <div class="cw-content--items">
                                                <?php foreach ( $clickwhale_group['options'] as $clickwhale_value => $clickwhale_options ) { ?>
                                                    <div id="cw-content--<?php echo esc_attr( $clickwhale_value ); ?>"
                                                         class="cw-content--item <?php echo esc_attr( $clickwhale_disabled ); ?>"
                                                         data-content="<?php echo esc_attr( $clickwhale_value ); ?>"
                                                    ><?php
                                                        if ( isset( $clickwhale_options['icon'] ) && $clickwhale_options['icon'] ) {
                                                            ?>
                                                            <svg class="feather"><use href="<?php echo esc_url( CLICKWHALE_ADMIN_ASSETS_DIR . '/images/feather-sprite.svg#' . $clickwhale_options['icon'] ); ?>"></use></svg>
                                                            <?php
                                                        }
                                                        echo esc_html( $clickwhale_options['name'] ); ?>
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
                                if ( $clickwhale_links ) {
                                    $clickwhale_template = new Clickwhale_Linkpage_Content_Templates();
                                    foreach ( $clickwhale_links as $link ) {
                                        echo wp_kses(
                                            $clickwhale_template->get_template(
                                                sanitize_key( $link['type'] ),
                                                false,
                                                false,
                                                array( 'data' => $link, 'linkpage_id' => intval( $clickwhale_item_id ) )
                                            ),
                                            Helper::get_allowed_tags()
                                        );
                                    }
                                }
                                ?>
                            </div>
                            <?php if ( $clickwhale_links_limit ) { ?>
                                <div class="cw-links-info">
                                    <?php echo esc_html( Linkpages_Helper::get_links_limitation_notice() ); ?>
                                    <?php echo wp_kses( Helper::get_pro_message(), Helper::get_allowed_tags() ); ?>
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

                        <?php do_action( 'clickwhale_linkpage_before_general_styles', $clickwhale_item ); ?>

                        <?php // PAGE TEXT COLOR ?>
                        <tr class="form-field">
                            <th scope="row">
                                <label for="styles[text_color]"><?php esc_html_e( 'Page Text Color', 'clickwhale' ); ?></label>
                            </th>
                            <td>
                                <input name="styles[text_color]"
                                       class="cw-color-control"
                                       type="text"
                                       value="<?php echo esc_attr( $clickwhale_styles['text_color'] ); ?>" />
                                <p class="description"><?php esc_html_e( 'Set page text color', 'clickwhale' ); ?></p>
                            </td>
                        </tr>

                        <?php
                        // PAGE BACKGROUND
                        $clickwhale_background = apply_filters( 'clickwhale_linkpage_advanced_background', false );
                        if ( ! $clickwhale_background ) {
                            ?>
                            <tr class="form-field">
                                <th scope="row">
                                    <label for="styles[bg_color]"><?php esc_html_e( 'Page Background', 'clickwhale' ); ?></label>
                                </th>
                                <td>
                                    <input name="styles[bg_color]"
                                           class="cw-color-control"
                                           type="text"
                                           value="<?php echo esc_attr( $clickwhale_styles['bg_color'] ); ?>" />
                                    <p class="description"><?php esc_html_e( 'Set page background color', 'clickwhale' ); ?></p>
                                </td>
                            </tr>

                            <?php
                        }
                        do_action( 'clickwhale_linkpage_after_general_styles', $clickwhale_item );
                        ?>

                        </tbody>
                    </table>

                    <hr>
                    <?php do_action( 'clickwhale_linkpage_after_general_styles_table', $clickwhale_item ); ?>

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
                                       value="<?php echo esc_attr( $clickwhale_styles['link_bg_color'] ); ?>" />
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
                                       value="<?php echo esc_attr( $clickwhale_styles['link_bg_color_hover'] ); ?>" />
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
                                       value="<?php echo esc_attr( $clickwhale_styles['link_color'] ); ?>" />
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
                                       value="<?php echo esc_attr( $clickwhale_styles['link_color_hover'] ); ?>" />
                                <p class="description"><?php esc_html_e( 'Set link text color (hover/active)', 'clickwhale' ); ?></p>
                            </td>
                        </tr>
                        </tbody>
                    </table>

                    <?php do_action( 'clickwhale_linkpage_after_styles_tables', $clickwhale_item ); ?>
                </div>

                <div id="lp-tab-seo">
                    <h2><?php esc_html_e( 'SEO Options', 'clickwhale' ); ?></h2>
                    <table cellspacing="2" cellpadding="5" style="width: 100%;" class="form-table">
                        <caption hidden><?php esc_html_e( 'Link Page SEO Options', 'clickwhale' ); ?></caption>
                        <tbody>
                        <?php
                        echo wp_kses(
                            Helper::render_control(
                                array(
                                    'row_label'   => esc_html__( 'SEO Title', 'clickwhale' ),
                                    'control'     => 'input',
                                    'id'          => 'socialSeoTitle',
                                    'name'        => 'social[seo][title]',
                                    'type'        => 'text',
                                    'value'       => esc_attr( wp_unslash( $clickwhale_seoTitle ) ),
                                    'placeholder' => '',
                                    'description' => esc_html__( 'Set page SEO title', 'clickwhale' )
                                ),
                                true
                            ),
                            Helper::get_allowed_tags()
                        );
                        echo wp_kses(
                            Helper::render_control(
                                array(
                                    'row_label'   => esc_html__( 'SEO Description', 'clickwhale' ),
                                    'control'     => 'input',
                                    'id'          => 'socialSeoDescription',
                                    'name'        => 'social[seo][description]',
                                    'type'        => 'text',
                                    'value'       => wp_unslash( $clickwhale_seoDescription ),
                                    'placeholder' => '',
                                    'description' => esc_html__( 'Set page SEO description', 'clickwhale' )
                                ),
                                true
                            ),
                            Helper::get_allowed_tags()
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
                                        echo wp_kses(
                                            sprintf(
                                                /* translators: %s: URL to WordPress Reading Settings screen */
                                                __( 'Search engines are not allowed to index this site. See the option "Search engine visibility" in <a href="%s" target="_blank">reading settings!</a>', 'clickwhale' ),
                                                esc_url( admin_url( 'options-reading.php' ) )
                                            ),
                                            array(
                                                'a' => array(
                                                    'href'   => array(),
                                                    'target' => array( '_blank' )
                                                )
                                            )
                                        );
                                        ?>
                                    </div>
                                    <?php
                                }

                                if ( $clickwhale_robots ) {
                                    $clickwhale_current_robots =
                                        isset( $clickwhale_social['seo']['robots'] )
                                            ? maybe_unserialize( $clickwhale_social['seo']['robots'] )
                                            : array();
                                    foreach ( $clickwhale_robots as $clickwhale_robotKey => $clickwhale_robotVal ) {
                                        ?>
                                        <p>
                                            <input type="checkbox"
                                                   id="robots-<?php echo esc_attr( $clickwhale_robotKey ); ?>"
                                                   name="social[seo][robots][]"
                                                   value="<?php echo esc_attr( $clickwhale_robotKey ); ?>"
                                                <?php
                                                if ( $clickwhale_current_robots ) {
                                                    checked( 1, in_array( $clickwhale_robotKey, $clickwhale_current_robots ) );
                                                }
                                                if ( ! get_option( 'blog_public' ) || get_option( 'blog_public' ) === '0' ) { ?>
                                                    disabled
                                                <?php } ?>
                                            />
                                            <label for="robots-<?php echo esc_attr( $clickwhale_robotKey ); ?>">
                                                <?php echo esc_attr( wp_unslash( $clickwhale_robotVal['title'] ) ); ?>
                                                <small>(<?php echo esc_attr( wp_unslash( $clickwhale_robotVal['description'] ) ); ?>
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
                        echo wp_kses(
                            Helper::render_control(
                                array(
                                    'row_label'   => esc_html__( 'Open Graph Title (Optional)', 'clickwhale' ),
                                    'control'     => 'input',
                                    'id'          => 'socialOGTitle',
                                    'name'        => 'social[seo][ogtitle]',
                                    'type'        => 'text',
                                    'value'       => esc_attr( wp_unslash( $clickwhale_seoOGTitle ) ),
                                    'placeholder' => esc_attr( wp_unslash( $clickwhale_seoTitle ) ),
                                    'description' => esc_html__( 'The title of your page for social network. By default this is Link Page title.', 'clickwhale' )
                                ),
                                true
                            ),
                            Helper::get_allowed_tags()
                        );
                        echo wp_kses(
                            Helper::render_control(
                                array(
                                    'row_label'   => esc_html__( 'Open Graph Description (Optional)', 'clickwhale' ),
                                    'control'     => 'input',
                                    'id'          => 'socialOGDescription',
                                    'name'        => 'social[seo][ogdescription]',
                                    'type'        => 'text',
                                    'value'       => esc_attr( wp_unslash( $clickwhale_seoOGDescription ) ),
                                    'placeholder' => esc_attr( wp_unslash( $clickwhale_seoDescription ) ),
                                    'description' => esc_html__( 'The description of your page for social network. By default this is SEO description.', 'clickwhale' )
                                ),
                                true
                            ),
                            Helper::get_allowed_tags()
                        );
                        ?>

                        <tr class="form-field">
                            <th scope="row">
                                <label for="ogimage"><?php esc_html_e( 'Open Graph Image', 'clickwhale' ); ?></label>
                            </th>
                            <td>
                                <div class="og-image-field image-field">
                                    <?php
                                    $clickwhale_ogImage = wp_get_attachment_image_url( $clickwhale_seoOGImageId );

                                    if ( $clickwhale_seoOGImageId && $clickwhale_ogImage ) { ?>
                                        <a href="#"
                                           class="cw-linkpage-image-upload"
                                        ><img alt="linkpage-og-image" src="<?php echo esc_url( $clickwhale_ogImage ); ?>" /></a>
                                        <a href="#"
                                           class="button cw-linkpage-image-remove"
                                        ><?php esc_html_e( 'Remove image', 'clickwhale' ); ?></a>
                                        <input type="hidden" name="social[seo][ogimage]" value="<?php echoesc_attr( $clickwhale_seoOGImageId ); ?>" />
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
                                   href="<?php echo esc_url( $clickwhale_seoOGPreviewVendorURL . rawurlencode( $clickwhale_seoOGLPURL ) ); ?>"
                                   target="_blank"
                                   rel="noopener"
                                ><?php esc_html_e( 'View live preview', 'clickwhale' ); ?></a>
                                <p class="description"></p>
                            </td>
                        </tr>

                        <?php do_action( 'clickwhale_linkpage_after_og_fields', $clickwhale_item ); ?>
                        </tbody>
                    </table>

                    <?php do_action( 'clickwhale_linkpage_after_seo_tables', $clickwhale_item ); ?>

                </div>

                <?php do_action( 'clickwhale_linkpage_after_tabs_content', $clickwhale_item ); ?>

            </div>

            <input type="hidden"
                   id="created_at"
                   name="created_at"
                   value="<?php echo esc_attr( $clickwhale_item['created_at'] ); ?>"
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
            />
        </div>
    </form>

    <?php do_action( 'clickwhale_admin_sidebar_end' ); ?>

</div>