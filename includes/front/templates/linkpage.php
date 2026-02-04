<?php

use clickwhale\includes\front\Clickwhale_Public_Linkpage;
use clickwhale\includes\front\tracking\Clickwhale_View_Track;
use clickwhale\includes\helpers\Helper;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $post;
$linkpage = new Clickwhale_Public_Linkpage( $post );
$view = new Clickwhale_View_Track();
$view->maybe_update_track_database( $post->linkpage['id'] );
$user_id = get_current_user_id();
?>
<!DOCTYPE html>
<html lang="<?php bloginfo( 'language' ); ?>">
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="profile" href="https://gmpg.org/xfn/11">
    <link rel="canonical" href="<?php echo esc_url( $linkpage->get_url() ); ?>">
    <?php wp_head(); ?>
    <style><?php echo esc_html( $linkpage->get_styles() ); ?></style>
    <?php do_action( 'clickwhale/link_page_head', $post->linkpage, $post->linkpage['id'], $user_id ); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<div class="cw-linkpage-public--wrap">
    <div class="cw-linkpage-public--top">
        <div class="cw-linkpage-public--header">
            <?php echo wp_kses( $linkpage->get_logo(), Helper::get_allowed_tags() ); ?>
            <div class="cw-linkpage-public--title"><?php echo esc_html( $linkpage->get_title() ); ?></div>
            <?php if ( $linkpage->get_desc() ) { ?>
                <div class="cw-linkpage-public--description"><?php echo wp_kses_post( $linkpage->get_desc() ); ?></div>
            <?php } ?>
        </div>
        <div class="cw-linkpage-public--links"><?php echo wp_kses( $linkpage->get_links(), Helper::get_allowed_tags() ); ?></div>
    </div>
    <div class="cw-linkpage-public--bottom">
        <?php
        if ( $linkpage->get_legals_menu() ) {
            echo wp_kses( $linkpage->get_legals_menu(), Helper::get_allowed_tags() );
        }
        echo wp_kses( $linkpage->get_credits_link(), Helper::get_allowed_tags() );
        ?>
    </div>
</div>
<?php wp_footer(); ?>
<?php do_action( 'clickwhale/link_page_footer', $post->linkpage, $post->linkpage['id'], $user_id ); ?>
</body>
</html>