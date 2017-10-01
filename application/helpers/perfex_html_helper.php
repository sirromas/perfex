<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Remove <br /> html tags from string to show in textarea with new linke
 * @param  string $text
 * @return string formatted text
 */
function clear_textarea_breaks($text)
{
    $_text  = '';
    $_text  = $text;
    $breaks = array(
        "<br />",
        "<br>",
        "<br/>"
    );
    $_text  = str_ireplace($breaks, "", $_text);
    $_text  = trim($_text);
    return $_text;
}
/**
 * Equivalent function to nl2br php function but keeps the html if found and do not ruin the formatting
 * @param  string $string
 * @return string
 */
function nl2br_save_html($string)
{
    if(! preg_match("#</.*>#", $string)) // avoid looping if no tags in the string.
        return nl2br($string);

    $string = str_replace(array("\r\n", "\r", "\n"), "\n", $string);

    $lines=explode("\n", $string);
    $output='';
    foreach($lines as $line)
    {
        $line = rtrim($line);
        if(! preg_match("#</?[^/<>]*>$#", $line)) // See if the line finished with has an html opening or closing tag
            $line .= '<br />';
        $output .= $line . "\n";
    }

    return $output;
}
/**
 * Coma separated tags for input
 * @param  array $tag_names
 * @return string
 */
function prep_tags_input($tag_names){
    $tag_names = array_filter($tag_names, function($value) { return $value !== ''; });
    return implode(',',$tag_names);
}
/**
 * Function will render tags as html version to show to the user
 * @param  string $tags
 * @return string
 */
function render_tags($tags){

    $tags_html = '';
    if(!is_array($tags)){
        $tags = explode(',',$tags);
    }
    $tags = array_filter($tags, function($value) { return $value !== ''; });
    if(count($tags) > 0){
        $CI = &get_instance();
        $tags_html .= '<div class="tags-labels">';
        $i = 0;
        $len = count($tags);
        foreach($tags as $tag){
            $tag_id = 0;
            $CI->db->select('id')->where('name',$tag);
            $tag_row = $CI->db->get('tbltags')->row();
            if($tag_row){
                $tag_id = $tag_row->id;
            }
            $tags_html .= '<span class="label label-tag tag-id-'.$tag_id.'"><span class="tag">'.$tag.'</span><span class="hide">'.($i != $len - 1 ? ', ' : '') .'</span></span>';
            $i++;
        }
        $tags_html .= '</div>';
    }

    return $tags_html;
}
/**
 * Load app stylesheet based on option
 * Can load minified stylesheet and non minified
 *
 * This function also check if there is my_ prefix stylesheet to load them.
 * If in options is set to load minified files and the filename that is passed do not contain minified version the
 * original file will be used.
 *
 * @param  string $path
 * @param  string $filename
 * @return string
 */
function app_stylesheet($path,$filename){

    $CI = &get_instance();

    if(file_exists(FCPATH.$path.'/my_'.$filename)){
        $filename = 'my_'.$filename;
    }

    if(get_option('use_minified_files') == 1){
        $original_file_name = $filename;
        $_temp = explode('.',$filename);
        $last = count($_temp) -1;
        $extension = $_temp[$last];
        unset($_temp[$last]);
        $filename = '';
        foreach($_temp as $t){
            $filename .= $t.'.';
        }
        $filename.= 'min.'.$extension;

        if(!file_exists(FCPATH.$path.'/'.$filename)){
            $filename = $original_file_name;
        }
    }
    if(file_exists(FCPATH.$path.'my_'.$filename)){
        $filename = 'my_'.$filename;
    }


    if(ENVIRONMENT == 'development'){
        $latest_version = time();
    } else {
        $latest_version = get_app_version();
    }

    return '<link href="'.base_url($path.'/'.$filename.'?v='.$latest_version).'" rel="stylesheet">'.PHP_EOL;
}
/**
 * Load app script based on option
 * Can load minified stylesheet and non minified
 *
 * This function also check if there is my_ prefix stylesheet to load them.
 * If in options is set to load minified files and the filename that is passed do not contain minified version the
 * original file will be used.
 *
 * @param  string $path
 * @param  string $filename
 * @return string
 */
function app_script($path,$filename){

    $CI = &get_instance();

    if(file_exists(FCPATH.$path.'/my_'.$filename)){
        $filename = 'my_'.$filename;
    }

    if(get_option('use_minified_files') == 1){
        $original_file_name = $filename;
        $_temp = explode('.',$filename);
        $last = count($_temp) -1;
        $extension = $_temp[$last];
        unset($_temp[$last]);
        $filename = '';
        foreach($_temp as $t){
            $filename .= $t.'.';
        }
        $filename.= 'min.'.$extension;
        if(!file_exists($path.'/'.$filename)){
            $filename = $original_file_name;
        }
    }

    if(ENVIRONMENT == 'development'){
        $latest_version = time();
    } else {
        $latest_version = get_app_version();
    }
    return '<script src="'.base_url($path.'/'.$filename.'?v='.$latest_version).'"></script>'.PHP_EOL;
}
/**
 * Return application version formatted
 * @return string
 */
function get_app_version(){
    $CI = &get_instance();
    $CI->load->config('migration');
    return wordwrap($CI->config->item('migration_version'),1,'.',true);
}
/**
 * For more readable code created this function to render only yes or not values for settings
 * @param  string $option_value option from database to compare
 * @param  string $label        input label
 * @param  string $tooltip      tooltip
 */
function render_yes_no_option($option_value, $label, $tooltip = '')
{
    ob_start();
    if ($tooltip != '') {
        $tooltip = ' data-toggle="tooltip" title="' . _l($tooltip) . '"';
    }
?>
    <div class="form-group"<?php
    echo $tooltip;
?>>
    <label for="<?php
    echo $option_value;
?>" class="control-label clearfix"><?php
    echo _l($label);
?></label>
    <div class="radio radio-primary radio-inline">
        <input type="radio" id="y_opt_1_<?php
    echo $label;
?>" name="settings[<?php
    echo $option_value;
?>]" value="1" <?php
    if (get_option($option_value) == '1') {
        echo 'checked';
    }
?>>
        <label for="y_opt_1_<?php
    echo $label;
?>"><?php
    echo _l('settings_yes');
?></label>
        </div>
        <div class="radio radio-primary radio-inline">
            <input type="radio" id="y_opt_2_<?php
    echo $label;
?>" name="settings[<?php
    echo $option_value;
?>]" value="0" <?php
    if (get_option($option_value) == '0') {
        echo 'checked';
    }
?>>
            <label for="y_opt_2_<?php
    echo $label;
?>"><?php
    echo _l('settings_no');
?></label>
            </div>
        </div>
        <?php
    $settings = ob_get_contents();
    ob_end_clean();
    echo $settings;
}
/**
 * Tasks html table used all over the application for relation tasks
 * This table is not used for the main tasks table
 * @param  array  $table_attributes
 * @return string
 */
