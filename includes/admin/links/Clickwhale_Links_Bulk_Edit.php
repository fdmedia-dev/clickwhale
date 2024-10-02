<?php
namespace clickwhale\includes\admin\links;

use clickwhale\includes\helpers\{Links_Helper, Categories_Helper};

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Clickwhale_Links_Bulk_Edit {

    /**
     * @var array
     */
	private $posts;

    /**
     * @var int
     */
	protected $columns;

	public function __construct( array $posts, int $columns ) {
		$this->posts   = $posts;
		$this->columns = $columns;

		add_action( 'admin_print_footer_scripts', [ $this, 'admin_scripts' ] );
	}

	private function get_authors(): string {
		$authors_dropdown = '';

		if ( current_user_can( 'edit_others_posts' ) ) {
			$dropdown_name  = 'link_author';
			$dropdown_class = 'authors';
			if ( wp_is_large_user_count() ) {
				$authors_dropdown = sprintf(
					'<select name="%s" class="%s hidden"></select>',
					esc_attr( $dropdown_name ), esc_attr( $dropdown_class )
				);
			} else {
				$users_opt = array(
					'hide_if_only_one_author' => false,
					'capability'              => array( 'edit_posts' ),
					'name'                    => $dropdown_name,
					'class'                   => $dropdown_class,
					'multi'                   => 1,
					'echo'                    => 0,
					'show'                    => 'display_name_with_login',
					'show_option_none'        => __( '&mdash; No Change &mdash;' ),
				);

				$authors = wp_dropdown_users( $users_opt );

				if ( $authors ) {
					$authors_dropdown = '<label class="inline-edit-author">';
					$authors_dropdown .= '<span class="title">' . __( 'Author' ) . '</span>';
					$authors_dropdown .= $authors;
					$authors_dropdown .= '</label>';
				}
			}
		}

		return $authors_dropdown;
	}

	private function get_categories(): string {
		$categories = Categories_Helper::get_all();

		if ( ! $categories ) {
			return false;
		}

		$categories_output = '';

		$categories_output .= '<fieldset class="inline-edit-col-center inline-edit-categories">';
		$categories_output .= '<div class="inline-edit-col">';
		$categories_output .= '<span class="title inline-edit-categories-label">';
		$categories_output .= __( 'Categories', CLICKWHALE_NAME );
		$categories_output .= '</span>';
		$categories_output .= '<ul class="cat-checklist category-checklist">';
		foreach ( $categories as $category ) {
			$categories_output .= '<li id="category-' . $category->id . '">';
			$categories_output .= '<label class="selectit">';
			$categories_output .= '<input value="' . $category->id . '" type="checkbox" name="link_category[]" id="in-category-' . $category->id . '">';
			$categories_output .= ' ' . $category->title;
			$categories_output .= '</label>';
			$categories_output .= '</li>';
		}
		$categories_output .= '</ul>';
		$categories_output .= '</div>';
		$categories_output .= '</fieldset>';

		return $categories_output;
	}

	private function get_redirection(): string {
		$redirection_dropdown = '';
		$redirections         = array(
			301 => '301 Moved permanently',
			302 => '302 Found / Moved temporarily',
			303 => '303 See Other',
			307 => '307 Temporarily Redirect',
			308 => '308 Permanent Redirect',
		);

		$redirection_dropdown .= '<label class="inline-edit-redirection">';
		$redirection_dropdown .= '<span class="title">' . __( 'Redirection' ) . '</span>';
		$redirection_dropdown .= '<select name="redirection_status">';
		$redirection_dropdown .= '<option value="-1">' . __( '&mdash; No Change &mdash;' ) . '</option>';
		foreach ( $redirections as $k => $v ) {
			$redirection_dropdown .= '<option value="' . $k . '">' . $v . '</option>';
		}
		$redirection_dropdown .= '</select>';
		$redirection_dropdown .= '</label>';

		return $redirection_dropdown;
	}

	private function get_nofollow(): string {
		$nofollow_dropdown = '';
		$nofollow          = array(
			0 => 'Follow & Index',
			1 => 'Nofollow & Noindex'
		);

		$nofollow_dropdown .= '<label class="inline-edit-nofollow">';
		$nofollow_dropdown .= '<span class="title">' . __( 'Nofollow' ) . '</span>';
		$nofollow_dropdown .= '<select name="nofollow_status">';
		$nofollow_dropdown .= '<option value="-1">' . __( '&mdash; No Change &mdash;' ) . '</option>';
		foreach ( $nofollow as $k => $v ) {
			$nofollow_dropdown .= '<option value="' . $k . '">' . $v . '</option>';
		}
		$nofollow_dropdown .= '</select>';
		$nofollow_dropdown .= '</label>';

		return $nofollow_dropdown;
	}

	private function get_sponsored(): string {
		$sponsored_dropdown = '';
		$sponsored          = array(
			0 => 'No sponsored',
			1 => 'Sponsored'
		);

		$sponsored_dropdown .= '<label class="inline-edit-sponsored">';
		$sponsored_dropdown .= '<span class="title">' . __( 'Sponsored' ) . '</span>';
		$sponsored_dropdown .= '<select name="sponsored_status">';
		$sponsored_dropdown .= '<option value="-1">' . __( '&mdash; No Change &mdash;' ) . '</option>';
		foreach ( $sponsored as $k => $v ) {
			$sponsored_dropdown .= '<option value="' . $k . '">' . $v . '</option>';
		}
		$sponsored_dropdown .= '</select>';
		$sponsored_dropdown .= '</label>';

		return $sponsored_dropdown;
	}

	public function render_quick_edit() {
		ob_start();
		?>
        <tr class="hidden"></tr>
        <tr id="bulk-edit"
            class="inline-edit-row inline-edit-row-post bulk-edit-row bulk-edit-row-post bulk-edit-post inline-editor">
            <td class="colspanchange" colspan="<?php echo $this->columns ?>">
                <div class="inline-edit-wrapper" role="region" aria-labelledby="bulk-edit-legend" tabindex="-1">
                    <fieldset class="inline-edit-col-left">
                        <legend class="inline-edit-legend" id="bulk-edit-legend">
							<?php _e( 'Bulk Edit', CLICKWHALE_NAME ); ?>
                        </legend>
                        <div class="inline-edit-col">

                            <div id="bulk-title-div">
                                <div id="bulk-titles">

                                    <ul id="bulk-titles-list" role="list">
										<?php
										foreach ( $this->posts as $link_id ) {
											$link = Links_Helper::get_by_id( intval( $link_id ) );
											?>
                                            <li class="ntdelitem">
                                                <button type="button"
                                                        id="<?php echo $link['id'] ?>"
                                                        class="button-link ntdelbutton"></button>
                                                <span class="ntdeltitle" aria-hidden="true">
                                                    <?php echo $link['title'] ?>
                                                </span>
                                            </li>
										<?php } ?>
                                    </ul>

                                </div>
                            </div>

                        </div>
                    </fieldset>

					<?php echo $this->get_categories(); ?>

                    <fieldset class="inline-edit-col-right">
                        <label class="inline-edit-tags wp-clearfix">
                            <span class="title"><?php _e( 'Options', CLICKWHALE_NAME ); ?></span>
                        </label>
                        <div class="inline-edit-col">
							<?php
							echo $this->get_authors();
							echo $this->get_redirection();
							echo $this->get_nofollow();
							echo $this->get_sponsored();
							?>
                        </div>
                    </fieldset>


                    <div class="submit inline-edit-save">
                        <button type="submit" class="button button-primary" id="bulk_edit">
							<?php _e( 'Update', CLICKWHALE_NAME ); ?>
                        </button>
                        <button type="button" class="button cancel">
							<?php _e( 'Cancel', CLICKWHALE_NAME ); ?>
                        </button>

                        <div class="notice notice-error notice-alt inline hidden">
                            <p class="error"></p>
                        </div>
                    </div>
                </div>
            </td>
        </tr>
		<?php
		$output = ob_get_contents();
		ob_clean();

		return $output;
	}

	public function admin_scripts() {
		$nonce = wp_create_nonce( 'bulk_edit' );
		?>
        <script type='text/javascript'>
            jQuery(document).ready(function() {

                jQuery('#bulk-action-selector-top, #bulk-action-selector-bottom').val('edit');

                jQuery('#bulk-titles-list li').each(function() {
                    const link_id = (jQuery(this).find('button').attr('id'));
                    jQuery('.check-column input[type="checkbox"][value="' + link_id + '"]').prop('checked', true);
                });

                jQuery('#bulk-titles-list .button-link').on('click', function(e) {
                    e.preventDefault();
                    const selected_links = jQuery('#bulk-titles-list li').length;

                    if (selected_links - 1 > 0) {
                        jQuery(this).parent().remove();
                        jQuery('.check-column input[type="checkbox"][value="' + jQuery(this).attr('id') + '"]').prop('checked', false);
                    } else {
                        jQuery('#bulk-edit').remove();
                    }
                });

                jQuery('.inline-edit-save .cancel').on('click', function(e) {
                    e.preventDefault();
                    jQuery('#bulk-edit').remove();
                    jQuery('.check-column input[type="checkbox"]').prop('checked', false);

                    let url = window.location.href;

                    url = url
                        .replace('action=edit', 'action=-1')
                        .replace('action2=edit', 'action2=-1')
                        .replace(/&id[\d*]=\d*/gm, '')
                        .replace(/&id%5B\d*%5D=\d*/gm, '');

                    window.location.href = url;
                });
            });
        </script>
		<?php
	}
}