<?php
namespace clickwhale_pro\includes\admin\statistics;

class Clickwhale_Pro_Statistics {
	public Clickwhale_Pro_Clicks_Statistics $clicksStatistics;
	public Clickwhale_Pro_Views_Statistics $viewsStatistics;

	public function __construct() {
		$this->load_dependencies();

		$this->clicksStatistics = new Clickwhale_Pro_Clicks_Statistics();
		$this->viewsStatistics  = new Clickwhale_Pro_Views_Statistics();

		add_action( 'admin_print_footer_scripts', [ $this, 'admin_scripts' ] );
	}

	public function load_dependencies() {
		require_once CLICKWHALE_PRO_DIR . 'includes/admin/statistics/Clickwhale_Pro_Clicks_Statistics.php';
		require_once CLICKWHALE_PRO_DIR . 'includes/admin/statistics/Clickwhale_Pro_Views_Statistics.php';
	}

	public static function statistic_periods(): array {
		return array(
			'today'      => array(
				'start' => date( 'Y-m-d' ),
				'end'   => date( 'Y-m-d' ),
				'label' => __( 'Today', CLICKWHALE_PRO_SLUG )
			),
			'yesterday'  => array(
				'start' => date( 'Y-m-d', strtotime( '-1 days' ) ),
				'end'   => date( 'Y-m-d', strtotime( '-1 days' ) ),
				'label' => __( 'Yesterday', CLICKWHALE_PRO_SLUG )
			),
			'last7days'  => array(
				'start'    => date( 'Y-m-d', strtotime( '-6 days' ) ),
				'end'      => date( 'Y-m-d' ),
				'label'    => __( 'Last 7 Days', CLICKWHALE_PRO_SLUG ),
				'selected' => true
			),
			'last30days' => array(
				'start' => date( 'Y-m-d', strtotime( '-29 days' ) ),
				'end'   => date( 'Y-m-d' ),
				'label' => __( 'Last 30 Days', CLICKWHALE_PRO_SLUG )
			),
			'thisMonth'  => array(
				'start' => date( 'Y-m-01' ),
				'end'   => date( 'Y-m-d' ),
				'label' => __( 'This Month', CLICKWHALE_PRO_SLUG )
			),
			'lastMonth'  => array(
				'start' => date( 'Y-m-d', strtotime( 'first day of last month' ) ),
				'end'   => date( 'Y-m-d', strtotime( 'last day of last month' ) ),
				'label' => __( 'Last Month', CLICKWHALE_PRO_SLUG )
			),
			'thisYear'   => array(
				'start' => date( 'Y-01-01' ),
				'end'   => date( 'Y-m-d' ),
				'label' => __( 'This Year', CLICKWHALE_PRO_SLUG )
			),
			'LastYear'   => array(
				'start' => date( 'Y-m-d', strtotime( 'last year January 1st' ) ),
				'end'   => date( 'Y-m-d', strtotime( 'last year December 31st' ) ),
				'label' => __( 'Last Year', CLICKWHALE_PRO_SLUG )
			),
			'all'        => array(
				'label' => __( 'All Time', CLICKWHALE_PRO_SLUG )
			),
			'custom'     => array(
				'label' => __( 'Custom', CLICKWHALE_PRO_SLUG )
			)
		);
	}

