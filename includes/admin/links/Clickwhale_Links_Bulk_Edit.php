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
    private array $posts;

    /**
     * @var int
     */
    protected int $columns;

    public function __construct( array $posts, int $columns ) {
        $this->posts = $posts;
        $this->columns = $columns;

        add_action( 'admin_print_footer_scripts', array( $this, 'admin_scripts' ) );
    }

    private function get_categories(): string {
        $categories = Categories_Helper::get_all();

        if ( ! $categories ) {
            return false;
        }

        $output = '<fieldset class="inline-edit-col-center inline-edit-categories">';
        $output .= '<div class="inline-edit-col">';
        $output .= '<span class="title inline-edit-categories-label">';
        $output .= __( 'Categories', 'clickwhale' );
        $output .= '</span>';
        $output .= '<ul class="cat-checklist category-checklist">';

        foreach ( $categories as $category ) {
            $category_id = intval( $category->id );
            $output .= '<li id="category-' . $category_id . '">';
            $output .= '<label class="selectit">';
            $output .= '<input value="' . $category_id . '" type="checkbox" name="link_category[]" id="in-category-' . $category_id . '">';
            $output .= ' ' . esc_html( $category->title );
            $output .= '</label>';
            $output .= '</li>';
        }

        $output .= '</ul>';
        $output .= '</div>';
        $output .= '</fieldset>';

        return $output;
    }

    private function get_redirection(): string {
        $redirections = Links_Helper::get_redirections();

        $output = '<label class="inline-edit-redirection">';
        $output .= '<span class="title">' . __( 'Redirection', 'clickwhale' ) . '</span>';
        $output .= '<select name="redirection_status">';
        $output .= '<option value="-1">&mdash; ' . __( 'No Change', 'clickwhale' ) . ' &mdash;</option>';

        foreach ( $redirections as $k => $v ) {
            $output .= '<option value="' . $k . '">' . $v . '</option>';
        }

        $output .= '</select>';
        $output .= '</label>';

        return $output;
    }

    private function get_link_target(): string {
        $link_targets = array_merge(
            array( '' => __( 'Default', 'clickwhale' ) ),
            Links_Helper::get_link_targets()
        );

        $output = '<label class="inline-edit-link-target">';
        $output .= '<span class="title">' . __( 'Link Target', 'clickwhale' ) . '</span>';
        $output .= '<select name="link_target_status">';
        $output .= '<option value="-1">&mdash; ' . __( 'No Change', 'clickwhale' ) . ' &mdash;</option>';

        foreach ( $link_targets as $k => $v ) {
            $output .= '<option value="' . $k . '">' . $v . '</option>';
        }

        $output .= '</select>';
        $output .= '</label>';

        return $output;
    }

    private function get_nofollow(): string {
        $nofollow = array(
            0 => 'Follow & Index',
            1 => 'Nofollow & Noindex'
        );

        $output = '<label class="inline-edit-nofollow">';
        $output .= '<span class="title">' . __( 'Nofollow', 'clickwhale' ) . '</span>';
        $output .= '<select name="nofollow_status">';
        $output .= '<option value="-1">&mdash; ' . __( 'No Change', 'clickwhale' ) . ' &mdash;</option>';

        foreach ( $nofollow as $k => $v ) {
            $output .= '<option value="' . $k . '">' . $v . '</option>';
        }

        $output .= '</select>';
        $output .= '</label>';

        return $output;
    }

    private function get_sponsored(): string {
        $sponsored = array(
            0 => 'No sponsored',
            1 => 'Sponsored'
        );

        $output = '<label class="inline-edit-sponsored">';
        $output .= '<span class="title">' . __( 'Sponsored', 'clickwhale' ) . '</span>';
        $output .= '<select name="sponsored_status">';
        $output .= '<option value="-1">&mdash; ' . __( 'No Change', 'clickwhale' ) . ' &mdash;</option>';

        foreach ( $sponsored as $k => $v ) {
            $output .= '<option value="' . $k . '">' . $v . '</option>';
        }

        $output .= '</select>';
        $output .= '</label>';

        return $output;
    }

    public function render_quick_edit(): string {
        ob_start();
        ?>
        <tr class="hidden"></tr>
        <tr id="bulk-edit"
            class="inline-edit-row inline-edit-row-post bulk-edit-row bulk-edit-row-post bulk-edit-post inline-editor">
            <td class="colspanchange" colspan="<?php echo esc_attr( $this->columns ); ?>">
                <div class="inline-edit-wrapper" role="region" aria-labelledby="bulk-edit-legend" tabindex="-1">
                    <fieldset class="inline-edit-col-left">
                        <legend class="inline-edit-legend" id="bulk-edit-legend">
                            <?php _e( 'Bulk Edit', 'clickwhale' ); ?>
                        </legend>
                        <div class="inline-edit-col">
                            <div id="bulk-title-div">
                                <div id="bulk-titles">
                                    <ul id="bulk-titles-list" role="list">
                                        <?php
                                        foreach ( $this->posts as $link_id ) {
                                            $link = Links_Helper::get_by_id( intval( $link_id ) );
                                            if ( $link ) { ?>
                                                <li class="ntdelitem">
                                                    <button type="button"
                                                            id="<?php echo esc_attr( $link['id'] ); ?>"
                                                            class="button-link ntdelbutton"></button>
                                                    <span class="ntdeltitle" aria-hidden="true"><?php echo esc_html( $link['title'] ); ?></span>
                                                </li>
                                            <?php } ?>
                                        <?php } ?>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </fieldset>

                    <?php echo $this->get_categories(); ?>

                    <fieldset class="inline-edit-col-right">
                        <label class="inline-edit-tags wp-clearfix">
                            <span class="title"><?php _e( 'Options', 'clickwhale' ); ?></span>
                        </label>
                        <div class="inline-edit-col">
                        <?php
                            echo $this->get_redirection();
                            echo $this->get_link_target();
                            echo $this->get_nofollow();
                            echo $this->get_sponsored();
                        ?>
                        </div>
                    </fieldset>

                    <div class="submit inline-edit-save">
                        <button type="submit" class="button button-primary" id="bulk_edit">
                            <?php _e( 'Update', 'clickwhale' ); ?>
                        </button>
                        <button type="button" class="button cancel">
                            <?php _e( 'Cancel', 'clickwhale' ); ?>
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
        ?>
        <script type='text/javascript'>
            jQuery(document).ready(function(){
                jQuery('#bulk-action-selector-top, #bulk-action-selector-bottom').val('edit');

                jQuery('#bulk-titles-list li').each(function(){
                    const link_id = (jQuery(this).find('button').attr('id'));
                    jQuery('.check-column input[type="checkbox"][value="' + link_id + '"]').prop('checked', true);
                });

                jQuery('#bulk-titles-list .button-link').on('click', function(e){
                    e.preventDefault();
                    const selected_links = jQuery('#bulk-titles-list li').length;

                    if (selected_links - 1 > 0){
                        jQuery(this).parent().remove();
                        jQuery('.check-column input[type="checkbox"][value="' + jQuery(this).attr('id') + '"]').prop('checked', false);
                    } else {
                        jQuery('#bulk-edit').remove();
                    }
                });

                jQuery('.inline-edit-save .cancel').on('click', function(e){
                    e.preventDefault();
                    jQuery('#bulk-edit').remove();
                    jQuery('.check-column input[type="checkbox"]').prop('checked', false);

                    window.location.href = <?php echo wp_json_encode( add_query_arg( array(
                        'action' => '-1',  // replace `action`
                        'action2' => '-1', // replace `action2`
                        'id' => false      // remove `id`
                    ) ) ); ?>;
                });
            });
        </script>
        <?php
    }
}