function init_relation_tasks_table($table_attributes = array())
{
      $table_data = array(
        array(
            'name'=>_l('tasks_dt_name'),
            'th_attrs'=>array(
                'style'=>'min-width:200px'
                )
            ),
         array(
            'name'=>_l('tasks_dt_datestart'),
            'th_attrs'=>array(
                'style'=>'min-width:75px'
                )
            ),
         array(
            'name'=>_l('task_duedate'),
            'th_attrs'=>array(
                'style'=>'min-width:75px'
                )
            ),
        _l('tags'),
         array(
            'name'=>_l('task_assigned'),
            'th_attrs'=>array(
                'style'=>'min-width:75px'
                )
            ),
        _l('tasks_list_priority'),
        _l('task_status')
    );

    if($table_attributes['data-new-rel-type'] == 'project'){
        array_unshift($table_data,'<span class="hide"> - </span><div class="checkbox mass_select_all_wrap"><input type="checkbox" id="mass_select_all" data-to-table="rel-tasks"><label></label></div>');
    }

    $custom_fields = get_custom_fields('tasks', array(
        'show_on_table' => 1
    ));

    foreach ($custom_fields as $field) {
        array_push($table_data, $field['name']);
    }

    $table_data = do_action('tasks_related_table_columns',$table_data);

    array_push($table_data, array('name'=>_l('options'),'th_attrs'=>array('class'=>'table-tasks-options')));

    $name = 'rel-tasks';
    if ($table_attributes['data-new-rel-type'] == 'lead') {
        $name = 'rel-tasks-leads';
    }

    $table = '';
    $CI =& get_instance();
    $table_name = '.table-' . $name;
    $CI->load->view('admin/tasks/tasks_filter_by', array(
        'view_table_name' => $table_name
    ));
    if (has_permission('tasks', '', 'create')) {
        $disabled   = '';
        $table_name = addslashes($table_name);
        if ($table_attributes['data-new-rel-type'] == 'customer' && is_numeric($table_attributes['data-new-rel-id'])) {
            if (total_rows('tblclients', array(
                'active' => 0,
                'userid' => $table_attributes['data-new-rel-id']
            )) > 0) {
                $disabled = ' disabled';
            }
        }
        echo "<a href='#' class='btn btn-info pull-left mbot25 mright5 new-task-relation" . $disabled . "' onclick=\"new_task_from_relation('$table_name'); return false;\" data-rel-id='".$table_attributes['data-new-rel-id']."' data-rel-type='".$table_attributes['data-new-rel-type']."'>" . _l('new_task') . "</a>";
    }

    if ($table_attributes['data-new-rel-type'] == 'project') {
            echo "<a href='" . admin_url('tasks/list_tasks?project_id=' . $table_attributes['data-new-rel-id'] . '&kanban=true') . "' class='btn btn-default pull-left mbot25 mright5 hidden-xs'>" . _l('view_kanban') . "</a>";
            echo "<a href='" . admin_url('tasks/detailed_overview?project_id=' . $table_attributes['data-new-rel-id']) . "' class='btn btn-success pull-right mbot25'>" . _l('detailed_overview') . "</a>";
            echo '<div class="clearfix"></div>';
            echo $CI->load->view('admin/tasks/_bulk_actions',array('table'=>'.table-rel-tasks'),true);
            echo $CI->load->view('admin/tasks/_summary',array('rel_id'=>$table_attributes['data-new-rel-id'],'rel_type'=>'project','table'=>$table_name),true);
            echo '<a href="#" data-toggle="modal" data-target="#tasks_bulk_actions" class="hide bulk-actions-btn" data-table=".table-rel-tasks">'._l('bulk_actions').'</a>';

    } else if($table_attributes['data-new-rel-type'] == 'customer'){
        echo '<div class="clearfix"></div>';
        echo '<div id="tasks_related_filter">';
        echo '<p class="bold">'._l('task_related_to').': </p>';

        echo '<div class="checkbox checkbox-inline mbot25">
        <input type="checkbox" checked value="customer" disabled id="ts_rel_to_customer" name="tasks_related_to[]">
        <label for="ts_rel_to_customer">'._l('client').'</label>
        </div>

        <div class="checkbox checkbox-inline mbot25">
        <input type="checkbox" value="project" id="ts_rel_to_project" name="tasks_related_to[]">
        <label for="ts_rel_to_project">'._l('projects').'</label>
        </div>

        <div class="checkbox checkbox-inline mbot25">
        <input type="checkbox" value="invoice" id="ts_rel_to_invoice" name="tasks_related_to[]">
        <label for="ts_rel_to_invoice">'._l('invoices').'</label>
        </div>

        <div class="checkbox checkbox-inline mbot25">
        <input type="checkbox" value="estimate" id="ts_rel_to_estimate" name="tasks_related_to[]">
        <label for="ts_rel_to_estimate">'._l('estimates').'</label>
        </div>

        <div class="checkbox checkbox-inline mbot25">
        <input type="checkbox" value="contract" id="ts_rel_to_contract" name="tasks_related_to[]">
        <label for="ts_rel_to_contract">'._l('contracts').'</label>
        </div>

        <div class="checkbox checkbox-inline mbot25">
        <input type="checkbox" value="ticket" id="ts_rel_to_ticket" name="tasks_related_to[]">
        <label for="ts_rel_to_ticket">'._l('tickets').'</label>
        </div>

        <div class="checkbox checkbox-inline mbot25">
        <input type="checkbox" value="expense" id="ts_rel_to_expense" name="tasks_related_to[]">
        <label for="ts_rel_to_expense">'._l('expenses').'</label>
        </div>

        <div class="checkbox checkbox-inline mbot25">
        <input type="checkbox" value="proposal" id="ts_rel_to_proposal" name="tasks_related_to[]">
        <label for="ts_rel_to_proposal">'._l('proposals').'</label>
        </div>';

        echo '</div>';
    }
    echo "<div class='clearfix'></div>";
    $table .= render_datatable($table_data, $name, array(), $table_attributes);

    return $table;
}
/**
 * Function used to render <option> for relation
 * This function will do all the necessary checking and return the options
 * @param  mixed $data
 * @param  string $type   rel_type
 * @param  string $rel_id rel_id
 * @return string
 */
function init_relation_options($data, $type, $rel_id = '')
{
    $_data = array();

    $has_permission_projects_view  = has_permission('projects', '', 'view');
    $has_permission_customers_view = has_permission('customers', '', 'view');
    $has_permission_contracts_view = has_permission('contracts', '', 'view');
    $has_permission_invoices_view  = has_permission('invoices', '', 'view');
    $has_permission_estimates_view = has_permission('estimates', '', 'view');
    $has_permission_expenses_view  = has_permission('expenses', '', 'view');
    $has_permission_proposals_view = has_permission('proposals', '', 'view');
    $is_admin                      = is_admin();
    $CI =& get_instance();
    $CI->load->model('projects_model');

    foreach ($data as $relation) {
        $relation_values = get_relation_values($relation, $type);
        if ($type == 'project') {
            if (!$has_permission_projects_view) {
                if (!$CI->projects_model->is_member($relation_values['id']) && $rel_id != $relation_values['id']) {
                    continue;
                }
            }
        } else if ($type == 'lead') {
            if(!$is_admin){
                if ($relation['assigned'] != get_staff_user_id() && $relation['addedfrom'] != get_staff_user_id() && $relation['is_public'] != 1 && $rel_id != $relation_values['id']) {
                    continue;
                }
            }
        } else if ($type == 'customer') {
            if (!$has_permission_customers_view && !have_assigned_customers() && $rel_id != $relation_values['id']) {
                continue;
            } else if (have_assigned_customers() && $rel_id != $relation_values['id'] && !$has_permission_customers_view) {
                if (!is_customer_admin($relation_values['id'])) {
                    continue;
                }
            }
        } else if ($type == 'contract') {
            if (!$has_permission_contracts_view && $rel_id != $relation_values['id'] && $relation_values['addedfrom'] != get_staff_user_id()) {
                continue;
            }
        } else if ($type == 'invoice') {
            if (!$has_permission_invoices_view && $rel_id != $relation_values['id'] && $relation_values['addedfrom'] != get_staff_user_id()) {
                continue;
            }
        } else if ($type == 'estimate') {
            if (!$has_permission_estimates_view && $rel_id != $relation_values['id'] && $relation_values['addedfrom'] != get_staff_user_id()) {
                continue;
            }
        } else if ($type == 'expense') {
            if (!$has_permission_expenses_view && $rel_id != $relation_values['id'] && $relation_values['addedfrom'] != get_staff_user_id()) {
                continue;
            }
        } else if ($type == 'proposal') {
            if (!$has_permission_proposals_view && $rel_id != $relation_values['id'] && $relation_values['addedfrom'] != get_staff_user_id()) {
                continue;
            }
        }

        $_data[] = $relation_values;
      //  echo '<option value="' . $relation_values['id'] . '"' . $selected . '>' . $relation_values['name'] . '</option>';
    }

    return $_data;
}
/**
 * Function to translate ticket priority
 * The apps offers ability to translate ticket priority no matter if they are stored in database
 * @param  mixed $id
 * @return string
 */
function ticket_priority_translate($id)
{
    if ($id == '' || is_null($id)) {
        return '';
    }

    $line = _l('ticket_priority_db_' . $id,'',false);

    if ($line == 'db_translate_not_found') {
        $CI =& get_instance();
        $CI->db->where('priorityid', $id);
        $priority = $CI->db->get('tblpriorities')->row();
        if (!$priority) {
            return '';
        }
        return $priority->name;
    }

    return $line;
}
/**
 * Function to translate ticket status
 * The apps offers ability to translate ticket status no matter if they are stored in database
 * @param  mixed $id
 * @return string
 */
function ticket_status_translate($id)
{
    if ($id == '' || is_null($id)) {
        return '';
    }
    $line = _l('ticket_status_db_' . $id,'',false);
    if ($line == 'db_translate_not_found') {
        $CI =& get_instance();
        $CI->db->where('ticketstatusid', $id);
        $status = $CI->db->get('tblticketstatus')->row();
        if (!$status) {
            return '';
        }
        return $status->name;
    }
    return $line;
}
/**
 * Format task priority based on passed priority id
 * @param  mixed $id
 * @return string
 */
function task_priority($id)
{
    if ($id == '1') {
        $priority = _l('task_priority_low');
    } else if ($id == '2') {
        $priority = _l('task_priority_medium');
    } else if ($id == '3') {
        $priority = _l('task_priority_high');
    } else if ($id == '4') {
        $priority = _l('task_priority_urgent');
    } else {
        $priority = $id;
    }
    return $priority;
}
/**
 * Return class based on task priority id
 * @param  mixed $id
 * @return string
 */
