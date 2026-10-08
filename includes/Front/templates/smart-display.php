<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * @uses clickwhale\includes\front\smart_displays\Clickwhale_Smart_Display_Template $template
 */

$id             = $template->get_id();
$title          = $template->get_title();
$desc           = $template->get_description();
$disclosure     = $template->get_disclosure();
$price          = $template->get_price();
$image          = $template->get_image();
$primary_button = $template->get_primary_button();
?>
<div class="cw-smart-display-public--wrap" id="cw-smart-display-<?php echo $id; ?>">
    <?php if ( $image ) : ?>
        <div class="cw-smart-display-public--image"><?php echo $image; ?></div>
    <?php endif; ?>
    <div class="cw-smart-display-public--content">
        <div class="cw-smart-display-public--header">
            <div class="cw-smart-display-public--title"><?php echo $title; ?></div>
        </div>
        <div class="cw-smart-display-public--body">
            <?php if ( $desc ) : ?>
                <div class="cw-smart-display-public--description"><?php echo $desc; ?></div>
            <?php endif; ?>
        </div>
        <div class="cw-smart-display-public--footer">
            <?php if ( $price ) : ?>
                <div class="cw-smart-display-public--price"><?php echo $price; ?></div>
            <?php endif; ?>
            <div class="cw-smart-display-public--buttons">
                <?php
                echo $primary_button;
                do_action( 'clickwhale_smart_display_after_primary_button', $template );
                ?>
            </div>
            <?php if ( $disclosure ) : ?>
                <div class="cw-smart-display-public--disclosure"><?php echo $disclosure; ?></div>
            <?php endif; ?>
        </div>
        <?php echo $template->get_credits(); ?>
    </div>
</div>