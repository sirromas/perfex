<?php echo form_hidden('settings[customer_settings]','true'); ?>
<div class="form-group">
	<label for="clients_default_theme" class="control-label"><?php echo _l('settings_clients_default_theme'); ?></label>
	<select name="settings[clients_default_theme]"
		id="clients_default_theme" class="form-control selectpicker"
		data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>">
		<?php foreach(get_all_client_themes() as $theme){ ?>
		<option value="<?php echo $theme; ?>"
			<?php if(active_clients_theme() == $theme){echo 'selected';} ?>><?php echo ucfirst($theme); ?></option>
		<?php } ?>
	</select>
</div>
<hr />
<?php echo render_select( 'settings[customer_default_country]',get_all_countries(),array( 'country_id',array( 'short_name')), 'customer_default_country',get_option('customer_default_country')); ?>
<hr />
<?php render_yes_no_option('company_is_required','company_is_required'); ?>
<hr />
<?php render_yes_no_option('company_requires_vat_number_field','company_requires_vat_number_field'); ?>
<hr />
<?php render_yes_no_option('allow_registration','settings_clients_allow_registration'); ?>
<hr />
<?php render_yes_no_option('allow_primary_contact_to_view_edit_billing_and_shipping','allow_primary_contact_to_view_edit_billing_and_shipping'); ?>
<hr />
<i class="fa fa-question-circle" data-toggle="tooltip"
	data-title="<?php echo _l('only_own_files_contacts_help'); ?>"></i>
<?php render_yes_no_option('only_own_files_contacts','only_own_files_contacts'); ?>
<hr />
<?php render_yes_no_option('allow_contact_to_delete_files','allow_contact_to_delete_files'); ?>
<hr />
<i class="fa fa-question-circle" data-toggle="tooltip"
	data-title="<?php echo _l('settings_general_use_knowledgebase_tooltip'); ?>"></i>
<?php render_yes_no_option('use_knowledge_base','settings_general_use_knowledgebase'); ?>
<hr />
<?php render_yes_no_option('knowledge_base_without_registration','settings_clients_allow_kb_view_without_registration'); ?>
<hr />
<?php $default_contact_permissions = unserialize(get_option('default_contact_permissions')); ?>
<div class="form-group">
	<label for="" class="control-label"><?php echo _l('default_contact_permissions'); ?></label>
	<?php foreach($contacts_permissions as $p){ ?>
	<div class="checkbox checkbox-primary">
		<input type="checkbox" name="settings[default_contact_permissions][]"
			<?php if(is_array($default_contact_permissions) && in_array($p['id'],$default_contact_permissions)){echo 'checked';} ?>
			id="dcp_<?php echo $p['id']; ?>" value="<?php echo $p['id']; ?>"> <label
			for="dcp_<?php echo $p['id']; ?>"><?php echo $p['name']; ?></label>
	</div>
	<?php } ?>
</div>
<hr />
<i class="fa fa-question-circle" data-toggle="tooltip"
	data-title="<?php echo _l('invoices').', '._l('estimates').', '._l('payments').', '._l('customer_statement'); ?>"></i>
<?php echo render_textarea('settings[customer_info_format]','customer_info_format',clear_textarea_breaks(get_option('customer_info_format')),array('rows'=>8,'style'=>'line-height:20px;')); ?>
<p>
	<a href="#" class="settings-textarea-merge-field"
		data-to="customer_info_format">{company_name}</a>, <a href="#"
		class="settings-textarea-merge-field" data-to="customer_info_format">{street}</a>,
	<a href="#" class="settings-textarea-merge-field"
		data-to="customer_info_format">{city}</a>, <a href="#"
		class="settings-textarea-merge-field" data-to="customer_info_format">{state}</a>,
	<a href="#" class="settings-textarea-merge-field"
		data-to="customer_info_format">{zip_code}</a>, <a href="#"
		class="settings-textarea-merge-field" data-to="customer_info_format">{country_code}</a>,
	<a href="#" class="settings-textarea-merge-field"
		data-to="customer_info_format">{country_name}</a>, <a href="#"
		class="settings-textarea-merge-field" data-to="customer_info_format">{phone}</a>,
	<a href="#" class="settings-textarea-merge-field"
		data-to="customer_info_format">{vat_number}</a>, <a href="#"
		class="settings-textarea-merge-field" data-to="customer_info_format">{vat_number_with_label}</a>

</p>
<?php

$custom_fields = get_custom_fields('customers');
if (count($custom_fields) > 0) {
    echo '<hr />';
    echo '<p><b>' . _l('custom_fields') . '</b></p>';
    echo '<ul class="list-group">';
    foreach ($custom_fields as $field) {
        echo '<li class="list-group-item"><b>' . $field['name'] . '</b>: ' . '<a href="#" class="settings-textarea-merge-field" data-to="customer_info_format">{cf_' . $field['id'] . '}</a></li>';
	}
	echo '</ul>';
	echo '<hr />';
}
?>
