<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 *
 * @author moyo
 *
 */
class Reports extends Admin_controller
{

    private $_instance;

    public function __construct()
    {
        parent::__construct();
        if (!has_permission('reports', '', 'view')) {
            access_denied('reports');
        }
        $this->_instance = &get_instance();
        $this->load->model('reports_model');
    }

    /* No access on this url */

    /**
     */
    public function index()
    {
        redirect(admin_url());
    }

    /* See knowledge base article reports */

    /**
     */
    public function knowledge_base_articles()
    {
        $this->load->model('knowledge_base_model');
        $data['groups'] = $this->knowledge_base_model->get_kbg();
        $data['title'] = _l('kb_reports');
        $this->load->view('admin/reports/knowledge_base_articles', $data);
    }

    /* Report leads conversions */
    /**
     */
    public function leads()
    {
        $data['regions'] = $this->reports_model->get_leads_regions();
        $data['staff'] = $this->reports_model->get_leads_staff();
        $type = 'leads';
        if ($this->input->get('type')) {
            $type = $type . '_' . $this->input->get('type');
            $data['leads_staff_report'] = json_encode($this->reports_model->leads_staff_report());
        }
        $this->load->model('leads_model');
        $data['statuses'] = $this->leads_model->get_status();
        $data['leads_this_week_report'] = json_encode($this->reports_model->leads_this_week_report());
        $data['leads_sources_report'] = json_encode($this->reports_model->leads_sources_report());
        $this->load->view('admin/reports/' . $type, $data);
    }

    /**
     */
    public function get_leads_data_ajax()
    {
        $item = $_POST['item'];
        $data = $this->reports_model->get_leads_conversion_report_data(json_decode($item));
        /*
         * echo "<pre>";
         * print_r($data);
         * echo "</pre>";
         */
        echo $data;
    }

    /* Sales reports */
    /**
     */
    public function sales()
    {
        $data['mysqlVersion'] = $this->db->query('SELECT VERSION() as version')->row();
        $data['sqlMode'] = $this->db->query('SELECT @@sql_mode as mode')->row();

        if (is_using_multiple_currencies()) {
            $this->load->model('currencies_model');
            $data['currencies'] = $this->currencies_model->get();
        }
        $this->load->model('invoices_model');
        $this->load->model('estimates_model');
        $this->load->model('proposals_model');
        $data['invoice_statuses'] = $this->invoices_model->get_statuses();
        $data['estimate_statuses'] = $this->estimates_model->get_statuses();
        $data['payments_years'] = $this->reports_model->get_distinct_payments_years();
        $data['estimates_sale_agents'] = $this->estimates_model->get_sale_agents();
        $data['employees'] = $this->invoices_model->get_employees();
        $data['regions'] = $this->invoices_model->get_regions();
        $data['item_products'] = $this->invoices_model->get_products_list();
        $data['invoices_sale_agents'] = $this->invoices_model->get_sale_agents();

        $data['proposals_sale_agents'] = $this->proposals_model->get_sale_agents();
        $data['proposals_statuses'] = $this->proposals_model->get_statuses();

        $data['invoice_taxes'] = $this->distinct_taxes('invoice');
        $data['estimate_taxes'] = $this->distinct_taxes('estimate');
        $data['proposal_taxes'] = $this->distinct_taxes('proposal');

        $data['title'] = _l('sales_reports');
        $this->load->view('admin/reports/sales', $data);
    }

    /* Customer report */

    /**
     */
    public function customers_report()
    {
        if ($this->input->is_ajax_request()) {

            $this->load->model('currencies_model');
            $this->load->model('invoices_model');

            $select = array(
                'CASE company WHEN "" THEN (SELECT CONCAT(firstname, " ", lastname) FROM tblcontacts WHERE userid = tblclients.userid and is_primary = 1) ELSE company END as company',
                '(SELECT value FROM tblcustomfieldsvalues WHERE tblcustomfieldsvalues.fieldid=1 and tblcustomfieldsvalues.relid=tblclients.userid)',
                '(SELECT staff_id FROM tblcustomeradmins WHERE tblcustomeradmins.customer_id=tblclients.userid)',
                '(SELECT COUNT(clientid) FROM tblinvoices WHERE tblinvoices.clientid = tblclients.userid AND status != 5)',
                '(SELECT SUM(subtotal) - SUM(discount_total) FROM tblinvoices WHERE tblinvoices.clientid = tblclients.userid AND status != 5)',
                '(SELECT SUM(total) FROM tblinvoices WHERE tblinvoices.clientid = tblclients.userid AND status != 5)'
            );
            $where = array();

            $custom_date_select = $this->get_where_report_period();
            if ($custom_date_select != '') {
                $i = 0;
                foreach ($select as $_select) {
                    if ($i > 3) {
                        $_temp = substr($_select, 0, -1);
                        $_temp .= ' ' . $custom_date_select . ')';
                        $select[$i] = $_temp;
                    }
                    $i++;
                }
            }

            $by_currency = $this->input->post('report_currency');
            $currency = $this->currencies_model->get_base_currency();
            $currency_symbol = $this->currencies_model->get_currency_symbol($currency->id);
            if ($by_currency) {
                $i = 0;
                foreach ($select as $_select) {
                    if ($i > 3) {
                        $_temp = substr($_select, 0, -1);
                        $_temp .= ' AND currency =' . $by_currency . ')';
                        $select[$i] = $_temp;
                    }
                    $i++;
                }
                $currency = $this->currencies_model->get($by_currency);
                $currency_symbol = $this->currencies_model->get_currency_symbol($currency->id);
            }

            if ($this->input->post('c_regions')) {
                $regions = $this->input->post('c_regions');
                $s_regions = array();
                if (is_array($regions)) {
                    foreach ($regions as $r) {
                        if ($r != '') {
                            $clients = $this->invoices_model->get_clientid_by_region(trim($r));
                            if (count($clients) > 0) {
                                foreach ($clients as $clientid) {
                                    array_push($s_regions, $clientid);
                                } // end if count($clients)>0
                            } // end if $r != ''
                        } // end if $r != ''
                    } // end foreach
                    if (count($s_regions) > 0) {
                        array_push($where, 'AND userid IN (' . implode(', ', $s_regions) . ')');
                    } // end if count($s_regions) > 0
                } // end if is_array($regions)
            }

            if ($this->input->post('c_employees')) {
                $employees = $this->input->post('c_employees');
                $s_employees = array();
                if (is_array($employees)) {
                    foreach ($employees as $e) {
                        if ($e > 0) {
                            $clients = $this->invoices_model->get_clientid_by_admin($e);
                            if (count($clients) > 0) {
                                foreach ($clients as $clientid) {
                                    array_push($s_employees, $clientid);
                                } // end if count($clients)>0
                            } // end if $r != ''
                        } // end if $e > 0
                    } // end foreach
                } // end if is_array($employees)
                if (count($s_employees) > 0) {
                    array_push($where, 'AND userid IN (' . implode(', ', $s_employees) . ')');
                }
            }

            $aColumns = $select;
            $sIndexColumn = "userid";
            $sTable = 'tblclients';

            $result = data_tables_init($aColumns, $sIndexColumn, $sTable, array(), $where, array(
                'userid'
            ));

            /*
             * echo "<pre>";
             * print_r($result);
             * echo "</pre>";
             * die ('Stopped');
             */

            $output = $result['output'];
            $rResult = $result['rResult'];
            // $totalCustomers=count()
            $row = array();
            $x = 0;

            foreach ($rResult as $aRow) {
                $row = array();
                for ($i = 0; $i < count($aColumns); $i++) {
                    if (strpos($aColumns[$i], 'as') !== false && !isset($aRow[$aColumns[$i]])) {
                        $_data = $aRow[strafter($aColumns[$i], 'as ')];
                    }  // end if
                    else {
                        $_data = $aRow[$aColumns[$i]];
                    }
                    if ($i == 0) {
                        $color = $this->invoices_model->get_client_link_color($aRow['userid']);
                        $_data = '<a href="' . admin_url('clients/client/' . $aRow['userid']) . '" style="color:' . $color . ';" target="_blank">' . $aRow['company'] . '</a>';
                    } // end if

                    if ($i == 2) {
                        $_data = $this->invoices_model->get_staff_name_by_id($aRow[$aColumns[$i]]);
                    } elseif ($aColumns[$i] == $select[4] || $aColumns[$i] == $select[5]) {
                        if ($_data == null) {
                            $_data = 0;
                        }
                        $_data = format_money($_data, $currency_symbol);
                    }
                    $row[] = $_data;
                }
                $output['aaData'][] = $row;
                $x++;
            } // end foreach
        } // end if $this->input->is_ajax_request()

        echo json_encode($output);
        die();
    }


