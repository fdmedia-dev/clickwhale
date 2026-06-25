<?php
global $wpdb;

use Clickwhale\Front\SmartDisplays\Clickwhale_Smart_Display_Template;
use Clickwhale\Helpers\{
        Helper,
        Integrations_Helper,
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

/**
 * Integrations
 * @since 2.8.0
 */
$integrations_options = get_option( 'clickwhale_integrations_options', array() );
$amz_connect_defaults = Integrations_Helper::get_integration_default_options( 'amz_connect' );
$amz_connect_enabled  = Integrations_Helper::is_integration_enabled( 'amz_connect' );
$amz_connect_status   = Integrations_Helper::get_integration_status( 'amz_connect' );
$amz_connect_stores   = Integrations_Helper::get_amz_connect_stores();
$amz_connect_api_key  = Integrations_Helper::get_integration_api_key( 'amz_connect' );

// Per-smart-display AMZ Connect data (from smart_display_data table)
$sd_amz_data   = $item_id ? Smart_Displays_Helper::get_integration_data( $item_id, 'amz_connect' ) : array();
$sd_amz_params = ! empty( $sd_amz_data['params'] ) ? json_decode( $sd_amz_data['params'], true ) : array();
$sd_amz_active = ! empty( $sd_amz_data['is_active'] );
$sd_amz_asin   = $sd_amz_params['asin'] ?? '';
$sd_amz_store  = $sd_amz_params['store'] ?? ( $integrations_options['amz_connect']['fields']['default_store'] ?? 'com' );

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

    if ( $item_id && empty( $item['title'] ) ) {
        echo '<div class="notice notice-warning"><p>'
             . esc_html__( 'This display is inactive because required fields are empty. Fill in at least the Title to publish it.', 'clickwhale' )
             . '</p></div>';
    }

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
                            <label for="sd-name"><?php esc_html_e( 'Name', 'clickwhale' ); ?></label>
                        </th>
                        <td>
                            <?php
                            echo Helper::render_control(
                                    array(
                                            'control'     => 'input',
                                            'type'        => 'text',
                                            'id'          => 'sd-name',
                                            'name'        => 'name',
                                            'value'       => esc_attr( wp_unslash( $item['name'] ?? '' ) ),
                                            'placeholder' => esc_html__( 'Internal name for admin use', 'clickwhale' ),
                                            'required'    => true,
                                            'description' => esc_html__( 'Used in the admin list and block editor. Not shown on the frontend.', 'clickwhale' ),
                                    )
                            );
                            ?>
                        </td>
                    </tr>
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
                            <p id="sd-override-title-row"<?php if ( ! $sd_amz_active ) echo ' style="display:none;"'; ?>>
                                <label>
                                    <input type="checkbox"
                                           id="sd-override-title"
                                           name="sd_override[title]"
                                           value="1"
                                           <?php checked( ! empty( $options['override']['title'] ) ); ?>
                                    />
                                    <?php esc_html_e( 'Override with custom value', 'clickwhale' ); ?>
                                </label>
                            </p>
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
                    <tr class="form-field fullwidth-textarea">
                        <td colspan="2">
                            <label for="smart-display-description"><?php esc_html_e( 'Description', 'clickwhale' ); ?></label>
                            <p class="description "><?php echo esc_html__( 'Paste your content here', 'clickwhale' ) ?></p>
                            <textarea name="description"
                                      id="smart-display-description"
                                      rows="10"><?php echo esc_textarea( wp_unslash( $item['description'] ) ); ?></textarea>
                            <p id="sd-override-description-row"<?php if ( ! $sd_amz_active ) echo ' style="display:none;"'; ?>>
                                <label>
                                    <input type="checkbox"
                                           id="sd-override-description"
                                           name="sd_override[description]"
                                           value="1"
                                           <?php checked( ! empty( $options['override']['description'] ) ); ?>
                                    />
                                    <?php esc_html_e( 'Override with custom value', 'clickwhale' ); ?>
                                </label>
                            </p>
                        </td>
                    </tr>
                    <?php
                    echo Helper::render_control(
                            array(
                                    'row_label'   => esc_html__( 'Primary Button Text', 'clickwhale' ),
                                    'control'     => 'input',
                                    'type'        => 'text',
                                    'id'          => 'primary_text',
                                    'name'        => 'options[primary][text]',
                                    'value'       => esc_attr( wp_unslash( $options['primary']['text'] ?? '' ) ),
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

                <div id="sd-aside">
                    <?php if ( $amz_connect_enabled ) { ?>
                        <div id="sd-amz-connect" class="clickwhale-box sm">
                            <input type="hidden" name="integration" value="amz_connect">
                            <input type="hidden" id="amz-raw-data"
                                   name="sd_integrations[amz_connect][raw_data]"
                                   value="<?php echo esc_attr( $sd_amz_data['raw_data'] ?? '' ); ?>"
                            >
                            <input type="hidden" id="amz-is-set" name="sd_integrations[amz_connect][is_set]"
                                   value="<?php echo $sd_amz_active ? '1' : '0'; ?>">
                            <div class="clickwhale-box--header">
                                <div class="clickwhale-box--header-left">
                                    <div class="clickwhale-box--header-image">
                                        <img src="<?php echo $amz_connect_defaults['image'] ?>"
                                             alt="<?php echo $amz_connect_defaults['name'] ?>">
                                    </div>
                                    <div class="clickwhale-box--header-meta">
                                        <h2><?php echo $amz_connect_defaults['name'] ?></h2>
                                    </div>
                                </div>
                                <div class="clickwhale-box--header-right">
                                    <div class="clickwhale-api-status <?php echo $amz_connect_status ?> inline">
                                        <?php echo ucfirst( $amz_connect_status ) ?>
                                    </div>
                                </div>
                            </div>

                            <table class="form-table">
                                <tbody>
                                <tr class="form-field">
                                    <th scope="row">
                                        <label for="amz-asin"><?php esc_html_e( 'Product ASIN', 'clickwhale' ); ?></label>
                                    </th>
                                    <td>
                                        <input type="text"
                                               id="amz-asin"
                                               name="sd_integrations[amz_connect][asin]"
                                               value="<?php echo esc_attr( $sd_amz_asin ); ?>"
                                               placeholder="<?php esc_attr_e( 'e.g. B08MQZXN1X', 'clickwhale' ); ?>"
                                               style="width:300px;"
                                               autocomplete="off"
                                        >
                                    </td>
                                </tr>
                                <tr class="form-field">
                                    <th scope="row">
                                        <label for="amz-store"><?php esc_html_e( 'Store', 'clickwhale' ); ?></label>
                                    </th>
                                    <td>
                                        <select id="amz-store" name="sd_integrations[amz_connect][store]"
                                                style="width:300px;">
                                            <?php foreach ( $amz_connect_stores as $store_key => $store_info ) : ?>
                                                <option value="<?php echo esc_attr( $store_key ); ?>"
                                                        <?php selected( $store_key, $sd_amz_store ); ?>>
                                                    <?php echo esc_html( $store_info['flag'] . ' ' . $store_info['domain'] . ' (' . $store_info['name'] . ')' ); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <p class="description">
                                            <?php esc_html_e( 'Default from settings. Can be overridden per display.', 'clickwhale' ); ?>
                                        </p>
                                    </td>
                                </tr>
                                <tr>
                                    <th></th>
                                    <td>
                                        <div id="amz_actions">
                                            <button type="button" id="amz-fetch-btn" class="button button-primary">
                                                <?php esc_html_e( 'Fetch Product', 'clickwhale' ); ?>
                                            </button>
                                            <span id="amz-fetch-status" class="clickwhale-api-status inline"></span>
                                        </div>
                                    </td>
                                </tr>
                                <tr id="amz-use-toggle-row" <?php echo $sd_amz_active ? '' : 'style="display:none;"'; ?>>
                                    <th></th>
                                    <td>
                                        <label>
                                            <input type="checkbox" id="amz-use-toggle" <?php echo $sd_amz_active ? 'checked' : ''; ?> />
                                            <?php esc_html_e( 'Use Amazon product data', 'clickwhale' ); ?>
                                        </label>
                                    </td>
                                </tr>
                                <tr id="amz-link-url-row" style="display:none;">
                                    <th scope="row">
                                        <label for="amz-link-url-display"><?php esc_html_e( 'Product URL', 'clickwhale' ); ?></label>
                                    </th>
                                    <td>
                                        <input type="text" id="amz-link-url-display" readonly
                                               style="width:300px;background:#f6f7f7;color:#646970;"
                                               value="<?php echo esc_attr( $options['link_url'] ?? '' ); ?>"/>
                                    </td>
                                </tr>
                                </tbody>
                            </table>

                            <?php if ( ! empty( $sd_amz_data['updated_at'] ) ) { ?>
                                <div class="clickwhale-box--footer">
                                    <p class="description">
                                        <?php
                                        echo esc_html__( 'Product data fetched. Last sync:', 'clickwhale' ) . ' ';
                                        echo esc_html( wp_date( 'M j, Y, H:i', strtotime( $sd_amz_data['updated_at'] ) ) );
                                        ?>
                                    </p>
                                </div>
                            <?php } ?>
                        </div>
                    <?php } else { ?>
                        <div id="sd-amz-connect" class="notice notice-info inline">
                            <p><?php echo sprintf(
                                        __( 'AMZ Connect is not configured. Please check <a href="%s">Integrations Settings</a>', 'clickwhale' ),
                                        '?page=clickwhale-settings&tab=integrations_options' )
                                ?></p>
                        </div>
                    <?php } ?>

                    <div id="sd-preview">
                        <div id="sd-preview--header">
                            <h2><?php _e( 'Smart Display Preview', 'clickwhale' ); ?></h2>
                        </div>
                        <p id="cw-preview-placeholder"
                           style="display:none;color:#646970;font-style:italic;margin:.5rem 0 0;">
                            <?php esc_html_e( 'No display data yet. Fill in the fields above.', 'clickwhale' ); ?>
                        </p>
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
                </div>

                <div id="sd-actions">
                    <input type="hidden" id="amz-image-url" name="options[image_url]"
                           value="<?php echo esc_attr( $options['image_url'] ?? '' ); ?>">
                    <input type="hidden" id="amz-link-url" name="options[link_url]"
                           value="<?php echo esc_attr( $options['link_url'] ?? '' ); ?>">
                    <input type="hidden" id="sd-original-json" name="sd_original_json"
                           value="<?php echo esc_attr( wp_json_encode( $options['original'] ?? [] ) ); ?>">
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