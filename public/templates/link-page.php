<?php
global $wpdb, $post, $wp_query;

$title       = isset( $post->post_title ) ? wp_kses( $post->post_title, wp_kses_allowed_html( 'post' ) ) : '';
$description = isset( $post->post_content['description'] ) ? wp_kses( $post->post_content['description'], wp_kses_allowed_html( 'post' ) ) : '';
$links       = $post->post_content['links'];
$options     = get_option( 'clickwhale_general_options' );
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

		<?php if ( isset( $post->post_content['logo'] ) ) { ?>
            <div class="linkpage-public--logo custom-logo">
                <img src="<?php echo esc_url( $post->post_content['logo'] ); ?>" alt="">
            </div>
		<?php } else { ?>
            <div class="linkpage-public--logo default-logo">
                <img src="<?php get_bloginfo( 'url' ) ?>/wp-content/plugins/clickwhale/admin/images/click-whale.svg"
                     alt="">
            </div>
		<?php } ?>

		<?php if ( $title ) { ?>
            <div class="linkpage-public--title"><?php echo $title ?></div>
		<?php } ?>
		<?php if ( $description ) { ?>
            <div class="linkpage-public--description"><?php echo $description ?></div>
		<?php } ?>

		<?php if ( $links ) { ?>
            <div class="linkpage-public--links">
				<?php
				foreach ( $links as $link ) {
					$link_data = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}clickwhale_links WHERE id=%d", $link['id'] ), ARRAY_A );
					$url       = get_bloginfo( 'url' ) . '/' . $options['slug'] . '/' . $link_data['slug'];

					if ( $link_data ) {
						$link_title = $link['title'] ? $link['title'] : $link_data['title'];
						?>
                        <a href="<?php echo esc_url( $url ) ?>"><?php echo esc_html( $link_title ) ?></a>
					<?php } ?>
				<?php } ?>
            </div>
		<?php } ?>

    </div>
</div>
</body>
</html>