function get_task_priority_class($id)
{
    if ($id == 1) {
        $class = 'muted';
    } else if ($id == 2) {
        $class = 'info';
    } else if ($id == 3) {
        $class = 'warning';
    } else {
        $class = 'danger';
    }
    return $class;
}

function get_project_status_by_id($id)
{
   $CI = &get_instance();
   if(!class_exists('projects_model')){
        $CI->load->model('projects_model');
   }

   $statuses = $CI->projects_model->get_project_statuses();

   $status = array(
      'id'=>0,
      'bg_color'=>'#333',
      'text_color'=>'#333',
      'name'=>'[Status Not Found]',
      'order'=>1,
      );

    foreach($statuses as $s){
        if($s['id'] == $id){
            $status = $s;
            break;
        }
    }
    return $status;
}

/**
 * @deprecated
 * @param  mixed  $id
 * @param  boolean $replace_default_by_muted
 * @return string
 */
function get_project_label($id, $replace_default_by_muted = false)
{
    return project_status_color_class($id,$replace_default_by_muted);
}

/**
 * @deprecated
 * @param  mixed  $id
 * @param  boolean $replace_default_by_muted
 * @return string
 */
function project_status_color_class($id,$replace_default_by_muted = false){

   if ($id == 1 || $id == 5) {
        $class = 'default';
        if($replace_default_by_muted == true){
            $class = 'muted';
        }
    } else if ($id == 2) {
        $class = 'info';
    } else if ($id == 3) {
        $class = 'warning';
    } else {
        // ID == 4 finished
        $class = 'success';
    }

    $hook_data = do_action('project_status_color_class', array(
        'id' => $id,
        'class' => $class
    ));

    $class     = $hook_data['class'];
    return $class;
}
/**
 * @deprecated
 * Project status translate
 * @param  mixed  $id
 * @return string
 */
function project_status_by_id($id){
    $label = _l('project_status_'.$id);
    $hook_data = do_action('project_status_label',array('id'=>$id,'label'=>$label));
    $label = $hook_data['label'];
    return $label;
}
/**
 * Function that renders input for admin area based on passed arguments
 * @param  string $name             input name
 * @param  string $label            label name
 * @param  string $value            default value
 * @param  string $type             input type eq text,number
 * @param  array  $input_attrs      attributes on <input
 * @param  array  $form_group_attr  <div class="form-group"> html attributes
 * @param  string $form_group_class additional form group class
 * @param  string $input_class      additional class on input
 * @return string
 */
function render_input($name, $label = '', $value = '', $type = 'text', $input_attrs = array(), $form_group_attr = array(), $form_group_class = '', $input_class = '')
{
    $input            = '';
    $_form_group_attr = '';
    $_input_attrs     = '';
    foreach ($input_attrs as $key => $val) {
        // tooltips
        if ($key == 'title') {
            $val = _l($val);
        }
        $_input_attrs .= $key . '=' . '"' . $val . '" ';
    }

    $_input_attrs = rtrim($_input_attrs);

    foreach ($form_group_attr as $key => $val) {
        // tooltips
        if ($key == 'title') {
            $val = _l($val);
        }
        $_form_group_attr .= $key . '=' . '"' . $val . '" ';
    }

    $_form_group_attr = rtrim($_form_group_attr);

    if (!empty($form_group_class)) {
        $form_group_class = ' ' . $form_group_class;
    }
    if (!empty($input_class)) {
        $input_class = ' ' . $input_class;
    }
    $input .= '<div class="form-group' . $form_group_class . '" ' . $_form_group_attr . '>';
    if ($label != '') {
        $input .= '<label for="' . $name . '" class="control-label">' . _l($label,'',false) . '</label>';
    }
    $input .= '<input type="' . $type . '" id="' . $name . '" name="' . $name . '" class="form-control' . $input_class . '" ' . $_input_attrs . ' value="' . set_value($name, $value) . '">';
    $input .= '</div>';
    return $input;
}
/**
 * Render color picker input
 * @param  string $name        input name
 * @param  string $label       field name
 * @param  string $value       default value
 * @param  array  $input_attrs <input sttributes
 * @return string
 */
function render_color_picker($name, $label = '', $value = '', $input_attrs = array())
{

    $_input_attrs = '';
    foreach ($input_attrs as $key => $val) {
        // tooltips
        if ($key == 'title') {
            $val = _l($val);
        }
        $_input_attrs .= $key . '=' . '"' . $val . '"';
    }

    $picker = '';
    $picker .= '<div class="form-group">';
    $picker .= '<label for="' . $name . '" class="control-label">' . $label . '</label>';
    $picker .= '<div class="input-group mbot15 colorpicker-input">
    <input type="text" value="' . set_value($name, $value) . '" name="' . $name . '" id="' . $name . '" class="form-control" ' . $_input_attrs . ' />
    <span class="input-group-addon"><i></i></span>
</div>';
    $picker .= '</div>';
    return $picker;
}
/**
 * Render date picker input for admin area
 * @param  [type] $name             input name
 * @param  string $label            input label
 * @param  string $value            default value
 * @param  array  $input_attrs      input attributes
 * @param  array  $form_group_attr  <div class="form-group"> div wrapper html attributes
 * @param  string $form_group_class form group div wrapper additional class
 * @param  string $input_class      <input> additional class
 * @return string
 */
function render_date_input($name, $label = '', $value = '', $input_attrs = array(), $form_group_attr = array(), $form_group_class = '', $input_class = '')
{
    $input            = '';
    $_form_group_attr = '';
    $_input_attrs     = '';
    foreach ($input_attrs as $key => $val) {
        // tooltips
        if ($key == 'title') {
            $val = _l($val);
        }
        $_input_attrs .= $key . '=' . '"' . $val . '" ';
    }

    $_input_attrs = rtrim($_input_attrs);

    foreach ($form_group_attr as $key => $val) {
        // tooltips
        if ($key == 'title') {
            $val = _l($val);
        }
        $_form_group_attr .= $key . '=' . '"' . $val . '" ';
    }

    $_form_group_attr = rtrim($_form_group_attr);

    if (!empty($form_group_class)) {
        $form_group_class = ' ' . $form_group_class;
    }
    if (!empty($input_class)) {
        $input_class = ' ' . $input_class;
    }
    $input .= '<div class="form-group' . $form_group_class . '" ' . $_form_group_attr . '>';
    if ($label != '') {
        $input .= '<label for="' . $name . '" class="control-label">' . _l($label,'',false) . '</label>';
    }
    $input .= '<div class="input-group date">';
    $input .= '<input type="text" id="' . $name . '" name="' . $name . '" class="form-control datepicker' . $input_class . '" ' . $_input_attrs . ' value="' . set_value($name,$value) . '">';
    $input .= '<div class="input-group-addon">
    <i class="fa fa-calendar calendar-icon"></i>
</div>';
    $input .= '</div>';
    $input .= '</div>';
    return $input;
}
/**
 * Render date time picker input for admin area
 * @param  [type] $name             input name
 * @param  string $label            input label
 * @param  string $value            default value
 * @param  array  $input_attrs      input attributes
 * @param  array  $form_group_attr  <div class="form-group"> div wrapper html attributes
 * @param  string $form_group_class form group div wrapper additional class
 * @param  string $input_class      <input> additional class
 * @return string
 */
function render_datetime_input($name, $label = '', $value = '', $input_attrs = array(), $form_group_attr = array(), $form_group_class = '', $input_class = '')
{
    $html = render_date_input($name, $label, $value, $input_attrs, $form_group_attr, $form_group_class, $input_class);
    $html = str_replace('datepicker', 'datetimepicker', $html);
    return $html;
}
/**
 * Render textarea for admin area
 * @param  [type] $name             textarea name
 * @param  string $label            textarea label
 * @param  string $value            default value
 * @param  array  $textarea_attrs      textarea attributes
 * @param  array  $form_group_attr  <div class="form-group"> div wrapper html attributes
 * @param  string $form_group_class form group div wrapper additional class
 * @param  string $textarea_class      <textarea> additional class
 * @return string
 */
