		<!-- fake fields are a workaround for chrome autofill getting the wrong fields -->
		<input  type="text" class="fake-autofill-field" name="fakeusernameremembered" value=''/>
		<input  type="password" class="fake-autofill-field" name="fakepasswordremembered" value=''/>
		<h4><?php echo _l('settings_smtp_settings_heading'); ?></h4>
		<p class="text-muted"><?php echo _l('settings_smtp_settings_subheading'); ?></p>
		<hr />
		<div class="form-group">
			<label for="email_protocol"><?php echo _l('email_protocol'); ?></label><br />
			<div class="radio radio-inline radio-primary">
				<input type="radio" name="settings[email_protocol]" id="smtp" value="smtp" <?php if(get_option('email_protocol') == 'smtp'){echo 'checked';} ?>>
				<label for="smtp">SMTP</label>
			</div>

			<div class="radio radio-inline radio-primary">
				<input type="radio" name="settings[email_protocol]" id="mail" value="mail" <?php if(get_option('email_protocol') == 'mail'){echo 'checked';} ?>>
				<label for="mail">Mail</label>
			</div>
			<div class="radio radio-inline radio-primary">
				<input type="radio" name="settings[email_protocol]" id="sendmail" value="sendmail" <?php if(get_option('email_protocol') == 'sendmail'){echo 'checked';} ?>>
				<label for="sendmail">Sendmail</label>
			</div>
			<div class="form-group mtop15">
				<label for="smtp_encryption"><?php echo _l('smtp_encryption'); ?></label><br />
				<select name="settings[smtp_encryption]" class="selectpicker" data-width="100%">
					<option value="" <?php if(get_option('smtp_encryption') == ''){echo 'selected';} ?>><?php echo _l('smtp_encryption_none'); ?></option>
					<option value="ssl" <?php if(get_option('smtp_encryption') == 'ssl'){echo 'selected';} ?>>SSL</option>
					<option value="tls" <?php if(get_option('smtp_encryption') == 'tls'){echo 'selected';} ?>>TLS</option>
				</select>
			</div>
		</div>
		<?php echo render_input('settings[smtp_host]','settings_email_host',get_option('smtp_host')); ?>
		<?php echo render_input('settings[smtp_port]','settings_email_port',get_option('smtp_port')); ?>
		<?php echo render_input('settings[smtp_email]','settings_email',get_option('smtp_email')); ?>
		<i class="fa fa-question-circle" data-toggle="tooltip" data-title="<?php echo _l('smtp_username_help'); ?>"></i>
		<?php echo render_input('settings[smtp_username]','smtp_username',get_option('smtp_username')); ?>
		<?php
		$ps = get_option('smtp_password');
		if(!empty($ps)){
			if(false == $this->encryption->decrypt($ps)){
				$ps = $ps;
			} else {
				$ps = $this->encryption->decrypt($ps);
			}
		}
		echo render_input('settings[smtp_password]','settings_email_password',$ps,'password',array('autocomplete'=>'off')); ?>
		<?php echo render_input('settings[smtp_email_charset]','settings_email_charset',get_option('smtp_email_charset')); ?>
		<?php echo render_textarea('settings[email_signature]','settings_email_signature',get_option('email_signature')); ?>
		<hr />
		<?php echo render_textarea('settings[email_header]','email_header',get_option('email_header'),array('rows'=>15)); ?>
		<?php echo render_textarea('settings[email_footer]','email_footer',get_option('email_footer'),array('rows'=>15)); ?>
		<hr />
		<h4><?php echo _l('settings_send_test_email_heading'); ?></h4>
		<p class="text-muted"><?php echo _l('settings_send_test_email_subheading'); ?></p>
		<div class="form-group">
			<div class="input-group">
				<input type="email" class="form-control" name="test_email" data-ays-ignore="true" placeholder="<?php echo _l('settings_send_test_email_string'); ?>">
				<div class="input-group-btn">
					<button type="button" class="btn btn-default test_email p7">Test</button>
				</div>
			</div>
		</div>
