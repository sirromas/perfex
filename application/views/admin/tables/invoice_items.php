<?php
defined('BASEPATH') or exit('No direct script access allowed');
$staffid = $_SESSION['staff_user_id'];
$this->_instance->load->model('roles_model');
$roleid = $this->_instance->roles_model->get_current_user_role($staffid);

$aColumns = array(
    'description',
    'long_description',
    'tblitems.rate',
    't1.taxrate as taxrate_1',
    't2.taxrate as taxrate_2',
    'unit',
    'tblitems_groups.name'
);
$sIndexColumn = "id";
$sTable = 'tblitems';

$join = array(
    'LEFT JOIN tbltaxes t1 ON t1.id = tblitems.tax',
    'LEFT JOIN tbltaxes t2 ON t2.id = tblitems.tax2',
    'LEFT JOIN tblitems_groups ON tblitems_groups.id = tblitems.group_id'
);
$additionalSelect = array(
    'tblitems.id',
    't1.name as taxname_1',
    't2.name as taxname_2',
    't1.id as tax_id_1',
    't2.id as tax_id_2',
    'group_id'
);
$result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, array(), $additionalSelect);
$output = $result['output'];
$rResult = $result['rResult'];

foreach ($rResult as $aRow) {

    $row = array();
    for ($i = 0; $i < count($aColumns); $i++) {

        if (strpos($aColumns[$i], 'as') !== false && !isset($aRow[$aColumns[$i]])) {
            $_data = $aRow[strafter($aColumns[$i], 'as ')];
        } // end if
        else {
            $_data = $aRow[$aColumns[$i]];
        } // end else

        if ($aColumns[$i] == 't1.taxrate as taxrate_1') {
            if (!$aRow['taxrate_1']) {
                $aRow['taxrate_1'] = 0;
            }
            $_data = '<span data-toggle="tooltip" title="' . $aRow['taxname_1'] . '" data-taxid="' . $aRow['tax_id_1'] . '">' . $aRow['taxrate_1'] . '%' . '</span>';
        } // end elseif
        elseif ($aColumns[$i] == 't2.taxrate as taxrate_2') {
            if (!$aRow['taxrate_2']) {
                $aRow['taxrate_2'] = 0;
            }
            $_data = '<span data-toggle="tooltip" title="' . $aRow['taxname_2'] . '" data-taxid="' . $aRow['tax_id_2'] . '">' . $aRow['taxrate_2'] . '%' . '</span>';
        } // end elseif
        elseif ($aColumns[$i] == 'description') {
            if ($roleid != 1) {
                $_data = '<a href="#" data-toggle="modal" data-target="#sales_item_modal" data-id="' . $aRow['id'] . '">' . $_data . '</a>';
            } // end if
            else {
                $_data = '<a href="#" onclick="return false;">' . $_data . '</a>';
            } // end else
        } // end elseif

        $row[] = $_data;

    } // end foreach

    $options = '';

    if (has_permission('items', '', 'edit')) {
        if ($roleid != 1) {
            $options .= icon_btn('#' . $aRow['id'], 'pencil-square-o', 'btn-default', array(
                'data-toggle' => 'modal',
                'data-target' => '#sales_item_modal',
                'data-id' => $aRow['id']
            ));
        } // end if
        else {
            $options .= "";
        } // end else
    } // end if has_permissiom


    if (has_permission('items', '', 'delete')) {
        $options .= icon_btn('invoice_items/delete/' . $aRow['id'], 'remove', 'btn-danger _delete');
    }


    $row[] = $options;

    $output['aaData'][] = $row;
}
