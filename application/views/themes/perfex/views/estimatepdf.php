<?php
$dimensions = $pdf->getPageDimensions();

if ($tag != '') {
    $pdf->SetFillColor(240, 240, 240);
    $pdf->SetDrawColor(245, 245, 245);
    $pdf->SetXY(0, 0);
    $pdf->SetFont($font_name, 'B', 15);
    $pdf->SetTextColor(0);
    $pdf->SetLineWidth(0.75);
    $pdf->StartTransform();
    $pdf->Rotate(- 35, 109, 235);
    $pdf->Cell(100, 1, mb_strtoupper($tag, 'UTF-8'), 'TB', 0, 'C', '1');
    $pdf->StopTransform();
    $pdf->SetFont($font_name, '', $font_size);
    $pdf->setX(10);
    $pdf->setY(10);
}

$info_right_column = '';
$info_left_column = '';

$info_right_column .= '<span style="font-weight:bold;font-size:27px;">' . _l('estimate_pdf_heading') . '</span><br />';
$info_right_column .= '<b style="color:#4e4e4e;"># ' . $estimate_number . '</b>';

if (get_option('show_status_on_pdf_ei') == 1) {
    $info_right_column .= '<br /><span style="color:rgb(' . estimate_status_color_pdf($status) . ');text-transform:uppercase;">' . format_estimate_status($status, '', false) . '</span>';
}

// write the first column
$info_left_column .= pdf_logo_url();
$pdf->MultiCell(($dimensions['wk'] / 2) - $dimensions['lm'], 0, $info_left_column, 0, 'J', 0, 0, '', '', true, 0, true, true, 0);
// write the second column
$pdf->MultiCell(($dimensions['wk'] / 2) - $dimensions['rm'], 0, $info_right_column, 0, 'R', 0, 1, '', '', true, 0, true, false, 0);
$pdf->ln(6);

// Get Y position for the separation
$y = $pdf->getY();
$estimate_info = '<div style="color:#424242;">';
$estimate_info .= format_organization_info();
$estimate_info .= '</div>';

$pdf->writeHTMLCell(($swap == '1' ? ($dimensions['wk']) - ($dimensions['lm'] * 2) : ($dimensions['wk'] / 2) - $dimensions['lm']), '', '', $y, $estimate_info, 0, 0, false, true, ($swap == '1' ? 'R' : 'J'), true);

// Estimate to
$client_details = '<b>' . _l('estimate_to') . '</b><br />';
$client_details .= '<div style="color:#424242;">';
$client_details .= format_customer_info($estimate, 'estimate', 'billing');
$client_details .= '</div>';

$pdf->writeHTMLCell(($dimensions['wk'] / 2) - $dimensions['rm'], '', '', ($swap == '1' ? $y : ''), $client_details, 0, 1, false, true, ($swap == '1' ? 'J' : 'R'), true);

$pdf->Ln(5);
// ship to to
if ($estimate->include_shipping == 1 && $estimate->show_shipping_on_estimate == 1) {
    $shipping_details = '<b>' . _l('ship_to') . '</b><br />';
    $shipping_details .= '<div style="color:#424242;">';
    $shipping_details .= format_customer_info($estimate, 'estimate', 'shipping');
    $pdf->writeHTMLCell(($dimensions['wk'] - ($dimensions['rm'] + $dimensions['lm'])), '', '', '', $shipping_details, 0, 1, false, true, ($swap == '1' ? 'L' : 'R'), true);
    $pdf->Ln(5);
}
// Dates
$pdf->Cell(0, 0, _l('estimate_data_date') . ': ' . _d($estimate->date), 0, 1, ($swap == '1' ? 'L' : 'R'), 0, '', 0);

if (! empty($estimate->expirydate)) {
    $pdf->Cell(0, 0, _l('estimate_data_expiry_date') . ': ' . _d($estimate->expirydate), 0, 1, ($swap == '1' ? 'L' : 'R'), 0, '', 0);
}

if (! empty($estimate->reference_no)) {
    $pdf->Cell(0, 0, _l('reference_no') . ': ' . $estimate->reference_no, 0, 1, ($swap == '1' ? 'L' : 'R'), 0, '', 0);
}

if ($estimate->sale_agent != 0 && get_option('show_sale_agent_on_estimates') == 1) {
    $pdf->Cell(0, 0, _l('sale_agent_string') . ': ' . get_staff_full_name($estimate->sale_agent), 0, 1, ($swap == '1' ? 'L' : 'R'), 0, '', 0);
}
// check for estimate custom fields which is checked show on pdf
$pdf_custom_fields = get_custom_fields('estimate', array(
    'show_on_pdf' => 1
));
foreach ($pdf_custom_fields as $field) {
    $value = get_custom_field_value($estimate->id, $field['id'], 'estimate');
    if ($value == '') {
        continue;
    }
    $pdf->writeHTMLCell(0, '', '', '', $field['name'] . ': ' . $value, 0, 1, false, true, ($swap == '1' ? 'J' : 'R'), true);
}
// The Table
$pdf->Ln(7);
$item_width = 38;
// If show item taxes is disabled in PDF we should increase the item width table heading
$item_width = get_option('show_tax_per_item') == 0 ? $item_width + 15 : $item_width;

