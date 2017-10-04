<?php
header('Content-Type: text/html; charset=utf-8');
defined('BASEPATH') or exit('No direct script access allowed');

class Leads extends Admin_controller
{

    private $not_importable_leads_fields = array(
        'id',
        'source',
        'assigned',
        'status',
        'dateadded',
        'last_status_change',
        'addedfrom',
        'leadorder',
        'date_converted',
        'lost',
        'junk',
        'is_imported_from_email_integration',
        'email_integration_uid',
        'is_public',
        'dateassigned',
        'client_id',
        'lastcontact',
        'last_lead_status',
        'from_form_id',
        'default_language'
    );

    public function __construct()
    {
        parent::__construct();
        $this->load->model('leads_model');
    }
    
    /* List all leads canban and table */
    public function index($id = '')
    {
        if (! is_staff_member()) {
            access_denied('Leads');
        }
        if ($this->input->is_ajax_request()) {
            if ($this->input->get('kanban')) {
                $data['statuses'] = $this->leads_model->get_status();
                echo $this->load->view('admin/leads/kan-ban', $data, true);
                die();
            } elseif ($this->input->get('table_leads')) {
                $this->perfex_base->get_table_data('leads');
            }
        }
        $data['switch_kanban'] = true;
        if ($this->session->has_userdata('leads_kanban_view') && $this->session->userdata('leads_kanban_view') == 'true') {
            $data['switch_kanban'] = false;
            $data['bodyclass'] = 'kan-ban-body';
        }
        
        $data['staff'] = $this->staff_model->get('', 1);
        
        $data['statuses'] = $this->leads_model->get_status();
        $data['sources'] = $this->leads_model->get_source();
        $data['title'] = _l('leads');
        // in case accesed the url leads/index/ directly with id - used in search
        $data['leadid'] = $id;
        $this->load->view('admin/leads/manage_leads', $data);
    }

    public function leads_kanban_load_more()
    {
        if (! is_staff_member()) {
            header("HTTP/1.0 404 Not Found");
            echo _l('access_denied');
            die();
        }
        
        $status = $this->input->get('status');
        $page = $this->input->get('page');
        
        $this->db->where('id', $status);
        $status = $this->db->get('tblleadsstatus')->row_array();
        
        $leads = $this->leads_model->do_kanban_query($status['id'], $this->input->get('search'), $page, array(
            'sort_by' => $this->input->get('sort_by'),
            'sort' => $this->input->get('sort')
        ));
        
        foreach ($leads as $lead) {
            $this->load->view('admin/leads/_kan_ban_card', array(
                'lead' => $lead,
                'status' => $status
            ));
        }
    }
    
    /* Add or update lead */
    public function lead($id = '')
    {
        if (! is_staff_member()) {
            header("HTTP/1.0 404 Not Found");
            echo _l('access_denied');
            die();
        }
        $reminder_data = '';
        
        $data['lead_locked'] = false;
        if ($this->input->get('status_id')) {
            $data['status_id'] = $this->input->get('status_id');
        }  // end if $this->input->get('status_id')
else {
            $data['status_id'] = get_option('leads_default_status');
        } // end else
        
        $lead = null;
        if (is_numeric($id)) {
            $lead = $this->leads_model->get($id);
            if (! $lead) {
                header("HTTP/1.0 404 Not Found");
                echo _l('lead_not_found');
                die();
            }
            if (! is_admin()) {
                if (($lead->assigned != get_staff_user_id() && $lead->addedfrom != get_staff_user_id() && $lead->is_public != 1)) {
                    header('HTTP/1.0 400 Bad error');
                    echo _l('access_denied');
                    die();
                }
            }
        }
        if ($this->input->post()) {
            if ($id == '') {
                $id = $this->leads_model->add($this->input->post());
                $_id = false;
                $success = false;
                $message = '';
                if ($id) {
                    $success = true;
                    $_id = $id;
                    $message = _l('added_successfully', _l('lead'));
                }
                echo json_encode(array(
                    'success' => $success,
                    'id' => $_id,
                    'message' => $message
                ));
            }  // end if $id == ''
else {
                $proposal_warning = false;
                $original_lead = $this->leads_model->get($id);
                $data = $this->input->post();
                $success = $this->leads_model->update($data, $id);
                $message = '';
                if ($success) {
                    $lead = $this->leads_model->get($id);
                    if (total_rows('tblproposals', array(
                        'rel_type' => 'lead',
                        'rel_id' => $id
                    )) > 0 && ($original_lead->email != $lead->email) && $lead->email != '') {
                        $proposal_warning = true;
                    }
                    $message = _l('updated_successfully', _l('lead'));
                }
                echo json_encode(array(
                    'success' => $success,
                    'message' => $message,
                    'proposal_warning' => $proposal_warning
                ));
            }
            die();
        } // end if $this->input->post()
        if ($lead == null && is_numeric($id)) {
            echo _l('lead_not_found');
            die();
        }  // end if $lead == null && is_numeric($id)
else {
            if (total_rows('tblclients', array(
                'leadid' => $id
            )) > 0) {
                if (! is_admin() && get_option('lead_lock_after_convert_to_customer') == 1) {
                    $data['lead_locked'] = true;
                }
            }
            $data['members'] = $this->staff_model->get('', 1, array(
                'is_not_staff' => 0
            ));
            
            if ($lead) {
                $reminder_data = $this->load->view('admin/includes/modals/reminder', array(
                    'id' => $lead->id,
                    'name' => 'lead',
                    'members' => $data['members'],
                    'reminder_title' => _l('lead_set_reminder_title')
                ), true);
            }
            $data['lead'] = $lead;
            $data['mail_activity'] = $this->leads_model->get_mail_activity($id);
            $data['notes'] = $this->misc_model->get_notes($id, 'lead');
            $data['activity_log'] = $this->leads_model->get_lead_activity_log($id);
        } // end else
        
        $data['statuses'] = $this->leads_model->get_status();
        $data['sources'] = $this->leads_model->get_source();
        $data = do_action('lead_view_data', $data);
        echo json_encode(array(
            'data' => $this->load->view('admin/leads/lead', $data, true),
            'reminder_data' => $reminder_data
        ));
    }

