<?php
defined('BASEPATH') or exit('No direct script access allowed');

$hasPermissionDelete = has_permission('payments', '', 'delete');
$CI = &get_instance();
$CI->load->helper('perfex_misc_helper');
$item = $_SESSION['item'];
$staffid = $_SESSION['staff_user_id'];
$this->_instance->load->model('roles_model');
$roleid = $this->_instance->roles_model->get_current_user_role($staffid);

$aColumns = array(
    'tblinvoicepaymentrecords.id as id',
    'invoiceid',
    'paymentmode',
    'transactionid',
    'CASE company WHEN "" THEN (SELECT CONCAT(firstname, " ", lastname) FROM tblcontacts WHERE userid = tblclients.userid and is_primary = 1) ELSE company END as company',
    'amount',
    'tblinvoicepaymentrecords.date as date'
);

$join = array(
    'LEFT JOIN tblinvoices ON tblinvoices.id = tblinvoicepaymentrecords.invoiceid',
    'LEFT JOIN tblclients ON tblclients.userid = tblinvoices.clientid',
    'LEFT JOIN tblcurrencies ON tblcurrencies.id = tblinvoices.currency',
    'LEFT JOIN tblinvoicepaymentsmodes ON tblinvoicepaymentsmodes.id = tblinvoicepaymentrecords.paymentmode'
);

$where = array();
if (is_numeric($clientid)) {
    array_push($where, 'AND tblclients.userid=' . $clientid);
}

if (!has_permission('payments', '', 'view')) {
    array_push($where, 'AND invoiceid IN (SELECT id FROM tblinvoices WHERE addedfrom=' . get_staff_user_id() . ')');
}

$sIndexColumn = "id";
$sTable = 'tblinvoicepaymentrecords';

$result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, array(
    'clientid',
    'symbol',
    'tblinvoicepaymentsmodes.name as payment_mode_name',
    'tblinvoicepaymentsmodes.id as paymentmodeid',
    'paymentmethod'
));

$output = $result['output'];
$rResult = $result['rResult'];

$this->_instance->load->model('payment_modes_model');


$online_modes = $this->_instance->payment_modes_model->get_online_payment_modes(true);

foreach ($rResult as $aRow) {

    $row = array();
    switch ($roleid) {
        case 1:
            $status = $this->_instance->roles_model->is_my_client($aRow['clientid'], $staffid);
            if ($status) {
                $row[] = '<a href="' . admin_url('payments/payment/' . $aRow['id']) . '">' . $aRow['id'] . '</a>';
                $row[] = '<a href="' . admin_url('invoices/list_invoices/' . $aRow['invoiceid']) . '">' . format_invoice_number($aRow['invoiceid']) . '</a>';
            } // end if
            else {
                $row[] = '<a href="#" onclick="return false;">' . $aRow['id'] . '</a>';
                $row[] = '<a href="#" onclick="return false;">' . format_invoice_number($aRow['invoiceid']) . '</a>';
            }
            break;
        case 3:
            $teamname = $this->_instance->roles_model->get_user_team($staffid);
            $status = $this->_instance->roles_model->is_team_client($aRow['clientid'], $teamname);
            if ($status) {
                $row[] = '<a href="' . admin_url('payments/payment/' . $aRow['id']) . '">' . $aRow['id'] . '</a>';
                $row[] = '<a href="' . admin_url('invoices/list_invoices/' . $aRow['invoiceid']) . '">' . format_invoice_number($aRow['invoiceid']) . '</a>';
            } // end if
            else {
                $row[] = '<a href="#" onclick="return false;">' . $aRow['id'] . '</a>';
                $row[] = '<a href="#" onclick="return false;">' . format_invoice_number($aRow['invoiceid']) . '</a>';
            }
            break;
        default:
            $row[] = '<a href="' . admin_url('payments/payment/' . $aRow['id']) . '">' . $aRow['id'] . '</a>';
            $row[] = '<a href="' . admin_url('invoices/list_invoices/' . $aRow['invoiceid']) . '">' . format_invoice_number($aRow['invoiceid']) . '</a>';
    }


    $outputPaymentMode = $aRow['payment_mode_name'];
    // Since version 1.0.1
    if (is_null($aRow['paymentmodeid'])) {
        foreach ($online_modes as $online_mode) {
            if ($aRow['paymentmode'] == $online_mode['id']) {
                $outputPaymentMode = $online_mode['name'];
            }
        }
    }
    if (!empty($aRow['paymentmethod'])) {
        $outputPaymentMode .= ' - ' . $aRow['paymentmethod'];
    }
    $row[] = $outputPaymentMode;

    $row[] = $aRow['transactionid'];

    $color = get_client_link_color($aRow['clientid']);
    switch ($roleid) {
        case 1:
            $status = $this->_instance->roles_model->is_my_client($aRow['clientid'], $staffid);
            if ($status) {
                $row[] = '<a href="' . admin_url('clients/client/' . $aRow['clientid']) . '" style="color:' . $color . ';">' . $aRow['company'] . '</a>';
            } // end if
            else {
                $row[] = '<a href="#" onclick="return false" style="color:' . $color . ';">' . $aRow['company'] . '</a>';
            }
            break;
        case 3:
            $teamname = $this->_instance->roles_model->get_user_team($staffid);
            $status = $this->_instance->roles_model->is_team_client($aRow['clientid'], $teamname);
            if ($status) {
                $row[] = '<a href="' . admin_url('clients/client/' . $aRow['clientid']) . '" style="color:' . $color . ';">' . $aRow['company'] . '</a>';
            } // end if
            else {
                $row[] = '<a href="#" onclick="return false" style="color:' . $color . ';">' . $aRow['company'] . '</a>';
            }
            break;
        default:
            $row[] = '<a href="' . admin_url('clients/client/' . $aRow['clientid']) . '" style="color:' . $color . ';">' . $aRow['company'] . '</a>';
    }

    $row[] = format_money($aRow['amount'], $aRow['symbol']);

    $row[] = _d($aRow['date']);


    switch ($roleid) {
        case 1:
            $status = $this->_instance->roles_model->is_my_client($aRow['clientid'], $staffid);
            if ($status) {
                $options = icon_btn('payments/payment/' . $aRow['id'], 'pencil-square-o');
                if ($hasPermissionDelete) {
                    $options .= icon_btn('payments/delete/' . $aRow['id'], 'remove', 'btn-danger _delete');
                }
            } // end if
            else {
                $options = "";
                if ($hasPermissionDelete) {
                    $options .= "";
                }
            } // end else
            break;
        case 3:
            $teamname = $this->_instance->roles_model->get_user_team($staffid);
            $status = $this->_instance->roles_model->is_team_client($aRow['clientid'], $teamname);
            if ($status) {
                $options = icon_btn('payments/payment/' . $aRow['id'], 'pencil-square-o');
                if ($hasPermissionDelete) {
                    $options .= icon_btn('payments/delete/' . $aRow['id'], 'remove', 'btn-danger _delete');
                }
            } // end if
            else {
                $options = "";
                if ($hasPermissionDelete) {
                    $options .= "";
                }
            } // end else
            break;
        default:
            $options = icon_btn('payments/payment/' . $aRow['id'], 'pencil-square-o');
            if ($hasPermissionDelete) {
                $options .= icon_btn('payments/delete/' . $aRow['id'], 'remove', 'btn-danger _delete');
            }
    } // end of switch

    $row[] = $options;
    $output['aaData'][] = $row;
}
