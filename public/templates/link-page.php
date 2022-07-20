<?php
global $post, $wp_query;

$logo        = isset( $post->post_content['logo'] ) ? $post->post_content['logo'] : get_bloginfo( 'url' ) . '/wp-content/plugins/clickwhale/admin/images/click-whale.svg';
$logo_class  = isset( $post->post_content['logo'] ) ? 'custom-logo' : 'default-logo';
$title       = $post->post_title;
$description = $post->post_content['description'];
$links       = maybe_unserialize( $post->post_content['links'] );
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>

    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="profile" href="https://gmpg.org/xfn/11">
    <title><?php echo $title ?></title>

	<?php wp_head(); ?>

</head>

<body <?php body_class(); ?>>
<div class="linkpage-public--wrap">
    <div class="linkpage-public--inner">
        <div class="linkpage-public--logo <?php echo $logo_class ?>">
            <img src="<?php echo esc_url( $logo ); ?>" alt="">
        </div>
        <div class="linkpage-public--title"><?php echo $title ?></div>
        <div class="linkpage-public--description"><?php echo $description ?></div>

		<?php if ( $links ) { ?>
            <div class="linkpage-public--links">
				<?php
				foreach ( $links as $link ) {
					$link_data = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}clickwhale_links WHERE id=%d", $link['id'] ), ARRAY_A );
					if ( $link_data ) {
						$link_title = $link['title'] ? $link['title'] : $link_data['title'];
						?>
                        <a href="<?php echo esc_url( $link_data['slug'] ) ?>"><?php echo esc_html( $link_title ) ?></a>
					<?php } ?>
				<?php } ?>
            </div>
		<?php } ?>

    </div>
</div>
</body>
</html>

