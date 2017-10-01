<!-- Copy Project -->
<div class="modal fade" id="copy_project" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <?php echo form_open(admin_url('projects/copy/'.$project->id),array('id'=>'copy_form')); ?>
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">
                    <?php echo _l('copy_project'); ?>
                </h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">
                        <div class="checkbox checkbox-primary">
                            <input type="checkbox" class="copy" name="tasks" id="c_tasks" checked>
                            <label for="c_tasks"><?php echo _l('tasks'); ?></label>
                        </div>
                        <div class="checkbox checkbox-primary mleft10">
                            <input type="checkbox" name="tasks_include_checklist_items" id="tasks_include_checklist_items" checked>
                            <label for="tasks_include_checklist_items"><small><?php echo _l('copy_project_task_include_check_list_items'); ?></small></label>
                        </div>
                        <div class="checkbox checkbox-primary mleft10">
                            <input type="checkbox" name="task_include_assignees" id="task_include_assignees" checked>
                            <label for="task_include_assignees"><small><?php echo _l('copy_project_task_include_assignees'); ?></small></label>
                        </div>
                        <div class="checkbox checkbox-primary mleft10">
                            <input type="checkbox" name="task_include_followers" id="copy_project_task_include_followers" checked>
                            <label for="copy_project_task_include_followers"><small><?php echo _l('copy_project_task_include_followers'); ?></small></label>
                        </div>
                        <div class="checkbox checkbox-primary">
                            <input type="checkbox" name="milestones" id="c_milestones" checked>
                            <label for="c_milestones"><?php echo _l('project_milestones'); ?></label>
                        </div>
                        <div class="checkbox checkbox-primary">
                            <input type="checkbox" name="members" id="c_members" class="copy" checked>
                            <label for="c_members"><?php echo _l('project_members'); ?></label>
                        </div>
                        <hr />
                        <div class="copy-project-tasks-status-wrapper">
                            <p class="bold"><?php echo _l('copy_project_tasks_status'); ?></p>
                            <?php foreach($task_statuses as $cp_task_status){ ?>
                                <div class="radio radio-primary">
                                    <input type="radio" name="copy_project_task_status" value="<?php echo $cp_task_status['id']; ?>" id="cp_task_status_<?php echo $cp_task_status['id']; ?>"<?php if($cp_task_status['id'] == '1'){echo ' checked';} ?>>
                                    <label for="cp_task_status_<?php echo $cp_task_status['id']; ?>"><?php echo $cp_task_status['name']; ?></label>
                                </div>
                            <?php } ?>
                            <hr />
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <?php echo render_date_input('start_date','project_start_date',_d(date('Y-m-d'))); ?>
                            </div>
                            <div class="col-md-6">
                                <?php echo render_date_input('deadline','project_deadline'); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('close'); ?></button>
                <button type="submit" data-form="#copy_form" autocomplete="off" data-loading-text="<?php echo _l('wait_text'); ?>"  class="btn btn-info"><?php echo _l('copy_project'); ?></button>
            </div>
        </div>
        <!-- /.modal-content -->
        <?php echo form_close(); ?>
    </div>
    <!-- /.modal-dialog -->
</div>
<!-- /.modal -->
<!-- Copy Project end -->
