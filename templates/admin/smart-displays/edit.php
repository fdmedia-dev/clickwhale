<?php
global $wpdb;

use Clickwhale\Front\SmartDisplays\Clickwhale_Smart_Display_Template;
use Clickwhale\Helpers\{
        Helper,
        Links_Helper,
        Smart_Displays_Helper
};

Smart_Displays_Helper::get_limitation_error( $_GET['id'] );

$smart_display          = clickwhale()->smart_display;
$item                   = $smart_display->get_item( $_GET );
$item_id                = intval( $item['id'] );
$count                  = Smart_Displays_Helper::get_count();
$limit                  = Smart_Displays_Helper::get_limit();
$plugin_defaults        = clickwhale()->settings->default_options();
$smart_displays_options = get_option( 'clickwhale_smart_displays_options' );
$image_id               = esc_attr( $item['image'] ?? '' );
$links                  = Links_Helper::get_all( 'title', 'asc', ARRAY_A );
$link_id                = intval( $item['link_id'] ?? 0 );
$disclosure             = esc_html( $smart_displays_options['disclosure'] ?? '' );
$options                = maybe_unserialize( $item['options'] );
$price                  = $options['price'] ?? '';
do_action( 'clickwhale_admin_banner' );
?>
<style><?php echo Clickwhale_Smart_Display_Template::get_smart_display_styles(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></style>
<div class="wrap" id="smart-display-edit">
    <?php
    echo Helper::render_heading(
            array(
                    'name'         => esc_html__( 'Smart Display', 'clickwhale' ),
                    'is_edit'      => $item_id !== 0,
                    'link_to_list' => CLICKWHALE_SLUG . '-smart-displays',
                    'link_to_add'  => CLICKWHALE_SLUG . '-edit-smart-display',
                    'is_limit'     => $count >= $limit
            )
    );

    $smart_display->show_message( $item_id );

    do_action( 'clickwhale_admin_sidebar_begin' );
    ?>
    <form id="form_edit_<?php echo $smart_display->instance_single; ?>"
          class="clickwhale_form_edit"
          method="POST"
          action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
    >
        <input type="hidden" name="action"
               value="save_update_clickwhale_<?php echo $smart_display->instance_single; ?>"/>
        <input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce( basename( __FILE__ ) ) ); ?>"/>
        <input type="hidden" name="id" value="<?php echo $item_id; ?>"/>
        <div id="post-body-content">
            <div id="sd-tab">
                <table id="sd-table" style="width: 100%;" class="form-table">
                    <caption hidden><?php _e( 'Smart Display Settings', 'clickwhale' ); ?></caption>
                    <tbody>
                    <tr class="form-field">
                        <th scope="row">
                            <label for="title"><?php _e( 'Title', 'clickwhale' ); ?></label>
                        </th>
                        <td>
                            <?php
                            echo Helper::render_control(
                                    array(
                                            'control'     => 'input',
                                            'type'        => 'text',
                                            'id'          => 'title',
                                            'name'        => 'title',
                                            'value'       => esc_attr( wp_unslash( $item['title'] ) ),
                                            'placeholder' => esc_html__( 'Smart Display Title', 'clickwhale' ),
                                            'required'    => true
                                    )
                            );
                            ?>
                            <p id="cw-title--description"></p>
                            <?php
                            if ( $item_id !== 0 ) {
                                ?>
                                <p id="cw-shortcode--text"
                                   class="code"
                                   title="<?php esc_attr_e( 'Copy shortcode', 'clickwhale' ); ?>"
                                ><?php echo esc_html__( 'Shortcode', 'clickwhale' ) . ': '; ?><span id="cw-shortcode"><?php
                                        echo esc_html( Smart_Displays_Helper::get_shortcode( $item_id ) ); ?></span>
                                    <svg class="feather">
                                        <use href="<?php echo CLICKWHALE_ADMIN_ASSETS_DIR; ?>/images/feather-sprite.svg#copy"></use>
                                    </svg>
                                </p>
                                <?php
                            }
                            ?>
                        </td>
                    </tr>
                    <tr class="form-field">
                        <th scope="row">
                            <label for="image"><?php esc_html_e( 'Image', 'clickwhale' ); ?></label>
                        </th>
                        <td>
                            <div class="cw-smart-display-field image-field">
                                <?php
                                $logo_src = wp_get_attachment_image_url( $image_id );
                                if ( $image_id && $logo_src ) { ?>
                                    <a href="#"
                                       class="cw-smart-display-image-upload"
                                    ><img alt="cw-smart-display-image" src="<?php echo esc_url( $logo_src ); ?>"/></a>
                                    <a href="#"
                                       class="button cw-smart-display-image-remove"
                                    ><?php esc_html_e( 'Remove image', 'clickwhale' ); ?></a>
                                    <input type="hidden"
                                           id="image"
                                           name="image"
                                           value="<?php echo $image_id; ?>"
                                    />
                                <?php } else { ?>
                                    <a href="#"
                                       class="button cw-smart-display-image-upload"
                                    ><?php esc_html_e( 'Upload image', 'clickwhale' ); ?></a>
                                    <a href="#"
                                       class="button cw-smart-display-image-remove"
                                       style="display: none;"
                                    ><?php esc_html_e( 'Remove image', 'clickwhale' ); ?></a>
                                    <input type="hidden"
                                           id="image"
                                           name="image"
                                           value=""
                                    />
                                <?php } ?>
                            </div>
                            <p><?php esc_html_e( 'Please use a ratio of 1:1', 'clickwhale' ); ?></p>
                        </td>
                    </tr>
                    <tr class="form-field">
                        <th scope="row">
                            <label for="link_id"><?php esc_html_e( 'Link', 'clickwhale' ); ?></label>
                        </th>
                        <td>
                            <select id="link_id"
                                    name="link_id"
                                    class="select-link"
                            >
                                <option value="0"><?php esc_html_e( 'Select Link', 'clickwhale' ); ?></option>
                                <?php foreach ( $links as $link ) {
                                    $value = esc_attr( $link['id'] );
                                    ?>
                                    <option value="<?php echo $value; ?>"
                                            data-title="<?php echo esc_attr( $link['title'] ); ?>"
                                            data-url="<?php echo esc_url( $link['url'] ); ?>"
                                            <?php selected( $link_id, $value ); ?>
                                    ><?php echo esc_html( $link['title'] ) . ' (' . esc_html( $link['url'] ) . ')'; ?></option>
                                <?php } ?>
                            </select>
                        </td>
                    </tr>
                    <?php
                    echo Helper::render_control(
                            array(
                                    'row_label'   => esc_html__( 'Description', 'clickwhale' ),
                                    'control'     => 'textarea',
                                    'id'          => 'smart-display-description',
                                    'name'        => 'description',
                                    'value'       => esc_textarea( wp_unslash( $item['description'] ) ),
                                    'description' => esc_html__( 'Paste your content here', 'clickwhale' )
                            ),
                            true
                    );
                    echo Helper::render_control(
                            array(
                                    'row_label' => esc_html__( 'Title and Image as Link', 'clickwhale' ),
                                    'control'   => 'checkbox',
                                    'id'        => 'is-title-linked',
                                    'name'      => 'options[is_title_linked]',
                                    'value'     => ! empty( $options['is_title_linked'] ) ? 1 : 0,
                                    'label'     => esc_html__( 'Should the title and image be shown as a link?', 'clickwhale' )
                            ),
                            true
                    );
                    echo Helper::render_control(
                            array(
                                    'row_label'   => esc_html__( 'Primary Button Text', 'clickwhale' ),
                                    'control'     => 'input',
                                    'type'        => 'text',
                                    'id'          => 'primary_text',
                                    'name'        => 'options[primary][text]',
                                    'value'       => esc_attr( wp_unslash( $options['primary']['text'] ) ),
                                    'placeholder' => esc_html( $smart_displays_options['primary']['text'] ),
                                    'description' => esc_html__( 'Set primary button text (leave blank to use default value)', 'clickwhale' )
                            ),
                            true
                    );
                    echo Helper::render_control(
                            array(
                                    'row_label'   => esc_html__( 'Price', 'clickwhale' ),
                                    'control'     => 'input',
                                    'type'        => 'text',
                                    'id'          => 'price',
                                    'name'        => 'options[price]',
                                    'value'       => esc_attr( $price ),
                                    'placeholder' => esc_html__( 'e.g. $1999.99 or 1999.99 USD', 'clickwhale' ),
                                    'description' => esc_html__( 'Set price (leave blank to omit)', 'clickwhale' )
                            ),
                            true
                    );
                    do_action( 'clickwhale_smart_display_after_primary_fields', $item ); ?>
                    </tbody>
                </table>

                <div id="sd-preview">
                    <h2><?php _e( 'Smart Display Preview', 'clickwhale' ); ?></h2>
                    <div class="cw-smart-display-preview--wrap">
                        <div class="cw-smart-display-preview">
                            <div class="cw-smart-display-preview--image"></div>
                            <div class="cw-smart-display-preview--content">
                                <div class="cw-smart-display-preview--header">
                                    <div class="cw-smart-display-preview--title"></div>
                                </div>
                                <div class="cw-smart-display-preview--body">
                                    <div class="cw-smart-display-preview--description"></div>
                                </div>
                                <div class="cw-smart-display-preview--footer">
                                    <div class="cw-smart-display-preview--price"><?php echo esc_html( $price ); ?></div>
                                    <div class="cw-smart-display-preview--buttons"></div>
                                    <div class="cw-smart-display-preview--disclosure"><?php echo $disclosure; ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="sd-actions">
                    <input type="hidden"
                           id="created_at"
                           name="created_at"
                           value="<?php echo esc_attr( $item['created_at'] ); ?>"
                    />
                    <?php if ( $count < $limit ) { ?>
                        <input type="submit"
                               id="submit"
                               name="submit"
                               class="button-primary"
                               value="<?php esc_attr_e( 'Save', 'clickwhale' ); ?>"
                        />
                    <?php } ?>
                </div>
            </div>
        </div>
    </form>
    <?php do_action( 'clickwhale_admin_sidebar_end' ); ?>
</div>