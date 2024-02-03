<?php
namespace clickwhale_pro\includes\admin\statistics;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Clickwhale_Pro_Views_Statistics {

	/**
	 * @var false|string
	 */
	public static $withViews;

	public function __construct() {
		self::$withViews = $this->get_linkpages_with_views();
	}

	public static function get_linkpages_with_views() {
		global $wpdb;

		return $wpdb->get_results(
			"SELECT track.linkpage_id as id, COUNT(*) count, linkpages.id AS item_id, linkpages.title
					FROM {$wpdb->prefix}clickwhale_track track
					LEFT JOIN {$wpdb->prefix}clickwhale_linkpages linkpages 
					ON track.linkpage_id = linkpages.id
					WHERE track.event_type='view'
					GROUP BY track.linkpage_id
					ORDER BY id desc",
			ARRAY_A
		);
	}

	private function get_filter_linkpages_select( $linkpages ) {
		if ( ! $linkpages ) {
			return false;
		}

		$options = '<option value="0" selected>' . __( 'All', CLICKWHALE_PRO_NAME ) . '</option>';
		foreach ( $linkpages as $linkpage ) {
			if ( is_null( $linkpage['item_id'] ) ) {
				$linkpage['title'] = __( 'Unknown Link Page', CLICKWHALE_PRO_NAME );
			}
			$options .= '<option value="' . $linkpage['id'] . '">' . wp_unslash( $linkpage['title'] ) . ' (id: ' . $linkpage['id'] . ')</option>';
		}

		return '<select id="clickwhaleLinkpageSelect" multiple="multiple" class="clickwhale-select">' . $options . '</select>';
	}

	private function get_linkpage_row( array $linkpage ) {
		if ( ! $linkpage ) {
			return false;
		}

		$rowID         = 'data-row="' . $linkpage['id'] . '"';
		$cellClicks    = '<td class="clickwhale-statistics---clicks">' . $linkpage['count'] ?: 0 . '</td>';
		$cellTitle     = is_null( $linkpage['item_id'] )
			? '<td>' . __( 'Unknown Link Page', CLICKWHALE_PRO_NAME ) . ' (id: ' . $linkpage['id'] . ')' . '</td>'
			: '<td>' . wp_unslash( $linkpage['title'] ) . ' (id: ' . $linkpage['item_id'] . ')' . '</td>';
		$editTooltip   = __( 'Edit Link Page', CLICKWHALE_PRO_NAME );
		$statTooltip   = __( 'Select Link Page for the statistics', CLICKWHALE_PRO_NAME );
		$removeTooltip = __( 'Remove Link Page from the statistics', CLICKWHALE_PRO_NAME );
		$editURL       = esc_url( admin_url( 'admin.php?page=' . CLICKWHALE_SLUG . '-edit-linkpage&id=' . $linkpage['id'] ) );
		$edit          = is_null( $linkpage['item_id'] )
			? ''
			: '<a href="' . $editURL . '" target="_blank" rel="noopener" title="' . $editTooltip . '"><svg class="feather"><use href="' . CLICKWHALE_ADMIN_ASSETS_DIR . '/images/feather-sprite.svg#edit-2"></use></svg></a>';
		$stat          = '<button type="button" data-type="view" data-action="add" data-id="' . $linkpage['id'] . '" title="' . $statTooltip . '"><svg class="feather"><use href="' . CLICKWHALE_ADMIN_ASSETS_DIR . '/images/feather-sprite.svg#bar-chart-2"></use></svg></button>';
		$remove        = '<button type="button" data-type="click" data-action="remove" data-id="' . $linkpage['id'] . '" title="' . $removeTooltip . '"><svg class="feather"><use href="' . CLICKWHALE_ADMIN_ASSETS_DIR . '/images/feather-sprite.svg#x"></use></svg></button>';
		$sellActions   = '<td class="statistics-table-actions">' . $edit . $stat . $remove . '</td>';

		return '<tr ' . $rowID . '>' . $cellTitle . $cellClicks . $sellActions . '</tr>';
	}

	private function get_linkpages_table( $linkpages ) {
		if ( ! $linkpages ) {
			return false;
		}
		$rows  = '';
		$thead = '';

		$thead .= '<thead><tr>';
		$thead .= '<th>' . __( 'Link Page', CLICKWHALE_PRO_NAME ) . '</th>';
		$thead .= '<th width="100">' . __( 'Views', CLICKWHALE_PRO_NAME ) . '</th>';
		$thead .= '<th width="100"></th>';
		$thead .= '</tr></thead>';

		foreach ( $linkpages as $linkpage ) {
			$rows .= $this->get_linkpage_row( $linkpage );
		}

		$tfoot = '<tfoot><tr><th colspan="4">' . __( 'Nothing to display', CLICKWHALE_PRO_NAME ) . '</th></tr></tfoot>';

		return '<table id="clickwhaleViewsTable" class="wp-list-table">' . $thead . $rows . $tfoot . '</table>';
	}

