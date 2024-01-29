<?php
namespace clickwhale_pro\includes\admin\statistics;

class Clickwhale_Pro_Clicks_Statistics {

	public static $withClicks;

	public function __construct() {
		self::$withClicks = $this->get_links_with_clicks();
	}

	public static function get_links_with_clicks() {
		global $wpdb;

		return $wpdb->get_results(
			"SELECT track.link_id AS id, COUNT(*) count, links.id as item_id, links.title, links.url
					FROM {$wpdb->prefix}clickwhale_track track
					LEFT JOIN {$wpdb->prefix}clickwhale_links links 
					ON track.link_id = links.id
					WHERE track.event_type='click' AND track.link_id != '0'
					GROUP BY track.link_id
					ORDER BY id desc",
			ARRAY_A
		);
	}

	private function get_filter_links_select( $links ) {
		if ( ! $links ) {
			return false;
		}

		$options = '<option value="0" selected>' . __( 'All', CLICKWHALE_PRO_SLUG ) . '</option>';
		foreach ( $links as $link ) {
			if ( is_null( $link['item_id'] ) ) {
				$link['title'] = __( 'Unknown Link', CLICKWHALE_PRO_SLUG );
			}
			$options .= '<option value="' . $link['id'] . '">' . wp_unslash( $link['title'] ) . ' (id: ' . $link['id'] . ')</option>';
		}

		return '<select id="clickwhaleLinkSelect" multiple="multiple" class="clickwhale-select">' . $options . '</select>';
	}

	private function get_link_row( array $link ) {
		if ( ! $link ) {
			return false;
		}

		$rowID         = 'data-row="' . $link['id'] . '"';
		$cellTitle     = is_null( $link['item_id'] )
			? '<td>' . __( 'Unknown Link', CLICKWHALE_PRO_SLUG ) . ' (id: ' . $link['id'] . ')' . '</td>'
			: '<td>' . wp_unslash( $link['title'] ) . ' (id: ' . $link['id'] . ')' . '</td>';
		$cellURL       = is_null( $link['item_id'] )
			? '<td></td>'
			: '<td><small>' . $link['url'] . '</small></td>';
		$editTooltip   = __( 'Edit link', CLICKWHALE_PRO_SLUG );
		$statTooltip   = __( 'Select link for the statistics', CLICKWHALE_PRO_SLUG );
		$removeTooltip = __( 'Remove link from the statistics', CLICKWHALE_PRO_SLUG );
		$editURL       = esc_url( admin_url( 'admin.php?page=clickwhale-edit-link&id=' . $link['id'] ) );
		$edit          = is_null( $link['item_id'] )
			? ''
			: '<a href="' . $editURL . '" target="_blank" rel="noopener" title="' . $editTooltip . '"><svg class="feather"><use href="' . CLICKWHALE_ADMIN_ASSETS_DIR . '/images/feather-sprite.svg#edit-2"></use></svg></a>';
		$stat          = '<button type="button" data-type="click" data-action="add" data-id="' . $link['id'] . '" title="' . $statTooltip . '"><svg class="feather"><use href="' . CLICKWHALE_ADMIN_ASSETS_DIR . '/images/feather-sprite.svg#bar-chart-2"></use></svg></button>';
		$remove        = '<button type="button" data-type="click" data-action="remove" data-id="' . $link['id'] . '" title="' . $removeTooltip . '"><svg class="feather"><use href="' . CLICKWHALE_ADMIN_ASSETS_DIR . '/images/feather-sprite.svg#x"></use></svg></button>';
		$sellActions   = '<td class="statistics-table-actions">' . $edit . $stat . $remove . '</td>';
		$cellClicks    = '<td class="clickwhale-statistics---clicks">' . $link['count'] ?: 0 . '</td>';

		return '<tr ' . $rowID . '>' . $cellTitle . $cellURL . $cellClicks . $sellActions . '</tr>';
	}

	private function get_links_table( $links ) {
		if ( ! $links ) {
			return false;
		}
		$rows  = '';
		$thead = '';

		$thead .= '<thead><tr>';
		$thead .= '<th>' . __( 'Link', CLICKWHALE_PRO_SLUG ) . '</th>';
		$thead .= '<th>' . __( 'URL', CLICKWHALE_PRO_SLUG ) . '</th>';
		$thead .= '<th width="100">' . __( 'Clicks', CLICKWHALE_PRO_SLUG ) . '</th>';
		$thead .= '<th width="100"></th>';
		$thead .= '</tr></thead>';

		foreach ( $links as $link ) {
			$rows .= $this->get_link_row( $link );
		}
		$tfoot = '<tfoot><tr><th colspan="4">' . __( 'Nothing to display', CLICKWHALE_PRO_SLUG ) . '</th></tr></tfoot>';

		return '<table id="clickwhaleClicksTable" class="wp-list-table">' . $thead . $rows . $tfoot . '</table>';
	}

