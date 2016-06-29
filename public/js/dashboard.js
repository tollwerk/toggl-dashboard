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
    return Math.round(100 * this.point.y / this.total) + '%';
}
