
<div id="items-report" class="hide">
    <?php if($mysqlVersion && strpos($mysqlVersion->version,'5.6') !== FALSE && $sqlMode && strpos($sqlMode->mode,'ONLY_FULL_GROUP_BY') !== FALSE){ ?>
    <div class="alert alert-danger">Sales Report may not work properly
		because ONLY_FULL_GROUP_BY is enabled, consult with your hosting
		provider to disable ONLY_FULL_GROUP_BY in sql_mode configuration. In
		case the items report is working properly you can just ignore this
		message.</div>
    <?php } ?>
    <!--<p class="mbot20 text-info"><?php echo _l('item_report_paid_invoices_notice'); ?></p>-->
    
    <div class="row">
        
        <?php if(count($item_products) > 0 ) { ?>
        <div class="col-md-3">
            <div class="form-group">
                <label for="item_products"><?php echo _l('item_products'); ?></label>
                <select name="item_products" class="selectpicker" data-width="100%">
                    <option value="" selected><?php echo _l('products_all'); ?></option>
                    <?php foreach($item_products as $item){ ?>
                        <option value="<?php echo $item; ?>"><?php echo $item; ?></option>
                    <?php } ?>
                </select>
            </div>
        </div>
        <?php } ?>
        

        <?php if(count($teams) > 0 ) { ?>
        <div class="col-md-3">
            <div class="form-group">
                <label for="i_regions"><?php echo _l('teams'); ?></label>
                <select name="i_regions" class="selectpicker" multiple data-width="100%">
                    <option value="" selected><?php echo _l('teams_all'); ?></option>
                    <?php foreach($teams as $t){ ?>
                        <option value="<?php echo $t; ?>"><?php echo $t; ?></option>
                    <?php } ?>
                </select>
            </div>
        </div>
        <?php } ?>


         <?php
        if(count($employees) > 0 ) { ?>
        <div class="col-md-3">
            <div class="form-group">
                <label for="i_employees"><?php echo _l('employee_string'); ?></label>
                <select name="i_employees" class="selectpicker" multiple data-width="100%">
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
    
    <?php if(count($invoices_sale_agents) > 0 ) { ?>
    <div class="row">
		<div class="col-md-4">
			<div class="form-group">
				<label for="sale_agent_items"><?php echo _l('sale_agent_string'); ?></label>
				<select name="sale_agent_items" class="selectpicker" multiple
					data-width="100%">
					<option value="" selected><?php echo _l('invoice_status_report_all'); ?></option>
        <?php foreach($invoices_sale_agents as $agent){ ?>
        <option value="<?php echo $agent['sale_agent']; ?>"><?php echo get_staff_full_name($agent['sale_agent']); ?></option>
        <?php } ?>
      </select>
			</div>
		</div>
	</div>
<?php } ?>
  <table
		class="table table-striped table-items-report scroll-responsive">
		<thead>
			<tr>
			    
			    <!--  
			    <th><?php echo _l('reports_item'); ?></th>
				<th><?php echo _l('quantity_sold'); ?></th>
				<th><?php echo _l('total_amount'); ?></th>
				<th><?php echo _l('avg_price'); ?></th>
			    -->
			      
				<th><?php echo _l('reports_item'); ?></th>
				<th><?php echo _l('total_items'); ?></th>
				<th><?php echo _l('invoice_item_date'); ?></th>
				<th><?php echo _l('region'); ?></th>
				<th><?php echo _l('manager'); ?></th>
				
			</tr>
		</thead>
		<tbody>

		</tbody>
		<tfoot>
			<tr>
				<td></td>
				<td class="qty"></td>
				<td class="amount"></td>
			</tr>
		</tfoot>
	</table>
</div>
