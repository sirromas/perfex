<div class="modal fade" id="vaultConfirmPassword" tabindex="-1" role="dialog">
   <div class="modal-dialog" role="document">
      <?php echo form_open(admin_url('clients/vault_encrypt_password')); ?>
      <div class="modal-content">
         <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <h4 class="modal-title"><?php echo _l('view_password'); ?></h4>
         </div>
         <div class="modal-body">
            <p class="bold"><?php echo _l('security_reasons_re_enter_password'); ?></p>
            <?php echo render_input('user_password','','','password'); ?>
            <?php echo form_hidden('id'); ?>
         </div>
         <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('close'); ?></button>
            <button type="submit" class="btn btn-info"><?php echo _l('confirm'); ?></button>
         </div>
      </div>
      <!-- /.modal-content -->
      <?php echo form_close(); ?>
   </div>
   <!-- /.modal-dialog -->
</div>
<!-- /.modal -->
