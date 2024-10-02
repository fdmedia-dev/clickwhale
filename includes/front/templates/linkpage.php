<?php

use clickwhale\includes\front\Clickwhale_Public_Linkpage;
use clickwhale\includes\front\tracking\Clickwhale_View_Track;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $post;

$linkpage = new Clickwhale_Public_Linkpage( $post );
$view     = new Clickwhale_View_Track( $post->linkpage['id'] );
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="profile" href="https://gmpg.org/xfn/11">

	<?php wp_head(); ?>
	<?php echo $linkpage->get_styles(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<div class="linkpage-public--wrap">
    <div class="linkpage-public--inner">

        <div class="linkpage-public--logo"><?php echo $linkpage->get_logo(); ?></div>

        <div class="linkpage-public--title"><?php echo $linkpage->get_title(); ?></div>

		<?php if ( $linkpage->get_desc() ) { ?>
            <div class="linkpage-public--description"><?php echo $linkpage->get_desc(); ?></div>
		<?php } ?>

        <div class="linkpage-public--links"><?php echo $linkpage->get_links(); ?></div>

    </div>
    <div class="linkpage-public--bottom">
		<?php
		if ( $linkpage->get_legals_menu() ) {
			echo $linkpage->get_legals_menu();
		}
		echo $linkpage->get_credits_link();
		?>
    </div>
</div>
<?php wp_footer(); ?>
</body>
</html>
