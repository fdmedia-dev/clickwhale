<?php

use clickwhale_pro\includes\admin\statistics\Clickwhale_Pro_Statistics;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$statistics = new Clickwhale_Pro_Statistics();

do_action( 'clickwhale_admin_banner' );
?>

<div class="wrap clickwhale-statistics-wrap">

	<h1 class="wp-heading-inline"><?php _e( 'Statistics', CLICKWHALE_PRO_NAME ) ?></h1>
	<hr class="wp-header-end">

	<div class="clickwhale-statistics-content">
		<div id="clickwhale-tabs--statistics" class="clickwhale-tabs">
			<ul>
				<li><a href="#lp-tab-links"><?php _e( 'Links', CLICKWHALE_PRO_NAME ) ?></a></li>
				<li><a href="#lp-tab-linkpages"><?php _e( 'Linkpages', CLICKWHALE_PRO_NAME ) ?></a></li>
			</ul>

			<div id="lp-tab-linkpages">
				<div class="clickwhale-tab">
					<?php $statistics->viewsStatistics->show_statistics(); ?>
				</div>
			</div>
			<div id="lp-tab-links">
				<div class="clickwhale-tab">
					<?php $statistics->clicksStatistics->show_statistics(); ?>
				</div>
			</div>
		</div>
	</div>
</div>