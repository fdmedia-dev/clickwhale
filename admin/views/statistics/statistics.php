<?php
$statistics = new ClickwhaleStatistics();
$statistics->init();

$promoShow = apply_filters( 'clickwhale_statistics_promo', $statistics->show_promo() );

do_action( 'clickwhale_admin_banner' );
?>

<div class="wrap clickwhale-statistics-wrap">

	<h1 class="wp-heading-inline"><?php _e( 'Statistics', 'clickwhale' ) ?></h1>
	<hr class="wp-header-end">

	<div class="clickwhale-statistics-content">

		<?php if ( $promoShow ) { ?>
			<style>
				.wrap {
					display: flex;
					flex-direction: column;
					align-items: center;
					text-align: center;
				}
			</style>

			<br>
			<img src="http://localhost:10021/wp-content/uploads/2022/11/linkpage-plc.png" width="150">
			<h1 class="wp-heading-inline"><?php echo esc_html( get_admin_page_title() ) ?></h1>
			<?php settings_errors(); ?>

			<p>Florian, we are waiting for you...</p>
		<?php } ?>

		<?php do_action( 'clickwhale_pro_statistics' ); ?>

	</div>
</div>