    public function save_form_data()
    {
        $data = $this->input->post(null, null);
        
        // form data should be always sent to the request and never should be empty
        // this code is added to prevent losing the old form in case any errors
        if (! isset($data['formData']) || isset($data['formData']) && ! $data['formData']) {
            echo json_encode(array(
                'success' => false
            ));
            die();
        }
        $this->db->where('id', $data['id']);
        $this->db->update('tblwebtolead', array(
            'form_data' => $data['formData']
        ));
        if ($this->db->affected_rows() > 0) {
            echo json_encode(array(
                'success' => true,
                'message' => _l('updated_successfully', _l('web_to_lead_form'))
            ));
        } else {
            echo json_encode(array(
                'success' => false
            ));
        }
    }

    public function form($id = '')
    {
        if (! is_admin()) {
            access_denied('Web To Lead Access');
        }
        if ($this->input->post()) {
            if ($id == '') {
                $data = $this->input->post();
                $id = $this->leads_model->add_form($data);
                if ($id) {
                    set_alert('success', _l('added_successfully', _l('web_to_lead_form')));
                    redirect(admin_url('leads/form/' . $id));
                }
            } else {
                $success = $this->leads_model->update_form($id, $this->input->post());
                if ($success) {
                    set_alert('success', _l('updated_successfully', _l('web_to_lead_form')));
                }
                redirect(admin_url('leads/form/' . $id));
            }
        }
        
        $data['formData'] = array();
        $custom_fields = get_custom_fields('leads', 'type != "link"');
        
        $cfields = format_external_form_custom_fields($custom_fields);
        $data['title'] = _l('web_to_lead');
        
        if ($id != '') {
            $data['form'] = $this->leads_model->get_form(array(
                'id' => $id
            ));
            $data['title'] = $data['form']->name . ' - ' . _l('web_to_lead_form');
            $data['formData'] = $data['form']->form_data;
        }
        
        $this->load->model('roles_model');
        $data['roles'] = $this->roles_model->get();
        $data['sources'] = $this->leads_model->get_source();
        $data['statuses'] = $this->leads_model->get_status();
        
        $data['members'] = $this->staff_model->get('', 1, array(
            'is_not_staff' => 0
        ));
        
        $data['languages'] = $this->perfex_base->get_available_languages();
        $data['cfields'] = $cfields;
        $data['form_builder_assets'] = true;
        
        $db_fields = array();
        $fields = array(
            'name',
            'title',
            'email',
            'phonenumber',
            'company',
            'address',
            'city',
            'state',
            'country',
            'zip',
            'description',
            'website'
        );
        
        $fields = do_action('lead_form_available_database_fields', $fields);
        
        $className = 'form-control';
        
        foreach ($fields as $f) {
            $_field_object = new stdClass();
            $type = 'text';
            
            if ($f == 'email') {
                $type = 'email';
            } elseif ($f == 'description') {
                $type = 'textarea';
            } elseif ($f == 'country') {
                $type = 'select';
            }
            
            if ($f == 'name') {
                $label = _l('lead_add_edit_name');
            } elseif ($f == 'email') {
                $label = _l('lead_add_edit_email');
            } elseif ($f == 'phonenumber') {
                $label = _l('lead_add_edit_phonenumber');
            } else {
                $label = _l('lead_' . $f);
            }
            
            $field_array = array(
                'type' => $type,
                'label' => $label,
                'className' => $className,
                'name' => $f
            );
            
            if ($f == 'country') {
                $field_array['values'] = array();
                $countries = get_all_countries();
                foreach ($countries as $country) {
                    $selected = false;
                    if (get_option('customer_default_country') == $country['country_id']) {
                        $selected = true;
                    }
                    array_push($field_array['values'], array(
                        'label' => $country['short_name'],
                        'value' => (int) $country['country_id'],
                        'selected' => $selected
                    ));
                }
            }
            
            if ($f == 'name') {
                $field_array['required'] = true;
            }
            
            $_field_object->label = $label;
            $_field_object->name = $f;
            $_field_object->fields = array();
            $_field_object->fields[] = $field_array;
            $db_fields[] = $_field_object;
        }
        $data['db_fields'] = $db_fields;
        $this->load->view('admin/leads/formbuilder', $data);
    }