	public function show_statistics() {
		$linkpages_select = $this->get_filter_linkpages_select( self::$withViews );
		$periods          = Clickwhale_Pro_Statistics::statistic_periods();
		?>

        <div class="clickwhale-statistics--filters">

            <div class="clickwhale-statistics--filter filter-list filter-linkpages">
                <h4 class="wp-heading-inline"><?php _e( 'Select Link Page', CLICKWHALE_PRO_NAME ) ?></h4>
				<?php echo $linkpages_select ?>
            </div>

            <div class="clickwhale-statistics--filter filter-period">
                <h4 class="wp-heading-inline"><?php _e( 'Period', CLICKWHALE_PRO_NAME ) ?></h4>
				<?php if ( $periods ) { ?>
                    <select id="clickwhaleViewsPeriod" class="clickwhale-select">
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
                <div id="clickwhaleViewsCustomPeriod" class="clickwhale-statistics--custom-period">
                    <input type="text"
                           id="clickwhaleViewsCustomPeriodStart"
                           class="clickwhale-statistics--custom-period--start"
                           min="2022-12-11"
                           placeholder="<?php _e( 'Start Date', CLICKWHALE_PRO_NAME ) ?>">
                    <input type="text"
                           id="clickwhaleViewsCustomPeriodEnd"
                           class="clickwhale-statistics--custom-period--end"
                           placeholder="<?php _e( 'End Date', CLICKWHALE_PRO_NAME ) ?>">
                    <button class="button" type="button"><?php _e( 'Filter', CLICKWHALE_PRO_NAME ) ?></button>
                </div>
            </div>
        </div>

        <div class="clickwhale-statistics--grid clickwhale-statistics--cards">
            <div class="clickwhale-statistics--item clickwhale-statistics--card" id="viewsPerToday">
                <p><?php _e( 'Views for today', CLICKWHALE_PRO_NAME ) ?></p>
                <div class="clickwhale-statistics--card-value">
                    <h4>0</h4>
                    <span>0%</span><?php _e( 'from yesterday', CLICKWHALE_PRO_NAME ) ?>
                </div>
            </div>
            <div class="clickwhale-statistics--item clickwhale-statistics--card" id="viewsPerWeek">
                <p><?php _e( 'Views for this week', CLICKWHALE_PRO_NAME ) ?></p>
                <div class="clickwhale-statistics--card-value">
                    <h4>0</h4>
                    <span>0%</span><?php _e( 'from last week', CLICKWHALE_PRO_NAME ) ?>
                </div>
            </div>
            <div class="clickwhale-statistics--item clickwhale-statistics--card" id="viewsPerMonth">
                <p><?php _e( 'Views for this month', CLICKWHALE_PRO_NAME ) ?></p>
                <div class="clickwhale-statistics--card-value">
                    <h4>0</h4>
                    <span>0%</span><?php _e( 'from last month', CLICKWHALE_PRO_NAME ) ?>
                </div>
            </div>
        </div>

        <div class="clickwhale-statistics--grid clickwhale-statistics--charts">
            <div class="clickwhale-statistics--item">
                <div class="clickwhale-statistics--item-header">
					<?php _e( 'Link Pages Views Overview', CLICKWHALE_PRO_NAME ); ?>
                </div>

                <div class="clickwhale-statistics--chart-clicks">
                    <canvas id="clickwhaleViewsChart"></canvas>
                </div>
            </div>
            <div class="clickwhale-statistics--item">
                <div class="clickwhale-statistics--item-header">
					<?php _e( 'Most Viewed Link Pages', CLICKWHALE_PRO_NAME ); ?>
                </div>
                <div class="clickwhale-statistics--chart-most-clicked">
                    <canvas id="clickwhaleMostViewedChart"></canvas>
                </div>
            </div>
        </div>

        <div class="clickwhale-statistics--item">
            <div class="clickwhale-statistics--item-header">
				<?php _e( 'Total Link Pages Views', CLICKWHALE_PRO_NAME ); ?>
            </div>
            <div class="clickwhale-statistics--table">
				<?php echo $this->get_linkpages_table( self::$withViews ); ?>
            </div>
        </div>

		<?php
	}
}