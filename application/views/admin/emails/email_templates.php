<?php init_head(); ?>
<div id="wrapper">
<div class="content email-templates">
    <div class="row">
        <div class="col-md-12">
            <div class="panel_s">
                <div class="panel-body">
                    <div class="row">
                        <div class="col-md-12">
                        <h4 class="no-margin"><?php echo _l('email_templates'); ?></h3>
                           <hr class="hr-panel-heading" />
                            <h4 class="bold well email-template-heading"><?php echo _l('email_template_ticket_fields_heading'); ?></h4>
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th><?php echo _l('email_templates_table_heading_name'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($tickets as $ticket_template){ ?>
                                        <tr>
                                            <td class="<?php if($ticket_template['active'] == 0){echo 'text-throught';} ?>">
                                                <a href="<?php echo admin_url('emails/email_template/'.$ticket_template['emailtemplateid']); ?>"><?php echo $ticket_template['name']; ?></a>
                                                <?php if(ENVIRONMENT !== 'production'){ ?>
                                                    <br/><small><?php echo $ticket_template['slug']; ?></small>
                                                <?php } ?>
                                            </td>
                                        </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                            </div>
                            <div class="col-md-12">
                            <h4 class="bold well email-template-heading"><?php echo _l('estimates'); ?></h4>
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th><?php echo _l('email_templates_table_heading_name'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($estimate as $estimate_template){ ?>
                                        <tr>
                                            <td class="<?php if($estimate_template['active'] == 0){echo 'text-throught';} ?>">
                                                <a href="<?php echo admin_url('emails/email_template/'.$estimate_template['emailtemplateid']); ?>"><?php echo $estimate_template['name']; ?></a>
                                                  <?php if(ENVIRONMENT !== 'production'){ ?>
                                                    <br/><small><?php echo $estimate_template['slug']; ?></small>
                                                <?php } ?>
                                            </td>
                                        </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                            </div>
                            <div class="clearfix"></div>
                            <div class="col-md-12">
                            <h4 class="bold well email-template-heading"><?php echo _l('email_template_contracts_fields_heading'); ?></h4>
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th><?php echo _l('email_templates_table_heading_name'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($contracts as $contract_template){ ?>
                                        <tr>
                                            <td class="<?php if($contract_template['active'] == 0){echo 'text-throught';} ?>">
                                                <a href="<?php echo admin_url('emails/email_template/'.$contract_template['emailtemplateid']); ?>"><?php echo $contract_template['name']; ?></a>
                                                  <?php if(ENVIRONMENT !== 'production'){ ?>
                                                    <br/><small><?php echo $contract_template['slug']; ?></small>
                                                <?php } ?>
                                            </td>
                                        </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                            </div>
                            <div class="col-md-12">
                            <h4 class="bold well email-template-heading"><?php echo _l('email_template_invoices_fields_heading'); ?></h4>
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th><?php echo _l('email_templates_table_heading_name'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($invoice as $invoice_template){ ?>
                                        <tr>
                                            <td class="<?php if($invoice_template['active'] == 0){echo 'text-throught';} ?>">
                                                <a href="<?php echo admin_url('emails/email_template/'.$invoice_template['emailtemplateid']); ?>"><?php echo $invoice_template['name']; ?></a>
                                                  <?php if(ENVIRONMENT !== 'production'){ ?>
                                                    <br/><small><?php echo $invoice_template['slug']; ?></small>
                                                <?php } ?>
                                            </td>
                                        </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                            </div>
                            <div class="clearfix"></div>
                            <div class="col-md-12">
                            <h4 class="bold well email-template-heading"><?php echo _l('tasks'); ?></h4>
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th><?php echo _l('email_templates_table_heading_name'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($tasks as $task_template){ ?>
                                        <tr>
                                            <td class="<?php if($task_template['active'] == 0){echo 'text-throught';} ?>">
                                                <a href="<?php echo admin_url('emails/email_template/'.$task_template['emailtemplateid']); ?>"><?php echo $task_template['name']; ?></a>
                                                  <?php if(ENVIRONMENT !== 'production'){ ?>
                                                    <br/><small><?php echo $task_template['slug']; ?></small>
                                                <?php } ?>
                                            </td>
                                        </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                            </div>
                            <div class="col-md-12">
                            <h4 class="bold well email-template-heading"><?php echo _l('email_template_clients_fields_heading'); ?></h4>
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th><?php echo _l('email_templates_table_heading_name'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($client as $client_template){ ?>
                                        <tr>
                                            <td class="<?php if($client_template['active'] == 0){echo 'text-throught';} ?>">
                                                <a href="<?php echo admin_url('emails/email_template/'.$client_template['emailtemplateid']); ?>"><?php echo $client_template['name']; ?></a>
                                                  <?php if(ENVIRONMENT !== 'production'){ ?>
                                                    <br/><small><?php echo $client_template['slug']; ?></small>
                                                <?php } ?>
                                            </td>
                                        </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                            </div>
                            <div class="clearfix"></div>
                            <div class="col-md-12">
                            <h4 class="bold well email-template-heading"><?php echo _l('email_template_proposals_fields_heading'); ?></h4>
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th><?php echo _l('email_templates_table_heading_name'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($proposals as $proposal_template){ ?>
                                        <tr>
                                            <td class="<?php if($proposal_template['active'] == 0){echo 'text-throught';} ?>">
                                                <a href="<?php echo admin_url('emails/email_template/'.$proposal_template['emailtemplateid']); ?>"><?php echo $proposal_template['name']; ?></a>
                                                  <?php if(ENVIRONMENT !== 'production'){ ?>
                                                    <br/><small><?php echo $proposal_template['slug']; ?></small>
                                                <?php } ?>
                                            </td>
                                        </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                            </div>
                            <div class="col-md-12">
                            <h4 class="bold well email-template-heading"><?php echo _l('projects'); ?></h4>
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th><?php echo _l('email_templates_table_heading_name'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($projects as $project_template){ ?>
                                            <tr>
                                                <td class="<?php if($project_template['active'] == 0){echo 'text-throught';} ?>">
                                                    <a href="<?php echo admin_url('emails/email_template/'.$project_template['emailtemplateid']); ?>"><?php echo $project_template['name']; ?></a>
                                                      <?php if(ENVIRONMENT !== 'production'){ ?>
                                                    <br/><small><?php echo $project_template['slug']; ?></small>
                                                <?php } ?>
                                                </td>
                                            </tr>
                                            <?php } ?>
                                        </tbody>
                                    </table>
                                </div>
                        </div>
                        <div class="col-md-12">
                            <h4 class="bold well email-template-heading"><?php echo _l('staff_members'); ?></h4>
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th><?php echo _l('email_templates_table_heading_name'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($staff as $staff_template){ ?>
                                            <tr>
                                                <td class="<?php if($staff_template['active'] == 0){echo 'text-throught';} ?>">
                                                    <a href="<?php echo admin_url('emails/email_template/'.$staff_template['emailtemplateid']); ?>"><?php echo $staff_template['name']; ?></a>
                                                      <?php if(ENVIRONMENT !== 'production'){ ?>
                                                    <br/><small><?php echo $staff_template['slug']; ?></small>
                                                <?php } ?>
                                                </td>
                                            </tr>
                                            <?php } ?>
                                        </tbody>
                                    </table>
                                </div>
                        </div>
                         <div class="col-md-12">
                            <h4 class="bold well email-template-heading"><?php echo _l('leads'); ?></h4>
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th><?php echo _l('email_templates_table_heading_name'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($leads as $lead_template){ ?>
                                            <tr>
                                                <td class="<?php if($lead_template['active'] == 0){echo 'text-throught';} ?>">
                                                    <a href="<?php echo admin_url('emails/email_template/'.$lead_template['emailtemplateid']); ?>"><?php echo $lead_template['name']; ?></a>
                                                      <?php if(ENVIRONMENT !== 'production'){ ?>
                                                    <br/><small><?php echo $lead_template['slug']; ?></small>
                                                <?php } ?>
                                                </td>
                                            </tr>
                                            <?php } ?>
                                        </tbody>
                                    </table>
                                </div>
                        </div>
                        <div class="clearfix"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
</body>
</html>
