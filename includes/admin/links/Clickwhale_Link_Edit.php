<?php
namespace clickwhale\includes\admin\links;

use clickwhale\includes\admin\Clickwhale_Instance_Edit;
use clickwhale\includes\Clickwhale;
use clickwhale\includes\helpers\{Links_Helper};
use clickwhale\includes\helpers\Helper;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Clickwhale_Link_Edit extends Clickwhale_Instance_Edit {

	public function __construct() {
		parent::__construct( 'links', 'link' );
	}

	/**
	 * Could be hooked by filter "clickwhale_link_defaults"
	 *
	 * @return array
	 */
	public function get_defaults(): array {
		$plugin_defaults = clickwhale()->settings->default_options();

		return array(
			'id'          => 0,
			'title'       => '',
			'url'         => '',
			'slug'        => '',
			'redirection' => $plugin_defaults['general']['options']['redirect_type'],
			'nofollow'    => '',
			'sponsored'   => '',
			'description' => '',
			'categories'  => '',
			'author'      => 0,
			'created_at'  => '',
			'updated_at'  => '',
		);
	}

	public function save_update() {
		global $wpdb;

		$links_table        = Helper::get_clickwhale_bd_table_name( $this->instance_plural );
		$item               = array_intersect_key( $_POST, $this->get_defaults() );
		$item['categories'] = isset( $item['categories'] ) ? implode( ',', $item['categories'] ) : '';
		$item['nofollow']   = isset( $item['nofollow'] );
		$item['sponsored']  = isset( $item['sponsored'] );
		$item['author']     = get_current_user_id();

		// Check if item exists and then update or insert
		// in some cases default check (not false and < 0) goes wrong

		if ( Links_Helper::get_by_id( intval( $item['id'] ) ) ) {
			$wpdb->update(
				$links_table,
				$item,
				array( 'id' => $item['id'] )
			);
			do_action( 'clickwhale_update_link_meta', $item['id'], $_POST );
			$this->set_transient( $item['id'], 'updated' );
		} else {
			$wpdb->insert(
				$links_table,
				$item
			);
			$item['id'] = $wpdb->insert_id;
			do_action( 'clickwhale_insert_link_meta', $item['id'], $_POST );
			$this->set_transient( $item['id'], 'added' );
		}

		$url = 'admin.php?page=' . CLICKWHALE_SLUG . '-edit-' . $this->instance_single . '&id=' . $item['id'];
		wp_redirect( admin_url( $url ) );
		die;
	}

	public function admin_scripts(): void {
		$nonce = wp_create_nonce( 'slug_exists' );
		?>
        <script type='text/javascript'>
            jQuery(document).ready(function () {

                const
                    title = jQuery('#title'),
                    slug = jQuery('#cw-slug');

				<?php
				/**
				 * if checked "Disable random slug" option
				 * use title as slug
				 */
				if ( Helper::get_clickwhale_option( 'general', 'random_slug' ) ) {
				$slugOptionsGeneral = Helper::get_clickwhale_option( 'general', 'slug' )
					? trailingslashit( Helper::get_clickwhale_option( 'general', 'slug' ) )
					: '';
				?>
                const slugOptionsGeneral = "<?php echo $slugOptionsGeneral; ?>";

                jQuery(title).on('blur', function () {
                    if (!slug.val() || slug.val() === slugOptionsGeneral) {
                        slug.val(slugOptionsGeneral + this.value).trigger("blur");
                    }
                });
                jQuery(slug).on('blur', function () {
                    if (title.val() && (!this.value || this.value === slugOptionsGeneral)) {
                        slug.val(slugOptionsGeneral + title.val()).trigger("blur");
                    }
                });
				<?php } ?>

                /**
                 * Submit action
                 * 1. Check title (not null)
                 * 2. Check slug (not null)
                 * 3. Check slug (exists as post/page slug)
                 */
                jQuery('#submit').click(function (e) {
                    const slugExists = slug_exists();

                    if (!title.val()) {
                        e.preventDefault();

                        title.addClass('error')
                            .next().text('<?php _e( 'Please enter title', CLICKWHALE_NAME ) ?>');
                    } else {
                        title.removeClass('error').next().text('');
                    }

                    if (!slug.val()) {
                        e.preventDefault();

                        slug.addClass('error')
                            .next().text('<?php _e( 'Please enter slug', CLICKWHALE_NAME ) ?>')
                    } else {
                        slug.removeClass('error').next().text('');
                    }

                    if (slugExists === true) {
                        e.preventDefault();

                        slug.addClass('error');
                        jQuery('#cw-slug--description').text('<?php _e( 'This slug is already in use! Please enter another slug',
							CLICKWHALE_NAME ) ?>')
                    }
                });

                function slug_exists() {
                    let result = null;
                    jQuery.ajax({
                        async: false,
                        type: 'post',
                        dataType: 'json',
                        url: ajaxurl,
                        data: {
                            'security': '<?php echo $nonce ?>',
                            'action': 'clickwhale/admin/slug_exists',
                            'type': 'link',
                            'slug': slug.val(),
                            'id': <?php echo esc_attr( intval( $_GET['id'] ?? 0 ) ); ?>
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