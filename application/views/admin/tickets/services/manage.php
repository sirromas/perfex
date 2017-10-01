<?php init_head(); ?>
<div id="wrapper">
	<div class="content">
		<div class="row">
			<div class="col-md-12">
				<div class="panel_s">
					<div class="panel-body">
						<div class="_buttons">
							<a href="#" onclick="new_service(); return false;" class="btn btn-info pull-left display-block"><?php echo _l('new_service'); ?></a>
						</div>
						<div class="clearfix"></div>
						<hr class="hr-panel-heading" />
						<div class="clearfix"></div>
						<?php render_datatable(array(
							_l('services_dt_name'),
							_l('options'),
							),'services'); ?>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="modal fade" id="service" tabindex="-1" role="dialog">
		<div class="modal-dialog">
			<?php echo form_open(admin_url('tickets/service')); ?>
			<div class="modal-content">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
					<h4 class="modal-title">
						<span class="edit-title"><?php echo _l('ticket_service_edit'); ?></span>
						<span class="add-title"><?php echo _l('new_service'); ?></span>
					</h4>
				</div>
				<div class="modal-body">
					<div class="row">
						<div class="col-md-12">
							<div id="additional"></div>
							<?php echo render_input('name','service_add_edit_name'); ?>
						</div>
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('close'); ?></button>
					<button type="submit" class="btn btn-info"><?php echo _l('submit'); ?></button>
				</div>
			</div><!-- /.modal-content -->
			<?php echo form_close(); ?>
		</div><!-- /.modal-dialog -->
	</div><!-- /.modal -->
	<?php init_tail(); ?>
	<script>
		$(function(){
			initDataTable('.table-services', window.location.href, [1], [1]);
			_validate_form($('form'),{name:'required'},manage_ticket_services);
			$('#service').on('hidden.bs.modal', function(event) {
				$('#additional').html('');
				$('#service input').val('');
				$('.add-title').removeClass('hide');
				$('.edit-title').removeClass('hide');
			});
		});
		function manage_ticket_services(form) {
			var data = $(form).serialize();
			var url = form.action;
			$.post(url, data).done(function(response) {
				window.location.reload();
			});
			return false;
		}
		function new_service(){
			$('#service').modal('show');
			$('.edit-title').addClass('hide');
		}
		function edit_service(invoker,id){
			var name = $(invoker).data('name');
			$('#additional').append(hidden_input('id',id));
			$('#service input[name="name"]').val(name);
			$('#service').modal('show');
			$('.add-title').addClass('hide');
		}

	</script>
</body>
</html>
