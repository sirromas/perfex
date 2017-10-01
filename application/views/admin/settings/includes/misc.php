    <ul class="nav nav-tabs" role="tablist">
      <li role="presentation" class="active">
        <a href="#misc" aria-controls="misc" role="tab" data-toggle="tab"><?php echo _l('settings_group_misc'); ?></a>
      </li>
      <li role="presentation">
        <a href="#set_newsfeed" aria-controls="set_newsfeed" role="tab" data-toggle="tab"><?php echo _l('settings_group_newsfeed'); ?></a>
      </li>
      <li role="presentation">
        <a href="#set_recaptcha" aria-controls="set_recaptcha" role="tab" data-toggle="tab"><?php echo _l('re_captcha'); ?></a>
      </li>
    </ul>

    <div class="tab-content mtop30">
      <div role="tabpanel" class="tab-pane active" id="misc">
        <?php echo render_input('settings[google_api_key]','settings_google_api',get_option('google_api_key')); ?>
        <hr />
        <?php echo render_input('settings[dropbox_app_key]','dropbox_app_key',get_option('dropbox_app_key')); ?>
        <hr />
        <?php echo render_input('settings[tables_pagination_limit]','settings_general_tables_limit',get_option('tables_pagination_limit'),'number'); ?>
        <hr />
        <i class="fa fa-question-circle" data-toggle="tooltip" data-title="<?php echo _l('scroll_responsive_tables_help'); ?>"></i>
        <?php echo render_yes_no_option('scroll_responsive_tables','scroll_responsive_tables'); ?>
        <hr />
        <i class="fa fa-question-circle" data-toggle="tooltip" data-title="<?php echo _l('text_not_recommended_for_servers_limited_resources'); ?>"></i>
        <?php
        $params = array();
        if(get_option('pusher_realtime_notifications') == 1){
          $params['disabled'] = true;
        }

        echo render_input('settings[auto_check_for_new_notifications]','auto_check_for_new_notifications',get_option('auto_check_for_new_notifications'),'number',$params); ?>
        <hr />
        <?php echo render_input('settings[media_max_file_size_upload]','settings_media_max_file_size_upload',get_option('media_max_file_size_upload'),'number'); ?>
        <hr />

        <?php echo render_input('settings[limit_top_search_bar_results_to]','settings_limit_top_search_bar_results',get_option('limit_top_search_bar_results_to'),'number'); ?>
        <hr />
        <?php echo render_select('settings[default_staff_role]',$roles,array('roleid','name'),'settings_general_default_staff_role',get_option('default_staff_role'),array(),array('data-toggle'=>'tooltip','title'=>'settings_general_default_staff_role_tooltip')); ?>
        <hr />
        <?php echo render_input('settings[delete_activity_log_older_then]','delete_activity_log_older_then',get_option('delete_activity_log_older_then'),'number'); ?>
        <hr />
        <?php echo render_yes_no_option('show_setup_menu_item_only_on_hover','show_setup_menu_item_only_on_hover'); ?>
        <hr />
        <div class="form-group">
          <label><?php echo _l('show_table_export_button'); ?></label><br />
          <div class="radio radio-primary">
            <input type="radio" id="stbxb_all" name="settings[show_table_export_button]" value="to_all"<?php if(get_option('show_table_export_button') == 'to_all'){echo ' checked';} ?>>
            <label for="stbxb_all"><?php echo _l('show_table_export_all'); ?></label>
          </div>

          <div class="radio radio-primary">
            <input type="radio" id="stbxb_admins" name="settings[show_table_export_button]" value="only_admins"<?php if(get_option('show_table_export_button') == 'only_admins'){echo ' checked';} ?>>
            <label for="stbxb_admins"><?php echo _l('show_table_export_admins'); ?></label>

          </div>
          <div class="radio radio-primary">
            <input type="radio" id="stbxb_hide" name="settings[show_table_export_button]" value="hide"<?php if(get_option('show_table_export_button') == 'hide'){echo ' checked';} ?>>
            <label for="stbxb_hide"><?php echo _l('show_table_export_hide'); ?></label>
          </div>
        </div>
        <hr />
        <?php echo render_yes_no_option('hide_notified_reminders_from_calendar','hide_notified_reminders_from_calendar'); ?>
        <hr />
        <?php echo render_yes_no_option('show_help_on_setup_menu','show_help_on_setup_menu'); ?>
        <hr />
        <?php render_yes_no_option('use_minified_files','use_minified_files'); ?>
      </div>
      <div role="tabpanel" class="tab-pane" id="set_newsfeed">
       <?php echo render_input('settings[newsfeed_maximum_files_upload]','settings_newsfeed_max_file_upload_post',get_option('newsfeed_maximum_files_upload'),'number'); ?>
       <hr />
       <?php echo render_input('settings[newsfeed_maximum_file_size]','settings_newsfeed_max_file_size',get_option('newsfeed_maximum_file_size'),'number'); ?>
     </div>
     <div role="tabpanel" class="tab-pane" id="set_recaptcha">
       <?php echo render_input('settings[recaptcha_site_key]','recaptcha_site_key',get_option('recaptcha_site_key')); ?>
       <?php echo render_input('settings[recaptcha_secret_key]','recaptcha_secret_key',get_option('recaptcha_secret_key')); ?>
       <hr />
       <?php echo render_yes_no_option('use_recaptcha_customers_area','use_recaptcha_customers_area'); ?>
     </div>
   </div>