    public function forms($id = '')
    {
        if (! is_admin()) {
            access_denied('Web To Lead Access');
        }
        
        if ($this->input->is_ajax_request()) {
            $this->perfex_base->get_table_data('web_to_lead');
        }
        
        $data['title'] = _l('web_to_lead');
        $this->load->view('admin/leads/forms', $data);
    }

    public function delete_form($id)
    {
        if (! is_admin()) {
            access_denied('Web To Lead Access');
        }
        
        $success = $this->leads_model->delete_form($id);
        if ($success) {
            set_alert('success', _l('deleted', _l('web_to_lead_form')));
        }
        
        redirect(admin_url('leads/forms'));
    }

    public function update_all_proposal_emails_linked_to_lead($id)
    {
        $success = false;
        $email = '';
        if ($this->input->post('update')) {
            $this->load->model('proposals_model');
            
            $this->db->select('email');
            $this->db->where('id', $id);
            $email = $this->db->get('tblleads')->row()->email;
            
            $proposals = $this->proposals_model->get('', array(
                'rel_type' => 'lead',
                'rel_id' => $id
            ));
            $affected_rows = 0;
            
            foreach ($proposals as $proposal) {
                $this->db->where('id', $proposal['id']);
                $this->db->update('tblproposals', array(
                    'email' => $email
                ));
                if ($this->db->affected_rows() > 0) {
                    $affected_rows ++;
                }
            }
            
            if ($affected_rows > 0) {
                $success = true;
            }
        }
        
        echo json_encode(array(
            'success' => $success,
            'message' => _l('proposals_emails_updated', array(
                _l('lead_lowercase'),
                $email
            ))
        ));
    }

    public function switch_kanban($set = 0)
    {
        if ($set == 1) {
            $set = 'true';
        } else {
            $set = 'false';
        }
        $this->session->set_userdata(array(
            'leads_kanban_view' => $set
        ));
        redirect($_SERVER['HTTP_REFERER']);
    }
    
    /* Delete lead from database */
    public function delete($id)
    {
        if (! $id) {
            redirect(admin_url('sources'));
        }
        if (! is_lead_creator($id) && ! is_admin()) {
            die();
        }
        $response = $this->leads_model->delete($id);
        if (is_array($response) && isset($response['referenced'])) {
            set_alert('warning', _l('is_referenced', _l('lead_lowercase')));
        } elseif ($response === true) {
            set_alert('success', _l('deleted', _l('lead')));
        } else {
            set_alert('warning', _l('problem_deleting', _l('lead_lowercase')));
        }
        redirect($_SERVER['HTTP_REFERER']);
    }

    public function mark_as_lost($id)
    {
        if (! is_staff_member()) {
            header("HTTP/1.0 404 Not Found");
            echo _l('access_denied');
            die();
        }
        $message = '';
        $success = $this->leads_model->mark_as_lost($id);
        if ($success) {
            $message = _l('lead_marked_as_lost');
        }
        echo json_encode(array(
            'success' => $success,
            'message' => $message
        ));
    }

    public function unmark_as_lost($id)
    {
        if (! is_staff_member()) {
            header("HTTP/1.0 404 Not Found");
            echo _l('access_denied');
            die();
        }
        $message = '';
        $success = $this->leads_model->unmark_as_lost($id);
        if ($success) {
            $message = _l('lead_unmarked_as_lost');
        }
        echo json_encode(array(
            'success' => $success,
            'message' => $message
        ));
    }

    public function mark_as_junk($id)
    {
        if (! is_staff_member()) {
            header("HTTP/1.0 404 Not Found");
            echo _l('access_denied');
            die();
        }
        $message = '';
        $success = $this->leads_model->mark_as_junk($id);
        if ($success) {
            $message = _l('lead_marked_as_junk');
        }
        echo json_encode(array(
            'success' => $success,
            'message' => $message
        ));
    }

    public function unmark_as_junk($id)
    {
        if (! is_staff_member()) {
            header("HTTP/1.0 404 Not Found");
            echo _l('access_denied');
            die();
        }
        $message = '';
        $success = $this->leads_model->unmark_as_junk($id);
        if ($success) {
            $message = _l('lead_unmarked_as_junk');
        }
        echo json_encode(array(
            'success' => $success,
            'message' => $message
        ));
    }

    public function add_lead_attachment()
    {
        $leadid = $this->input->post('leadid');
        echo json_encode(handle_lead_attachments($leadid));
    }