function render_textarea($name, $label = '', $value = '', $textarea_attrs = array(), $form_group_attr = array(), $form_group_class = '', $textarea_class = '')
{

    $textarea         = '';
    $_form_group_attr = '';
    $_textarea_attrs  = '';
    if (!isset($textarea_attrs['rows'])) {
        $textarea_attrs['rows'] = 4;
    }

    foreach ($textarea_attrs as $key => $val) {
        // tooltips
        if ($key == 'title') {
            $val = _l($val);
        }
        $_textarea_attrs .= $key . '=' . '"' . $val . '" ';
    }

    $_textarea_attrs = rtrim($_textarea_attrs);

    foreach ($form_group_attr as $key => $val) {
        if ($key == 'title') {
            $val = _l($val);
        }
        $_form_group_attr .= $key . '=' . '"' . $val . '" ';
    }

    $_form_group_attr = rtrim($_form_group_attr);

    if (!empty($textarea_class)) {
        $textarea_class = ' ' . $textarea_class;
    }
    if (!empty($form_group_class)) {
        $form_group_class = ' ' . $form_group_class;
    }
    $textarea .= '<div class="form-group' . $form_group_class . '" ' . $_form_group_attr . '>';
    if ($label != '') {
        $textarea .= '<label for="' . $name . '" class="control-label">' . _l($label,'',false) . '</label>';
    }

    $v = clear_textarea_breaks($value);
    if (strpos($textarea_class, 'tinymce') !== false) {
        $v = $value;
    }
    $textarea .= '<textarea id="' . $name . '" name="' . $name . '" class="form-control' . $textarea_class . '" ' . $_textarea_attrs . '>' . set_value($name, $v) . '</textarea>';

    $textarea .= '</div>';
    return $textarea;
}
/**
 * Render <select> field optimized for admin area and bootstrap-select plugin
 * @param  string  $name             select name
 * @param  array  $options          option to include
 * @param  array   $option_attrs     additional options attributes to include, attributes accepted based on the bootstrap-selectp lugin
 * @param  string  $label            select label
 * @param  string  $selected         default selected value
 * @param  array   $select_attrs     <select> additional attributes
 * @param  array   $form_group_attr  <div class="form-group"> div wrapper html attributes
 * @param  string  $form_group_class <div class="form-group"> additional class
 * @param  string  $select_class     additional <select> class
 * @param  boolean $include_blank    do you want to include the first <option> to be empty
 * @return string
 */
function render_select($name, $options, $option_attrs = array(), $label = '', $selected = '', $select_attrs = array(), $form_group_attr = array(), $form_group_class = '', $select_class = '', $include_blank = true)
{

    $callback_translate = '';
    if (isset($options['callback_translate'])) {
        $callback_translate = $options['callback_translate'];
        unset($options['callback_translate']);
    }
    $select           = '';
    $_form_group_attr = '';
    $_select_attrs    = '';
    if (!isset($select_attrs['data-width'])) {
        $select_attrs['data-width'] = '100%';
    }
    if (!isset($select_attrs['data-none-selected-text'])) {
        $select_attrs['data-none-selected-text'] = _l('dropdown_non_selected_tex');
    }
    foreach ($select_attrs as $key => $val) {
        // tooltips
        if ($key == 'title') {
            $val = _l($val);
        }
        $_select_attrs .= $key . '=' . '"' . $val . '" ';
    }

    $_select_attrs = rtrim($_select_attrs);

    foreach ($form_group_attr as $key => $val) {
        // tooltips
        if ($key == 'title') {
            $val = _l($val);
        }
        $_form_group_attr .= $key . '=' . '"' . $val . '" ';
    }
    $_form_group_attr = rtrim($_form_group_attr);
    if (!empty($select_class)) {
        $select_class = ' ' . $select_class;
    }
    if (!empty($form_group_class)) {
        $form_group_class = ' ' . $form_group_class;
    }
    $select .= '<div class="form-group' . $form_group_class . '" ' . $_form_group_attr . '>';
    if ($label != '') {
        $select .= '<label for="' . $name . '" class="control-label">' . _l($label,'',false) . '</label>';
    }
    $select .= '<select id="' . $name . '" name="' . $name . '" class="selectpicker' . $select_class . '" ' . $_select_attrs . ' data-live-search="true">';
    if ($include_blank == true) {
        $select .= '<option value=""></option>';
    }
    foreach ($options as $option) {
        $val       = '';
        $_selected = '';
        $key       = '';
        if (isset($option[$option_attrs[0]]) && !empty($option[$option_attrs[0]])) {
            $key = $option[$option_attrs[0]];
        }
        if (!is_array($option_attrs[1])) {
            $val = $option[$option_attrs[1]];
        } else {
            foreach ($option_attrs[1] as $_val) {
                $val .= $option[$_val] . ' ';
            }
        }
        $val = trim($val);

        if ($callback_translate != '') {
            if (function_exists($callback_translate) && is_callable($callback_translate)) {
                $val = call_user_func($callback_translate, $key);
            }
        }
        $data_sub_text = '';
        if (!is_array($selected)) {
            if ($selected != '') {
                if ($selected == $key) {
                    $_selected = ' selected';
                }
            }
        } else {
            foreach ($selected as $id) {
                if ($key == $id) {
                    $_selected = ' selected';
                }
            }
        }
        if (isset($option_attrs[2])) {

            if (strpos($option_attrs[2], ',') !== false) {
                $sub_text = '';
                $_temp    = explode(',', $option_attrs[2]);
                foreach ($_temp as $t) {
                    if (isset($option[$t])) {
                        $sub_text .= $option[$t] . ' ';
                    }
                }
            } else {
                if (isset($option[$option_attrs[2]])) {
                    $sub_text = $option[$option_attrs[2]];
                } else {
                    $sub_text = $option_attrs[2];
                }
            }
            $data_sub_text = ' data-subtext=' . '"' . $sub_text . '"';
        }
        $data_content = '';
        if (isset($option['option_attributes'])) {
            foreach ($option['option_attributes'] as $_opt_attr_key => $_opt_attr_val) {
                $data_content .= $_opt_attr_key . '=' . '"' . $_opt_attr_val . '"';
            }
        }
        $select .= '<option value="' . $key . '"' . $_selected . $data_content . '' . $data_sub_text . '>' . $val . '</option>';
    }
    $select .= '</select>';
    $select .= '</div>';
    return $select;
}

