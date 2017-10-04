<?php echo render_input('settings[tasks_kanban_limit]','tasks_kanban_limit',get_option('tasks_kanban_limit'),'number'); ?>
<hr />
<?php echo render_yes_no_option('show_all_tasks_for_project_member','show_all_tasks_for_project_member'); ?>
<hr />
<?php render_yes_no_option('client_staff_add_edit_delete_task_comments_first_hour','settings_client_staff_add_edit_delete_task_comments_first_hour'); ?>
<hr />
<i class="fa fa-question-circle" data-toggle="tooltip"
	data-title="<?php echo _l('new_task_auto_assign_current_member_help'); ?>"></i>
<?php render_yes_no_option('new_task_auto_assign_current_member','new_task_auto_assign_current_member'); ?>
<hr />
<?php render_yes_no_option('auto_stop_tasks_timers_on_new_timer','auto_stop_tasks_timers_on_new_timer'); ?>
<hr />
<?php render_yes_no_option('timer_started_change_status_in_progress','timer_started_change_status_in_progress'); ?>
<hr />
<div class="form-group">
	<label for="default_task_priority" class="control-label"><?php echo _l('default_task_priority'); ?></label>
	<select name="settings[default_task_priority]" class="selectpicker"
		id="default_task_priority" data-width="100%"
		data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>">
		<option value="1"
			<?php if(get_option('default_task_priority') == 1){echo 'selected';} ?>><?php echo _l('task_priority_low'); ?></option>
		<option value="2"
			<?php if(get_option('default_task_priority') == 2){echo 'selected';} ?>><?php echo _l('task_priority_medium'); ?></option>
		<option value="3"
			<?php if(get_option('default_task_priority') == 3){echo 'selected';} ?>><?php echo _l('task_priority_high'); ?></option>
		<option value="4"
			<?php if(get_option('default_task_priority') == 4){echo 'selected';} ?>><?php echo _l('task_priority_urgent'); ?></option>
	</select>
	<hr />
	<div class="form-group">
		<label for="settings[task_modal_class]" class="control-label">
      <?php echo _l('modal_width_class'); ?> (modal-lg, modal-xl, modal-xxl)
    </label> <input type="text" id="settings[task_modal_class]"
			name="settings[task_modal_class]" class="form-control"
			value="<?php echo get_option('task_modal_class'); ?>">
	</div>
</div>
