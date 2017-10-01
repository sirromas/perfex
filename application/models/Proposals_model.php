<?php
defined('BASEPATH') or exit('No direct script access allowed');
class Proposals_model extends CRM_Model
{
    private $statuses;
    private $copy = false;

    public function __construct()
    {
        parent::__construct();
        $this->statuses = do_action('before_set_proposal_statuses', array(
            6,
            4,
            1,
            5,
            2,
            3
        ));
    }

    public function get_statuses()
    {
        return $this->statuses;
    }

    public function get_sale_agents()
    {
        return $this->db->query("SELECT DISTINCT(assigned) as sale_agent FROM tblproposals WHERE assigned != 0")->result_array();
    }

    public function get_proposals_years()
    {
        return $this->db->query('SELECT DISTINCT(YEAR(date)) as year FROM tblproposals')->result_array();
    }

    public function do_kanban_query($status, $search = '', $page = 1, $sort = array(), $count = false)
    {
        $default_pipeline_order      = get_option('default_proposals_pipeline_sort');
        $default_pipeline_order_type = get_option('default_proposals_pipeline_sort_type');
        $limit                       = get_option('proposals_pipeline_limit');

        $has_permission_view = has_permission('proposals', '', 'view');
        $has_permission_view_own = has_permission('proposals', '', 'view_own');
        $allow_staff_view_proposals_assigned = get_option('allow_staff_view_proposals_assigned');
        $staffId = get_staff_user_id();

        $this->db->select('id,invoice_id,estimate_id,subject,rel_type,rel_id,total,date,open_till,currency,proposal_to,status');
        $this->db->from('tblproposals');
        $this->db->where('status', $status);
        if (!$has_permission_view) {
            if ($has_permission_view_own) {
                $userWhere = '(addedfrom='.$staffId;
                if ($allow_staff_view_proposals_assigned == 1) {
                    $userWhere .= ' OR assigned='.$staffId;
                }
                $userWhere .= ')';
            } else {
                $userWhere .= 'assigned='.$staffId;
            }
            $this->db->where($userWhere);
        }
        if ($search != '') {
            if (!_startsWith($search, '#')) {
                $this->db->where('(
                phone LIKE "%' . $search . '%"
                OR
                zip LIKE "%' . $search . '%"
                OR
                content LIKE "%' . $search . '%"
                OR
                state LIKE "%' . $search . '%"
                OR
                city LIKE "%' . $search . '%"
                OR
                email LIKE "%' . $search . '%"
                OR
                address LIKE "%' . $search . '%"
                OR
                proposal_to LIKE "%' . $search . '%"
                OR
                total LIKE "%' . $search . '%"
                OR
                subject LIKE "%' . $search . '%")');
            } else {
                $this->db->where('tblproposals.id IN
                (SELECT rel_id FROM tbltags_in WHERE tag_id IN
                (SELECT id FROM tbltags WHERE name="' . strafter($search, '#') . '")
                AND tbltags_in.rel_type=\'proposal\' GROUP BY rel_id HAVING COUNT(tag_id) = 1)
                ');
            }
        }

        if (isset($sort['sort_by']) && $sort['sort_by'] && isset($sort['sort']) && $sort['sort']) {
            $this->db->order_by($sort['sort_by'], $sort['sort']);
        } else {
            $this->db->order_by($default_pipeline_order, $default_pipeline_order_type);
        }

        if($count == false){
            if ($page > 1) {
                $page--;
                $position = ($page * $limit);
                $this->db->limit($limit, $position);
            } else {
                $this->db->limit($limit);
            }
        }

        if ($count == false) {
            return $this->db->get()->result_array();
        } else {
            return $this->db->count_all_results();
        }
    }

    /**
     * Inserting new proposal function
     * @param mixed $data $_POST data
     */
    public function add($data)
    {
        $saveAndSend = false;
        if (isset($data['save_and_send'])) {
            $saveAndSend = true;
            unset($data['save_and_send']);
        }

        $unsetters = array(
            'currency_symbol',
            'price',
            'taxname',
            'unit',
            'description',
            'long_description',
            'taxid',
            'rate',
            'quantity',
            'item_select'
        );
        foreach ($unsetters as $unseter) {
            if (isset($data[$unseter])) {
                unset($data[$unseter]);
            }
        }

        if (isset($data['allow_comments'])) {
            $data['allow_comments'] = 1;
        } else {
            $data['allow_comments'] = 0;
        }
        if (isset($data['custom_fields'])) {
            $custom_fields = $data['custom_fields'];
            unset($data['custom_fields']);
        }

        if (isset($data['country']) && $data['country'] == '') {
            $data['country'] = 0;
        }

        $tags = '';
        if (isset($data['tags'])) {
            $tags  = $data['tags'];
            unset($data['tags']);
        }

        $data['datecreated'] = date('Y-m-d H:i:s');
        $data['addedfrom']   = get_staff_user_id();
        $data['date']        = to_sql_date($data['date']);
        $data['hash']        = md5(rand() . microtime());
        // Check if the key exists
        $this->db->where('hash', $data['hash']);
        $exists = $this->db->get('tblproposals')->row();
        if ($exists) {
            $data['hash'] = md5(rand() . microtime());
        }
        if (empty($data['rel_type'])) {
            unset($data['rel_type']);
            unset($data['rel_id']);
        } else {
            if (empty($data['rel_id'])) {
                unset($data['rel_type']);
                unset($data['rel_id']);
            }
        }
        if (!empty($data['open_till'])) {
            $data['open_till'] = to_sql_date($data['open_till']);
        } else {
            unset($data['open_till']);
        }

        $items = array();

        if (isset($data['newitems'])) {
            $items = $data['newitems'];
            unset($data['newitems']);
        }

        if ($data['discount_total'] == 0) {
            $data['discount_type'] = '';
        }

        if ((isset($data['adjustment']) && !is_numeric($data['adjustment'])) || !isset($data['adjustment'])) {
            $data['adjustment'] = 0;
        } elseif (isset($data['adjustment']) && is_numeric($data['adjustment'])) {
            $data['adjustment'] = number_format($data['adjustment'], get_decimal_places(), '.', '');
        }

        if ($this->copy == false) {
            $data['content'] = '{proposal_items}';
        }
        $data = do_action('before_create_proposal',$data);
        $this->db->insert('tblproposals', $data);
        $insert_id = $this->db->insert_id();
        if ($insert_id) {
            if (isset($custom_fields)) {
                handle_custom_fields_post($insert_id, $custom_fields);
            }

            handle_tags_save($tags, $insert_id, 'proposal');

            if (count($items) > 0) {
                foreach ($items as $key => $item) {
                    $this->db->insert('tblitems_in', array(
                        'description' => $item['description'],
                        'long_description' => nl2br($item['long_description']),
                        'qty' => $item['qty'],
                        'rate' => number_format($item['rate'], get_decimal_places(), '.', ''),
                        'rel_id' => $insert_id,
                        'rel_type' => 'proposal',
                        'item_order' => $item['order'],
                        'unit' => $item['unit']

                    ));
                    $itemid = $this->db->insert_id();
                    if ($itemid) {
                        if (isset($item['taxname']) && is_array($item['taxname'])) {
                            foreach ($item['taxname'] as $taxname) {
                                if ($taxname != '') {
                                    $tax_array    = explode('|', $taxname);
                                    if (isset($tax_array[0]) && isset($tax_array[1])) {
                                        $tax_name = trim($tax_array[0]);
                                        $tax_rate = trim($tax_array[1]);
                                        if (total_rows('tblitemstax', array('itemid'=>$itemid, 'taxrate'=>$tax_rate, 'taxname'=>$tax_name, 'rel_id'=>$insert_id, 'rel_type'=>'proposal')) == 0) {
                                            $this->db->insert('tblitemstax', array(
                                                'itemid' => $itemid,
                                                'taxrate' => $tax_rate,
                                                'taxname' => $tax_name,
                                                'rel_id' => $insert_id,
                                                'rel_type' => 'proposal'
                                            ));
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
            $proposal = $this->get($insert_id);
            if ($proposal->assigned != 0) {
                if ($proposal->assigned != get_staff_user_id()) {
                    $notified = add_notification(array(
                        'description' => 'not_proposal_assigned_to_you',
                        'touserid' => $proposal->assigned,
                        'fromuserid' => get_staff_user_id(),
                        'link' => 'proposals/list_proposals/' . $insert_id,
                        'additional_data' => serialize(array(
                            $proposal->subject
                        ))
                    ));
                    if ($notified) {
                        pusher_trigger_notification(array($proposal->assigned));
                    }
                }
            }
            if ($data['rel_type'] == 'lead') {
                $this->load->model('leads_model');
                $this->leads_model->log_lead_activity($data['rel_id'], 'not_lead_activity_created_proposal', false, serialize(array(
                    '<a href="' . admin_url('proposals/list_proposals/' . $insert_id) . '" target="_blank">' . $data['subject'] . '</a>'
                )));
            }
            $this->update_total_tax($insert_id);
            logActivity('New Proposal Created [ID:' . $insert_id . ']');

            if($saveAndSend === true){
                $this->send_proposal_to_email($insert_id, 'proposal-send-to-customer', true);
            }

            do_action('proposal_created',$insert_id);
            return $insert_id;
        }

        return false;
    }

    /**
     * Update proposal
     * @param  mixed $data $_POST data
     * @param  mixed $id   proposal id
     * @return boolean
     */
    public function update($data, $id)
    {
        $saveAndSend = false;
        if (isset($data['save_and_send'])) {
            $saveAndSend = true;
            unset($data['save_and_send']);
        }

        $unsetters = array(
            'currency_symbol',
            'price',
            'taxname',
            'unit',
            'taxid',
            'isedit',
            'description',
            'long_description',
            'tax',
            'rate',
            'quantity',
            'item_select'
        );

        foreach ($unsetters as $u) {
            if (isset($data[$u])) {
                unset($data[$u]);
            }
        }

        if ($data['country'] == '') {
            $data['country'] = 0;
        }


        $affectedRows     = 0;
        $current_proposal = $this->get($id);
        if (empty($data['rel_type'])) {
            $data['rel_id']   = null;
            $data['rel_type'] = '';
        } else {
            if (empty($data['rel_id'])) {
                $data['rel_id']   = null;
                $data['rel_type'] = '';
            }
        }
        if (isset($data['allow_comments'])) {
            $data['allow_comments'] = 1;
        } else {
            $data['allow_comments'] = 0;
        }
        $data['date'] = to_sql_date($this->input->post('date'));
        if (!empty($data['open_till'])) {
            $data['open_till'] = to_sql_date($data['open_till']);
        } else {
            $data['open_till'] = null;
        }
        if (isset($data['custom_fields'])) {
            $custom_fields = $data['custom_fields'];
            if (handle_custom_fields_post($id, $custom_fields)) {
                $affectedRows++;
            }
            unset($data['custom_fields']);
        }


        $items = array();
        if (isset($data['items'])) {
            $items = $data['items'];
            unset($data['items']);
        }
        $newitems = array();
        if (isset($data['newitems'])) {
            $newitems = $data['newitems'];
            unset($data['newitems']);
        }
        if ((isset($data['adjustment']) && !is_numeric($data['adjustment'])) || !isset($data['adjustment'])) {
            $data['adjustment'] = 0;
        } elseif (isset($data['adjustment']) && is_numeric($data['adjustment'])) {
            $data['adjustment'] = number_format($data['adjustment'], get_decimal_places(), '.', '');
        }

        if ($data['discount_total'] == 0) {
            $data['discount_type'] = '';
        }

        // Delete items checked to be removed from database
        if (isset($data['removed_items'])) {
            foreach ($data['removed_items'] as $remove_item_id) {
                $this->db->where('id', $remove_item_id);
                $this->db->delete('tblitems_in');
                if ($this->db->affected_rows() > 0) {
                    $affectedRows++;
                    $this->db->where('itemid', $remove_item_id);
                    $this->db->where('rel_type', 'proposal');
                    $this->db->delete('tblitemstax');
                }
            }
            unset($data['removed_items']);
        }


        if (isset($data['tags'])) {
            if (handle_tags_save($data['tags'], $id, 'proposal')) {
                $affectedRows++;
            }
            unset($data['tags']);
        }

        $this->db->where('id', $id);
        $this->db->update('tblproposals', $data);
        if ($this->db->affected_rows() > 0) {
            $affectedRows++;
            $proposal_now = $this->get($id);
            if ($current_proposal->assigned != $proposal_now->assigned) {
                if ($proposal_now->assigned != get_staff_user_id()) {
                    $notified = add_notification(array(
                        'description' => 'not_proposal_assigned_to_you',
                        'touserid' => $proposal_now->assigned,
                        'fromuserid' => get_staff_user_id(),
                        'link' => 'proposals/list_proposals/' . $id,
                        'additional_data' => serialize(array(
                            $proposal_now->subject
                        ))
                    ));
                    if ($notified) {
                        pusher_trigger_notification(array($proposal_now->assigned));
                    }
                }
            }
        }


        if (count($items) > 0) {
            foreach ($items as $key => $item) {
                $estimate_item_id = $item['itemid'];
                $this->db->where('id', $estimate_item_id);
                $this->db->update('tblitems_in', array(
                    'item_order' => $item['order'],
                    'description' => $item['description'],
                    'long_description' => nl2br($item['long_description']),
                    'rate' => number_format($item['rate'], get_decimal_places(), '.', ''),
                    'qty' => $item['qty'],
                    'unit' => $item['unit']
                ));
                if ($this->db->affected_rows() > 0) {
                    $affectedRows++;
                }

                if (!isset($item['taxname']) || (isset($item['taxname']) && count($item['taxname']) == 0)) {
                    $this->db->where('itemid', $estimate_item_id);
                    $this->db->where('rel_type', 'proposal');
                    $this->db->delete('tblitemstax');
                } else {
                    $item_taxes        = get_proposal_item_taxes($estimate_item_id);
                    $_item_taxes_names = array();
                    foreach ($item_taxes as $_item_tax) {
                        array_push($_item_taxes_names, $_item_tax['taxname']);
                    }
                    $i = 0;
                    foreach ($_item_taxes_names as $_item_tax) {
                        if (!in_array($_item_tax, $item['taxname'])) {
                            $this->db->where('id', $item_taxes[$i]['id']);
                            $this->db->delete('tblitemstax');
                            if ($this->db->affected_rows() > 0) {
                                $affectedRows++;
                            }
                        }
                        $i++;
                    }
                    if (isset($item['taxname']) && is_array($item['taxname'])) {
                        foreach ($item['taxname'] as $taxname) {
                            if ($taxname != '') {
                                $tax_array    = explode('|', $taxname);
                                $tax_name = trim($tax_array[0]);
                                $tax_rate = trim($tax_array[1]);
                                if (total_rows('tblitemstax', array(
                                    'taxname' => $tax_name,
                                    'itemid' => $estimate_item_id,
                                    'taxrate' => $tax_rate,
                                    'rel_type' => 'proposal',
                                    'rel_id'=>$id
                                    )) == 0) {
                                    $this->db->insert('tblitemstax', array(
                                        'taxrate' => $tax_rate,
                                        'taxname' => $tax_name,
                                        'itemid' => $estimate_item_id,
                                        'rel_id' => $id,
                                        'rel_type' => 'proposal'
                                        ));
                                    if ($this->db->affected_rows() > 0) {
                                        $affectedRows++;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }


        if (count($newitems) > 0) {
            foreach ($newitems as $key => $item) {
                $this->db->insert('tblitems_in', array(
                    'description' => $item['description'],
                    'long_description' => nl2br($item['long_description']),
                    'qty' => $item['qty'],
                    'rate' => number_format($item['rate'], get_decimal_places(), '.', ''),
                    'rel_id' => $id,
                    'rel_type' => 'proposal',
                    'item_order' => $item['order'],
                    'unit' => $item['unit']
                ));
                $new_item_added = $this->db->insert_id();
                if ($new_item_added) {
                    if (isset($item['taxname']) && is_array($item['taxname'])) {
                        foreach ($item['taxname'] as $taxname) {
                            if ($taxname != '') {
                                $tax_array    = explode('|', $taxname);
                                if (isset($tax_array[0]) && isset($tax_array[1])) {
                                    $tax_name = trim($tax_array[0]);
                                    $tax_rate = trim($tax_array[1]);
                                    if (total_rows('tblitemstax', array(
                                        'taxname' => $tax_name,
                                        'itemid' => $new_item_added,
                                        'taxrate' => $tax_rate,
                                        'rel_type' => 'proposal',
                                        'rel_id'=>$id
                                        )) == 0) {
                                        $this->db->insert('tblitemstax', array(
                                            'taxrate' => $tax_rate,
                                            'taxname' => $tax_name,
                                            'itemid' => $new_item_added,
                                            'rel_id' => $id,
                                            'rel_type' => 'proposal'
                                            ));
                                        if($this->db->affected_rows() > 0){
                                            $affectedRows++;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        if ($affectedRows > 0) {
            $this->update_total_tax($id);
        }

        if($saveAndSend === true){
            $this->send_proposal_to_email($id, 'proposal-send-to-customer', true);
        }

        if($affectedRows > 0){
            logActivity('Proposal Updated [ID:' . $id . ']');
            return true;
        }

        return false;
    }

    /**
     * Get proposals
     * @param  mixed $id proposal id OPTIONAL
     * @return mixed
     */
    public function get($id = '', $where = array(), $for_editor = false)
    {
        $this->db->where($where);

        if (is_client_logged_in()) {
            $this->db->where('status !=', 0);
        }

        $this->db->select('*,tblcurrencies.id as currencyid, tblproposals.id as id, tblcurrencies.name as currency_name');
        $this->db->from('tblproposals');
        $this->db->join('tblcurrencies', 'tblcurrencies.id = tblproposals.currency', 'left');

        if (is_numeric($id)) {
            $this->db->where('tblproposals.id', $id);
            $proposal = $this->db->get()->row();
            if ($proposal) {
                $proposal->attachments                           = $this->get_attachments($id);
                $proposal->items                                 = $this->get_proposal_items($id);
                $proposal->visible_attachments_to_customer_found = false;
                foreach ($proposal->attachments as $attachment) {
                    if ($attachment['visible_to_customer'] == 1) {
                        $proposal->visible_attachments_to_customer_found = true;
                        break;
                    }
                }
                if ($for_editor == false) {
                    $merge_fields = array();
                    $merge_fields = array_merge($merge_fields, get_proposal_merge_fields($id));
                    $merge_fields = array_merge($merge_fields, get_other_merge_fields());
                    foreach ($merge_fields as $key => $val) {
                        if (stripos($proposal->content, $key) !== false) {
                            $proposal->content = str_ireplace($key, $val, $proposal->content);
                        } else {
                            $proposal->content = str_ireplace($key, '', $proposal->content);
                        }
                    }
                }
            }

            return $proposal;
        }

        return $this->db->get()->result_array();
    }

    public function update_total_tax($id)
    {
        $total_tax         = 0;
        $taxes             = array();
        $_calculated_taxes = array();
        $proposal          = $this->get($id);
        foreach ($proposal->items as $item) {
            $item_taxes = get_proposal_item_taxes($item['id']);
            if (count($item_taxes) > 0) {
                foreach ($item_taxes as $tax) {
                    $calc_tax     = 0;
                    $tax_not_calc = false;
                    if (!in_array($tax['taxname'], $_calculated_taxes)) {
                        array_push($_calculated_taxes, $tax['taxname']);
                        $tax_not_calc = true;
                    }
                    if ($tax_not_calc == true) {
                        $taxes[$tax['taxname']]          = array();
                        $taxes[$tax['taxname']]['total'] = array();
                        array_push($taxes[$tax['taxname']]['total'], (($item['qty'] * $item['rate']) / 100 * $tax['taxrate']));
                        $taxes[$tax['taxname']]['tax_name'] = $tax['taxname'];
                        $taxes[$tax['taxname']]['taxrate']  = $tax['taxrate'];
                    } else {
                        array_push($taxes[$tax['taxname']]['total'], (($item['qty'] * $item['rate']) / 100 * $tax['taxrate']));
                    }
                }
            }
        }
        foreach ($taxes as $tax) {
            $total = array_sum($tax['total']);
            if ($proposal->discount_percent != 0 && $proposal->discount_type == 'before_tax') {
                $total_tax_calculated = ($total * $proposal->discount_percent) / 100;
                $total                = ($total - $total_tax_calculated);
            }
            $total_tax += $total;
        }
        $this->db->where('id', $id);
        $this->db->update('tblproposals', array(
            'total_tax' => $total_tax
        ));
    }

    /**
     * Get all estimate items
     * @param  mixed $id estimateid
     * @return array
     */
    public function get_proposal_items($id)
    {
        $this->db->select();
        $this->db->from('tblitems_in');
        $this->db->where('rel_id', $id);
        $this->db->where('rel_type', 'proposal');
        $this->db->order_by('item_order', 'asc');

        return $this->db->get()->result_array();
    }

    public function update_pipeline($data)
    {
        $this->mark_action_status($data['status'], $data['proposalid']);
        foreach ($data['order'] as $order_data) {
            $this->db->where('id', $order_data[0]);
            $this->db->update('tblproposals', array(
                'pipeline_order' => $order_data[1]
            ));
        }
    }

    public function get_attachments($proposal_id, $id = '')
    {
        // If is passed id get return only 1 attachment
        if (is_numeric($id)) {
            $this->db->where('id', $id);
        } else {
            $this->db->where('rel_id', $proposal_id);
        }
        $this->db->where('rel_type', 'proposal');
        $result = $this->db->get('tblfiles');
        if (is_numeric($id)) {
            return $result->row();
        } else {
            return $result->result_array();
        }
    }

    /**
     *  Delete proposal attachment
     * @param   mixed $id  attachmentid
     * @return  boolean
     */
    public function delete_attachment($id)
    {
        $attachment = $this->get_attachments('', $id);
        $deleted    = false;
        if ($attachment) {
            if (empty($attachment->external)) {
                unlink(get_upload_path_by_type('proposal') . $attachment->rel_id . '/' . $attachment->file_name);
            }
            $this->db->where('id', $attachment->id);
            $this->db->delete('tblfiles');
            if ($this->db->affected_rows() > 0) {
                $deleted = true;
                logActivity('Proposal Attachment Deleted [ProposalID: ' . $attachment->rel_id . ']');
            }
            if (is_dir(get_upload_path_by_type('proposal') . $attachment->rel_id)) {
                // Check if no attachments left, so we can delete the folder also
                $other_attachments = list_files(get_upload_path_by_type('proposal') . $attachment->rel_id);
                if (count($other_attachments) == 0) {
                    // okey only index.html so we can delete the folder also
                    delete_dir(get_upload_path_by_type('proposal') . $attachment->rel_id);
                }
            }
        }

        return $deleted;
    }

    /**
     * Add proposal comment
     * @param mixed  $data   $_POST comment data
     * @param boolean $client is request coming from the client side
     */
    public function add_comment($data, $client = false)
    {
        if (is_staff_logged_in()) {
            $client = false;
        }

        if (isset($data['action'])) {
            unset($data['action']);
        }
        $data['dateadded'] = date('Y-m-d H:i:s');
        if ($client == false) {
            $data['staffid'] = get_staff_user_id();
        }
        $data['content'] = nl2br($data['content']);
        $this->db->insert('tblproposalcomments', $data);
        $insert_id = $this->db->insert_id();
        if ($insert_id) {
            $proposal = $this->get($data['proposalid']);

            $merge_fields = array();
            $merge_fields = array_merge($merge_fields, get_proposal_merge_fields($proposal->id));

            $this->load->model('emails_model');

            $this->emails_model->set_rel_id($data['proposalid']);
            $this->emails_model->set_rel_type('proposal');

            if ($client == true) {
                // Get creator and assigned;
                $this->db->where('staffid', $proposal->addedfrom);
                $this->db->or_where('staffid', $proposal->assigned);
                $staff_proposal = $this->db->get('tblstaff')->result_array();
                $notifiedUsers = array();
                foreach ($staff_proposal as $member) {
                    $notified = add_notification(array(
                        'description' => 'not_proposal_comment_from_client',
                        'touserid' => $member['staffid'],
                        'fromcompany' => 1,
                        'fromuserid' => null,
                        'link' => 'proposals/list_proposals/' . $data['proposalid'],
                        'additional_data' => serialize(array(
                            $proposal->subject
                        ))
                    ));
                    if ($notified) {
                        array_push($notifiedUsers, $member['staffid']);
                    }
                    // Send email to admin that client commented
                    $this->emails_model->send_email_template('proposal-comment-to-admin', $member['email'], $merge_fields);
                }
                pusher_trigger_notification($notifiedUsers);
            } else {
                // Send email to client that admin commented
                $this->emails_model->send_email_template('proposal-comment-to-client', $proposal->email, $merge_fields);
            }

            return true;
        }

        return false;
    }

    public function edit_comment($data, $id)
    {
        $this->db->where('id', $id);
        $this->db->update('tblproposalcomments', array(
            'content' => nl2br($data['content'])
        ));
        if ($this->db->affected_rows() > 0) {
            return true;
        }

        return false;
    }

    /**
     * Get proposal comments
     * @param  mixed $id proposal id
     * @return array
     */
    public function get_comments($id)
    {
        $this->db->where('proposalid', $id);
        $this->db->order_by('dateadded', 'ASC');

        return $this->db->get('tblproposalcomments')->result_array();
    }

    /**
     * Get proposal single comment
     * @param  mixed $id  comment id
     * @return object
     */
    public function get_comment($id)
    {
        $this->db->where('id', $id);

        return $this->db->get('tblproposalcomments')->row();
    }

    /**
     * Remove proposal comment
     * @param  mixed $id comment id
     * @return boolean
     */
    public function remove_comment($id)
    {
        $comment = $this->get_comment($id);
        $this->db->where('id', $id);
        $this->db->delete('tblproposalcomments');
        if ($this->db->affected_rows() > 0) {
            logActivity('Proposal Comment Removed [ProposalID:' . $comment->proposalid . ', Comment Content: ' . $comment->content . ']');

            return true;
        }

        return false;
    }

    /**
     * Copy proposal
     * @param  mixed $id proposal id
     * @return mixed
     */
    public function copy($id)
    {
        $this->copy = true;
        $proposal        = $this->get($id, array(), true);
        $not_copy_fields = array(
            'addedfrom',
            'id',
            'datecreated',
            'hash',
            'status',
            'invoice_id',
            'estimate_id',
            'is_expiry_notified',
            'date_converted'
        );
        $fields          = $this->db->list_fields('tblproposals');
        $insert_data     = array();
        foreach ($fields as $field) {
            if (!in_array($field, $not_copy_fields)) {
                $insert_data[$field] = $proposal->$field;
            }
        }

        $insert_data['addedfrom']   = get_staff_user_id();
        $insert_data['datecreated'] = date('Y-m-d H:i:s');
        $insert_data['date']        = _d(date('Y-m-d'));
        $insert_data['status']      = 6;
        $insert_data['hash']        = md5(rand() . microtime());
        // Check if the key exists
        $this->db->where('hash', $insert_data['hash']);
        $exists = $this->db->get('tblproposals')->row();
        if ($exists) {
            $insert_data['hash'] = md5(rand() . microtime());
        }

        // in case open till is expired set new 7 days starting from current date
        if ($insert_data['open_till'] && get_option('proposal_due_after') != 0) {
            $insert_data['open_till'] = _d(date('Y-m-d', strtotime('+'.get_option('proposal_due_after').' DAY', strtotime(date('Y-m-d')))));
        }

        $insert_data['newitems'] = array();
        $key                     = 1;
        foreach ($proposal->items as $item) {
            $insert_data['newitems'][$key]['description']      = $item['description'];
            $insert_data['newitems'][$key]['long_description'] = clear_textarea_breaks($item['long_description']);
            $insert_data['newitems'][$key]['qty']              = $item['qty'];
            $insert_data['newitems'][$key]['unit']             = $item['unit'];
            $insert_data['newitems'][$key]['taxname']          = array();
            $taxes                                             = get_proposal_item_taxes($item['id']);
            foreach ($taxes as $tax) {
                // tax name is in format TAX1|10.00
                array_push($insert_data['newitems'][$key]['taxname'], $tax['taxname']);
            }
            $insert_data['newitems'][$key]['rate']  = $item['rate'];
            $insert_data['newitems'][$key]['order'] = $item['item_order'];
            $key++;
        }

        $id = $this->add($insert_data);

        if ($id) {
            $custom_fields = get_custom_fields('proposal');
            foreach ($custom_fields as $field) {
                $value = get_custom_field_value($proposal->id, $field['id'], 'proposal');
                if ($value == '') {
                    continue;
                }
                $this->db->insert('tblcustomfieldsvalues', array(
                    'relid' => $id,
                    'fieldid' => $field['id'],
                    'fieldto' => 'proposal',
                    'value' => $value
                ));
            }

            $tags = get_tags_in($proposal->id, 'proposal');
            handle_tags_save($tags, $id, 'proposal');

            logActivity('Copied Proposal ' . format_proposal_number($proposal->id));

            return $id;
        }

        return false;
    }

    /**
     * Take proposal action (change status) manually
     * @param  mixed $status status id
     * @param  mixed  $id     proposal id
     * @param  boolean $client is request coming from client side or not
     * @return boolean
     */
    public function mark_action_status($status, $id, $client = false)
    {
        $original_proposal = $this->get($id);
        $this->db->where('id', $id);
        $this->db->update('tblproposals', array(
            'status' => $status
        ));

        if ($this->db->affected_rows() > 0) {
            // Client take action
            if ($client == true) {
                $revert = false;
                // Declined
                if ($status == 2) {
                    $message = 'not_proposal_proposal_declined';
                } elseif ($status == 3) {
                    $message = 'not_proposal_proposal_accepted';
                    // Accepted
                } else {
                    $revert = true;
                }
                // This is protection that only 3 and 4 statuses can be taken as action from the client side
                if ($revert == true) {
                    $this->db->where('id', $id);
                    $this->db->update('tblproposals', array(
                        'status' => $original_proposal->status
                    ));

                    return false;
                } else {
                    $merge_fields = array();
                    $merge_fields = array_merge($merge_fields, get_proposal_merge_fields($original_proposal->id));

                    // Get creator and assigned;
                    $this->db->where('staffid', $original_proposal->addedfrom);
                    $this->db->or_where('staffid', $original_proposal->assigned);
                    $staff_proposal = $this->db->get('tblstaff')->result_array();
                    $notifiedUsers = array();
                    foreach ($staff_proposal as $member) {
                        $notified = add_notification(array(
                            'fromcompany' => true,
                            'touserid' => $member['staffid'],
                            'description' => $message,
                            'link' => 'proposals/list_proposals/' . $id
                        ));
                        if ($notified) {
                            array_push($notifiedUsers, $member['staffid']);
                        }
                    }

                    pusher_trigger_notification($notifiedUsers);

                    $this->load->model('emails_model');

                    $this->emails_model->set_rel_id($id);
                    $this->emails_model->set_rel_type('proposal');

                    // Send thank you to the customer email template
                    if ($status == 3) {
                        foreach ($staff_proposal as $member) {
                            $this->emails_model->send_email_template('proposal-client-accepted', $member['email'], $merge_fields);
                        }
                        $this->emails_model->send_email_template('proposal-client-thank-you', $original_proposal->email, $merge_fields);
                    } else {
                        // Client declined send template to admin
                        foreach ($staff_proposal as $member) {
                            $this->emails_model->send_email_template('proposal-client-declined', $member['email'], $merge_fields);
                        }
                    }
                }
            } else {
                // in case admin mark as open the the open till date is smaller then current date set open till date 7 days more
                if ((date('Y-m-d', strtotime($original_proposal->open_till)) < date('Y-m-d')) && $status == 1) {
                    $open_till = date('Y-m-d', strtotime('+7 DAY', strtotime(date('Y-m-d'))));
                    $this->db->where('id', $id);
                    $this->db->update('tblproposals', array(
                        'open_till' => $open_till
                    ));
                }
            }
            logActivity('Proposal Status Changes [ProposalID:' . $id . ', Status:' . format_proposal_status($status, '', false) . ',Client Action: ' . (int) $client . ']');

            return true;
        }

        return false;
    }

    /**
     * Delete proposal
     * @param  mixed $id proposal id
     * @return boolean
     */
    public function delete($id)
    {
        $this->db->where('id', $id);
        $this->db->delete('tblproposals');
        if ($this->db->affected_rows() > 0) {
            $this->db->where('proposalid', $id);
            $this->db->delete('tblproposalcomments');
            // Get related tasks
            $this->db->where('rel_type', 'proposal');
            $this->db->where('rel_id', $id);

            $tasks = $this->db->get('tblstafftasks')->result_array();
            foreach ($tasks as $task) {
                $this->tasks_model->delete_task($task['id']);
            }

            $attachments = $this->get_attachments($id);
            foreach ($attachments as $attachment) {
                $this->delete_attachment($attachment['id']);
            }

            $this->db->where('rel_id', $id);
            $this->db->where('rel_type', 'proposal');
            $this->db->delete('tblitems_in');


            $this->db->where('rel_id', $id);
            $this->db->where('rel_type', 'proposal');
            $this->db->delete('tblitemstax');

            $this->db->where('rel_id', $id);
            $this->db->where('rel_type', 'proposal');
            $this->db->delete('tbltags_in');

            // Delete the custom field values
            $this->db->where('relid', $id);
            $this->db->where('fieldto', 'proposal');
            $this->db->delete('tblcustomfieldsvalues');

            $this->db->where('rel_type', 'proposal');
            $this->db->where('rel_id', $id);
            $this->db->delete('tblreminders');

            $this->db->where('rel_type', 'proposal');
            $this->db->where('rel_id', $id);
            $this->db->delete('tblviewstracking');

            logActivity('Proposal Deleted [ProposalID:' . $id . ']');

            return true;
        }

        return false;
    }

    /**
     * Get relation proposal data. Ex lead or customer will return the necesary db fields
     * @param  mixed $rel_id
     * @param  string $rel_type customer/lead
     * @return object
     */
    public function get_relation_data_values($rel_id, $rel_type)
    {
        $data = new StdClass();
        if ($rel_type == 'customer') {
            $this->db->where('userid', $rel_id);
            $_data = $this->db->get('tblclients')->row();

            $primary_contact_id = get_primary_contact_user_id($rel_id);
            if ($primary_contact_id) {
                $contact     = $this->clients_model->get_contact($primary_contact_id);
                $data->email = $contact->email;
            }

            $data->phone = $_data->phonenumber;
            if (!empty($_data->company)) {
                $data->to = $_data->company;
            } else {
                $data->to = $contact->firstname . ' ' . $contact->lastname;
            }

            $data->address = $_data->address;
            $data->zip     = $_data->zip;
            $data->country = $_data->country;
            $data->state   = $_data->state;
            $data->city    = $_data->city;

            $default_currency = $this->clients_model->get_customer_default_currency($rel_id);
            if ($default_currency != 0) {
                $data->currency = $default_currency;
            }
        } elseif ($rel_type = 'lead') {
            $this->db->where('id', $rel_id);
            $_data       = $this->db->get('tblleads')->row();
            $data->phone = $_data->phonenumber;

            if (empty($_data->company)) {
                $data->to = $_data->name;
            } else {
                $data->to = $_data->company;
            }

            $data->address = $_data->address;
            $data->email   = $_data->email;
            $data->zip     = $_data->zip;
            $data->country = $_data->country;
            $data->state   = $_data->state;
            $data->city    = $_data->city;
        }

        return $data;
    }

    /**
     * Sent proposal to email
     * @param  mixed  $id        proposalid
     * @param  string  $template  email template to sent
     * @param  boolean $attachpdf attach proposal pdf or not
     * @return boolean
     */
    public function send_expiry_reminder($id)
    {
        $proposal = $this->get($id);
        $pdf      = proposal_pdf($proposal);
        $attach   = $pdf->Output(slug_it($proposal->subject) . '.pdf', 'S');
        $this->load->model('emails_model');

        $this->emails_model->set_rel_id($id);
        $this->emails_model->set_rel_type('proposal');

        $this->emails_model->add_attachment(array(
            'attachment' => $attach,
            'filename' => slug_it($proposal->subject) . '.pdf',
            'type' => 'application/pdf'
        ));

        $merge_fields = array();
        $merge_fields = array_merge($merge_fields, get_proposal_merge_fields($proposal->id));
        $this->emails_model->send_email_template('proposal-expiry-reminder', $proposal->email, $merge_fields);

        $this->db->where('id', $id);
        $this->db->update('tblproposals', array(
            'is_expiry_notified' => 1
            ));

        return true;
    }

    public function send_proposal_to_email($id, $template = '', $attachpdf = true, $cc = '')
    {
        $this->load->model('emails_model');

        $this->emails_model->set_rel_id($id);
        $this->emails_model->set_rel_type('proposal');

        $proposal = $this->get($id);

        // Proposal status is draft update to sent
        if ($proposal->status == 6) {
            $this->db->where('id', $id);
            $this->db->update('tblproposals', array('status'=>4));
            $proposal = $this->get($id);
        }

        if ($attachpdf) {
            $pdf    = proposal_pdf($proposal);
            $attach = $pdf->Output(slug_it($proposal->subject) . '.pdf', 'S');
            $this->emails_model->add_attachment(array(
                'attachment' => $attach,
                'filename' => slug_it($proposal->subject) . '.pdf',
                'type' => 'application/pdf'
            ));
        }

        if ($this->input->post('email_attachments')) {
            $_other_attachments = $this->input->post('email_attachments');
            foreach ($_other_attachments as $attachment) {
                $_attachment = $this->get_attachments($id, $attachment);
                $this->emails_model->add_attachment(array(
                    'attachment' => get_upload_path_by_type('proposal') . $id . '/' . $_attachment->file_name,
                    'filename' => $_attachment->file_name,
                    'type' => $_attachment->filetype,
                    'read' => true
                ));
            }
        }

        $merge_fields = array();
        $merge_fields = array_merge($merge_fields, get_proposal_merge_fields($proposal->id));
        $sent         = $this->emails_model->send_email_template($template, $proposal->email, $merge_fields, '', $cc);
        if ($sent) {
            // Set to status sent
            $this->db->where('id', $id);
            $this->db->update('tblproposals', array(
                'status' => 4
            ));

            return true;
        }

        return false;
    }
}