    public function add_external_attachment()
    {
        if ($this->input->post()) {
            $this->leads_model->add_attachment_to_database($this->input->post('lead_id'), $this->input->post('files'), $this->input->post('external'));
        }
    }

    public function delete_attachment($id)
    {
        if (! is_staff_member()) {
            header("HTTP/1.0 404 Not Found");
            echo _l('access_denied');
            die();
        }
        echo json_encode(array(
            'success' => $this->leads_model->delete_lead_attachment($id)
        ));
    }

    public function delete_note($id)
    {
        if (! is_staff_member()) {
            header("HTTP/1.0 404 Not Found");
            echo _l('access_denied');
            die();
        }
        echo json_encode(array(
            'success' => $this->misc_model->delete_note($id)
        ));
    }
    
    // Sources
    /* Manage leads sources */
    public function sources()
    {
        if (! is_admin()) {
            access_denied('Leads Sources');
        }
        $data['sources'] = $this->leads_model->get_source();
        $data['title'] = 'Leads sources';
        $this->load->view('admin/leads/manage_sources', $data);
    }
    
    /* Add or update leads sources */
    public function source()
    {
        if (! is_admin()) {
            access_denied('Leads Sources');
        }
        if ($this->input->post()) {
            if (! $this->input->post('id')) {
                $id = $this->leads_model->add_source($this->input->post());
                if ($id) {
                    set_alert('success', _l('added_successfully', _l('lead_source')));
                }
            } else {
                $data = $this->input->post();
                $id = $data['id'];
                unset($data['id']);
                $success = $this->leads_model->update_source($data, $id);
                if ($success) {
                    set_alert('success', _l('updated_successfully', _l('lead_source')));
                }
            }
        }
    }
    
    /* Delete leads source */
    public function delete_source($id)
    {
        if (! is_admin()) {
            access_denied('Delete Lead Source');
        }
        if (! $id) {
            redirect(admin_url('leads/sources'));
        }
        $response = $this->leads_model->delete_source($id);
        if (is_array($response) && isset($response['referenced'])) {
            set_alert('warning', _l('is_referenced', _l('lead_source_lowercase')));
        } elseif ($response == true) {
            set_alert('success', _l('deleted', _l('lead_source')));
        } else {
            set_alert('warning', _l('problem_deleting', _l('lead_source_lowercase')));
        }
        redirect(admin_url('leads/sources'));
    }
    
    // Statuses
    /* View leads statuses */
    public function statuses()
    {
        if (! is_admin()) {
            access_denied('Leads Statuses');
        }
        $data['statuses'] = $this->leads_model->get_status();
        $data['title'] = 'Leads statuses';
        $this->load->view('admin/leads/manage_statuses', $data);
    }
    
    /* Add or update leads status */
    public function status()
    {
        if (! is_admin()) {
            access_denied('Leads Statuses');
        }
        if ($this->input->post()) {
            if (! $this->input->post('id')) {
                $id = $this->leads_model->add_status($this->input->post());
                if ($id) {
                    set_alert('success', _l('added_successfully', _l('lead_status')));
                }
            } else {
                $data = $this->input->post();
                $id = $data['id'];
                unset($data['id']);
                $success = $this->leads_model->update_status($data, $id);
                if ($success) {
                    set_alert('success', _l('updated_successfully', _l('lead_status')));
                }
            }
        }
    }
    
    /* Delete leads status from databae */
    public function delete_status($id)
    {
        if (! is_admin()) {
            access_denied('Leads Statuses');
        }
        if (! $id) {
            redirect(admin_url('leads/statuses'));
        }
        $response = $this->leads_model->delete_status($id);
        if (is_array($response) && isset($response['referenced'])) {
            set_alert('warning', _l('is_referenced', _l('lead_status_lowercase')));
        } elseif ($response == true) {
            set_alert('success', _l('deleted', _l('lead_status')));
        } else {
            set_alert('warning', _l('problem_deleting', _l('lead_status_lowercase')));
        }
        redirect(admin_url('leads/statuses'));
    }
    
    /* Add new lead note */
    public function add_note($rel_id)
    {
        if (! is_staff_member()) {
            header("HTTP/1.0 404 Not Found");
            echo _l('access_denied');
            die();
        }
        if ($this->input->post()) {
            $data = $this->input->post();
            
            if ($data['contacted_indicator'] == 'yes') {
                $contacted_date = to_sql_date($data['custom_contact_date'], true);
                $data['date_contacted'] = $contacted_date;
            }
            unset($data['contacted_indicator']);
            unset($data['custom_contact_date']);
            
            $note_id = $this->misc_model->add_note($data, 'lead', $rel_id);
            if ($note_id) {
                if (isset($contacted_date)) {
                    $this->db->where('id', $rel_id);
                    $this->db->update('tblleads', array(
                        'lastcontact' => $contacted_date
                    ));
                    if ($this->db->affected_rows() > 0) {
                        $this->leads_model->log_lead_activity($rel_id, 'not_lead_activity_contacted', false, serialize(array(
                            get_staff_full_name(get_staff_user_id()),
                            _dt($contacted_date)
                        )));
                    }
                }
            }
        }
        echo $rel_id;
    }