if(!function_exists('render_form_builder_field')){
    function render_form_builder_field($field){
        $type = $field->type;
        $classNameCol = 'col-md-12';
        if(isset($field->className)){
            if(strpos($field->className,'form-col') !== FALSE){
                $classNames = explode(' ',$field->className);
                if(is_array($classNames)){

                    $classNameColArray = array_filter($classNames,function($class){
                        return _startsWith($class,'form-col');
                    });

                    $classNameCol = implode(' ',$classNameColArray);
                    $classNameCol = trim($classNameCol);
                    $classNameCol = str_replace('form-col', 'col-md', $classNameCol);

                }
            }
        }

        echo '<div class="'.$classNameCol.'">';
        if($type == 'header' || $type == 'paragraph'){
            echo '<'.$field->subtype.' class="'.(isset($field->className) ? $field->className : '').'">'.nl2br($field->label).'</'.$field->subtype.'>';
        } else {

         echo '<div class="form-group" data-type="'.$type.'" data-name="'.$field->name.'" data-required="'.(isset($field->required) ? true : 'false').'">';
         echo '<label class="control-label" for="'.$field->name.'">'.(isset($field->required) ? ' <span class="text-danger">* </span> ': '').$field->label.''.(isset($field->description) ? ' <i class="fa fa-question-circle" data-toggle="tooltip" data-title="'.$field->description.'"></i>' : '').'</label>';
         if($type == 'file' || $type == 'text' || $type == 'email'){
            echo '<input'.(isset($field->required) ? ' required="true"': '') . (isset($field->placeholder) ? ' placeholder="'.$field->placeholder.'"' : '') . ' type="'.$type.'" name="'.$field->name.'" id="'.$field->name.'" class="'.(isset($field->className) ? $field->className : '').'" value="'.(isset($field->value) ? $field->value : '').'"'.($field->type == 'file' ? ' accept="'.get_form_accepted_mimes().'" filesize="'.file_upload_max_size().'"' : '').'>';
        } else if($type == 'textarea'){
            echo '<textarea'.(isset($field->required) ? ' required="true"': '').' id="'.$field->name.'" name="'.$field->name.'" rows="'.(isset($field->rows) ? $field->rows : '4').'" class="'.(isset($field->className) ? $field->className : '').'" placeholder="'.(isset($field->placeholder) ? $field->placeholder : '').'">'.(isset($field->value) ? $field->value : '').'</textarea>';
        } else if($type == 'date'){
            echo '<input'.(isset($field->required) ? ' required="true"': '').' placeholder="'.(isset($field->placeholder) ? $field->placeholder : '').'" type="text" class="'.(isset($field->className) ? $field->className : '').' datepicker" name="'.$field->name.'" id="'.$field->name.'" value="'.(isset($field->value) ? _d($field->value) : '').'">';
        } elseif($type == 'datetime'){
            echo '<input'.(isset($field->required) ? ' required="true"': '').' placeholder="'.(isset($field->placeholder) ? $field->placeholder : '').'" type="text" class="'.(isset($field->className) ? $field->className : '').' datetimepicker" name="'.$field->name.'" id="'.$field->name.'" value="'.(isset($field->value) ? _dt($field->value) : '').'">';
        } else if($type == 'color'){
         echo '<div class="input-group colorpicker-input">
         <input'.(isset($field->required) ? ' required="true"': '').' placeholder="'.(isset($field->placeholder) ? $field->placeholder : '').'" type="text"' . (isset($field->value) ? ' value="'.$field->value.'"' : '') .  ' name="' . $field->name . '" id="' . $field->name . '" class="'.(isset($field->className) ? $field->className : '').'" />
             <span class="input-group-addon"><i></i></span>
         </div>';
         } else if($type == 'select'){
            echo '<select'.(isset($field->required) ? ' required="true"': '').''.(isset($field->multiple) ? ' multiple="true"' : '').' class="'.(isset($field->className) ? $field->className : '').'" name="'.$field->name.(isset($field->multiple) ? '[]' : '').'" id="'.$field->name.'"'.(isset($field->values) && count($field->values)> 10 ? 'data-live-search="true"': '').'data-none-selected-text="'.(isset($field->placeholder) ? $field->placeholder : '').'">';
            $values = array();
            if(isset($field->values) && count($field->values) > 0){
              foreach($field->values as $option){
                echo '<option value="'.$option->value.'" '.(isset($option->selected) ? ' selected' : '').'>'.$option->label.'</option>';
            }
        }
        echo '</select>';
        } else if($type == 'checkbox-group'){
            $values = array();
            if(isset($field->values) && count($field->values) > 0){
               $i = 0;
               echo '<div class="chk">';
               foreach($field->values as $checkbox){
                  echo '<div class="checkbox'.((isset($field->inline) && $field->inline == 'true') || (isset($field->className) && strpos($field->className,'form-inline-checkbox') !== FALSE) ? ' checkbox-inline' : '').'">';
                  echo '<input'.(isset($field->required) ? ' required="true"': '').' class="'.(isset($field->className) ? $field->className : '').'" type="checkbox" id="chk_'.$field->name.'_'.$i.'" value="'.$checkbox->value.'" name="'.$field->name.'[]"'.(isset($checkbox->selected) ? ' checked' : '').'>';
                  echo '<label for="chk_'.$field->name.'_'.$i.'">';
                  echo $checkbox->label;
                  echo '</label>';
                  echo '</div>';
                  $i++;
              }
              echo '</div>';
          }
        }
        echo '</div>';
        }
         echo '</div>';
    }
}
function app_external_form_footer($form){
   $date_format = get_option('dateformat');
   $date_format = explode('|', $date_format);
   $date_format = $date_format[0];
   $locale_key = get_locale_key($form->language);
 ?>
<script src="<?php echo base_url('assets/plugins/jquery/jquery.min.js'); ?>"></script>
<script src="<?php echo base_url('assets/plugins/bootstrap/js/bootstrap.min.js'); ?>"></script>
<script src="<?php echo base_url('assets/plugins/jquery-validation/jquery.validate.min.js'); ?>"></script>
<script src="<?php echo base_url('assets/plugins/app-build/moment.min.js'); ?>"></script>
<?php app_select_plugin_js($locale_key); ?>
<?php
if($locale_key != 'en'){
  if(file_exists(FCPATH.'assets/plugins/jquery-validation/localization/messages_'.$locale_key.'.min.js')){ ?>
  <script src="<?php echo base_url('assets/plugins/jquery-validation/localization/messages_'.$locale_key.'.min.js'); ?>"></script>
  <?php } else if(file_exists(FCPATH.'assets/plugins/jquery-validation/localization/messages_'.$locale_key.'_'.strtoupper($locale_key).'.min.js')){ ?>
  <script src="<?php echo base_url('assets/plugins/jquery-validation/localization/messages_'.$locale_key.'_'.strtoupper($locale_key).'.min.js'); ?>"></script>
  <?php } } ?>
  <script src="<?php echo base_url('assets/plugins/datetimepicker/jquery.datetimepicker.full.min.js'); ?>"></script>
  <script src="<?php echo base_url('assets/plugins/bootstrap-colorpicker/js/bootstrap-colorpicker.min.js'); ?>"></script>
 <script>
    $(function(){
     var time_format = '<?php echo get_option('time_format'); ?>';
     var date_format = '<?php echo $date_format; ?>';

     $('body').tooltip({
       selector: '[data-toggle="tooltip"]'
     });

     $('body').find('.colorpicker-input').colorpicker({
       format: "hex"
     });

     var date_picker_options = {
       format: date_format,
       timepicker: false,
       lazyInit: true,
       dayOfWeekStart: '<?php echo get_option('calendar_first_day '); ?>',
     }

    $('.datepicker').datetimepicker(date_picker_options);
     var date_time_picker_options = {
      lazyInit: true,
      scrollInput: false,
      dayOfWeekStart: '<?php echo get_option('calendar_first_day '); ?>',
    }
    if(time_format == 24){
      date_time_picker_options.format = date_format + ' H:i';
    } else {
      date_time_picker_options.format =  date_format + ' g:i A';
      date_time_picker_options.formatTime = 'g:i A';
    }
    $('.datetimepicker').datetimepicker(date_time_picker_options);

    $('body').find('select').selectpicker({
      showSubtext: true,
    });

    $.validator.addMethod('filesize', function (value, element, param) {
        return this.optional(element) || (element.files[0].size <= param)
    }, "<?php echo _l('ticket_form_validation_file_size',bytesToSize('', file_upload_max_size())); ?>");

    $.validator.addMethod( "extension", function( value, element, param ) {
        param = typeof param === "string" ? param.replace( /,/g, "|" ) : "png|jpe?g|gif";
        return this.optional( element ) || value.match( new RegExp( "\\.(" + param + ")$", "i" ) );
    }, $.validator.format( "<?php echo _l('validation_extension_not_allowed'); ?>" ) );

    $.validator.setDefaults({
     highlight: function(element) {
       $(element).closest('.form-group').addClass('has-error');
     },
     unhighlight: function(element) {
       $(element).closest('.form-group').removeClass('has-error');
     },
     errorElement: 'p',
     errorClass: 'text-danger',
     errorPlacement: function(error, element) {
       if (element.parent('.input-group').length || element.parents('.chk').length) {
         if (!element.parents('.chk').length) {
           error.insertAfter(element.parent());
         } else {
           error.insertAfter(element.parents('.chk'));
         }
       } else {
         error.insertAfter(element);
       }
     }
   });
    });
 </script>
 <?php
}

function app_external_form_header($form){
    ?>
  <?php echo app_stylesheet('assets/css','reset.css'); ?>
  <?php if(get_option('favicon') != ''){ ?>
  <link href="<?php echo base_url('uploads/company/'.get_option('favicon')); ?>" rel="shortcut icon">
  <?php } ?>
  <link href='<?php echo base_url('assets/plugins/roboto/roboto.css'); ?>' rel='stylesheet'>
  <link href="<?php echo base_url('assets/plugins/bootstrap/css/bootstrap.min.css'); ?>" rel="stylesheet">
  <?php if(is_rtl(true)){ ?>
  <link rel="stylesheet" href="<?php echo base_url('assets/plugins/bootstrap-arabic/css/bootstrap-arabic.min.css'); ?>">
  <?php } ?>
  <link href="<?php echo base_url('assets/plugins/datetimepicker/jquery.datetimepicker.min.css'); ?>" rel="stylesheet">
  <link href="<?php echo base_url('assets/plugins/bootstrap-colorpicker/css/bootstrap-colorpicker.min.css'); ?>" rel="stylesheet">
  <link href="<?php echo base_url('assets/plugins/font-awesome/css/font-awesome.min.css'); ?>" rel="stylesheet">
  <link href="<?php echo base_url('assets/plugins/bootstrap-select/css/bootstrap-select.min.css'); ?>" rel="stylesheet">
  <?php echo app_stylesheet('assets/css','forms.css'); ?>
  <?php if(get_option('recaptcha_secret_key') != '' && get_option('recaptcha_site_key') != '' && $form->recaptcha == 1){ ?>
  <script src='https://www.google.com/recaptcha/api.js'></script>
  <?php } ?>
  <?php if(file_exists(FCPATH.'assets/css/custom.css')){ ?>
  <link href="<?php echo base_url('assets/css/custom.css'); ?>" rel="stylesheet">
  <?php } ?>
  <?php render_custom_styles(array('general','buttons')); ?>
 <?php
}

/**
 * Init admin head
 * @param  boolean $aside should include aside
 */
function init_head($aside = true)
{
    $CI =& get_instance();
    $CI->load->view('admin/includes/head');
    $CI->load->view('admin/includes/header',array('startedTimers'=>$CI->misc_model->get_staff_started_timers()));
    $CI->load->view('admin/includes/setup_menu');
    if ($aside == true) {
        $CI->load->view('admin/includes/aside');
    }
}
/**
 * Init admin footer/tails
 */
function init_tail()
{
    $CI =& get_instance();
    $CI->load->view('admin/includes/scripts');
}
/**
 * Render table used for datatables
 * @param  array  $headings           [description]
 * @param  string $class              table class / added prefix table-$class
 * @param  array  $additional_classes
 * @return string                     formatted table
 */
/**
 * Render table used for datatables
 * @param  array   $headings
 * @param  string  $class              table class / add prefix eq.table-$class
 * @param  array   $additional_classes additional table classes
 * @param  array   $table_attributes   table attributes
 * @param  boolean $tfoot              includes blank tfoot
 * @return string
 */
