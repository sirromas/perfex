<?php

$CI = &get_instance();
$CI->load->helper('perfex_misc_helper');
$roleid=get_user_role();

?>

<div class="row" id="customer_report_controls" class="hide"
	style="display: none;">

	<div class="clearfix"></div>

    <?php if (count($teams) > 0) { ?>
        <div class="col-md-3">
		<div class="form-group">
			<label for="c_regions"><?php echo _l('teams'); ?></label> <select
				name="c_regions" class="selectpicker" multiple data-width="100%">
                <?php if ($roleid!=3) { ?>
				<option value="" selected><?php echo _l('teams_all'); ?></option>
                    <?php foreach ($teams as $t) { ?>
                        <option value="<?php echo $t; ?>"><?php echo $t; ?></option>
                    <?php } ?>
                <?php }
                else {
                foreach ($teams as $t) { ?>
                    <option value="<?php echo $t; ?>" selected><?php echo $t; ?></option>
                <?php }
                } ?>
                </select>
		</div>
	</div>
    <?php } ?>


    <?php
    if (count($employees) > 0) {
        ?>
        <div class="col-md-3">
		<div class="form-group">
			<label for="c_employees"><?php echo _l('employee_string'); ?></label>
			<select name="c_employees" class="selectpicker" multiple
				data-width="100%">
            	<option value="" selected><?php echo _l('employee_all'); ?></option>
                    <?php foreach ($employees as $e) { ?>
                        <option value="<?php echo $e; ?>"><?php echo get_staff_full_name($e); ?></option>
                    <?php } ?>
                </select>
		</div>
	</div>
    <?php } ?>

    <div class="clearfix"></div>

</div>

<div id="customers-report" class="hide">
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
