<script>
    var weekly_payments_statistics;
    $(function() {

        var tickets_chart_departments = $('#tickets-awaiting-reply-by-department');
        var tickets_chart_status = $('#tickets-awaiting-reply-by-status');
        var leads_chart = $('#leads_status_stats');
        var projects_chart = $('#projects_status_stats');

        if (tickets_chart_departments.length > 0) {
            // Tickets awaiting reply by department chart
            var tickets_dep_chart = new Chart(tickets_chart_departments, {
                type: 'doughnut',
                data: <?php echo $tickets_awaiting_reply_by_department; ?>,
            });
        }
        if (tickets_chart_status.length > 0) {
            // Tickets awaiting reply by department chart
            new Chart(tickets_chart_status, {
                type: 'doughnut',
                data: <?php echo $tickets_reply_by_status; ?>,
            });
        }
        if (leads_chart.length > 0) {
            // Leads overview status
            new Chart(leads_chart, {
                type: 'doughnut',
                data: <?php echo $leads_status_stats; ?>,
                options:{maintainAspectRatio:false}
            });
        }
        if(projects_chart.length > 0){
            // Projects statuses
            new Chart(projects_chart, {
                type: 'doughnut',
                data: <?php echo $projects_status_stats; ?>,
                options:{
                    maintainAspectRatio:false,
                }
            });
        }
        // Payments statistics
        init_weekly_payment_statistics( <?php echo $weekly_payment_stats; ?> );
        $('select[name="currency"]').on('change', function() {
            init_weekly_payment_statistics();
        });
    });
    function init_weekly_payment_statistics(data) {
        if ($('#weekly-payment-statistics').length > 0) {

            if (typeof(weekly_payments_statistics) !== 'undefined') {
                weekly_payments_statistics.destroy();
            }
            if (typeof(data) == 'undefined') {
                var currency = $('select[name="currency"]').val();
                $.get(admin_url + 'home/weekly_payments_statistics/' + currency, function(response) {
                    weekly_payments_statistics = new Chart($('#weekly-payment-statistics'), {
                        type: 'bar',
                        data: response,
                        options: {
                            responsive:true,
                            scales: {
                                yAxes: [{
                                  ticks: {
                                    beginAtZero: true,
                                }
                            }]
                        },
                    },
                });
                }, 'json');
            } else {
                weekly_payments_statistics = new Chart($('#weekly-payment-statistics'), {
                    type: 'bar',
                    data: data,
                    options: {
                        responsive: true,
                        scales: {
                            yAxes: [{
                              ticks: {
                                beginAtZero: true,
                            }
                        }]
                    },
                },
            });
            }

        }
    }
</script>