$qty_heading = _l('estimate_table_quantity_heading');
if ($estimate->show_quantity_as == 2) {
    $qty_heading = _l('estimate_table_hours_heading');
} else 
    if ($estimate->show_quantity_as == 3) {
        $qty_heading = _l('estimate_table_quantity_heading') . '/' . _l('estimate_table_hours_heading');
    }

// Header
$tblhtml = '<table width="100%" bgcolor="#fff" cellspacing="0" cellpadding="8" border="0">
<tr height="30" bgcolor="' . get_option('pdf_table_heading_color') . '" style="color:' . get_option('pdf_table_heading_text_color') . ';">
    <th width="5%;" align="center">#</th>
    <th width="' . $item_width . '%" align="left">' . _l('estimate_table_item_heading') . '</th>
    <th width="12%" align="right">' . $qty_heading . '</th>
    <th width="15%" align="right">' . _l('estimate_table_rate_heading') . '</th>';
if (get_option('show_tax_per_item') == 1) {
    $tblhtml .= '<th width="15%" align="right">' . _l('estimate_table_tax_heading') . '</th>';
}
$tblhtml .= '<th width="15%" align="right">' . _l('estimate_table_amount_heading') . '</th>
</tr>';
// Items

$tblhtml .= '<tbody>';

$items_data = get_table_items_and_taxes($estimate->items, 'estimate');

$tblhtml .= $items_data['html'];
$taxes = $items_data['taxes'];

$tblhtml .= '</tbody>';
$tblhtml .= '</table>';

$pdf->writeHTML($tblhtml, true, false, false, false, '');

$pdf->Ln(8);
$tbltotal = '';
$tbltotal .= '<table cellpadding="6" style="font-size:' . ($font_size + 4) . 'px">';
$tbltotal .= '
<tr>
    <td align="right" width="85%"><strong>' . _l('estimate_subtotal') . '</strong></td>
    <td align="right" width="15%">' . format_money($estimate->subtotal, $estimate->symbol) . '</td>
</tr>';

if ($estimate->discount_percent != 0) {
    $tbltotal .= '
    <tr>
        <td align="right" width="85%"><strong>' . _l('estimate_discount') . '(' . _format_number($estimate->discount_percent, true) . '%)' . '</strong></td>
        <td align="right" width="15%">-' . format_money($estimate->discount_total, $estimate->symbol) . '</td>
    </tr>';
}

foreach ($taxes as $tax) {
    $total = array_sum($tax['total']);
    if ($estimate->discount_percent != 0 && $estimate->discount_type == 'before_tax') {
        $total_tax_calculated = ($total * $estimate->discount_percent) / 100;
        $total = ($total - $total_tax_calculated);
    }
    // The tax is in format TAXNAME|20
    $_tax_name = explode('|', $tax['tax_name']);
    $tbltotal .= '<tr>
    <td align="right" width="85%"><strong>' . $_tax_name[0] . '(' . _format_number($tax['taxrate']) . '%)' . '</strong></td>
    <td align="right" width="15%">' . format_money($total, $estimate->symbol) . '</td>
</tr>';
}
if ((int) $estimate->adjustment != 0) {
    $tbltotal .= '<tr>
    <td align="right" width="85%"><strong>' . _l('estimate_adjustment') . '</strong></td>
    <td align="right" width="15%">' . format_money($estimate->adjustment, $estimate->symbol) . '</td>
</tr>';
}

$tbltotal .= '
<tr style="background-color:#f0f0f0;">
    <td align="right" width="85%"><strong>' . _l('estimate_total') . '</strong></td>
    <td align="right" width="15%">' . format_money($estimate->total, $estimate->symbol) . '</td>
</tr>';

$tbltotal .= '</table>';

$pdf->writeHTML($tbltotal, true, false, false, false, '');

if (get_option('total_to_words_enabled') == 1) {
    // Set the font bold
    $pdf->SetFont($font_name, 'B', $font_size);
    $pdf->Cell(0, 0, _l('num_word') . ': ' . $CI->numberword->convert($estimate->total, $estimate->currency_name), 0, 1, 'C', 0, '', 0);
    // Set the font again to normal like the rest of the pdf
    $pdf->SetFont($font_name, '', $font_size);
    $pdf->Ln(4);
}

if (! empty($estimate->clientnote)) {
    $pdf->Ln(4);
    $pdf->SetFont($font_name, 'B', $font_size);
    $pdf->Cell(0, 0, _l('estimate_note'), 0, 1, 'L', 0, '', 0);
    $pdf->SetFont($font_name, '', $font_size);
    $pdf->Ln(2);
    $pdf->writeHTMLCell('', '', '', '', $estimate->clientnote, 0, 1, false, true, 'L', true);
}

if (! empty($estimate->terms)) {
    $pdf->Ln(4);
    $pdf->SetFont($font_name, 'B', $font_size);
    $pdf->Cell(0, 0, _l('terms_and_conditions'), 0, 1, 'L', 0, '', 0);
    $pdf->SetFont($font_name, '', $font_size);
    $pdf->Ln(2);
    $pdf->writeHTMLCell('', '', '', '', $estimate->terms, 0, 1, false, true, 'L', true);
}
