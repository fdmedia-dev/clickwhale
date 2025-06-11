<?php
namespace clickwhale\includes\admin\links;

use clickwhale\includes\admin\Clickwhale_Instance_Edit;
use clickwhale\includes\helpers\{Helper, Links_Helper};

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Clickwhale_Link_Edit extends Clickwhale_Instance_Edit {

    /**
     * @var string
     */
    private string $links_table;

    public function __construct() {
        parent::__construct( 'links', 'link', 'Link' );

        $this->links_table = Helper::get_db_table_name( $this->instance_plural );
    }

    /**
     * @return array
     */
    public function get_defaults(): array {
        $defaults = clickwhale()->settings->default_options();
        $link_manager_options = get_option( 'clickwhale_link_manager_options' );

        return array(
            'id'          => 0,
            'title'       => '',
            'url'         => '',
            'slug'        => '',
            'redirection' => $link_manager_options['redirect_type'] ?? $defaults['link_manager']['options']['redirect_type'],
            'link_target' => $link_manager_options['link_target'] ?? $defaults['link_manager']['options']['link_target'],
            'nofollow'    => '',
            'sponsored'   => '',
            'description' => '',
            'categories'  => '',
            'author'      => 0,
            'created_at'  => '',
            'updated_at'  => ''
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

    public function save_update(): void {
        global $wpdb;
        $item = array_intersect_key( $_POST, $this->get_defaults() );
        $item['categories'] = isset( $item['categories'] ) ? sanitize_text_field( implode( ',', $item['categories'] ) ) : '';
        $item['nofollow']   = isset( $item['nofollow'] );
        $item['sponsored']  = isset( $item['sponsored'] );
        $item['author']     = get_current_user_id();
        $id = intval( $item['id'] );

        // Check if item exists and then update or insert.
        // In some cases default check (not false or 0) goes wrong
        if ( Links_Helper::get_by_id( $id ) ) {
            $wpdb->update(
                $this->links_table,
                $item,
                array( 'id' => $id )
            );
            do_action( 'clickwhale_link_updated', $id, $_POST );
            $this->set_transient( $id, 'updated' );

        } else {
            $wpdb->insert(
                $this->links_table,
                $item
            );
            $id = $wpdb->insert_id;
            do_action( 'clickwhale_link_inserted', $id, $_POST );
            $this->set_transient( $id, 'added' );
        }

        $url = 'admin.php?page=' . CLICKWHALE_SLUG . '-edit-link&id=' . $id;
        wp_redirect( esc_url_raw( admin_url( $url ) ) );
        exit;
    }

    public function admin_scripts(): void {
        $page_id = intval( $_GET['id'] ?? 0 );

        if ( isset( $_GET['page'] ) && sanitize_key( $_GET['page'] ) === CLICKWHALE_SLUG . '-edit-link' ) {
            ?>
            <script type='text/javascript'>
                jQuery(document).ready(function(){
                    const pageID = +'<?php echo $page_id; ?>'; // `string` to `integer`

                        // Store the last viewed tab index for current (existing) CW Link
                        if (pageID > 0){
                            const
                                tabID = 'clickwhale-link-' + pageID,
                                localTabID = localStorage.getItem(tabID);

                            if (localTabID){
                                jQuery('#clickwhale-tabs').tabs({active: localTabID});
                            }
                            jQuery('#clickwhale-tabs li').on('click', function(){
                                localStorage.setItem(tabID, jQuery(this).index());
                            });
                        }
                });
            </script>
            <?php
        }
        ?>

        <script type='text/javascript'>
            jQuery(document).ready(function(){
                const pageID = +'<?php echo $page_id; ?>'; // `string` to `integer`

                let
                    submit = jQuery('#submit'),
                    form = submit.closest('form'),
                    title = jQuery('#title'),
                    slug = jQuery('#cw-slug'),
                    url = jQuery('#url'),
                    slugPrefix = '';

                <?php $slug_prefix = Helper::get_clickwhale_option( 'link_manager', 'slug' ); ?>

                if (0 === pageID){
                    slugPrefix = "<?php echo ( $slug_prefix ) ? esc_js( untrailingslashit( $slug_prefix ) ) : ''; ?>";
                }

                /**
                 * Title blur action
                 */
                title
                    .on('blur', function(){
                        const
                            $this = jQuery(this),
                            original = $this.val();

                        if (!original){
                            return false;
                        }

                        $this.removeClass('error').next().text('');

                        if (!slug.val()
                            || (slugPrefix && sanitizeSlug(slug.val()) === slugPrefix)
                        ){

                            const newSlug = slugPrefix ? slugPrefix + '/' + original : original;
                            slug.val(newSlug)
                                .removeClass('error')
                                .next().text('')
                                .end()
                                .trigger('blur');
                        }
                    })
                    .on('input', function(){
                        jQuery(this).removeClass('error').next().text('');
                    });

                /**
                 * Slug blur action.
                 * Sanitize slug and paste link page slug into `URL Preview`
                 */
                slug
                    .on('blur', function(){
                        const
                            $this = jQuery(this),
                            $previewContainer = jQuery('#cw-slug--text');

                        let original = $this.val();

                        if (!original
                            || (slugPrefix && sanitizeSlug(original) === slugPrefix)
                        ){
                            if (!title.val()){
                                $previewContainer.find('span').html('');
                                return false;
                            }

                            $this.removeClass('error').next().text('');

                            original = slugPrefix ? slugPrefix + '/' + title.val() : title.val();
                            $this.val(original);
                        }

                        const sanitized = sanitizeSlug(original);

                        if (slugPrefix && sanitized === slugPrefix){
                            $this.val(sanitized + '/');
                        } else {
                            $this.val(sanitized);
                        }

                        $previewContainer.find('span').html(sanitized + '/');

                        if (!sanitized){
                            $this.addClass('error')
                                .next().html(`
                                    <?php echo esc_js( esc_html__( 'Please enter slug', 'clickwhale' ) ); ?>
                                    <br>
                                    <?php echo esc_js( esc_html__( 'Allowed alphanumeric characters (a...z, A...Z, 0...9), underscore (_) and dash (-), with optional slash (/) as separator', 'clickwhale' ) ); ?>
                                    `.trim()
                            );
                        }
                    })
                    .on('input', function(){
                        jQuery(this).removeClass('error').next().text('');
                    });

                /**
                 * Submit action
                 * 1. Check title (not null)
                 * 2. Check slug (not null)
                 * 3. Check if slug is already used by CW links, CW link pages, WP posts/pages/taxonomies
                 * 4. Check url (not null)
                 */
                form.on('submit', function(e){
                    const $previewContainer = jQuery('#cw-slug--text');

                    let slugOriginal = slug.val();

                    jQuery('#clickwhale-tabs').tabs('option', 'active', 0);

                    if (!title.val()){
                        e.preventDefault();
                        generalTabNotValid();
                        title.addClass('error')
                            .next().text('<?php echo esc_js( __( 'Please enter title', 'clickwhale' ) ); ?>');
                        return false;
                    } else {
                        title.removeClass('error').next().text('');
                    }

                    if (!slugOriginal){
                        e.preventDefault();
                        generalTabNotValid();
                        slug.addClass('error')
                            .next().text('<?php echo esc_js( __( 'Please enter slug', 'clickwhale' ) ); ?>');
                        $previewContainer.find('span').html('');
                        return false;
                    } else {
                        slug.removeClass('error').next().text('');
                    }

                    const sanitized = sanitizeSlug(slugOriginal);
                    slug.val(sanitized);
                    $previewContainer.find('span').html(sanitized + '/');

                    if (!sanitized){
                        e.preventDefault();
                        generalTabNotValid();
                        slug.addClass('error')
                            .next().html(`
                                <?php echo esc_js( esc_html__( 'Please enter slug', 'clickwhale' ) ); ?>
                                <br>
                                <?php echo esc_js( esc_html__( 'Allowed alphanumeric characters (a...z, A...Z, 0...9), underscore (_) and dash (-), with optional slash (/) as separator', 'clickwhale' ) ); ?>
                                `.trim()
                            );
                        return false;
                    } else {
                        slug.removeClass('error').next().text('');
                    }

                    let slugMatch = slugExists();

                    if (undefined !== slugMatch.id){
                        e.preventDefault();
                        generalTabNotValid();

                        const
                            slugUrl = '<?php echo esc_js( esc_url( home_url( '/' ) ) ); ?>' + slug.val(),
                            idPart = (slugMatch.id > 0) ? `, ID: ${slugMatch.id}` : '';

                        slug.addClass('error')
                            .next().html(`
                                <?php
                                    echo esc_js( esc_html__( 'This slug is already used in %1$s', 'clickwhale' ) ) .
                                    '<br/ >' .
                                    esc_js( esc_html__( 'Type: %2$s%3$s', 'clickwhale' ) ) .
                                    '. ' .
                                    esc_js( esc_html__( 'Please enter another slug', 'clickwhale' ) );
                                ?>
                                `.trim()
                                .replace('%1$s', `<b><a href="${slugUrl}" target="_blank">${slugUrl}</a></b>`)
                                .replace('%2$s', `<b>${slugMatch.type}</b>`)
                                .replace('%3$s', idPart)
                            );
                        return false;
                    } else {
                        slug.removeClass('error').next().text('');
                    }

                    if (!url.val()){
                        e.preventDefault();
                        generalTabNotValid();
                        url.addClass('error')
                            .next().text('<?php echo esc_js( __( 'Please enter URL', 'clickwhale' ) ); ?>');
                        return false;
                    } else {
                        url.removeClass('error').next().text('');
                    }

                    submit.trigger('clickwhale.link.save', { formEvent: e });
                });

                /** JS FUNCTIONS */

                function sanitizeSlug(){
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
                        }, success: function(response){
                            result = response.data;
                        }
                    });
                    return result;
                }

                function slugExists(){
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
                            'id': <?php echo intval( $_GET['id'] ?? 0 ); ?>
                        }, success: function(response){
                            result = response.data;
                        }
                    });
                    return result;
                }

                function generalTabNotValid(){
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
