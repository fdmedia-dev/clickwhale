<?php
namespace clickwhale\includes\admin\categories;

use clickwhale\includes\admin\Clickwhale_Instance_Edit;
use clickwhale\includes\helpers\Categories_Helper;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Clickwhale_Category_Edit extends Clickwhale_Instance_Edit {

    public function __construct() {
        $this->instance_plural = 'categories';
        $this->instance_single = 'category';
        $this->instance_helper = Categories_Helper::class;
        parent::__construct();
    }

    protected function get_title_i18n(): string {
        return __( 'Category', 'clickwhale' );
    }

    public function get_defaults(): array {
        return array(
            'id'          => 0,
            'title'       => '',
            'slug'        => '',
            'description' => ''
        );
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
                            .next().text(<?php echo wp_json_encode( __( 'Please enter title', 'clickwhale' ) ); ?>);
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
                            .next().html(<?php echo wp_json_encode(
                                esc_html__( 'Please enter slug. Allowed characters:', 'clickwhale' ) .
                                '<br>-' .
                                esc_html__( 'alphanumeric (a...z, A...Z, 0...9)', 'clickwhale' ) .
                                '<br>-' .
                                esc_html__( 'underscore (_) and dash (-)', 'clickwhale' ) ); ?>
                            )
                        ;
                        return false;
                    } else {
                        slug.removeClass('error').next().text('');
                    }

                    let slug_obj = slugExists();

                    if (undefined !== slug_obj.id){
                        e.preventDefault();
                        slug.addClass('error')
                            .next().html(<?php echo wp_json_encode(
                                esc_html__( 'This slug is already used in %1$s (%2$s ID: %d)', 'clickwhale' ) .
                                '<br>' .
                                esc_html__( 'Please enter another slug', 'clickwhale' ) ); ?>
                                .replace('%1$s', `<b>${slug_obj.title}</b>`)
                                .replace('%2$s', slug_obj.type)
                                .replace('%d', slug_obj.id)
                            )
                        ;
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
