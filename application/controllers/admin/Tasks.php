<?php
defined('BASEPATH') or exit('No direct script access allowed');
class Tasks extends Admin_controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('projects_model');
    }

    /* Open also all taks if user access this /tasks url */
    public function index($id = '')
    {
        $this->list_tasks($id);
    }

    /* List all tasks */
    public function list_tasks($id = '')
    {

        // if passed from url
        $_custom_view = '';
        if ($this->input->get('custom_view')) {
            $_custom_view = $this->input->get('custom_view');
        }

        if ($this->input->is_ajax_request()) {
            if ($this->input->get('kanban')) {
                $data = array();
                echo $this->load->view('admin/tasks/kan_ban', $data, true);
                die();
            } else {
                $this->perfex_base->get_table_data('tasks');
            }
        }
        $data['taskid'] = '';
        if (is_numeric($id)) {
            $data['taskid'] = $id;
        }

        if ($this->input->get('kanban')) {
            $this->switch_kanban(0, true);
        }

        $data['switch_kanban'] = false;
        $data['bodyclass']     = 'tasks_page';
        if ($this->session->has_userdata('tasks_kanban_view') && $this->session->userdata('tasks_kanban_view') == 'true') {
            $data['switch_kanban'] = true;
            $data['bodyclass']     = 'tasks_page kan-ban-body';
        }

        $data['custom_view'] = $_custom_view;
        $data['title']       = _l('tasks');
        $this->load->view('admin/tasks/manage', $data);
    }

    public function tasks_kanban_load_more()
    {
        $status = $this->input->get('status');
        $page   = $this->input->get('page');

        $where = array();
        if ($this->input->get('project_id')) {
            $where['rel_id']   = $this->input->get('project_id');
            $where['rel_type'] = 'project';
        }

        $tasks = $this->tasks_model->do_kanban_query($status, $this->input->get('search'), $page, false, $where);

        foreach ($tasks as $task) {
            $this->load->view('admin/tasks/_kan_ban_card', array(
                'task' => $task,
                'status' => $status
            ));
        }
    }

    public function update_order()
    {
        $this->tasks_model->update_order($this->input->post());
    }

    public function switch_kanban($set = 0, $manual = false)
    {
        if ($set == 1) {
            $set = 'false';
        } else {
            $set = 'true';
        }

        $this->session->set_userdata(array(
            'tasks_kanban_view' => $set
        ));
        if ($manual == false) {
            // clicked on VIEW KANBAN from projects area and will redirect again to the same view
            if (strpos($_SERVER['HTTP_REFERER'], 'project_id') !== false) {
                redirect(admin_url('tasks'));
            } else {
                redirect($_SERVER['HTTP_REFERER']);
            }
        }
    }

    public function update_task_description($id)
    {
        if (has_permission('tasks', '', 'edit')) {
            $this->db->where('id', $id);
            $this->db->update('tblstafftasks', array(
                'description' => $this->input->post('description', false)
            ));
        }
    }

    public function detailed_overview()
    {
        $overview = array();

        $has_permission_create = has_permission('tasks', '', 'create');
        $has_permission_view = has_permission('tasks', '', 'view');

        if (!$has_permission_create) {
            $staff_id = get_staff_user_id();
        } elseif ($this->input->post('member')) {
            $staff_id = $this->input->post('member');
        } else {
            $staff_id = '';
        }

        $month  = ($this->input->post('month') ? $this->input->post('month') : date('m'));
        if ($this->input->post() && $this->input->post('month') == '') {
            $month = '';
        }

        $status = $this->input->post('status');

        $fetch_month_from = 'startdate';

        $year             = ($this->input->post('year') ? $this->input->post('year') : date('Y'));
        $project_id = $this->input->get('project_id');

        for ($m = 1; $m <= 12; $m++) {
            if ($month != '' && $month != $m) {
                continue;
            }
            $this->db->where('MONTH(' . $fetch_month_from . ')', $m);
            $this->db->where('YEAR(' . $fetch_month_from . ')', $year);

            if ($project_id && $project_id != '') {
                $this->db->where('rel_id', $project_id);
                $this->db->where('rel_type', 'project');
            }

            if (is_numeric($staff_id)) {
                $this->db->where('(id IN (SELECT taskid FROM tblstafftaskassignees WHERE staffid=' . $staff_id . '))');
            }

            // User dont have permission for view but have for create
            // Only show tasks createad by this user.
            if (!$has_permission_view && $has_permission_create) {
                $this->db->where('addedfrom', get_staff_user_id());
            }

            if ($status) {
                $this->db->where('status', $status);
            }

            $this->db->order_by($fetch_month_from, 'ASC');
            array_push($overview, $m);
            $overview[$m] = $this->db->get('tblstafftasks')->result_array();
        }

        unset($overview[0]);

        $overview = array(
            'staff_id' => $staff_id,
            'detailed' => $overview
        );

        $data['members']  = $this->staff_model->get();
        $data['overview'] = $overview['detailed'];
        $data['years']    = $this->tasks_model->get_distinct_tasks_years(($this->input->post('month_from') ? $this->input->post('month_from') : 'startdate'));
        $data['staff_id'] = $overview['staff_id'];
        $data['title']    = _l('detailed_overview');
        $this->load->view('admin/tasks/detailed_overview', $data);
    }

    public function init_relation_tasks($rel_id, $rel_type)
    {
        if ($this->input->is_ajax_request()) {
            $this->perfex_base->get_table_data('tasks_relations', array(
                'rel_id' => $rel_id,
                'rel_type' => $rel_type
            ));
        }
    }

    /* Add new task or update existing */
    public function task($id = '')
    {
        if (!has_permission('tasks', '', 'edit') && !has_permission('tasks', '', 'create')) {
            access_denied('Tasks');
        }

        $data = array();
        // FOr new task add directly from the projects milestones
        if ($this->input->get('milestone_id')) {
            $this->db->where('id', $this->input->get('milestone_id'));
            $milestone = $this->db->get('tblmilestones')->row();
            if ($milestone) {
                $data['_milestone_selected_data'] = array(
                    'id' => $milestone->id,
                    'due_date' => _d($milestone->due_date)
                );
            }
        }
        if ($this->input->get('start_date')) {
            $data['start_date'] = $this->input->get('start_date');
        }
        if ($this->input->post()) {
            $data                = $this->input->post();
            $data['description'] = $this->input->post('description', false);
            if ($id == '') {
                if (!has_permission('tasks', '', 'create')) {
                    header('HTTP/1.0 400 Bad error');
                    echo json_encode(array(
                        'success' => false,
                        'message' => _l('access_denied')
                    ));
                    die;
                }
                $id      = $this->tasks_model->add($data);
                $_id     = false;
                $success = false;
                $message = '';
                if ($id) {
                    $success = true;
                    $_id     = $id;
                    $message = _l('added_successfully', _l('task'));
                    $uploadedFiles = handle_task_attachments_array($id);
                    if($uploadedFiles && is_array($uploadedFiles)){
                        foreach($uploadedFiles as $file){
                            $this->misc_model->add_attachment_to_database($id,'task',array($file));
                        }
                    }
                }
                echo json_encode(array(
                    'success' => $success,
                    'id' => $_id,
                    'message' => $message
                ));
            } else {
                if (!has_permission('tasks', '', 'edit')) {
                    header('HTTP/1.0 400 Bad error');
                    echo json_encode(array(
                        'success' => false,
                        'message' => _l('access_denied')
                    ));
                    die;
                }
                $success = $this->tasks_model->update($data, $id);
                $message = '';
                if ($success) {
                    $message = _l('updated_successfully', _l('task'));
                }
                echo json_encode(array(
                    'success' => $success,
                    'message' => $message,
                    'id' => $id
                ));
            }
            die;
        }

        $data['milestones'] = array();
        $data['checklistTemplates'] = $this->tasks_model->get_checklist_templates();
        if ($id == '') {
            $title = _l('add_new', _l('task_lowercase'));
        } else {
            $data['task'] = $this->tasks_model->get($id);
            if ($data['task']->rel_type == 'project') {
                $data['milestones'] = $this->projects_model->get_milestones($data['task']->rel_id);
            }
            $title = _l('edit', _l('task_lowercase')) . ' ' . $data['task']->name;
        }
        $data['project_end_date_attrs'] = array();
        if ($this->input->get('rel_type') == 'project' && $this->input->get('rel_id')) {
            $project = $this->projects_model->get($this->input->get('rel_id'));
            if ($project->deadline) {
                $data['project_end_date_attrs'] = array(
                    'data-date-end-date' => $project->deadline
                );
            }
        }
        $data['id']    = $id;
        $data['title'] = $title;
        $this->load->view('admin/tasks/task', $data);
    }

    public function copy()
    {
        if (has_permission('tasks', '', 'create')) {
            $new_task_id = $this->tasks_model->copy($this->input->post());
            $response    = array(
                'new_task_id' => '',
                'alert_type' => 'warning',
                'message' => _l('failed_to_copy_task'),
                'success' => false
            );
            if ($new_task_id) {
                $response['message']     = _l('task_copied_successfully');
                $response['new_task_id'] = $new_task_id;
                $response['success']     = true;
                $response['alert_type']  = 'success';
            }
            echo json_encode($response);
        }
    }

    public function get_billable_task_data($task_id)
    {
        $task              = $this->tasks_model->get_billable_task_data($task_id);
        $task->description = seconds_to_time_format($task->total_seconds) . ' ' . _l('hours');
        echo json_encode($task);
    }

    /* Get task data in a right pane */
    public function get_task_data()
    {
        $taskid = $this->input->post('taskid');
        // Task main data
        $task   = $this->tasks_model->get($taskid);
        if (!$task) {
            header("HTTP/1.0 404 Not Found");
            echo 'Task not found';
            die();
        }

        $data['checklistTemplates'] = $this->tasks_model->get_checklist_templates();
        $data['task']           = $task;
        $data['id']             = $task->id;
        $data['staff']          = $this->staff_model->get('', 1);
        $data['task_is_billed'] = $this->tasks_model->is_task_billed($taskid);

        $this->load->view('admin/tasks/view_task_template', $data);
    }

    public function get_staff_started_timers()
    {
        $data['startedTimers'] = $this->misc_model->get_staff_started_timers();
        $_data['html']           = $this->load->view('admin/tasks/started_timers', $data, true);
        if (count($data['startedTimers']) > 0) {
            $_data['timers_found'] = true;
        }
        echo json_encode($_data);
    }

    public function save_checklist_item_template(){
        if(has_permission('checklist_templates','','create')){
            $id = $this->tasks_model->add_checklist_template($this->input->post('description'));
            echo json_encode(array('id'=>$id));
        }
    }

    public function remove_checklist_item_template($id){
        if(has_permission('checklist_templates','','delete')){
            $success = $this->tasks_model->remove_checklist_item_template($id);
            echo json_encode(array('success'=>$success));
        }
    }

    public function init_checklist_items()
    {
        if ($this->input->is_ajax_request()) {
            if ($this->input->post()) {
                $post_data          = $this->input->post();
                $data['task_id']    = $post_data['taskid'];
                $data['checklists'] = $this->tasks_model->get_checklist_items($post_data['taskid']);
                $this->load->view('admin/tasks/checklist_items_template', $data);
            }
        }
    }

    public function task_tracking_stats($task_id)
    {
        $data['stats'] = json_encode($this->tasks_model->task_tracking_stats($task_id));
        $this->load->view('admin/tasks/tracking_stats', $data);
    }

    public function checkbox_action($listid, $value)
    {
        $this->db->where('id', $listid);
        $this->db->update('tbltaskchecklists', array(
            'finished' => $value
        ));

        if ($this->db->affected_rows() > 0) {
            if ($value == 1) {
                $this->db->where('id', $listid);
                $this->db->update('tbltaskchecklists', array(
                    'finished_from' => get_staff_user_id()
                ));
                do_action('task_checklist_item_finished', $listid);
            }
        }
    }

    public function add_checklist_item()
    {
        if ($this->input->is_ajax_request()) {
            if ($this->input->post()) {
                echo json_encode(array(
                    'success' => $this->tasks_model->add_checklist_item($this->input->post())
                ));
            }
        }
    }

    public function update_checklist_order()
    {
        if ($this->input->is_ajax_request()) {
            if ($this->input->post()) {
                $this->tasks_model->update_checklist_order($this->input->post());
            }
        }
    }

    public function delete_checklist_item($id)
    {
        $list = $this->tasks_model->get_checklist_item($id);
        if (has_permission('tasks', '', 'delete') || $list->addedfrom == get_staff_user_id()) {
            if ($this->input->is_ajax_request()) {
                echo json_encode(array(
                    'success' => $this->tasks_model->delete_checklist_item($id)
                ));
            }
        }
    }

    public function update_checklist_item()
    {
        if ($this->input->is_ajax_request()) {
            if ($this->input->post()) {
                $desc = $this->input->post('description');
                $desc = trim($desc);
                $this->tasks_model->update_checklist_item($this->input->post('listid'), $desc);
                echo json_encode(array('can_be_template'=>(total_rows('tblcheckliststemplates',array('description'=>$desc)) == 0)));
            }
        }
    }

    public function make_public($task_id)
    {
        if (!has_permission('tasks', '', 'edit')) {
            json_encode(array(
                'success' => false
            ));
            die;
        }
        echo json_encode(array(
            'success' => $this->tasks_model->make_public($task_id)
        ));
    }

    public function add_external_attachment()
    {
        if ($this->input->post()) {
            $this->tasks_model->add_attachment_to_database($this->input->post('task_id'), $this->input->post('files'), $this->input->post('external'));
        }
    }

    /* Add new task comment / ajax */
    public function add_task_comment()
    {
        echo json_encode(array(
            'success' => $this->tasks_model->add_task_comment($this->input->post(null, false))
        ));
    }

    /* Add new task follower / ajax */
    public function add_task_followers()
    {
        if (has_permission('tasks', '', 'edit') || has_permission('tasks', '', 'create')) {
            echo json_encode(array(
                'success' => $this->tasks_model->add_task_followers($this->input->post())
            ));
        }
    }

    /* Add task assignees / ajax */
    public function add_task_assignees()
    {
        if (has_permission('tasks', '', 'edit') || has_permission('tasks', '', 'create')) {
            echo json_encode(array(
                'success' => $this->tasks_model->add_task_assignees($this->input->post())
            ));
        }
    }

    public function edit_comment()
    {
        if ($this->input->post()) {
            $success = $this->tasks_model->edit_comment($this->input->post(null, false));
            $message = '';
            if ($success) {
                $message = _l('task_comment_updated');
            }
            echo json_encode(array(
                'success' => $success,
                'message' => $message
            ));
        }
    }

    /* Remove task comment / ajax */
    public function remove_comment($id)
    {
        echo json_encode(array(
            'success' => $this->tasks_model->remove_comment($id)
        ));
    }

    /* Remove assignee / ajax */
    public function remove_assignee($id, $taskid)
    {
        if (has_permission('tasks', '', 'edit') && has_permission('tasks', '', 'create')) {
            $success = $this->tasks_model->remove_assignee($id, $taskid);
            $message = '';
            if ($success) {
                $message = _l('task_assignee_removed');
            }
            echo json_encode(array(
                'success' => $success,
                'message' => $message
            ));
        }
    }

    /* Remove task follower / ajax */
    public function remove_follower($id, $taskid)
    {
        if (has_permission('tasks', '', 'edit') && has_permission('tasks', '', 'create')) {
            $success = $this->tasks_model->remove_follower($id, $taskid);
            $message = '';
            if ($success) {
                $message = _l('task_follower_removed');
            }
            echo json_encode(array(
                'success' => $success,
                'message' => $message
            ));
        }
    }

    /* Mark task as complete / ajax*/
    public function mark_complete($id)
    {
        $success = $this->tasks_model->mark_complete($id);
        $message = '';
        if ($success) {
            $message = _l('task_marked_as_complete');
        }
        echo json_encode(array(
            'success' => $success,
            'message' => $message
        ));
    }

    public function mark_as($status, $id)
    {
        $success = $this->tasks_model->mark_as($status, $id);
        $message = '';

        if ($success) {
            $message = _l('task_marked_as_success', format_task_status($status, true, true));
        }
        echo json_encode(array(
            'success' => $success,
            'message' => $message
        ));
    }

    /* Unmark task as complete / ajax*/
    public function unmark_complete($id)
    {
        $success = $this->tasks_model->unmark_complete($id);
        $message = '';
        if ($success) {
            $message = _l('task_unmarked_as_complete');
        }
        echo json_encode(array(
            'success' => $success,
            'message' => $message
        ));
    }

    /* Delete task from database */
    public function delete_task($id)
    {
        if (!has_permission('tasks', '', 'delete')) {
            access_denied('tasks');
        }
        $success = $this->tasks_model->delete_task($id);
        $message = _l('problem_deleting', _l('task_lowercase'));
        if ($success) {
            $message = _l('deleted', _l('task'));
            set_alert('success', $message);
        } else {
            set_alert('warning', $message);
        }

        if (strpos($_SERVER['HTTP_REFERER'], 'tasks/index') !== false || strpos($_SERVER['HTTP_REFERER'], 'tasks/view') !== false) {
            redirect(admin_url('tasks'));
        } elseif (preg_match("/projects\/view\/[1-9]+/", $_SERVER['HTTP_REFERER'])) {
            $project_url = explode('?', $_SERVER['HTTP_REFERER']);
            redirect($project_url[0].'?group=project_tasks');
        } else {
            redirect($_SERVER['HTTP_REFERER']);
        }
    }

    /**
     * Remove task attachment
     * @since  Version 1.0.1
     * @param  mixed $id attachment it
     * @return json
     */
    public function remove_task_attachment($id)
    {
        if ($this->input->is_ajax_request()) {
            echo json_encode(array(
                'success' => $this->tasks_model->remove_task_attachment($id)
            ));
        }
    }

    /**
     * Upload task attachment
     * @since  Version 1.0.1
     */
    public function upload_file()
    {
        if ($this->input->post()) {
            $taskid = $this->input->post('taskid');
            $file   = handle_tasks_attachments($taskid);
            if ($file) {
                $files   = array();
                $files[] = $file;
                $success = $this->tasks_model->add_attachment_to_database($taskid, $file);
                echo json_encode(array(
                    'success' => $success
                ));
            }
        }
    }

    public function timer_tracking()
    {
        echo json_encode(array(
            'success' => $this->tasks_model->timer_tracking($this->input->post('task_id'), $this->input->post('timer_id'), nl2br($this->input->post('note')))
        ));
    }

    public function delete_timesheet($id)
    {
        if (has_permission('tasks', '', 'delete') || has_permission('projects', '', 'delete')) {
            $alert_type = 'warning';
            $success    = $this->tasks_model->delete_timesheet($id);
            if ($success) {
                $message = _l('deleted', _l('project_timesheet'));
                set_alert('success', $message);
            }
            if (!$this->input->is_ajax_request()) {
                redirect($_SERVER['HTTP_REFERER']);
            }
        }
    }

    public function log_time()
    {
        $success = $this->tasks_model->timesheet($this->input->post());
        if ($success === true) {
            $this->session->set_flashdata('task_single_timesheets_open', true);
            $message = _l('added_successfully', _l('project_timesheet'));
        } elseif (is_array($success) && isset($success['end_time_smaller'])) {
            $message = _l('failed_to_add_project_timesheet_end_time_smaller');
        } else {
            $message = _l('project_timesheet_not_updated');
        }

        echo json_encode(array(
            'success' => $success,
            'message' => $message
        ));
        die;
    }

    public function update_tags()
    {
        if (has_permission('tasks', '', 'create') || has_permission('tasks', '', 'edit')) {
            handle_tags_save($this->input->post('tags'), $this->input->post('task_id'), 'task');
        }
    }

    public function bulk_action()
    {
        do_action('before_do_bulk_action_for_tasks');
        $total_deleted = 0;
        if ($this->input->post()) {

            $status   = $this->input->post('status');
            $ids      = $this->input->post('ids');
            $tags      = $this->input->post('tags');
            $assignees = $this->input->post('assignees');
            $milestone = $this->input->post('milestone');
            $priority = $this->input->post('priority');

            if (is_array($ids)) {
                foreach ($ids as $id) {
                    if ($this->input->post('mass_delete')) {
                        if (has_permission('tasks', '', 'delete')) {
                            if ($this->tasks_model->delete_task($id)) {
                                $total_deleted++;
                            }
                        }
                    } else {
                        if ($status) {
                            $this->tasks_model->mark_as($status, $id);
                        }
                        if ($priority || $milestone) {
                            $update = array();
                            if($priority){
                                $update['priority'] = $priority;
                            }
                            if($milestone){
                                $update['milestone'] = $milestone;
                            }
                            $this->db->where('id', $id);
                            $this->db->update('tblstafftasks', $update);
                        }
                        if ($tags) {
                            handle_tags_save($tags, $id, 'task');
                        }
                        if ($assignees) {
                            $notifiedUsers = array();
                            foreach ($assignees as $user_id) {
                                if (!$this->tasks_model->is_task_assignee($user_id, $id)) {
                                    $this->db->select('rel_type,rel_id');
                                    $this->db->where('id', $id);
                                    $task = $this->db->get('tblstafftasks')->row();
                                    if ($task->rel_type == 'project') {
                                        // User is we are trying to assign the task is not project member
                                        if (total_rows('tblprojectmembers', array('project_id'=>$task->rel_id, 'staff_id'=>$user_id)) == 0) {
                                            $this->db->insert('tblprojectmembers', array('project_id'=>$task->rel_id, 'staff_id'=>$user_id));
                                        }
                                    }
                                    $this->db->insert('tblstafftaskassignees', array(
                                        'staffid'=>$user_id,
                                        'taskid'=>$id,
                                        'assigned_from'=>get_staff_user_id()
                                        ));
                                    if ($user_id != get_staff_user_id()) {

                                        $notification_data = array(
                                        'description' => 'not_task_assigned_to_you',
                                        'touserid' => $user_id,
                                        'link' => '#taskid=' . $id
                                        );

                                        $this->db->select('name');
                                        $this->db->where('id', $id);
                                        $task_name                            = $this->db->get('tblstafftasks')->row()->name;
                                        $notification_data['additional_data'] = serialize(array(
                                            $task_name
                                        ));
                                        if (add_notification($notification_data)) {
                                            array_push($notifiedUsers,$user_id);
                                        }
                                    }
                                }
                            }
                            pusher_trigger_notification($notifiedUsers);
                        }
                    }
                }
            }
            if ($this->input->post('mass_delete')) {
                set_alert('success', _l('total_tasks_deleted', $total_deleted));
            }
        }
    }
}
