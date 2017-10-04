<?php init_head(); ?>
<div id="wrapper">
	<div class="content">
		<div class="row">
			<div class="col-md-12">
				<p class="text-info inline-block" data-placement="bottom"
					data-toggle="tooltip"
					data-title="<?php echo _l('leads_report_converted_notice'); ?>">
					<i class="fa fa-question-circle"></i>
				</p>
				<div class="panel_s">
					<div class="panel-body">
						<a href="<?php echo admin_url('reports/leads'); ?>"
							class="btn btn-success"><?php echo _l('switch_to_staff_report'); ?></a>
					</div>
				</div>
			</div>
			<div class="col-md-12 animated fadeIn">
				<div class="panel_s">
					<div class="panel-body">

                        <?php // echo form_open($this->uri->uri_string() . '?type=staff'); ?>

                        <!-- Reports control row div-->
						<div class="row">

							<!-- Calendar dates -->
							<div class="col-md-2">
                                <?php echo render_date_input('staff_report_from_date', 'from_date', $this->input->post('staff_report_from_date')); ?>
                            </div>

							<div class="col-md-2">
                                <?php echo render_date_input('staff_report_to_date', 'to_date', $this->input->post('staff_report_to_date')); ?>
                            </div>

                            <?php if (count($regions) > 0) { ?>
                                <div class="col-md-3">
								<div class="form-group">
									<label for="l_regions"><?php echo _l('regions'); ?></label> <select
										name="l_regions" id="l_regions" class="selectpicker"
										data-width="100%">
										<option value="" selected><?php echo _l('regions_all'); ?></option>
                                            <?php foreach ($regions as $r) { ?>
                                                <option
											value="<?php echo $r; ?>"><?php echo $r; ?></option>
                                            <?php } ?>
                                        </select>
								</div>
							</div>
                            <?php } ?>


                            <?php
                            if (count($staff) > 0) {
                                ?>
                                <div class="col-md-3">
								<div class="form-group">
									<label for="l_staff"><?php echo _l('employee_string'); ?></label>
									<select name="l_staff" id="l_staff" class="selectpicker"
										data-width="100%">
										<option value="" selected><?php echo _l('employee_all'); ?></option>
                                            <?php foreach ($staff as $e) { ?>
                                                <option
											value="<?php echo $e; ?>"><?php echo get_staff_full_name($e); ?></option>
                                            <?php } ?>
                                        </select>
								</div>
							</div>
                            <?php } ?>

                            <!-- Generate button -->
							<div class="col-md-1 text-left">
								<button type="submit" id="get_leads_report_data"
									class="btn btn-info label-margin"><?php echo _l('generate'); ?></button>
							</div>
						</div>

                        <?php // echo form_close(); ?>

                        <hr />
						<div class="relative" style="max-height: 380px">
							<canvas class="leads-staff-report mtop20" height="380"
								id="leads-staff-report"></canvas>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<?php init_tail(); ?>
<script type="text/javascript">
    $(document).ready(function () {
        console.log("ready!");
        var myChart; // global variable

        myChart = new Chart($('#leads-staff-report'), {
            data:<?php echo $leads_staff_report; ?>,
            type: 'bar',
            options: {responsive: true, maintainAspectRatio: false}
        });

        $("#get_leads_report_data").click(function () {
            myChart.destroy();
            var date1 = $('#staff_report_from_date').val();
            var date2 = $('#staff_report_to_date').val();
            var region = $('#l_regions').val();
            var staffid = $('#l_staff').val();
            var item = {
                date1: date1,
                date2: date2,
                region: region,
                staffid: staffid
            };

            var url = '/admin/reports/get_leads_data_ajax';
            $.post(url, {item: JSON.stringify(item)}).done(function (data) {
                myChart = new Chart($('#leads-staff-report'), {
                    data: JSON.parse(data),
                    type: 'bar',
                    options: {responsive: true, maintainAspectRatio: false}
                }); // end of chart
            }); // end of post
        }); // end of click function
    }); // end of document ready


</script>
</body>
</html>
