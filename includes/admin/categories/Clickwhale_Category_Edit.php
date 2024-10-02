<?php
namespace clickwhale\includes\admin\categories;

use clickwhale\includes\admin\Clickwhale_Instance_Edit;
use clickwhale\includes\helpers\{Helper, Categories_Helper};

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Clickwhale_Category_Edit extends Clickwhale_Instance_Edit {

	public function __construct() {
		parent::__construct( 'categories', 'category' );
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

		$table        = Helper::get_db_table_name( 'categories' );
		$item         = array_intersect_key( $_POST, $this->get_defaults() );
		$item['slug'] = $item['slug'] ? sanitize_title( $item['slug'] ) : sanitize_title( $item['title'] );

		// Check if item exists and then update or insert
		// in some cases default check (not false and < 0) goes wrong
		$category = Categories_Helper::get_by_id( intval( $item['id'] ) );

		if ( $category ) {
			$wpdb->update(
				$table,
				$item,
				array( 'id' => $item['id'] )
			);
			$this->set_transient( $item['id'], 'updated' );
		} else {
			$wpdb->insert(
				$table,
				$item
			);
			$item['id'] = $wpdb->insert_id;
			$this->set_transient( $item['id'], 'added' );
		}

		$url = 'admin.php?page=' . CLICKWHALE_SLUG . '-edit-' . $this->instance_single . '&id=' . $item['id'];
		wp_redirect( admin_url( $url ) );
	}

	public function admin_scripts(): void {
		$nonce = wp_create_nonce( 'slug_exists' );
		?>
        <script type='text/javascript'>
            jQuery(document).ready(function() {

                const
                    title = jQuery('#title'),
                    slug = jQuery('#slug');

                /**
                 * Submit action
                 * 1. Check title (not null)
                 * 2. Check slug (not null)
                 * 3. Check slug (exists as post/page slug)
                 */
                jQuery('#submit').on('click', function(e) {

                    if (!title.val()) {
                        e.preventDefault();

                        title.addClass('error').next().text('<?php _e( 'Please enter title', CLICKWHALE_NAME ) ?>');
                    } else {
                        title.removeClass('error').next().text('');
                    }

                    if (slugExists() === true) {
                        e.preventDefault();

                        slug.addClass('error');
                        jQuery('#cw-slug--description').text('<?php _e( 'This slug is already in use! Please enter another slug', CLICKWHALE_NAME ) ?>')
                    }

                });

                /**
                 *
                 * JS FUNCTIONS
                 *
                 */

                function slugExists() {
                    let result = null;
                    jQuery.ajax({
                        async: false,
                        type: 'post',
                        dataType: 'json',
                        url: ajaxurl,
                        data: {
                            'security': '<?php echo $nonce ?>',
                            'action': 'clickwhale/admin/slug_exists',
                            'type': 'category',
                            'slug': slug.val() ? slug.val() : title.val(),
                            'id': <?php echo esc_attr( intval( $_GET['id'] ?? 0 ) ); ?>
                        }, success: function(response) {
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