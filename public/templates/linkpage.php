<?php
global $post;

$linkpage = new Clickwhale_Public_Linkpage( $post );
$view     = new Clickwhale_View_Track( $post->post_content['id'] );
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="profile" href="https://gmpg.org/xfn/11">
    <title><?php echo $linkpage->get_title() ?></title>

	<?php wp_head(); ?>
	<?php echo $linkpage->get_styles() ?>
</head>

<body <?php body_class(); ?>>
<div class="linkpage-public--wrap">
    <div class="linkpage-public--inner">

        <div class="linkpage-public--logo"><?php echo $linkpage->get_logo(); ?></div>

        <div class="linkpage-public--title"><?php echo $linkpage->get_title() ?></div>

		<?php if ( $linkpage->get_description() ) { ?>
            <div class="linkpage-public--description"><?php echo $linkpage->get_description() ?></div>
		<?php } ?>

        <div class="linkpage-public--links"><?php echo $linkpage->get_links() ?></div>

		<?php if ( 'local' !== wp_get_environment_type() ) { ?>
			<?php if ( $linkpage->get_socails() ) { ?>
                <ul class="linkpage-public--social"><?php echo $linkpage->get_socails() ?></ul>
			<?php } ?>
		<?php } ?>

    </div>

	<?php echo $linkpage->get_copyright() ?>
</div>
<?php wp_footer(); ?>
</body>
</html>

