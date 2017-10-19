<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Invoices_model extends CRM_Model
{

    private $shipping_fields = array(
        'shipping_street',
        'shipping_city',
        'shipping_city',
        'shipping_state',
        'shipping_zip',
        'shipping_country'
    );

    private $statuses = array(
        1,
        2,
        3,
        4,
        5,
        6
    );

    public function __construct()
    {
        parent::__construct();
        $ci =& get_instance();
        $ci->load->helper('perfex_general_helper');
    }

    /**
     *
     * @return array
     */
    public function get_statuses()
    {
        return $this->statuses;
    }

    /**
     *
     * @return mixed
     */
    public function get_sale_agents()
    {
        return $this->db->query("SELECT DISTINCT(sale_agent) as sale_agent " . "FROM tblinvoices WHERE sale_agent != 0")->result_array();
    }


    /**
     * @param $name
     * @return mixed
     */
    public function get_employee_id_by_name($name)
    {
        $names = explode(' ', $name);
        if (count($names) == 2) {
            $query = "select * from tblstaff 
                    where firstname='" . $names[0] . "' 
                    and lastname='" . $names[1] . "'";
        } // end if
        else {
            $query = "select * from tblstaff 
                    where firstname='" . $names[0] . "'";
        } // end else
        $result = $this->db->query($query);
        foreach ($result->result() as $row) {
            $id = $row->staffid;
        }
        return $id;
    }


    /**
     * @param $date_1
     * @param $date_2
     * @param string $differenceFormat
     * @return string
     */
    function dateDifference($date_1, $date_2, $differenceFormat = '%a')
    {
        $datetime1 = date_create($date_1);
        $datetime2 = date_create($date_2);
        $interval = date_diff($datetime1, $datetime2);
        return $interval->format($differenceFormat);
    }


    /**
     * @param $clientid
     * @return int|string
     */
    function get_client_link_color($clientid)
    {
        $query = "select * from tblcustomfieldsvalues 
                where relid=$clientid and fieldid=2 
                            and fieldto='customers'";
        $result = $this->db->query($query);
        foreach ($result->result() as $row) {
            $lastvisit = $row->value;
        }

        if ($lastvisit != '') {
            $now = date('Y-m-d', time());
            $days = $this->dateDifference($lastvisit, $now);
            if ($days >= 0 && $days <= 7) {
                $color = '#84c529';
            }
            if ($days > 7 && $days <= 15) {
                $color = '#f7c80c';
            }
            if ($days > 15) {
                $color = 'red';
            }
        } // end if
        else {
            $color = '';
        }
        return $color;
    }

    /**
     * @param $staffid
     * @return mixed
     */
    public function is_employee_has_clients($staffid)
    {
        if ($staffid > 0) {
            $query = "select * from tblcustomeradmins where staff_id=$staffid";
            $result = $this->db->query($query);
            $num = $result->num_rows();
        } // end if
        else {
            $num = 0;
        } // end else
        return $num;
    }


    /**
     * @param $months_report
     * @return array
     */
    public function get_where_report_period($months_report)
    {
        if ($months_report != '') {
            if (is_numeric($months_report)) {
                // Last month
                if ($months_report == '1') {
                    $beginMonth = date('Y-m-01', strtotime("-$months_report MONTH"));
                    $endMonth = date('Y-m-t', strtotime('-1 MONTH'));
                } // end if $months_report == '1'
                else {
                    $months_report = (int)$months_report;
                    $months_report--;
                    $beginMonth = date('Y-m-01', strtotime("-$months_report MONTH"));
                    $endMonth = date('Y-m-t');
                } // end else
            } // end if is_numeric($months_report)

            elseif ($months_report == 'this_month') {
                $beginMonth = date('Y-m-01');
                $endMonth = date('Y-m-t');
            } // else if

            elseif ($months_report == 'this_year') {
                $beginMonth = date('Y-m-d', strtotime(date('Y-01-01')));
                $endMonth = date('Y-m-d', strtotime(date('Y-12-' . date('d', strtotime('last day of this year')))));

            } // end else if

            elseif ($months_report == 'last_year') {
                $beginMonth = date('Y-m-d', strtotime(date(date('Y', strtotime('last year')) . '-01-01')));
                $endMonth = date('Y-m-d', strtotime(date(date('Y', strtotime('last year')) . '-12-' . date('d', strtotime('last day of last year')))));
            } // end elseif

            elseif ($months_report == 'custom') {
                $beginMonth = to_sql_date($this->input->post('report_from'));
                $endMonth = to_sql_date($this->input->post('report_to'));
            } // end else if
        } // end if $months_report != ''
        $dates = array('date1' => $beginMonth, 'date2' => $endMonth);
        return $dates;
    }

    /**
     * @param $userid
     * @param $months_report
     * @return mixed
     */
    public function is_new_customer($userid, $months_report)
    {
        $dates = $this->get_where_report_period($months_report);
        $date1 = $dates['date1'];
        $date2 = $dates['date2'];
        $query = "select * from tblcustomfieldsvalues 
                where fieldid=7 
                and relid=$userid 
                and value <>'' 
                and value between '$date1' and '$date2'";
        $result = $this->db->query($query);
        $num = $result->num_rows();
        return $num;
    }

    /**
     *
     * @return array
     */
    public function get_employees()
    {
        $employees = array();
        $query = "select * from tblstaff where active='1' order by firstname";
        $result = $this->db->query($query);
        foreach ($result->result() as $row) {
            $names[] = $row->firstname . " " . $row->lastname;
        }
        sort($names, SORT_NATURAL);
        foreach ($names as $name) {
            $staffid = $this->get_employee_id_by_name($name);
            $status = $this->is_employee_has_clients($staffid);
            if ($staffid > 0 && $status > 0) {
                $employees[] = $staffid;
            }
        }
        return $employees;
    }

    /**
     *
     * @param
     *            $relid
     * @return mixed
     */
    public function get_region_name_by_rel($relid)
    {
        $query = "select * from blcustomfieldsvalues where fieldid=1 
                and relid=$relid";
        $result = $this->db->query($query);
        foreach ($result->result() as $row) {
            $value = $row->value;
        }
        return $value;
    }

    /**
     *
     * @param
     *            $clientid
     * @return string
     */
    public function get_staff_name_by_id($addedfrom)
    {
        if ($addedfrom > 0) {
            $query = "select * from tblstaff where staffid=$addedfrom";
            $result = $this->db->query($query);
            foreach ($result->result() as $row) {
                $name = $row->firstname . ' ' . $row->lastname;
            }
        }  // end if
        else {
            $name = 'N/A';
        }
        return $name;
    }


    /**
     * @param $userid
     * @return string
     */
    public function get_staff_name_by_client_id($userid)
    {
        $query = "SELECT * FROM tblcustomeradmins where customer_id=$userid";
        $result = $this->db->query($query);
        foreach ($result->result() as $row) {
            $saff_id = $row->staff_id;
        }
        $name = $this->get_staff_name_by_id($saff_id);
        return $name;
    }

    /**
     *
     * @param
     *            $region_name
     * @return mixed
     */
    public function is_region_has_invoices($region_name)
    {
        $cleardata = trim($region_name);
        $query = "select * from tblcustomfieldsvalues where value='$cleardata'";
        $result = $this->db->query($query);
        $num = $result->num_rows();
        return $num;
    }

    /**
     *
     * @return array
     */
    public function get_regions()
    {
        $regionsArr = array();
        $result = $this->db->query("SELECT options as regions " . "from tblcustomfields where id=1");
        foreach ($result->result() as $row) {
            $regions = $row->regions; // comma separated string
        }
        $regionsdata = explode(',', $regions); // array
        foreach ($regionsdata as $region_name) {
            $status = $this->is_region_has_invoices($region_name);
            if ($status > 0) {
                $regionsArr[] = $region_name;
            } // end if
        } // end foreach
        return $regionsArr;
    }




    /*
     * 
     */
    /**
     * @return array
     */
    public function get_products_list()
    {
        $products = array();
        $query = "select * from tblitems order by description";
        $result = $this->db->query($query);
        foreach ($result->result() as $row) {
            $products[] = $row->description;
        }
        return $products;
    }

    /**
     *
     * @param
     *            $regionname
     * @return array
     */
    public function get_clientid_by_region($regionname)
    {
        $query = "select * from tblcustomfieldsvalues where value='$regionname'";
        $result = $this->db->query($query);
        foreach ($result->result() as $row) {
            $clientids[] = $row->relid;
        }
        return $clientids;
    }

    /**
     *
     * @param
     *            $staff_id
     * @return array
     */
    public function get_clientid_by_admin($staff_id)
    {
        $query = "select * from tblcustomeradmins where staff_id=$staff_id";
        $result = $this->db->query($query);
        foreach ($result->result() as $row) {
            $clientids[] = $row->customer_id;
        }
        return $clientids;
    }

    /**
     * Get invoice by id
     *
     * @param mixed $id
     * @return array
     */
    public function get($id = '', $where = array())
    {
        $this->db->select('*, tblcurrencies.id as currencyid, tblinvoices.id as id, tblcurrencies.name as currency_name');
        $this->db->from('tblinvoices');
        $this->db->join('tblcurrencies', 'tblcurrencies.id = tblinvoices.currency', 'left');
        $this->db->where($where);
        if (is_numeric($id)) {
            $this->db->where('tblinvoices' . '.id', $id);
            $invoice = $this->db->get()->row();
            if ($invoice) {
                $invoice->items = $this->get_invoice_items($id);
                $invoice->attachments = $this->get_attachments($id);

                if ($invoice->project_id != 0) {
                    $this->load->model('projects_model');
                    $invoice->project_data = $this->projects_model->get($invoice->project_id);
                }

                $invoice->visible_attachments_to_customer_found = false;
                foreach ($invoice->attachments as $attachment) {
                    if ($attachment['visible_to_customer'] == 1) {
                        $invoice->visible_attachments_to_customer_found = true;
                        break;
                    }
                }

                $i = 0;
                $this->load->model('payments_model');
                $invoice->client = $this->clients_model->get($invoice->clientid);
                if ($invoice->client) {
                    if ($invoice->client->company == '') {
                        $invoice->client->company = $invoice->client->firstname . ' ' . $invoice->client->lastname;
                    }
                }
                $invoice->payments = $this->payments_model->get_invoice_payments($id);
            }

            return $invoice;
        }

        $this->db->order_by('number,YEAR(date)', 'desc');

        return $this->db->get()->result_array();
    }

    /**
     * Get all invoice items
     *
     * @param mixed $id
     *            invoiceid
     * @return array
     */
    public function get_invoice_items($id)
    {
        $this->db->select();
        $this->db->from('tblitems_in');
        $this->db->where('rel_id', $id);
        $this->db->where('rel_type', 'invoice');
        $this->db->order_by('item_order', 'asc');
        $items = $this->db->get()->result_array();

        return $items;
    }

    /**
     *
     * @param
     *            $id
     * @return mixed
     */
    public function get_invoice_item($id)
    {
        $this->db->where('id', $id);

        return $this->db->get('tblitems_in')->row();
    }

    /**
     *
     * @param
     *            $id
     * @return bool
     */
    public function mark_as_cancelled($id)
    {
        $this->db->where('id', $id);
        $this->db->update('tblinvoices', array(
            'status' => 5
        ));
        if ($this->db->affected_rows() > 0) {
            $this->log_invoice_activity($id, 'invoice_activity_marked_as_cancelled');
            do_action('invoice_marked_as_cancelled', $id);

            return true;
        }

        return false;
    }

    /**
     *
     * @param
     *            $id
     * @return bool
     */
    public function unmark_as_cancelled($id)
    {
        $this->db->where('id', $id);
        $this->db->update('tblinvoices', array(
            'status' => 1
        ));
        if ($this->db->affected_rows() > 0) {
            $this->log_invoice_activity($id, 'invoice_activity_unmarked_as_cancelled');

            return true;
        }

        return false;
    }

    /**
     * Get this invoice generated recurring invoices
     *
     * @since Version 1.0.1
     * @param mixed $id
     *            main invoice id
     * @return array
     */
    public function get_invoice_recurring_invoices($id)
    {
        $this->db->where('is_recurring_from', $id);
        $invoices = $this->db->get('tblinvoices')->result_array();
        $recurring_invoices = array();
        foreach ($invoices as $invoice) {
            $recurring_invoices[] = $this->get($invoice['id']);
        }

        return $recurring_invoices;
    }

    /**
     * Get invoice total from all statuses
     *
     * @since Version 1.0.2
     * @param mixed $data
     *            $_POST data
     * @return array
     */
    public function get_invoices_total($data)
    {
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

        $result = array();
        $result['due'] = array();
        $result['paid'] = array();
        $result['overdue'] = array();

        $has_permission_view = has_permission('invoices', '', 'view');

        for ($i = 1; $i <= 3; $i++) {
            $this->db->select('id,total');
            $this->db->from('tblinvoices');
            $this->db->where('currency', $currencyid);
            // Exclude cancelled invoices
            $this->db->where('status !=', 5);
            // Exclude draft
            $this->db->where('status !=', 6);

            if (isset($data['project_id']) && $data['project_id'] != '') {
                $this->db->where('project_id', $data['project_id']);
            } elseif (isset($data['customer_id']) && $data['customer_id'] != '') {
                $this->db->where('clientid', $data['customer_id']);
            }

            if ($i == 3) {
                $this->db->where('status', 4);
            }

            if (isset($data['years']) && count($data['years']) > 0) {
                $this->db->where_in('YEAR(date)', $data['years']);
            } else {
                $this->db->where('YEAR(date)', date('Y'));
            }

            if (!$has_permission_view) {
                $this->db->where('addedfrom', get_staff_user_id());
            }

            $invoices = $this->db->get()->result_array();
            foreach ($invoices as $invoice) {
                if ($i == 1) {
                    $result['due'][] = get_invoice_total_left_to_pay($invoice['id'], $invoice['total']);
                } elseif ($i == 2) {
                    $paid_where = array(
                        'field' => 'amount'
                    );
                    $paid_where['where'] = array(
                        'invoiceid' => $invoice['id']
                    );
                    $result['paid'][] = sum_from_table('tblinvoicepaymentrecords', $paid_where);
                } elseif ($i == 3) {
                    $result['overdue'][] = $invoice['total'];
                }
            }
        }
        $result['due'] = array_sum($result['due']);
        $result['paid'] = array_sum($result['paid']);
        $result['overdue'] = array_sum($result['overdue']);
        $result['symbol'] = $this->currencies_model->get_currency_symbol($currencyid);
        $result['currencyid'] = $currencyid;

        return $result;
    }

    /**
     * Insert new invoice to database
     *
     * @param array $data
     *            invoiec data
     * @return mixed - false if not insert, invoice ID if succes
     */
    public function add($data, $expense = false)
    {
        $data['prefix'] = get_option('invoice_prefix');
        $data['number_format'] = get_option('invoice_number_format');

        if (isset($data['save_as_draft'])) {
            $data['status'] = 6;
            unset($data['save_as_draft']);
        }

        $saveAndSend = false;
        if (isset($data['save_and_send'])) {
            $saveAndSend = true;
            unset($data['save_and_send']);
        }

        if (isset($data['billed_tasks'])) {
            $billed_tasks = array_map("unserialize", array_unique(array_map("serialize", $data['billed_tasks'])));
            unset($data['billed_tasks']);
        }
        if (isset($data['billed_expenses'])) {
            $data['billed_expenses'] = array_map("unserialize", array_unique(array_map("serialize", $data['billed_expenses'])));
            $billed_expenses = $data['billed_expenses'];
            unset($data['billed_expenses']);
        }

        if (isset($data['project_id']) && $data['project_id'] == '' || !isset($data['project_id'])) {
            $data['project_id'] = 0;
        }

        if (isset($data['invoices_to_merge'])) {
            $invoices_to_merge = $data['invoices_to_merge'];
            unset($data['invoices_to_merge']);
        }
        if (isset($data['cancel_merged_invoices'])) {
            $cancel_merged_invoices = true;
            unset($data['cancel_merged_invoices']);
        }

        if ((isset($data['adjustment']) && !is_numeric($data['adjustment'])) || !isset($data['adjustment'])) {
            $data['adjustment'] = 0;
        } elseif (isset($data['adjustment']) && is_numeric($data['adjustment'])) {
            $data['adjustment'] = number_format($data['adjustment'], get_decimal_places(), '.', '');
        }

        $unsetters = array(
            'currency_symbol',
            'price',
            'taxname',
            'description',
            'long_description',
            'unit',
            'taxid',
            'rate',
            'quantity',
            'item_select',
            'billed_tasks',
            'task_select',
            'task_id',
            'expense_id',
            'repeat_every_custom',
            'repeat_type_custom',
            'bill_expenses'
        );

        if (isset($data['recurring'])) {
            if ($data['recurring'] == 'custom') {
                $data['recurring_type'] = $data['repeat_type_custom'];
                $data['custom_recurring'] = 1;
                $data['recurring'] = $data['repeat_every_custom'];
            }
        } else {
            $data['custom_recurring'] = 0;
            $data['recurring'] = 0;
        }

        foreach ($unsetters as $unseter) {
            if (isset($data[$unseter])) {
                unset($data[$unseter]);
            }
        }
        if (isset($data['custom_fields'])) {
            $custom_fields = $data['custom_fields'];
            unset($data['custom_fields']);
        }
        $data['hash'] = md5(rand() . microtime());
        // Check if the key exists
        $this->db->where('hash', $data['hash']);
        $exists = $this->db->get('tblinvoices')->row();
        if ($exists) {
            $data['hash'] = md5(rand() . microtime());
        }

        $data['adminnote'] = nl2br($data['adminnote']);
        $data['clientnote'] = nl2br_save_html($data['clientnote']);
        $data['terms'] = nl2br_save_html($data['terms']);

        $data['date'] = to_sql_date($data['date']);
        if (!empty($data['duedate'])) {
            $data['duedate'] = to_sql_date($data['duedate']);
        } else {
            unset($data['duedate']);
        }
        if ($data['sale_agent'] == '') {
            $data['sale_agent'] = 0;
        }

        if (isset($data['cancel_overdue_reminders'])) {
            $data['cancel_overdue_reminders'] = 1;
        } else {
            $data['cancel_overdue_reminders'] = 0;
        }

        if (isset($data['recurring_ends_on']) && $data['recurring_ends_on'] == '') {
            unset($data['recurring_ends_on']);
        } elseif (isset($data['recurring_ends_on']) && $data['recurring_ends_on'] != '') {
            $data['recurring_ends_on'] = to_sql_date($data['recurring_ends_on']);
        }
        // Since version 1.0.1
        if (isset($data['allowed_payment_modes'])) {
            $data['allowed_payment_modes'] = serialize($data['allowed_payment_modes']);
        } else {
            $data['allowed_payment_modes'] = serialize(array());
        }
        $data['datecreated'] = date('Y-m-d H:i:s');
        if (!DEFINED('CRON')) {
            $data['addedfrom'] = get_staff_user_id();
        }
        $items = array();
        if (isset($data['newitems'])) {
            $items = $data['newitems'];
            unset($data['newitems']);
        }
        if (!isset($data['include_shipping'])) {
            foreach ($this->shipping_fields as $_s_field) {
                if (isset($data[$_s_field])) {
                    $data[$_s_field] = null;
                }
            }
            $data['show_shipping_on_invoice'] = 1;
            $data['include_shipping'] = 0;
        } else {
            // we dont need to overwrite to 1 unless its coming from the main function add
            if (!DEFINED('CRON') && $expense == false) {
                $data['include_shipping'] = 1;
                // set by default for the next time to be checked
                if (isset($data['show_shipping_on_invoice'])) {
                    $data['show_shipping_on_invoice'] = 1;
                } else {
                    $data['show_shipping_on_invoice'] = 0;
                }
            }
            // else its just like they are passed
        }
        if (isset($data['discount_total']) && $data['discount_total'] == 0) {
            $data['discount_type'] = '';
        }
        $_data = do_action('before_invoice_added', array(
            'data' => $data,
            'items' => $items
        ));
        $data = $_data['data'];
        $items = $_data['items'];

        $this->db->insert('tblinvoices', $data);
        $insert_id = $this->db->insert_id();
        if ($insert_id) {

            // Update next invoice number in settings
            $this->db->where('name', 'next_invoice_number');
            $this->db->set('value', 'value+1', false);
            $this->db->update('tbloptions');

            if (isset($custom_fields)) {
                handle_custom_fields_post($insert_id, $custom_fields);
            }

            if (isset($invoices_to_merge)) {
                $_merged = false;
                foreach ($invoices_to_merge as $m) {
                    $or_merge = $this->get($m);
                    if (!isset($cancel_merged_invoices)) {
                        if ($this->delete($m, true)) {
                            $_merged = true;
                        }
                    } else {
                        if ($this->mark_as_cancelled($m)) {
                            $_merged = true;
                            $admin_note = $or_merge->adminnote;
                            $note = 'Merged into invoice ' . format_invoice_number($insert_id);
                            if ($admin_note != '') {
                                $admin_note .= "\n\r" . $note;
                            } else {
                                $admin_note = $note;
                            }
                            $this->db->where('id', $m);
                            $this->db->update('tblinvoices', array(
                                'adminnote' => $admin_note
                            ));
                            // Delete the old items related from the merged invoice
                            foreach ($or_merge->items as $or_merge_item) {
                                $this->db->where('item_id', $or_merge_item['id']);
                                $this->db->delete('tblitemsrelated');
                            }
                        }
                    }
                    if ($_merged) {
                        $this->db->where('invoiceid', $or_merge->id);
                        $is_expense_invoice = $this->db->get('tblexpenses')->row();
                        if ($is_expense_invoice) {
                            $this->db->where('id', $is_expense_invoice->id);
                            $this->db->update('tblexpenses', array(
                                'invoiceid' => $insert_id
                            ));
                        }
                        if (total_rows('tblestimates', array(
                                'invoiceid' => $or_merge->id
                            )) > 0) {
                            $this->db->where('invoiceid', $or_merge->id);
                            $estimate = $this->db->get('tblestimates')->row();
                            $this->db->where('id', $estimate->id);
                            $this->db->update('tblestimates', array(
                                'invoiceid' => $insert_id
                            ));
                        } elseif (total_rows('tblproposals', array(
                                'invoice_id' => $or_merge->id
                            )) > 0) {
                            $this->db->where('invoice_id', $or_merge->id);
                            $proposal = $this->db->get('tblproposals')->row();
                            $this->db->where('id', $proposal->id);
                            $this->db->update('tblproposals', array(
                                'invoice_id' => $insert_id
                            ));
                        }
                    }
                }
            }
            if (isset($billed_tasks)) {
                foreach ($billed_tasks as $key => $tasks) {
                    foreach ($tasks as $t) {
                        $_task = $this->tasks_model->get($t);
                        $_task_data = array(
                            'billed' => 1,
                            'invoice_id' => $insert_id
                        );
                        if ($_task->status != 5) {
                            $_task_data['status'] = 5;
                            $_task_data['datefinished'] = date('Y-m-d H:i:s');
                        }
                        $this->db->where('id', $t);
                        $this->db->update('tblstafftasks', $_task_data);
                    }
                }
            }
            if (isset($billed_expenses)) {
                foreach ($billed_expenses as $key => $val) {
                    foreach ($val as $expense_id) {
                        $this->db->where('id', $expense_id);
                        $this->db->update('tblexpenses', array(
                            'invoiceid' => $insert_id
                        ));
                    }
                }
            }

            update_invoice_status($insert_id);

            if (count($items) > 0) {
                foreach ($items as $key => $item) {
                    $this->db->insert('tblitems_in', array(
                        'description' => $item['description'],
                        'long_description' => nl2br($item['long_description']),
                        'qty' => $item['qty'],
                        'rate' => number_format($item['rate'], get_decimal_places(), '.', ''),
                        'rel_id' => $insert_id,
                        'rel_type' => 'invoice',
                        'item_order' => $item['order'],
                        'unit' => $item['unit']
                    ));

                    $itemid = $this->db->insert_id();

                    if ($itemid) {
                        if (isset($billed_tasks[$key])) {
                            foreach ($billed_tasks[$key] as $_task_id) {
                                $this->db->insert('tblitemsrelated', array(
                                    'item_id' => $itemid,
                                    'rel_id' => $_task_id,
                                    'rel_type' => 'task'
                                ));
                            }
                        } elseif (isset($billed_expenses[$key])) {
                            foreach ($billed_expenses[$key] as $_expense_id) {
                                $this->db->insert('tblitemsrelated', array(
                                    'item_id' => $itemid,
                                    'rel_id' => $_expense_id,
                                    'rel_type' => 'expense'
                                ));
                            }
                        }
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
                                                'rel_type' => 'invoice'
                                            )) == 0) {
                                            $this->db->insert('tblitemstax', array(
                                                'itemid' => $itemid,
                                                'taxrate' => $tax_rate,
                                                'taxname' => $tax_name,
                                                'rel_id' => $insert_id,
                                                'rel_type' => 'invoice'
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

            if (!DEFINED('CRON') && $expense == false) {
                $lang_key = 'invoice_activity_created';
            } elseif (!DEFINED('CRON') && $expense == true) {
                $lang_key = 'invoice_activity_from_expense';
            } elseif (DEFINED('CRON') && $expense == false) {
                $lang_key = 'invoice_activity_recurring_created';
            } else {
                $lang_key = 'invoice_activity_recurring_from_expense_created';
            }
            $this->log_invoice_activity($insert_id, $lang_key);

            if ($saveAndSend === true) {
                $this->send_invoice_to_client($insert_id, '', true, '', true);
            }
            do_action('after_invoice_added', $insert_id);

            return $insert_id;
        }

        return false;
    }

    /**
     *
     * @param
     *            $id
     */
    public function update_total_tax($id)
    {
        $total_tax = 0;
        $taxes = array();
        $_calculated_taxes = array();
        $invoice = $this->get($id);
        foreach ($invoice->items as $item) {
            $item_taxes = get_invoice_item_taxes($item['id']);
            if (count($item_taxes) > 0) {
                foreach ($item_taxes as $tax) {
                    $calc_tax = 0;
                    $tax_not_calc = false;
                    if (!in_array($tax['taxname'], $_calculated_taxes)) {
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
            if ($invoice->discount_percent != 0 && $invoice->discount_type == 'before_tax') {
                $total_tax_calculated = ($total * $invoice->discount_percent) / 100;
                $total = ($total - $total_tax_calculated);
            }
            $total_tax += $total;
        }
        $this->db->where('id', $id);
        $this->db->update('tblinvoices', array(
            'total_tax' => $total_tax
        ));
    }

    /**
     *
     * @param
     *            $clientid
     * @return mixed
     */
    public function get_expenses_to_bill($clientid)
    {
        $this->load->model('expenses_model');
        $where = 'billable=1 AND clientid=' . $clientid . ' AND invoiceid IS NULL';
        if (!has_permission('expenses', '', 'view')) {
            $where .= ' AND addedfrom=' . get_staff_user_id();
        }

        return $this->expenses_model->get('', $where);
    }

    /**
     *
     * @param
     *            $client_id
     * @param
     *            $current_invoice
     * @return array
     */
    public function check_for_merge_invoice($client_id, $current_invoice)
    {
        if ($current_invoice != 'undefined') {
            $this->db->select('status');
            $this->db->where('id', $current_invoice);
            $row = $this->db->get('tblinvoices')->row();
            // Cant merge on paid invoice and partialy paid and cancelled
            if ($row->status == 2 || $row->status == 3 || $row->status == 5) {
                return array();
            }
        }

        $statuses = array(
            1,
            4,
            6
        );

        $has_permission_view = has_permission('invoices', '', 'view');
        $this->db->select('id');
        $this->db->where('clientid', $client_id);
        $this->db->where('STATUS IN (' . implode(', ', $statuses) . ')');
        if (!$has_permission_view) {
            $this->db->where('addedfrom', get_staff_user_id());
        }
        if ($current_invoice != 'undefined') {
            $this->db->where('id !=', $current_invoice);
        }

        $invoices = $this->db->get('tblinvoices')->result_array();
        $_invoices = array();
        foreach ($invoices as $invoice) {
            $_invoices[] = $this->get($invoice['id']);
        }

        return $_invoices;
    }

    /**
     * Copy invoice
     *
     * @param mixed $id
     *            invoice id to copy
     * @return mixed
     */
    public function copy($id)
    {
        $_invoice = $this->get($id);
        $new_invoice_data = array();
        $new_invoice_data['clientid'] = $_invoice->clientid;
        $new_invoice_data['number'] = get_option('next_invoice_number');
        $new_invoice_data['date'] = _d(date('Y-m-d'));

        if ($_invoice->duedate && get_option('invoice_due_after') != 0) {
            $new_invoice_data['duedate'] = _d(date('Y-m-d', strtotime('+' . get_option('invoice_due_after') . ' DAY', strtotime(date('Y-m-d')))));
        }

        $new_invoice_data['save_as_draft'] = true;
        $new_invoice_data['recurring_type'] = $_invoice->recurring_type;
        $new_invoice_data['custom_recurring'] = $_invoice->custom_recurring;
        $new_invoice_data['show_quantity_as'] = $_invoice->show_quantity_as;
        $new_invoice_data['currency'] = $_invoice->currency;
        $new_invoice_data['subtotal'] = $_invoice->subtotal;
        $new_invoice_data['total'] = $_invoice->total;
        $new_invoice_data['adminnote'] = $_invoice->adminnote;
        $new_invoice_data['adjustment'] = $_invoice->adjustment;
        $new_invoice_data['discount_percent'] = $_invoice->discount_percent;
        $new_invoice_data['discount_total'] = $_invoice->discount_total;
        $new_invoice_data['recurring'] = $_invoice->recurring;
        $new_invoice_data['discount_type'] = $_invoice->discount_type;
        $new_invoice_data['terms'] = $_invoice->terms;
        $new_invoice_data['sale_agent'] = $_invoice->sale_agent;
        $new_invoice_data['project_id'] = $_invoice->project_id;
        $new_invoice_data['recurring_ends_on'] = $_invoice->recurring_ends_on;
        // Since version 1.0.6
        $new_invoice_data['billing_street'] = $_invoice->billing_street;
        $new_invoice_data['billing_city'] = $_invoice->billing_city;
        $new_invoice_data['billing_state'] = $_invoice->billing_state;
        $new_invoice_data['billing_zip'] = $_invoice->billing_zip;
        $new_invoice_data['billing_country'] = $_invoice->billing_country;
        $new_invoice_data['shipping_street'] = $_invoice->shipping_street;
        $new_invoice_data['shipping_city'] = $_invoice->shipping_city;
        $new_invoice_data['shipping_state'] = $_invoice->shipping_state;
        $new_invoice_data['shipping_zip'] = $_invoice->shipping_zip;
        $new_invoice_data['shipping_country'] = $_invoice->shipping_country;
        if ($_invoice->include_shipping == 1) {
            $new_invoice_data['include_shipping'] = $_invoice->include_shipping;
        }
        $new_invoice_data['show_shipping_on_invoice'] = $_invoice->show_shipping_on_invoice;
        // Set to unpaid status automatically
        $new_invoice_data['status'] = 1;
        $new_invoice_data['clientnote'] = $_invoice->clientnote;
        $new_invoice_data['adminnote'] = $_invoice->adminnote;
        $new_invoice_data['allowed_payment_modes'] = unserialize($_invoice->allowed_payment_modes);
        $new_invoice_data['newitems'] = array();
        $key = 1;
        foreach ($_invoice->items as $item) {
            $new_invoice_data['newitems'][$key]['description'] = $item['description'];
            $new_invoice_data['newitems'][$key]['long_description'] = clear_textarea_breaks($item['long_description']);
            $new_invoice_data['newitems'][$key]['qty'] = $item['qty'];
            $new_invoice_data['newitems'][$key]['unit'] = $item['unit'];
            $new_invoice_data['newitems'][$key]['taxname'] = array();
            $taxes = get_invoice_item_taxes($item['id']);
            foreach ($taxes as $tax) {
                // tax name is in format TAX1|10.00
                array_push($new_invoice_data['newitems'][$key]['taxname'], $tax['taxname']);
            }
            $new_invoice_data['newitems'][$key]['rate'] = $item['rate'];
            $new_invoice_data['newitems'][$key]['order'] = $item['item_order'];
            $key++;
        }
        $id = $this->invoices_model->add($new_invoice_data);
        if ($id) {
            $this->db->where('id', $id);
            $this->db->update('tblinvoices', array(
                'cancel_overdue_reminders' => $_invoice->cancel_overdue_reminders
            ));

            $custom_fields = get_custom_fields('invoice');
            foreach ($custom_fields as $field) {
                $value = get_custom_field_value($_invoice->id, $field['id'], 'invoice');
                if ($value == '') {
                    continue;
                }
                $this->db->insert('tblcustomfieldsvalues', array(
                    'relid' => $id,
                    'fieldid' => $field['id'],
                    'fieldto' => 'invoice',
                    'value' => $value
                ));
            }
            logActivity('Copied Invoice ' . format_invoice_number($_invoice->id));

            do_action('invoice_copied', array(
                'copy_from' => $_invoice->id,
                'copy_id' => $id
            ));

            return $id;
        }

        return false;
    }

    /**
     * Update invoice data
     *
     * @param array $data
     *            invoice data
     * @param mixed $id
     *            invoiceid
     * @return boolean
     */
    public function update($data, $id)
    {
        $original_invoice = $this->get($id);

        $saveAndSend = false;
        if (isset($data['save_and_send'])) {
            $saveAndSend = true;
            unset($data['save_and_send']);
        }

        // From the top checkboxes bill expenses to merge in invoice, no need for this in the update function
        if (isset($data['bill_expenses'])) {
            unset($data['bill_expenses']);
        }

        if (isset($data['invoices_to_merge'])) {
            $invoices_to_merge = $data['invoices_to_merge'];
            unset($data['invoices_to_merge']);
        }
        if (isset($data['cancel_merged_invoices'])) {
            $cancel_merged_invoices = true;
            unset($data['cancel_merged_invoices']);
        }
        if (isset($data['project_id']) && $data['project_id'] == '' || !isset($data['project_id'])) {
            $data['project_id'] = 0;
        }

        if ($data['recurring_ends_on'] == '') {
            $data['recurring_ends_on'] = null;
        } else {
            $data['recurring_ends_on'] = to_sql_date($data['recurring_ends_on']);
        }

        $affectedRows = 0;
        $data['number'] = trim($data['number']);
        $original_number_formatted = format_invoice_number($id);
        $original_number = $original_invoice->number;

        if (isset($data['billed_tasks'])) {
            $billed_tasks = $data['billed_tasks'];
            unset($data['billed_tasks']);
        }

        if (isset($data['billed_expenses'])) {
            $billed_expenses = array_map("unserialize", array_unique(array_map("serialize", $data['billed_expenses'])));
            unset($data['billed_expenses']);
        }

        if (isset($data['cancel_overdue_reminders'])) {
            $data['cancel_overdue_reminders'] = 1;
        } else {
            $data['cancel_overdue_reminders'] = 0;
        }

        if (isset($data['recurring'])) {
            if ($data['recurring'] == 'custom') {
                $data['recurring_type'] = $data['repeat_type_custom'];
                $data['custom_recurring'] = 1;
                $data['recurring'] = $data['repeat_every_custom'];
            } else {
                $data['recurring_type'] = null;
                $data['custom_recurring'] = 0;
            }
        } else {
            $data['custom_recurring'] = 0;
            $data['recurring'] = 0;
            $data['recurring_type'] = null;
        }
        $unsetters = array(
            'currency_symbol',
            'price',
            'taxname',
            'taxid',
            'isedit',
            'unit',
            'description',
            'long_description',
            'tax',
            'rate',
            'quantity',
            'item_select',
            'task_select',
            'task_id',
            'expense_id',
            'repeat_every_custom',
            'repeat_type_custom',
            'merge_current_invoice'
        );
        foreach ($unsetters as $u) {
            if (isset($u)) {
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

        if ((isset($data['adjustment']) && !is_numeric($data['adjustment'])) || !isset($data['adjustment'])) {
            $data['adjustment'] = 0;
        } elseif (isset($data['adjustment']) && is_numeric($data['adjustment'])) {
            $data['adjustment'] = number_format($data['adjustment'], get_decimal_places(), '.', '');
        }

        if (!isset($data['include_shipping'])) {
            foreach ($this->shipping_fields as $_s_field) {
                if (isset($data[$_s_field])) {
                    $data[$_s_field] = null;
                }
            }
            $data['show_shipping_on_invoice'] = 1;
            $data['include_shipping'] = 0;
        } else {
            $data['include_shipping'] = 1;
            // set by default for the next time to be checked
            if (isset($data['show_shipping_on_invoice'])) {
                $data['show_shipping_on_invoice'] = 1;
            } else {
                $data['show_shipping_on_invoice'] = 0;
            }
        }
        if ($data['sale_agent'] == '') {
            $data['sale_agent'] = 0;
        }
        // Since version 1.0.1
        if (isset($data['allowed_payment_modes'])) {
            $data['allowed_payment_modes'] = serialize($data['allowed_payment_modes']);
        } else {
            $data['allowed_payment_modes'] = serialize(array());
        }

        $data['terms'] = nl2br_save_html($data['terms']);
        $data['clientnote'] = nl2br_save_html($data['clientnote']);
        $data['adminnote'] = nl2br($data['adminnote']);

        $data['date'] = to_sql_date($data['date']);
        if (!empty($data['duedate'])) {
            $data['duedate'] = to_sql_date($data['duedate']);
        } else {
            $data['duedate'] = null;
        }
        if (isset($data['discount_total']) && $data['discount_total'] == 0) {
            $data['discount_type'] = '';
        }
        if (isset($data['custom_fields'])) {
            $custom_fields = $data['custom_fields'];
            if (handle_custom_fields_post($id, $custom_fields)) {
                $affectedRows++;
            }
            unset($data['custom_fields']);
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
        $_data = do_action('before_invoice_updated', $action_data);
        $data['removed_items'] = $_data['removed_items'];
        $newitems = $_data['newitems'];
        $items = $_data['items'];
        $data = $_data['data'];
        if (isset($billed_tasks)) {
            foreach ($billed_tasks as $key => $tasks) {
                foreach ($tasks as $t) {
                    $_task = $this->tasks_model->get($t);
                    $_task_data = array(
                        'billed' => 1,
                        'invoice_id' => $id
                    );
                    if ($_task->status != 5) {
                        $_task_data['status'] = 5;
                        $_task_data['datefinished'] = date('Y-m-d H:i:s');
                    }
                    $this->db->where('id', $t);
                    $this->db->update('tblstafftasks', $_task_data);
                }
            }
        }
        if (isset($billed_expenses)) {
            foreach ($billed_expenses as $key => $val) {
                foreach ($val as $expense_id) {
                    $this->db->where('id', $expense_id);
                    $this->db->update('tblexpenses', array(
                        'invoiceid' => $id
                    ));
                }
            }
        }
        // Delete items checked to be removed from database
        if (isset($data['removed_items'])) {
            foreach ($data['removed_items'] as $remove_item_id) {
                $original_item = $this->get_invoice_item($remove_item_id);
                $this->db->where('id', $remove_item_id);
                $this->db->delete('tblitems_in');
                if ($this->db->affected_rows() > 0) {
                    $this->log_invoice_activity($id, 'invoice_estimate_activity_removed_item', false, serialize(array(
                        $original_item->description
                    )));
                    $affectedRows++;

                    $this->db->where('itemid', $remove_item_id);
                    $this->db->where('rel_type', 'invoice');
                    $this->db->delete('tblitemstax');

                    $this->db->where('item_id', $original_item->id);
                    $related_items = $this->db->get('tblitemsrelated')->result_array();
                    foreach ($related_items as $rel_item) {
                        if ($rel_item['rel_type'] == 'task') {
                            $this->db->where('id', $rel_item['rel_id']);
                            $this->db->update('tblstafftasks', array(
                                'invoice_id' => null,
                                'billed' => 0
                            ));
                        } elseif ($rel_item['rel_type'] == 'expense') {
                            $this->db->where('id', $rel_item['rel_id']);
                            $this->db->update('tblexpenses', array(
                                'invoiceid' => null
                            ));
                        }
                        $this->db->where('item_id', $original_item->id);
                        $this->db->delete('tblitemsrelated');
                    }
                }

                $this->db->where('itemid', $remove_item_id);
                $this->db->where('rel_type', 'invoice');
                $this->db->delete('tblitemstax');
            }
            unset($data['removed_items']);
        }
        $this->db->where('id', $id);
        $this->db->update('tblinvoices', $data);
        if ($this->db->affected_rows() > 0) {
            $affectedRows++;
            if ($original_number != $data['number']) {
                $this->log_invoice_activity($original_invoice->id, 'invoice_activity_number_changed', false, serialize(array(
                    $original_number_formatted,
                    format_invoice_number($original_invoice->id)
                )));
            }
        }
        $this->load->model('taxes_model');
        if (count($items) > 0) {
            foreach ($items as $key => $item) {
                $invoice_item_id = $item['itemid'];
                $original_item = $this->get_invoice_item($invoice_item_id);
                $this->db->where('id', $invoice_item_id);
                $this->db->update('tblitems_in', array(
                    'item_order' => $item['order'],
                    'unit' => $item['unit']
                ));
                if ($this->db->affected_rows() > 0) {
                    $affectedRows++;
                }

                // Check for invoice item short description change
                $this->db->where('id', $invoice_item_id);
                $this->db->update('tblitems_in', array(
                    'description' => $item['description']
                ));
                if ($this->db->affected_rows() > 0) {
                    $this->log_invoice_activity($id, 'invoice_estimate_activity_updated_item_short_description', false, serialize(array(
                        $original_item->description,
                        $item['description']
                    )));
                    $affectedRows++;
                }
                // Check for item long description change
                $this->db->where('id', $invoice_item_id);
                $this->db->update('tblitems_in', array(
                    'long_description' => nl2br($item['long_description'])
                ));
                if ($this->db->affected_rows() > 0) {
                    $this->log_invoice_activity($id, 'invoice_estimate_activity_updated_item_long_description', false, serialize(array(
                        $original_item->long_description,
                        $item['long_description']
                    )));
                    $affectedRows++;
                }
                if (!isset($item['taxname']) || (isset($item['taxname']) && count($item['taxname']) == 0)) {
                    $this->db->where('itemid', $invoice_item_id);
                    $this->db->where('rel_type', 'invoice');
                    $this->db->delete('tblitemstax');
                } else {
                    $item_taxes = get_invoice_item_taxes($invoice_item_id);
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
                                $tax_array = explode('|', $taxname);
                                $tax_name = trim($tax_array[0]);
                                $tax_rate = trim($tax_array[1]);
                                if (total_rows('tblitemstax', array(
                                        'taxname' => $tax_name,
                                        'itemid' => $invoice_item_id,
                                        'taxrate' => $tax_rate,
                                        'rel_type' => 'invoice',
                                        'rel_id' => $id
                                    )) == 0) {
                                    $this->db->insert('tblitemstax', array(
                                        'taxrate' => $tax_rate,
                                        'taxname' => $tax_name,
                                        'itemid' => $invoice_item_id,
                                        'rel_id' => $id,
                                        'rel_type' => 'invoice'
                                    ));
                                    if ($this->db->affected_rows() > 0) {
                                        $affectedRows++;
                                    }
                                }
                            }
                        }
                    }
                }
                // Check for item rate change
                $this->db->where('id', $invoice_item_id);
                $this->db->update('tblitems_in', array(
                    'rate' => number_format($item['rate'], get_decimal_places(), '.', '')
                ));
                if ($this->db->affected_rows() > 0) {
                    $this->log_invoice_activity($id, 'invoice_estimate_activity_updated_item_rate', false, serialize(array(
                        $original_item->rate,
                        $item['rate']
                    )));
                    $affectedRows++;
                }
                // CHeck for invoice quantity change
                $this->db->where('id', $invoice_item_id);
                $this->db->update('tblitems_in', array(
                    'qty' => $item['qty']
                ));
                if ($this->db->affected_rows() > 0) {
                    $this->log_invoice_activity($id, 'invoice_estimate_activity_updated_qty_item', false, serialize(array(
                        $item['description'],
                        $original_item->qty,
                        $item['qty']
                    )));
                    $affectedRows++;
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
                    'rel_type' => 'invoice',
                    'item_order' => $item['order'],
                    'unit' => $item['unit']
                ));
                $new_item_added = $this->db->insert_id();
                if ($new_item_added) {
                    if (isset($billed_tasks[$key])) {
                        foreach ($billed_tasks[$key] as $_task_id) {
                            $this->db->insert('tblitemsrelated', array(
                                'item_id' => $new_item_added,
                                'rel_id' => $_task_id,
                                'rel_type' => 'task'
                            ));
                        }
                    } elseif (isset($billed_expenses[$key])) {
                        foreach ($billed_expenses[$key] as $_expense_id) {
                            $this->db->insert('tblitemsrelated', array(
                                'item_id' => $new_item_added,
                                'rel_id' => $_expense_id,
                                'rel_type' => 'expense'
                            ));
                        }
                    }
                    if (isset($item['taxname']) && is_array($item['taxname'])) {
                        foreach ($item['taxname'] as $taxname) {
                            if ($taxname != '') {
                                $tax_array = explode('|', $taxname);
                                if (isset($tax_array[0]) && isset($tax_array[1])) {
                                    $tax_name = trim($tax_array[0]);
                                    $tax_rate = trim($tax_array[1]);
                                    if (total_rows('tblitemstax', array(
                                            'taxrate' => $tax_rate,
                                            'taxname' => $tax_name,
                                            'itemid' => $new_item_added,
                                            'rel_id' => $id,
                                            'rel_type' => 'invoice'
                                        )) == 0) {
                                        $this->db->insert('tblitemstax', array(
                                            'taxrate' => $tax_rate,
                                            'taxname' => $tax_name,
                                            'itemid' => $new_item_added,
                                            'rel_id' => $id,
                                            'rel_type' => 'invoice'
                                        ));
                                        if ($this->db->affected_rows() > 0) {
                                            $affectedRows++;
                                        }
                                    }
                                }
                            }
                        }
                    }
                    $this->log_invoice_activity($id, 'invoice_estimate_activity_added_item', false, serialize(array(
                        $item['description']
                    )));
                    $affectedRows++;
                }
            }
        }
        if (isset($invoices_to_merge)) {
            $_merged = false;
            foreach ($invoices_to_merge as $m) {
                $or_merge = $this->get($m);
                if (!isset($cancel_merged_invoices)) {
                    if ($this->delete($m, true)) {
                        $_merged = true;
                    }
                } else {
                    if ($this->mark_as_cancelled($m)) {
                        $_merged = true;
                        $admin_note = $or_merge->adminnote;
                        $note = 'Merged into invoice ' . format_invoice_number($id);
                        if ($admin_note != '') {
                            $admin_note .= "\n\r" . $note;
                        } else {
                            $admin_note = $note;
                        }
                        $this->db->where('id', $m);
                        $this->db->update('tblinvoices', array(
                            'adminnote' => $admin_note
                        ));
                    }
                }
                if ($_merged) {
                    $this->db->where('invoiceid', $or_merge->id);
                    $is_expense_invoice = $this->db->get('tblexpenses')->row();
                    if ($is_expense_invoice) {
                        $this->db->where('id', $is_expense_invoice->id);
                        $this->db->update('tblexpenses', array(
                            'invoiceid' => $id
                        ));
                    }
                    if (total_rows('tblestimates', array(
                            'invoiceid' => $or_merge->id
                        )) > 0) {
                        $this->db->where('invoiceid', $or_merge->id);
                        $estimate = $this->db->get('tblestimates')->row();
                        $this->db->where('id', $estimate->id);
                        $this->db->update('tblestimates', array(
                            'invoiceid' => $id
                        ));
                    } elseif (total_rows('tblproposals', array(
                            'invoice_id' => $or_merge->id
                        )) > 0) {
                        $this->db->where('invoice_id', $or_merge->id);
                        $proposal = $this->db->get('tblproposals')->row();
                        $this->db->where('id', $proposal->id);
                        $this->db->update('tblproposals', array(
                            'invoice_id' => $id
                        ));
                    }
                }
            }
        }

        if ($affectedRows > 0) {
            $this->update_total_tax($id);
            update_invoice_status($id);
        }

        if ($saveAndSend === true) {
            $this->send_invoice_to_client($id, '', true, '', true);
        }

        if ($affectedRows > 0) {
            do_action('after_invoice_updated', $id);
            return true;
        }
        return false;
    }

    /**
     *
     * @param
     *            $invoiceid
     * @param string $id
     * @return mixed
     */
    public function get_attachments($invoiceid, $id = '')
    {
        // If is passed id get return only 1 attachment
        if (is_numeric($id)) {
            $this->db->where('id', $id);
        } else {
            $this->db->where('rel_id', $invoiceid);
        }
        $this->db->where('rel_type', 'invoice');
        $result = $this->db->get('tblfiles');
        if (is_numeric($id)) {
            return $result->row();
        } else {
            return $result->result_array();
        }
    }

    /**
     * Delete invoice attachment
     *
     * @since Version 1.0.4
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
                unlink(get_upload_path_by_type('invoice') . $attachment->rel_id . '/' . $attachment->file_name);
            }
            $this->db->where('id', $attachment->id);
            $this->db->delete('tblfiles');
            if ($this->db->affected_rows() > 0) {
                $deleted = true;
                logActivity('Invoice Attachment Deleted [InvoiceID: ' . $attachment->rel_id . ']');
            }

            if (is_dir(get_upload_path_by_type('invoice') . $attachment->rel_id)) {
                // Check if no attachments left, so we can delete the folder also
                $other_attachments = list_files(get_upload_path_by_type('invoice') . $attachment->rel_id);
                if (count($other_attachments) == 0) {
                    // okey only index.html so we can delete the folder also
                    delete_dir(get_upload_path_by_type('invoice') . $attachment->rel_id);
                }
            }
        }

        return $deleted;
    }

    /**
     * Delete invoice items and all connections
     *
     * @param mixed $id
     *            invoiceid
     * @return boolean
     */
    public function delete($id, $merge = false)
    {
        if (get_option('delete_only_on_last_invoice') == 1 && $merge == false) {
            if (!is_last_invoice($id)) {
                return false;
            }
        }
        $number = format_invoice_number($id);

        do_action('before_invoice_deleted', $id);
        $this->db->where('id', $id);
        $this->db->delete('tblinvoices');
        if ($this->db->affected_rows() > 0) {
            if (get_option('invoice_number_decrement_on_delete') == 1 && $merge == false) {
                $current_next_invoice_number = get_option('next_invoice_number');
                if ($current_next_invoice_number > 1) {
                    // Decrement next invoice number to
                    $this->db->where('name', 'next_invoice_number');
                    $this->db->set('value', 'value-1', false);
                    $this->db->update('tbloptions');
                }
            }
            if ($merge == false) {
                $this->db->where('invoiceid', $id);
                $this->db->update('tblexpenses', array(
                    'invoiceid' => null
                ));

                $this->db->where('invoice_id', $id);
                $this->db->update('tblproposals', array(
                    'invoice_id' => null,
                    'date_converted' => null
                ));

                $this->db->where('invoice_id', $id);
                $this->db->update('tblstafftasks', array(
                    'invoice_id' => null,
                    'billed' => 0
                ));

                // if is converted from estimate set the estimate invoice to null
                if (total_rows('tblestimates', array(
                        'invoiceid' => $id
                    )) > 0) {
                    $this->db->where('invoiceid', $id);
                    $estimate = $this->db->get('tblestimates')->row();
                    $this->db->where('id', $estimate->id);
                    $this->db->update('tblestimates', array(
                        'invoiceid' => null,
                        'invoiced_date' => null
                    ));
                    $this->load->model('estimates_model');
                    $this->estimates_model->log_estimate_activity($estimate->id, 'not_estimate_invoice_deleted');
                }
            }
            $this->db->where('rel_type', 'invoice');
            $this->db->where('rel_id', $id);
            $this->db->delete('tblreminders');

            $this->db->where('rel_type', 'invoice');
            $this->db->where('rel_id', $id);
            $this->db->delete('tblviewstracking');

            $items = $this->get_invoice_items($id);
            $this->db->where('rel_id', $id);
            $this->db->where('rel_type', 'invoice');
            $this->db->delete('tblitems_in');

            foreach ($items as $item) {
                $this->db->where('item_id', $item['id']);
                $this->db->delete('tblitemsrelated');
            }
            $this->db->where('invoiceid', $id);
            $this->db->delete('tblinvoicepaymentrecords');

            $this->db->where('rel_id', $id);
            $this->db->where('rel_type', 'invoice');
            $this->db->delete('tblsalesactivity');

            $this->db->where('is_recurring_from', $id);
            $this->db->update('tblinvoices', array(
                'is_recurring_from' => null
            ));

            // Delete the custom field values
            $this->db->where('relid', $id);
            $this->db->where('fieldto', 'invoice');
            $this->db->delete('tblcustomfieldsvalues');

            $this->db->where('rel_id', $id);
            $this->db->where('rel_type', 'invoice');
            $this->db->delete('tblitemstax');

            // Get billed tasks for this invoice and set to unbilled
            $this->db->where('invoice_id', $id);
            $tasks = $this->db->get('tblstafftasks')->result_array();
            foreach ($tasks as $task) {
                $this->db->where('id', $task['id']);
                $this->db->update('tblstafftasks', array(
                    'invoice_id' => null,
                    'billed' => 0
                ));
            }

            $attachments = $this->get_attachments($id);
            foreach ($attachments as $attachment) {
                $this->delete_attachment($attachment['id']);
            }
            // Get related tasks
            $this->db->where('rel_type', 'invoice');
            $this->db->where('rel_id', $id);
            $tasks = $this->db->get('tblstafftasks')->result_array();
            foreach ($tasks as $task) {
                $this->tasks_model->delete_task($task['id']);
            }
            if ($merge == false) {
                logActivity('Invoice Deleted [' . $number . ']');
            }

            return true;
        }

        return false;
    }

    /**
     * Set invoice to sent when email is successfuly sended to client
     *
     * @param mixed $id
     *            invoiceid
     * @param mixed $manually
     *            is staff manually marking this invoice as sent
     * @return boolean
     */
    public function set_invoice_sent($id, $manually = false, $emails_sent = array(), $is_status_updated = false)
    {
        $this->db->where('id', $id);
        $this->db->update('tblinvoices', array(
            'sent' => 1,
            'datesend' => date('Y-m-d H:i:s')
        ));
        $marked = false;
        if ($this->db->affected_rows() > 0) {
            $marked = true;
        }
        if (DEFINED('CRON')) {
            $additional_activity_data = serialize(array(
                '<custom_data>' . implode(', ', $emails_sent) . '</custom_data>'
            ));
            $description = 'invoice_activity_sent_to_client_cron';
        } else {
            if ($manually == false) {
                $additional_activity_data = serialize(array(
                    '<custom_data>' . implode(', ', $emails_sent) . '</custom_data>'
                ));
                $description = 'invoice_activity_sent_to_client';
            } else {
                $additional_activity_data = serialize(array());
                $description = 'invoice_activity_marked_as_sent';
            }
        }

        if ($is_status_updated == false) {
            update_invoice_status($id, true);
        }

        $this->log_invoice_activity($id, $description, false, $additional_activity_data);

        return $marked;
    }

    /**
     * Sent overdue notice to client for this invoice
     *
     * @since Since Version 1.0.1
     * @param mxied $id
     *            invoiceid
     * @return boolean
     */
    public function send_invoice_overdue_notice($id)
    {
        $this->load->model('emails_model');
        $this->emails_model->set_rel_id($id);
        $this->emails_model->set_rel_type('invoice');

        $invoice = $this->get($id);
        $invoice_number = format_invoice_number($invoice->id);
        $pdf = invoice_pdf($invoice);
        $attach = $pdf->Output($invoice_number . '.pdf', 'S');
        $emails_sent = array();
        $send = false;
        $contacts = $this->clients_model->get_contacts($invoice->clientid);
        foreach ($contacts as $contact) {
            if (has_contact_permission('invoices', $contact['id'])) {
                $this->emails_model->add_attachment(array(
                    'attachment' => $attach,
                    'filename' => $invoice_number . '.pdf',
                    'type' => 'application/pdf'
                ));
                $merge_fields = array();
                $merge_fields = array_merge($merge_fields, get_client_contact_merge_fields($invoice->clientid, $contact['id']));
                $merge_fields = array_merge($merge_fields, get_invoice_merge_fields($invoice->id));
                if ($this->emails_model->send_email_template('invoice-overdue-notice', $contact['email'], $merge_fields)) {
                    array_push($emails_sent, $contact['email']);
                    $send = true;
                }
            }
        }
        if ($send) {
            if (DEFINED('CRON')) {
                $_from = '[CRON]';
            } else {
                $_from = get_staff_full_name();
            }

            $this->db->where('id', $id);
            $this->db->update('tblinvoices', array(
                'last_overdue_reminder' => date('Y-m-d')
            ));
            $this->log_invoice_activity($id, 'user_sent_overdue_reminder', false, serialize(array(
                '<custom_data>' . implode(', ', $emails_sent) . '</custom_data>',
                $_from
            )));

            do_action('invoice_overdue_reminder_sent', array(
                'sent_to' => $emails_sent,
                'invoice_id' => $id
            ));

            return true;
        }

        return false;
    }

    /**
     * Send invoice to client
     *
     * @param mixed $id
     *            invoiceid
     * @param string $template
     *            email template to sent
     * @param boolean $attachpdf
     *            attach invoice pdf or not
     * @return boolean
     */
    public function send_invoice_to_client($id, $template = '', $attachpdf = true, $cc = '', $manually = false)
    {
        $this->load->model('emails_model');

        $this->emails_model->set_rel_id($id);
        $this->emails_model->set_rel_type('invoice');

        $invoice = $this->get($id);

        if ($template == '') {
            if ($invoice->sent == 0) {
                $template = 'invoice-send-to-client';
            } else {
                $template = 'invoice-already-send';
            }
            $template = do_action('after_invoice_sent_template_statement', $template);
        }
        $invoice_number = format_invoice_number($invoice->id);

        $emails_sent = array();
        $send = false;
        // Manually is used when sending the invoice via add/edit area button Save & Send
        if (!DEFINED('CRON') && $manually === false) {
            $sent_to = $this->input->post('sent_to');
        } else {
            $sent_to = array();
            $contacts = $this->clients_model->get_contacts($invoice->clientid);
            foreach ($contacts as $contact) {
                if (has_contact_permission('invoices', $contact['id'])) {
                    array_push($sent_to, $contact['id']);
                }
            }
        }

        if (is_array($sent_to) && count($sent_to) > 0) {
            $status_updated = update_invoice_status($invoice->id, true, true);

            if ($attachpdf) {
                $_pdf_invoice = $this->get($id);
                $pdf = invoice_pdf($_pdf_invoice);
                $attach = $pdf->Output($invoice_number . '.pdf', 'S');
            }

            $i = 0;
            foreach ($sent_to as $contact_id) {
                if ($contact_id != '') {
                    if ($attachpdf) {
                        $this->emails_model->add_attachment(array(
                            'attachment' => $attach,
                            'filename' => $invoice_number . '.pdf',
                            'type' => 'application/pdf'
                        ));
                    }
                    if ($this->input->post('email_attachments')) {
                        $_other_attachments = $this->input->post('email_attachments');
                        foreach ($_other_attachments as $attachment) {
                            $_attachment = $this->get_attachments($id, $attachment);
                            $this->emails_model->add_attachment(array(
                                'attachment' => get_upload_path_by_type('invoice') . $id . '/' . $_attachment->file_name,
                                'filename' => $_attachment->file_name,
                                'type' => $_attachment->filetype,
                                'read' => true
                            ));
                        }
                    }
                    $contact = $this->clients_model->get_contact($contact_id);
                    $merge_fields = array();
                    $merge_fields = array_merge($merge_fields, get_client_contact_merge_fields($invoice->clientid, $contact_id));

                    $merge_fields = array_merge($merge_fields, get_invoice_merge_fields($invoice->id));
                    // Send cc only for the first contact
                    if (!empty($cc) && $i > 0) {
                        $cc = '';
                    }
                    if ($this->emails_model->send_email_template($template, $contact->email, $merge_fields, '', $cc)) {
                        $send = true;
                        array_push($emails_sent, $contact->email);
                    }
                }
                $i++;
            }
        } else {
            return false;
        }
        if ($send) {
            $this->set_invoice_sent($id, false, $emails_sent, true);

            return true;
        } else {
            // In case the invoice not sended and the status was draft and the invoiec status is updated before send return back to draft status
            if ($invoice->status == 6 && $status_updated !== false) {
                $this->db->where('id', $invoice->id);
                $this->db->update('tblinvoices', array(
                    'status' => 6
                ));
            }
        }

        return false;
    }

    /**
     * All invoice activity
     *
     * @param mixed $id
     *            invoiceid
     * @return array
     */
    public function get_invoice_activity($id)
    {
        $this->db->where('rel_id', $id);
        $this->db->where('rel_type', 'invoice');
        $this->db->order_by('date', 'asc');

        return $this->db->get('tblsalesactivity')->result_array();
    }

    /**
     * Log invoice activity to database
     *
     * @param mixed $id
     *            invoiceid
     * @param string $description
     *            activity description
     */
    public function log_invoice_activity($id, $description = '', $client = false, $additional_data = '')
    {
        if (DEFINED('CRON')) {
            $staffid = '[CRON]';
            $full_name = '[CRON]';
        } elseif ($client == true) {
            $staffid = null;
            $full_name = '';
        } else {
            $staffid = get_staff_user_id();
            $full_name = get_staff_full_name(get_staff_user_id());
        }
        $this->db->insert('tblsalesactivity', array(
            'description' => $description,
            'date' => date('Y-m-d H:i:s'),
            'rel_id' => $id,
            'rel_type' => 'invoice',
            'staffid' => $staffid,
            'full_name' => $full_name,
            'additional_data' => $additional_data
        ));
    }

    /**
     *
     * @return mixed
     */
    public function get_invoices_years()
    {
        return $this->db->query('SELECT DISTINCT(YEAR(date)) as year FROM tblinvoices ORDER BY year DESC')->result_array();
    }
}