    public function add_activity()
    {
        if (! is_staff_member()) {
            header("HTTP/1.0 404 Not Found");
            echo _l('access_denied');
            die();
        }
        
        if ($this->input->post()) {
            $leadid = $this->input->post('leadid');
            $message = $this->input->post('activity');
            $aId = $this->leads_model->log_lead_activity($leadid, $message);
            if ($aId) {
                $this->db->where('id', $aId);
                $this->db->update('tblleadactivitylog', array(
                    'custom_activity' => 1
                ));
            }
        }
    }

    public function get_convert_data($id)
    {
        if (! is_staff_member()) {
            header("HTTP/1.0 404 Not Found");
            echo _l('access_denied');
            die();
        }
        $data['lead'] = $this->leads_model->get($id);
        $this->load->view('admin/leads/convert_to_customer', $data);
    }

    /**
     * Convert lead to client
     * 
     * @since version 1.0.1
     * @return mixed
     */
    public function convert_to_customer()
    {
        if (! is_staff_member()) {
            access_denied('Lead Convert to Customer');
        }
        
        if ($this->input->post()) {
            $merge_db_field_country_found = false;
            $default_country = get_option('customer_default_country');
            $data = $this->input->post();
            $original_lead_email = $data['original_lead_email'];
            unset($data['original_lead_email']);
            
            if (isset($data['transfer_notes'])) {
                $notes = $this->misc_model->get_notes($data['leadid'], 'lead');
                unset($data['transfer_notes']);
            }
            
            if (isset($data['merge_db_fields'])) {
                $merge_db_fields = $data['merge_db_fields'];
                unset($data['merge_db_fields']);
            }
            if (isset($data['merge_db_contact_fields'])) {
                $merge_db_contact_fields = $data['merge_db_contact_fields'];
                unset($data['merge_db_contact_fields']);
            }
            if (isset($data['include_leads_custom_fields'])) {
                $include_leads_custom_fields = $data['include_leads_custom_fields'];
                unset($data['include_leads_custom_fields']);
            }
            if (! isset($merge_db_fields)) {
                if ($default_country != '') {
                    $data['country'] = $default_country;
                }
            } elseif (isset($merge_db_fields)) {
                foreach ($merge_db_fields as $key => $val) {
                    if ($val == 'country') {
                        $merge_db_field_country_found = true;
                        break;
                    }
                }
                if ($merge_db_field_country_found === false) {
                    if ($default_country != '') {
                        $data['country'] = $default_country;
                    }
                }
            }
            $id = $this->clients_model->add($data, true);
            if ($id) {
                if (isset($notes)) {
                    foreach ($notes as $note) {
                        $this->db->insert('tblnotes', array(
                            'rel_id' => $id,
                            'rel_type' => 'customer',
                            'dateadded' => $note['dateadded'],
                            'addedfrom' => $note['addedfrom'],
                            'description' => $note['description'],
                            'date_contacted' => $note['date_contacted']
                        ));
                    }
                }
                if (! has_permission('customers', '', 'view') && get_option('auto_assign_customer_admin_after_lead_convert') == 1) {
                    $this->db->insert('tblcustomeradmins', array(
                        'date_assigned' => date('Y-m-d H:i:s'),
                        'customer_id' => $id,
                        'staff_id' => get_staff_user_id()
                    ));
                }
                $this->leads_model->log_lead_activity($data['leadid'], 'not_lead_activity_converted', false, serialize(array(
                    get_staff_full_name()
                )));
                $default_status = $this->leads_model->get_status('', array(
                    'isdefault' => 1
                ));
                $this->db->where('id', $data['leadid']);
                $this->db->update('tblleads', array(
                    'date_converted' => date('Y-m-d H:i:s'),
                    'status' => $default_status[0]['id'],
                    'junk' => 0,
                    'lost' => 0
                ));
                // Check if lead email is different then client email
                $contact = $this->clients_model->get_contact(get_primary_contact_user_id($id));
                if ($contact->email != $original_lead_email) {
                    if ($original_lead_email != '') {
                        $this->leads_model->log_lead_activity($data['leadid'], 'not_lead_activity_converted_email', false, serialize(array(
                            $original_lead_email,
                            $contact->email
                        )));
                    }
                }
                if (isset($include_leads_custom_fields)) {
                    foreach ($include_leads_custom_fields as $fieldid => $value) {
                        // checked don't merge
                        if ($value == 5) {
                            continue;
                        }
                        // get the value of this leads custom fiel
                        $this->db->where('relid', $data['leadid']);
                        $this->db->where('fieldto', 'leads');
                        $this->db->where('fieldid', $fieldid);
                        $lead_custom_field_value = $this->db->get('tblcustomfieldsvalues')->row()->value;
                        // Is custom field for contact ot customer
                        if ($value == 1 || $value == 4) {
                            if ($value == 4) {
                                $field_to = 'contacts';
                            } else {
                                $field_to = 'customers';
                            }
                            $this->db->where('id', $fieldid);
                            $field = $this->db->get('tblcustomfields')->row();
                            // check if this field exists for custom fields
                            $this->db->where('fieldto', $field_to);
                            $this->db->where('name', $field->name);
                            $exists = $this->db->get('tblcustomfields')->row();
                            $copy_custom_field_id = null;
                            if ($exists) {
                                $copy_custom_field_id = $exists->id;
                            } else {
                                // there is no name with the same custom field for leads at the custom side create the custom field now
                                $this->db->insert('tblcustomfields', array(
                                    'fieldto' => $field_to,
                                    'name' => $field->name,
                                    'required' => $field->required,
                                    'type' => $field->type,
                                    'options' => $field->options,
                                    'display_inline' => $field->display_inline,
                                    'field_order' => $field->field_order,
                                    'slug' => slug_it($field_to . '_' . $field->name, array(
                                        'delimiter' => '_'
                                    )),
                                    'active' => $field->active,
                                    'only_admin' => $field->only_admin,
                                    'show_on_table' => $field->show_on_table,
                                    'bs_column' => $field->bs_column
                                ));
                                $new_customer_field_id = $this->db->insert_id();
                                if ($new_customer_field_id) {
                                    $copy_custom_field_id = $new_customer_field_id;
                                }
                            }
                            if ($copy_custom_field_id != null) {
                                $insert_to_custom_field_id = $id;
                                if ($value == 4) {
                                    $insert_to_custom_field_id = get_primary_contact_user_id($id);
                                    ;
                                }
                                $this->db->insert('tblcustomfieldsvalues', array(
                                    'relid' => $insert_to_custom_field_id,
                                    'fieldid' => $copy_custom_field_id,
                                    'fieldto' => $field_to,
                                    'value' => $lead_custom_field_value
                                ));
                            }
                        } elseif ($value == 2) {
                            if (isset($merge_db_fields)) {
                                $db_field = $merge_db_fields[$fieldid];
                                // in case user don't select anything from the db fields
                                if ($db_field == '') {
                                    continue;
                                }
                                if ($db_field == 'country' || $db_field == 'shipping_country' || $db_field == 'billing_country') {
                                    $this->db->where('iso2', $lead_custom_field_value);
                                    $this->db->or_where('short_name', $lead_custom_field_value);
                                    $this->db->or_like('long_name', $lead_custom_field_value);
                                    $country = $this->db->get('tblcountries')->row();
                                    if ($country) {
                                        $lead_custom_field_value = $country->country_id;
                                    } else {
                                        $lead_custom_field_value = 0;
                                    }
                                }
                                $this->db->where('userid', $id);
                                $this->db->update('tblclients', array(
                                    $db_field => $lead_custom_field_value
                                ));
                            }
                        } elseif ($value == 3) {
                            if (isset($merge_db_contact_fields)) {
                                $db_field = $merge_db_contact_fields[$fieldid];
                                if ($db_field == '') {
                                    continue;
                                }
                                $primary_contact_id = get_primary_contact_user_id($id);
                                $this->db->where('id', $primary_contact_id);
                                $this->db->update('tblcontacts', array(
                                    $db_field => $lead_custom_field_value
                                ));
                            }
                        }
                    }
                }
                // set the lead to status client in case is not status client
                $this->db->where('isdefault', 1);
                $status_client_id = $this->db->get('tblleadsstatus')->row()->id;
                $this->db->where('id', $data['leadid']);
                $this->db->update('tblleads', array(
                    'status' => $status_client_id
                ));
                set_alert('success', _l('lead_to_client_base_converted_success'));
                logActivity('Created Lead Client Profile [LeadID: ' . $data['leadid'] . ', ClientID: ' . $id . ']');
                redirect(admin_url('clients/client/' . $id));
            }
        }
    }
    
