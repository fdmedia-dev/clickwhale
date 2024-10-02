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
    private $links_table;

    public function __construct() {
		parent::__construct( 'links', 'link' );

        $this->links_table = Helper::get_db_table_name( $this->instance_plural );
	}

	/**
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

    public function render_tabs() {
        $tabs = array(
            'general' => array(
                'name' => __( 'General', CLICKWHALE_NAME ),
                'url'  => 'general',
            )
        );

        return apply_filters( 'clickwhale_link_tabs', $tabs );
    }

    public function save_update(): void {
        global $wpdb;

        $item = array_intersect_key( $_POST, $this->get_defaults() );

		$item['categories'] = isset( $item['categories'] ) ? implode( ',', $item['categories'] ) : '';
		$item['nofollow']   = isset( $item['nofollow'] );
		$item['sponsored']  = isset( $item['sponsored'] );
		$item['author']     = get_current_user_id();

		// Check if item exists and then update or insert.
		// In some cases default check (not false or 0) goes wrong
		if ( Links_Helper::get_by_id( intval( $item['id'] ) ) ) {
			$wpdb->update(
                $this->links_table,
				$item,
				array( 'id' => $item['id'] )
			);

            do_action( 'clickwhale_link_updated', $item['id'], $_POST );
			$this->set_transient( $item['id'], 'updated' );

		} else {
            $wpdb->insert(
                $this->links_table,
                $item
            );

            $item['id'] = $wpdb->insert_id;
            do_action( 'clickwhale_link_inserted', $item['id'], $_POST );
            $this->set_transient( $item['id'], 'added' );
        }

        $url = 'admin.php?page=' . CLICKWHALE_SLUG . '-edit-link' . '&id=' . $item['id'];
        wp_redirect( admin_url( $url ) );
	}

	public function admin_scripts(): void {
		$nonce = wp_create_nonce( 'slug_exists' );

        if ( isset( $_GET['page'] ) && $_GET['page'] === CLICKWHALE_SLUG . '-edit-link' ) { ?>
            <script type='text/javascript'>
                jQuery(document).ready(function() {

                    <?php if ( isset( $_GET['id'] ) ) { ?>
                        const page_id = '<?php echo sanitize_text_field( intval( $_GET['id'] ) ); ?>';

                        if (localStorage.getItem('tab-' + page_id)) {
                            jQuery('#clickwhale-tabs').tabs({active: localStorage.getItem('tab-' + page_id)});
                        }

                        jQuery('#clickwhale-tabs li').on('click', function() {
                            localStorage.setItem('tab-' + page_id, jQuery(this).index());
                        });
                    <?php } ?>
                });
            </script>
        <?php } ?>

        <script type='text/javascript'>
            jQuery(document).ready(function() {
                let
                    submit = jQuery('#submit'),
                    form = submit.closest('form'),
                    title = jQuery('#title'),
                    slug = jQuery('#cw-slug'),
                    url = jQuery('#url');

                /* Slug */
				<?php
				/**
				 * If checked "Disable random slug" option
				 * use title as slug
				 */
				if ( Helper::get_clickwhale_option( 'general', 'random_slug' ) ) {
                    $slugOptionsGeneral = Helper::get_clickwhale_option( 'general', 'slug' )
                        ? trailingslashit( Helper::get_clickwhale_option( 'general', 'slug' ) )
                        : '';
                ?>
                    const slugOptionsGeneral = "<?php echo $slugOptionsGeneral; ?>";

                    jQuery(title).on('blur', function() {
                        if (!slug.val() || slug.val() === slugOptionsGeneral) {
                            slug.val(slugOptionsGeneral + this.value).trigger("blur");
                        }
                    });
                    jQuery(slug).on('blur', function() {
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
                 * 4. Check url (not null)
                 */
                form.on('submit', function(e) {
                    jQuery('#clickwhale-tabs').tabs('option', 'active', 0);

                    if (!title.val()) {
                        e.preventDefault();
                        generalTabNotValid();
                        title.addClass('error')
                            .next().text('<?php _e( 'Please enter title', CLICKWHALE_NAME ) ?>');
                        return false;
                    } else {
                        title.removeClass('error').next().text('');
                    }

                    if (!slug.val()) {
                        e.preventDefault();
                        generalTabNotValid();
                        slug.addClass('error')
                            .next().text('<?php _e( 'Please enter slug', CLICKWHALE_NAME ) ?>');
                        return false;
                    } else {
                        slug.removeClass('error').next().text('');
                    }

                    if (slugExists() === true) {
                        e.preventDefault();
                        generalTabNotValid();
                        slug.addClass('error')
                            .next().text('<?php _e( 'This slug is already in use! Please enter another slug', CLICKWHALE_NAME ) ?>');
                        return false;
                    } else {
                        slug.removeClass('error').next().text('');
                    }

                    if (!url.val()) {
                        e.preventDefault();
                        generalTabNotValid();
                        url.addClass('error')
                            .next().text('<?php _e( 'Please enter URL', CLICKWHALE_NAME ) ?>');
                        return false;
                    } else {
                        url.removeClass('error').next().text('');
                    }

                    submit.trigger('clickwhale.link.save', { formEvent: e });
                });

                /**
                 * JS FUNCTIONS
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
                            'type': 'link',
                            'slug': slug.val(),
                            'id': <?php echo esc_attr( intval( $_GET['id'] ?? 0 ) ); ?>
                        }, success: function(response) {
                            result = response.data;
                        }
                    });
                    return result;
                }

                function generalTabNotValid() {
                    // Delete previous `success` notice
                    jQuery('.updated').remove();

                    // Scroll page to top
                    jQuery('html, body').animate({ scrollTop: 0 }, 'fast');
                }
            });
        </script>
		<?php
    }
}