	public function admin_scripts() {
		$nonce_total_clicks_for_period     = wp_create_nonce( 'clickwhale_pro_total_clicks_for_period' );
		$nonce_clicks_count_for_day_and_id = wp_create_nonce( 'clickwhale_pro_clicks_count_for_day_and_id' );
		$nonce_total_views_for_period      = wp_create_nonce( 'clickwhale_pro_total_views_for_period' );
		$nonce_views_count_for_day_and_id  = wp_create_nonce( 'clickwhale_pro_views_count_for_day_and_id' );
		?>
        <script>
            jQuery(document).ready(function () {
                const
                    startLimitDate = '2022-12-11',
                    linksSelect = jQuery('#clickwhaleLinkSelect'),
                    linkpagesSelect = jQuery('#clickwhaleLinkpageSelect'),
                    clicksPeriodSelect = jQuery('#clickwhaleClicksPeriod'),
                    viewsPeriodSelect = jQuery('#clickwhaleViewsPeriod'),
                    clicksTable = jQuery('#clickwhaleClicksTable'),
                    viewsTable = jQuery('#clickwhaleViewsTable'),
                    currentClicksPeriod = get_selected_period(clicksPeriodSelect),
                    currentViewsPeriod = get_selected_period(viewsPeriodSelect),
                    clicksCountForDay = get_clicks_count_for_day_and_id(),
                    viewsCountForDay = get_views_count_for_day_and_id();

                let totalClicksForPeriod = get_total_clicks_for_period(currentClicksPeriod),
                    totalViewsForPeriod = get_total_views_for_period(currentViewsPeriod);

                // charts
                const
                    mainChartOptions = {
                        cubicInterpolationMode: 'monotone',
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            x: {
                                grid: {
                                    display: false
                                },
                                ticks: {
                                    padding: 10,
                                    font: {
                                        size: 10
                                    },
                                    maxTicksLimit: 20,
                                    autoSkip: true,
                                    callback: function (value, index) {
                                        const date = new Date(this.getLabelForValue(value));
                                        return date.toLocaleString('default', {month: 'short', day: 'numeric'});
                                    },
                                },
                            },
                            y: {
                                grid: {
                                    color: "#eeeeee"
                                },
                                border: {
                                    dash: [4, 4],
                                },
                                beginAtZero: true
                            }
                        },
                        plugins: {
                            colors: {
                                forceOverride: true
                            },
                        }
                    },
                    plugin = {
                        id: 'emptyDoughnut',
                        afterDraw(chart, args, options) {
                            const {datasets} = chart.data;
                            const {color, width, radiusDecrease} = options;
                            let hasData = false;

                            for (let i = 0; i < datasets.length; i += 1) {
                                const dataset = datasets[i];
                                hasData |= dataset.data.length > 0;
                            }

                            if (!hasData) {
                                const {chartArea: {left, top, right, bottom}, ctx} = chart;
                                const centerX = (left + right) / 2;
                                const centerY = (top + bottom) / 2;
                                const r = Math.min(right - left, bottom - top) / 2;

                                ctx.beginPath();
                                ctx.lineWidth = width || 2;
                                ctx.strokeStyle = color || 'rgba(255, 128, 0, 0.5)';
                                ctx.textAlign = 'center';
                                ctx.textBaseline = 'middle';
                                ctx.fillText('<?php _e( 'No data to display',
									CLICKWHALE_PRO_SLUG ) ?>', centerX, centerY);
                                ctx.arc(centerX, centerY, (r - radiusDecrease || 0), 0, 2 * Math.PI);
                                ctx.stroke();
                            }
                        }
                    },
                    clicksChart = new Chart(document.getElementById('clickwhaleClicksChart'), {
                        type: 'line',
                        data: {
                            datasets: [setMainChartDefaultData()]
                        },
                        options: mainChartOptions
                    }),
                    viewsChart = new Chart(document.getElementById('clickwhaleViewsChart'), {
                        type: 'line',
                        data: {
                            datasets: [setMainChartDefaultData()]
                        },
                        options: mainChartOptions
                    }),
                    mostClickedChart = new Chart(document.getElementById('clickwhaleMostClickedChart'), {
                        type: 'doughnut',
                        data: getDoughnutChartDatasetData(totalClicksForPeriod, linksSelect.val()),
                        options: {
                            responsive: true,
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                },
                                emptyDoughnut: {
                                    color: 'rgba(225, 225, 225, 1)',
                                    width: 2,
                                    radiusDecrease: 20
                                }
                            }
                        },
                        plugins: [plugin]
                    }),
                    mostViewedChart = new Chart(document.getElementById('clickwhaleMostViewedChart'), {
                        type: 'doughnut',
                        data: getDoughnutChartDatasetData(totalViewsForPeriod, linkpagesSelect.val()),
                        options: {
                            responsive: true,
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                },
                                emptyDoughnut: {
                                    color: 'rgba(225, 225, 225, 1)',
                                    width: 2,
                                    radiusDecrease: 20
                                }
                            }
                        },
                        plugins: [plugin]
                    });

                clicksChart.config.data.datasets[0].data = getChartDatasetData(filterCount(clicksCountForDay, currentClicksPeriod), currentClicksPeriod);
                clicksChart.update();

                viewsChart.config.data.datasets[0].data = getChartDatasetData(filterCount(viewsCountForDay, currentViewsPeriod), currentViewsPeriod);
                viewsChart.update();
                // end charts

                // tables
                updateTable('clicks');
                updateTable('views');
                // end tables

                init_period_tads()

                jQuery('.clickwhale-tabs').tabs({
                    activate: function (event, ui) {
                        init_period_tads()
                    }
                });

                /* Clicks (links) */

                linksSelect
                    .on('select2:select', function (item) {
                        const
                            period = get_selected_period(clicksPeriodSelect),
                            dataArray = filterCount(clicksCountForDay, period, item.params.data.id);

                        addChartData(
                            clicksChart,
                            item.params.data.id,
                            item.params.data.text,
                            period,
                            dataArray
                        );
                        clicksTable.find('tr[data-row="' + item.params.data.id + '"]').addClass('is-active');
                    })
                    .on('select2:unselect', function (item) {
                        removeChartData(clicksChart, item.params.data.text);
                        clicksTable.find('tr[data-row="' + item.params.data.id + '"]').removeClass('is-active');
                    })
                    .on('change', function () {
                        addDataToCard('clicks', '#clicksPerToday', 'today');
                        addDataToCard('clicks', '#clicksPerWeek', 'week');
                        addDataToCard('clicks', '#clicksPerMonth', 'month');

                        updateDoughnut('clicks', mostClickedChart, jQuery(this).val());
                        updateTable('clicks', jQuery(this).val());
                    });

                clicksPeriodSelect.on('change', function () {
                    const period = get_selected_period(clicksPeriodSelect);

                    if (jQuery(this).val() === 'today' || jQuery(this).val() === 'yesterday') {
                        clicksChart.config.type = 'bar';
                        clicksChart.config.options.scales['x'].offset = true;
                    } else {
                        clicksChart.config.type = 'line';
                        clicksChart.config.options.scales['x'].offset = false;
                    }
                    clicksChart.update();

                    if (jQuery(this).val() === 'custom') {
                        jQuery(this).parent().find('.clickwhale-statistics--custom-period').addClass('is-active');
                    } else {
                        jQuery(this).parent().find('.clickwhale-statistics--custom-period').removeClass('is-active');
                        onPeriodSelectChange('clicks', period);
                    }
                });
                /* end */

                /* Views (link pages) */

                linkpagesSelect
                    .on('select2:select', function (item) {
                        const
                            period = get_selected_period(viewsPeriodSelect),
                            dataArray = filterCount(viewsCountForDay, period, item.params.data.id);

                        addChartData(
                            viewsChart,
                            item.params.data.id,
                            item.params.data.text,
                            period,
                            dataArray
                        );

                        viewsTable.find('tr[data-row="' + item.params.data.id + '"]').addClass('is-active');
                    })
                    .on('select2:unselect', function (item) {
                        removeChartData(viewsChart, item.params.data.text);
                        viewsTable.find('tr[data-row="' + item.params.data.id + '"]').removeClass('is-active');
                    })
                    .on('change', function () {
                        addDataToCard('views', '#viewsPerToday', 'today');
                        addDataToCard('views', '#viewsPerWeek', 'week');
                        addDataToCard('views', '#viewsPerMonth', 'month');

                        updateDoughnut('views', mostViewedChart, jQuery(this).val());
                        updateTable('views', jQuery(this).val());
                    });

                viewsPeriodSelect.on('change', function () {
                    const period = get_selected_period(viewsPeriodSelect);

                    if (jQuery(this).val() === 'today' || jQuery(this).val() === 'yesterday') {
                        viewsChart.config.type = 'bar';
                        viewsChart.config.options.scales['x'].offset = true;
                    } else {
                        viewsChart.config.type = 'line';
                        viewsChart.config.options.scales['x'].offset = false;
                    }
                    viewsChart.update();

                    if (jQuery(this).val() === 'custom') {
                        jQuery(this).parent().find('.clickwhale-statistics--custom-period').addClass('is-active');
                    } else {
                        jQuery(this).parent().find('.clickwhale-statistics--custom-period').removeClass('is-active');
                        onPeriodSelectChange('views', period);
                    }

                });

                /* Custom period */
                jQuery('.clickwhale-statistics--custom-period--start, .clickwhale-statistics--custom-period--end')
                    .on('focus', function () {
                        this.type = 'date';
                        this.showPicker();
                    })
                    .on('blur', function () {
                        this.type = 'text';
                    });

                jQuery('.clickwhale-statistics--custom-period--start').on('change', function () {
                    const date = new Date(Date.parse(this.value));
                    const min = formatDate(new Date(date.setDate(date.getDate() + 1)));

                    jQuery(this).parent().find('.clickwhale-statistics--custom-period--end').attr('min', min);
                });
                jQuery('.clickwhale-statistics--custom-period--end').on('change', function () {
                    const date = new Date(Date.parse(this.value));
                    const max = formatDate(new Date(date.setDate(date.getDate() - 1)));

                    jQuery(this).parent().find('.clickwhale-statistics--custom-period--start').attr('max', max);
                });
                jQuery('.clickwhale-statistics--custom-period button').on('click', function (event) {
                    event.preventDefault();

                    if (jQuery(this).parent().attr('id') === 'clickwhaleClicksCustomPeriod') {
                        const period = get_selected_period(clicksPeriodSelect);

                        onPeriodSelectChange('clicks', period);
                    }

                    if (jQuery(this).parent().attr('id') === 'clickwhaleViewsCustomPeriod') {
                        const period = get_selected_period(viewsPeriodSelect);

                        onPeriodSelectChange('views', period);
                    }
                });

                /* end */

                /* Cards */
                // clicks
                addDataToCard('clicks', '#clicksPerToday', 'today');
                addDataToCard('clicks', '#clicksPerWeek', 'week');
                addDataToCard('clicks', '#clicksPerMonth', 'month');
                // views
                addDataToCard('views', '#viewsPerToday', 'today');
                addDataToCard('views', '#viewsPerWeek', 'week');
                addDataToCard('views', '#viewsPerMonth', 'month');

                /* end */

                jQuery('.statistics-table-actions').on('click', 'button', function (e) {
                    e.preventDefault();

                    const
                        button = jQuery(this),
                        text = button.parent().siblings('td:first').text(),
                        items = button.data('type') === 'click' ? linksSelect.val() : linkpagesSelect.val(),
                        select = button.data('type') === 'click' ? linksSelect : linkpagesSelect,
                        id = button.data('id').toString(),
                        action = button.data('action');

                    button.parent().closest('tr').addClass('is-active');

                    if (action === 'add') {
                        if (!items.includes(id)) {
                            items.push(id);
                            select
                                .val(items)
                                .trigger('change')
                                .trigger({
                                    type: 'select2:select',
                                    params: {
                                        data: {
                                            disabled: false,
                                            id: id,
                                            selected: true,
                                            text: text,
                                            title: ""
                                        }
                                    }
                                });
                        }
                    }

                    if (action === 'remove') {
                        const index = items.indexOf(id);
                        if (index > -1) {
                            items.splice(index, 1);
                        }
                        select
                            .val(items)
                            .trigger('change')
                            .trigger({
                                type: 'select2:unselect',
                                params: {
                                    data: {
                                        disabled: false,
                                        id: id,
                                        selected: true,
                                        text: text,
                                        title: ""
                                    }
                                }
                            });
                    }
                });

                /* FUNCTIONS */

                // get default data
                function setMainChartDefaultData() {
                    return {
                        borderJoinStyle: 'round',
                        borderWidth: 2,
                        fill: true,
                        label: "<?php _e( 'All', CLICKWHALE_PRO_SLUG ); ?>",
                        pointBorderWidth: 2,
                        pointHoverBorderWidth: 2,
                        pointRadius: 2,
                        snapGap: true,
                    }
                }

                // get json data

                function get_clicks_count_for_day_and_id() {
                    let result = null;

                    jQuery.ajax({
                        async: false,
                        type: 'post',
                        dataType: 'json',
                        url: ajaxurl,
                        data: {
                            'security': '<?php echo $nonce_clicks_count_for_day_and_id ?>',
                            'action': 'clickwhale_pro/admin/get_clicks_count_for_day_and_id'
                        }, success: function (response) {
                            result = response.data;
                        }
                    });

                    return result.items;
                }

                function get_total_clicks_for_period(period) {
                    let result = null;

                    jQuery.ajax({
                        async: false,
                        type: 'post',
                        dataType: 'json',
                        url: ajaxurl,
                        data: {
                            'security': '<?php echo $nonce_total_clicks_for_period ?>',
                            'action': 'clickwhale_pro/admin/get_total_clicks_for_period',
                            'period': period
                        }, success: function (response) {
                            result = response.data;
                        }
                    });

                    return result.items;
                }

                function get_views_count_for_day_and_id() {
                    let result = null;

                    jQuery.ajax({
                        async: false,
                        type: 'post',
                        dataType: 'json',
                        url: ajaxurl,
                        data: {
                            'security': '<?php echo $nonce_views_count_for_day_and_id ?>',
                            'action': 'clickwhale_pro/admin/get_views_count_for_day_and_id'
                        }, success: function (response) {
                            result = response.data;
                        }
                    });

                    return result.items;
                }

                function get_total_views_for_period(period) {
                    let result = null;

                    jQuery.ajax({
                        async: false,
                        type: 'post',
                        dataType: 'json',
                        url: ajaxurl,
                        data: {
                            'security': '<?php echo $nonce_total_views_for_period ?>',
                            'action': 'clickwhale_pro/admin/get_total_views_for_period',
                            'period': period
                        }, success: function (response) {
                            result = response.data;
                        }
                    });

                    return result.items;
                }

                // period select

                function init_period_tads() {
                    clicksPeriodSelect.select2({
                        minimumResultsForSearch: -1
                    });
                    viewsPeriodSelect.select2({
                        minimumResultsForSearch: -1
                    });
                }

                // Doughnut (Most Clicked)

                function getDoughnutChartDatasetData(items, selected = []) {

                    if (items === undefined || items === null) {
                        return false;
                    }

                    const
                        unknown = '<?php _e( 'Unknown', CLICKWHALE_PRO_SLUG ) ?>',
                        data = {
                            labels: [],
                            datasets: [{
                                label: '<?php _e( 'Total', CLICKWHALE_PRO_SLUG ) ?>',
                                data: []
                            }]
                        };

                    if (selected.length > 0) {
                        items.forEach(item => {
                            if (selected.includes('0')) {
                                if (item.item_id === null || item.item_id === 'undefined') {
                                    data.labels.push(unknown + ' (id: ' + item.id + ')');
                                } else {
                                    data.labels.push(item.title.replace('\\', ''));
                                }
                                data.datasets[0].data.push(item.count);
                            } else if (selected.includes(item.id)) {
                                if (item.item_id === null || item.item_id === 'undefined') {
                                    data.labels.push(unknown + ' (id: ' + item.id + ')');
                                } else {
                                    data.labels.push(item.title.replace('\\', ''));
                                }
                                data.datasets[0].data.push(item.count);
                            }
                        });
                    }


                    return data;
                }

                function updateDoughnut(type, chart, items) {
                    const data = type === 'clicks' ? totalClicksForPeriod : totalViewsForPeriod;

                    chart.config.data = getDoughnutChartDatasetData(data, items);
                    chart.update();
                }

                // Cards

                function addDataToCard(type, element, period = '') {
                    const
                        todayElement = jQuery(element).find('.clickwhale-statistics--card-value h4'),
                        percentageElement = jQuery(element).find('.clickwhale-statistics--card-value span'),
                        today = '<?php echo date( 'Y-m-d' ) ?>',
                        yesterday = '<?php echo date( 'Y-m-d', strtotime( '-1 days' ) ) ?>',
                        thisWeekStart = '<?php echo date( 'Y-m-d', strtotime( 'this week Monday' ) ) ?>',
                        lastWeekStart = '<?php echo date( 'Y-m-d', strtotime( 'last week Monday' ) ) ?>',
                        lastWeekEnd = '<?php echo date( 'Y-m-d', strtotime( 'last week Sunday' ) ) ?>',
                        thisMonthStart = '<?php echo date( 'Y-m-d', strtotime( 'first day of this month' ) ) ?>',
                        lastMonthStart = '<?php echo date( 'Y-m-d', strtotime( 'first day of last month' ) ) ?>',
                        lastMonthEnd = '<?php echo date( 'Y-m-d', strtotime( 'last day of last month' ) ) ?>',
                        items = type === 'clicks' ? clicksCountForDay : viewsCountForDay,
                        ids = type === 'clicks' ? linksSelect.val() : linkpagesSelect.val(),
                        result = {};

                    let prefix = '',
                        colorClass = '';

                    result.current = 0;
                    result.previous = 0;

                    switch (period) {
                        case 'today':

                            if (ids.includes('0')) {
                                items.forEach(item => {
                                    if (item.created === today) {
                                        result.current += parseInt(item.count);
                                    }
                                    if (item.created === yesterday) {
                                        result.previous += parseInt(item.count);
                                    }
                                });
                            } else {
                                items.forEach(item => {
                                    if (item.created === today && ids.includes(item.item_id)) {
                                        result.current += parseInt(item.count);
                                    }
                                    if (item.created === yesterday && ids.includes(item.item_id)) {
                                        result.previous += parseInt(item.count);
                                    }
                                });
                            }

                            break;
                        case 'week':

                            if (ids.includes('0')) {
                                items.forEach(item => {
                                    if (item.created >= thisWeekStart && item.created <= today) {
                                        result.current += parseInt(item.count);
                                    }
                                    if (item.created >= lastWeekStart && item.created <= lastWeekEnd) {
                                        result.previous += parseInt(item.count);
                                    }
                                });
                            } else {
                                items.forEach(item => {
                                    if (item.created >= thisWeekStart && item.created <= today && ids.includes(item.item_id)) {
                                        result.current += parseInt(item.count);
                                    }
                                    if (item.created >= lastWeekStart && item.created <= lastWeekEnd && ids.includes(item.item_id)) {
                                        result.previous += parseInt(item.count);
                                    }
                                });
                            }

                            break;
                        case 'month':

                            if (ids.includes('0')) {
                                items.forEach(item => {
                                    if (item.created >= thisMonthStart && item.created <= today) {
                                        result.current += parseInt(item.count);
                                    }
                                    if (item.created >= lastMonthStart && item.created <= lastMonthEnd) {
                                        result.previous += parseInt(item.count);
                                    }
                                });
                            } else {
                                items.forEach(item => {
                                    if (item.created >= thisMonthStart && item.created <= today && ids.includes(item.item_id)) {
                                        result.current += parseInt(item.count);
                                    }
                                    if (item.created >= lastMonthStart && item.created <= lastMonthEnd && ids.includes(item.item_id)) {
                                        result.previous += parseInt(item.count);
                                    }
                                });
                            }

                            break;
                    }

                    result.difference = result.previous > 0
                        ? parseFloat(((result.current - result.previous) / result.previous * 100).toFixed(2))
                        : parseFloat(((result.current - result.previous) * 100).toFixed(2));

                    if (result.difference > 0) {
                        prefix = '+';
                        colorClass = 'positive';
                    }
                    if (result.difference < 0) {
                        colorClass = 'negative';
                    }

                    todayElement.text(result.current);
                    jQuery(element).find('.clickwhale-statistics--card-value').removeClass('positive negative').addClass(colorClass);
                    if (result.difference === 0) {
                        prefix = '';
                        jQuery(element).find('.clickwhale-statistics--card-value').removeClass('positive negative');
                    }
                    percentageElement.text(prefix + result.difference + '%');

                }

                // other

                function get_selected_period(select) {
                    const
                        period = [],
                        value = select.val();

                    switch (value) {
                        case 'all':
                            period.push(formatDate(new Date(startLimitDate)));
                            period.push(formatDate(new Date()));

                            break;
                        case 'custom':
                            const
                                customWrap = select.parent().find('.clickwhale-statistics--custom-period'),
                                customStart = customWrap.find('.clickwhale-statistics--custom-period--start').val(),
                                customEnd = customWrap.find('.clickwhale-statistics--custom-period--end').val();

                            if (customStart) {
                                period.push(customStart);
                            }
                            if (customStart) {
                                period.push(customEnd);
                            }

                            break;
                        default:
                            period.push(select.find('option:selected').data('start'));
                            period.push(select.find('option:selected').data('end'));
                    }

                    return period.length === 2 ? period : false;
                }

                /**
                 * Filter (array) viewsCountForDay to {created: string, count: num} format
                 * @param array
                 * @param period array
                 * @param id string
                 * @returns {*[]}
                 */
                function filterCount(array, period, id = '') {
                    let result = [];

                    if (id && id !== '0') {
                        array.forEach(item => {
                            if (item.created >= period[0] && item.created <= period[1] && item.id === id) {
                                result = processingFilteredItem(item, result);
                            }
                        });
                    } else {
                        array.forEach(item => {
                            if (item.created >= period[0] && item.created <= period[1]) {
                                result = processingFilteredItem(item, result);
                            }
                        });
                    }

                    return result;
                }

                /**
                 * if object with given value exists in accumulator array then updates its count value
                 * else adds new object
                 * @param item object
                 * @param accumulator array of objects
                 * @returns {*}
                 */
                function processingFilteredItem(item, accumulator) {
                    const found = accumulator.findIndex(element => element.created === item.created);
                    if (found >= 0) {
                        accumulator[found].count += parseInt(item.count);
                    } else {
                        const obj = {};

                        obj.created = item.created;
                        obj.count = 0;
                        obj.count += parseInt(item.count);

                        accumulator.push(obj);
                    }

                    return accumulator;
                }

                function formatDate(date) {
                    return new Date(date).toISOString().split('T')[0];
                }

                function addEmptyPeriodObject(dates) {
                    const period = [];

                    for (let d = new Date(dates[0]); d <= new Date(dates[1]); d.setDate(d.getDate() + 1)) {
                        const empty = {
                            'created': formatDate(d),
                            'count': 0
                        }
                        period.push(empty);
                    }

                    return period;
                }

                function getChartDatasetData(data, period) {
                    const
                        dataset = [],
                        emptyPeriod = addEmptyPeriodObject(period),
                        dates = new Set(data.map(date => date.created)),
                        merged = [...data, ...emptyPeriod.filter(date => !dates.has(date.created))];

                    merged.sort((a, b) => (a.created > b.created) ? 1 : ((b.created > a.created) ? -1 : 0))

                    merged.forEach(item => {
                        const datasetItem = {};
                        datasetItem['x'] = item.created;
                        datasetItem['y'] = parseInt(item.count);

                        dataset.push(datasetItem);
                    });

                    return dataset;
                }

                function addChartData(chart, id, label, period, array = []) {
                    const dataset = setMainChartDefaultData();
                    if (id === '0') {
                        dataset.label = "<?php _e( 'All', CLICKWHALE_PRO_SLUG ); ?>";
                    } else {
                        dataset.label = label;
                    }
                    dataset.data = getChartDatasetData(array, period);

                    chart.config.data.datasets.push(dataset);
                    chart.update();
                }

                function removeChartData(chart, label) {
                    let datasets = chart.config.data.datasets;

                    for (let i = 0; i < datasets.length; i++) {
                        if (datasets[i]['label'] === label) {
                            datasets.splice(i, 1);
                        }
                    }
                    chart.config.data.datasets = datasets;
                    chart.update();
                }

                function resetChart(chart) {
                    chart.config.data.datasets = [];
                    chart.update();
                }

                function onPeriodSelectChange(type, period) {

                    let items = [];

                    switch (type) {
                        case 'clicks':
                            items = linksSelect.val();
                            totalClicksForPeriod = get_total_clicks_for_period(period);

                            if (items.length === 0) {
                                return false;
                            }

                            // reset current charts
                            resetChart(clicksChart);
                            resetChart(mostClickedChart);

                            //update main chart
                            items.forEach(itemID => {
                                const
                                    text = linksSelect.find('option[value="' + itemID + '"]').text(),
                                    array = filterCount(clicksCountForDay, period, itemID);

                                addChartData(clicksChart, itemID, text, period, array);
                            });

                            // update doughnut chart
                            updateDoughnut('clicks', mostClickedChart, items)

                            // update table

                            updateTable(type, items);

                            break;
                        case 'views':
                            items = linkpagesSelect.val();
                            totalViewsForPeriod = get_total_views_for_period(period);

                            if (items.length === 0) {
                                return false;
                            }

                            // reset current charts
                            resetChart(viewsChart);
                            resetChart(mostViewedChart);

                            //update main chart
                            items.forEach(itemID => {
                                const
                                    text = linkpagesSelect.find('option[value="' + itemID + '"]').text(),
                                    array = filterCount(viewsCountForDay, period, itemID);

                                addChartData(viewsChart, itemID, text, period, array);
                            });

                            // update doughnut chart
                            updateDoughnut('views', mostViewedChart, items)

                            // update table
                            updateTable(type, items);

                            break;
                        default:
                            return;
                    }
                }

                function updateTable(type, values = []) {

                    let element = null,
                        statistics = null;

                    switch (type) {
                        case 'clicks':
                            element = jQuery('#clickwhaleClicksTable');
                            statistics = totalClicksForPeriod;
                            break;
                        case 'views':
                            element = jQuery('#clickwhaleViewsTable');
                            statistics = totalViewsForPeriod;
                            break;
                        default:
                            return;
                    }

                    if (values.length === 0 || values.includes('0')) {
                        element.find('tbody tr').addClass('is-hidden');
                        if (statistics && statistics.length > 0) {
                            statistics.forEach(row => {
                                element.find('tbody tr[data-row="' + row.id + '"]')
                                    .removeClass('is-hidden')
                                    .find('.clickwhale-statistics---clicks').text(row.count);
                            })
                        }
                    } else {
                        element.find('tbody tr').addClass('is-hidden');

                        values.forEach(row => {
                            const currentRowStatistics =
                                statistics && statistics.length > 0
                                    ? statistics.filter(item => item.id === row)
                                    : false;
                            const count =
                                currentRowStatistics && currentRowStatistics.length > 0
                                    ? currentRowStatistics[0].count
                                    : 0;

                            element.find('tbody tr[data-row="' + row + '"]')
                                .removeClass('is-hidden')
                                .find('.clickwhale-statistics---clicks').text(count);
                        })
                    }
                    empty_data_message_display(element);
                }

                function empty_data_message_display(element) {
                    const
                        total = element.find('tbody tr').length,
                        hidden = element.find('tbody tr.is-hidden').length;

                    if (total === hidden) {
                        element.find('tfoot').addClass('is-visible');
                    } else {
                        element.find('tfoot').removeClass('is-visible');
                    }
                }

            });

        </script>
		<?php
	}
}