    // Ajax
    /* Used in canban when dragging */
    public function update_kan_ban_lead_status()
    {
        if ($this->input->post()) {
            if ($this->input->is_ajax_request()) {
                $this->leads_model->update_lead_status($this->input->post());
            }
        }
    }

    public function update_status_order()
    {
        if ($this->input->post()) {
            $this->leads_model->update_status_order();
        }
    }

    public function test_email_integration()
    {
        if (! is_admin()) {
            access_denied('Leads Test Email Integration');
        }
        
        require_once (APPPATH . 'third_party/php-imap/Imap.php');
        $mail = $this->leads_model->get_email_integration();
        $ps = $mail->password;
        if (false == $this->encryption->decrypt($ps)) {
            set_alert('danger', _l('failed_to_decrypt_password'));
            redirect(admin_url('leads/email_integration'));
        }
        $mailbox = $mail->imap_server;
        $username = $mail->email;
        $password = $this->encryption->decrypt($ps);
        $encryption = $mail->encryption;
        // open connection
        $imap = new Imap($mailbox, $username, $password, $encryption);
        if ($imap->isConnected() === false) {
            set_alert('danger', _l('lead_email_connection_not_ok') . '<br /><b>' . $imap->getError() . '</b>');
        } else {
            set_alert('success', _l('lead_email_connection_ok'));
        }
        redirect(admin_url('leads/email_integration'));
    }

