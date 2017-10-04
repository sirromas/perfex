<?php
if (total_rows('tblstafftasks', array(
    'rel_id' => $project->id,
    'rel_type' => 'project'
)) == 0) {
    echo '<div class="alert alert-info">' . _l('no_tasks_found') . '</div>';
}
if (has_permission('projects', '', 'create')) {
    ?>
<a href="#" class="btn btn-info" onclick="new_milestone();return false;"><?php echo _l('new_milestone'); ?></a>
<?php } ?>
<?php if(has_permission('tasks','','create')){ ?>
<a href="#" class="btn btn-info new-task-phase"
	onclick="new_task_from_relation('.table-rel-tasks','project',<?php echo $project->id; ?>); return false;"><?php echo _l('new_task'); ?></a>
<?php } ?>
<a href="#" class="btn btn-default"
	onclick="milestones_switch_view(); return false;"><i
	class="fa fa-th-list"></i></a>
<div class="tasks-phases">
	<div class="checkbox mtop30">
		<input type="checkbox" value="yes" id="exclude_completed_tasks"
			name="exclude_completed_tasks"
			<?php if($milestones_exclude_completed_tasks){echo ' checked';} ?>
			onclick="window.location.href = '<?php echo admin_url('projects/view/'.$project->id.'?group=project_milestones&exclude_completed='); ?>'+(this.checked ? 'yes' : 'no')">
		<label for="exclude_completed_tasks"><?php echo _l('exclude_completed_tasks') ?></label>
	</div>
<?php
$milestones = array();
$milestones[] = array(
    'name' => _l('milestones_uncategorized'),
    'id' => 0,
    'total_logged_time' => $this->projects_model->calc_milestone_logged_time($project->id, 0),
    'color' => NULL
);
$_milestones = $this->projects_model->get_milestones($project->id);
foreach ($_milestones as $m) {
    $milestones[] = $m;
}
?>
<div class="row">
  <?php
