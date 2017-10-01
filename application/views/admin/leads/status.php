    <div class="modal fade" id="status" tabindex="-1" role="dialog">
        <div class="modal-dialog">
        <?php echo form_open(admin_url('leads/status'),array('id'=>'leads-status-form')); ?>
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title">
                        <span class="edit-title"><?php echo _l('edit_status'); ?></span>
                        <span class="add-title"><?php echo _l('lead_new_status'); ?></span>
                    </h4>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div id="additional"></div>
                            <?php echo render_input('name','leads_status_add_edit_name'); ?>
                            <?php echo render_color_picker('color',_l('leads_status_color')); ?>
                            <?php echo render_input('statusorder','leads_status_add_edit_order',total_rows('tblleadsstatus') + 1,'number'); ?>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('close'); ?></button>
                    <button type="submit" class="btn btn-info"><?php echo _l('submit'); ?></button>
                </div>
            </div><!-- /.modal-content -->
            <?php echo form_close(); ?>
        </div><!-- /.modal-dialog -->
    </div><!-- /.modal -->