    /**
     *
     */
    public function customers_report_new()
    {
        if ($this->input->is_ajax_request()) {

            $this->load->model('currencies_model');
            $this->load->model('invoices_model');

            $select = array(
                'CASE company WHEN "" THEN (SELECT CONCAT(firstname, " ", lastname) FROM tblcontacts WHERE userid = tblclients.userid and is_primary = 1) ELSE company END as company',
                '(SELECT value as region FROM tblcustomfieldsvalues WHERE tblcustomfieldsvalues.fieldid=1 and tblcustomfieldsvalues.relid=tblclients.userid)',
                '(SELECT staff_id FROM tblcustomeradmins WHERE tblcustomeradmins.customer_id=tblclients.userid)',
                '(SELECT COUNT(clientid) FROM tblinvoices WHERE tblinvoices.clientid = tblclients.userid AND status != 5)',
                '(SELECT SUM(subtotal) - SUM(discount_total) FROM tblinvoices WHERE tblinvoices.clientid = tblclients.userid AND status != 5)',
                '(SELECT SUM(total) FROM tblinvoices WHERE tblinvoices.clientid = tblclients.userid AND status != 5)'
            );
            $where = array();

            $custom_date_select = $this->get_where_report_period();
            if ($custom_date_select != '') {
                $i = 0;
                foreach ($select as $_select) {
                    if ($i > 3) {
                        $_temp = substr($_select, 0, -1);
                        $_temp .= ' ' . $custom_date_select . ')';
                        $select[$i] = $_temp;
                    }
                    $i++;
                }
            }

            $by_currency = $this->input->post('report_currency');
            $currency = $this->currencies_model->get_base_currency();
            $currency_symbol = $this->currencies_model->get_currency_symbol($currency->id);

            if ($by_currency) {
                $i = 0;
                foreach ($select as $_select) {
                    if ($i > 3) {
                        $_temp = substr($_select, 0, -1);
                        $_temp .= ' AND currency =' . $by_currency . ')';
                        $select[$i] = $_temp;
                    }
                    $i++;
                }
                $currency = $this->currencies_model->get($by_currency);
                $currency_symbol = $this->currencies_model->get_currency_symbol($currency->id);
            }


            if ($this->input->post('c_regions')) {
                $regions = $this->input->post('c_regions');
                $s_regions = array();
                if (is_array($regions)) {
                    foreach ($regions as $r) {
                        if ($r != '') {
                            $clients = $this->invoices_model->get_clientid_by_region(trim($r));
                            if (count($clients) > 0) {
                                foreach ($clients as $clientid) {
                                    array_push($s_regions, $clientid);
                                } // end if count($clients)>0
                            } // end if $r != ''
                        } // end if $r != ''
                    } // end foreach
                    if (count($s_regions) > 0) {
                        array_push($where, 'AND userid IN (' . implode(', ', $s_regions) . ')');
                    } // end if count($s_regions) > 0
                } // end if is_array($regions)
            }

            if ($this->input->post('c_employees')) {
                $employees = $this->input->post('c_employees');
                $s_employees = array();
                if (is_array($employees)) {
                    foreach ($employees as $e) {
                        if ($e > 0) {
                            $clients = $this->invoices_model->get_clientid_by_admin($e);
                            if (count($clients) > 0) {
                                foreach ($clients as $clientid) {
                                    array_push($s_employees, $clientid);
                                } // end if count($clients)>0
                            } // end if $r != ''
                        } // end if $e > 0
                    } // end foreach
                } // end if is_array($employees)
                if (count($s_employees) > 0) {
                    array_push($where, 'AND userid IN (' . implode(', ', $s_employees) . ')');
                }
            }

            $aColumns = $select;
            $sIndexColumn = "userid";
            $sTable = 'tblclients';
            $months_report = $this->input->post('report_months');
            $dates = $this->invoices_model->get_where_report_period($months_report);
            $date1 = $dates['date1'];
            $date2 = $dates['date2'];
            $join = array(

                'JOIN tblcustomfieldsvalues ON (tblcustomfieldsvalues.fieldid=7 
                 and tblcustomfieldsvalues.relid=tblclients.userid 
                 and tblcustomfieldsvalues.value between "'.$date1.'" and "'.$date2.'")'

            );

            $result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, array(
                'userid'
            ));

            /*
            echo "<pre>";
            print_r($result);
            echo "</pre>";
            die ('Stopped');
            */

            $output = $result['output'];
            $rResult = $result['rResult'];
            $x = 0;

