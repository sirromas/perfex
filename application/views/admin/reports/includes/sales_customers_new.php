<div class="row" id="customer_report_controls" class="hide"
     style="display: none;">

    <div class="clearfix"></div>

</div>

<div id="new-customers" class="hide">
    <?php

    render_datatable(array(
        _l('reports_sales_dt_customers_client'),
        _l('region'),
        _l('employee_string'),
        _l('reports_sales_dt_customers_total_invoices'),
        _l('reports_sales_dt_items_customers_amount'),
        _l('reports_sales_dt_items_customers_amount_with_tax')
    ), 'customers-report scroll-responsive');
    ?>
</div>