function render_datatable($headings = array(), $class = '', $additional_classes = array(''), $table_attributes = array())
{
    $_additional_classes = '';
    $_table_attributes   = ' ';
    if (count($additional_classes) > 0) {
        $_additional_classes = ' ' . implode(' ', $additional_classes);
    }
    $CI =& get_instance();
    $browser = $CI->agent->browser();
    $IEfix   = '';
    if ($browser == 'Internet Explorer') {
        $IEfix = 'ie-dt-fix';
    }
    foreach ($table_attributes as $key => $val) {
        $_table_attributes .= $key . '=' . '"' . $val . '" ';
    }

    $table = '<div class="' . $IEfix . '"><table' . $_table_attributes . 'class="dt-table-loading table table-striped table-' . $class . '' . $_additional_classes . '">';
    $table .= '<thead>';
    $table .= '<tr>';
    foreach ($headings as $heading) {
        if(!is_array($heading)){
            $table .= '<th>' . $heading . '</th>';
        } else {
            $th_attrs = '';
            if(isset($heading['th_attrs'])){
                foreach ($heading['th_attrs'] as $key => $val) {
                    $th_attrs .= $key . '=' . '"' . $val . '" ';
                }
            }
            $th_attrs = ($th_attrs != '' ? ' '.$th_attrs : $th_attrs);
            $table .= '<th'.$th_attrs.'>' . $heading['name'] . '</th>';
        }
    }
    $table .= '</tr>';
    $table .= '</thead>';
    $table .= '<tbody></tbody>';
    $table .= '</table></div>';
    echo $table;
}
/**
 * Get company logo from company uploads folder
 * @param  string $url     href url of image
 * @param  string $href_class Additional classes on href
 */
function get_company_logo($uri = '', $href_class = '')
{
    $company_logo = get_option('company_logo');
    $company_name = get_option('companyname');

    if ($uri == '') {
        $logo_href = site_url();
    } else {
        $logo_href = site_url($uri);
    }

    if ($company_logo != '') {
        echo '<a href="' . $logo_href . '" class="' . $href_class . ' logo img-responsive"><img src="' . base_url('uploads/company/' . $company_logo) . '" class="img-responsive" alt="' . $company_name . '"></a>';
    } else if ($company_name != '') {
        echo '<a href="' . $logo_href . '" class="' . $href_class . ' logo">' . $company_name . '</a>';
    } else {
        echo '';
    }
}
function payment_gateway_logo(){
    $url = do_action('payment_gateway_logo_url',base_url('uploads/company/' . get_option('company_logo')));
    $width = do_action('payment_gateway_logo_width','auto');
    $height = do_action('payment_gateway_logo_height','34px');
    return '<img src="'.$url.'" width="'.$width.'" height="'.$height.'">';
}
function payment_gateway_head($title = 'Payment for Invoice'){
    ob_start(); ?>
       <!DOCTYPE html>
        <html lang="en">
           <head>
              <meta charset="utf-8">
              <meta http-equiv="X-UA-Compatible" content="IE=edge">
              <meta name="viewport" content="width=device-width, initial-scale=1">
              <!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
              <title>
                 <?php echo $title; ?>
              </title>
              <?php if(get_option('favicon') != ''){ ?>
              <link href="<?php echo base_url('uploads/company/'.get_option('favicon')); ?>" rel="shortcut icon">
              <?php } ?>
              <?php echo app_stylesheet('assets/css','reset.css'); ?>
              <!-- Bootstrap -->
              <link href="<?php echo base_url(); ?>assets/plugins/bootstrap/css/bootstrap.min.css" rel="stylesheet">
              <link href='<?php echo base_url('assets/plugins/roboto/roboto.css'); ?>' rel='stylesheet'>
              <?php echo app_stylesheet('assets/css','bs-overides.css'); ?>
              <?php echo app_stylesheet(template_assets_path().'/css','style.css'); ?>
              <style>
                 .text-danger {
                    color: #fc2d42;
                 }
              </style>
             <?php do_action('payment_gateway_head'); ?>
           </head>
        <?php
        $contents = ob_get_contents();
        ob_end_clean();
        return $contents;
}
function payment_gateway_scripts(){
    ob_start(); ?>
        <script src="<?php echo base_url(); ?>assets/plugins/jquery/jquery.min.js"></script>
        <script src="<?php echo base_url(); ?>assets/plugins/bootstrap/js/bootstrap.min.js"></script>
        <script src="<?php echo base_url('assets/plugins/jquery-validation/jquery.validate.min.js'); ?>"></script>
        <script>
           $.validator.setDefaults({
               errorElement: 'span',
               errorClass: 'text-danger',
           });
        </script>
    <?php do_action('payment_gateway_scripts');
    $contents = ob_get_contents();
    ob_end_clean();
    return $contents;
}
function payment_gateway_footer(){
    ob_start();
        do_action('payment_gateway_footer');
    ?>
    </body>
    <html>
    <?php
    $contents = ob_get_contents();
    ob_end_clean();
    return $contents;
}
/**
 * Return staff profile image url
 * @param  mixed $staff_id
 * @param  string $type
 * @return string
 */
function staff_profile_image_url($staff_id, $type = 'small')
{
    $url = base_url('assets/images/user-placeholder.jpg');
    $CI =& get_instance();
    $CI->db->select('profile_image');
    $CI->db->from('tblstaff');
    $CI->db->where('staffid', $staff_id);
    $staff = $CI->db->get()->row();
    if ($staff) {
        if (!empty($staff->profile_image)) {
            $profileImagePath = 'uploads/staff_profile_images/' . $staff_id . '/' . $type . '_' . $staff->profile_image;
            if(file_exists($profileImagePath)){
                $url = base_url($profileImagePath);
            }
        }
    }
    return $url;
}
/**
 * Return contact profile image url
 * @param  mixed $contact_id
 * @param  string $type
 * @return string
 */
function contact_profile_image_url($contact_id, $type = 'small')
{
    $url = base_url('assets/images/user-placeholder.jpg');
    $CI =& get_instance();
    $CI->db->select('profile_image');
    $CI->db->from('tblcontacts');
    $CI->db->where('id', $contact_id);
    $contact = $CI->db->get()->row();
    if ($contact) {
        if (!empty($contact->profile_image)) {
            $path = 'uploads/client_profile_images/' . $contact_id . '/' . $type . '_' . $contact->profile_image;
            if(file_exists($path)){
                $url = base_url($path);
            }
        }
    }
    return $url;
}
/**
 * Staff profile image with href
 * @param  boolean $id        staff id
 * @param  array   $classes   image classes
 * @param  string  $type
 * @param  array   $img_attrs additional <img /> attributes
 * @return string
 */
function staff_profile_image($id = false, $classes = array('staff-profile-image'), $type = 'small', $img_attrs = array())
{
    $url = base_url('assets/images/user-placeholder.jpg');

    $CI =& get_instance();
    $CI->db->select('profile_image,firstname,lastname');
    $CI->db->where('staffid', $id);
    $result = $CI->db->get('tblstaff')->row();

    $_attributes = '';
    foreach ($img_attrs as $key => $val) {
        $_attributes .= $key . '=' . '"' . $val . '" ';
    }

    $blankImageFormatted = '<img src="' . $url . '" ' . $_attributes . ' class="' . implode(' ', $classes) . '" />';

    if (!$result) {
        return $blankImageFormatted;
    }

    if ($result && $result->profile_image !== null) {

        $profileImagePath = 'uploads/staff_profile_images/' . $id . '/' . $type . '_' . $result->profile_image;
        if(file_exists($profileImagePath)){
            $profile_image = '<img ' . $_attributes . ' src="' . base_url($profileImagePath) . '" class="' . implode(' ', $classes) . '" alt="' . $result->firstname . ' ' . $result->lastname . '" />';
        } else {
            return $blankImageFormatted;
        }
    } else {
        $profile_image = '<img src="' . $url . '" ' . $_attributes . ' class="' . implode(' ', $classes) . '" alt="' . $result->firstname . ' ' . $result->lastname . '" />';
    }
    return $profile_image;
}
/**
 * Generate small icon button / font awesome
 * @param  string $url        href url
 * @param  string $type       icon type
 * @param  string $class      button class
 * @param  array  $attributes additional attributes
 * @return string
 */
function icon_btn($url = '', $type = '', $class = 'btn-default', $attributes = array())
{
    $_attributes = '';
    foreach ($attributes as $key => $val) {
        $_attributes .= $key . '=' . '"' . $val . '" ';
    }
    $_url = '#';
    if (_startsWith($url, 'http')) {
        $_url = $url;
    } else if ($url !== '#') {
        $_url = admin_url($url);
    }
    return '<a href="' . $_url . '" class="btn ' . $class . ' btn-icon" ' . $_attributes . '><i class="fa fa-' . $type . '"></i></a>';
}
/**
 * Render admin tickets table
 * @param string  $name        table name
 * @param boolean $bulk_action include checkboxes on the left side for bulk actions
 */