    public function email_integration()
    {
        if (! is_admin()) {
            access_denied('Leads Email Intregration');
        }
        if ($this->input->post()) {
            $data = $this->input->post(null, false);
            
            if (isset($data['fakeusernameremembered'])) {
                unset($data['fakeusernameremembered']);
            }
            if (isset($data['fakepasswordremembered'])) {
                unset($data['fakepasswordremembered']);
            }
            
            $success = $this->leads_model->update_email_integration($data);
            if ($success) {
                set_alert('success', _l('leads_email_integration_updated'));
            }
            redirect(admin_url('leads/email_integration'));
        }
        $data['roles'] = $this->roles_model->get();
        $data['sources'] = $this->leads_model->get_source();
        $data['statuses'] = $this->leads_model->get_status();
        
        $data['members'] = $this->staff_model->get('', 1, array(
            'is_not_staff' => 0
        ));
        $data['title'] = _l('leads_email_integration');
        $data['mail'] = $this->leads_model->get_email_integration();
        $this->load->view('admin/leads/email_integration', $data);
    }

    public function change_status_color()
    {
        if ($this->input->post()) {
            $this->leads_model->change_status_color($this->input->post());
        }
    }

    public function import()
    {
        if (! is_admin()) {
            access_denied('Leads Import');
        }
        
        $simulate_data = array();
        $total_imported = 0;
        if ($this->input->post()) {
            if (isset($_FILES['file_csv']['name']) && $_FILES['file_csv']['name'] != '') {
                // Get the temp file path
                $tmpFilePath = $_FILES['file_csv']['tmp_name'];
                // Make sure we have a filepath
                if (! empty($tmpFilePath) && $tmpFilePath != '') {
                    // Setup our new file path
                    $newFilePath = TEMP_FOLDER . $_FILES['file_csv']['name'];
                    if (! file_exists(TEMP_FOLDER)) {
                        mkdir(TEMP_FOLDER, 777);
                    }
                    if (move_uploaded_file($tmpFilePath, $newFilePath)) {
                        $import_result = true;
                        $fd = fopen($newFilePath, 'r');
                        $rows = array();
                        while ($row = fgetcsv($fd)) {
                            $rows[] = $row;
                        }
                        fclose($fd);
                        $data['total_rows_post'] = count($rows);
                        if (count($rows) <= 1) {
                            set_alert('warning', 'Not enought rows for importing');
                            redirect(admin_url('leads/import'));
                        }
                        
                        unset($rows[0]);
                        if ($this->input->post('simulate')) {
                            if (count($rows) > 500) {
                                set_alert('warning', 'Recommended splitting the CSV file into smaller files. Our recomendation is 500 row, your CSV file has ' . count($rows));
                            }
                        }
                        $db_temp_fields = $this->db->list_fields('tblleads');
                        $db_fields = array();
                        foreach ($db_temp_fields as $field) {
                            if (in_array($field, $this->not_importable_leads_fields)) {
                                continue;
                            }
                            $db_fields[] = $field;
                        }
                        $custom_fields = get_custom_fields('leads');
                        $_row_simulate = 0;
                        foreach ($rows as $row) {
                            // do for db fields
                            $insert = array();
                            for ($i = 0; $i < count($db_fields); $i ++) {
                                // Avoid errors on nema field. is required in database
                                if ($db_fields[$i] == 'name' && $row[$i] == '') {
                                    $row[$i] = '/';
                                } elseif ($db_fields[$i] == 'country') {
                                    if ($row[$i] != '') {
                                        if (! is_numeric($row[$i])) {
                                            $this->db->where('iso2', $row[$i]);
                                            $this->db->or_where('short_name', $row[$i]);
                                            $this->db->or_where('long_name', $row[$i]);
                                            $country = $this->db->get('tblcountries')->row();
                                            if ($country) {
                                                $row[$i] = $country->country_id;
                                            } else {
                                                $row[$i] = 0;
                                            }
                                        }
                                    } else {
                                        $row[$i] = 0;
                                    }
                                }
                                $insert[$db_fields[$i]] = $row[$i];
                            }
                            if (count($insert) > 0) {
                                if (isset($insert['email']) && $insert['email'] != '') {
                                    if (total_rows('tblleads', array(
                                        'email' => $insert['email']
                                    )) > 0) {
                                        continue;
                                    }
                                }
                                $total_imported ++;
                                $insert['dateadded'] = date('Y-m-d H:i:s');
                                $insert['addedfrom'] = get_staff_user_id();
                                $insert['lastcontact'] = null;
                                $insert['status'] = $this->input->post('status');
                                $insert['source'] = $this->input->post('source');
                                if ($this->input->post('responsible')) {
                                    $insert['assigned'] = $this->input->post('responsible');
                                }
                                if (! $this->input->post('simulate')) {
                                    foreach ($insert as $key => $val) {
                                        $insert[$key] = trim($val);
                                    }
                                    $this->db->insert('tblleads', $insert);
                                    $leadid = $this->db->insert_id();
                                } else {
                                    if ($insert['country'] != 0) {
                                        $c = get_country($insert['country']);
                                        if ($c) {
                                            $insert['country'] = $c->short_name;
                                        }
                                    } else {
                                        $insert['country'] = '';
                                    }
                                    $simulate_data[$_row_simulate] = $insert;
                                    $leadid = true;
                                }
                                if ($leadid) {
                                    $insert = array();
                                    foreach ($custom_fields as $field) {
                                        if (! $this->input->post('simulate')) {
                                            if ($row[$i] != '') {
                                                $this->db->insert('tblcustomfieldsvalues', array(
                                                    'relid' => $leadid,
                                                    'fieldid' => $field['id'],
                                                    'value' => trim($row[$i]),
                                                    'fieldto' => 'leads'
                                                ));
                                            }
                                        } else {
                                            $simulate_data[$_row_simulate][$field['name']] = $row[$i];
                                        }
                                        $i ++;
                                    }
                                }
                            }
                            $_row_simulate ++;
                            if ($this->input->post('simulate') && $_row_simulate >= 100) {
                                break;
                            }
                        }
                        unlink($newFilePath);
                    }
                } else {
                    set_alert('warning', _l('import_upload_failed'));
                }
            }
        }
        $data['statuses'] = $this->leads_model->get_status();
        $data['sources'] = $this->leads_model->get_source();
        
        $data['members'] = $this->staff_model->get('', 1);
        if (count($simulate_data) > 0) {
            $data['simulate'] = $simulate_data;
        }
        if (isset($import_result)) {
            set_alert('success', _l('import_total_imported', $total_imported));
        }
        
        $data['not_importable'] = $this->not_importable_leads_fields;
        $data['title'] = 'Import';
        $this->load->view('admin/leads/import', $data);
    }

