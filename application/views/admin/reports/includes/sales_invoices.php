<?php

$CI = &get_instance();
$CI->load->helper('perfex_misc_helper');
$roleid=get_user_role();

?>

<div id="invoices-report" class="hide">
	<div class="row">
		<div class="col-md-3">
			<div class="form-group">
				<label for="invoice_status"><?php echo _l('report_invoice_status'); ?></label>
				<select name="invoice_status" class="selectpicker" multiple
					data-width="100%">
					<option value="" selected><?php echo _l('invoice_status_report_all'); ?></option>
                  <?php foreach($invoice_statuses as $status){ if($status ==5){continue;} ?>
                  <option value="<?php echo $status; ?>"><?php echo format_invoice_status($status,'',false) ?></option>
                  <?php } ?>
               </select>
			</div>
		</div>
         
         <?php if(count($invoices_sale_agents) > 0 ) { ?>
         <div class="col-md-3">
			<div class="form-group">
				<label for="sale_agent_invoices"><?php echo _l('sale_agent_string'); ?></label>
				<select name="sale_agent_invoices" class="selectpicker" multiple
					data-width="100%">
					<option value="" selected><?php echo _l('invoice_status_report_all'); ?></option>
                  <?php foreach($invoices_sale_agents as $agent){ ?>
                  <option value="<?php echo $agent['sale_agent']; ?>"><?php echo get_staff_full_name($agent['sale_agent']); ?></option>
                  <?php } ?>
               </select>
			</div>
		</div>
         <?php } ?>
          
         <?php

        // We use regions dropdown to populate teams data
        if (count($teams) >0)  { ?>
         <div class="col-md-3">
			<div class="form-group">
				<label for="regions"><?php echo _l('teams'); ?></label> <select
					name="regions" class="selectpicker" multiple data-width="100%">
                    <?php if ($roleid!=3) { ?>
					<option value="" selected><?php echo _l('teams_all'); ?></option>
                      <?php foreach($teams as $t){ ?>
                      <option value="<?php echo $t; ?>"><?php echo $t; ?></option>
                      <?php } ?>
                      <?php } // end if
                      else { ?>
                        <?php foreach($teams as $t){ ?>
                            <option value="<?php echo $t; ?>" selected><?php echo $t; ?></option>
                        <?php } ?>
                     <?php } ?>
               </select>
			</div>
		</div>
         <?php } ?> 
     
          
         <?php
        if (count($employees) > 0) {

            ?>
         <div class="col-md-3">
			<div class="form-group">
				<label for="employees"><?php echo _l('employee_string'); ?></label>
				<select name="employees" class="selectpicker" multiple
					data-width="100%">
                 	<option value="" selected><?php echo _l('employee_all'); ?></option>
                    <?php foreach($employees as $e){ ?>
                  <option value="<?php echo $e; ?>"><?php echo get_staff_full_name($e); ?></option>
                  <?php } ?>
               </select>
			</div>
		</div>
         <?php } ?>  
          
         <div class="clearfix"></div>
	</div>
	<table
		class="table table-striped table-invoices-report scroll-responsive">
		<thead>
			<tr>
				<th><?php echo _l('report_invoice_number'); ?></th>
				<th><?php echo _l('report_invoice_customer'); ?></th>
				<th><?php echo _l('region'); ?></th>
				<th><?php echo _l('employee_string'); ?></th>
				<th><?php echo _l('invoice_estimate_year'); ?></th>
				<th><?php echo _l('report_invoice_date'); ?></th>
				<th><?php echo _l('report_invoice_duedate'); ?></th>
				<th><?php echo _l('report_invoice_amount'); ?></th>
				<th><?php echo _l('report_invoice_amount_with_tax'); ?></th>
				<th><?php echo _l('report_invoice_total_tax'); ?></th>
                  <?php foreach($invoice_taxes as $tax){ ?>
                  <th><?php echo $tax['taxname']; ?> <small><?php echo $tax['taxrate']; ?>%</small></th>
                  <?php } ?>
                  <th><?php echo _l('invoice_discount'); ?></th>
				<th><?php echo _l('invoice_adjustment'); ?></th>
				<th><?php echo _l('report_invoice_amount_open'); ?></th>
				<th><?php echo _l('report_invoice_status'); ?></th>
			</tr>
		</thead>
		<tbody></tbody>
		<tfoot>
			<tr>
				<td></td>
				<td></td>
				<td></td>
				<td></td>
				<td></td>
				<td></td>
				<td></td>
				<td class="subtotal"></td>
				<td class="total"></td>
				<td class="total_tax"></td>
                  <?php foreach($invoice_taxes as $key => $tax){ ?>
                  <td class="total_tax_single_<?php echo $key; ?>"></td>
                  <?php } ?>
                  <td class="discount_total"></td>
				<td class="adjustment"></td>
				<td class="amount_open"></td>
				<td></td>
			</tr>
		</tfoot>
	</table>
</div>
