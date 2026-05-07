<?php

namespace Clickwhale\Admin\Links;

use Clickwhale\Admin\Clickwhale_Instance_Edit;
use Clickwhale\Helpers\{Helper, Links_Helper};

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Clickwhale_Link_Edit extends Clickwhale_Instance_Edit {

    public function __construct() {
        $this->instance_plural = 'links';
        $this->instance_single = 'link';
        $this->instance_helper = Links_Helper::class;
        parent::__construct();
    }

    protected function get_title_i18n(): string {
        return __( 'Link', 'clickwhale' );
    }

    /**
     * @return array
     */
    public function get_defaults(): array {
        $defaults             = clickwhale()->settings->default_options();
        $link_manager_options = get_option( 'clickwhale_link_manager_options' );

        return array(
                'id'             => 0,
                'title'          => '',
                'url'            => '',
                'slug'           => '',
                'redirection'    => $link_manager_options['redirect_type'] ?? $defaults['link_manager']['options']['redirect_type'],
                'link_target'    => $link_manager_options['link_target'] ?? $defaults['link_manager']['options']['link_target'],
                'nofollow'       => '',
                'sponsored'      => '',
                'description'    => '',
                'categories'     => '',
                'created_by_api' => 0,
                'author'         => 0,
                'created_at'     => '',
                'updated_at'     => ''
        );
    }

    public function render_tabs() {
        $tabs = array(
                'general' => array(
                        'name' => __( 'General', 'clickwhale' ),
                        'url'  => 'general'
                )
        );

        return apply_filters( 'clickwhale_link_tabs', $tabs );
    }

    /**
     * Adds `Link Scanner` tab after `General` tab.
     * If Pro version is active, this runs after Pro tabs as well.
     *
     * @param $tabs
     *
     * @return array
     * @see Clickwhale_Link_Edit::render_tabs()
     */
    public function link_tabs( $tabs ): array {
        return array_merge( $tabs, array(
                'link_scanner' => array(
                        'name' => __( 'Link Scanner', 'clickwhale' ),
                        'url'  => 'scanner'
                )
        ) );
    }

    public function link_scanner_html( $item ) {
        $id        = intval( $item['id'] );
        $cached    = ( $id ) ? get_transient( 'clickwhale_scanned_link_' . $id ) : array();
        $timestamp = $cached['timestamp'] ?? '';
        $last_time = ( $timestamp ) ? wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $timestamp ) : '';
        $html      = $cached['html'] ?? '';
        ?>
        <div id="link-tab-link_scanner">
            <p><?php esc_html_e( 'Here you can check the posts and pages to see if this link has been placed on any of them.', 'clickwhale' ); ?></p>
            <div>
                <button type="button"
                        class="button"
                        id="scan-button"
                ><?php esc_html_e( 'Scan Now', 'clickwhale' ); ?></button>
                <span class="spinner"></span>
                <p class="scan-button-description"><?php esc_html_e( 'Please save changes to enable link scanner', 'clickwhale' ); ?></p>
            </div>
            <div class="link-tab-link_scanner-table-wrapper <?php echo ( $timestamp ) ? 'is-visible' : ''; ?>">
                <p class="table-description <?php echo ( $timestamp ) ? 'is-visible' : ''; ?>">
                    <?php esc_html_e( 'Last scan:', 'clickwhale' ); ?>
                    <span class="last-time"><?php echo esc_html( $last_time ); ?></span>
                    <span>(<?php esc_html_e( 'result is cached for 1 day', 'clickwhale' ); ?>)</span>
                </p>
                <table class="wp-list-table">
                    <caption hidden><?php esc_html_e( 'Scanned links', 'clickwhale' ); ?></caption>
                    <thead class="<?php echo ( $timestamp ) ? 'is-visible' : ''; ?>">
                    <tr>
                        <th scope="col"><?php esc_html_e( 'Post ID', 'clickwhale' ); ?></th>
                        <th scope="col"><?php esc_html_e( 'Post Type', 'clickwhale' ); ?></th>
                        <th scope="col"><?php esc_html_e( 'Post Title', 'clickwhale' ); ?></th>
                        <th scope="col"><?php esc_html_e( 'Keywords Linked', 'clickwhale' ); ?></th>
                        <th scope="col"><?php esc_html_e( 'Actions', 'clickwhale' ); ?></th>
                    </tr>
                    </thead>
                    <tbody><?php echo wp_kses( $html, Helper::get_allowed_tags() ); ?></tbody>
                    <tfoot>
                    <tr>
                        <th colspan="5"><?php esc_html_e( 'This link could not be found on any posts or pages.', 'clickwhale' ); ?></th>
                    </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        <?php
    }

    public function after_tabs_content( $item ) {
        $this->link_scanner_html( $item );
    }

    protected function process_item_before_save( array $item ): array {
        $item['categories'] = isset( $item['categories'] ) ? sanitize_text_field( implode( ',', $item['categories'] ) ) : '';
        $item['nofollow']   = isset( $item['nofollow'] );
        $item['sponsored']  = isset( $item['sponsored'] );
        $item['author']     = get_current_user_id();

        return $item;
    }

    public function admin_scripts(): void {
        $slug_prefix = Helper::get_clickwhale_option( 'link_manager', 'slug' );
        $id          = (int) filter_input( INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT );
        $tab         = sanitize_key( (string) filter_input( INPUT_GET, 'tab' ) );
        ?>
        <script type='text/javascript'>
            jQuery(document).ready(function () {
                const
                    pageID = +'<?php echo intval( $id ); ?>', // `string` to `integer`
                    activeTab = '<?php echo sanitize_key( $tab ); ?>',
                    urlSearchParams = new URLSearchParams(window.location.search),
                    slugNotice = <?php echo wp_json_encode(
                                esc_html__( 'Please enter slug. Allowed characters:', 'clickwhale' ) .
                                '<br>-' .
                                esc_html__( 'alphanumeric (a...z, A...Z, 0...9)', 'clickwhale' ) .
                                '<br>-' .
                                esc_html__( 'underscore (_) and dash (-)', 'clickwhale' ) .
                                '<br>-' .
                                esc_html__( 'with optional slash (/) as separator', 'clickwhale' ) ); ?>
                ;

                let
                    $tabs = jQuery('#clickwhale-tabs'),
                    submit = jQuery('#submit'),
                    form = submit.closest('form'),
                    title = jQuery('#title'),
                    slug = jQuery('#cw-slug'),
                    url = jQuery('#url'),
                    slugPrefix = '',
                    $scanWrapper = jQuery('#link-tab-link_scanner'),
                    scanBtn = $scanWrapper.find('#scan-button'),
                    scanBtnDesc = $scanWrapper.find('.scan-button-description'),
                    scanSpinner = $scanWrapper.find('.spinner'),
                    $scanTableWrapper = $scanWrapper.find('.link-tab-link_scanner-table-wrapper'),
                    scanTableDesc = $scanTableWrapper.find('.table-description'),
                    scanTable = $scanWrapper.find('table'),
                    scanThead = scanTable.find('thead'),
                    scanTbody = scanTable.find('tbody'),
                    scanTfoot = scanTable.find('tfoot')
                ;

                $tabs.tabs();

                if (activeTab && 'link_scanner' === activeTab) {
                    urlSearchParams.delete('tab');

                    const
                        linkScannerTabIndex = $tabs.find('a[href="#link-tab-link_scanner"]').closest('li').index(),
                        query = urlSearchParams.toString(),
                        newUrl = window.location.pathname + (query ? '?' + query : '');

                    $tabs.tabs('option', 'active', linkScannerTabIndex);
                    window.history.replaceState({}, '', newUrl);
                }

                if (0 === pageID) {
                    slugPrefix = <?php echo wp_json_encode( ( $slug_prefix ) ? untrailingslashit( $slug_prefix ) : '' ); ?>;
                    scanBtn.addClass('disabled');
                    scanBtnDesc.addClass('is-visible');
                }

                /**
                 * Title blur action
                 */
                title
                    .on('blur', function () {
                        const
                            $this = jQuery(this),
                            original = $this.val();

                        if (!original) {
                            return false;
                        }

                        $this.removeClass('error').next().text('');

                        if (!slug.val()
                            || (slugPrefix && sanitizeSlug(slug.val()) === slugPrefix)
                        ) {

                            const newSlug = slugPrefix ? slugPrefix + '/' + original : original;
                            slug.val(newSlug)
                                .removeClass('error')
                                .next().text('')
                                .end()
                                .trigger('blur');
                        }
                    })
                    .on('input', function () {
                        jQuery(this).removeClass('error').next().text('');
                    });

                /**
                 * Slug blur action.
                 * Sanitize slug and paste link page slug into `URL Preview`
                 */
                slug
                    .on('blur', function () {
                        const
                            $this = jQuery(this),
                            $previewContainer = jQuery('#cw-slug--text');

                        let original = $this.val();

                        if (!original
                            || (slugPrefix && sanitizeSlug(original) === slugPrefix)
                        ) {
                            if (!title.val()) {
                                $previewContainer.find('span').html('');
                                return false;
                            }

                            $this.removeClass('error').next().text('');

                            original = slugPrefix ? slugPrefix + '/' + title.val() : title.val();
                            $this.val(original);
                        }

                        const sanitized = sanitizeSlug(original);

                        if (slugPrefix && sanitized === slugPrefix) {
                            $this.val(sanitized + '/');
                        } else {
                            $this.val(sanitized);
                        }

                        $previewContainer.find('span').html(sanitized + '/');

                        if (!sanitized) {
                            $this.addClass('error')
                                .next().html(slugNotice);
                        }
                    })
                    .on('input', function () {
                        jQuery(this).removeClass('error').next().text('');
                    });

                /**
                 * Submit action
                 * 1. Check title (not null)
                 * 2. Check slug (not null)
                 * 3. Check if slug is already used by CW links, CW link pages, WP posts/pages/taxonomies
                 * 4. Check url (not null)
                 */
                form.on('submit', function (e) {
                    const $previewContainer = jQuery('#cw-slug--text');

                    let slugOriginal = slug.val();

                    $tabs.tabs('option', 'active', 0);

                    if (!title.val()) {
                        e.preventDefault();
                        generalTabNotValid();
                        title.addClass('error')
                            .next().text(<?php echo wp_json_encode( __( 'Please enter title', 'clickwhale' ) ); ?>);
                        return false;
                    } else {
                        title.removeClass('error').next().text('');
                    }

                    if (!slugOriginal) {
                        e.preventDefault();
                        generalTabNotValid();
                        slug.addClass('error')
                            .next().text(<?php echo wp_json_encode( __( 'Please enter slug', 'clickwhale' ) ); ?>);
                        $previewContainer.find('span').html('');
                        return false;
                    } else {
                        slug.removeClass('error').next().text('');
                    }

                    const sanitized = sanitizeSlug(slugOriginal);
                    slug.val(sanitized);
                    $previewContainer.find('span').html(sanitized + '/');

                    if (!sanitized) {
                        e.preventDefault();
                        generalTabNotValid();
                        slug.addClass('error')
                            .next().html(slugNotice);
                        return false;
                    } else {
                        slug.removeClass('error').next().text('');
                    }

                    let slugMatch = slugExists();

                    if (undefined !== slugMatch.id) {
                        e.preventDefault();
                        generalTabNotValid();

                        <?php
                        /* translators: %s: matched resource ID (post/term ID) */
                        $id_tpl = esc_html__( 'ID: %s', 'clickwhale' );

                        /* translators: %s: matched resource type (e.g. post type or taxonomy) */
                        $type_tpl = esc_html__( 'type: %s', 'clickwhale' );

                        $wp_tpl = esc_html__( 'by WordPress (core, theme, plugin or rewrite rules).', 'clickwhale' );

                        /* translators: 1: HTML link to the URL using this slug, 2: HTML string with ID and type */
                        $slug_used_tpl = esc_html__( 'This slug is already used in %1$s', 'clickwhale' )
                                         . '<br>'
                                         . '%2$s'
                                         . '<br>'
                                         . esc_html__( 'Please enter another slug.', 'clickwhale' );
                        ?>
                        const
                            baseUrl = <?php echo wp_json_encode( esc_url( home_url( '/' ) ) ); ?>,
                            idTpl = <?php echo wp_json_encode( $id_tpl ); ?>,
                            typeTpl = <?php echo wp_json_encode( $type_tpl ); ?>,
                            wpTpl = <?php echo wp_json_encode( $wp_tpl ); ?>,
                            slugUrl = baseUrl + slug.val(),
                            idTemplate = (slugMatch.id > 0) ? idTpl.replace('%s', `<b>${slugMatch.id}</b>, `) : '',
                            slugType = (slugMatch.id > 0) ? typeTpl.replace('%s', `<b>${slugMatch.type}</b>.`) : wpTpl
                        ;

                        slug.addClass('error')
                            .next().html(<?php echo wp_json_encode( $slug_used_tpl ); ?>
                            .replace('%1$s', `<b><a href="${slugUrl}" target="_blank">${slugUrl}</a></b>`)
                            .replace('%2$s', `${idTemplate}${slugType}`)
                    )
                        ;
                        return false;
                    } else {
                        slug.removeClass('error').next().text('');
                    }

                    if (!url.val()) {
                        e.preventDefault();
                        generalTabNotValid();
                        url.addClass('error')
                            .next().text(<?php echo wp_json_encode( __( 'Please enter URL', 'clickwhale' ) ); ?>);
                        return false;
                    } else {
                        url.removeClass('error').next().text('');
                    }

                    submit.trigger('clickwhale.link.save', {formEvent: e});
                });

                /**
                 * Link Scanner
                 */
                scanBtn.on('click', () => {
                    scanBtn.addClass('disabled');

                    if (0 === pageID) {
                        return false;
                    }

                    scanSpinner.addClass('is-active');

                    jQuery
                        .post(ajaxurl, {
                            security: <?php echo wp_json_encode( wp_create_nonce( 'clickwhale_link_scanner' ) ); ?>,
                            action: 'clickwhale/admin/scan_links',
                            id: pageID,
                            slug: slug.val()
                        })
                        .done(function (response) {
                            if (response.success && response.data?.html) {
                                scanTbody.html(response.data.html);
                                scanTfoot.removeClass('is-visible');
                                scanThead.addClass('is-visible');
                                scanTableDesc
                                    .addClass('is-visible')
                                    .find('span.last-time').text(response.data.last_time);
                            } else {
                                scanTfoot.addClass('is-visible');
                                scanThead.removeClass('is-visible');
                                scanTableDesc.removeClass('is-visible');
                            }
                        })
                        .fail(function (jqXHR, textStatus, errorThrown) {
                            scanTfoot.addClass('is-visible');
                            scanThead.removeClass('is-visible');
                            scanTableDesc.removeClass('is-visible');
                        })
                        .always(function () {
                            $scanTableWrapper.addClass('is-visible');
                            scanBtn.removeClass('disabled');
                            scanSpinner.removeClass('is-active');
                        });
                });

                scanTbody.on('click', '.link_scanner-toggle-titles', function (e) {
                    e.preventDefault();

                    const
                        $this = jQuery(this),
                        $td = $this.closest('td'),
                        $hiddenItems = $td.find('li.limited');

                    if ($this.text() === $this.data('show-all')) {
                        $hiddenItems.removeClass('hidden');
                        $this.text($this.data('show-less'));
                    } else {
                        $hiddenItems.addClass('hidden');
                        $this.text($this.data('show-all'));
                    }
                });

                /** JS FUNCTIONS */

                function sanitizeSlug() {
                    let result = null;
                    jQuery.ajax({
                        async: false,
                        type: 'post',
                        dataType: 'json',
                        url: ajaxurl,
                        data: {
                            'security': <?php echo wp_json_encode( wp_create_nonce( 'sanitize_slug' ) ); ?>,
                            'action': 'clickwhale/admin/sanitize_slug',
                            'type': 'link',
                            'slug': slug.val()
                        }, success: function (response) {
                            result = response.data;
                        }
                    });
                    return result;
                }

                function slugExists() {
                    let result = null;
                    jQuery.ajax({
                        async: false,
                        type: 'post',
                        dataType: 'json',
                        url: ajaxurl,
                        data: {
                            'security': <?php echo wp_json_encode( wp_create_nonce( 'slug_exists' ) ); ?>,
                            'action': 'clickwhale/admin/slug_exists',
                            'type': 'link',
                            'slug': slug.val(),
                            'id': pageID
                        }, success: function (response) {
                            result = response.data;
                        }
                    });
                    return result;
                }

                function generalTabNotValid() {
                    // Delete previous `success` notice
                    jQuery('.updated').remove();

                    // Scroll page to top
                    jQuery('html, body').animate({scrollTop: 0}, 'fast');
                }
            });
        </script>
        <?php
    }
}
