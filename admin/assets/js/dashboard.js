var graph_height = 242;
var dashboard = jQuery('.dig_admin_dashboard_wrapper');

function load_dashboard_data() {
    if (!jQuery('#digits_dashboard_view').length) {
        return;
    }
    get_data('users');
    get_data('login');
}

function get_data(graph_type) {
    var formData = {};
    formData.action = 'digits_admin_dashboard_stats';
    formData.graph_type = graph_type;
    formData.nonce = digdashboard.nonce;
    jQuery.ajax({
        type: "POST",
        url: digdashboard.ajax_url,
        data: formData,
        success: function (res) {
            if (res.success) {
                var data = res.data;

                if (data.type === 'logins') {
                    var wrapper = dashboard.find('#digits_dashboard_graph_logins_stats');
                    wrapper.find('.dig_admin_dashboard_graph_total_value').text(data.total_data);
                    render_total_logins_graph(data.graph);
                } else if (data.type === 'user') {
                    var wrapper = dashboard.find('#digits_dashboard_graph_users_stats');
                    wrapper.find('.dig_admin_dashboard_graph_total_value').text(data.total_data);
                    render_total_user_graph(data.graph);

                    var total_time_save = data.total_time_save;
                    var total_otps = data.total_otps;
                    dashboard.find('.dig_admin_stat_min_saved').text(total_time_save);
                    dashboard.find('.dig_admin_stat_otp_del').text(total_otps);
                }

            }
        },
        error: function () {
        }
    });
}

function render_total_logins_graph(data) {
    var options = {
        series: [{
            name: 'logins',
            data: data
        }],
        chart: {
            height: graph_height,
            type: 'area',
            background: '#fff',
            zoom: {
                enabled: false,
            },
            toolbar: {
                show: false,
                tools: {
                    download: false,
                }
            },
        },
        colors: ['#7d39ff'],
        grid: {
            show: false,
            padding: {
                left: 0,
                right: 0,
            }
        },
        yaxis: {
            show: false,
        },
        dataLabels: {
            enabled: false
        },
        stroke: {
            curve: 'smooth'
        },
        xaxis: {
            axisBorder: {
                show: false,
            },
            axisTicks: {
                show: false,
            },
            tooltip: {
                enabled: false,
            },
            type: 'datetime',
            labels: {
                datetimeFormatter: {
                    year: '',
                    month: "MMM",
                    day: '',
                    hour: '',
                },
                style: {
                    cssClass: 'digits_admin_dashboard_graph_labels',
                },
            }
        },
        tooltip: {
            custom: function (d) {
                var dataPointIndex = d.dataPointIndex;
                var obj = data[dataPointIndex];
                var timestamp = obj[0];
                var number = obj[1];
                var date = new Date(timestamp);
                var options = {month: 'short'};
                var date_str = date.getDate() + ' ' + date.toLocaleDateString("en-us", options);
                var dts = '<span class="x_label">&nbsp;on&nbsp;' + date_str + '</span>';
                return '<div class="digits_dashboard_graph_tooltip">' +
                    '<span>' + number + dts + '</span>' +
                    '</div>'
            }

        },
        fill: {
            colors: ['#7d39ff'],
            type: 'gradient',
            gradient: {
                opacityFrom: 0.5,
                opacityTo: 0,
                stops: [0, 100]
            }
        },
    };

    var chart = new ApexCharts(document.querySelector("#digits_dashboard_graph_logins"), options);
    chart.render();
}

function render_total_user_graph(data) {
    var options = {
        series: [{
            name: "users",
            data: data
        }],
        grid: {
            show: false
        },
        yaxis: {
            show: false,
        },
        chart: {
            type: 'bar',
            height: graph_height,
            background: '#fff',
            zoom: {
                enabled: false,
            },
            toolbar: {
                show: false,
                tools: {
                    download: false,
                }
            },
        },
        fill: {
            type: 'gradient',
            colors: ['rgba(125, 57, 255, 0.7)'],
            gradient: {
                type: "vertical",
                gradientToColors: ['rgba(125, 57, 255, 0.4)'],
                inverseColors: false,
                opacityFrom: 1,
                opacityTo: 1,
                stops: [0, 100],
            }
        },
        plotOptions: {
            bar: {
                borderRadius: 8,
                borderRadiusApplication: 'end',
            },
        },
        dataLabels: {
            enabled: false,
        },
        xaxis: {
            type: 'category',
            labels: {
                formatter: function (val) {
                    return val;
                },
                style: {
                    cssClass: 'digits_admin_dashboard_graph_labels',
                },
            },
            crosshairs: {
                show: false
            },
            axisBorder: {
                show: false,
            },
            axisTicks: {
                show: false,
            },
        },
        tooltip: {
            custom: function (data) {
                var series = data.series;
                var seriesIndex = data.seriesIndex;
                var dataPointIndex = data.dataPointIndex;
                var w = data.w;
                return '<div class="digits_dashboard_graph_tooltip">' +
                    '<span>' + series[seriesIndex][dataPointIndex] + '</span>' +
                    '</div>'
            }

        },
        states: {
            hover: {
                filter: {
                    type: 'none',
                    value: 0,
                }
            },
            active: {
                filter: {
                    type: 'none',
                    value: 0,
                }
            },
        }

    };

    var chart = new ApexCharts(document.querySelector("#digits_dashboard_graph_users"), options);
    chart.render();
}

load_dashboard_data();