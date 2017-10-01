<div class="mtop40">
  <div class="row mbot15">
    <div class="col-md-4 col-md-offset-4 text-center">
      <h1 class="text-uppercase"><?php echo _l('customer_forgot_password_heading'); ?></h1>
    </div>
  </div>
  <div class="col-md-4 col-md-offset-4">
    <div class="bgwhite p15">
      <?php echo form_open($this->uri->uri_string()); ?>
      <?php echo validation_errors('<div class="alert alert-danger text-center">', '</div>'); ?>
      <?php if($this->session->flashdata('message-danger')){ ?>
        <div class="alert alert-danger">
          <?php echo $this->session->flashdata('message-danger'); ?>
        </div>
        <?php } ?>
        <?php echo render_input('email','customer_forgot_password_email','','email'); ?>
        <div class="form-group">
          <button type="submit" class="btn btn-info btn-block"><?php echo _l('customer_forgot_password_submit'); ?></button>
        </div>
        <?php echo form_close(); ?>
      </div>
    </div>
  </div>