function AdminTicketsTableStructure($name = '', $bulk_action = false)
{
    if ($name == '') {
        $name = 'tickets-table';
    }
    ob_start();
?>
<table class="table dt-table-loading <?php
    echo $name;
?> table-tickets">
<thead>
<tr>
<?php
    if ($bulk_action == true) {
?>
<th>
    <span class="hide"> - </span><div class="checkbox mass_select_all_wrap"><input type="checkbox" id="mass_select_all" data-to-table="tickets"><label></label></div>
</th>
<?php
    }
?>
<th>#</th>
<th><?php
    echo _l('ticket_dt_subject');
?></th>
<th><?php
    echo _l('tags');
?></th>
<th><?php
    echo _l('ticket_dt_department');
?></th>
<th<?php if(get_option('services') == 0){echo ' class="not_visible"'; }?>><?php
        echo _l('ticket_dt_service');
?></th>
<th><?php
    echo _l('ticket_dt_submitter');
?></th>
<th><?php
    echo _l('ticket_dt_status');
?></th>
<th><?php
    echo _l('ticket_dt_priority');
?></th>
<th><?php
    echo _l('ticket_dt_last_reply');
?></th>
<th class="ticket_created_column">
<?php
    echo _l('ticket_date_created');
?></th>
<?php
    $custom_fields = get_custom_fields('tickets', array(
        'show_on_table' => 1
    ));
    foreach ($custom_fields as $field) {
?>
    <th><?php
        echo $field['name'];
?></th>
<?php
    }
?>
<th><?php
    echo _l('options');
?></th>
</tr>
</thead>
<tbody>
</tbody>
</table>

<?php
    $table = ob_get_contents();
    ob_end_clean();
    return $table;
}

function app_js_alerts(){
   $alertclass = get_alert_class();
   if($alertclass != ''){
    $CI = &get_instance();
      $alert_message = '';
      $alert = $CI->session->flashdata('message-'.$alertclass);
      if(is_array($alert)){
        foreach($alert as $alert_data){
          $alert_message.= '<span>'.$alert_data . '</span><br />';
      }
  } else {
    $alert_message .= $alert;
  }
    ?>
    <script>
        $(function(){
          alert_float('<?php echo $alertclass; ?>',"<?php echo $alert_message; ?>");
      });
    </script>
<?php }
}

function app_admin_ajax_search_function(){ ?>
<script>
  function init_ajax_search(type, selector, server_data, url){
    clearInterval(autocheck_notifications_timer_id);

    var ajaxSelector = $('body').find(selector);
    if(ajaxSelector.length){
      var options = {
        ajax: {
          url: (typeof(url) == 'undefined' ? admin_url + 'misc/get_relation_data' : url),
          data: function () {
            var data = {};
            data.type = type;
            data.rel_id = '';
            data.q = '{{{q}}}';
            if(typeof(server_data) != 'undefined'){
              jQuery.extend(data, server_data);
            }
            return data;
          }
        },
        locale: {
          emptyTitle: "<?php echo _l('search_ajax_empty'); ?>",
          statusInitialized: "<?php echo _l('search_ajax_initialized'); ?>",
          statusSearching:"<?php echo _l('search_ajax_searching'); ?>",
          statusNoResults:"<?php echo _l('not_results_found'); ?>",
          searchPlaceholder:"<?php echo _l('search_ajax_placeholder'); ?>",
          currentlySelected:"<?php echo _l('currently_selected'); ?>",
        },
        requestDelay:500,
        cache:false,
        preprocessData: function(processData){
          var bs_data = [];
          var len = processData.length;
          for(var i = 0; i < len; i++){
            var tmp_data =  {
              'value': processData[i].id,
              'text': processData[i].name,
            };
            if(processData[i].subtext){
              tmp_data.data = {subtext:processData[i].subtext}
            }
            bs_data.push(tmp_data);
          }
          return bs_data;
        },
        preserveSelectedPosition:'after',
        preserveSelected:true
      }
      if(ajaxSelector.data('empty-title')){
        options.locale.emptyTitle = ajaxSelector.data('empty-title');
      }
      ajaxSelector.selectpicker().ajaxSelectPicker(options);
    }
  }
 </script>
<?php
}

function get_task_status_by_id($id)
{
   $CI = &get_instance();
   $statuses = $CI->tasks_model->get_statuses();

   $status = array(
      'id'=>0,
      'bg_color'=>'#333',
      'text_color'=>'#333',
      'name'=>'[Status Not Found]',
      'order'=>1,
      );

    foreach($statuses as $s){
        if($s['id'] == $id){
            $status = $s;
            break;
        }
    }

    return $status;
}
/**
 * Function that format task status for the final user
 * @param  string  $id    status id
 * @param  boolean $text
 * @param  boolean $clean
 * @return string
 */
function format_task_status($status, $text = false, $clean = false)
{
    if(!is_array($status)){
        $status = get_task_status_by_id($status);
    }

    $status_name = $status['name'];
    $hook_data = do_action('task_status_name',array('current'=>$status_name,'status_id'=>$status['id']));
    $status_name = $hook_data['current'];

    if ($clean == true) {
        return $status_name;
    }

    $style = '';
    $class = '';
    if ($text == false) {
        $style = 'border: 1px solid '.$status['color'].';color:'.$status['color'].';';
        $class = 'label';
    } else {
        $style = 'color:'.$status['color'].';';
    }

    return '<span class="'.$class.'" style="'.$style.'">' . $status_name . '</span>';
}
if(!function_exists('get_table_items_and_taxes')) {
/**
 * Pluggable function for all table items HTML and PDF
 * @param  array  $items         all items
 * @param  [type]  $type          where do items come form, eq invoice,estimate,proposal etc..
 * @param  boolean $admin_preview in admin preview add additional sortable classes
 * @return array
 */
    function get_table_items_and_taxes($items, $type, $admin_preview = false)
    {
        $result['html']    = '';
        $result['taxes']   = array();
        $_calculated_taxes = array();
        $i                 = 1;
        foreach ($items as $item) {
            $_item             = '';
            $tr_attrs       = '';
            $td_first_sortable = '';
            if ($admin_preview == true) {
                $tr_attrs       = ' class="sortable" data-item-id="' . $item['id'] . '"';
                $td_first_sortable = ' class="dragger item_no"';
            }

            if(class_exists('pdf')){
                $font_size = get_option('pdf_font_size');
                if($font_size == ''){
                    $font_size = 10;
                }

                $tr_attrs .= ' style="font-size:'.($font_size+4).'px;"';
            }

            $_item .= '<tr' . $tr_attrs . '>';
            $_item .= '<td' . $td_first_sortable . ' align="center">' . $i . '</td>';
            $_item .= '<td class="description" align="left;"><span style="font-size:'.(isset($font_size) ? $font_size+4 : '').'px;"><strong>' . $item['description'] . '</strong></span><br /><span style="color:#424242;">' . $item['long_description'] . '</span></td>';

            $_item .= '<td align="right">' . floatVal($item['qty']);
            if ($item['unit']) {
                $_item .= ' ' . $item['unit'];
            }

            $_item .= '</td>';
            $_item .= '<td align="right">' . _format_number($item['rate']) . '</td>';
            if (get_option('show_tax_per_item') == 1) {
                $_item .= '<td align="right">';
            }

            $item_taxes = array();

            // Separate functions exists to get item taxes for Invoice, Estimate, Proposal.
            $func_taxes = 'get_'.$type.'_item_taxes';
            if(function_exists($func_taxes)){
                $item_taxes = call_user_func($func_taxes, $item['id']);
            }

            if (count($item_taxes) > 0) {
                foreach ($item_taxes as $tax) {
                    $calc_tax     = 0;
                    $tax_not_calc = false;
                    if (!in_array($tax['taxname'], $_calculated_taxes)) {
                        array_push($_calculated_taxes, $tax['taxname']);
                        $tax_not_calc = true;
                    }
                    if ($tax_not_calc == true) {
                        $result['taxes'][$tax['taxname']]          = array();
                        $result['taxes'][$tax['taxname']]['total'] = array();
                        array_push($result['taxes'][$tax['taxname']]['total'], (($item['qty'] * $item['rate']) / 100 * $tax['taxrate']));
                        $result['taxes'][$tax['taxname']]['tax_name'] = $tax['taxname'];
                        $result['taxes'][$tax['taxname']]['taxrate']  = $tax['taxrate'];
                    } else {
                        array_push($result['taxes'][$tax['taxname']]['total'], (($item['qty'] * $item['rate']) / 100 * $tax['taxrate']));
                    }
                    if (get_option('show_tax_per_item') == 1) {
                        $item_tax = '';
                        if ((count($item_taxes) > 1 && get_option('remove_tax_name_from_item_table') == false) || get_option('remove_tax_name_from_item_table') == false || mutiple_taxes_found_for_item($item_taxes)) {
                            $tmp = explode('|',$tax['taxname']);
                            $item_tax = $tmp[0] . ' ' . _format_number($tmp[1]) . '%<br />';
                        } else {
                            $item_tax .= _format_number($tax['taxrate']) . '%';
                        }
                        $hook_data = array('final_tax_html'=>$item_tax,'item_taxes'=>$item_taxes,'item_id'=>$item['id']);
                        $hook_data = do_action('item_tax_table_row',$hook_data);
                        $item_tax = $hook_data['final_tax_html'];
                        $_item .= $item_tax;
                    }
                }
            } else {
                if (get_option('show_tax_per_item') == 1) {
                    $hook_data = array('final_tax_html'=>'0%','item_taxes'=>$item_taxes,'item_id'=>$item['id']);
                    $hook_data = do_action('item_tax_table_row',$hook_data);
                    $_item .= $hook_data['final_tax_html'];
                }
            }

            if (get_option('show_tax_per_item') == 1) {
                $_item .= '</td>';
            }

            /**
             * Since @version 1.7.0
             * Possible action hook user to include tax in item total amount calculated with the quantiy
             * eq Rate * QTY + TAXES APPLIED
             */

            $hook_data = do_action('final_item_amount', array(
                    'amount'=>($item['qty'] * $item['rate']),
                    'item_taxes'=>$item_taxes,
                    'item'=>$item
                ));

            $item_amount_with_quantity = _format_number($hook_data['amount']);

            $_item .= '<td class="amount" align="right">' . $item_amount_with_quantity . '</td>';
            $_item .= '</tr>';
            $result['html'] .= $_item;
            $i++;
        }

       return do_action('before_return_table_items_html_and_taxes',$result);
    }

}
/**
 * @deprecated
 */