$m = 1;
foreach ($milestones as $milestone) {
    $milestonesTasksWhere = array(
        'milestone' => $milestone['id']
    );
    if ($milestones_exclude_completed_tasks) {
        $milestonesTasksWhere['status !='] = 5;
    }
    $tasks = $this->projects_model->get_tasks($project->id, $milestonesTasksWhere, true);
    $cpicker = '';
    if (has_permission('projects', '', 'create') && $milestone['id'] != 0) {
        foreach (get_system_favourite_colors() as $color) {
            $color_selected_class = 'cpicker-small';
            $cpicker .= "<div class='kanban-cpicker cpicker " . $color_selected_class . "' data-color='" . $color . "' style='background:" . $color . ";border:1px solid " . $color . "'></div>";
        }
    }
    $total_project_tasks = total_rows('tblstafftasks', array(
        'rel_type' => 'project',
        'rel_id' => $project->id,
        'milestone' => $milestone['id']
    ));
    $total_finished_tasks = total_rows('tblstafftasks', array(
        'rel_type' => 'project',
        'rel_id' => $project->id,
        'status' => 5,
        'milestone' => $milestone['id']
    ));
    $percent = 0;
    if ($total_finished_tasks >= floatval($total_project_tasks)) {
        $percent = 100;
    } else {
        if ($total_project_tasks !== 0) {
            $percent = number_format(($total_finished_tasks * 100) / $total_project_tasks, 2);
        }
    }
    $milestone_color = '';
    if (! empty($milestone["color"]) && ! is_null($milestone['color']) && has_permission('projects', '', 'create')) {
        $milestone_color = ' style="background:' . $milestone["color"] . ';border:1px solid ' . $milestone['color'] . '"';
    }
    ?>
  <div
			class="col-md-3 mtop25 milestone-column<?php if($milestone['id'] == 0 && count($tasks) == 0){echo ' hide';} ?>"
			data-milestone-id="<?php echo $milestone['id']; ?>">
			<div
				class="panel-heading panel-heading-bg <?php if($milestone_color != ''){echo 'color-not-auto-adjusted color-white ';} ?><?php if($milestone['id'] != 0){echo 'task-phase';}else{echo 'info-bg';} ?>"
				<?php echo $milestone_color; ?>>
     <?php if($milestone['id'] != 0){ ?>
     <i class="fa fa-file-text pointer" aria-hidden="true"
					data-toggle="popover"
					data-title="<?php echo _l('milestone_description'); ?>"
					data-html="true"
					data-content="<?php echo htmlspecialchars($milestone['description']); ?>"></i>&nbsp;
     <?php } ?>
     <?php if($milestone['id'] != 0 && has_permission('projects','','edit')){ ?>
     <a href="#"
					data-description-visible-to-customer="<?php echo $milestone['description_visible_to_customer']; ?>"
					data-description="<?php echo htmlspecialchars(clear_textarea_breaks($milestone['description'])); ?>"
					data-name="<?php echo $milestone['name']; ?>"
					data-due_date="<?php echo _d($milestone['due_date']); ?>"
					data-order="<?php echo $milestone['milestone_order']; ?>"
					onclick="edit_milestone(this,<?php echo $milestone['id']; ?>); return false;"
					class="edit-milestone-phase <?php if($milestone['color'] != ''){echo 'color-white';} ?>">
      <?php } ?>
      <span class="bold"><?php echo $milestone['name']; ?></span>
      <?php if($milestone['id'] != 0 && has_permission('projects','','edit')){ ?>
    </a>
    <?php } ?>
    <?php if($milestone['id'] != 0 && (has_permission('tasks','','create') || has_permission('projects','','create'))){  ?>
    <a href="#" onclick="return false;" class="pull-right text-dark"
					data-placement="bottom" data-toggle="popover"
					data-content="
    <div  class='text-center'><?php if(has_permission('tasks','','create')){ ?><button
						type='button' return
						false;' class='btn btn-success btn-block mtop10 new-task-to-milestone'>
      <?php echo _l('new_task'); ?>
    </button>
    <?php } ?>
  
			
			</div>
  <?php if($cpicker != ''){echo '<hr />';}; ?>
  <div class='kan-ban-settings cpicker-wrapper'>
    <?php echo $cpicker; ?>
  </div>
			<a href='#'
				class='reset_milestone_color <?php if($milestone_color == ''){echo 'hide';} ?>'
				data-color=''>
    <?php echo _l('reset_to_default_color'); ?>
  </a>" data-html="true" data-trigger="focus"> <i
				class="fa fa-angle-down"></i> </a>
<?php } ?>
<?php if(has_permission('tasks','','create')){ ?>
<?php

        
echo '<br /><small>' . _l('milestone_total_logged_time') . ': ' . seconds_to_time_format($milestone['total_logged_time']) . '</small>';
    }
    ?>
</div>
		<div class="panel-body">
  <?php
    echo '<ul class="milestone-tasks-wrapper ms-task">';
    echo '<li class="inline-block"></li>';
    foreach ($tasks as $task) {
        $assignees = $this->tasks_model->get_task_assignees($task['id']);
        $current_user_is_assigned = $this->tasks_model->is_task_assignee(get_staff_user_id(), $task['id']);
        ?>
   <li data-task-id="<?php echo $task['id']; ?>"
				class="task<?php if(has_permission('tasks','','create') || has_permission('tasks','','edit')){echo ' sortable';} ?><?php if($current_user_is_assigned){echo ' current-user-task';} if((!empty($task['duedate']) && $task['duedate'] < date('Y-m-d')) && $task['status'] != 5){ echo ' overdue-task'; } ?>">
				<div class="media">
      <?php
        if (count($assignees) > 0) {
            ?>
      <div class="media-left">
        <?php
            
if ($current_user_is_assigned) {
                echo staff_profile_image(get_staff_user_id(), array(
                    'staff-profile-image-small pull-left'
                ), 'small', array(
                    'data-toggle' => 'tooltip',
                    'data-title' => _l('project_task_assigned_to_user')
                ));
            }
            foreach ($assignees as $assigned) {
                if ($assigned['assigneeid'] != get_staff_user_id()) {
                    echo staff_profile_image($assigned['assigneeid'], array(
                        'staff-profile-image-xs sub-staff-assigned-milestone pull-left'
                    ), 'small', array(
                        'data-toggle' => 'tooltip',
                        'data-title' => $assigned['firstname'] . ' ' . $assigned['lastname']
                    ));
                }
            }
            ?>
   </div>

   <?php } ?>
   <div class="media-body">
						<a href="#"
							class="task_milestone pull-left mtop5<?php if($task['status'] == 5){echo ' text-muted line-throught';} ?>"
							onclick="init_task_modal(<?php echo $task['id']; ?>); return false;"><?php echo $task['name']; ?></a>
    <?php if(has_permission('tasks','','create')){ ?>
    <small><?php echo _l('task_total_logged_time'); ?>
      <b>
        <?php echo seconds_to_time_format($task['total_logged_time']); ?>
      </b>
      <?php } ?>
    </small> <br /> <small><?php echo _l('task_status'); ?>: <?php echo format_task_status($task['status'],true); ?></small>
						<br /> <small><?php echo _l('tasks_dt_datestart'); ?>: <b><?php echo _d($task['startdate']); ?></b></small>
    <?php if(is_date($task['duedate'])){ ?>
    -
    <small><?php echo _l('task_duedate'); ?>: <b><?php echo _d($task['duedate']); ?></b></small>
    <?php } ?>
  </div>
				</div>
			</li>
<?php } ?>
</ul>
		</div>
		<div class="panel-footer">
			<div class="progress no-margin progress-bg-dark">
				<div class="progress-bar not-dynamic progress-bar-success"
					role="progressbar" aria-valuenow="40" aria-valuemin="0"
					aria-valuemax="100" style="width: 0%"
					data-percent="<?php echo $percent; ?>"></div>
			</div>
		</div>
	</div>
<?php if($m == 4){echo '<div class="clearfix"></div>';} ?>
<?php $m++;} ?>
</div>
</div>
<div id="milestones-table" class="hide mtop25">
  <?php render_datatable(array(
    _l('milestone_name'),
    _l('milestone_due_date'),
    _l('options')
    ),'milestones'); ?>
  </div>

