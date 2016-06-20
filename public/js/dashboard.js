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
