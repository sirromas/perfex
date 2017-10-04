<?php
$customer_tabs = array(
    array(
        'name' => 'profile',
        'url' => admin_url('clients/client/' . $client->userid . '?group=profile'),
        'icon' => 'fa fa-user-circle',
        'lang' => _l('client_add_edit_profile'),
        'visible' => true,
        'order' => 1
    ),
    array(
        'name' => 'notes',
        'url' => admin_url('clients/client/' . $client->userid . '?group=notes'),
        'icon' => 'fa fa-sticky-note-o',
        'lang' => _l('contracts_notes_tab'),
        'visible' => true,
        'order' => 2
    ),
    array(
        'name' => 'statement',
        'url' => admin_url('clients/client/' . $client->userid . '?group=statement'),
        'icon' => 'fa fa-area-chart',
        'lang' => _l('customer_statement'),
        'visible' => (has_permission('invoices', '', 'view') && has_permission('payments', '', 'view')),
        'order' => 3
    ),
    array(
        'name' => 'invoices',
        'url' => admin_url('clients/client/' . $client->userid . '?group=invoices'),
        'icon' => 'fa fa-file-text',
        'lang' => _l('client_invoices_tab'),
        'visible' => (has_permission('invoices', '', 'view') || has_permission('invoices', '', 'view_own')),
        'order' => 4
    ),
    array(
        'name' => 'payments',
        'url' => admin_url('clients/client/' . $client->userid . '?group=payments'),
        'icon' => 'fa fa-line-chart',
        'lang' => _l('client_payments_tab'),
        'visible' => (has_permission('payments', '', 'view') || has_permission('invoices', '', 'view_own')),
        'order' => 5
    ),
    array(
        'name' => 'proposals',
        'url' => admin_url('clients/client/' . $client->userid . '?group=proposals'),
        'icon' => 'fa fa-file-powerpoint-o',
        'lang' => _l('proposals'),
        'visible' => (has_permission('proposals', '', 'view') || has_permission('proposals', '', 'view_own') || (get_option('allow_staff_view_proposals_assigned') == 1 && total_rows('tblproposals', array(
            'assigned' => get_staff_user_id()
        )) > 0)),
        'order' => 6
    ),
    array(
        'name' => 'estimates',
        'url' => admin_url('clients/client/' . $client->userid . '?group=estimates'),
        'icon' => 'fa fa-clipboard',
        'lang' => _l('estimates'),
        'visible' => (has_permission('estimates', '', 'view') || has_permission('estimates', '', 'view_own')),
        'order' => 7
    ),
    array(
        'name' => 'expenses',
        'url' => admin_url('clients/client/' . $client->userid . '?group=expenses'),
        'icon' => 'fa fa-file-text-o',
        'lang' => _l('expenses'),
        'visible' => (has_permission('expenses', '', 'view') || has_permission('expenses', '', 'view_own')),
        'order' => 8
    ),
    array(
        'name' => 'contracts',
        'url' => admin_url('clients/client/' . $client->userid . '?group=contracts'),
        'icon' => 'fa fa-file',
        'lang' => _l('contracts'),
        'visible' => (has_permission('contracts', '', 'view') || has_permission('contracts', '', 'view_own')),
        'order' => 9
    ),
    array(
        'name' => 'projects',
        'url' => admin_url('clients/client/' . $client->userid . '?group=projects'),
        'icon' => 'fa fa-bars',
        'lang' => _l('projects'),
        'visible' => true,
        'order' => 10
    ),
    array(
        'name' => 'tasks',
        'url' => admin_url('clients/client/' . $client->userid . '?group=tasks'),
        'icon' => 'fa fa-tasks',
        'lang' => _l('tasks'),
        'visible' => true,
        'order' => 11
    ),
    array(
        'name' => 'tickets',
        'url' => admin_url('clients/client/' . $client->userid . '?group=tickets'),
        'icon' => 'fa fa-ticket',
        'lang' => _l('tickets'),
        'visible' => ((get_option('access_tickets_to_none_staff_members') == 1 && ! is_staff_member()) || is_staff_member()),
        'order' => 12
    ),
    array(
        'name' => 'attachments',
        'url' => admin_url('clients/client/' . $client->userid . '?group=attachments'),
        'icon' => 'fa fa-paperclip',
        'lang' => _l('customer_attachments'),
        'visible' => true,
        'order' => 13
    ),
    array(
        'name' => 'vault',
        'url' => admin_url('clients/client/' . $client->userid . '?group=vault'),
        'icon' => 'fa fa-lock',
        'lang' => _l('vault'),
        'visible' => true,
        'order' => 14
    ),
    array(
        'name' => 'reminders',
        'url' => admin_url('clients/client/' . $client->userid . '?group=reminders'),
        'icon' => 'fa fa-clock-o',
        'lang' => _l('client_reminders_tab'),
        'visible' => true,
        'order' => 15,
        'id' => 'reminders'
    ),
    array(
        'name' => 'map',
        'url' => admin_url('clients/client/' . $client->userid . '?group=map'),
        'icon' => 'fa fa-map-marker',
        'lang' => _l('customer_map'),
        'visible' => true,
        'order' => 16
    )
)
;

$hook_data = do_action('customer_profile_tabs', array(
    'tabs' => $customer_tabs,
    'client' => $client
));
$customer_tabs = $hook_data['tabs'];

usort($customer_tabs, function ($a, $b)
{
    return $a['order'] - $b['order'];
});

?>
<ul class="nav navbar-pills nav-tabs nav-stacked" role="tablist">
   <?php

foreach ($customer_tabs as $tab) {
    if ((isset($tab['visible']) && $tab['visible'] == true) || ! isset($tab['visible'])) {
        ?>
      <li class="<?php if($tab['name'] == 'profile'){echo 'active';} ?>">
		<a data-group="<?php echo $tab['name']; ?>"
		href="<?php echo $tab['url']; ?>"><i
			class="<?php echo $tab['icon']; ?> menu-icon" aria-hidden="true"></i><?php echo $tab['lang']; ?>
            <?php
        
if (isset($tab['id']) && $tab['id'] == 'reminders') {
            $total_reminders = total_rows('tblreminders', array(
                'isnotified' => 0,
                'staff' => get_staff_user_id(),
                'rel_type' => 'customer',
                'rel_id' => $client->userid
            ));
            if ($total_reminders > 0) {
                echo '<span class="badge">' . $total_reminders . '</span>';
            }
        }
        ?>
      </a>
	</li>
  <?php } ?>
  <?php } ?>
</ul>
