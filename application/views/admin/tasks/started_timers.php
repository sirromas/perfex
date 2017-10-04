<?php
$total_timers = count($startedTimers);
if ($total_timers == 0) {
    echo '<li class="text-center inline-block full-width">' . _l('no_timers_found') . '</li>';
}
$i = 0;
foreach ($startedTimers as $timer) {
    $task = $this->tasks_model->get($timer['task_id']);
    $data = '';
    if ($task) {
        $data .= '<li class="timer"><a href="#" class="_timer font-medium" onclick="init_task_modal(' . $timer['task_id'] . ');return false;">' . $task->name . '</a>';
        $data .= '<span class="text-muted">' . _l('timer_top_started', _dt($timer['start_time'], true)) . '</span><br /><span class="text-success">' . _l('task_total_logged_time') . ' ' . seconds_to_time_format($this->tasks_model->calc_task_total_time($task->id, ' AND staff_id=' . get_staff_user_id())) . '</span>';
        $data .= '<p class="mtop10"><a href="#" class="label label-danger" data-toggle="popover" data-html="true" data-trigger="manual" data-title="' . _l("note") . '" data-container="body" data-template="<div class=\'popover popover-top-timer-note\' role=\'tooltip\'><div class=\'arrow\'></div><h3 class=\'popover-title\'></h3><div class=\'popover-content\'></div></div>" data-content="' . htmlspecialchars(render_textarea("timesheet_note")) . '<button type=\'button\'  onclick=\'timer_action(this,' . $task->id . ',' . $timer['id'] . ');\' class=\'btn btn-info btn-xs\'>' . _l('save') . '</button>" onclick="return false;"><i class="fa fa-clock-o"></i> ' . _l('task_stop_timer') . '</a></p>';
        $data .= '</li>';
        if ($i >= 0 && $i != $total_timers - 1) {
            $data .= '<hr />';
        }
    }
    echo $data;
    $i ++;
}
/*
 * $assigned_staff_tasks_top = $this->tasks_model->get_tasks_by_staff_id(get_staff_user_id(),'status !=5 AND id NOT IN (select task_id FROM tbltaskstimers WHERE staff_id = '.get_staff_user_id().' AND end_time IS NULL)');
 *
 * if(count($assigned_staff_tasks_top) > 0){
 * echo '<li class="divider mtop15 inline-block full-width divider-top-started-timers"></li>';
 * echo '<li>';
 * echo '<a href="#" onclick="slideToggle(\'#top_start_timer\'); return false;" class="started-timers-button text-center text-uppercase"><span class="text-muted">'._l('task_start_timer').'</span></a>';
 * echo '<div id="top_start_timer" class="hide mtop15">';
 * foreach($assigned_staff_tasks_top as $top_task){
 * echo '<p class="mbot15"><a href="#" onclick="timer_action(this,'.$top_task['id'].'); return false;">'.$top_task['name'].'<i class="fa fa-clock-o pull-right"></i></a>';
 * if(!empty($top_task['rel_id'])){
 * echo '<br /><small class="text-muted">';
 * $top_task_rel_data = get_relation_data($top_task['rel_type'],$top_task['rel_id']);
 * $top_task_rel_value = get_relation_values($top_task_rel_data,$top_task['rel_type']);
 * echo _l('task_single_related').': '.$top_task_rel_value['name'];
 * echo '</small>';
 * }
 * echo '</p>';
 * }
 * echo '</div>';
 * echo '</li>';
 * } else {
 * if($total_timers == 0){
 * echo '<li class="divider mtop15 inline-block full-width divider-top-started-timers"></li>';
 * }
 * }
 */
if (is_admin()) {
    echo '<li>';
    echo '<a href="' . admin_url('staff/timesheets?view=all') . '" class="started-timers-button mtop15 text-center text-uppercase"><span class="text-muted">' . _l('view_members_timesheets') . '</span></a>';
    echo '</li>';
}
