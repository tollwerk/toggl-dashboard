var Tollwerk = {Dashboard: {}}

/**
 * Initialize a user time chart
 *
 * @param {String} id Container ID
 * @param {Array} data User data
 */
Tollwerk.Dashboard.initUserTimeChart = function (id, data) {
    $(function () {
        $('#' + id).highcharts(data);
    });
}

/**
 * Return a rounded performance data label
 *
 * @returns {string} Performance
 */
Tollwerk.Dashboard.performance = function() {
    return Math.round(100 * this.point.billable.time / this.total) + '%';
}

/**
 * Return billable stats
 *
 * @returns {string} Billable stats
 */
Tollwerk.Dashboard.billables = function() {
    var tooltip = '<span style="color:' + this.color + '">●</span> ' + this.series.name + ': ';
    tooltip += this.billable.time + 'h / €' + this.billable.sum + ' (' + this.billable.status + '%)';
    tooltip += '<br/>';
    return tooltip;
}