            foreach ($rResult as $aRow) {
                $row = array();
                for ($i = 0; $i < count($aColumns); $i++) {
                    if (strpos($aColumns[$i], 'as') !== false && !isset($aRow[$aColumns[$i]])) {
                        $_data = $aRow[strafter($aColumns[$i], 'as ')];
                    }  // end if
                    else {
                        $_data = $aRow[$aColumns[$i]];
                    }
                    if ($i == 0) {
                        $color = $this->invoices_model->get_client_link_color($aRow['userid']);
                        $_data = '<a href="' . admin_url('clients/client/' . $aRow['userid']) . '" style="color:' . $color . ';" target="_blank">' . $aRow['company'] . '</a>';
                    } // end if

                    if ($i == 2) {
                        $_data = $this->invoices_model->get_staff_name_by_client_id($aRow['userid']);
                    } // end if

                    elseif ($aColumns[$i] == $select[4] || $aColumns[$i] == $select[5]) {
                        if ($_data == null) {
                            $_data = 0;
                        }
                        $_data = format_money($_data, $currency_symbol);
                    }
                    $row[] = $_data;
                } // end for

                //$months_report = $this->input->post('report_months');
                //$status = $this->invoices_model->is_new_customer($aRow['userid'], $months_report);
                //if ($status > 0) {
                    $output['aaData'][] = $row;
                    $x++;
                //}

            } // end foreach
        } // end if $this->input->is_ajax_request()

        echo json_encode($output);
        die();
    }

    /**
     */
    public function payments_received()
    {
        if ($this->input->is_ajax_request()) {
            $this->load->model('currencies_model');
            $this->load->model('payment_modes_model');
            $this->load->model('invoices_model');

            $online_modes = $this->payment_modes_model->get_online_payment_modes(true);
            $select = array(
                'tblinvoicepaymentrecords.id',
                'tblinvoicepaymentrecords.date',
                'invoiceid',
                'CASE company WHEN "" THEN (SELECT CONCAT(firstname, " ", lastname) FROM tblcontacts WHERE userid = tblclients.userid and is_primary = 1) ELSE company END as company',
                'paymentmode',
                'transactionid',
                'note',
                'amount'
            );
            $where = array(
                'AND status != 5'
            );

            $custom_date_select = $this->get_where_report_period('tblinvoicepaymentrecords.date');
            if ($custom_date_select != '') {
                array_push($where, $custom_date_select);
            }

            $by_currency = $this->input->post('report_currency');
            if ($by_currency) {
                $currency = $this->currencies_model->get($by_currency);
                array_push($where, 'AND currency=' . $by_currency);
            } else {
                $currency = $this->currencies_model->get_base_currency();
            }

            $currency_symbol = $this->currencies_model->get_currency_symbol($currency->id);

            $aColumns = $select;
            $sIndexColumn = "id";
            $sTable = 'tblinvoicepaymentrecords';
            $join = array(
                'JOIN tblinvoices ON tblinvoices.id = tblinvoicepaymentrecords.invoiceid',
                'LEFT JOIN tblclients ON tblclients.userid = tblinvoices.clientid',
                'LEFT JOIN tblinvoicepaymentsmodes ON tblinvoicepaymentsmodes.id = tblinvoicepaymentrecords.paymentmode'
            );

            $result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, array(
                'number',
                'clientid',
                'tblinvoicepaymentsmodes.name',
                'tblinvoicepaymentsmodes.id as paymentmodeid',
                'paymentmethod'
            ));

            $output = $result['output'];
            $rResult = $result['rResult'];

            $footer_data['total_amount'] = 0;
            foreach ($rResult as $aRow) {
                $row = array();
                for ($i = 0; $i < count($aColumns); $i++) {
                    if (strpos($aColumns[$i], 'as') !== false && !isset($aRow[$aColumns[$i]])) {
                        $_data = $aRow[strafter($aColumns[$i], 'as ')];
                    } else {
                        $_data = $aRow[$aColumns[$i]];
                    }
                    if ($aColumns[$i] == 'paymentmode') {
                        $_data = $aRow['name'];
                        if (is_null($aRow['paymentmodeid'])) {
                            foreach ($online_modes as $online_mode) {
                                if ($aRow['paymentmode'] == $online_mode['id']) {
                                    $_data = $online_mode['name'];
                                }
                            }
                        }
                        if (!empty($aRow['paymentmethod'])) {
                            $_data .= ' - ' . $aRow['paymentmethod'];
                        }
                    } elseif ($aColumns[$i] == 'tblinvoicepaymentrecords.id') {
                        $_data = '<a href="' . admin_url('payments/payment/' . $_data) . '" target="_blank">' . $_data . '</a>';
                    } elseif ($aColumns[$i] == 'tblinvoicepaymentrecords.date') {
                        $_data = _d($_data);
                    } elseif ($aColumns[$i] == 'invoiceid') {
                        $_data = '<a href="' . admin_url('invoices/list_invoices/' . $aRow[$aColumns[$i]]) . '" target="_blank">' . format_invoice_number($aRow['invoiceid']) . '</a>';
                    } elseif ($i == 3) {
                        $color = $this->invoices_model->get_client_link_color($aRow['clientid']);
                        $_data = '<a href="' . admin_url('clients/client/' . $aRow['clientid']) . '" style="color:' . $color . ';" target="_blank">' . $aRow['company'] . '</a>';
                    } elseif ($aColumns[$i] == 'amount') {
                        $footer_data['total_amount'] += $_data;
                        $_data = format_money($_data, $currency_symbol);
                    }

                    $row[] = $_data;
                }
                $output['aaData'][] = $row;
            }

            $footer_data['total_amount'] = format_money($footer_data['total_amount'], $currency_symbol);
            $output['sums'] = $footer_data;
            echo json_encode($output);
            die();
        }
    }

    /**
     */
    public function proposals_report()
    {
        if ($this->input->is_ajax_request()) {
            $this->load->model('currencies_model');
            $this->load->model('proposals_model');
            $this->load->model('invoices_model');

            $proposalsTaxes = $this->distinct_taxes('proposal');
            $totalTaxesColumns = count($proposalsTaxes);

            $select = array(
                'id',
                'subject',
                'proposal_to',
                'date',
                'open_till',
                'subtotal',
                'total',
                'total_tax',
                'discount_total',
                'adjustment',
                'status'
            );

            $proposalsTaxesSelect = array_reverse($proposalsTaxes);

            foreach ($proposalsTaxesSelect as $key => $tax) {
                array_splice($select, 8, 0, '(
                    SELECT CASE
                    WHEN discount_percent != 0 AND discount_type = "before_tax" THEN ROUND(SUM((qty*rate/100*tblitemstax.taxrate) - (qty*rate/100*tblitemstax.taxrate * discount_percent/100)),' . get_decimal_places() . ')
                    ELSE ROUND(SUM(qty*rate/100*tblitemstax.taxrate),' . get_decimal_places() . ')
                    END
                    FROM tblitems_in
                    INNER JOIN tblitemstax ON tblitemstax.itemid=tblitems_in.id
                    WHERE tblitems_in.rel_type="proposal" AND taxname="' . $tax['taxname'] . '" AND taxrate="' . $tax['taxrate'] . '" AND tblitems_in.rel_id=tblproposals.id) as total_tax_single_' . $key);
            }

            $where = array();
            $custom_date_select = $this->get_where_report_period();
            if ($custom_date_select != '') {
                array_push($where, $custom_date_select);
            }

            if ($this->input->post('proposal_status')) {
                $statuses = $this->input->post('proposal_status');
                $_statuses = array();
                if (is_array($statuses)) {
                    foreach ($statuses as $status) {
                        if ($status != '') {
                            array_push($_statuses, $status);
                        }
                    }
                }
                if (count($_statuses) > 0) {
                    array_push($where, 'AND status IN (' . implode(', ', $_statuses) . ')');
                }
            }

            if ($this->input->post('proposals_sale_agents')) {
                $agents = $this->input->post('proposals_sale_agents');
                $_agents = array();
                if (is_array($agents)) {
                    foreach ($agents as $agent) {
                        if ($agent != '') {
                            array_push($_agents, $agent);
                        }
                    }
                }
                if (count($_agents) > 0) {
                    array_push($where, 'AND assigned IN (' . implode(', ', $_agents) . ')');
                }
            }

            $by_currency = $this->input->post('report_currency');
            if ($by_currency) {
                $currency = $this->currencies_model->get($by_currency);
                array_push($where, 'AND currency=' . $by_currency);
            } else {
                $currency = $this->currencies_model->get_base_currency();
            }

            $currency_symbol = $this->currencies_model->get_currency_symbol($currency->id);

            $aColumns = $select;
            $sIndexColumn = "id";
            $sTable = 'tblproposals';
            $join = array();

            $result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, array(
                'rel_id',
                'rel_type',
                'discount_percent'
            ));

            $output = $result['output'];
            $rResult = $result['rResult'];

            $footer_data = array(
                'total' => 0,
                'subtotal' => 0,
                'total_tax' => 0,
                'discount_total' => 0,
                'adjustment' => 0
            );

            foreach ($proposalsTaxes as $key => $tax) {
                $footer_data['total_tax_single_' . $key] = 0;
            }

            foreach ($rResult as $aRow) {
                $row = array();

                $row[] = '<a href="' . admin_url('proposals/list_proposals/' . $aRow['id']) . '" target="_blank">' . format_proposal_number($aRow['id']) . '</a>';

                $row[] = '<a href="' . admin_url('proposals/list_proposals/' . $aRow['id']) . '" target="_blank">' . $aRow['subject'] . '</a>';

                if ($aRow['rel_type'] == 'lead') {
                    $row[] = '<a href="#" onclick="init_lead(' . $aRow['rel_id'] . ');return false;" target="_blank" data-toggle="tooltip" data-title="' . _l('lead') . '">' . $aRow['proposal_to'] . '</a>' . '<span class="hide">' . _l('lead') . '</span>';
                } elseif ($aRow['rel_type'] == 'customer') {
                    $color = $this->invoices_model->get_client_link_color($aRow['rel_id']);
                    $row[] = '<a href="' . admin_url('clients/client/' . $aRow['rel_id']) . '" style="color:' . $color . ';" target="_blank" data-toggle="tooltip" data-title="' . _l('client') . '">' . $aRow['proposal_to'] . '</a>' . '<span class="hide">' . _l('client') . '</span>';
                } else {
                    $row[] = '';
                }

                $row[] = _d($aRow['date']);

                $row[] = _d($aRow['open_till']);

                $row[] = format_money($aRow['subtotal'], $currency_symbol);
                $footer_data['subtotal'] += $aRow['subtotal'];

                $row[] = format_money($aRow['total'], $currency_symbol);
                $footer_data['total'] += $aRow['total'];

                $row[] = format_money($aRow['total_tax'], $currency_symbol);
                $footer_data['total_tax'] += $aRow['total_tax'];

                $t = $totalTaxesColumns - 1;
                $i = 0;
                foreach ($proposalsTaxes as $tax) {
                    $row[] = format_money(($aRow['total_tax_single_' . $t] == null ? 0 : $aRow['total_tax_single_' . $t]), $currency_symbol);
                    $footer_data['total_tax_single_' . $i] += ($aRow['total_tax_single_' . $t] == null ? 0 : $aRow['total_tax_single_' . $t]);
                    $t--;
                    $i++;
                }

                $row[] = format_money($aRow['discount_total'], $currency_symbol);
                $footer_data['discount_total'] += $aRow['discount_total'];

                $row[] = format_money($aRow['adjustment'], $currency_symbol);
                $footer_data['adjustment'] += $aRow['adjustment'];

                $row[] = format_proposal_status($aRow['status']);
                $output['aaData'][] = $row;
            }

            foreach ($footer_data as $key => $total) {
                $footer_data[$key] = format_money($total, $currency_symbol);
            }

            $output['sums'] = $footer_data;
            echo json_encode($output);
            die();
        }
    }

    /**
     */
    public function estimates_report()
    {
        if ($this->input->is_ajax_request()) {
            $this->load->model('currencies_model');
            $this->load->model('estimates_model');
            $this->load->model('invoices_model');

            $estimateTaxes = $this->distinct_taxes('estimate');
            $totalTaxesColumns = count($estimateTaxes);

            $select = array(
                'number',
                'CASE company WHEN "" THEN (SELECT CONCAT(firstname, " ", lastname) FROM tblcontacts WHERE userid = tblclients.userid and is_primary = 1) ELSE company END as company',
                'invoiceid',
                'YEAR(date) as year',
                'date',
                'expirydate',
                'subtotal',
                'total',
                'total_tax',
                'discount_total',
                'adjustment',
                'reference_no',
                'status'
            );

            $estimatesTaxesSelect = array_reverse($estimateTaxes);

            foreach ($estimatesTaxesSelect as $key => $tax) {
                array_splice($select, 9, 0, '(
                    SELECT CASE
                    WHEN discount_percent != 0 AND discount_type = "before_tax" THEN ROUND(SUM((qty*rate/100*tblitemstax.taxrate) - (qty*rate/100*tblitemstax.taxrate * discount_percent/100)),' . get_decimal_places() . ')
                    ELSE ROUND(SUM(qty*rate/100*tblitemstax.taxrate),' . get_decimal_places() . ')
                    END
                    FROM tblitems_in
                    INNER JOIN tblitemstax ON tblitemstax.itemid=tblitems_in.id
                    WHERE tblitems_in.rel_type="estimate" AND taxname="' . $tax['taxname'] . '" AND taxrate="' . $tax['taxrate'] . '" AND tblitems_in.rel_id=tblestimates.id) as total_tax_single_' . $key);
            }

            $where = array();
            $custom_date_select = $this->get_where_report_period();
            if ($custom_date_select != '') {
                array_push($where, $custom_date_select);
            }

            if ($this->input->post('estimate_status')) {
                $statuses = $this->input->post('estimate_status');
                $_statuses = array();
                if (is_array($statuses)) {
                    foreach ($statuses as $status) {
                        if ($status != '') {
                            array_push($_statuses, $status);
                        }
                    }
                }
                if (count($_statuses) > 0) {
                    array_push($where, 'AND status IN (' . implode(', ', $_statuses) . ')');
                }
            }

            if ($this->input->post('sale_agent_estimates')) {
                $agents = $this->input->post('sale_agent_estimates');
                $_agents = array();
                if (is_array($agents)) {
                    foreach ($agents as $agent) {
                        if ($agent != '') {
                            array_push($_agents, $agent);
                        }
                    }
                }
                if (count($_agents) > 0) {
                    array_push($where, 'AND sale_agent IN (' . implode(', ', $_agents) . ')');
                }
            }

            $by_currency = $this->input->post('report_currency');
            if ($by_currency) {
                $currency = $this->currencies_model->get($by_currency);
                array_push($where, 'AND currency=' . $by_currency);
            } else {
                $currency = $this->currencies_model->get_base_currency();
            }
            $currency_symbol = $this->currencies_model->get_currency_symbol($currency->id);

            $aColumns = $select;
            $sIndexColumn = "id";
            $sTable = 'tblestimates';
            $join = array(
                'JOIN tblclients ON tblclients.userid = tblestimates.clientid'
            );

            $result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, array(
                'userid',
                'clientid',
                'tblestimates.id',
                'discount_percent'
            ));

            $output = $result['output'];
            $rResult = $result['rResult'];

            $footer_data = array(
                'total' => 0,
                'subtotal' => 0,
                'total_tax' => 0,
                'discount_total' => 0,
                'adjustment' => 0
            );

            foreach ($estimateTaxes as $key => $tax) {
                $footer_data['total_tax_single_' . $key] = 0;
            }

            foreach ($rResult as $aRow) {
                $row = array();

                $row[] = '<a href="' . admin_url('estimates/list_estimates/' . $aRow['id']) . '" target="_blank">' . format_estimate_number($aRow['id']) . '</a>';

                $color = $this->invoices_model->get_client_link_color($aRow['userid']);
                $row[] = '<a href="' . admin_url('clients/client/' . $aRow['userid']) . '" style="color:' . $color . ';" target="_blank">' . $aRow['company'] . '</a>';

                if ($aRow['invoiceid'] === null) {
                    $row[] = '';
                } else {
                    $row[] = '<a href="' . admin_url('invoices/list_invoices/' . $aRow['invoiceid']) . '" target="_blank">' . format_invoice_number($aRow['invoiceid']) . '</a>';
                }

                $row[] = $aRow['year'];

                $row[] = _d($aRow['date']);

                $row[] = _d($aRow['expirydate']);

                $row[] = format_money($aRow['subtotal'], $currency_symbol);
                $footer_data['subtotal'] += $aRow['subtotal'];

                $row[] = format_money($aRow['total'], $currency_symbol);
                $footer_data['total'] += $aRow['total'];

                $row[] = format_money($aRow['total_tax'], $currency_symbol);
                $footer_data['total_tax'] += $aRow['total_tax'];

                $t = $totalTaxesColumns - 1;
                $i = 0;
                foreach ($estimateTaxes as $tax) {
                    $row[] = format_money(($aRow['total_tax_single_' . $t] == null ? 0 : $aRow['total_tax_single_' . $t]), $currency_symbol);
                    $footer_data['total_tax_single_' . $i] += ($aRow['total_tax_single_' . $t] == null ? 0 : $aRow['total_tax_single_' . $t]);
                    $t--;
                    $i++;
                }

                $row[] = format_money($aRow['discount_total'], $currency_symbol);
                $footer_data['discount_total'] += $aRow['discount_total'];

                $row[] = format_money($aRow['adjustment'], $currency_symbol);
                $footer_data['adjustment'] += $aRow['adjustment'];

                $row[] = $aRow['reference_no'];

                $row[] = format_estimate_status($aRow['status']);

                $output['aaData'][] = $row;
            }
            foreach ($footer_data as $key => $total) {
                $footer_data[$key] = format_money($total, $currency_symbol);
            }
            $output['sums'] = $footer_data;
            echo json_encode($output);
            die();
        }
    }

    /**
     *
     * @param string $field
     * @return string
     */
    private function get_where_report_period($field = 'date')
    {
        $months_report = $this->input->post('report_months');
        $custom_date_select = '';
        if ($months_report != '') {
            if (is_numeric($months_report)) {
                // Last month
                if ($months_report == '1') {
                    $beginMonth = date('Y-m-01', strtotime("-$months_report MONTH"));
                    $endMonth = date('Y-m-t', strtotime('-1 MONTH'));
                } // end if

                else {
                    $months_report = (int)$months_report;
                    $months_report--;
                    $beginMonth = date('Y-m-01', strtotime("-$months_report MONTH"));
                    $endMonth = date('Y-m-t');
                } // end else
                $custom_date_select = 'AND (' . $field . ' BETWEEN "' . $beginMonth . '" AND "' . $endMonth . '")';
            } // end if

            elseif ($months_report == 'this_month') {
                $custom_date_select = 'AND (' . $field . ' BETWEEN "' . date('Y-m-01') . '" AND "' . date('Y-m-t') . '")';
            } // else if

            elseif ($months_report == 'this_year') {
                $custom_date_select = 'AND (' . $field . ' BETWEEN "' . date('Y-m-d', strtotime(date('Y-01-01'))) . '" AND "' . date('Y-m-d', strtotime(date('Y-12-' . date('d', strtotime('last day of this year'))))) . '")';
            } elseif ($months_report == 'last_year') {
                $custom_date_select = 'AND (' . $field . ' BETWEEN "' . date('Y-m-d', strtotime(date(date('Y', strtotime('last year')) . '-01-01'))) . '" AND "' . date('Y-m-d', strtotime(date(date('Y', strtotime('last year')) . '-12-' . date('d', strtotime('last day of last year'))))) . '")';
            } // end elseif

            elseif ($months_report == 'custom') {
                $from_date = to_sql_date($this->input->post('report_from'));
                $to_date = to_sql_date($this->input->post('report_to'));
                if ($from_date == $to_date) {
                    $custom_date_select = 'AND ' . $field . ' = "' . $from_date . '"';
                } // end if
                else {
                    $custom_date_select = 'AND (' . $field . ' BETWEEN "' . $from_date . '" AND "' . $to_date . '")';
                } // end else
            } // end else if
        } // end if

        return $custom_date_select;
    }

    /*
     *
     */
    /**
     *
     */
    public function items()
    {
        if ($this->input->is_ajax_request()) {

            $this->load->model('currencies_model');
            $this->load->model('invoices_model');

            $aColumns = array(
                'description as item',
                'tblitems_in.qty as total',
                'rate as rate',
                'rate as avg_price',
                'tblinvoices.date',
                'tblinvoices.cleintid',
                'tblinvoices.addedfrom'
            );

            $sIndexColumn = "id";
            $sTable = 'tblitems_in';

            $join = array(
                'JOIN tblinvoices           ON tblinvoices.id = tblitems_in.rel_id',
                'JOIN tblclients            ON tblclients.userid=tblinvoices.clientid',
                'JOIN tblcustomfieldsvalues ON (tblcustomfieldsvalues.fieldid=1 and tblcustomfieldsvalues.relid=tblinvoices.clientid)'
            );

            $additionalColumns = array(
                'tblitems_in.rel_id',
                'tblclients.userid',
                'tblinvoices.id',
                'tblinvoices.clientid',
                'tblinvoices.addedfrom',
                'tblcustomfieldsvalues.relid',
                'tblcustomfieldsvalues.value'
            );

            $where = array(
                'AND rel_type="invoice"',
                'AND status != 5'
            );

            $custom_date_select = $this->get_where_report_period();
            if ($custom_date_select != '') {
                array_push($where, $custom_date_select);
            }

            $by_currency = $this->input->post('report_currency');
            if ($by_currency) {
                $currency = $this->currencies_model->get($by_currency);
                array_push($where, 'AND currency=' . $by_currency);
            } else {
                $currency = $this->currencies_model->get_base_currency();
            }

            if ($this->input->post('sale_agent_items')) {
                $agents = $this->input->post('sale_agent_items');
                $_agents = array();
                if (is_array($agents)) {
                    foreach ($agents as $agent) {
                        if ($agent != '') {
                            array_push($_agents, $agent);
                        }
                    }
                }
                if (count($_agents) > 0) {
                    array_push($where, 'AND sale_agent IN (' . implode(', ', $_agents) . ')');
                }
            }

            if ($this->input->post('item_products')) {
                $product = $this->input->post('item_products');
                if ($product != '') {
                    array_push($where, "AND tblitems_in.description='$product'");
                }
            }

            if ($this->input->post('i_regions')) {
                $regions = $this->input->post('i_regions');
                $s_regions = array();
                if (is_array($regions)) {
                    foreach ($regions as $r) {
                        if ($r != '') {
                            $clients = $this->invoices_model->get_clientid_by_region(trim($r));
                            if (count($clients) > 0) {
                                foreach ($clients as $clientid) {
                                    array_push($s_regions, $clientid);
                                } // end if count($clients)>0
                            } // end if $r != ''
                        } // end if $r != ''
                    } // end foreach
                    if (count($s_regions) > 0) {
                        array_push($where, 'AND tblinvoices.clientid IN (' . implode(', ', $s_regions) . ')');
                    } // end if count($s_regions) > 0
                } // end if is_array($regions)
            }

            if ($this->input->post('i_employees')) {
                $employees = $this->input->post('i_employees');
                $s_employees = array();
                if (is_array($employees)) {
                    foreach ($employees as $e) {
                        if ($e > 0) {
                            array_push($s_employees, $e);
                        }
                    }
                }
                if (count($s_employees) > 0) {
                    array_push($where, 'AND tblinvoices.addedfrom IN (' . implode(', ', $s_employees) . ')');
                }
            }

            $currency_symbol = $this->currencies_model->get_currency_symbol($currency->id);
            $result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, $additionalColumns);

            $output = $result['output'];
            $rResult = $result['rResult'];

            $footer_data = array(
                'total_amount' => 0,
                'total_qty' => 0
            );

            foreach ($rResult as $aRow) {
                $row = array();
                $employee = $this->invoices_model->get_staff_name_by_id($aRow['addedfrom']);
                $row[] = $aRow['item'];
                $row[] = $aRow['total'];
                $row[] = $aRow['tblinvoices.date'];
                $row[] = $aRow['value'];
                $row[] = $employee;
                $footer_data['total_qty'] += $aRow['total'];
                $output['aaData'][] = $row;
            }

            $footer_data['total_amount'] = '';

            $output['sums'] = $footer_data;
            echo json_encode($output);
            die();

        } // end if $this->input
    }

    /**
     */
    public function invoices_report()
    {
        if ($this->input->is_ajax_request()) {
            $invoice_taxes = $this->distinct_taxes('invoice');
            $totalTaxesColumns = count($invoice_taxes);

            $this->load->model('currencies_model');
            $this->load->model('invoices_model');

            $select = array(
                'number',
                'CASE company WHEN "" THEN (SELECT CONCAT(firstname, " ", lastname) FROM tblcontacts WHERE userid = tblclients.userid and is_primary = 1) ELSE company END as company',
                'value',
                'firstname',
                'lastname',
                'YEAR(date) as year',
                'date',
                'duedate',
                'subtotal',
                'total',
                'total_tax',
                'discount_total',
                'adjustment',
                '(SELECT SUM(amount) FROM tblinvoicepaymentrecords WHERE invoiceid = tblinvoices.id)',
                'status'
            );
            $where = array(
                'AND status != 5'
            );

            $invoiceTaxesSelect = array_reverse($invoice_taxes);

            foreach ($invoiceTaxesSelect as $key => $tax) {
                array_splice($select, 8, 0, '(
                    SELECT CASE
                    WHEN discount_percent != 0 AND discount_type = "before_tax" THEN ROUND(SUM((qty*rate/100*tblitemstax.taxrate) - (qty*rate/100*tblitemstax.taxrate * discount_percent/100)),' . get_decimal_places() . ')
                    ELSE ROUND(SUM(qty*rate/100*tblitemstax.taxrate),' . get_decimal_places() . ')
                    END
                    FROM tblitems_in
                    INNER JOIN tblitemstax ON tblitemstax.itemid=tblitems_in.id
                    WHERE tblitems_in.rel_type="invoice" AND taxname="' . $tax['taxname'] . '" AND taxrate="' . $tax['taxrate'] . '" AND tblitems_in.rel_id=tblinvoices.id) as total_tax_single_' . $key);
            }

            $custom_date_select = $this->get_where_report_period();
            if ($custom_date_select != '') {
                array_push($where, $custom_date_select);
            }

            if ($this->input->post('regions')) {
                $regions = $this->input->post('regions');
                $s_regions = array();
                if (is_array($regions)) {
                    foreach ($regions as $r) {
                        if ($r != '') {
                            $clients = $this->invoices_model->get_clientid_by_region(trim($r));
                            if (count($clients) > 0) {
                                foreach ($clients as $clientid) {
                                    array_push($s_regions, $clientid);
                                } // end if count($clients)>0
                            } // end if $r != ''
                        } // end if $r != ''
                    } // end foreach
                    if (count($s_regions) > 0) {
                        array_push($where, 'AND clientid IN (' . implode(', ', $s_regions) . ')');
                    } // end if count($s_regions) > 0
                } // end if is_array($regions)
            }

            if ($this->input->post('employees')) {
                $employees = $this->input->post('employees');
                $s_employees = array();
                if (is_array($employees)) {
                    foreach ($employees as $e) {
                        if ($e > 0) {
                            array_push($s_employees, $e);
                        }
                    }
                }
                if (count($s_employees) > 0) {
                    array_push($where, 'AND addedfrom IN (' . implode(', ', $s_employees) . ')');
                }
            }

            if ($this->input->post('sale_agent_invoices')) {
                $agents = $this->input->post('sale_agent_invoices');
                $_agents = array();
                if (is_array($agents)) {
                    foreach ($agents as $agent) {
                        if ($agent != '') {
                            array_push($_agents, $agent);
                        }
                    }
                }
                if (count($_agents) > 0) {
                    array_push($where, 'AND sale_agent IN (' . implode(', ', $_agents) . ')');
                }
            }

            $by_currency = $this->input->post('report_currency');
            $totalPaymentsColumnIndex = (11 + $totalTaxesColumns - 1);

            if ($by_currency) {
                $_temp = substr($select[$totalPaymentsColumnIndex], 0, -1);
                $_temp .= ' AND currency =' . $by_currency . ') as total_payments';
                $select[$totalPaymentsColumnIndex] = $_temp;

                $currency = $this->currencies_model->get($by_currency);
                array_push($where, 'AND currency=' . $by_currency);
            } else {
                $currency = $this->currencies_model->get_base_currency();
                $select[$totalPaymentsColumnIndex] = $select[$totalPaymentsColumnIndex] .= ' as total_payments';
            }
            $currency_symbol = $this->currencies_model->get_currency_symbol($currency->id);

            if ($this->input->post('invoice_status')) {
                $statuses = $this->input->post('invoice_status');
                $_statuses = array();
                if (is_array($statuses)) {
                    foreach ($statuses as $status) {
                        if ($status != '') {
                            array_push($_statuses, $status);
                        }
                    }
                }
                if (count($_statuses) > 0) {
                    array_push($where, 'AND status IN (' . implode(', ', $_statuses) . ')');
                }
            }

            $aColumns = $select;
            $sIndexColumn = "id";
            $sTable = 'tblinvoices';
            $join = array(
                'JOIN tblclients ON tblclients.userid = tblinvoices.clientid',
                'JOIN tblcustomfieldsvalues ON (tblcustomfieldsvalues.fieldid=1 and tblcustomfieldsvalues.relid=tblinvoices.clientid)',
                'JOIN tblstaff ON tblstaff.staffid=tblinvoices.addedfrom'
            );

            $result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, array(
                'userid',
                'clientid',
                'tblinvoices.id',
                'tblstaff.staffid',
                'tblinvoices.addedfrom',
                'tblcustomfieldsvalues.relid',
                'discount_percent'
            ));

            $output = $result['output'];
            $rResult = $result['rResult'];

            $footer_data = array(
                'total' => 0,
                'subtotal' => 0,
                'total_tax' => 0,
                'discount_total' => 0,
                'adjustment' => 0,
                'amount_open' => 0
            );

            foreach ($invoice_taxes as $key => $tax) {
                $footer_data['total_tax_single_' . $key] = 0;
            }

            foreach ($rResult as $aRow) {
                $row = array();

                $row[] = '<a href="' . admin_url('invoices / list_invoices / ' . $aRow['id']) . '" target="_blank">' . format_invoice_number($aRow['id']) . '</a>';

                $color = $this->invoices_model->get_client_link_color($aRow['userid']);
                $row[] = '<a href="' . admin_url('clients / client / ' . $aRow['userid']) . '" style="color:' . $color . ';" target="_blank">' . $aRow['company'] . '</a>';

                $row[] = $aRow['value'];

                $row[] = $aRow['firstname'] . ' ' . $aRow['lastname'];

                $row[] = $aRow['year'];

                $row[] = _d($aRow['date']);

                $row[] = _d($aRow['duedate']);

                $row[] = format_money($aRow['subtotal'], $currency_symbol);
                $footer_data['subtotal'] += $aRow['subtotal'];

                $row[] = format_money($aRow['total'], $currency_symbol);
                $footer_data['total'] += $aRow['total'];

                $row[] = format_money($aRow['total_tax'], $currency_symbol);
                $footer_data['total_tax'] += $aRow['total_tax'];

                $t = $totalTaxesColumns - 1;
                $i = 0;
                foreach ($invoice_taxes as $tax) {
                    $row[] = format_money(($aRow['total_tax_single_' . $t] == null ? 0 : $aRow['total_tax_single_' . $t]), $currency_symbol);
                    $footer_data['total_tax_single_' . $i] += ($aRow['total_tax_single_' . $t] == null ? 0 : $aRow['total_tax_single_' . $t]);
                    $t--;
                    $i++;
                }

                $row[] = format_money($aRow['discount_total'], $currency_symbol);
                $footer_data['discount_total'] += $aRow['discount_total'];

                $row[] = format_money($aRow['adjustment'], $currency_symbol);
                $footer_data['adjustment'] += $aRow['adjustment'];

                $amountOpen = $aRow['total'] - $aRow['total_payments'];
                $row[] = format_money($amountOpen, $currency_symbol);
                $footer_data['amount_open'] += $amountOpen;

                $row[] = format_invoice_status($aRow['status']);

                $output['aaData'][] = $row;
            }

            foreach ($footer_data as $key => $total) {
                $footer_data[$key] = format_money($total, $currency_symbol);
            }

            $output['sums'] = $footer_data;
            echo json_encode($output);
            die();
        }
    }

    /**
     *
     * @param string $type
     */
    public function expenses($type = 'simple_report')
    {
        $this->load->model('currencies_model');
        $data['base_currency'] = $this->currencies_model->get_base_currency();
        $data['currencies'] = $this->currencies_model->get();

        $data['title'] = _l('expenses_report');
        if ($type != 'simple_report') {
            $this->load->model('expenses_model');
            $data['categories'] = $this->expenses_model->get_category();
            $data['years'] = $this->expenses_model->get_expenses_years();

            if ($this->input->is_ajax_request()) {
                $aColumns = array(
                    'category',
                    'amount',
                    'tax',
                    'tax2',
                    '(SELECT taxrate FROM tbltaxes WHERE id=tblexpenses.tax)',
                    'amount as amount_with_tax',
                    'billable',
                    'date',
                    'CASE company WHEN "" THEN (SELECT CONCAT(firstname, " ", lastname) FROM tblcontacts WHERE userid = tblclients.userid and is_primary = 1) ELSE company END as company',
                    'invoiceid',
                    'reference_no',
                    'paymentmode'
                );
                $join = array(
                    'LEFT JOIN tblclients ON tblclients.userid = tblexpenses.clientid',
                    'LEFT JOIN tblexpensescategories ON tblexpensescategories.id = tblexpenses.category'
                );
                $where = array();
                $filter = array();
                include_once(APPPATH . 'views/admin/tables/includes/expenses_filter.php');
                if (count($filter) > 0) {
                    array_push($where, 'AND (' . prepare_dt_filter($filter) . ')');
                }

                $by_currency = $this->input->post('currency');
                if ($by_currency) {
                    $currency = $this->currencies_model->get($by_currency);
                    array_push($where, 'AND currency=' . $by_currency);
                } else {
                    $currency = $this->currencies_model->get_base_currency();
                }
                $currency_symbol = $this->currencies_model->get_currency_symbol($currency->id);

                $sIndexColumn = "id";
                $sTable = 'tblexpenses';
                $result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, array(
                    'tblexpensescategories.name as category_name',
                    'tblexpenses.id',
                    'tblexpenses.clientid',
                    'currency'
                ));
                $output = $result['output'];
                $rResult = $result['rResult'];
                $this->load->model('currencies_model');
                $this->load->model('payment_modes_model');

                $footer_data = array(
                    'amount' => 0,
                    'total_tax' => 0,
                    'amount_with_tax' => 0
                );

                foreach ($rResult as $aRow) {
                    $row = array();
                    for ($i = 0; $i < count($aColumns); $i++) {
                        if (strpos($aColumns[$i], 'as') !== false && !isset($aRow[$aColumns[$i]])) {
                            $_data = $aRow[strafter($aColumns[$i], 'as ')];
                        } else {
                            $_data = $aRow[$aColumns[$i]];
                        }
                        if ($aRow['tax'] != 0) {
                            $_tax = get_tax_by_id($aRow['tax']);
                        }
                        if ($aRow['tax2'] != 0) {
                            $_tax2 = get_tax_by_id($aRow['tax2']);
                        }
                        if ($aColumns[$i] == 'category') {
                            $_data = '<a href="' . admin_url('expenses / list_expenses / ' . $aRow['id']) . '" target="_blank">' . $aRow['category_name'] . '</a>';
                        } elseif ($aColumns[$i] == 'amount' || $i == 5) {
                            $total = $_data;
                            if ($i != 5) {
                                $footer_data['amount'] += $total;
                            } else {
                                if ($aRow['tax'] != 0 && $i == 5) {
                                    $total += ($total / 100 * $_tax->taxrate);
                                }
                                if ($aRow['tax2'] != 0 && $i == 5) {
                                    $total += ($aRow['amount'] / 100 * $_tax2->taxrate);
                                }
                                $footer_data['amount_with_tax'] += $total;
                            }

                            $_data = format_money($total, $currency_symbol);
                        } elseif ($i == 8) {
                            $_data = '<a href="' . admin_url('clients / client / ' . $aRow['clientid']) . '">' . $aRow['company'] . '</a>';
                        } elseif ($aColumns[$i] == 'paymentmode') {
                            $_data = '';
                            if ($aRow['paymentmode'] != '0' && !empty($aRow['paymentmode'])) {
                                $payment_mode = $this->payment_modes_model->get($aRow['paymentmode'], array(), false, true);
                                if ($payment_mode) {
                                    $_data = $payment_mode->name;
                                }
                            }
                        } elseif ($aColumns[$i] == 'date') {
                            $_data = _d($_data);
                        } elseif ($aColumns[$i] == 'tax') {
                            if ($aRow['tax'] != 0) {
                                $_data = $_tax->name . ' - ' . _format_number($_tax->taxrate) . '%';
                            } else {
                                $_data = '';
                            }
                        } elseif ($aColumns[$i] == 'tax2') {
                            if ($aRow['tax2'] != 0) {
                                $_data = $_tax2->name . ' - ' . _format_number($_tax2->taxrate) . '%';
                            } else {
                                $_data = '';
                            }
                        } elseif ($i == 4) {
                            if ($aRow['tax'] != 0 || $aRow['tax2'] != 0) {
                                if ($aRow['tax'] != 0) {
                                    $total = ($total / 100 * $_tax->taxrate);
                                }
                                if ($aRow['tax2'] != 0) {
                                    $total += ($aRow['amount'] / 100 * $_tax2->taxrate);
                                }
                                $_data = format_money($total, $currency_symbol);
                                $footer_data['total_tax'] += $total;
                            } else {
                                $_data = _format_number(0);
                            }
                        } elseif ($aColumns[$i] == 'billable') {
                            if ($aRow['billable'] == 1) {
                                $_data = _l('expenses_list_billable');
                            } else {
                                $_data = _l('expense_not_billable');
                            }
                        } elseif ($aColumns[$i] == 'invoiceid') {
                            if ($_data) {
                                $_data = '<a href="' . admin_url('invoices / list_invoices / ' . $_data) . '">' . format_invoice_number($_data) . '</a>';
                            } else {
                                $_data = '';
                            }
                        }
                        $row[] = $_data;
                    }
                    $output['aaData'][] = $row;
                }

                foreach ($footer_data as $key => $total) {
                    $footer_data[$key] = format_money($total, $currency_symbol);
                }

                $output['sums'] = $footer_data;
                echo json_encode($output);
                die();
            }
            $this->load->view('admin/reports/expenses_detailed', $data);
        } else {
            if (!$this->input->get('year')) {
                $data['current_year'] = date('Y');
            } else {
                $data['current_year'] = $this->input->get('year');
            }

            $data['export_not_supported'] = ($this->agent->browser() == 'Internet Explorer' || $this->agent->browser() == 'Spartan');

            $this->load->model('expenses_model');

            $data['chart_not_billable'] = json_encode($this->reports_model->get_stats_chart_data(_l('not_billable_expenses_by_categories'), array(
                'billable' => 0
            ), array(
                'backgroundColor' => 'rgba(252,45,66,0.4)',
                'borderColor' => '#fc2d42'
            ), $data['current_year']));

            $data['chart_billable'] = json_encode($this->reports_model->get_stats_chart_data(_l('billable_expenses_by_categories'), array(
                'billable' => 1
            ), array(
                'backgroundColor' => 'rgba(37,155,35,0.2)',
                'borderColor' => '#84c529'
            ), $data['current_year']));

            $data['expense_years'] = $this->expenses_model->get_expenses_years();
            $data['categories'] = $this->expenses_model->get_category();

            $this->load->view('admin/reports/expenses', $data);
        }
    }

    /**
     *
     * @param string $year
     */
    public function expenses_vs_income($year = '')
    {
        $_expenses_years = array();
        $_years = array();
        $this->load->model('expenses_model');
        $expenses_years = $this->expenses_model->get_expenses_years();
        $payments_years = $this->reports_model->get_distinct_payments_years();
        foreach ($expenses_years as $y) {
            array_push($_years, $y['year']);
        }
        foreach ($payments_years as $y) {
            array_push($_years, $y['year']);
        }
        $_years = array_map("unserialize", array_unique(array_map("serialize", $_years)));
        $data['years'] = $_years;
        $data['chart_expenses_vs_income_values'] = json_encode($this->reports_model->get_expenses_vs_income_report($year));
        $data['title'] = _l('als_expenses_vs_income');
        $this->load->view('admin/reports/expenses_vs_income', $data);
    }

    /* Total income report / ajax chart */

    /**
     */
    public function total_income_report()
    {
        echo json_encode($this->reports_model->total_income_report());
    }

    /**
     */
    public function report_by_payment_modes()
    {
        echo json_encode($this->reports_model->report_by_payment_modes());
    }

    /**
     */
    public function report_by_customer_groups()
    {
        echo json_encode($this->reports_model->report_by_customer_groups());
    }

    /* Leads conversion monthly report / ajax chart */

    /**
     *
     * @param
     *            $month
     */
    public function leads_monthly_report($month)
    {
        echo json_encode($this->reports_model->leads_monthly_report($month));
    }

    /**
     *
     * @param
     *            $rel_type
     * @return mixed
     */
    private function distinct_taxes($rel_type)
    {
        return $this->db->query("SELECT DISTINCT taxname,taxrate FROM tblitemstax WHERE rel_type = '" . $rel_type . "' ORDER BY taxname ASC")->result_array();
    }
}