    public function email_exists()
    {
        if ($this->input->post()) {
            // First we need to check if the email is the same
            $leadid = $this->input->post('leadid');
            
            if ($leadid != '') {
                $this->db->where('id', $leadid);
                $_current_email = $this->db->get('tblleads')->row();
                if ($_current_email->email == $this->input->post('email')) {
                    echo json_encode(true);
                    die();
                }
            }
            $exists = total_rows('tblleads', array(
                'email' => $this->input->post('email')
            ));
            if ($exists > 0) {
                echo 'false';
            } else {
                echo 'true';
            }
        }
    }

    public function bulk_action()
    {
        if (! is_staff_member()) {
            header("HTTP/1.0 404 Not Found");
            echo _l('access_denied');
            die();
        }
        
        do_action('before_do_bulk_action_for_leads');
        $total_deleted = 0;
        if ($this->input->post()) {
            $ids = $this->input->post('ids');
            $status = $this->input->post('status');
            $source = $this->input->post('source');
            $assigned = $this->input->post('assigned');
            $visibility = $this->input->post('visibility');
            $tags = $this->input->post('tags');
            $last_contact = $this->input->post('last_contact');
            $is_admin = is_admin();
            if (is_array($ids)) {
                foreach ($ids as $id) {
                    if ($this->input->post('mass_delete')) {
                        if ($is_admin) {
                            if ($this->leads_model->delete($id)) {
                                $total_deleted ++;
                            }
                        }
                    } else {
                        if ($status || $source || $assigned || $last_contact || $visibility) {
                            $update = array();
                            if ($status) {
                                // We will use the same function to update the status
                                $this->leads_model->update_lead_status(array(
                                    'status' => $status,
                                    'leadid' => $id
                                ));
                            }
                            if ($source) {
                                $update['source'] = $source;
                            }
                            if ($assigned) {
                                $update['assigned'] = $assigned;
                            }
                            if ($last_contact) {
                                $last_contact = to_sql_date($last_contact, true);
                                $update['lastcontact'] = $last_contact;
                            }
                            
                            if ($visibility) {
                                if ($visibility == 'public') {
                                    $update['is_public'] = 1;
                                } else {
                                    $update['is_public'] = 0;
                                }
                            }
                            
                            if (count($update) > 0) {
                                $this->db->where('id', $id);
                                $this->db->update('tblleads', $update);
                            }
                        }
                        if ($tags) {
                            handle_tags_save($tags, $id, 'lead');
                        }
                    }
                }
            }
        }
        
        if ($this->input->post('mass_delete')) {
            set_alert('success', _l('total_leads_deleted', $total_deleted));
        }
    }
}
