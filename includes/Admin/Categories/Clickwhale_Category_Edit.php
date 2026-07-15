<?php

namespace Clickwhale\Admin\Categories;

use Clickwhale\Admin\Clickwhale_Instance_Edit;
use Clickwhale\Helpers\Categories_Helper;
use Clickwhale\Helpers\Helper;

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
        $table = Helper::get_db_table_name( $this->instance_plural );
        $item  = array_intersect_key( $_POST, $this->get_defaults() );
        $id    = intval( $item['id'] );

        // Check if item exists and then update or insert
        // in some cases default check (not false and < 0) goes wrong
        if ( Categories_Helper::get_by_id( $id ) ) {
            $wpdb->update(
                    $table,
                    $item,
                    array( 'id' => $id )
            );
            $this->set_transient( $id, 'updated' );

        } else {
            unset( $item['id'] );
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
        $id = (int) filter_input( INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT );
        ?>
        <script type='text/javascript'>
            jQuery(document).ready(function () {
                const
                    title = jQuery('#title'),
                    slug = jQuery('#slug'),
                    form = jQuery('#submit').closest('form');

                clickwhaleUnsavedChanges.track(form);

                /**
                 * Submit action
                 * 1. Check title (not null)
                 * 2. Check slug (not null)
                 * 3. Check if slug is already used by CW categories
                 */
                jQuery('#submit').on('click', function (e) {
                    if (!title.val()) {
                        e.preventDefault();
                        title.addClass('error')
                            .next().text(<?php echo wp_json_encode( __( 'Please enter title', 'clickwhale' ) ); ?>);
                        return false;
                    } else {
                        title.removeClass('error').next().text('');
                    }

                    if (!slug.val()) {
                        slug.val(title.val());
                    }

                    slug.val(sanitizeSlug(slug.val()));

                    if (!slug.val()) {
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

                    if (undefined !== slug_obj.id) {
                        e.preventDefault();
                        slug.addClass('error')
                            .next().html(<?php echo wp_json_encode(
                        /* translators: 1: matched resource title, 2: resource type, 3: resource numeric ID */
                                esc_html__( 'This slug is already used in %1$s (%2$s ID: %3$d)', 'clickwhale' ) .
                                '<br>' .
                                esc_html__( 'Please enter another slug', 'clickwhale' ) ); ?>
                            .replace('%1$s', `<b>${slug_obj.title}</b>`)
                            .replace('%2$s', slug_obj.type)
                            .replace('%3$d', slug_obj.id)
                    )
                        ;
                        return false;
                    } else {
                        slug.removeClass('error').next().text('');
                    }

                    clickwhaleUnsavedChanges.markClean();
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
                            'type': 'category',
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
                            'type': 'category',
                            'slug': slug.val() ? slug.val() : title.val(),
                            'id': <?php echo intval( $id ); ?>
                        }, success: function (response) {
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
