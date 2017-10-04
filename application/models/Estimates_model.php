<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Estimates_model extends CRM_Model
{

    private $shipping_fields = array(
        'shipping_street',
        'shipping_city',
        'shipping_city',
        'shipping_state',
        'shipping_zip',
        'shipping_country'
    );

    private $statuses;

    public function __construct()
    {
        parent::__construct();
        $this->statuses = do_action('before_set_estimate_statuses', array(
            1,
            2,
            5,
            3,
            4
        ));
    }

    /**
     * Get unique sale agent for estimates / Used for filters
     * 
     * @return array
     */
    public function get_sale_agents()
    {
        return $this->db->query("SELECT DISTINCT(sale_agent) as sale_agent FROM tblestimates WHERE sale_agent != 0")->result_array();
    }

    /**
     * Get estimate/s
     * 
     * @param mixed $id
     *            estimate id
     * @param array $where
     *            perform where
     * @return mixed
     */
    public function get($id = '', $where = array())
    {
        $this->db->select('*,tblcurrencies.id as currencyid, tblestimates.id as id, tblcurrencies.name as currency_name');
        $this->db->from('tblestimates');
        $this->db->join('tblcurrencies', 'tblcurrencies.id = tblestimates.currency', 'left');
        $this->db->where($where);
        if (is_numeric($id)) {
            $this->db->where('tblestimates.id', $id);
            $estimate = $this->db->get()->row();
            if ($estimate) {
                $estimate->attachments = $this->get_attachments($id);
                $estimate->visible_attachments_to_customer_found = false;
                foreach ($estimate->attachments as $attachment) {
                    if ($attachment['visible_to_customer'] == 1) {
                        $estimate->visible_attachments_to_customer_found = true;
                        break;
                    }
                }
                $estimate->items = $this->get_estimate_items($id);
                if ($estimate->project_id != 0) {
                    $this->load->model('projects_model');
                    $estimate->project_data = $this->projects_model->get($estimate->project_id);
                }
                $estimate->client = $this->clients_model->get($estimate->clientid);
            }
            
            return $estimate;
        }
        $this->db->order_by('number,YEAR(date)', 'desc');
        
        return $this->db->get()->result_array();
    }

    /**
     * Get estimate statuses
     * 
     * @return array
     */
    public function get_statuses()
    {
        return $this->statuses;
    }

    /**
     * Function that will perform estimates pipeline query
     * 
     * @param mixed $status            
     * @param string $search            
     * @param integer $page            
     * @param array $sort            
     * @param boolean $count            
     * @return array
     */
    public function do_kanban_query($status, $search = '', $page = 1, $sort = array(), $count = false)
    {
        $default_pipeline_order = get_option('default_estimates_pipeline_sort');
        $default_pipeline_order_type = get_option('default_estimates_pipeline_sort_type');
        $limit = get_option('estimates_pipeline_limit');
        
        $fields_client = $this->db->list_fields('tblclients');
        $fields_estimates = $this->db->list_fields('tblestimates');
        
        $has_permission_view = has_permission('estimates', '', 'view');
        
        $this->db->select('tblestimates.id,status,invoiceid,CASE company WHEN "" THEN (SELECT CONCAT(firstname, " ", lastname) FROM tblcontacts WHERE userid = tblclients.userid and is_primary = 1) ELSE company END as company,total,currency,symbol,date,expirydate,clientid');
        $this->db->from('tblestimates');
        $this->db->join('tblclients', 'tblclients.userid = tblestimates.clientid');
        $this->db->join('tblcurrencies', 'tblestimates.currency = tblcurrencies.id');
        $this->db->where('status', $status);
        if (! $has_permission_view) {
            $this->db->where('addedfrom', get_staff_user_id());
        }
        
        if ($search != '') {
            if (! _startsWith($search, '#')) {
                $where = '(';
                $i = 0;
                foreach ($fields_client as $f) {
                    $where .= 'tblclients.' . $f . ' LIKE "%' . $search . '%"';
                    $where .= ' OR ';
                    $i ++;
                }
                $i = 0;
                foreach ($fields_estimates as $f) {
                    $where .= 'tblestimates.' . $f . ' LIKE "%' . $search . '%"';
                    $where .= ' OR ';
                    
                    $i ++;
                }
                $where = substr($where, 0, - 4);
                $where .= ')';
                $this->db->where($where);
            } else {
                $this->db->where('tblestimates.id IN
                (SELECT rel_id FROM tbltags_in WHERE tag_id IN
                (SELECT id FROM tbltags WHERE name="' . strafter($search, '#') . '")
                AND tbltags_in.rel_type=\'estimate\' GROUP BY rel_id HAVING COUNT(tag_id) = 1)
                ');
            }
        }
        
        if (isset($sort['sort_by']) && $sort['sort_by'] && isset($sort['sort']) && $sort['sort']) {
            $this->db->order_by('tblestimates.' . $sort['sort_by'], $sort['sort']);
        } else {
            $this->db->order_by('tblestimates.' . $default_pipeline_order, $default_pipeline_order_type);
        }
        
        if ($count == false) {
            if ($page > 1) {
                $page --;
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
     * Convert estimate to invoice
     * 
     * @param mixed $id
     *            estimate id
     * @return mixed New invoice ID
     */
    public function convert_to_invoice($id, $client = false, $draft_invoice = false)
    {
        // Recurring invoice date is okey lets convert it to new invoice
        $_estimate = $this->get($id);
        
        $new_invoice_data = array();
        if ($draft_invoice == true) {
            $new_invoice_data['save_as_draft'] = true;
        }
        $new_invoice_data['clientid'] = $_estimate->clientid;
        $new_invoice_data['project_id'] = $_estimate->project_id;
        $new_invoice_data['number'] = get_option('next_invoice_number');
        $new_invoice_data['date'] = _d(date('Y-m-d'));
        $new_invoice_data['duedate'] = _d(date('Y-m-d'));
        if (get_option('invoice_due_after') != 0) {
            $new_invoice_data['duedate'] = _d(date('Y-m-d', strtotime('+' . get_option('invoice_due_after') . ' DAY', strtotime(date('Y-m-d')))));
        }
        $new_invoice_data['show_quantity_as'] = $_estimate->show_quantity_as;
        $new_invoice_data['currency'] = $_estimate->currency;
        $new_invoice_data['subtotal'] = $_estimate->subtotal;
        $new_invoice_data['total'] = $_estimate->total;
        $new_invoice_data['adjustment'] = $_estimate->adjustment;
        $new_invoice_data['discount_percent'] = $_estimate->discount_percent;
        $new_invoice_data['discount_total'] = $_estimate->discount_total;
        $new_invoice_data['discount_type'] = $_estimate->discount_type;
        $new_invoice_data['sale_agent'] = $_estimate->sale_agent;
        // Since version 1.0.6
        $new_invoice_data['billing_street'] = $_estimate->billing_street;
        $new_invoice_data['billing_city'] = $_estimate->billing_city;
        $new_invoice_data['billing_state'] = $_estimate->billing_state;
        $new_invoice_data['billing_zip'] = $_estimate->billing_zip;
        $new_invoice_data['billing_country'] = $_estimate->billing_country;
        $new_invoice_data['shipping_street'] = $_estimate->shipping_street;
        $new_invoice_data['shipping_city'] = $_estimate->shipping_city;
        $new_invoice_data['shipping_state'] = $_estimate->shipping_state;
        $new_invoice_data['shipping_zip'] = $_estimate->shipping_zip;
        $new_invoice_data['shipping_country'] = $_estimate->shipping_country;
        if ($_estimate->include_shipping == 1) {
            $new_invoice_data['include_shipping'] = 1;
        }
        $new_invoice_data['show_shipping_on_invoice'] = $_estimate->show_shipping_on_estimate;
        $new_invoice_data['terms'] = get_option('predefined_terms_invoice');
        $new_invoice_data['clientnote'] = get_option('predefined_clientnote_invoice');
        // Set to unpaid status automatically
        $new_invoice_data['status'] = 1;
        $new_invoice_data['clientnote'] = '';
        $new_invoice_data['adminnote'] = '';
        
        $this->load->model('payment_modes_model');
        $modes = $this->payment_modes_model->get('', array(
            'expenses_only !=' => 1
        ));
        $temp_modes = array();
        foreach ($modes as $mode) {
            if ($mode['selected_by_default'] == 0) {
                continue;
            }
            $temp_modes[] = $mode['id'];
        }
        $new_invoice_data['allowed_payment_modes'] = $temp_modes;
        $new_invoice_data['newitems'] = array();
        $key = 1;
        foreach ($_estimate->items as $item) {
            $new_invoice_data['newitems'][$key]['description'] = $item['description'];
            $new_invoice_data['newitems'][$key]['long_description'] = $item['long_description'];
            $new_invoice_data['newitems'][$key]['qty'] = $item['qty'];
            $new_invoice_data['newitems'][$key]['unit'] = $item['unit'];
            $new_invoice_data['newitems'][$key]['taxname'] = array();
            $taxes = get_estimate_item_taxes($item['id']);
            foreach ($taxes as $tax) {
                // tax name is in format TAX1|10.00
                array_push($new_invoice_data['newitems'][$key]['taxname'], $tax['taxname']);
            }
            $new_invoice_data['newitems'][$key]['rate'] = $item['rate'];
            $new_invoice_data['newitems'][$key]['order'] = $item['item_order'];
            $key ++;
        }
        $this->load->model('invoices_model');
        $id = $this->invoices_model->add($new_invoice_data);
        if ($id) {
            // Customer accepted the estimate and is auto converted to invoice
            if (! is_staff_logged_in()) {
                $this->db->where('rel_type', 'invoice');
                $this->db->where('rel_id', $id);
                $this->db->delete('tblsalesactivity');
                $this->invoices_model->log_invoice_activity($id, 'invoice_activity_auto_converted_from_estimate', true, serialize(array(
                    '<a href="' . admin_url('estimates/list_estimates/' . $_estimate->id) . '">' . format_estimate_number($_estimate->id) . '</a>'
                )));
            }
            // For all cases update addefrom and sale agent from the invoice
            // May happen staff is not logged in and these values to be 0
            $this->db->where('id', $id);
            $this->db->update('tblinvoices', array(
                'addedfrom' => $_estimate->addedfrom,
                'sale_agent' => $_estimate->sale_agent
            ));
            
            // Update estimate with the new invoice data and set to status accepted
            $this->db->where('id', $_estimate->id);
            $this->db->update('tblestimates', array(
                'invoiced_date' => date('Y-m-d H:i:s'),
                'invoiceid' => $id,
                'status' => 4
            ));
            if ($client == false) {
                $this->log_estimate_activity($_estimate->id, 'estimate_activity_converted', false, serialize(array(
                    '<a href="' . admin_url('invoices/list_invoices/' . $id) . '">' . format_invoice_number($id) . '</a>'
                )));
            }
            
            do_action('estimate_converted_to_invoice', array(
                'invoice_id' => $id,
                'estimate_id' => $_estimate->id
            ));
        }
        
        return $id;
    }

    /**
     * Copy estimate
     * 
     * @param mixed $id
     *            estimate id to copy
     * @return mixed
     */
    public function copy($id)
    {
        $_estimate = $this->get($id);
        $new_estimate_data = array();
        $new_estimate_data['clientid'] = $_estimate->clientid;
        $new_estimate_data['project_id'] = $_estimate->project_id;
        $new_estimate_data['number'] = get_option('next_estimate_number');
        $new_estimate_data['date'] = _d(date('Y-m-d'));
        $new_estimate_data['expirydate'] = null;
        
        if ($_estimate->expirydate && get_option('estimate_due_after') != 0) {
            $new_estimate_data['expirydate'] = _d(date('Y-m-d', strtotime('+' . get_option('estimate_due_after') . ' DAY', strtotime(date('Y-m-d')))));
        }
        
        $new_estimate_data['show_quantity_as'] = $_estimate->show_quantity_as;
        $new_estimate_data['currency'] = $_estimate->currency;
        $new_estimate_data['subtotal'] = $_estimate->subtotal;
        $new_estimate_data['total'] = $_estimate->total;
        $new_estimate_data['adminnote'] = $_estimate->adminnote;
        $new_estimate_data['adjustment'] = $_estimate->adjustment;
        $new_estimate_data['discount_percent'] = $_estimate->discount_percent;
        $new_estimate_data['discount_total'] = $_estimate->discount_total;
        $new_estimate_data['discount_type'] = $_estimate->discount_type;
        $new_estimate_data['terms'] = $_estimate->terms;
        $new_estimate_data['sale_agent'] = $_estimate->sale_agent;
        $new_estimate_data['reference_no'] = $_estimate->reference_no;
        // Since version 1.0.6
        $new_estimate_data['billing_street'] = $_estimate->billing_street;
        $new_estimate_data['billing_city'] = $_estimate->billing_city;
        $new_estimate_data['billing_state'] = $_estimate->billing_state;
        $new_estimate_data['billing_zip'] = $_estimate->billing_zip;
        $new_estimate_data['billing_country'] = $_estimate->billing_country;
        $new_estimate_data['shipping_street'] = $_estimate->shipping_street;
        $new_estimate_data['shipping_city'] = $_estimate->shipping_city;
        $new_estimate_data['shipping_state'] = $_estimate->shipping_state;
        $new_estimate_data['shipping_zip'] = $_estimate->shipping_zip;
        $new_estimate_data['shipping_country'] = $_estimate->shipping_country;
        if ($_estimate->include_shipping == 1) {
            $new_estimate_data['include_shipping'] = $_estimate->include_shipping;
        }
        $new_estimate_data['show_shipping_on_estimate'] = $_estimate->show_shipping_on_estimate;
        // Set to unpaid status automatically
        $new_estimate_data['status'] = 1;
        $new_estimate_data['clientnote'] = $_estimate->clientnote;
        $new_estimate_data['adminnote'] = '';
        $new_estimate_data['newitems'] = array();
        $key = 1;
        foreach ($_estimate->items as $item) {
            $new_estimate_data['newitems'][$key]['description'] = $item['description'];
            $new_estimate_data['newitems'][$key]['long_description'] = clear_textarea_breaks($item['long_description']);
            $new_estimate_data['newitems'][$key]['qty'] = $item['qty'];
            $new_estimate_data['newitems'][$key]['unit'] = $item['unit'];
            $new_estimate_data['newitems'][$key]['taxname'] = array();
            $taxes = get_estimate_item_taxes($item['id']);
            foreach ($taxes as $tax) {
                // tax name is in format TAX1|10.00
                array_push($new_estimate_data['newitems'][$key]['taxname'], $tax['taxname']);
            }
            $new_estimate_data['newitems'][$key]['rate'] = $item['rate'];
            $new_estimate_data['newitems'][$key]['order'] = $item['item_order'];
            $key ++;
        }
        $id = $this->add($new_estimate_data);
        if ($id) {
            $custom_fields = get_custom_fields('estimate');
            foreach ($custom_fields as $field) {
                $value = get_custom_field_value($_estimate->id, $field['id'], 'estimate');
                if ($value == '') {
                    continue;
                }
                
                $this->db->insert('tblcustomfieldsvalues', array(
                    'relid' => $id,
                    'fieldid' => $field['id'],
                    'fieldto' => 'estimate',
                    'value' => $value
                ));
            }
            
            $tags = get_tags_in($_estimate->id, 'estimate');
            handle_tags_save($tags, $id, 'estimate');
            
            logActivity('Copied Estimate ' . format_estimate_number($_estimate->id));
            
            return $id;
        }
        
        return false;
    }

    /**
     * Performs estimates totals status
     * 
     * @param array $data            
     * @return array
     */
    public function get_estimates_total($data)
    {
        $statuses = $this->get_statuses();
        $this->load->model('currencies_model');
        if (isset($data['currency'])) {
            $currencyid = $data['currency'];
        } elseif (isset($data['customer_id']) && $data['customer_id'] != '') {
            $currencyid = $this->clients_model->get_customer_default_currency($data['customer_id']);
            if ($currencyid == 0) {
                $currencyid = $this->currencies_model->get_base_currency()->id;
            }
        } elseif (isset($data['project_id']) && $data['project_id'] != '') {
            $this->load->model('projects_model');
            $currencyid = $this->projects_model->get_currency($data['project_id'])->id;
        } else {
            $currencyid = $this->currencies_model->get_base_currency()->id;
        }
        
        $symbol = $this->currencies_model->get_currency_symbol($currencyid);
        $where = '';
        if (isset($data['customer_id']) && $data['customer_id'] != '') {
            $where = ' AND clientid=' . $data['customer_id'];
        }
        
        if (isset($data['project_id']) && $data['project_id'] != '') {
            $where .= ' AND project_id=' . $data['project_id'];
        }
        
        if (! has_permission('estimates', '', 'view')) {
            $where .= ' AND addedfrom=' . get_staff_user_id();
        }
        $sql = 'SELECT';
        foreach ($statuses as $estimate_status) {
            $sql .= '(SELECT SUM(total) FROM tblestimates WHERE status=' . $estimate_status;
            $sql .= ' AND currency =' . $currencyid;
            if (isset($data['years']) && count($data['years']) > 0) {
                $sql .= ' AND YEAR(date) IN (' . implode(', ', $data['years']) . ')';
            } else {
                $sql .= ' AND YEAR(date) = ' . date('Y');
            }
            $sql .= $where;
            $sql .= ') as "' . $estimate_status . '",';
        }
        
        $sql = substr($sql, 0, - 1);
        $result = $this->db->query($sql)->result_array();
        $_result = array();
        $i = 1;
        foreach ($result as $key => $val) {
            foreach ($val as $status => $total) {
                $_result[$i]['total'] = $total;
                $_result[$i]['symbol'] = $symbol;
                $_result[$i]['status'] = $status;
                $i ++;
            }
        }
        $_result['currencyid'] = $currencyid;
        
        return $_result;
    }

    /**
     * Get all estimate items
     * 
     * @param mixed $id
     *            estimateid
     * @return array
     */
    public function get_estimate_items($id)
    {
        $this->db->select();
        $this->db->from('tblitems_in');
        $this->db->where('rel_id', $id);
        $this->db->where('rel_type', 'estimate');
        $this->db->order_by('item_order', 'asc');
        
        return $this->db->get()->result_array();
    }

    /**
     * Insert new estimate to database
     * 
     * @param array $data
     *            invoiec data
     * @return mixed - false if not insert, estimate ID if succes
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
            'description',
            'long_description',
            'taxid',
            'unit',
            'rate',
            'quantity',
            'item_select'
        );
        foreach ($unsetters as $unseter) {
            if (isset($data[$unseter])) {
                unset($data[$unseter]);
            }
        }
        $data['prefix'] = get_option('estimate_prefix');
        $data['number_format'] = get_option('estimate_number_format');
        
        if (isset($data['custom_fields'])) {
            $custom_fields = $data['custom_fields'];
            unset($data['custom_fields']);
        }
        $data['date'] = to_sql_date($data['date']);
        if (! empty($data['expirydate'])) {
            $data['expirydate'] = to_sql_date($data['expirydate']);
        } else {
            unset($data['expirydate']);
        }
        
        if (isset($data['project_id']) && $data['project_id'] == '' || ! isset($data['project_id'])) {
            $data['project_id'] = 0;
        }
        
        $data['hash'] = md5(rand() . microtime());
        // Check if the key exists
        $this->db->where('hash', $data['hash']);
        $exists = $this->db->get('tblestimates')->row();
        if ($exists) {
            $data['hash'] = md5(rand() . microtime());
        }
        
        $tags = '';
        if (isset($data['tags'])) {
            $tags = $data['tags'];
            unset($data['tags']);
        }
        
        $data['adminnote'] = nl2br($data['adminnote']);
        $data['clientnote'] = nl2br_save_html($data['clientnote']);
        $data['terms'] = nl2br_save_html($data['terms']);
        
        $data['datecreated'] = date('Y-m-d H:i:s');
        $data['addedfrom'] = get_staff_user_id();
        $items = array();
        if (isset($data['newitems'])) {
            $items = $data['newitems'];
            unset($data['newitems']);
        }
        if (! isset($data['include_shipping'])) {
            foreach ($this->shipping_fields as $_s_field) {
                if (isset($data[$_s_field])) {
                    $data[$_s_field] = null;
                }
            }
            $data['show_shipping_on_estimate'] = 1;
            $data['include_shipping'] = 0;
        } else {
            $data['include_shipping'] = 1;
            // set by default for the next time to be checked
            if (isset($data['show_shipping_on_estimate'])) {
                $data['show_shipping_on_estimate'] = 1;
            } else {
                $data['show_shipping_on_estimate'] = 0;
            }
        }
        if ($data['discount_total'] == 0) {
            $data['discount_type'] = '';
        }
        $_data = do_action('before_estimate_added', array(
            'data' => $data,
            'items' => $items
        ));
        if ($data['sale_agent'] == '') {
            $data['sale_agent'] = 0;
        }
        
        if ((isset($data['adjustment']) && ! is_numeric($data['adjustment'])) || ! isset($data['adjustment'])) {
            $data['adjustment'] = 0;
        } elseif (isset($data['adjustment']) && is_numeric($data['adjustment'])) {
            $data['adjustment'] = number_format($data['adjustment'], get_decimal_places(), '.', '');
        }
        
        $data = $_data['data'];
        $items = $_data['items'];
        $this->db->insert('tblestimates', $data);
        $insert_id = $this->db->insert_id();
        if ($insert_id) {
            // Update next estimate number in settings
            $this->db->where('name', 'next_estimate_number');
            $this->db->set('value', 'value+1', false);
            $this->db->update('tbloptions');
            
            if (isset($custom_fields)) {
                handle_custom_fields_post($insert_id, $custom_fields);
            }
            
            handle_tags_save($tags, $insert_id, 'estimate');
            
            if (count($items) > 0) {
                foreach ($items as $key => $item) {
                    $this->db->insert('tblitems_in', array(
                        'description' => $item['description'],
                        'long_description' => nl2br($item['long_description']),
                        'qty' => $item['qty'],
                        'rate' => number_format($item['rate'], get_decimal_places(), '.', ''),
                        'rel_id' => $insert_id,
                        'rel_type' => 'estimate',
                        'item_order' => $item['order'],
                        'unit' => $item['unit']
                    ));
                    $itemid = $this->db->insert_id();
                    if ($itemid) {
                        if (isset($item['taxname']) && is_array($item['taxname'])) {
                            foreach ($item['taxname'] as $taxname) {
                                if ($taxname != '') {
                                    $tax_array = explode('|', $taxname);
                                    if (isset($tax_array[0]) && isset($tax_array[1])) {
                                        $tax_name = trim($tax_array[0]);
                                        $tax_rate = trim($tax_array[1]);
                                        if (total_rows('tblitemstax', array(
                                            'itemid' => $itemid,
                                            'taxrate' => $tax_rate,
                                            'taxname' => $tax_name,
                                            'rel_id' => $insert_id,
                                            'rel_type' => 'estimate'
                                        )) == 0) {
                                            $this->db->insert('tblitemstax', array(
                                                'itemid' => $itemid,
                                                'taxrate' => $tax_rate,
                                                'taxname' => $tax_name,
                                                'rel_id' => $insert_id,
                                                'rel_type' => 'estimate'
                                            ));
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
            $this->update_total_tax($insert_id);
            $this->log_estimate_activity($insert_id, 'estimate_activity_created');
            if ($saveAndSend === true) {
                $this->send_estimate_to_client($insert_id, '', true, '', true);
            }
            do_action('after_estimate_added', $insert_id);
            return $insert_id;
        }
        
        return false;
    }

    /**
     * Get item by id
     * 
     * @param mixed $id
     *            item id
     * @return object
     */
    public function get_estimate_item($id)
    {
        $this->db->where('id', $id);
        
        return $this->db->get('tblitems_in')->row();
    }

    /**
     * Update estimate data
     * 
     * @param array $data
     *            estimate data
     * @param mixed $id
     *            estimateid
     * @return boolean
     */
    public function update($data, $id)
    {
        $data['number'] = trim($data['number']);
        $affectedRows = 0;
        $original_estimate = $this->get($id);
        $original_status = $original_estimate->status;
        $original_number = $original_estimate->number;
        $original_number_formatted = format_estimate_number($id);
        
        $saveAndSend = false;
        if (isset($data['save_and_send'])) {
            $saveAndSend = true;
            unset($data['save_and_send']);
        }
        $unsetters = array(
            'unit',
            'currency_symbol',
            'price',
            'taxname',
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
        if ((isset($data['adjustment']) && ! is_numeric($data['adjustment'])) || ! isset($data['adjustment'])) {
            $data['adjustment'] = 0;
        } elseif (isset($data['adjustment']) && is_numeric($data['adjustment'])) {
            $data['adjustment'] = number_format($data['adjustment'], get_decimal_places(), '.', '');
        }
        if ($data['sale_agent'] == '') {
            $data['sale_agent'] = 0;
        }
        
        if (isset($data['project_id']) && $data['project_id'] == '' || ! isset($data['project_id'])) {
            $data['project_id'] = 0;
        }
        
        $data['adminnote'] = nl2br($data['adminnote']);
        $data['clientnote'] = nl2br_save_html($data['clientnote']);
        $data['terms'] = nl2br_save_html($data['terms']);
        
        $data['date'] = to_sql_date($data['date']);
        $data['expirydate'] = to_sql_date($data['expirydate']);
        if ($data['discount_total'] == 0) {
            $data['discount_type'] = '';
        }
        if (isset($data['custom_fields'])) {
            $custom_fields = $data['custom_fields'];
            if (handle_custom_fields_post($id, $custom_fields)) {
                $affectedRows ++;
            }
            unset($data['custom_fields']);
        }
        
        if (isset($data['tags'])) {
            if (handle_tags_save($data['tags'], $id, 'estimate')) {
                $affectedRows ++;
            }
            unset($data['tags']);
        }
        
        if (! isset($data['include_shipping'])) {
            foreach ($this->shipping_fields as $_s_field) {
                if (isset($data[$_s_field])) {
                    $data[$_s_field] = null;
                }
            }
            $data['show_shipping_on_estimate'] = 1;
            $data['include_shipping'] = 0;
        } else {
            $data['include_shipping'] = 1;
            // set by default for the next time to be checked
            if (isset($data['show_shipping_on_estimate'])) {
                $data['show_shipping_on_estimate'] = 1;
            } else {
                $data['show_shipping_on_estimate'] = 0;
            }
        }
        $action_data = array(
            'data' => $data,
            'newitems' => $newitems,
            'items' => $items,
            'id' => $id,
            'removed_items' => array()
        );
        if (isset($data['removed_items'])) {
            $action_data['removed_items'] = $data['removed_items'];
        }
        $_data = do_action('before_estimate_updated', $action_data);
        $data['removed_items'] = $_data['removed_items'];
        $newitems = $_data['newitems'];
        $items = $_data['items'];
        $data = $_data['data'];
        // Delete items checked to be removed from database
        if (isset($data['removed_items'])) {
            foreach ($data['removed_items'] as $remove_item_id) {
                $original_item = $this->get_estimate_item($remove_item_id);
                $this->db->where('id', $remove_item_id);
                $this->db->delete('tblitems_in');
                if ($this->db->affected_rows() > 0) {
                    $this->log_estimate_activity($id, 'invoice_estimate_activity_removed_item', false, serialize(array(
                        $original_item->description
                    )));
                    $affectedRows ++;
                    $this->db->where('itemid', $remove_item_id);
                    $this->db->where('rel_type', 'estimate');
                    $this->db->delete('tblitemstax');
                }
            }
            unset($data['removed_items']);
        }
        $this->db->where('id', $id);
        $this->db->update('tblestimates', $data);
        if ($this->db->affected_rows() > 0) {
            // Check for status change
            if ($original_status != $data['status']) {
                $this->log_estimate_activity($original_estimate->id, 'not_estimate_status_updated', false, serialize(array(
                    '<original_status>' . $original_status . '</original_status>',
                    '<new_status>' . $data['status'] . '</new_status>'
                )));
                if ($data['status'] == 2) {
                    $this->db->where('id', $id);
                    $this->db->update('tblestimates', array(
                        'sent' => 1
                    ));
                }
            }
            if ($original_number != $data['number']) {
                $this->log_estimate_activity($original_estimate->id, 'estimate_activity_number_changed', false, serialize(array(
                    $original_number_formatted,
                    format_estimate_number($original_estimate->id)
                )));
            }
            $affectedRows ++;
        }
        $this->load->model('taxes_model');
        if (count($items) > 0) {
            foreach ($items as $key => $item) {
                $estimate_item_id = $item['itemid'];
                $original_item = $this->get_estimate_item($estimate_item_id);
                $this->db->where('id', $estimate_item_id);
                $this->db->update('tblitems_in', array(
                    'item_order' => $item['order'],
                    'unit' => $item['unit']
                ));
                if ($this->db->affected_rows() > 0) {
                    $affectedRows ++;
                }
                // Check for invoice item short description change
                $this->db->where('id', $estimate_item_id);
                $this->db->update('tblitems_in', array(
                    'description' => $item['description']
                ));
                if ($this->db->affected_rows() > 0) {
                    $this->log_estimate_activity($id, 'invoice_estimate_activity_updated_item_short_description', false, serialize(array(
                        $original_item->description,
                        $item['description']
                    )));
                    $affectedRows ++;
                }
                // Check for item long description change
                $this->db->where('id', $estimate_item_id);
                $this->db->update('tblitems_in', array(
                    'long_description' => nl2br($item['long_description'])
                ));
                if ($this->db->affected_rows() > 0) {
                    $this->log_estimate_activity($id, 'invoice_estimate_activity_updated_item_long_description', false, serialize(array(
                        $original_item->long_description,
                        $item['long_description']
                    )));
                    $affectedRows ++;
                }
                if (! isset($item['taxname']) || (isset($item['taxname']) && count($item['taxname']) == 0)) {
                    $this->db->where('itemid', $estimate_item_id);
                    $this->db->where('rel_type', 'estimate');
                    $this->db->delete('tblitemstax');
                } else {
                    $item_taxes = get_estimate_item_taxes($estimate_item_id);
                    $_item_taxes_names = array();
                    foreach ($item_taxes as $_item_tax) {
                        array_push($_item_taxes_names, $_item_tax['taxname']);
                    }
                    $i = 0;
                    foreach ($_item_taxes_names as $_item_tax) {
                        if (! in_array($_item_tax, $item['taxname'])) {
                            $this->db->where('id', $item_taxes[$i]['id']);
                            $this->db->delete('tblitemstax');
                            if ($this->db->affected_rows() > 0) {
                                $affectedRows ++;
                            }
                        }
                        $i ++;
                    }
                    if (isset($item['taxname']) && is_array($item['taxname'])) {
                        foreach ($item['taxname'] as $taxname) {
                            if ($taxname != '') {
                                $tax_array = explode('|', $taxname);
                                $tax_name = trim($tax_array[0]);
                                $tax_rate = trim($tax_array[1]);
                                if (total_rows('tblitemstax', array(
                                    'taxname' => $tax_name,
                                    'itemid' => $estimate_item_id,
                                    'taxrate' => $tax_rate,
                                    'rel_id' => $id,
                                    'rel_type' => 'estimate'
                                )) == 0) {
                                    $this->db->insert('tblitemstax', array(
                                        'taxrate' => $tax_rate,
                                        'taxname' => $tax_name,
                                        'itemid' => $estimate_item_id,
                                        'rel_id' => $id,
                                        'rel_type' => 'estimate'
                                    ));
                                    if ($this->db->affected_rows() > 0) {
                                        $affectedRows ++;
                                    }
                                }
                            }
                        }
                    }
                }
                // Check for item rate change
                $this->db->where('id', $estimate_item_id);
                $this->db->update('tblitems_in', array(
                    'rate' => number_format($item['rate'], get_decimal_places(), '.', '')
                ));
                if ($this->db->affected_rows() > 0) {
                    $this->log_estimate_activity($id, 'invoice_estimate_activity_updated_item_rate', false, serialize(array(
                        $original_item->rate,
                        $item['rate']
                    )));
                    $affectedRows ++;
                }
                // CHeck for invoice quantity change
                $this->db->where('id', $estimate_item_id);
                $this->db->update('tblitems_in', array(
                    'qty' => $item['qty']
                ));
                if ($this->db->affected_rows() > 0) {
                    $this->log_estimate_activity($id, 'invoice_estimate_activity_updated_qty_item', false, serialize(array(
                        $item['description'],
                        $original_item->qty,
                        $item['qty']
                    )));
                    $affectedRows ++;
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
                    'rel_type' => 'estimate',
                    'item_order' => $item['order'],
                    'unit' => $item['unit']
                ));
                $new_item_added = $this->db->insert_id();
                if ($new_item_added) {
                    if (isset($item['taxname']) && is_array($item['taxname'])) {
                        foreach ($item['taxname'] as $taxname) {
                            if ($taxname != '') {
                                $tax_array = explode('|', $taxname);
                                if (isset($tax_array[0]) && isset($tax_array[1])) {
                                    $tax_name = trim($tax_array[0]);
                                    $tax_rate = trim($tax_array[1]);
                                    if (total_rows('tblitemstax', array(
                                        'taxname' => $tax_name,
                                        'itemid' => $new_item_added,
                                        'taxrate' => $tax_rate,
                                        'rel_id' => $id,
                                        'rel_type' => 'estimate'
                                    )) == 0) {
                                        $this->db->insert('tblitemstax', array(
                                            'taxrate' => $tax_rate,
                                            'taxname' => $tax_name,
                                            'itemid' => $new_item_added,
                                            'rel_id' => $id,
                                            'rel_type' => 'estimate'
                                        ));
                                        if ($this->db->affected_rows() > 0) {
                                            $affectedRows ++;
                                        }
                                    }
                                }
                            }
                        }
                    }
                    $this->log_estimate_activity($id, 'invoice_estimate_activity_added_item', false, serialize(array(
                        $item['description']
                    )));
                    $affectedRows ++;
                }
            }
        }
        if ($affectedRows > 0) {
            $this->update_total_tax($id);
        }
        
        if ($saveAndSend === true) {
            $this->send_estimate_to_client($id, '', true, '', true);
        }
        
        if ($affectedRows > 0) {
            do_action('after_estimate_updated', $id);
            return true;
        }
        
        return false;
    }

    public function mark_action_status($action, $id, $client = false)
    {
        $this->db->where('id', $id);
        $this->db->update('tblestimates', array(
            'status' => $action
        ));
        
        $notifiedUsers = array();
        
        if ($this->db->affected_rows() > 0) {
            $estimate = $this->get($id);
            if ($client == true) {
                $this->db->where('staffid', $estimate->addedfrom);
                $this->db->or_where('staffid', $estimate->sale_agent);
                $staff_estimate = $this->db->get('tblstaff')->result_array();
                $invoiceid = false;
                $invoiced = false;
                
                $this->load->model('emails_model');
                
                $this->emails_model->set_rel_id($id);
                $this->emails_model->set_rel_type('estimate');
                
                $merge_fields_for_staff_email = array();
                if (! is_client_logged_in()) {
                    $contact_id = get_primary_contact_user_id($estimate->clientid);
                } else {
                    $contact_id = get_contact_user_id();
                }
                $merge_fields_for_staff_email = array_merge($merge_fields_for_staff_email, get_client_contact_merge_fields($estimate->clientid, $contact_id));
                $merge_fields_for_staff_email = array_merge($merge_fields_for_staff_email, get_estimate_merge_fields($estimate->id));
                
                if ($action == 4) {
                    if (get_option('estimate_auto_convert_to_invoice_on_client_accept') == 1) {
                        $invoiceid = $this->convert_to_invoice($id, true);
                        $this->load->model('invoices_model');
                        if ($invoiceid) {
                            $invoiced = true;
                            $invoice = $this->invoices_model->get($invoiceid);
                            $this->log_estimate_activity($id, 'estimate_activity_client_accepted_and_converted', true, serialize(array(
                                '<a href="' . admin_url('invoices/list_invoices/' . $invoiceid) . '">' . format_invoice_number($invoice->id) . '</a>'
                            )));
                        }
                    } else {
                        $this->log_estimate_activity($id, 'estimate_activity_client_accepted', true);
                    }
                    
                    // Send thank you email to all contacts with permission estimates
                    $contacts = $this->clients_model->get_contacts($estimate->clientid);
                    foreach ($contacts as $contact) {
                        if (has_contact_permission('estimates', $contact['id'])) {
                            $merge_fields = array();
                            $merge_fields = array_merge($merge_fields, get_client_contact_merge_fields($estimate->clientid, $contact['id']));
                            $merge_fields = array_merge($merge_fields, get_estimate_merge_fields($estimate->id));
                            $this->emails_model->send_email_template('estimate-thank-you-to-customer', $contact['email'], $merge_fields);
                        }
                    }
                    foreach ($staff_estimate as $member) {
                        $notified = add_notification(array(
                            'fromcompany' => true,
                            'touserid' => $member['staffid'],
                            'description' => 'not_estimate_customer_accepted',
                            'link' => 'estimates/list_estimates/' . $id,
                            'additional_data' => serialize(array(
                                format_estimate_number($estimate->id)
                            ))
                        ));
                        if ($notified) {
                            array_push($notifiedUsers, $member['staffid']);
                        }
                        // Send staff email notification that customer accepted estimate
                        $this->emails_model->send_email_template('estimate-accepted-to-staff', $member['email'], $merge_fields_for_staff_email);
                    }
                    
                    pusher_trigger_notification($notifiedUsers);
                    
                    return array(
                        'invoiced' => $invoiced,
                        'invoiceid' => $invoiceid
                    );
                } elseif ($action == 3) {
                    foreach ($staff_estimate as $member) {
                        $notified = add_notification(array(
                            'fromcompany' => true,
                            'touserid' => $member['staffid'],
                            'description' => 'not_estimate_customer_declined',
                            'link' => 'estimates/list_estimates/' . $id,
                            'additional_data' => serialize(array(
                                format_estimate_number($estimate->id)
                            ))
                        ));
                        
                        if ($notified) {
                            array_push($notifiedUsers, $member['staffid']);
                        }
                        // Send staff email notification that customer declined estimate
                        $this->emails_model->send_email_template('estimate-declined-to-staff', $member['email'], $merge_fields_for_staff_email);
                    }
                    
                    pusher_trigger_notification($notifiedUsers);
                    $this->log_estimate_activity($id, 'estimate_activity_client_declined', true);
                    
                    return array(
                        'invoiced' => $invoiced,
                        'invoiceid' => $invoiceid
                    );
                }
            } else {
                if ($action == 2) {
                    $this->db->where('id', $id);
                    $this->db->update('tblestimates', array(
                        'sent' => 1
                    ));
                }
                // Admin marked estimate
                $this->log_estimate_activity($id, 'estimate_activity_marked', false, serialize(array(
                    '<status>' . $action . '</status>'
                )));
                
                return true;
            }
        }
        
        return false;
    }

    /**
     * Get estimate attachments
     * 
     * @param mixed $estimate_id            
     * @param string $id
     *            attachment id
     * @return mixed
     */
    public function get_attachments($estimate_id, $id = '')
    {
        // If is passed id get return only 1 attachment
        if (is_numeric($id)) {
            $this->db->where('id', $id);
        } else {
            $this->db->where('rel_id', $estimate_id);
        }
        $this->db->where('rel_type', 'estimate');
        $result = $this->db->get('tblfiles');
        if (is_numeric($id)) {
            return $result->row();
        } else {
            return $result->result_array();
        }
    }

    /**
     * Delete proposal attachment
     * 
     * @param mixed $id
     *            attachmentid
     * @return boolean
     */
    public function delete_attachment($id)
    {
        $attachment = $this->get_attachments('', $id);
        $deleted = false;
        if ($attachment) {
            if (empty($attachment->external)) {
                unlink(get_upload_path_by_type('estimate') . $attachment->rel_id . '/' . $attachment->file_name);
            }
            $this->db->where('id', $attachment->id);
            $this->db->delete('tblfiles');
            if ($this->db->affected_rows() > 0) {
                $deleted = true;
                logActivity('Estimate Attachment Deleted [EstimateID: ' . $attachment->rel_id . ']');
            }
            
            if (is_dir(get_upload_path_by_type('estimate') . $attachment->rel_id)) {
                // Check if no attachments left, so we can delete the folder also
                $other_attachments = list_files(get_upload_path_by_type('estimate') . $attachment->rel_id);
                if (count($other_attachments) == 0) {
                    // okey only index.html so we can delete the folder also
                    delete_dir(get_upload_path_by_type('estimate') . $attachment->rel_id);
                }
            }
        }
        
        return $deleted;
    }

    /**
     * Delete estimate items and all connections
     * 
     * @param mixed $id
     *            estimateid
     * @return boolean
     */
    public function delete($id)
    {
        if (get_option('delete_only_on_last_estimate') == 1) {
            if (! is_last_estimate($id)) {
                return false;
            }
        }
        $estimate = $this->get($id);
        if (! is_null($estimate->invoiceid)) {
            return array(
                'is_invoiced_estimate_delete_error' => true
            );
        }
        do_action('before_estimate_deleted', $id);
        $this->db->where('id', $id);
        $this->db->delete('tblestimates');
        if ($this->db->affected_rows() > 0) {
            if (get_option('estimate_number_decrement_on_delete') == 1) {
                $current_next_estimate_number = get_option('next_estimate_number');
                if ($current_next_estimate_number > 1) {
                    // Decrement next estimate number to
                    $this->db->where('name', 'next_estimate_number');
                    $this->db->set('value', 'value-1', false);
                    $this->db->update('tbloptions');
                }
            }
            if (total_rows('tblproposals', array(
                'estimate_id' => $id
            )) > 0) {
                $this->db->where('estimate_id', $id);
                $estimate = $this->db->get('tblproposals')->row();
                $this->db->where('id', $estimate->id);
                $this->db->update('tblproposals', array(
                    'estimate_id' => null,
                    'date_converted' => null
                ));
            }
            $this->db->where('rel_id', $id);
            $this->db->where('rel_type', 'estimate');
            $this->db->delete('tblnotes');
            
            $this->db->where('rel_type', 'estimate');
            $this->db->where('rel_id', $id);
            $this->db->delete('tblviewstracking');
            
            $this->db->where('rel_type', 'estimate');
            $this->db->where('rel_id', $id);
            $this->db->delete('tbltags_in');
            
            $this->db->where('rel_type', 'estimate');
            $this->db->where('rel_id', $id);
            $this->db->delete('tblreminders');
            
            $this->db->where('rel_id', $id);
            $this->db->where('rel_type', 'estimate');
            $this->db->delete('tblitems_in');
            
            $this->db->where('rel_id', $id);
            $this->db->where('rel_type', 'estimate');
            $this->db->delete('tblitemstax');
            
            $this->db->where('rel_id', $id);
            $this->db->where('rel_type', 'estimate');
            $this->db->delete('tblsalesactivity');
            
            // Delete the custom field values
            $this->db->where('relid', $id);
            $this->db->where('fieldto', 'estimate');
            $this->db->delete('tblcustomfieldsvalues');
            
            $attachments = $this->get_attachments($id);
            foreach ($attachments as $attachment) {
                $this->delete_attachment($attachment['id']);
            }
            
            // Get related tasks
            $this->db->where('rel_type', 'estimate');
            $this->db->where('rel_id', $id);
            $tasks = $this->db->get('tblstafftasks')->result_array();
            foreach ($tasks as $task) {
                $this->tasks_model->delete_task($task['id']);
            }
            
            return true;
        }
        
        return false;
    }

    /**
     * Set estimate to sent when email is successfuly sended to client
     * 
     * @param mixed $id
     *            estimateid
     */
    public function set_estimate_sent($id, $emails_sent = array())
    {
        $this->db->where('id', $id);
        $this->db->update('tblestimates', array(
            'sent' => 1,
            'datesend' => date('Y-m-d H:i:s')
        ));
        $this->log_estimate_activity($id, 'invoice_estimate_activity_sent_to_client', false, serialize(array(
            '<custom_data>' . implode(', ', $emails_sent) . '</custom_data>'
        )));
        // Update estimate status to sent
        $this->db->where('id', $id);
        $this->db->update('tblestimates', array(
            'status' => 2
        ));
    }

    /**
     * Function that update total tax in estimates table for each estimate
     * 
     * @param mixed $id
     *            estimate id
     * @return void
     */
    public function update_total_tax($id)
    {
        $total_tax = 0;
        $taxes = array();
        $_calculated_taxes = array();
        $estimate = $this->get($id);
        foreach ($estimate->items as $item) {
            $item_taxes = get_estimate_item_taxes($item['id']);
            if (count($item_taxes) > 0) {
                foreach ($item_taxes as $tax) {
                    $calc_tax = 0;
                    $tax_not_calc = false;
                    if (! in_array($tax['taxname'], $_calculated_taxes)) {
                        array_push($_calculated_taxes, $tax['taxname']);
                        $tax_not_calc = true;
                    }
                    if ($tax_not_calc == true) {
                        $taxes[$tax['taxname']] = array();
                        $taxes[$tax['taxname']]['total'] = array();
                        array_push($taxes[$tax['taxname']]['total'], (($item['qty'] * $item['rate']) / 100 * $tax['taxrate']));
                        $taxes[$tax['taxname']]['tax_name'] = $tax['taxname'];
                        $taxes[$tax['taxname']]['taxrate'] = $tax['taxrate'];
                    } else {
                        array_push($taxes[$tax['taxname']]['total'], (($item['qty'] * $item['rate']) / 100 * $tax['taxrate']));
                    }
                }
            }
        }
        foreach ($taxes as $tax) {
            $total = array_sum($tax['total']);
            if ($estimate->discount_percent != 0 && $estimate->discount_type == 'before_tax') {
                $total_tax_calculated = ($total * $estimate->discount_percent) / 100;
                $total = ($total - $total_tax_calculated);
            }
            $total_tax += $total;
        }
        $this->db->where('id', $id);
        $this->db->update('tblestimates', array(
            'total_tax' => $total_tax
        ));
    }

    /**
     * Send expiration reminder to customer
     * 
     * @param mixed $id
     *            estimate id
     * @return boolean
     */
    public function send_expiry_reminder($id)
    {
        $estimate = $this->get($id);
        $estimate_number = format_estimate_number($estimate->id);
        $pdf = estimate_pdf($estimate);
        $attach = $pdf->Output($estimate_number . '.pdf', 'S');
        $emails_sent = array();
        $contacts = $this->clients_model->get_contacts($estimate->clientid);
        $this->load->model('emails_model');
        
        $this->emails_model->set_rel_id($id);
        $this->emails_model->set_rel_type('estimate');
        
        foreach ($contacts as $contact) {
            if (has_contact_permission('estimates', $contact['id'])) {
                $this->emails_model->add_attachment(array(
                    'attachment' => $attach,
                    'filename' => $estimate_number . '.pdf',
                    'type' => 'application/pdf'
                ));
                $merge_fields = array();
                $merge_fields = array_merge($merge_fields, get_client_contact_merge_fields($estimate->clientid, $contact['id']));
                $merge_fields = array_merge($merge_fields, get_estimate_merge_fields($estimate->id));
                if ($this->emails_model->send_email_template('estimate-expiry-reminder', $contact['email'], $merge_fields)) {
                    array_push($emails_sent, $contact['email']);
                }
            }
        }
        
        if (count($emails_sent) > 0) {
            $this->db->where('id', $id);
            $this->db->update('tblestimates', array(
                'is_expiry_notified' => 1
            ));
            $this->log_estimate_activity($id, 'not_expiry_reminder_sent', false, serialize(array(
                '<custom_data>' . implode(', ', $emails_sent) . '</custom_data>'
            )));
            
            return true;
        }
        
        return false;
    }

    /**
     * Send estimate to client
     * 
     * @param mixed $id
     *            estimateid
     * @param string $template
     *            email template to sent
     * @param boolean $attachpdf
     *            attach estimate pdf or not
     * @return boolean
     */
    public function send_estimate_to_client($id, $template = '', $attachpdf = true, $cc = '', $manually = false)
    {
        $this->load->model('emails_model');
        
        $this->emails_model->set_rel_id($id);
        $this->emails_model->set_rel_type('estimate');
        
        $estimate = $this->get($id);
        if ($template == '') {
            if ($estimate->sent == 0) {
                $template = 'estimate-send-to-client';
            } else {
                $template = 'estimate-already-send';
            }
        }
        $estimate_number = format_estimate_number($estimate->id);
        
        $emails_sent = array();
        $send = false;
        $sent_to = $this->input->post('sent_to');
        if ($manually === true) {
            $sent_to = array();
            $contacts = $this->clients_model->get_contacts($estimate->clientid);
            foreach ($contacts as $contact) {
                if (has_contact_permission('estimates', $contact['id'])) {
                    array_push($sent_to, $contact['id']);
                }
            }
        }
        
        $status_now = $estimate->status;
        $status_auto_updated = false;
        if (is_array($sent_to) && count($sent_to) > 0) {
            $i = 0;
            // Auto update status to sent in case when user sends the estimate is with status draft
            if ($status_now == 1) {
                $this->db->where('id', $estimate->id);
                $this->db->update('tblestimates', array(
                    'status' => 2
                ));
                $status_auto_updated = true;
            }
            
            if ($attachpdf) {
                $_pdf_estimate = $this->get($estimate->id);
                $pdf = estimate_pdf($_pdf_estimate);
                $attach = $pdf->Output($estimate_number . '.pdf', 'S');
            }
            
            foreach ($sent_to as $contact_id) {
                if ($contact_id != '') {
                    if ($attachpdf) {
                        $this->emails_model->add_attachment(array(
                            'attachment' => $attach,
                            'filename' => $estimate_number . '.pdf',
                            'type' => 'application/pdf'
                        ));
                    }
                    
                    if ($this->input->post('email_attachments')) {
                        $_other_attachments = $this->input->post('email_attachments');
                        
                        foreach ($_other_attachments as $attachment) {
                            $_attachment = $this->get_attachments($id, $attachment);
                            
                            $this->emails_model->add_attachment(array(
                                'attachment' => get_upload_path_by_type('estimate') . $id . '/' . $_attachment->file_name,
                                'filename' => $_attachment->file_name,
                                'type' => $_attachment->filetype,
                                'read' => true
                            ));
                        }
                    }
                    
                    $contact = $this->clients_model->get_contact($contact_id);
                    
                    $merge_fields = array();
                    $merge_fields = array_merge($merge_fields, get_client_contact_merge_fields($estimate->clientid, $contact_id));
                    $merge_fields = array_merge($merge_fields, get_estimate_merge_fields($estimate->id));
                    // Send cc only for the first contact
                    if (! empty($cc) && $i > 0) {
                        $cc = '';
                    }
                    if ($this->emails_model->send_email_template($template, $contact->email, $merge_fields, '', $cc)) {
                        $send = true;
                        array_push($emails_sent, $contact->email);
                    }
                }
                $i ++;
            }
        } else {
            return false;
        }
        if ($send) {
            $this->set_estimate_sent($id, $emails_sent);
            
            return true;
        } else {
            if ($status_auto_updated) {
                // Estimate not send to customer but the status was previously updated to sent now we need to revert back to draft
                $this->db->where('id', $estimate->id);
                $this->db->update('tblestimates', array(
                    'status' => 1
                ));
            }
        }
        
        return false;
    }

    /**
     * All estimate activity
     * 
     * @param mixed $id
     *            estimateid
     * @return array
     */
    public function get_estimate_activity($id)
    {
        $this->db->where('rel_id', $id);
        $this->db->where('rel_type', 'estimate');
        $this->db->order_by('date', 'asc');
        
        return $this->db->get('tblsalesactivity')->result_array();
    }

    /**
     * Log estimate activity to database
     * 
     * @param mixed $id
     *            estimateid
     * @param string $description
     *            activity description
     */
    public function log_estimate_activity($id, $description = '', $client = false, $additional_data = '')
    {
        $staffid = get_staff_user_id();
        $full_name = get_staff_full_name(get_staff_user_id());
        if (DEFINED('CRON')) {
            $staffid = '[CRON]';
            $full_name = '[CRON]';
        } elseif ($client == true) {
            $staffid = null;
            $full_name = '';
        }
        
        $this->db->insert('tblsalesactivity', array(
            'description' => $description,
            'date' => date('Y-m-d H:i:s'),
            'rel_id' => $id,
            'rel_type' => 'estimate',
            'staffid' => $staffid,
            'full_name' => $full_name,
            'additional_data' => $additional_data
        ));
    }

    /**
     * Updates pipeline order when drag and drop
     * 
     * @param mixe $data
     *            $_POST data
     * @return void
     */
    public function update_pipeline($data)
    {
        $this->mark_action_status($data['status'], $data['estimateid']);
        foreach ($data['order'] as $order_data) {
            $this->db->where('id', $order_data[0]);
            $this->db->update('tblestimates', array(
                'pipeline_order' => $order_data[1]
            ));
        }
    }

    /**
     * Get estimate unique year for filtering
     * 
     * @return array
     */
    public function get_estimates_years()
    {
        return $this->db->query('SELECT DISTINCT(YEAR(date)) as year FROM tblestimates ORDER BY year DESC')->result_array();
    }
}
