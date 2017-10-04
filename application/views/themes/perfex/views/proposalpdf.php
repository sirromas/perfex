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

$pdf_logo_url = pdf_logo_url();
$pdf->writeHTMLCell(($dimensions['wk'] - ($dimensions['rm'] + $dimensions['lm'])), '', '', '', $pdf_logo_url, 0, 1, false, true, 'L', true);

$pdf->ln(4);
// Get Y position for the separation
$y = $pdf->getY();
$proposal_info = '<div style="color:#424242;">';
$proposal_info .= format_organization_info();
$proposal_info .= '</div>';

$pdf->writeHTMLCell(($swap == '0' ? (($dimensions['wk'] / 2) - $dimensions['rm']) : ''), '', '', ($swap == '0' ? $y : ''), $proposal_info, 0, 0, false, true, ($swap == '1' ? 'R' : 'J'), true);

$rowcount = max(array(
    $pdf->getNumLines($proposal_info, 80)
));

// Proposal to
$client_details = _l('proposal_to') . ':<br />';
$client_details .= '<div style="color:#424242;">';

$client_details .= '<b>' . $proposal->proposal_to . '</b><br />';

if (! empty($proposal->address)) {
    $client_details .= $proposal->address . '<br />';
}
if (! empty($proposal->city)) {
    $client_details .= $proposal->city;
}
if (! empty($proposal->state)) {
    $client_details .= ', ' . $proposal->state;
}
$country = get_country_short_name($proposal->country);
if (! empty($country)) {
    $client_details .= '<br />' . $country;
}
if (! empty($proposal->zip)) {
    $client_details .= ', ' . $proposal->zip;
}
if (! empty($proposal->email)) {
    $client_details .= '<br />' . $proposal->email;
}
if (! empty($proposal->phone)) {
    $client_details .= '<br />' . $proposal->phone;
}

$client_details .= '</div>';

$pdf->writeHTMLCell(($dimensions['wk'] / 2) - $dimensions['lm'], $rowcount * 7, '', ($swap == '1' ? $y : ''), $client_details, 0, 1, false, true, ($swap == '1' ? 'J' : 'R'), true);

$pdf->ln(6);

$proposal_date = _l('proposal_date') . ': ' . _d($proposal->date);
$open_till = '';

if (! empty($proposal->open_till)) {
    $open_till = _l('proposal_open_till') . ': ' . _d($proposal->open_till);
}

$custom_fields_data = '';

$pdf_custom_fields = get_custom_fields('proposal', array(
    'show_on_pdf' => 1
));
foreach ($pdf_custom_fields as $field) {
    $value = get_custom_field_value($proposal->id, $field['id'], 'proposal');
    if ($value == '') {
        continue;
    }
    $custom_fields_data .= $field['name'] . ': ' . $value . '<br />';
}

// Add new line if found custom fields so the custom field can go on the next line
$custom_fields_data = $custom_fields_data != '' ? '<br />' . $custom_fields_data : $custom_fields_data;

$item_width = 38;
// If show item taxes is disabled in PDF we should increase the item width table heading
$item_width = get_option('show_tax_per_item') == 0 ? $item_width + 15 : $item_width;

// The same language keys from estimates are used here
$qty_heading = _l('estimate_table_quantity_heading');
if ($proposal->show_quantity_as == 2) {
    $qty_heading = _l('estimate_table_hours_heading');
} else 
    if ($proposal->show_quantity_as == 3) {
        $qty_heading = _l('estimate_table_quantity_heading') . '/' . _l('estimate_table_hours_heading');
    }

// Header
$items_html = '<table width="100%" bgcolor="#fff" cellspacing="0" cellpadding="8" border="0">
<tr height="30" bgcolor="' . get_option('pdf_table_heading_color') . '" style="color:' . get_option('pdf_table_heading_text_color') . ';">
    <th width="5%;" align="center">#</th>
    <th width="' . $item_width . '%" align="left">' . _l('estimate_table_item_heading') . '</th>
    <th width="12%" align="right">' . $qty_heading . '</th>
    <th width="15%" align="right">' . _l('estimate_table_rate_heading') . '</th>';
if (get_option('show_tax_per_item') == 1) {
    $items_html .= '<th width="15%" align="right">' . _l('estimate_table_tax_heading') . '</th>';
}
$items_html .= '<th width="15%" align="right">' . _l('estimate_table_amount_heading') . '</th>
</tr>';

// Items
$items_html .= '<tbody>';

$items_data = get_table_items_and_taxes($proposal->items, 'proposal');

$taxes = $items_data['taxes'];
$items_html .= $items_data['html'];

$items_html .= '</tbody>';
$items_html .= '</table>';
$items_html .= '<br /><br />';
$items_html .= '';
$items_html .= '<table cellpadding="6" style="font-size:' . ($font_size + 4) . 'px">';
$items_html .= '
<tr>
    <td align="right" width="85%"><strong>' . _l('estimate_subtotal') . '</strong></td>
    <td align="right" width="15%">' . format_money($proposal->subtotal, $proposal->symbol) . '</td>
</tr>';
if ($proposal->discount_percent != 0) {
    $items_html .= '
    <tr>
        <td align="right" width="85%"><strong>' . _l('estimate_discount') . '(' . _format_number($proposal->discount_percent, true) . '%)' . '</strong></td>
        <td align="right" width="15%">-' . format_money($proposal->discount_total, $proposal->symbol) . '</td>
    </tr>';
}
foreach ($taxes as $tax) {
    $total = array_sum($tax['total']);
    if ($proposal->discount_percent != 0 && $proposal->discount_type == 'before_tax') {
        $total_tax_calculated = ($total * $proposal->discount_percent) / 100;
        $total = ($total - $total_tax_calculated);
    }
    // The tax is in format TAXNAME|20
    $_tax_name = explode('|', $tax['tax_name']);
    $items_html .= '<tr>
    <td align="right" width="85%"><strong>' . $_tax_name[0] . '(' . _format_number($tax['taxrate']) . '%)' . '</strong></td>
    <td align="right" width="15%">' . format_money($total, $proposal->symbol) . '</td>
</tr>';
}

if ((int) $proposal->adjustment != 0) {
    $items_html .= '<tr>
    <td align="right" width="85%"><strong>' . _l('estimate_adjustment') . '</strong></td>
    <td align="right" width="15%">' . format_money($proposal->adjustment, $proposal->symbol) . '</td>
</tr>';
}
$items_html .= '
<tr style="background-color:#f0f0f0;">
    <td align="right" width="85%"><strong>' . _l('estimate_total') . '</strong></td>
    <td align="right" width="15%">' . format_money($proposal->total, $proposal->symbol) . '</td>
</tr>';
$items_html .= '</table>';

if (get_option('total_to_words_enabled') == 1) {
    $items_html .= '<br /><br /><br />';
    $items_html .= '<strong style="text-align:center;">' . _l('num_word') . ': ' . $CI->numberword->convert($proposal->total, $proposal->currency_name) . '</strong>';
}

$proposal->content = str_replace('{proposal_items}', $items_html, $proposal->content);
// Get the proposals css
$css = file_get_contents(FCPATH . 'assets/css/proposals.css');
// Theese lines should aways at the end of the document left side. Dont indent these lines
$html = <<<EOF
<style>
    $css
</style>
<p style="font-size:20px;"># $number
<br /><span style="font-size:15px;">$proposal->subject</span>
</p>
$proposal_date
<br />
$open_till
$custom_fields_data
<br />
<div style="width:675px !important;">
$proposal->content
</div>
EOF;

$pdf->writeHTML($html, true, false, true, false, '');