function get_table_items_html_and_taxes($items, $type, $admin_preview = false)
{
    return get_table_items_and_taxes($items, $type, $admin_preview);
}
/**
 * @deprecated
 */
function get_table_items_pdf_and_taxes($items, $type)
{
    return get_table_items_and_taxes($items, $type);
}
function protected_file_url_by_path($path){
    return str_replace(FCPATH, '', $path);
}
/**
 * Callback for check_for_links
 */
function _make_url_clickable_cb($matches)
{
    $ret = '';
    $url = $matches[2];
    if (empty($url))
        return $matches[0];
    // removed trailing [.,;:] from URL
    if (in_array(substr($url, -1), array(
        '.',
        ',',
        ';',
        ':'
    )) === true) {
        $ret = substr($url, -1);
        $url = substr($url, 0, strlen($url) - 1);
    }
    return $matches[1] . "<a href=\"$url\" rel=\"nofollow\" target='_blank'>$url</a>" . $ret;
}
/**
 * Callback for check_for_links
 */
function _make_web_ftp_clickable_cb($matches)
{
    $ret  = '';
    $dest = $matches[2];
    $dest = 'http://' . $dest;
    if (empty($dest))
        return $matches[0];
    // removed trailing [,;:] from URL
    if (in_array(substr($dest, -1), array(
        '.',
        ',',
        ';',
        ':'
    )) === true) {
        $ret  = substr($dest, -1);
        $dest = substr($dest, 0, strlen($dest) - 1);
    }
    return $matches[1] . "<a href=\"$dest\" rel=\"nofollow\" target='_blank'>$dest</a>" . $ret;
}
/**
 * Callback for check_for_links
 */
function _make_email_clickable_cb($matches)
{
    $email = $matches[2] . '@' . $matches[3];
    return $matches[1] . "<a href=\"mailto:$email\">$email</a>";
}
/**
 * Check for links/emails/ftp in string to wrap in href
 * @param  string $ret
 * @return string      formatted string with href in any found
 */
function check_for_links($ret)
{
    $ret = ' ' . $ret;
    // in testing, using arrays here was found to be faster
    $ret = preg_replace_callback('#([\s>])([\w]+?://[\w\\x80-\\xff\#$%&~/.\-;:=,?@\[\]+]*)#is', '_make_url_clickable_cb', $ret);
    $ret = preg_replace_callback('#([\s>])((www|ftp)\.[\w\\x80-\\xff\#$%&~/.\-;:=,?@\[\]+]*)#is', '_make_web_ftp_clickable_cb', $ret);
    $ret = preg_replace_callback('#([\s>])([.0-9a-z_+-]+)@(([0-9a-z-]+\.)+[0-9a-z]{2,})#i', '_make_email_clickable_cb', $ret);
    // this one is not in an array because we need it to run last, for cleanup of accidental links within links
    $ret = preg_replace("#(<a( [^>]+?>|>))<a [^>]+?>([^>]+?)</a></a>#i", "$1$3</a>", $ret);
    $ret = trim($ret);
    return $ret;
}
/**
 * Strip tags
 * @param  string $html string to strip tags
 * @return string
 */
function _strip_tags($html)
{
    return strip_tags($html, '<br>,<em>,<p>,<ul>,<ol>,<li>,<h4><h3><h2><h1>,<pre>,<code>,<a>,<img>,<strong>,<b>,<blockquote>,<table>,<thead>,<th>,<tr>,<td>,<tbody>,<tfoot>');
}
/**
 * Adjust color brightness
 * @param  string $hex   hex color to adjust from
 * @param  mixed $steps eq -20 or 20
 * @return string
 */
function adjust_color_brightness($hex, $steps)
{
    // Steps should be between -255 and 255. Negative = darker, positive = lighter
    $steps = max(-255, min(255, $steps));
    // Normalize into a six character long hex string
    $hex   = str_replace('#', '', $hex);
    if (strlen($hex) == 3) {
        $hex = str_repeat(substr($hex, 0, 1), 2) . str_repeat(substr($hex, 1, 1), 2) . str_repeat(substr($hex, 2, 1), 2);
    }
    // Split into three parts: R, G and B
    $color_parts = str_split($hex, 2);
    $return      = '#';
    foreach ($color_parts as $color) {
        $color = hexdec($color); // Convert to decimal
        $color = max(0, min(255, $color + $steps)); // Adjust color
        $return .= str_pad(dechex($color), 2, '0', STR_PAD_LEFT); // Make two char hex code
    }
    return $return;
}
/**
 * Convert hex color to rgb
 * @param  string $color color hex code
 * @return string
 */
function hex2rgb($color)
{
    $color = str_replace('#', '', $color);
    if (strlen($color) != 6) {
        return array(
            0,
            0,
            0
        );
    }
    $rgb = array();
    for ($x = 0; $x < 3; $x++) {
        $rgb[$x] = hexdec(substr($color, (2 * $x), 2));
    }
    return $rgb;
}
/**
 * Function that strip all html tags from string/text/html
 * @param  string $str
 * @param  string $allowed prevent specific tags to be stripped
 * @return string
 */
function strip_html_tags($str, $allowed = '')
{
    $str = preg_replace('/(<|>)\1{2}/is', '', $str);
    $str = preg_replace(array(
        // Remove invisible content
        '@<head[^>]*?>.*?</head>@siu',
        '@<style[^>]*?>.*?</style>@siu',
        '@<script[^>]*?.*?</script>@siu',
        '@<object[^>]*?.*?</object>@siu',
        '@<embed[^>]*?.*?</embed>@siu',
        '@<applet[^>]*?.*?</applet>@siu',
        '@<noframes[^>]*?.*?</noframes>@siu',
        '@<noscript[^>]*?.*?</noscript>@siu',
        '@<noembed[^>]*?.*?</noembed>@siu',
        // Add line breaks before and after blocks
        '@</?((address)|(blockquote)|(center)|(del))@iu',
        '@</?((div)|(h[1-9])|(ins)|(isindex)|(p)|(pre))@iu',
        '@</?((dir)|(dl)|(dt)|(dd)|(li)|(menu)|(ol)|(ul))@iu',
        '@</?((table)|(th)|(td)|(caption))@iu',
        '@</?((form)|(button)|(fieldset)|(legend)|(input))@iu',
        '@</?((label)|(select)|(optgroup)|(option)|(textarea))@iu',
        '@</?((frameset)|(frame)|(iframe))@iu'
    ), array(
        ' ',
        ' ',
        ' ',
        ' ',
        ' ',
        ' ',
        ' ',
        ' ',
        ' ',
        "\n\$0",
        "\n\$0",
        "\n\$0",
        "\n\$0",
        "\n\$0",
        "\n\$0",
        "\n\$0",
        "\n\$0"
    ), $str);

    $str = strip_tags($str, $allowed);

    // Remove on events from attributes
    $re = '/\bon[a-z]+\s*=\s*(?:([\'"]).+?\1|(?:\S+?\(.*?\)(?=[\s>])))/i';
    $str = preg_replace($re, '', $str);

    return $str;
}
?>
