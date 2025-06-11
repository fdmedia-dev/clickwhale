<?php
namespace clickwhale\includes\admin\categories;

use clickwhale\includes\admin\Clickwhale_Instance_Edit;
use clickwhale\includes\helpers\{Helper, Categories_Helper};

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Clickwhale_Category_Edit extends Clickwhale_Instance_Edit {

    public function __construct() {
        parent::__construct( 'categories', 'category', 'Category' );
    }

    public function get_defaults(): array {
        return array(
            'id'          => 0,
            'title'       => '',
            'slug'        => '',
            'description' => ''
        );
    }

    public function save_update() {
        global $wpdb;
        $table = Helper::get_db_table_name( 'categories' );
        $item = array_intersect_key( $_POST, $this->get_defaults() );
        $id = intval( $item['id'] );

        // Check if category exists and then update or insert
        // in some cases default check (not false and < 0) goes wrong
        if ( Categories_Helper::get_by_id( $id ) ) {
            $wpdb->update(
                $table,
                $item,
                array( 'id' => $id )
            );
            $this->set_transient( $id, 'updated' );

        } else {
            $wpdb->insert(
                $table,
                $item
            );
            $id = $wpdb->insert_id;
            $this->set_transient( $id, 'added' );
        }

        $url = 'admin.php?page=' . CLICKWHALE_SLUG . '-edit-category&id=' . $id;
        wp_redirect( esc_url_raw( admin_url( $url ) ) );
        exit;
    }

    public function admin_scripts(): void {
        ?>
        <script type='text/javascript'>
            jQuery(document).ready(function(){
                const
                    title = jQuery('#title'),
                    slug = jQuery('#slug');

                /**
                 * Submit action
                 * 1. Check title (not null)
                 * 2. Check slug (not null)
                 * 3. Check if slug is already used by CW categories
                 */
                jQuery('#submit').on('click', function(e){
                    if (!title.val()){
                        e.preventDefault();
                        title.addClass('error')
                            .next().text('<?php echo esc_js( __( 'Please enter title', 'clickwhale' ) ); ?>');
                        return false;
                    } else {
                        title.removeClass('error').next().text('');
                    }

                    if (!slug.val()){
                        slug.val(title.val());
                    }

                    slug.val(sanitizeSlug(slug.val()));

                    if (!slug.val()){
                        e.preventDefault();
                        slug.addClass('error')
                            .next().html(`
                                <?php echo esc_js( esc_html__( 'Please enter slug', 'clickwhale' ) ); ?>
                                <br>
                                <?php echo esc_js( esc_html__( 'Allowed alphanumeric characters (a...z, A...Z, 0...9), underscore (_) and dash (-)', 'clickwhale' ) ); ?>
                                `.trim()
                            );
                        return false;
                    } else {
                        slug.removeClass('error').next().text('');
                    }

                    let slug_obj = slugExists();

                    if (undefined !== slug_obj.id){
                        e.preventDefault();
                        slug.addClass('error')
                            .next().html(`
                                <?php echo esc_js( esc_html__( 'This slug is already used in %1$s (%2$s ID: %d)', 'clickwhale' ) ) . '.'; ?>
                                <?php echo esc_js( esc_html__( 'Please enter another slug', 'clickwhale' ) ); ?>
                                `.trim()
                                .replace('%1$s', `<b>${slug_obj.title}</b>`)
                                .replace('%2$s', slug_obj.type)
                                .replace('%d', slug_obj.id)
                            );
                        return false;
                    } else {
                        slug.removeClass('error').next().text('');
                    }
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
                            'type': 'category',
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
                            'type': 'category',
                            'slug': slug.val() ? slug.val() : title.val(),
                            'id': <?php echo intval( $_GET['id'] ?? 0 ); ?>
                        }, success: function(response){
                            result = response.data;
                        }
                    });
                    return result;
                }
            });
        </script>
        <?php
    }
}