	public function show_statistics() {
		$links_select = $this->get_filter_links_select( self::$withClicks );
		$periods      = Clickwhale_Pro_Statistics::statistic_periods();
		?>

        <div class="clickwhale-statistics--filters">

            <div class="clickwhale-statistics--filter filter-list filter-links">
                <h4 class="wp-heading-inline"><?php _e( 'Select Link', CLICKWHALE_PRO_SLUG ) ?></h4>
				<?php echo $links_select ?>
            </div>

            <div class="clickwhale-statistics--filter filter-period">
                <h4 class="wp-heading-inline"><?php _e( 'Period', CLICKWHALE_PRO_SLUG ) ?></h4>
				<?php if ( $periods ) { ?>
                    <select id="clickwhaleClicksPeriod" class="clickwhale-select">
						<?php foreach ( $periods as $k => $period ) { ?>
                            <option value="<?php echo $k ?>"
								<?php echo isset( $period['start'] ) ? 'data-start="' . $period['start'] . '"' : '' ?>
								<?php echo isset( $period['end'] ) ? 'data-end="' . $period['end'] . '"' : '' ?>
								<?php selected( isset( $period['selected'] ) && $period['selected'] ) ?>
                            >
								<?php echo $period['label'] ?>
                            </option>
						<?php } ?>
                    </select>
				<?php } ?>
                <div id="clickwhaleClicksCustomPeriod" class="clickwhale-statistics--custom-period">
                    <input type="text"
                           id="clickwhaleClicksCustomPeriodStart"
                           class="clickwhale-statistics--custom-period--start"
                           min="2022-12-11"
                           placeholder="<?php _e( 'Start Date', CLICKWHALE_PRO_SLUG ) ?>">
                    <input type="text"
                           id="clickwhaleClicksCustomPeriodEnd"
                           class="clickwhale-statistics--custom-period--end"
                           placeholder="<?php _e( 'End Date', CLICKWHALE_PRO_SLUG ) ?>">
                    <button class="button" type="button"><?php _e( 'Filter', CLICKWHALE_PRO_SLUG ) ?></button>
                </div>
            </div>
        </div>

        <div class="clickwhale-statistics--grid clickwhale-statistics--cards">
            <div class="clickwhale-statistics--item clickwhale-statistics--card" id="clicksPerToday">
                <p><?php _e( 'Clicks for today', CLICKWHALE_PRO_SLUG ) ?></p>
                <div class="clickwhale-statistics--card-value">
                    <h4>0</h4>
                    <span>0%</span><?php _e( 'from yesterday', CLICKWHALE_PRO_SLUG ) ?>
                </div>
            </div>
            <div class="clickwhale-statistics--item clickwhale-statistics--card" id="clicksPerWeek">
                <p><?php _e( 'Clicks for this week', CLICKWHALE_PRO_SLUG ) ?></p>
                <div class="clickwhale-statistics--card-value">
                    <h4>0</h4>
                    <span>0%</span><?php _e( 'from last week', CLICKWHALE_PRO_SLUG ) ?>
                </div>
            </div>
            <div class="clickwhale-statistics--item clickwhale-statistics--card" id="clicksPerMonth">
                <p><?php _e( 'Clicks for this month', CLICKWHALE_PRO_SLUG ) ?></p>
                <div class="clickwhale-statistics--card-value">
                    <h4>0</h4>
                    <span>0%</span><?php _e( 'from last month', CLICKWHALE_PRO_SLUG ) ?>
                </div>
            </div>
        </div>

        <div class="clickwhale-statistics--grid clickwhale-statistics--charts">
            <div class="clickwhale-statistics--item">
                <div class="clickwhale-statistics--item-header">
					<?php _e( 'Link Clicks Overview', CLICKWHALE_PRO_SLUG ); ?>
                </div>

                <div class="clickwhale-statistics--chart-clicks">
                    <canvas id="clickwhaleClicksChart"></canvas>
                </div>
            </div>
            <div class="clickwhale-statistics--item">
                <div class="clickwhale-statistics--item-header">
					<?php _e( 'Most Clicked Links', CLICKWHALE_PRO_SLUG ); ?>
                </div>
                <div class="clickwhale-statistics--chart-most-clicked">
                    <canvas id="clickwhaleMostClickedChart"></canvas>
                </div>
            </div>
        </div>

        <div class="clickwhale-statistics--item">
            <div class="clickwhale-statistics--item-header">
				<?php _e( 'Total Links Clicks', CLICKWHALE_PRO_SLUG ); ?>
            </div>
            <div class="clickwhale-statistics--table">
				<?php echo $this->get_links_table( self::$withClicks ); ?>
            </div>
        </div>

		<?php
	}
}